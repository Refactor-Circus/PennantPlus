<?php

declare(strict_types=1);

namespace JayI\PennantPlus;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Grammars\MySqlGrammar;
use Illuminate\Database\Query\Grammars\PostgresGrammar;
use Illuminate\Database\Query\Grammars\SqlServerGrammar;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Pennant\Drivers\Decorator;
use Laravel\Pennant\Feature;
use ReflectionClass;
use stdClass;
use Symfony\Component\Finder\Finder;

/**
 * Reads the feature flag values Pennant has stored.
 *
 * Pennant's API resolves one feature for one scope at a time and cannot list
 * what it has stored, so this reads the database table directly.
 * Writes go through Pennant itself, in the feature flag actions.
 */
class FeatureFlagManager
{
    /**
     * The scope filter matching values stored for the null scope.
     */
    public const string GLOBAL = 'global';

    /**
     * The scope filter matching plain string scopes that are not models.
     */
    public const string OTHER = 'other';

    /**
     * The Pennant store being managed, null for Pennant's default.
     */
    public function storeName(): ?string
    {
        $store = config('pennantplus.store');

        return is_string($store) && $store !== '' ? $store : null;
    }

    public function store(): Decorator
    {
        return Feature::store($this->storeName());
    }

    /**
     * Whether the managed store keeps its values somewhere they can be listed:
     * the `database` driver, or the `pennantplus` driver built on it.
     */
    public function supportsListing(): bool
    {
        return in_array($this->storeConfig()['driver'], ['database', 'pennantplus'], true);
    }

    /**
     * Whether setting a feature's global value forgets the values stored for
     * every other scope, so each scope resolves afresh.
     */
    public function purgesScopesOnGlobalUpdate(): bool
    {
        return (bool) config('pennantplus.purge_scopes_on_global_update', true)
            && $this->supportsListing();
    }

    /**
     * Forget a feature's stored values for every scope but the global one.
     */
    public function forgetScopedValues(string $feature): void
    {
        $this->query()
            ->where('name', $feature)
            ->where('scope', '!=', Feature::serializeScope(null))
            ->delete();

        $this->store()->flushCache();
    }

    /**
     * Stored values, newest first, narrowed by the given filters.
     *
     * The scope filter is `global`, `other`, or a model type as Pennant
     * stores it: the class name, or the morph alias with `useMorphMap()`.
     *
     * @param  array{feature?: string|null, scope?: string|null, scope_id?: string|null}  $filters
     * @return LengthAwarePaginator<int, StoredFeatureValue>
     */
    public function paginate(array $filters = [], int $perPage = 25, ?int $page = null): LengthAwarePaginator
    {
        $query = $this->query()->orderByDesc('updated_at')->orderBy('name')->orderBy('scope');

        $feature = $filters['feature'] ?? null;

        if (is_string($feature) && $feature !== '') {
            $query->where('name', $feature);
        }

        $this->applyScopeFilter($query, $filters['scope'] ?? null, $filters['scope_id'] ?? null);

        $values = $query->paginate($perPage, page: $page)
            ->through(fn (stdClass $row): StoredFeatureValue => StoredFeatureValue::fromRow($row));

        $this->attachTitles($values->items());

        return $values;
    }

    /**
     * Feature names that are defined, discoverable, or have a stored value.
     *
     * @return array<int, string>
     */
    public function features(): array
    {
        $this->discoverFeatures();

        $stored = $this->supportsListing()
            ? $this->query()->distinct()->orderBy('name')->pluck('name')->all()
            : [];

        $names = array_unique([...$this->store()->defined(), ...$stored]);

        sort($names);

        return array_values(array_filter($names, is_string(...)));
    }

    /**
     * Define every class-based feature in the `pennantplus.features`
     * directories with Pennant, so features are listed before anything has
     * checked them.
     *
     * Pennant's own `discover()` takes a single namespace for a single flat
     * directory. This reads each file's namespace instead, so one glob can
     * cover features spread across many directories.
     */
    public function discoverFeatures(): void
    {
        $store = $this->store();

        foreach ($this->featureClasses() as $class) {
            $store->define($class);
        }
    }

    /**
     * The models values can be scoped to, keyed by class.
     *
     * @return array<class-string, ScopeModel>
     */
    public function scopeModels(): array
    {
        $configured = config('pennantplus.scopes', []);

        $models = [];

        foreach (is_array($configured) ? $configured : [] as $class => $options) {
            if (is_int($class) && is_string($options)) {
                [$class, $options] = [$options, []];
            }

            if (! is_string($class) || ! is_array($options)) {
                continue;
            }

            /** @var array{label?: string, search?: array<int, string>, title?: string} $options */
            $scope = ScopeModel::fromConfig($class, $options);

            $models[$scope->class] = $scope;
        }

        return $models;
    }

