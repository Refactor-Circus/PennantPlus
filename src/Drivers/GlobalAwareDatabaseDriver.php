<?php

declare(strict_types=1);

namespace JayI\PennantPlus\Drivers;

use Illuminate\Config\Repository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Query\Builder;
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
 * Registered as the `pennantplus` driver. A store using it takes the same
 * `connection` and `table` options as the `database` driver.
 */
class GlobalAwareDatabaseDriver extends DatabaseDriver
{
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
        if ($scope === null) {
            return parent::get($feature, null);
        }

        $stored = $this->newQuery()
            ->where('name', $feature)
            ->where('scope', Feature::serializeScope($scope))
            ->value('value');

        if (is_string($stored)) {
            return json_decode($stored, flags: JSON_OBJECT_AS_ARRAY | JSON_THROW_ON_ERROR);
        }

        $value = $this->resolveValue($feature, $scope);

        if ($value === $this->unknownFeatureValue) {
            return false;
        }

        if ($value !== parent::get($feature, null)) {
            $this->set($feature, $scope, $value);
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

    protected function newQuery(): Builder
    {
        return $this->connection()->table($this->store['table'] ?? 'features');
    }

    protected function connection(): Connection
    {
        return $this->db->connection($this->store['connection'] ?? null);
    }
}
