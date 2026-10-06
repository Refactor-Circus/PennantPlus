<?php

declare(strict_types=1);

namespace JayI\PennantPlus\Http;

use JayI\Foundation\Http\Requests\Request as FoundationRequest;
use JayI\PennantPlus\Domains\Feature\Services\FeatureFlagManager;

/**
 * Base HTTP request.
 *
 * Validation rules come from the Action the request wraps, and `persist()`
 * calls that same Action. Feature flags have no model to hold a policy, so
 * every request is authorized against the `pennantplus.ability` Gate ability
 * when one is configured, rather than through the Foundation authorizer.
 */
abstract class Request extends FoundationRequest
{
    public function authorize(): bool
    {
        return app(FeatureFlagManager::class)->allowsManagement($this->user());
    }

    /**
     * Feed a route parameter into validation under the same name.
     */
    protected function mergeRouteParameter(string $name): void
    {
        $value = $this->route($name);

        if (is_string($value)) {
            $this->merge([$name => $value]);
        }
    }
}
