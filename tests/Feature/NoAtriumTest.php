<?php

declare(strict_types=1);

use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;

/*
 * PennantPlus v2 ships only its JSON API and MCP server: nothing in the
 * package names Atrium, and the Atrium page, resolver and Blade conditional
 * are gone.
 */

/**
 * Every file under the given package directories.
 *
 * @param  list<string>  $directories
 * @return list<string>
 */
function pennantPlusFilesUnder(array $directories): array
{
    $root = dirname(__DIR__, 2);
    $files = [];

    foreach ($directories as $directory) {
        if (! is_dir($root.'/'.$directory)) {
            continue;
        }

        /** @var SplFileInfo $file */
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/'.$directory, FilesystemIterator::SKIP_DOTS)) as $file) {
            $files[] = $file->getPathname();
        }
    }

    return $files;
}

it('names atrium in no file under src, config, lang or database', function (): void {
    $root = dirname(__DIR__, 2);

    $found = array_values(array_map(
        fn (string $path): string => substr($path, strlen($root) + 1),
        array_filter(
            pennantPlusFilesUnder(['src', 'config', 'lang', 'database']),
            fn (string $path): bool => stripos((string) file_get_contents($path), 'atrium') !== false,
        ),
    ));

    expect(pennantPlusFilesUnder(['src']))->not->toBeEmpty()
        ->and($found)->toBe([]);
});

it('has no atrium directory in the source', function (): void {
    expect(is_dir(dirname(__DIR__, 2).'/src/Atrium'))->toBeFalse();
});

it('registers no pennantplusManages blade conditional', function (): void {
    $directives = array_keys(Blade::getCustomDirectives());

    foreach (['pennantplusManages', 'elsepennantplusManages', 'endpennantplusManages', 'unlesspennantplusManages'] as $directive) {
        expect($directives)->not->toContain($directive);
    }
});

it('registers no atrium.pennant route', function (): void {
    $names = collect(Route::getRoutes()->getRoutes())
        ->map(fn (RoutingRoute $route): ?string => $route->getName())
        ->filter()
        ->values();

    expect($names)->toContain('pennantplus.features.index')
        ->and($names->filter(fn (string $name): bool => str_starts_with($name, 'atrium.pennant.'))->all())->toBe([]);
});

it('has no atrium config key', function (): void {
    /** @var array<string, mixed> $defaults */
    $defaults = require dirname(__DIR__, 2).'/config/pennantplus.php';

    expect($defaults)->not->toHaveKey('atrium')
        ->and((array) config('pennantplus'))->not->toHaveKey('atrium')
        ->and(config('pennantplus.atrium.resolve_features'))->toBeNull();
});

it('keeps only the lang keys the source uses', function (): void {
    /** @var array<string, string> $lines */
    $lines = require dirname(__DIR__, 2).'/lang/en/pennantplus.php';

    $source = implode("\n", array_map(
        fn (string $path): string => (string) file_get_contents($path),
        pennantPlusFilesUnder(['src']),
    ));

    $unused = array_values(array_filter(
        array_keys($lines),
        fn (string $key): bool => ! str_contains($source, "'pennantplus::pennantplus.{$key}'"),
    ));

    expect($unused)->toBe([])
        ->and(array_keys($lines))->toEqualCanonicalizing(['global', 'other', 'scope_missing', 'feature_disabled']);
});
