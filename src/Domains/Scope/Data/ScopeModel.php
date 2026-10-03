<?php

declare(strict_types=1);

namespace JayI\PennantPlus\Domains\Scope\Data;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use JayI\PennantPlus\Domains\Scope\Exceptions\InvalidScopeModelException;
use Laravel\Pennant\Feature;

/**
 * A model that feature flag values can be scoped to, from
 * `pennantplus.scopes`.
 */
final class ScopeModel
{
    /** The model type as Pennant stores it: the class, or its morph alias. */
    public private(set) string $type;

    public private(set) string $label;

    /** @var array<int, string> */
    public private(set) array $search = [];

    public private(set) ?string $title = null;

    /**
     * @param  class-string<Model>  $class
     */
    public function __construct(public private(set) string $class)
    {
        // Serializing a keyed instance yields exactly the type Pennant writes,
        // whether or not the application told it to use the morph map.
        $instance = new $class;
        $instance->forceFill([$instance->getKeyName() => 0]);

        $this->type = Str::beforeLast(Feature::serializeScope($instance), '|');
        $this->label = Str::of(class_basename($class))->headline()->plural()->toString();
    }

    /**
     * @param  array{label?: string, search?: array<int, string>, title?: string}  $options
     */
    public static function fromConfig(string $class, array $options = []): self
    {
        if (! is_subclass_of($class, Model::class)) {
            throw InvalidScopeModelException::notAModel($class);
        }

        $scope = new self($class);

        if (isset($options['label'])) {
            $scope->label($options['label']);
        }

        if (isset($options['search'])) {
            $scope->search($options['search']);
        }

        if (isset($options['title'])) {
            $scope->title($options['title']);
        }

        return $scope;
    }

    public function label(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    /**
     * @param  array<int, string>  $columns
     */
    public function search(array $columns): static
    {
        $this->search = array_values($columns);

        return $this;
    }

    public function title(?string $attribute): static
    {
        $this->title = $attribute;

        return $this;
    }

    /**
     * Models whose key matches the term exactly, or whose search columns
     * contain it.
     *
     * @return Collection<int, Model>
     */
    public function find(string $term, int $limit = 20): Collection
    {
        $query = $this->query();

        $key = $query->getModel()->getQualifiedKeyName();

        return $query
            ->where(function (Builder $query) use ($key, $term): void {
                $query->where($key, $term);

                foreach ($this->search as $column) {
                    $query->orWhere($column, 'like', '%'.$term.'%');
                }
            })
            ->limit($limit)
            ->get();
    }

    /**
     * @param  array<int, string>  $keys
     * @return Collection<int, Model>
     */
    public function findMany(array $keys): Collection
    {
        $query = $this->query();

        return $query->whereIn($query->getModel()->getQualifiedKeyName(), $keys)->get();
    }

    /**
     * The value Pennant stores as the scope for the given model.
     */
    public function serialize(Model $model): string
    {
        return Feature::serializeScope($model);
    }

    /**
     * How a model is shown in the dashboard.
     */
    public function titleFor(Model $model): string
    {
        $title = $this->title === null ? null : $model->getAttribute($this->title);

        return is_scalar($title) && (string) $title !== ''
            ? (string) $title
            : class_basename($this->class).' #'.$this->keyOf($model);
    }

    public function keyOf(Model $model): string
    {
        $key = $model->getKey();

        return is_scalar($key) ? (string) $key : '';
    }

    /**
     * @return Builder<Model>
     */
    private function query(): Builder
    {
        /** @var Builder<Model> */
        return $this->class::query();
    }
}
