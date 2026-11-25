<?php

declare(strict_types=1);

use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\FileViewFinder;
use RefactorCircus\PennantPlus\PennantPlusServiceProvider;

/**
 * What `vendor:publish --tag=...` copies for a tag, source => destination,
 * with the sources resolved to real paths.
 *
 * @return array<string, string>
 */
function pennantPlusPublishedPaths(string $tag): array
{
    $paths = [];

    foreach (PennantPlusServiceProvider::pathsToPublish(PennantPlusServiceProvider::class, $tag) as $from => $to) {
        $paths[(string) realpath($from)] = $to;
    }

    ksort($paths);

    return $paths;
}

it('publishes only the config and lang files under the pennantplus tag', function (): void {
    $root = dirname(__DIR__, 2);

    $expected = [
        (string) realpath($root.'/config/pennantplus.php') => config_path('pennantplus.php'),
        (string) realpath($root.'/lang') => app()->langPath('vendor/pennantplus'),
    ];

    ksort($expected);

    expect(pennantPlusPublishedPaths('pennantplus'))->toBe($expected);
});

it('publishes the config and the lang files under their own tags', function (): void {
    $root = dirname(__DIR__, 2);

    expect(pennantPlusPublishedPaths('pennantplus-config'))->toBe([(string) realpath($root.'/config/pennantplus.php') => config_path('pennantplus.php')])
        ->and(pennantPlusPublishedPaths('pennantplus-lang'))->toBe([(string) realpath($root.'/lang') => app()->langPath('vendor/pennantplus')]);
});

it('has no views to publish', function (): void {
    expect(ServiceProvider::publishableGroups())->not->toContain('pennantplus-views')
        ->and(pennantPlusPublishedPaths('pennantplus-views'))->toBe([])
        ->and(is_dir(dirname(__DIR__, 2).'/resources/views'))->toBeFalse();
});

it('registers no view namespace', function (): void {
    $finder = View::getFinder();

    expect($finder instanceof FileViewFinder ? $finder->getHints() : [])->not->toHaveKey('pennantplus');
});
