<?php

declare(strict_types=1);

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
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

it('reads the table and connection of its store', function (): void {
    config(['pennant.stores.database.table' => 'missing_features']);
    Feature::forgetDrivers();

    Feature::define('beta', fn (): bool => true);

    expect(fn () => Feature::for(null)->active('beta'))->toThrow(QueryException::class);
});