    /**
     * The configured scope model for a class, or for a type as Pennant stores it.
     */
    public function scopeModel(string $classOrType): ?ScopeModel
    {
        foreach ($this->scopeModels() as $scope) {
            if ($scope->class === $classOrType || $scope->type === $classOrType) {
                return $scope;
            }
        }

        return null;
    }

    /**
     * Options for the scope filter: the configured models, then any other
     * model types that have stored values, keyed by type as Pennant stores it.
     *
     * @return array<string, string>
     */
    public function scopeFilterOptions(): array
    {
        $options = [];

        foreach ($this->scopeModels() as $scope) {
            $options[$scope->type] = $scope->label;
        }

        foreach ($this->scopeTypes() as $type) {
            $options[$type] ??= $type;
        }

        return $options;
    }

    /**
     * The model types that have at least one stored value.
     *
     * @return array<int, string>
     */
    public function scopeTypes(): array
    {
        if (! $this->supportsListing()) {
            return [];
        }

        $query = $this->query();

        $type = $this->typeExpression($query);

        /** @var array<int, string> */
        return $query
            ->where('scope', 'like', '%|%')
            ->distinct()
            ->orderBy($type)
            ->pluck($type)
            ->filter(fn (mixed $type): bool => is_string($type) && $type !== '')
            ->values()
            ->all();
    }

    /**
     * Serialize a scope picked in the dashboard the way Pennant stores it.
     *
     * The global scope is Pennant's null scope and `other` takes the given
     * string as is. A model scope must be configured in `pennantplus.scopes`
     * and the model must exist; null is returned otherwise.
     */
    public function resolveScope(string $type, ?string $id = null): ?string
    {
        if ($type === self::GLOBAL) {
            return Feature::serializeScope(null);
        }

        if ($type === self::OTHER) {
            return $id === null || $id === '' ? null : $id;
        }

        $scope = $this->scopeModel($type);

        if ($scope === null || $id === null || $id === '') {
            return null;
        }

        $model = $scope->findMany([$id])->first();

        return $model === null ? null : $scope->serialize($model);
    }

    /**
     * Whether the user (or a guest) may manage feature flags: always, unless
     * `pennantplus.ability` names a Gate ability to check.
     */
    public function allowsManagement(?Authenticatable $user): bool
    {
        $ability = config('pennantplus.ability');

        if (! is_string($ability) || $ability === '') {
            return true;
        }

        return Gate::forUser($user)->allows($ability);
    }

    /**
     * The serialized scope named by API input: `scope` as Pennant stores it,
     * or a `scope_type` (`global`, `other`, a configured model) with its
     * `scope_id`. Without either, the global scope.
     *
     * @param  array<string, mixed>  $input
     *
     * @throws ValidationException when the picked model does not exist
     */
    public function scopeFromInput(array $input): string
    {
        $scope = $input['scope'] ?? null;

        if (is_string($scope) && $scope !== '') {
            return $scope;
        }

        $type = $input['scope_type'] ?? self::GLOBAL;
        $id = $input['scope_id'] ?? null;

        $serialized = $this->resolveScope(
            is_string($type) ? $type : self::GLOBAL,
            is_scalar($id) ? (string) $id : null,
        );

        if ($serialized === null) {
            throw ValidationException::withMessages([
                'scope_id' => __('pennantplus::pennantplus.scope_missing'),
            ]);
        }

        return $serialized;
    }

    /**
     * The scope Pennant resolves a serialized scope against: null for the
     * global scope, the model for a configured `Type|key` scope, and the
     * string itself otherwise.
     *
     * @throws ModelNotFoundException when a configured scope model is missing
     */
    public function scopeValue(string $serialized): mixed
    {
        if ($serialized === Feature::serializeScope(null)) {
            return null;
        }

        if (! str_contains($serialized, '|')) {
            return $serialized;
        }

        $scope = $this->scopeModel(Str::before($serialized, '|'));

        if ($scope === null) {
            return $serialized;
        }

        $key = Str::after($serialized, '|');

        return $scope->findMany([$key])->first()
            ?? throw (new ModelNotFoundException)->setModel($scope->class, [$key]);
    }

    /**
     * A feature summarised: whether Pennant knows it, its stored global value
     * (null when none is stored yet), and how many other scopes hold a value.
     *
     * @return array{name: string, defined: bool, global: mixed, global_stored: bool, overrides: int}
     */
    public function summary(string $feature): array
    {
        $this->discoverFeatures();

        $global = Feature::serializeScope(null);

        $stored = $this->supportsListing()
            ? $this->query()->where('name', $feature)->where('scope', $global)->value('value')
            : null;

        return [
            'name' => $feature,
            'defined' => in_array($feature, $this->store()->defined(), true),
            'global' => is_string($stored) ? json_decode($stored, true, flags: JSON_THROW_ON_ERROR) : null,
            'global_stored' => is_string($stored),
            'overrides' => $this->supportsListing()
                ? $this->query()->where('name', $feature)->where('scope', '!=', $global)->count()
                : 0,
        ];
    }

