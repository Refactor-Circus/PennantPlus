<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Optional indexes on Pennant's `features` table for the store PennantPlus
 * manages. PennantPlus owns no tables, so this migration is never run on its
 * own: publish it with `php artisan vendor:publish --tag=pennantplus-migrations`.
 *
 * - (scope, name): the `pennantplus` driver reads every value stored for a
 *   scope at once; Pennant's own unique(name, scope) cannot serve that.
 * - (updated_at) and (name, updated_at): the dashboard and `GET values` list
 *   values newest first, optionally for one feature.
 */
return new class extends Migration
{
    public function up(): void
    {
        [$connection, $table] = $this->store();

        Schema::connection($connection)->table($table, function (Blueprint $blueprint) use ($table): void {
            $blueprint->index(['scope', 'name'], $table.'_scope_name_index');
            $blueprint->index('updated_at', $table.'_updated_at_index');
            $blueprint->index(['name', 'updated_at'], $table.'_name_updated_at_index');
        });
    }

    public function down(): void
    {
        [$connection, $table] = $this->store();

        Schema::connection($connection)->table($table, function (Blueprint $blueprint) use ($table): void {
            $blueprint->dropIndex($table.'_scope_name_index');
            $blueprint->dropIndex($table.'_updated_at_index');
            $blueprint->dropIndex($table.'_name_updated_at_index');
        });
    }

    /**
     * The connection and table of the managed Pennant store.
     *
     * @return array{0: string|null, 1: string}
     */
    private function store(): array
    {
        $store = config('pennantplus.store') ?: config('pennant.default');
        $config = config('pennant.stores.'.(is_string($store) ? $store : ''));
        $config = is_array($config) ? $config : [];

        return [
            is_string($config['connection'] ?? null) ? $config['connection'] : null,
            is_string($config['table'] ?? null) ? $config['table'] : 'features',
        ];
    }
};
