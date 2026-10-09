<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Gate;
use RefactorCircus\Foundation\Audit\Contracts\AuditTrail;
use RefactorCircus\Foundation\Audit\Data\AuditEntry;
use RefactorCircus\Foundation\Audit\Data\AuditFilter;
use RefactorCircus\Foundation\Audit\Data\AuditPage;

beforeEach(function (): void {
    Gate::define('viewAtrium', fn (): bool => true);
});

it('shows no history on the feature flags screen while no audit log is installed', function (): void {
    $this->actingAs(makeUser())->get(route('atrium.pennant.index'))
        ->assertOk()
        ->assertDontSee('data-testid="audit-trail"', false);
});

it('shows the package history on the feature flags screen', function (?string $feature): void {
    $trail = new class implements AuditTrail
    {
        public ?AuditFilter $filter = null;

        public function available(): bool
        {
            return true;
        }

        public function entries(AuditFilter $filter): AuditPage
        {
            $this->filter = $filter;

            return new AuditPage([
                new AuditEntry(1, 'pennantplus', 'feature_value.updated', 'web', CarbonImmutable::now(), '1', 'Ada'),
            ]);
        }
    };

    app()->instance(AuditTrail::class, $trail);

    $this->actingAs(makeUser())->get(route('atrium.pennant.index', array_filter(['feature' => $feature])))
        ->assertOk()
        ->assertSee('data-testid="audit-trail"', false)
        ->assertSee('feature_value.updated');

    // Flag values are about no model, so even a filtered feature shows the
    // package-wide history.
    expect($trail->filter?->source)->toBe('pennantplus')
        ->and($trail->filter?->subjectType)->toBeNull();
})->with([
    'every feature' => [null],
    'one feature' => ['new-checkout'],
]);
