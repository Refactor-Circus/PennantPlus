<?php

declare(strict_types=1);

namespace RefactorCircus\PennantPlus\Domains\Feature\Support;

use Illuminate\Config\Repository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Query\Builder;
use Laravel\Pennant\Contracts\HasFlushableCache;
use Laravel\Pennant\Drivers\DatabaseDriver;
use Laravel\Pennant\Feature;

/**
 * Pennant's database driver, minus the rows that only repeat the global value.
 *
 * The stock driver stores whatever a feature resolves to for every scope that
 * checks it. Here a non-null scope with no stored value is resolved and
 * compared with the global (null-scope) value: when they match nothing is
 * written, so the scope keeps following the global value. Only a value that
 * differs from global is stored. Explicit writes (`activate()`,
 * `deactivate()`) are unaffected.
 *
 * Stored values are read one scope at a time: the first check against a
 * scope loads every value stored for it in one query, and later checks
 * against that scope are answered from memory. Checking many features for
 * one user therefore costs two queries (the user's values and the global
 * ones), not one or two per feature. Pennant flushes this along with its own
 * cache (`Feature::flushCache()`, which Pennant calls as each Octane request,
 * task and tick starts and after each queued job), and every write through
 * the driver flushes it too.
 *
 * Registered as the `pennantplus` driver. A store using it takes the same
 * `connection` and `table` options as the `database` driver.
 */
class GlobalAwareDatabaseDriver extends DatabaseDriver implements HasFlushableCache
{
    /**
     * The stored values loaded so far, as JSON, keyed by serialized scope
     * and then feature name.
     *
     * @var array<string, array<string, string>>
     */
    private array $storedValues = [];

    /**
     * @param  array{connection?: string|null, table?: string|null}  $store
     */
    public function __construct(DatabaseManager $db, Dispatcher $events, Repository $config, private readonly array $store)
    {
        parent::__construct($db, $events, $config, 'pennantplus', []);
    }

    /**
     * @param  string  $feature
     * @param  mixed  $scope
     */
    public function get($feature, $scope): mixed
    {
        $serializedScope = Feature::serializeScope($scope);
        $stored = $this->storedValues($serializedScope)[$feature] ?? null;

        if ($stored !== null) {
            return json_decode($stored, flags: JSON_OBJECT_AS_ARRAY | JSON_THROW_ON_ERROR);
        }

        if ($scope === null) {
            $value = parent::get($feature, null);

            if (array_key_exists($feature, $this->featureStateResolvers)) {
                $this->remember($serializedScope, $feature, $value);
            }

            return $value;
        }

        $value = $this->resolveValue($feature, $scope);

        if ($value === $this->unknownFeatureValue) {
            return false;
        }

        if ($value !== $this->get($feature, null)) {
            parent::set($feature, $scope, $value);

            $this->remember($serializedScope, $feature, $value);
        }

        return $value;
    }

    /**
     * @param  array<string, array<int, mixed>>  $features
     * @return array<string, array<int, mixed>>
     */
    public function getAll($features): array
    {
        $values = [];

        foreach ($features as $feature => $scopes) {
            $values[$feature] = array_map(fn (mixed $scope): mixed => $this->get($feature, $scope), $scopes);
        }

        return $values;
    }

    /**
     * @param  string  $feature
     * @param  mixed  $scope
     * @param  mixed  $value
     */
    public function set($feature, $scope, $value): void
    {
        parent::set($feature, $scope, $value);

        $this->flushCache();
    }

    /**
     * @param  list<array{feature: string, scope: mixed, value: mixed}>  $features
     */
    public function setAll(array $features): void
    {
        parent::setAll($features);

        $this->flushCache();
    }

    /**
     * @param  string  $feature
     * @param  mixed  $value
     */
    public function setForAllScopes($feature, $value): void
    {
        parent::setForAllScopes($feature, $value);

        $this->flushCache();
    }

    /**
     * @param  string  $feature
     * @param  mixed  $scope
     */
    public function delete($feature, $scope): void
    {
        parent::delete($feature, $scope);

        $this->flushCache();
    }

    /**
     * @param  array<int, string>|null  $features
     */
    public function purge($features): void
    {
        parent::purge($features);

        $this->flushCache();
    }

    /**
     * Forget the stored values loaded so far, so the next check against each
     * scope reads it again.
     */
    public function flushCache(): void
    {
        $this->storedValues = [];
    }

    protected function newQuery(): Builder
    {
        return $this->connection()->table($this->store['table'] ?? 'features');
    }

    protected function connection(): Connection
    {
        return $this->db->connection($this->store['connection'] ?? null);
    }

    /**
     * Every value stored for the scope, loaded in one query the first time
     * the scope is checked.
     *
     * @return array<string, string>
     */
    private function storedValues(string $serializedScope): array
    {
        return $this->storedValues[$serializedScope] ??= $this->newQuery()
            ->where('scope', $serializedScope)
            ->pluck('value', 'name')
            ->map(fn (mixed $value): string => (string) $value)
            ->all();
    }

    /**
     * Record a value just stored for the scope, so the scope is not read again.
     */
    private function remember(string $serializedScope, string $feature, mixed $value): void
    {
        if (array_key_exists($serializedScope, $this->storedValues)) {
            $this->storedValues[$serializedScope][$feature] = json_encode($value, flags: JSON_THROW_ON_ERROR);
        }
    }
}
