<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;

/**
 * The committed document at the package root, as a string.
 */
function pennantPlusCommittedOpenApi(): string
{
    return (string) file_get_contents(dirname(__DIR__, 2).'/openapi.json');
}

/**
 * Export the document as `composer client:generate` does, into a temporary
 * file, and return its contents.
 */
function pennantPlusExportedOpenApi(): string
{
    $path = (string) tempnam(sys_get_temp_dir(), 'pennantplus-openapi-');

    try {
        expect(Artisan::call('scramble:export', ['--path' => $path]))->toBe(0);

        return (string) file_get_contents($path);
    } finally {
        @unlink($path);
    }
}

/**
 * @return array<string, mixed>
 */
function pennantPlusDecodedOpenApi(string $json): array
{
    /** @var array<string, mixed> $document */
    $document = json_decode($json, true, flags: JSON_THROW_ON_ERROR);

    return $document;
}

it('matches the committed openapi.json when exported again', function (): void {
    expect(pennantPlusDecodedOpenApi(pennantPlusExportedOpenApi()))
        ->toBe(pennantPlusDecodedOpenApi(pennantPlusCommittedOpenApi()));
});

it('describes PennantPlus 2.0.0 as an OpenAPI 3 document', function (): void {
    $document = pennantPlusDecodedOpenApi(pennantPlusCommittedOpenApi());
    $info = (array) $document['info'];

    expect((string) $document['openapi'])->toStartWith('3.')
        ->and($info['title'])->toBe('PennantPlus')
        ->and($info['version'])->toBe('2.0.0');
});

it('serves every operation under the pennantplus route prefix', function (): void {
    $servers = array_column((array) pennantPlusDecodedOpenApi(pennantPlusCommittedOpenApi())['servers'], 'url');

    expect($servers)->not->toBeEmpty();

    foreach ($servers as $url) {
        expect((string) $url)->toEndWith('/pennantplus');
    }
});

it('has one operation for each json api route, by path and method', function (): void {
    $operations = [];

    foreach ((array) pennantPlusDecodedOpenApi(pennantPlusCommittedOpenApi())['paths'] as $path => $item) {
        foreach (array_keys((array) $item) as $method) {
            if (in_array($method, ['get', 'put', 'post', 'patch', 'delete', 'head', 'options', 'trace'], true)) {
                $operations[] = strtoupper($method).' '.$path;
            }
        }
    }

    sort($operations);

    // The routes of src/Domains/*/routes.php and the history route, relative
    // to the `/pennantplus` server.
    expect($operations)->toBe([
        'DELETE /features/{feature}',
        'DELETE /values',
        'GET /features',
        'GET /features/{feature}',
        'GET /features/{feature}/check',
        'GET /history',
        'GET /scopes',
        'GET /scopes/{type}/models',
        'GET /values',
        'PUT /values',
    ]);
});

it('holds no machine-specific value', function (): void {
    $json = pennantPlusCommittedOpenApi();
    $root = dirname(__DIR__, 2);

    expect($json)->not->toContain($root)
        ->not->toContain(str_replace('\\', '/', $root))
        ->not->toContain('base64:')
        ->not->toContain('file://')
        ->not->toMatch('#/(Users|home|private|tmp|var/folders)/#')
        ->not->toMatch('#[A-Za-z]:\\\\\\\\#');
});
