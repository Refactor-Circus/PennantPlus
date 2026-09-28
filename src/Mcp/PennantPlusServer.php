<?php

declare(strict_types=1);

namespace JayI\PennantPlus\Mcp;

use JayI\PennantPlus\Mcp\Tools\CheckFeatureTool;
use JayI\PennantPlus\Mcp\Tools\ForgetFeatureValueTool;
use JayI\PennantPlus\Mcp\Tools\ListFeaturesTool;
use JayI\PennantPlus\Mcp\Tools\ListFeatureValuesTool;
use JayI\PennantPlus\Mcp\Tools\ListScopeTypesTool;
use JayI\PennantPlus\Mcp\Tools\PurgeFeatureTool;
use JayI\PennantPlus\Mcp\Tools\SearchScopeModelsTool;
use JayI\PennantPlus\Mcp\Tools\SetFeatureValueTool;
use JayI\PennantPlus\Mcp\Tools\ShowFeatureTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\ToolSearch;

#[Name('PennantPlus')]
#[Version('1.0.0')]
#[Instructions('Manage Laravel Pennant feature flags. Every feature has one global value (the null scope); a scope such as a user follows it until given its own value, which then overrides it. Setting a global value forgets every other scope value of that feature. Use list-scope-types and search-scope-models to find a scope, check-feature to see how a feature resolves for it, and set-feature-value / forget-feature-value / purge-feature to change stored values.')]
final class PennantPlusServer extends Server
{
    /**
     * @var array<class-string<ToolSearch>, array<int, class-string<Tool>|Tool>>
     */
    protected array $tools = [
        ToolSearch::class => [
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
        ],
    ];
}
