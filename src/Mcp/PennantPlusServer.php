<?php

declare(strict_types=1);

namespace RefactorCircus\PennantPlus\Mcp;

use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\ToolSearch;
use RefactorCircus\Foundation\Mcp\Server;
use RefactorCircus\PennantPlus\Domains\Feature\Mcp\Tools\CheckFeatureTool;
use RefactorCircus\PennantPlus\Domains\Feature\Mcp\Tools\ForgetFeatureValueTool;
use RefactorCircus\PennantPlus\Domains\Feature\Mcp\Tools\ListFeaturesTool;
use RefactorCircus\PennantPlus\Domains\Feature\Mcp\Tools\ListFeatureValuesTool;
use RefactorCircus\PennantPlus\Domains\Feature\Mcp\Tools\PurgeFeatureTool;
use RefactorCircus\PennantPlus\Domains\Feature\Mcp\Tools\SetFeatureValueTool;
use RefactorCircus\PennantPlus\Domains\Feature\Mcp\Tools\ShowFeatureTool;
use RefactorCircus\PennantPlus\Domains\Scope\Mcp\Tools\ListScopeTypesTool;
use RefactorCircus\PennantPlus\Domains\Scope\Mcp\Tools\SearchScopeModelsTool;
use RefactorCircus\PennantPlus\Mcp\Tools\ListPennantPlusHistoryTool;

#[Name('PennantPlus')]
#[Version('1.0.0')]
#[Instructions('Manage Laravel Pennant feature flags. Every feature has one global value (the null scope); a scope such as a user follows it until given its own value, which then overrides it. Setting a global value forgets every other scope value of that feature. Use list-scope-types and search-scope-models to find a scope, check-feature to see how a feature resolves for it, and set-feature-value / forget-feature-value / purge-feature to change stored values.')]
final class PennantPlusServer extends Server
{
    /**
     * Every tool the server offers, behind ToolSearch. Also registered with
     * Cortex when it is installed.
     *
     * @var array<int, class-string<Tool>>
     */
    public const array TOOLS = [
        // Features
        ListFeaturesTool::class,
        ShowFeatureTool::class,
        CheckFeatureTool::class,
        PurgeFeatureTool::class,

        // Stored values
        ListFeatureValuesTool::class,
        SetFeatureValueTool::class,
        ForgetFeatureValueTool::class,

        // Scopes
        ListScopeTypesTool::class,
        SearchScopeModelsTool::class,

        // History
        ListPennantPlusHistoryTool::class,
    ];

    /**
     * @var array<class-string<ToolSearch>, array<int, class-string<Tool>|Tool>>
     */
    protected array $tools = [
        ToolSearch::class => self::TOOLS,
    ];
}
