<?php

declare(strict_types=1);

/*
 * What the README, CHANGELOG, CLAUDE.md, AGENTS.md, the product docs and the
 * Boost skill promise. Plain file reads, so no framework boot.
 */

function pennantPlusRoot(): string
{
    return dirname(__DIR__, 2);
}

function pennantPlusDocument(string $path): string
{
    return (string) file_get_contents(pennantPlusRoot().'/'.$path);
}

/**
 * The body of the Markdown section under `$heading`, up to the next heading
 * of the same or a higher level; empty when the heading is missing.
 */
function pennantPlusSection(string $markdown, string $heading): string
{
    $level = strspn($heading, '#');
    $lines = explode("\n", str_replace("\r\n", "\n", $markdown));
    $body = null;

    foreach ($lines as $line) {
        if ($body === null) {
            if (str_starts_with($line, $heading)) {
                $body = [];
            }

            continue;
        }

        if (preg_match('/^(#{1,'.$level.'}) /', $line) === 1) {
            break;
        }

        $body[] = $line;
    }

    return implode("\n", $body ?? []);
}

/**
 * The guidance CLAUDE.md and AGENTS.md share: everything before the Ring
 * Master section, which Ringside writes into each file its own way.
 */
function pennantPlusSharedGuidance(string $path): string
{
    $contents = str_replace("\r\n", "\n", pennantPlusDocument($path));
    $contents = str_replace("<!-- ring-master:start -->\n", '', $contents);

    return trim(explode('## Ring Master', $contents, 2)[0]);
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

const PENNANTPLUS_SKILL = 'resources/boost/skills/pennantplus-development/SKILL.md';

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

it('introduces PennantPlus as layered flags with a JSON API and an MCP server', function (): void {
    $readme = str_replace("\r\n", "\n", pennantPlusDocument('README.md'));
    $intro = explode("\n## ", explode("\n# PennantPlus\n", $readme, 2)[1] ?? '', 2)[0];

    expect($intro)
        ->toContain('Pennant')
        ->toContain('JSON API')
        ->toContain('MCP server')
        ->not->toContain('Atrium page')
        ->not->toContain('dashboard');
});

it('has no Atrium dashboard, screen extensions or theme switcher sections in the README', function (string $heading): void {
    expect(pennantPlusDocument('README.md'))->not->toMatch('/^#+ '.preg_quote($heading, '/').'\s*$/mi');
})->with(['Atrium dashboard', 'Screen extensions', "Atrium's theme switcher"]);

it('documents the Atrium module, its build and its sources in the README', function (): void {
    expect(pennantPlusSection(pennantPlusDocument('README.md'), '## Atrium module'))
        ->toContain('dist/atrium/')
        ->toContain('extra.atrium')
        ->toContain('atrium/')
        ->toContain('pnpm build')
        ->toContain('No PHP code in PennantPlus refers to Atrium');
});

it('documents the OpenAPI document and the TypeScript client in the README', function (): void {
    $section = pennantPlusSection(pennantPlusDocument('README.md'), '## OpenAPI and TypeScript client');

    expect($section)
        ->toContain('openapi.json')
        ->toContain('@refactor-circus/pennantplus-client')
        ->toContain('#v2.0.0')
        ->toContain('createPennantPlusClient(')
        ->toContain('composer client:generate');
});

it('names Atrium v2 as the optional UI, and Atrium only so outside the Atrium module section', function (): void {
    $readme = str_replace("\r\n", "\n", pennantPlusDocument('README.md'));
    $section = pennantPlusSection($readme, '## Atrium module');

    expect($readme)->toContain('Atrium v2')
        ->and($section)->not->toBe('');

    $lines = array_filter(
        explode("\n", str_replace("## Atrium module\n".$section, '', $readme)),
        fn (string $line): bool => stripos($line, 'atrium') !== false && ! str_contains($line, 'Atrium v2'),
    );

    expect(array_values($lines))->toBe([]);
});

it('mentions none of the removed Atrium pieces in the README or the Boost skill', function (string $path): void {
    $contents = pennantPlusDocument($path);

    foreach (['@pennantplusManages', 'FeatureValueRow', 'FeatureFlagAccess', 'resolve_features'] as $needle) {
        expect($contents)->not->toContain($needle);
    }
})->with(['README.md', PENNANTPLUS_SKILL]);

it('describes the API, the MCP server and the client in the Boost skill, and no page', function (): void {
    $skill = pennantPlusDocument(PENNANTPLUS_SKILL);

    expect($skill)
        ->toContain('/pennantplus/features')
        ->toContain('MCP')
        ->toContain('createPennantPlusClient')
        ->toContain('composer client:generate')
        ->not->toMatch('/\bpages?\b(?!=)/i')
        ->not->toMatch('/dashboard/i')
        ->not->toContain('The Atrium page')
        ->not->toContain('atrium.pennant')
        ->not->toContain('Atrium::screens()');
});

it('has a v2.0.0 changelog section with Breaking, Upgrade and Added', function (): void {
    $release = pennantPlusSection(pennantPlusDocument('CHANGELOG.md'), '## [v2.0.0]');

    $breaking = pennantPlusSection($release, '### Breaking');
    $upgrade = pennantPlusSection($release, '### Upgrade');
    $added = pennantPlusSection($release, '### Added');

    expect($breaking)
        ->toContain('extra.atrium.plugins')
        ->toContain('pennantplus.atrium.resolve_features')
        ->toContain('@pennantplusManages')
        ->toContain('FeatureFlagAccess')
        ->toContain('FeatureValueRow')
        ->toContain('resources/views')
        ->toContain('pennantplus-views')
        ->toContain('lang/en/pennantplus.php');

    expect($upgrade)
        ->toContain('Atrium v2')
        ->toContain('Pennant integration');

    expect($added)
        ->toContain('openapi.json')
        ->toContain('@refactor-circus/pennantplus-client')
        ->toContain('createPennantPlusClient');
});

it('keeps the agent guidance free of the PHP Atrium integration, names the generated artifacts and the Atrium module', function (string $path): void {
    $guidance = pennantPlusDocument($path);

    expect($guidance)
        ->not->toContain('src/Atrium')
        ->not->toContain('ResetsScreenExtensions')
        ->not->toContain('screen extensions registry')
        ->not->toContain('Atrium::css()')
        ->not->toContain('x-atrium')
        ->not->toContain('FeatureValueRow')
        ->not->toContain('FeatureFlagAccess')
        ->not->toContain('## Frontend')
        ->toContain('No PHP code in the package names Atrium')
        ->toContain('dist/atrium/')
        ->toContain('extra.atrium')
        ->toContain('pnpm build')
        ->toContain('openapi.json')
        ->toContain('client/')
        ->toContain('composer client:generate');
})->with(['CLAUDE.md', 'AGENTS.md']);

it('keeps CLAUDE.md and AGENTS.md identical in their shared guidance', function (): void {
    expect(pennantPlusSharedGuidance('AGENTS.md'))->toBe(pennantPlusSharedGuidance('CLAUDE.md'));
});
