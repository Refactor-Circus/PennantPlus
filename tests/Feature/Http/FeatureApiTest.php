<?php

declare(strict_types=1);

use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use RefactorCircus\PennantPlus\Tests\Fixtures\Features\Billing\InvoicingFeature;
use Laravel\Pennant\Feature;

beforeEach(function (): void {
    config(['pennantplus.scopes' => [User::class => ['search' => ['name', 'email'], 'title' => 'email']]]);
});

it('lists features with their stored global value and override count', function (): void {
    $ada = makeUser();

    Feature::for(null)->activate('beta');
    Feature::for($ada)->deactivate('beta');

    $this->getJson(route('pennantplus.features.index'))
        ->assertOk()
        ->assertJsonPath('data.0.name', 'beta')
        ->assertJsonPath('data.0.global', true)
        ->assertJsonPath('data.0.global_stored', true)
        ->assertJsonPath('data.0.overrides', 1);
});

it('shows a class-based feature by its class name', function (): void {
    $this->getJson(route('pennantplus.features.show', InvoicingFeature::class))
        ->assertOk()
        ->assertJsonPath('data.name', InvoicingFeature::class)
        ->assertJsonPath('data.value', false);
});

it('checks a feature for a model scope', function (): void {
    $ada = makeUser();

    Feature::define('beta', fn (mixed $scope): bool => true);
    Feature::for($ada)->deactivate('beta');

    $this->getJson(route('pennantplus.features.check', ['feature' => 'beta', 'scope_type' => User::class, 'scope_id' => (string) $ada->getKey()]))
        ->assertOk()
        ->assertJsonPath('data.scope', Feature::serializeScope($ada))
        ->assertJsonPath('data.global', true)
        ->assertJsonPath('data.value', false)
        ->assertJsonPath('data.active', false)
        ->assertJsonPath('data.gate', false);
});

it('checks a feature globally when no scope is given', function (): void {
    Feature::define('beta', fn (): bool => true);

    $this->getJson(route('pennantplus.features.check', 'beta'))
        ->assertOk()
        ->assertJsonPath('data.scope', Feature::serializeScope(null))
        ->assertJsonPath('data.value', true)
        ->assertJsonPath('data.gate', true);
});

it('rejects a check for a model that does not exist', function (): void {
    $this->getJson(route('pennantplus.features.check', ['feature' => 'beta', 'scope_type' => User::class, 'scope_id' => '999']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('scope_id');
});

it('sets a global value and forgets every user value of the feature', function (): void {
    $ada = makeUser();

    Feature::for($ada)->activate('beta');

    $this->putJson(route('pennantplus.values.update'), ['feature' => 'beta', 'scope_type' => 'global', 'value' => false])
        ->assertOk()
        ->assertJsonPath('data.global', true)
        ->assertJsonPath('data.value', false);

    expect(DB::table('features')->where('name', 'beta')->pluck('scope')->all())->toBe([Feature::serializeScope(null)]);
});

it('sets a rich value for a model scope', function (): void {
    $ada = makeUser();

    $this->putJson(route('pennantplus.values.update'), [
        'feature' => 'plan',
        'scope_type' => User::class,
        'scope_id' => (string) $ada->getKey(),
        'value' => ['tier' => 'gold'],
    ])->assertOk()->assertJsonPath('data.scope', Feature::serializeScope($ada));

    Feature::flushCache();

    expect(Feature::for($ada)->value('plan'))->toBe(['tier' => 'gold']);
});

it('validates a value update', function (): void {
    $this->putJson(route('pennantplus.values.update'), ['scope_type' => 'nope'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['feature', 'scope_type', 'value']);
});

it('forgets a stored value by its serialized scope', function (): void {
    $ada = makeUser();

    Feature::for($ada)->activate('beta');

    $this->deleteJson(route('pennantplus.values.destroy'), ['feature' => 'beta', 'scope' => Feature::serializeScope($ada)])
        ->assertNoContent();

    $this->assertDatabaseMissing('features', ['name' => 'beta']);
});

it('purges a feature for every scope', function (): void {
    Feature::for(null)->activate('beta');
    Feature::for(makeUser())->activate('beta');
    Feature::for(null)->activate('reports');

    $this->deleteJson(route('pennantplus.features.destroy', 'beta'))->assertNoContent();

    $this->assertDatabaseMissing('features', ['name' => 'beta']);
    $this->assertDatabaseHas('features', ['name' => 'reports']);
});

it('lists stored values filtered by scope', function (): void {
    $ada = makeUser();

    Feature::for(null)->activate('beta');
    Feature::for($ada)->activate('reports');

    $this->getJson(route('pennantplus.values.index', ['scope' => User::class]))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.feature', 'reports')
        ->assertJsonPath('data.0.scope_id', (string) $ada->getKey())
        ->assertJsonPath('data.0.title', 'ada@example.com')
        ->assertJsonPath('meta.total', 1);
});

it('lists the scope types', function (): void {
    $this->getJson(route('pennantplus.scopes.index'))
        ->assertOk()
        ->assertJsonPath('data.0.type', 'global')
        ->assertJsonPath('data.1.type', User::class)
        ->assertJsonPath('data.1.searchable', true)
        ->assertJsonPath('data.2.type', 'other');
});

it('searches the models of a scope type', function (): void {
    $bob = makeUser('bob@example.com');

    $this->getJson(route('pennantplus.scopes.models', ['type' => User::class, 'q' => 'bob']))
        ->assertOk()
        ->assertJsonPath('data.0.id', (string) $bob->getKey())
        ->assertJsonPath('data.0.scope', Feature::serializeScope($bob));

    $this->getJson(route('pennantplus.scopes.models', ['type' => 'App\\Nope', 'q' => 'bob']))->assertNotFound();
});

it('checks the configured ability on every endpoint', function (): void {
    config(['pennantplus.ability' => 'manageFeatures']);
    Gate::define('manageFeatures', fn (?User $user): bool => false);

    $this->getJson(route('pennantplus.features.index'))->assertForbidden();
    $this->putJson(route('pennantplus.values.update'), ['feature' => 'beta', 'scope_type' => 'global', 'value' => true])->assertForbidden();

    $this->assertDatabaseMissing('features', ['name' => 'beta']);
});
