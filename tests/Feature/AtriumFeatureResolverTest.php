<?php

declare(strict_types=1);

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use JayI\Atrium\Domains\Navigation\Data\NavItem;
use JayI\Atrium\Domains\Navigation\Services\NavigationRegistry;
use JayI\Atrium\Facades\Atrium;
use JayI\PennantPlus\Domains\Feature\Services\FeatureGate;
use Laravel\Pennant\Feature;

beforeEach(function (): void {
    Feature::define('BillingSupportFeature', fn (): bool => true);
    Feature::define('InvoiceToolFeature', fn (mixed $scope): bool => $scope === null ? true : Feature::for(null)->active('InvoiceToolFeature'));
});

function atriumRequest(?Authenticatable $user = null): Request
{
    Feature::flushCache();

    $request = Request::create('/atrium');
    $request->setUserResolver(fn (): ?Authenticatable => $user);

    return $request;
}

it('answers Atrium from the global value for a global-only feature', function (): void {
    $user = makeUser();

    Feature::for($user)->deactivate('BillingSupportFeature');

    expect(Atrium::featureEnabled('BillingSupportFeature', atriumRequest($user)))->toBeTrue();

    Feature::for(null)->deactivate('BillingSupportFeature');

    expect(Atrium::featureEnabled('BillingSupportFeature', atriumRequest($user)))->toBeFalse();
});

it('answers Atrium per user for any other feature', function (): void {
    $user = makeUser();

    Feature::for($user)->deactivate('InvoiceToolFeature');

    expect(Atrium::featureEnabled('InvoiceToolFeature', atriumRequest($user)))->toBeFalse()
        ->and(Atrium::featureEnabled('InvoiceToolFeature', atriumRequest()))->toBeTrue();
});

it('lets global_only make Pennant decide global visibility alone', function (): void {
    config()->set('pennantplus.gate.global_only', ['*']);

    $user = makeUser();

    Feature::for($user)->deactivate('InvoiceToolFeature');

    expect(Atrium::featureEnabled('InvoiceToolFeature', atriumRequest($user)))->toBeTrue();
});

it('lets bypassed users through in Atrium too', function (): void {
    $user = makeUser();

    app(FeatureGate::class)->bypassUsing(fn (Authenticatable $candidate): bool => $candidate->getAuthIdentifier() === $user->getKey());

    Feature::for(null)->deactivate('InvoiceToolFeature');

    expect(Atrium::featureEnabled('InvoiceToolFeature', atriumRequest($user)))->toBeTrue()
        ->and(Atrium::featureEnabled('InvoiceToolFeature', atriumRequest()))->toBeFalse();
});

it('hides Atrium navigation gated by a feature that is off', function (): void {
    Atrium::nav(NavItem::make('Invoices')->url('/invoices')->feature('InvoiceToolFeature'));

    Feature::for(null)->deactivate('InvoiceToolFeature');

    $labels = array_map(fn (NavItem $item): string => $item->label, app(NavigationRegistry::class)->items(atriumRequest()));

    expect($labels)->not->toContain('Invoices');

    Feature::for(null)->activate('InvoiceToolFeature');

    $labels = array_map(fn (NavItem $item): string => $item->label, app(NavigationRegistry::class)->items(atriumRequest()));

    expect($labels)->toContain('Invoices');
});
