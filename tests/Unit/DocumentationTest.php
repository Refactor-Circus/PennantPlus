<?php

declare(strict_types=1);

/*
 * What the README, CLAUDE.md, the product docs and the Boost skill promise
 * about the feature flags screen. Plain file reads, so no framework boot.
 */

function pennantPlusRoot(): string
{
    return dirname(__DIR__, 2);
}

function pennantPlusDoc(string $path): string
{
    return (string) file_get_contents(pennantPlusRoot().'/'.$path);
}

/**
 * The lowest Atrium release the `conflict` entry lets through, as `x.y.z`.
 */
function pennantPlusMinimumAtrium(): string
{
    /** @var array{conflict?: array<string, string>} $manifest */
    $manifest = json_decode(pennantPlusDoc('composer.json'), true, flags: JSON_THROW_ON_ERROR);

    return ltrim($manifest['conflict']['refactor-circus/atrium'] ?? '', '<');
}

/**
 * @return list<string>
 */
function pennantPlusScannedFiles(): array
{
    $root = pennantPlusRoot();
    $files = [$root.'/README.md', $root.'/CLAUDE.md'];

    foreach (['src', 'resources', 'docs/agent/product'] as $directory) {
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

it('mentions no audit trail or history nav item in the code or the docs', function (): void {
    $found = [];

    foreach (pennantPlusScannedFiles() as $path) {
        $contents = (string) file_get_contents($path);

        foreach (['audit-trail', 'historyNavItem'] as $needle) {
            if (str_contains($contents, $needle)) {
                $found[] = substr($path, strlen(pennantPlusRoot()) + 1).': '.$needle;
            }
        }
    }

    expect($found)->toBe([]);
});

it('documents the feature flags screen, its slots, its rows and the minimum Atrium', function (string $path): void {
    $doc = pennantPlusDoc($path);

    expect(pennantPlusMinimumAtrium())->not->toBe('');

    foreach (['pennantplus.features', 'header.actions', '`before`', '`after`', 'row.actions', 'FeatureValueRow', 'refactor-circus/keen', pennantPlusMinimumAtrium()] as $promise) {
        expect($doc)->toContain($promise);
    }
})->with([
    'README' => 'README.md',
    'Boost skill' => 'resources/boost/skills/pennantplus-development/SKILL.md',
]);

it('says row permissions are Gate abilities given the FeatureValueRow and its raw scope', function (string $path): void {
    $doc = pennantPlusDoc($path);

    expect($doc)->toContain('Gate ability')
        ->and($doc)->toContain('$record->value->scope');
})->with([
    'README' => 'README.md',
    'Boost skill' => 'resources/boost/skills/pennantplus-development/SKILL.md',
]);
