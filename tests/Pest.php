<?php

declare(strict_types=1);

use Illuminate\Foundation\Auth\User;
use JayI\PennantPlus\Mcp\PennantPlusServer;
use JayI\PennantPlus\Tests\CortexTestCase;
use JayI\PennantPlus\Tests\TestCase;
use Laravel\Mcp\Request;
use Laravel\Mcp\Server\Testing\TestResponse;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Transport\FakeTransporter;
use Laravel\Mcp\Transport\JsonRpcResponse;

uses(TestCase::class)->in('Feature');
uses(CortexTestCase::class)->in('Cortex');

/**
 * Call a catalog tool the way a client reaches it: every PennantPlus tool
 * sits behind ToolSearch, so the call is routed through the execute_tools
 * entry point and the inner tool's own response is returned.
 *
 * @param  class-string<Tool>  $tool
 * @param  array<string, mixed>  $arguments
 */
function mcpTool(string $tool, array $arguments = []): TestResponse
{
    $entryPoints = (new PennantPlusServer(new FakeTransporter))->createContext()->tools();

    $execute = $entryPoints->firstOrFail(
        fn (Tool $candidate): bool => $candidate->name() === 'execute_tools',
    );

    $primitive = app($tool);

    $envelope = $execute->handle(new Request([
        'calls' => [['name' => $primitive->name(), 'arguments' => $arguments]],
    ]));

    /** @var array<string, mixed> $decoded */
    $decoded = json_decode(
        (string) collect($envelope)->firstOrFail()->content(),
        true,
        512,
        JSON_THROW_ON_ERROR,
    );

    /** @var array<string, mixed> $result */
    $result = $decoded['results'][0] ?? [];

    return new TestResponse($primitive, JsonRpcResponse::result(1, array_filter([
        'content' => $result['content'] ?? [],
        'structuredContent' => $result['structuredContent'] ?? null,
        'isError' => $result['isError'] ?? false,
    ], fn (mixed $value): bool => $value !== null)));
}

function makeUser(string $email = 'ada@example.com'): User
{
    return User::forceCreate(['name' => 'Ada', 'email' => $email, 'password' => 'x']);
}
