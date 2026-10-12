<?php

declare(strict_types=1);

use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Laravel\Pennant\Feature;
use RefactorCircus\PennantPlus\Domains\Feature\Actions\DeleteFeatureValueAction;
use RefactorCircus\PennantPlus\Domains\Feature\Actions\ListFeaturesAction;
use RefactorCircus\PennantPlus\Domains\Feature\Actions\UpdateFeatureValueAction;
use RefactorCircus\PennantPlus\Domains\Feature\Data\DeleteFeatureValueData;
use RefactorCircus\PennantPlus\Domains\Feature\Data\UpdateFeatureValueData;
use RefactorCircus\PennantPlus\Domains\Feature\Services\FeatureFlagManager;
use RefactorCircus\PennantPlus\PennantPlusServiceProvider;

/**
 * Run the callback and return the SQL of every query it ran.
 *
 * @return array<int, string>
 */
function queriesDuring(Closure $callback): array
{
    $queries = [];

    DB::listen(function ($query) use (&$queries): void {
        $queries[] = $query->sql;
    });

    $callback();

    return $queries;
}

/**
 * Store rows straight into Pennant's table.
 *
 * @param  array<int, string>  $scopes
 */
function storeRows(string $feature, array $scopes, string $value = 'true'): void
{
    $now = now();

    foreach (array_chunk($scopes, 500) as $chunk) {
        DB::table('features')->insert(array_map(fn (string $scope): array => [
            'name' => $feature,
            'scope' => $scope,
            'value' => $value,
            'created_at' => $now,
            'updated_at' => $now,
        ], $chunk));
    }
}

it('summarises every feature with the same few queries however many there are', function (): void {
    foreach (range(1, 3) as $i) {
        Feature::for(null)->activate("feature-{$i}");
        Feature::for("team-{$i}")->activate("feature-{$i}", 'scoped');
    }

    $few = count(queriesDuring(fn (): array => app(ListFeaturesAction::class)->execute()));

    foreach (range(4, 20) as $i) {
        Feature::for(null)->activate("feature-{$i}");
        Feature::for("team-{$i}")->activate("feature-{$i}", 'scoped');
    }

    $features = null;
    $many = count(queriesDuring(function () use (&$features): void {
        $features = app(ListFeaturesAction::class)->execute();
    }));

    expect($many)->toBe($few)->toBeLessThanOrEqual(3)
        ->and($features)->toHaveCount(20)
        ->and(collect($features)->firstWhere('name', 'feature-7'))->toBe([
            'name' => 'feature-7',
            'defined' => false,
            'global' => true,
            'global_stored' => true,
            'overrides' => 1,
        ]);
});

it('summarises a feature with nothing stored', function (): void {
    expect(app(FeatureFlagManager::class)->summary('missing'))->toBe([
        'name' => 'missing',
        'defined' => false,
        'global' => null,
        'global_stored' => false,
        'overrides' => 0,
    ]);
});

it('scans the feature directories once', function (): void {
    config(['pennantplus.features' => [dirname(__DIR__).'/Fixtures/Features/*']]);

    $manager = app(FeatureFlagManager::class);

    $first = $manager->features();

    // Removing the setting proves the second call reuses the first scan:
    // the same setting would answer the same classes without reading files.
    $memo = (new ReflectionProperty($manager, 'featureClasses'))->getValue($manager);

    expect($memo)->toHaveCount(1)
        ->and($manager->features())->toBe($first)
        ->and((new ReflectionProperty($manager, 'featureClasses'))->getValue($manager))->toBe($memo);
});

it('caches the model types that have values until PennantPlus writes', function (): void {
    $manager = app(FeatureFlagManager::class);

    Feature::for(makeUser())->activate('beta');

    $typeQueries = fn (): int => count(array_filter(
        queriesDuring(fn (): array => $manager->scopeTypes()),
        fn (string $sql): bool => str_contains($sql, 'like'),
    ));

    expect($typeQueries())->toBe(1)
        ->and($typeQueries())->toBe(0);

    app(UpdateFeatureValueAction::class)->execute(new UpdateFeatureValueData('beta', true, scope: 'team-7'));

    expect($typeQueries())->toBe(1);

    app(DeleteFeatureValueAction::class)->execute(new DeleteFeatureValueData('beta', scope: 'team-7'));

    expect($typeQueries())->toBe(1);
});

it('reads the model types afresh when the cache is off', function (): void {
    config(['pennantplus.scope_types_cache_seconds' => 0]);

    $manager = app(FeatureFlagManager::class);

    expect($manager->scopeTypes())->toBe([]);

    Feature::for(makeUser())->activate('beta');

    expect($manager->scopeTypes())->toBe([User::class]);
});

