<?php

declare(strict_types=1);

/*
 * What the README, CLAUDE.md, the product docs and the Boost skill promise.
 * Plain file reads, so no framework boot.
 */

function pennantPlusRoot(): string
{
    return dirname(__DIR__, 2);
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
