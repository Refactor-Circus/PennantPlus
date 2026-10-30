<?php

declare(strict_types=1);

use Composer\Autoload\ClassLoader;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Laravel\Pennant\Feature;
use RefactorCircus\Atrium\AtriumServiceProvider;
use RefactorCircus\Atrium\Domains\Access\Services\Gatekeeper;
use RefactorCircus\Atrium\Domains\Plugins\Services\PluginRegistry;
use RefactorCircus\PennantPlus\Atrium\FeatureFlagAccess;
use RefactorCircus\PennantPlus\Atrium\PennantPlugin;
use RefactorCircus\PennantPlus\PennantPlusServiceProvider;
use RefactorCircus\PennantPlus\Tests\WithoutAtrium\WithoutAtriumTestCase;

uses(WithoutAtriumTestCase::class);

/**
 * Every class, interface and trait loaded so far from Atrium or from the
 * PennantPlus layer that extends it.
 *
 * @return list<string>
 */
function loadedAtriumClasses(): array
{
    return array_values(array_filter(
        [...get_declared_classes(), ...get_declared_interfaces(), ...get_declared_traits()],
        fn (string $class): bool => str_starts_with($class, 'RefactorCircus\\Atrium\\') || str_starts_with($class, 'RefactorCircus\\PennantPlus\\Atrium\\'),
    ));
}

it('runs with the atrium psr-4 prefix removed from the autoloader', function (): void {
    $loaders = ClassLoader::getRegisteredLoaders();

    expect($loaders)->not->toBeEmpty();

    foreach ($loaders as $loader) {
        expect(array_keys($loader->getPrefixesPsr4()))->not->toContain('RefactorCircus\\Atrium\\')
            ->and($loader->findFile(AtriumServiceProvider::class))->toBeFalse();
    }
});

it('cannot load atrium', function (): void {
    expect(class_exists(AtriumServiceProvider::class))->toBeFalse()
        ->and(class_exists(Gatekeeper::class))->toBeFalse()
        ->and(class_exists(PluginRegistry::class))->toBeFalse();
});

it('boots the provider past its atrium guards', function (): void {
    expect(app()->getProvider(PennantPlusServiceProvider::class))->not->toBeNull()
        ->and(Blade::getCustomDirectives())->not->toHaveKey('pennantplusManages')
        ->and(class_exists(FeatureFlagAccess::class, false))->toBeFalse()
        ->and(class_exists(PennantPlugin::class, false))->toBeFalse();
});

it('registers no atrium route', function (): void {
    $names = collect(Route::getRoutes()->getRoutes())
        ->map(fn (RoutingRoute $route): ?string => $route->getName())
        ->filter()
        ->values();

    expect($names)->toContain('pennantplus.features.index')
        ->and($names->filter(fn (string $name): bool => str_starts_with($name, 'atrium.'))->all())->toBe([]);
});

it('answers the json api', function (): void {
    Feature::for(null)->activate('beta');

    $this->getJson(route('pennantplus.features.index'))
        ->assertOk()
        ->assertJsonPath('data.0.name', 'beta')
        ->assertJsonPath('data.0.global', true);
});

it('lists the mcp server tools', function (): void {
    $response = $this->postJson(WithoutAtriumTestCase::MCP_ROUTE, ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list'])
        ->assertOk()
        ->assertJsonPath('id', 1)
        ->assertJsonMissingPath('error');

    expect($response->json('result.tools'))->toBeArray()->not->toBeEmpty();
});

it('loads no atrium class while it boots and answers', function (): void {
    Feature::for(null)->activate('beta');

    $this->getJson(route('pennantplus.features.index'))->assertOk();
    $this->postJson(WithoutAtriumTestCase::MCP_ROUTE, ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list'])->assertOk();

    expect(loadedAtriumClasses())->toBe([]);
});
