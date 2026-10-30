<?php

declare(strict_types=1);

use RefactorCircus\Atrium\Domains\Screens\Data\SectionDefinition;
use RefactorCircus\Atrium\Domains\Screens\Data\TabDefinition;
use RefactorCircus\Atrium\Facades\Atrium;
use RefactorCircus\Atrium\Testing\ResetsScreenExtensions;
use RefactorCircus\PennantPlus\Tests\TestCase;

/*
 * What one test registers on the screen extensions registry is gone in the
 * next: the package test case resets it.
 */

it('resets screen extensions in the package test case', function (): void {
    expect(class_uses_recursive(TestCase::class))->toHaveKey(ResetsScreenExtensions::class);
});

test('a test registers a tab and a section on the feature flags screen', function (): void {
    Atrium::screens()
        ->tab('pennantplus.features', TabDefinition::make('acme.isolation')->view('acme::isolation')->public())
        ->section('pennantplus.features', 'before', SectionDefinition::make('acme.isolation')->view('acme::isolation')->public());

    expect(Atrium::screens()->tabs('pennantplus.features'))->toHaveCount(1)
        ->and(Atrium::screens()->sections('pennantplus.features', 'before'))->toHaveCount(1);
});

test('the next test finds the registry empty', function (): void {
    expect(Atrium::screens()->tabs('pennantplus.features'))->toBe([])
        ->and(Atrium::screens()->sections('pennantplus.features', 'before'))->toBe([]);
})->depends('a test registers a tab and a section on the feature flags screen');
