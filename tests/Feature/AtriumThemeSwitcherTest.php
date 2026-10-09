<?php

declare(strict_types=1);

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use JayI\Atrium\Domains\Themes\Features\ThemeSwitcherFeature;
use JayI\Atrium\Domains\Themes\Services\ThemeRegistry;
use Laravel\Pennant\Feature;

function themeRequest(?Authenticatable $user = null): Request
{
    Feature::flushCache();

    $request = Request::create('/atrium');
    $request->setUserResolver(fn (): ?Authenticatable => $user);

    return $request;
}

it('shows the theme switcher until the feature is turned off globally', function (): void {
    $themes = app(ThemeRegistry::class);
    $user = makeUser();

    expect($themes->switchable(themeRequest($user)))->toBeTrue();

    Feature::for(null)->deactivate(ThemeSwitcherFeature::class);

    expect($themes->switchable(themeRequest($user)))->toBeFalse();
});

it('hides the theme switcher for one user', function (): void {
    $themes = app(ThemeRegistry::class);
    $ada = makeUser();
    $bob = makeUser('bob@example.com');

    Feature::for($ada)->deactivate(ThemeSwitcherFeature::class);

    expect($themes->switchable(themeRequest($ada)))->toBeFalse()
        ->and($themes->switchable(themeRequest($bob)))->toBeTrue();
});

it('stores nothing for users who follow the global value', function (): void {
    config()->set('pennant.default', 'pennantplus');
    config()->set('pennant.stores.pennantplus', ['driver' => 'pennantplus']);

    $themes = app(ThemeRegistry::class);

    $themes->switchable(themeRequest(makeUser()));
    $themes->switchable(themeRequest(makeUser('bob@example.com')));

    expect(DB::table('features')->pluck('scope')->all())->toBe(['__laravel_null']);
});
