<?php

declare(strict_types=1);

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use JayI\PennantPlus\Domains\Feature\Services\FeatureFlagManager;
use Laravel\Pennant\Feature;

beforeEach(function (): void {
    config(['pennant.stores.database.driver' => 'pennantplus']);
    Feature::forgetDrivers();
});

/**
 * @return array<int, string>
 */
function scopedRows(string $feature): array
{
    return DB::table('features')
        ->where('name', $feature)
        ->where('scope', '!=', Feature::serializeScope(null))
        ->pluck('value')
        ->all();
}

/**
 * The bindings of every logged query against the features table.
 *
 * @return array<int, array<int, mixed>>
 */
function featureQueryBindings(): array
{
    return collect(DB::getQueryLog())
        ->filter(fn (array $query): bool => str_contains($query['query'], '"features"'))
        ->pluck('bindings')
        ->values()
        ->all();
}

it('stores no value for a scope that matches the global value', function (): void {
    Feature::define('beta', fn (mixed $scope): bool => $scope === null ? true : Feature::for(null)->active('beta'));

    expect(Feature::for(makeUser())->active('beta'))->toBeTrue()
        ->and(scopedRows('beta'))->toBe([])
        ->and(DB::table('features')->where('name', 'beta')->count())->toBe(1);
});

it('stores the value of a scope that differs from the global value', function (): void {
    Feature::define('beta', fn (mixed $scope): bool => $scope !== null);

    expect(Feature::for(makeUser())->active('beta'))->toBeTrue()
        ->and(scopedRows('beta'))->toBe(['true']);
});

it('lets a scope follow a change to the global value', function (): void {
    $user = makeUser();

    Feature::define('beta', fn (mixed $scope): bool => $scope === null ? false : Feature::for(null)->active('beta'));

    expect(Feature::for($user)->active('beta'))->toBeFalse();

    Feature::for(null)->activate('beta');
    Feature::flushCache();

    expect(Feature::for($user)->active('beta'))->toBeTrue()
        ->and(scopedRows('beta'))->toBe([]);
});

it('keeps an explicitly stored scope value', function (): void {
    $user = makeUser();

    Feature::define('beta', fn (mixed $scope): bool => $scope === null ? true : Feature::for(null)->active('beta'));

    Feature::for($user)->deactivate('beta');
    Feature::flushCache();

    expect(Feature::for($user)->active('beta'))->toBeFalse()
        ->and(scopedRows('beta'))->toBe(['false']);
});

it('reads each scope once however many features are checked against it', function (): void {
    $user = makeUser();
    $features = array_map(fn (int $index): string => "feature-{$index}", range(1, 10));

    foreach ($features as $feature) {
        Feature::define($feature, fn (mixed $scope): bool => $scope === null ? true : Feature::for(null)->active($feature));
        Feature::for(null)->active($feature);
    }

    Feature::flushCache();
    DB::enableQueryLog();

    foreach ($features as $feature) {
        expect(Feature::for($user)->active($feature))->toBeTrue();
    }

    expect(featureQueryBindings())->toBe([
        [Feature::serializeScope($user)],
        [Feature::serializeScope(null)],
    ]);
});

it('remembers a value it stores for a scope', function (): void {
    $user = makeUser();
    $driver = Feature::store()->getDriver();

    Feature::define('beta', fn (mixed $scope): bool => $scope !== null);

    expect($driver->get('beta', $user))->toBeTrue();

    DB::enableQueryLog();

    expect($driver->get('beta', $user))->toBeTrue()
        ->and(featureQueryBindings())->toBe([]);
});

it('reads a scope again after a write through the driver', function (): void {
    $driver = Feature::store()->getDriver();

    Feature::define('beta', fn (): bool => false);

    expect($driver->get('beta', null))->toBeFalse();

    $driver->set('beta', null, true);
    expect($driver->get('beta', null))->toBeTrue();

    $driver->delete('beta', null);
    expect($driver->get('beta', null))->toBeFalse();

    $driver->setForAllScopes('beta', true);
    expect($driver->get('beta', null))->toBeTrue();

    $driver->setAll([['feature' => 'beta', 'scope' => null, 'value' => false]]);
    expect($driver->get('beta', null))->toBeFalse();

    $driver->setForAllScopes('beta', true);
    $driver->purge(['beta']);
    expect($driver->get('beta', null))->toBeFalse();
});

it('keeps the values it has read until its cache is flushed', function (): void {
    $driver = Feature::store()->getDriver();

    Feature::define('beta', fn (): bool => false);

    expect($driver->get('beta', null))->toBeFalse();

    DB::table('features')->where('name', 'beta')->update(['value' => 'true']);

    expect($driver->get('beta', null))->toBeFalse();

    Feature::flushCache();

    expect($driver->get('beta', null))->toBeTrue();
});

it('lets a scope follow the global value again once its values are forgotten', function (): void {
    $user = makeUser();
    $driver = Feature::store()->getDriver();

    Feature::define('beta', fn (mixed $scope): bool => $scope === null ? true : Feature::for(null)->active('beta'));

    Feature::for($user)->deactivate('beta');

    expect($driver->get('beta', $user))->toBeFalse();

    app(FeatureFlagManager::class)->forgetScopedValues('beta');

    expect($driver->get('beta', $user))->toBeTrue();
});

it('reads the table and connection of its store', function (): void {
    config(['pennant.stores.database.table' => 'missing_features']);
    Feature::forgetDrivers();

    Feature::define('beta', fn (): bool => true);

    expect(fn () => Feature::for(null)->active('beta'))->toThrow(QueryException::class);
});
