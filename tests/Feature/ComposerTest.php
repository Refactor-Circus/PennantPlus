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
 * A job of the tests workflow, or '' when it is missing.
 */
function pennantPlusWorkflowJob(string $name): string
{
    $workflow = (string) file_get_contents(dirname(__DIR__, 2).'/.github/workflows/tests.yml');

    return preg_match('/^  '.preg_quote($name, '/').':\n(?:(?:    .*)?\n)*/m', $workflow, $match) === 1 ? $match[0] : '';
}

/**
 * The `without-atrium` job of the tests workflow, or '' when it is missing.
 */
function pennantPlusWithoutAtriumJob(): string
{
    return pennantPlusWorkflowJob('without-atrium');
}

it('requires no suite package but keystone', function (): void {
    $require = array_keys((array) pennantPlusComposerManifest()['require']);

    expect($require)->not->toContain('refactor-circus/atrium')
        ->and(array_values(array_filter($require, fn (string $package): bool => str_starts_with($package, 'refactor-circus/'))))
        ->toBe(['refactor-circus/keystone']);
});

it('names atrium in no require, require-dev, suggest or conflict entry', function (string $section): void {
    expect((array) (pennantPlusComposerManifest()[$section] ?? []))->not->toHaveKey('refactor-circus/atrium');
})->with(['require', 'require-dev', 'suggest', 'conflict']);

it('declares its atrium front-end module and no atrium plugin', function (): void {
    // The only Atrium entry: the module's built script, stylesheet and
    // integration manifest (JSON, no Atrium PHP). No
    // `plugins` (the v1 PHP plugin) or any other key.
    expect((array) (pennantPlusComposerManifest()['extra'] ?? []))->toHaveKey('atrium')
        ->and(pennantPlusComposerManifest()['extra']['atrium'] ?? null)->toBe([
            'key' => 'pennantplus',
            'scripts' => ['dist/atrium/atrium.js'],
            'styles' => ['dist/atrium/atrium.css'],
            'integration' => 'dist/atrium/integration.json',
        ]);
});

it('describes the package without atrium', function (): void {
    $description = (string) pennantPlusComposerManifest()['description'];

    expect($description)->not->toBe('')
        ->and(stripos($description, 'atrium'))->toBeFalse();
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

it('removes cortex and every other dev package that requires atrium in the without-atrium job', function (): void {
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

    // Atrium is no dev dependency of its own: removing what pulls it in is
    // what uninstalls it, and the job names only packages require-dev lists.
    expect($removed)->toContain('refactor-circus/cortex')
        ->not->toContain('refactor-circus/atrium')
        ->and(array_values(array_diff($removed, $devPackages)))->toBe([]);

    foreach ($requiringAtrium as $package) {
        expect($removed)->toContain($package);
    }
});

it('requires scramble for development only', function (): void {
    $manifest = pennantPlusComposerManifest();

    expect((array) $manifest['require-dev'])->toHaveKey('dedoc/scramble')
        ->and((array) $manifest['require'])->not->toHaveKey('dedoc/scramble');
});

it('regenerates the openapi document, its client copy and the client schema in order', function (): void {
    expect(pennantPlusComposerManifest()['scripts']['client:generate'] ?? null)->toBe([
        '@php vendor/bin/testbench scramble:export --path=openapi.json --ansi',
        "@php -r \"copy('openapi.json', 'client/openapi.json');\"",
        'npm --prefix client run generate',
    ]);
});

it('fails ci on a stale document or client in the client job', function (): void {
    $job = pennantPlusWorkflowJob('client');

    expect($job)->not->toBe('')
        ->toContain('runs-on: ubuntu-latest')
        ->toMatch('/php-version: [\'"]?8\.5[\'"]?\n/')
        ->toMatch('/node-version: [\'"]?lts\/\*[\'"]?\n/');

    $steps = [
        'npm --prefix client ci',
        'composer client:generate',
        'git diff --exit-code -- openapi.json client',
        'npm --prefix client run build',
    ];
    $positions = array_map(fn (string $step): int|false => strpos($job, $step), $steps);
    $ordered = $positions;
    sort($ordered);

    expect($positions)->not->toContain(false)
        ->and($positions)->toBe($ordered);
});

it('references scramble nowhere in src or config', function (): void {
    $root = dirname(__DIR__, 2);
    $offenders = [];

    foreach (['src', 'config'] as $directory) {
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/'.$directory, FilesystemIterator::SKIP_DOTS));

        /** @var SplFileInfo $file */
        foreach ($files as $file) {
            if (str_contains((string) file_get_contents($file->getPathname()), 'Dedoc\\')) {
                $offenders[] = substr($file->getPathname(), strlen($root) + 1);
            }
        }
    }

    expect($offenders)->toBe([]);
});
