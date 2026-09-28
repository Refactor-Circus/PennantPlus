<?php

declare(strict_types=1);

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Route;
use JayI\PennantPlus\FeatureGate;
use JayI\PennantPlus\Http\Middleware\EnsureFeatureActive;
use Laravel\Pennant\Feature;

beforeEach(function (): void {
    Feature::define('BillingSupportFeature', fn (): bool => true);
    Feature::define('InvoiceToolFeature', fn (mixed $scope): bool => $scope === null ? true : Feature::for(null)->active('InvoiceToolFeature'));
});

function gateAllows(string $feature, ?Authenticatable $user): bool
{
    Feature::flushCache();

    return app(FeatureGate::class)->allows($feature, $user);
}

it('checks a global-only feature against the global scope alone', function (): void {
    $user = makeUser();

    Feature::for($user)->deactivate('BillingSupportFeature');

    expect(gateAllows('BillingSupportFeature', $user))->toBeTrue();

    Feature::for(null)->deactivate('BillingSupportFeature');

    expect(gateAllows('BillingSupportFeature', $user))->toBeFalse();
});

it('requires any other feature to be active globally and for the user', function (): void {
    $user = makeUser();

    expect(gateAllows('InvoiceToolFeature', $user))->toBeTrue();

    Feature::for($user)->deactivate('InvoiceToolFeature');

    expect(gateAllows('InvoiceToolFeature', $user))->toBeFalse();

    Feature::for($user)->activate('InvoiceToolFeature');
    Feature::for(null)->deactivate('InvoiceToolFeature');

    expect(gateAllows('InvoiceToolFeature', $user))->toBeFalse();
});

it('checks only the global value without a user', function (): void {
    expect(gateAllows('InvoiceToolFeature', null))->toBeTrue();

    Feature::for(null)->deactivate('InvoiceToolFeature');

    expect(gateAllows('InvoiceToolFeature', null))->toBeFalse();
});

it('lets users the bypass callback accepts through every feature', function (): void {
    [$ada, $bob] = [makeUser('ada@example.com'), makeUser('bob@example.com')];

    app(FeatureGate::class)->bypassUsing(fn (Authenticatable $user): bool => $user->getAuthIdentifier() === $ada->getKey());

    Feature::for(null)->deactivate('BillingSupportFeature');

    expect(gateAllows('BillingSupportFeature', $ada))->toBeTrue()
        ->and(gateAllows('BillingSupportFeature', $bob))->toBeFalse();
});

it('reads the global-only patterns from config', function (): void {
    config(['pennantplus.gate.global_only' => ['Billing*']]);

    expect(app(FeatureGate::class)->isGlobalOnly('BillingSupportFeature'))->toBeTrue()
        ->and(app(FeatureGate::class)->isGlobalOnly('InvoiceToolFeature'))->toBeFalse();
});

it('forbids a route whose feature the gate refuses', function (): void {
    Route::middleware(EnsureFeatureActive::class.':BillingSupportFeature,InvoiceToolFeature')
        ->get('gated', fn (): string => 'ok');

    $user = makeUser();

    $this->actingAs($user)->get('gated')->assertOk();

    Feature::for($user)->deactivate('InvoiceToolFeature');
    Feature::flushCache();

    $this->actingAs($user)->get('gated')->assertForbidden();
});