    protected function query(): Builder
    {
        $config = $this->storeConfig();

        return DB::connection($config['connection'])->table($config['table']);
    }

    protected function applyScopeFilter(Builder $query, ?string $scope, ?string $id): void
    {
        $id = $id === '' ? null : $id;

        match ($scope) {
            null, '' => null,
            self::GLOBAL => $query->where('scope', Feature::serializeScope(null)),
            self::OTHER => $query
                ->where('scope', '!=', Feature::serializeScope(null))
                ->where('scope', 'not like', '%|%')
                ->when($id !== null, fn (Builder $query): Builder => $query->where('scope', $id)),
            default => $id === null
                ? $query->where('scope', 'like', '%|%')->where($this->typeExpression($query), $scope)
                : $query->where('scope', $scope.'|'.$id),
        };
    }

    /**
     * The model type half of a serialized `Type|key` scope, in SQL.
     *
     * Each form yields the whole scope, never an error, when it holds no
     * separator, so it is safe to evaluate against every row.
     */
    protected function typeExpression(Builder $query): Expression
    {
        $grammar = $query->getGrammar();

        return DB::raw(match (true) {
            $grammar instanceof MySqlGrammar => "substring_index(scope, '|', 1)",
            $grammar instanceof PostgresGrammar => "split_part(scope, '|', 1)",
            $grammar instanceof SqlServerGrammar => "left(scope, charindex('|', scope + '|') - 1)",
            default => "substr(scope, 1, instr(scope || '|', '|') - 1)",
        });
    }

    /**
     * Name each model-scoped value after its model, one query per model type.
     *
     * @param  array<int, StoredFeatureValue>  $values
     */
    protected function attachTitles(array $values): void
    {
        $byType = [];

        foreach ($values as $value) {
            $type = $value->scopeType();

            if ($type !== null) {
                $byType[$type][] = $value;
            }
        }

        foreach ($byType as $type => $scoped) {
            $scope = $this->scopeModel($type);

            if ($scope === null || $scope->title === null) {
                continue;
            }

            $models = $scope->findMany(array_values(array_unique(array_map(
                fn (StoredFeatureValue $value): string => (string) $value->scopeId(),
                $scoped,
            ))))->keyBy(fn (Model $model): string => $scope->keyOf($model));

            foreach ($scoped as $value) {
                $model = $models->get((string) $value->scopeId());

                if ($model !== null) {
                    $value->title($scope->titleFor($model));
                }
            }
        }
    }

    /**
     * Instantiable feature classes in the configured feature directories.
     *
     * @return array<int, class-string>
     */
    protected function featureClasses(): array
    {
        $paths = config('pennantplus.features', []);

        $directories = [];

        foreach (is_array($paths) ? $paths : [] as $path) {
            if (is_string($path) && $path !== '') {
                array_push($directories, ...(glob($path, GLOB_ONLYDIR) ?: []));
            }
        }

        if ($directories === []) {
            return [];
        }

        $classes = [];

        foreach (Finder::create()->files()->name('*.php')->in($directories) as $file) {
            $class = $this->classIn($file->getContents(), $file->getBasename('.php'));

            if ($class !== null) {
                $classes[] = $class;
            }
        }

        return $classes;
    }

    /**
     * The feature class a file declares, when it declares one Pennant can resolve.
     *
     * @return class-string|null
     */
    protected function classIn(string $source, string $basename): ?string
    {
        if (preg_match('/^namespace\s+([^;{\s]+)/m', $source, $matches) !== 1) {
            return null;
        }

        $class = $matches[1].'\\'.$basename;

        if (! class_exists($class)) {
            return null;
        }

        $reflection = new ReflectionClass($class);

        return $reflection->isInstantiable() && ($reflection->hasMethod('resolve') || $reflection->hasMethod('__invoke'))
            ? $class
            : null;
    }

    /**
     * @return array{driver: string|null, connection: string|null, table: string}
     */
    protected function storeConfig(): array
    {
        $store = $this->storeName() ?? config('pennant.default');

        $config = config('pennant.stores.'.(is_string($store) ? $store : ''));

        $config = is_array($config) ? $config : [];

        return [
            'driver' => is_string($config['driver'] ?? null) ? $config['driver'] : null,
            'connection' => is_string($config['connection'] ?? null) ? $config['connection'] : null,
            'table' => is_string($config['table'] ?? null) ? $config['table'] : 'features',
        ];
    }
}
