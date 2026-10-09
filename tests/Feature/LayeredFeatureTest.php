<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use RefactorCircus\PennantPlus\Tests\Fixtures\Layered\OffFeature;
use RefactorCircus\PennantPlus\Tests\Fixtures\Layered\OnFeature;
use Laravel\Pennant\Feature;

beforeEach(function (): void {
    config(['pennant.stores.database.driver' => 'pennantplus']);
    Feature::forgetDrivers();
});

it('seeds the global value from the default', function (): void {
    expect(Feature::for(null)->active(OnFeature::class))->toBeTrue()
        ->and(Feature::for(null)->active(OffFeature::class))->toBeFalse();
});

it('lets a user follow the global value without storing one', function (): void {
    $user = makeUser();

    expect(Feature::for($user)->active(OffFeature::class))->toBeFalse();

    Feature::for(null)->activate(OffFeature::class);
    Feature::flushCache();

    expect(Feature::for($user)->active(OffFeature::class))->toBeTrue()
        ->and(DB::table('features')->where('scope', '!=', Feature::serializeScope(null))->exists())->toBeFalse();
});

it('keeps a value given to the user', function (): void {
    $user = makeUser();

    Feature::for($user)->deactivate(OnFeature::class);
    Feature::flushCache();

    expect(Feature::for(null)->active(OnFeature::class))->toBeTrue()
        ->and(Feature::for($user)->active(OnFeature::class))->toBeFalse();
});
