<?php

declare(strict_types=1);

use Illuminate\Foundation\Auth\User;
use Laravel\Mcp\Server\Testing\TestResponse;
use Laravel\Mcp\Server\Tool;
use RefactorCircus\Keystone\Testing\McpTools;
use RefactorCircus\PennantPlus\Mcp\PennantPlusServer;
use RefactorCircus\PennantPlus\Tests\CortexTestCase;
use RefactorCircus\PennantPlus\Tests\TestCase;

uses(TestCase::class)->in('Feature');
uses(CortexTestCase::class)->in('Cortex');

/**
 * Call a PennantPlus tool as an MCP client does, through tool search, with
 * its answer checked against the tool's output schema.
 *
 * @param  class-string<Tool>  $tool
 * @param  array<string, mixed>  $arguments
 */
function mcpTool(string $tool, array $arguments = []): TestResponse
{
    return McpTools::call(PennantPlusServer::class, $tool, $arguments);
}

function makeUser(string $email = 'ada@example.com'): User
{
    return User::forceCreate(['name' => 'Ada', 'email' => $email, 'password' => 'x']);
}
