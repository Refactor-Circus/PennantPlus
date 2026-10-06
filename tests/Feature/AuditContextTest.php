<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use JayI\Foundation\Audit\Contracts\Auditable;
use JayI\PennantPlus\Domains\Feature\Actions\UpdateFeatureValueAction;
use JayI\PennantPlus\Domains\Feature\Events\FeatureValueUpdatedActionEvent;
use Laravel\Pennant\Feature;

it('records a stored flag value as an entry about no model, with the feature, scope and value', function (): void {
    Event::fake([FeatureValueUpdatedActionEvent::class]);

    app(UpdateFeatureValueAction::class)->execute('new-checkout', Feature::serializeScope(null), ['tier' => 'gold']);

    Event::assertDispatched(FeatureValueUpdatedActionEvent::class, function (FeatureValueUpdatedActionEvent $event): bool {
        expect($event)->toBeInstanceOf(Auditable::class)
            ->and($event->auditSubject())->toBeNull()
            ->and($event->auditContext())->toBe([
                'feature' => 'new-checkout',
                'scope' => Feature::serializeScope(null),
                'value' => ['tier' => 'gold'],
            ]);

        return true;
    });
});
