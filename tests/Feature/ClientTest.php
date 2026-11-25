<?php

declare(strict_types=1);

use Symfony\Component\Process\Process;

function pennantPlusClientPath(string $path = ''): string
{
    return dirname(__DIR__, 2).'/client'.($path === '' ? '' : '/'.$path);
}

/**
 * @return array<string, mixed>
 */
function pennantPlusClientJson(string $path): array
{
    /** @var array<string, mixed> $json */
    $json = json_decode((string) file_get_contents(pennantPlusClientPath($path)), true, flags: JSON_THROW_ON_ERROR);

    return $json;
}

/**
 * Every string a package.json `exports` entry points to.
 *
 * @return list<string>
 */
function pennantPlusClientExportTargets(mixed $exports): array
{
    if (is_string($exports)) {
        return [$exports];
    }

    $targets = [];

    foreach ((array) $exports as $export) {
        array_push($targets, ...pennantPlusClientExportTargets($export));
    }

    return $targets;
}

it('ships a copy of openapi.json that is byte-identical to the root document', function (): void {
    expect(pennantPlusClientPath('openapi.json'))->toBeFile()
        ->and(file_get_contents(pennantPlusClientPath('openapi.json')))
        ->toBe(file_get_contents(dirname(__DIR__, 2).'/openapi.json'));
});

it('ships the openapi-typescript output for client/openapi.json', function (): void {
    $binary = pennantPlusClientPath('node_modules/.bin/openapi-typescript'.(PHP_OS_FAMILY === 'Windows' ? '.cmd' : ''));

    if (! is_file($binary)) {
        $this->markTestSkipped('The client dependencies are not installed: run `npm --prefix client ci` to check client/src/schema.d.ts.');
    }

    $path = sys_get_temp_dir().'/pennantplus-schema-'.bin2hex(random_bytes(8)).'.d.ts';

    try {
        // As `npm --prefix client run generate` does, from inside client/.
        $process = new Process([$binary, 'openapi.json', '-o', $path], pennantPlusClientPath());
        $process->mustRun();

        expect(file_get_contents($path))->toBe(file_get_contents(pennantPlusClientPath('src/schema.d.ts')));
    } finally {
        @unlink($path);
    }
});

it('declares the client package', function (): void {
    $package = pennantPlusClientJson('package.json');

    expect($package['name'])->toBe('@refactor-circus/pennantplus-client')
        ->and($package['version'])->toBe('2.0.0')
        ->and($package['private'])->toBeTrue()
        ->and($package['type'])->toBe('module')
        ->and(array_keys((array) $package['dependencies']))->toBe(['openapi-fetch'])
        ->and((array) $package['devDependencies'])->toHaveKeys(['openapi-typescript', 'typescript'])
        ->and((array) $package['scripts'])->toMatchArray([
            'generate' => 'openapi-typescript openapi.json -o src/schema.d.ts',
            'build' => 'tsc -p .',
            'prepare' => 'npm run build',
        ]);
});

it('points the client entry points into dist', function (): void {
    $package = pennantPlusClientJson('package.json');
    $targets = pennantPlusClientExportTargets($package['exports'] ?? null);

    expect((string) $package['main'])->toStartWith('./dist/')
        ->and((string) $package['types'])->toStartWith('./dist/')
        ->and($targets)->not->toBeEmpty();

    foreach ($targets as $target) {
        expect($target)->toStartWith('./dist/');
    }
});

it('builds the client strictly into dist with declarations', function (): void {
    $options = (array) pennantPlusClientJson('tsconfig.json')['compilerOptions'];

    expect($options['strict'])->toBeTrue()
        ->and($options['declaration'])->toBeTrue()
        ->and($options['outDir'])->toBe('dist');
});

it('commits the lockfile and ignores the installed and built client', function (): void {
    $ignored = preg_split('/\R/', (string) file_get_contents(dirname(__DIR__, 2).'/.gitignore')) ?: [];

    expect(pennantPlusClientPath('package-lock.json'))->toBeFile()
        ->and($ignored)->toContain('/client/node_modules')
        ->toContain('/client/dist');
});

it('wraps openapi-fetch in createPennantPlusClient and re-exports the schema types', function (): void {
    $source = (string) file_get_contents(pennantPlusClientPath('src/index.ts'));

    expect($source)->toContain("from 'openapi-fetch'")
        ->toContain('export function createPennantPlusClient(options: ClientOptions = {}): Client<paths>')
        ->toContain('createClient<paths>(options)')
        ->toContain("export type { paths, components, operations } from '../src/schema.js'");
});
