<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Laravel\Pennant\Events\FeatureUpdated;
use Laravel\Pennant\Feature;
use RefactorCircus\PennantPlus\Domains\Feature\Actions\PurgeFeatureAction;
use RefactorCircus\PennantPlus\Domains\Feature\Actions\UpdateFeatureValueAction;
use RefactorCircus\PennantPlus\Domains\Feature\Data\PurgeFeatureData;
use RefactorCircus\PennantPlus\Domains\Feature\Data\UpdateFeatureValueData;
use RefactorCircus\PennantPlus\Domains\Feature\Events\FeaturePurgedActionEvent;
use RefactorCircus\PennantPlus\Domains\Feature\Events\FeatureValueUpdatedActionEvent;

it('rolls back every write and fires no finished event when storing a value fails', function (): void {
    Feature::for('team-a')->activate('beta', 'scoped');

    Event::fake([FeatureValueUpdatedActionEvent::class]);
    Event::listen(FeatureUpdated::class, fn (): never => throw new RuntimeException('Storage failed.'));

    expect(fn (): mixed => app(UpdateFeatureValueAction::class)->execute(
        new UpdateFeatureValueData('beta', true, scope: Feature::serializeScope(null)),
    ))->toThrow(RuntimeException::class, 'Storage failed.');

    Event::assertNotDispatched(FeatureValueUpdatedActionEvent::class);

    expect(DB::table('features')->where('name', 'beta')->pluck('value', 'scope')->all())
        ->toBe(['team-a' => '"scoped"']);
});

it('keeps every value and fires no finished event when a purge fails', function (): void {
    Feature::for('team-a')->activate('beta', 'scoped');

    Event::fake([FeaturePurgedActionEvent::class]);

    $failing = true;

    DB::listen(function () use (&$failing): void {
        if ($failing) {
            throw new RuntimeException('Storage failed.');
        }
    });

    expect(fn (): mixed => app(PurgeFeatureAction::class)->execute(new PurgeFeatureData('beta')))
        ->toThrow(RuntimeException::class, 'Storage failed.');

    Event::assertNotDispatched(FeaturePurgedActionEvent::class);

    $failing = false;

    expect(DB::table('features')->where('name', 'beta')->pluck('value', 'scope')->all())
        ->toBe(['team-a' => '"scoped"']);
});
