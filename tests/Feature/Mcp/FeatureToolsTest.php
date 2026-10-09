<?php

declare(strict_types=1);

use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Pennant\Feature;
use RefactorCircus\PennantPlus\Domains\Feature\Mcp\Tools\CheckFeatureTool;
use RefactorCircus\PennantPlus\Domains\Feature\Mcp\Tools\ForgetFeatureValueTool;
use RefactorCircus\PennantPlus\Domains\Feature\Mcp\Tools\ListFeaturesTool;
use RefactorCircus\PennantPlus\Domains\Feature\Mcp\Tools\ListFeatureValuesTool;
use RefactorCircus\PennantPlus\Domains\Feature\Mcp\Tools\PurgeFeatureTool;
use RefactorCircus\PennantPlus\Domains\Feature\Mcp\Tools\SetFeatureValueTool;
use RefactorCircus\PennantPlus\Domains\Feature\Mcp\Tools\ShowFeatureTool;
use RefactorCircus\PennantPlus\Domains\Scope\Mcp\Tools\ListScopeTypesTool;
use RefactorCircus\PennantPlus\Domains\Scope\Mcp\Tools\SearchScopeModelsTool;

beforeEach(function (): void {
    config(['pennantplus.scopes' => [User::class => ['search' => ['name', 'email'], 'title' => 'email']]]);
});

it('lists features with parity to the http payload', function (): void {
    Feature::for(null)->activate('beta');
    Feature::for(makeUser())->deactivate('beta');

    $http = $this->getJson(route('pennantplus.features.index'))->json('data');

    mcpTool(ListFeaturesTool::class)->assertOk()->assertStructuredContent(['data' => $http]);
});

it('lists zero features without erroring', function (): void {
    mcpTool(ListFeaturesTool::class)->assertOk()->assertStructuredContent(['data' => []]);
});

it('shows a feature with parity to the http payload', function (): void {
    Feature::for(null)->activate('beta');

    $http = $this->getJson(route('pennantplus.features.show', 'beta'))->json('data');

    mcpTool(ShowFeatureTool::class, ['feature' => 'beta'])->assertOk()->assertStructuredContent($http);
});

it('checks a feature for a model scope with parity to the http payload', function (): void {
    $ada = makeUser();

    Feature::define('beta', fn (): bool => true);
    Feature::for($ada)->deactivate('beta');

    $input = ['feature' => 'beta', 'scope_type' => User::class, 'scope_id' => (string) $ada->getKey()];

    $http = $this->getJson(route('pennantplus.features.check', $input))->json('data');

    mcpTool(CheckFeatureTool::class, $input)
        ->assertOk()
        ->assertStructuredContent($http)
        ->assertStructuredContent(fn (AssertableJson $json) => $json->where('value', false)->where('gate', false)->etc());
});

it('sets a JSON-encoded global value and forgets user values', function (): void {
    $ada = makeUser();

    Feature::for($ada)->activate('beta');

    mcpTool(SetFeatureValueTool::class, ['feature' => 'beta', 'scope_type' => 'global', 'value' => 'false'])
        ->assertOk()
        ->assertStructuredContent(fn (AssertableJson $json) => $json->where('global', true)->where('value', false)->etc());

    $this->assertDatabaseMissing('features', ['name' => 'beta', 'scope' => Feature::serializeScope($ada)]);
});

it('rejects a value that is not JSON', function (): void {
    mcpTool(SetFeatureValueTool::class, ['feature' => 'beta', 'scope_type' => 'global', 'value' => 'yes please'])
        ->assertHasErrors();

    $this->assertDatabaseMissing('features', ['name' => 'beta']);
});

it('forgets a stored value', function (): void {
    $ada = makeUser();

    Feature::for($ada)->activate('beta');

    mcpTool(ForgetFeatureValueTool::class, ['feature' => 'beta', 'scope' => Feature::serializeScope($ada)])->assertOk();

    $this->assertDatabaseMissing('features', ['name' => 'beta']);
});

it('purges a feature', function (): void {
    Feature::for(null)->activate('beta');

    mcpTool(PurgeFeatureTool::class, ['feature' => 'beta'])->assertOk()->assertSee('Feature purged.');

    $this->assertDatabaseMissing('features', ['name' => 'beta']);
});

it('lists stored values with page metadata', function (): void {
    $ada = makeUser();

    Feature::for($ada)->activate('reports');

    $http = $this->getJson(route('pennantplus.values.index', ['scope' => User::class]))->json('data');

    mcpTool(ListFeatureValuesTool::class, ['scope' => User::class])
        ->assertOk()
        ->assertStructuredContent(['data' => $http, 'meta' => ['current_page' => 1, 'last_page' => 1, 'total' => 1]]);
});

it('lists scope types and searches their models', function (): void {
    $bob = makeUser('bob@example.com');

    $types = $this->getJson(route('pennantplus.scopes.index'))->json('data');

    mcpTool(ListScopeTypesTool::class)->assertOk()->assertStructuredContent(['data' => $types]);

    mcpTool(SearchScopeModelsTool::class, ['type' => User::class, 'q' => 'bob'])
        ->assertOk()
        ->assertStructuredContent(fn (AssertableJson $json) => $json->where('data.0.scope', Feature::serializeScope($bob))->etc());

    mcpTool(SearchScopeModelsTool::class, ['type' => 'App\\Nope', 'q' => 'bob'])->assertHasErrors();
});

it('refuses every tool when the configured ability denies', function (): void {
    config(['pennantplus.ability' => 'manageFeatures']);
    Gate::define('manageFeatures', fn (?User $user): bool => false);

    mcpTool(ListFeaturesTool::class)->assertHasErrors();
    mcpTool(SetFeatureValueTool::class, ['feature' => 'beta', 'scope_type' => 'global', 'value' => 'true'])->assertHasErrors();

    $this->assertDatabaseMissing('features', ['name' => 'beta']);
});