it('forgets every scoped value in chunks once the global value is committed', function (): void {
    Feature::for(null)->activate('beta', false);
    storeRows('beta', array_map(fn (int $i): string => "team-{$i}", range(1, FeatureFlagManager::FORGET_CHUNK + 5)));
    Feature::for('team-keep')->activate('other', true);

    $level = DB::transactionLevel();
    $deletes = [];

    DB::listen(function ($query) use (&$deletes): void {
        if (str_starts_with(strtolower($query->sql), 'delete')) {
            $deletes[] = DB::transactionLevel();
        }
    });

    app(UpdateFeatureValueAction::class)->execute(new UpdateFeatureValueData('beta', true, scope: Feature::serializeScope(null)));

    expect($deletes)->toBe([$level, $level])
        ->and(DB::table('features')->where('name', 'beta')->pluck('value', 'scope')->all())
        ->toBe([Feature::serializeScope(null) => 'true'])
        ->and(DB::table('features')->where('name', 'other')->count())->toBe(1);
});

it('asks whether the viewer manages feature flags once per page, not per row', function (): void {
    config(['pennantplus.ability' => 'manageFeatures']);

    $checks = 0;

    Gate::define('viewAtrium', fn (User $user): bool => true);
    Gate::define('manageFeatures', function (User $user) use (&$checks): bool {
        $checks++;

        return true;
    });

    storeRows('beta', array_map(fn (int $i): string => "team-{$i}", range(1, 20)));

    $this->actingAs(makeUser())->get(route('atrium.pennant.index'))
        ->assertOk()
        ->assertSee('data-testid="pennant-toggle"', false);

    $perPage = $checks;
    $checks = 0;

    storeRows('gamma', array_map(fn (int $i): string => "team-{$i}", range(1, 20)));

    $this->actingAs(makeUser('bob@example.com'))->get(route('atrium.pennant.index'))->assertOk();

    expect($checks)->toBe($perPage);
});

it('builds the configured scope models once for the same config', function (): void {
    config(['pennantplus.scopes' => [User::class => ['label' => 'People']]]);

    $manager = app(FeatureFlagManager::class);

    $models = $manager->scopeModels();

    expect($manager->scopeModels()[User::class])->toBe($models[User::class])
        ->and($manager->scopeModel(User::class))->toBe($models[User::class]);

    config(['pennantplus.scopes' => [User::class => ['label' => 'Members']]]);

    expect($manager->scopeModel(User::class)?->label)->toBe('Members');
});

it('stores an unresolved global value without reading it twice', function (): void {
    config(['pennant.stores.database.driver' => 'pennantplus']);
    Feature::forgetDrivers();

    Feature::define('beta', fn (mixed $scope): bool => true);

    $queries = queriesDuring(fn (): bool => Feature::for(null)->active('beta'));

    expect(array_values(array_filter($queries, fn (string $sql): bool => str_starts_with($sql, 'select'))))->toHaveCount(1)
        ->and(array_values(array_filter($queries, fn (string $sql): bool => str_starts_with($sql, 'insert'))))->toHaveCount(1)
        ->and(DB::table('features')->where('name', 'beta')->value('value'))->toBe('true');
});

it('answers the stored global value when another process stores it first', function (): void {
    config(['pennant.stores.database.driver' => 'pennantplus']);
    Feature::forgetDrivers();

    Feature::define('beta', function (mixed $scope): bool {
        // Another process stores the value between the read and the insert.
        storeRows('beta', [Feature::serializeScope(null)], 'false');

        return true;
    });

    expect(Feature::for(null)->active('beta'))->toBeFalse();
});

it('publishes the optional index migration under its own tag only', function (): void {
    $paths = PennantPlusServiceProvider::pathsToPublish(PennantPlusServiceProvider::class, 'pennantplus-migrations');

    expect($paths)->toHaveCount(1)
        ->and(PennantPlusServiceProvider::pathsToPublish(PennantPlusServiceProvider::class, 'pennantplus'))
        ->not->toHaveKey(array_key_first($paths));
});

it('adds the indexes to the managed store table and removes them again', function (): void {
    $migration = dirname(__DIR__, 2).'/database/migrations';

    Artisan::call('migrate', ['--path' => $migration, '--realpath' => true]);

    $indexes = collect(Schema::getIndexes('features'))->pluck('columns', 'name');

    expect($indexes->get('features_scope_name_index'))->toBe(['scope', 'name'])
        ->and($indexes->get('features_updated_at_index'))->toBe(['updated_at'])
        ->and($indexes->get('features_name_updated_at_index'))->toBe(['name', 'updated_at']);

    Artisan::call('migrate:rollback', ['--path' => $migration, '--realpath' => true]);

    expect(collect(Schema::getIndexes('features'))->pluck('name'))->not->toContain('features_scope_name_index');
});
