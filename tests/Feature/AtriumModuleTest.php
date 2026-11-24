<?php

declare(strict_types=1);

/*
 * PennantPlus's Atrium front-end module: the build composer.json declares
 * under `extra.atrium` is committed in dist/atrium, ships in the dist
 * archive, and was built from the sources in atrium/ as they are now.
 * `build.json` holds the SHA-256 of those sources; this recomputes it the
 * way Atrium's Vite preset does (`hashInputs()`), so a source changed
 * without a rebuild fails here with no Node needed. CI's `ui` job also
 * rebuilds and diffs.
 */

function pennantPlusModuleRoot(): string
{
    return dirname(__DIR__, 2);
}

/**
 * @return array{key: string, scripts: list<string>, styles: list<string>}
 */
function pennantPlusModuleDeclaration(): array
{
    /** @var array{extra: array{atrium: array{key: string, scripts: list<string>, styles: list<string>}}} $manifest */
    $manifest = json_decode((string) file_get_contents(pennantPlusModuleRoot().'/composer.json'), true, flags: JSON_THROW_ON_ERROR);

    return $manifest['extra']['atrium'];
}

/**
 * @return array{key: string, shared_api: string, root: string, inputs: list<string>, hash: string}
 */
function pennantPlusModuleBuild(): array
{
    /** @var array{key: string, shared_api: string, root: string, inputs: list<string>, hash: string} $build */
    $build = json_decode((string) file_get_contents(pennantPlusModuleRoot().'/dist/atrium/build.json'), true, flags: JSON_THROW_ON_ERROR);

    return $build;
}

/**
 * The SHA-256 of `inputs` under `root`, as the preset's `hashInputs()`
 * takes it: each file (directories walked; node_modules, dist and dotfiles
 * skipped) as `{path}\0{sha256 of its content, CRLF as LF}\n`, sorted.
 *
 * @param  list<string>  $inputs
 */
function pennantPlusModuleSourcesHash(string $root, array $inputs): string
{
    $files = [];

    $walk = function (string $path) use (&$walk, &$files, $root): void {
        $absolute = $root.'/'.$path;

        if (! file_exists($absolute)) {
            return;
        }

        if (is_dir($absolute)) {
            foreach (scandir($absolute) ?: [] as $entry) {
                if (! in_array($entry, ['node_modules', 'dist', '.DS_Store', '.git'], true) && ! str_starts_with($entry, '.')) {
                    $walk($path.'/'.$entry);
                }
            }

            return;
        }

        $files[] = $path;
    };

    foreach ($inputs as $input) {
        $walk(rtrim($input, '/'));
    }

    $files = array_values(array_unique($files));
    sort($files, SORT_STRING);

    $lines = array_map(
        fn (string $path): string => $path."\0".hash('sha256', str_replace("\r\n", "\n", (string) file_get_contents($root.'/'.$path)))."\n",
        $files,
    );

    return hash('sha256', implode('', $lines));
}

it('declares the module under its integration key, with the built script and stylesheet', function (): void {
    expect(pennantPlusModuleDeclaration())->toBe([
        'key' => 'pennantplus',
        'scripts' => ['dist/atrium/atrium.js'],
        'styles' => ['dist/atrium/atrium.css'],
    ]);
});

it('commits every file composer.json declares', function (string $path): void {
    expect(is_file(pennantPlusModuleRoot().'/'.$path))->toBeTrue("{$path} is missing: run `pnpm build` in atrium/ and commit dist/atrium.")
        ->and(filesize(pennantPlusModuleRoot().'/'.$path))->toBeGreaterThan(0);
})->with(fn (): array => [...pennantPlusModuleDeclaration()['scripts'], ...pennantPlusModuleDeclaration()['styles']]);

it('ships the build and leaves the sources out of the dist archive', function (): void {
    $attributes = (string) file_get_contents(pennantPlusModuleRoot().'/.gitattributes');

    expect($attributes)->toMatch('/^\/atrium\s+export-ignore$/m')
        ->not->toMatch('/^\/dist\S*\s+export-ignore$/m');
});

it('was built for the shared api it targets, from atrium/', function (): void {
    $build = pennantPlusModuleBuild();

    expect($build['key'])->toBe('pennantplus')
        ->and($build['shared_api'])->toBe('1.0')
        ->and(realpath(pennantPlusModuleRoot().'/dist/atrium/'.$build['root']))->toBe(realpath(pennantPlusModuleRoot().'/atrium'))
        ->and($build['inputs'])->toContain('src');
});

it('was built from the sources as they are now', function (): void {
    $build = pennantPlusModuleBuild();
    $root = (string) realpath(pennantPlusModuleRoot().'/dist/atrium/'.$build['root']);

    expect(pennantPlusModuleSourcesHash($root, $build['inputs']))
        ->toBe($build['hash'], 'dist/atrium is older than its sources: run `pnpm build` in atrium/ and commit dist/atrium.');
});
