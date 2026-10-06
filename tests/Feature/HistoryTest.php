<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Gate;
use JayI\PennantPlus\Mcp\PennantPlusServer;
use JayI\PennantPlus\Mcp\Tools\ListPennantPlusHistoryTool;

it('answers the history route with 404 while no audit log is installed', function (): void {
    $this->getJson(route('pennantplus.history.index'))
        ->assertNotFound()
        ->assertJsonPath('message', 'No audit log is installed. Install jayi/keen to record history.');
});

it('lists the history tool on the MCP server under the package name', function (): void {
    expect(PennantPlusServer::TOOLS)->toContain(ListPennantPlusHistoryTool::class)
        ->and(app(ListPennantPlusHistoryTool::class)->name())->toBe('list-pennantplus-history-tool');
});

it('reports through the history tool that no audit log is installed', function (): void {
    mcpTool(ListPennantPlusHistoryTool::class)->assertHasErrors(['No audit log is installed']);
});

it('guards the history with the management ability', function (): void {
    Gate::define('manage-flags', fn (): bool => false);
    config()->set('pennantplus.ability', 'manage-flags');

    $this->getJson(route('pennantplus.history.index'))->assertForbidden();
});
