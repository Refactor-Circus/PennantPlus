<?php

declare(strict_types=1);

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use JayI\Atrium\Domains\Themes\Services\ThemeRegistry;
use JayI\PennantPlus\Atrium\Features\ThemeSwitcherFeature;
use Laravel\Pennant\Feature;

function themeRequest(?Authenticatable $user = null): Request
{
    Feature::flushCache();

    $request = Request::create('/atrium');
    $request->setUserResolver(fn (): ?Authenticatable => $user);

    return $request;
}

it('is the feature atrium shows its theme switcher behind', function (): void {
    expect(config('atrium.themes.switcher_feature'))->toBe(ThemeSwitcherFeature::class);
});

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
