<?php

declare(strict_types=1);

use JayI\Atrium\Support\Icons;
use JayI\PennantPlus\Atrium\PennantPlugin;

it('gives its sidebar section its own icon', function (): void {
    [$group] = app(PennantPlugin::class)->navigationGroups();

    expect($group->icon)->toBe(Icons::svg('flag'))
        ->and($group->sort)->toBe(90);
});

it('collects its pages in that section', function (): void {
    $plugin = app(PennantPlugin::class);
    [$group] = $plugin->navigationGroups();

    $labels = array_values(array_unique(array_map(fn ($item): ?string => $item->group, $plugin->navigation())));

    expect($labels)->toBe([$group->name]);
});
