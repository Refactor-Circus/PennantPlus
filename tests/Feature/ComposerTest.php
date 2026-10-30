<?php

declare(strict_types=1);

/**
 * @return array<string, mixed>
 */
function pennantPlusComposerManifest(): array
{
    /** @var array<string, mixed> $manifest */
    $manifest = json_decode((string) file_get_contents(dirname(__DIR__, 2).'/composer.json'), true, flags: JSON_THROW_ON_ERROR);

    return $manifest;
}

/**
 * The `without-atrium` job of the tests workflow, or '' when it is missing.
 */
function pennantPlusWithoutAtriumJob(): string
{
    $workflow = (string) file_get_contents(dirname(__DIR__, 2).'/.github/workflows/tests.yml');

    return preg_match('/^  without-atrium:\n(?:(?:    .*)?\n)*/m', $workflow, $match) === 1 ? $match[0] : '';
}

it('requires no suite package but keystone', function (): void {
    $require = array_keys((array) pennantPlusComposerManifest()['require']);

    expect($require)->not->toContain('refactor-circus/atrium')
        ->and(array_values(array_filter($require, fn (string $package): bool => str_starts_with($package, 'refactor-circus/'))))
        ->toBe(['refactor-circus/keystone']);
});

it('keeps atrium a suggestion and a dev dependency', function (): void {
    $manifest = pennantPlusComposerManifest();

    expect($manifest['suggest'])->toHaveKey('refactor-circus/atrium')
        ->and($manifest['require-dev'])->toHaveKey('refactor-circus/atrium')
        ->and(((array) $manifest['require-dev'])['refactor-circus/atrium'])->toBe('dev-main');
});

it('conflicts with atrium releases from before the screen registry', function (): void {
    expect(pennantPlusComposerManifest()['conflict'] ?? null)->toBe(['refactor-circus/atrium' => '<0.2.0']);
});

it('runs the without-atrium suite from composer test, after the unit tests', function (): void {
    $scripts = (array) pennantPlusComposerManifest()['scripts'];
    $test = array_values((array) $scripts['test']);

    expect($scripts['test:without-atrium'] ?? null)->toBe(['vendor/bin/pest --configuration=phpunit.without-atrium.xml --test-directory=tests/WithoutAtrium'])
        ->and($test)->toContain('@test:without-atrium')
        ->and(array_search('@test:without-atrium', $test, true))->toBe(array_search('@test:unit', $test, true) + 1);
});

it('runs the without-atrium suite in ci with atrium uninstalled', function (): void {
    $job = pennantPlusWithoutAtriumJob();

    expect($job)->not->toBe('')
        ->toMatch('/composer remove --dev [^\n]*--no-update/')
        ->toContain('composer update')
        ->toContain('composer test:without-atrium');

    $remove = (int) strpos($job, 'composer remove --dev');

    expect($remove)->toBeLessThan(strpos($job, 'composer update'))
        ->and(strpos($job, 'composer update'))
        ->toBeLessThan(strpos($job, 'composer test:without-atrium'));
});

it('removes atrium and every dev package that requires it in the without-atrium job', function (): void {
    $root = dirname(__DIR__, 2);
    $devPackages = array_keys((array) pennantPlusComposerManifest()['require-dev']);

    $requiringAtrium = array_values(array_filter($devPackages, function (string $package) use ($root): bool {
        $path = $root.'/vendor/'.$package.'/composer.json';

        if (! is_file($path)) {
            return false;
        }

        /** @var array<string, mixed> $manifest */
        $manifest = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);

        return array_key_exists('refactor-circus/atrium', (array) ($manifest['require'] ?? []));
    }));

    preg_match('/composer remove --dev ([^\n]*?) --no-update/', pennantPlusWithoutAtriumJob(), $match);
    $removed = preg_split('/\s+/', $match[1] ?? '', flags: PREG_SPLIT_NO_EMPTY) ?: [];

    expect($removed)->toContain('refactor-circus/atrium');

    foreach ($requiringAtrium as $package) {
        expect($removed)->toContain($package);
    }
});
