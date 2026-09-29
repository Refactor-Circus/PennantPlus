<?php

declare(strict_types=1);

use JayI\Cortex\Actions\CreateMcpInstructionVersionAction;
use JayI\Cortex\Actions\CreateToolDescriptionVersionAction;
use JayI\Cortex\Mcp\McpServerRegistry;
use JayI\Cortex\Tools\ToolRegistry;
use JayI\PennantPlus\Cortex\CortexIntegration;
use JayI\PennantPlus\Mcp\PennantPlusServer;
use JayI\PennantPlus\Mcp\Tools\ListFeaturesTool;
use Laravel\Ai\Contracts\Tool as AgentTool;
use Laravel\Ai\Tools\Request;
use Laravel\Mcp\Server\Transport\FakeTransporter;
use Laravel\Pennant\Feature;

it('registers the MCP server with Cortex', function (): void {
    $servers = app(McpServerRegistry::class);

    expect($servers->has('pennantplus'))->toBeTrue()
        ->and($servers->get('pennantplus'))->toBe(PennantPlusServer::class)
        ->and($servers->defaultInstructions('pennantplus'))->toStartWith('Manage Laravel Pennant feature flags');
});

it('offers every PennantPlus tool to Cortex agents under its own name', function (): void {
    $tools = app(ToolRegistry::class);

    $names = array_map(fn (string $class): string => app($class)->name(), PennantPlusServer::TOOLS);

    expect(PennantPlusServer::TOOLS)->toHaveCount(9)
        ->and(array_diff($names, $tools->names()))->toBe([])
        ->and($tools->get('list-features-tool'))->toBeInstanceOf(AgentTool::class)
        ->and($tools->tagsFor('list-features-tool'))->toContain('pennantplus');
});

it('offers only the tools listed in config', function (): void {
    config()->set('pennantplus.cortex.tools', ['list-features-tool', 'check-feature-tool']);
    app()->forgetInstance(ToolRegistry::class);

    $tools = app(ToolRegistry::class);

    expect($tools->has('list-features-tool'))->toBeTrue()
        ->and($tools->has('check-feature-tool'))->toBeTrue()
        ->and($tools->has('set-feature-value-tool'))->toBeFalse();
});

it('registers the server under the configured name', function (): void {
    config()->set('pennantplus.cortex.server', 'flags');
    app()->forgetInstance(McpServerRegistry::class);

    $servers = app(McpServerRegistry::class);

    expect($servers->get('flags'))->toBe(PennantPlusServer::class)
        ->and($servers->has('pennantplus'))->toBeFalse();
});

it('serves the instructions published in Cortex', function (): void {
    app(CreateMcpInstructionVersionAction::class)->execute('pennantplus', ['content' => 'Never purge a feature.', 'publish' => true]);

    expect((new PennantPlusServer(new FakeTransporter))->createContext()->instructions)->toBe('Never purge a feature.');
});

it('serves tool descriptions published in Cortex, to MCP clients and agents alike', function (): void {
    app(CreateToolDescriptionVersionAction::class)->execute('list-features-tool', ['content' => 'List our rollout flags.', 'publish' => true]);

    expect(app(ListFeaturesTool::class)->description())->toBe('List our rollout flags.')
        ->and(app(ToolRegistry::class)->get('list-features-tool')->description())->toBe('List our rollout flags.');
});

it('lets an agent call a PennantPlus tool with its arguments', function (): void {
    Feature::activate('beta');

    // The tool takes its own request class; without the arguments it would
    // fail validation on `feature` rather than resolve the feature.
    $result = (string) app(ToolRegistry::class)->get('check-feature-tool')->handle(new Request(['feature' => 'beta']));

    expect($result)->toContain('"value":true');
});

it('stays out of Cortex when turned off', function (): void {
    $integration = app(CortexIntegration::class);

    expect($integration->active())->toBeTrue();

    config()->set('pennantplus.cortex.enabled', false);

    expect($integration->active())->toBeFalse()
        ->and($integration->instructions())->toBeNull()
        ->and($integration->description('list-features-tool'))->toBeNull();
});
