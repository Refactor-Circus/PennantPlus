<?php

declare(strict_types=1);

namespace RefactorCircus\PennantPlus\Mcp;

use RefactorCircus\Foundation\Mcp\Requests\Request as FoundationRequest;
use RefactorCircus\PennantPlus\Domains\Feature\Services\FeatureFlagManager;

/**
 * Base MCP request: mirrors the HTTP request's `persist()` pattern so tools
 * stay thin and both surfaces resolve the same Actions.
 *
 * Feature flags have no model to hold a policy, so every tool call is
 * authorized against the `pennantplus.ability` Gate ability when one is
 * configured, rather than through the Foundation authorizer.
 */
abstract class Request extends FoundationRequest
{
    protected function authorize(): bool
    {
        return $this->features()->allowsManagement($this->user());
    }

    protected function features(): FeatureFlagManager
    {
        return app(FeatureFlagManager::class);
    }
}
