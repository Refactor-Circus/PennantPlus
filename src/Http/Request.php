<?php

declare(strict_types=1);

namespace JayI\PennantPlus\Http;

use Illuminate\Foundation\Http\FormRequest;
use JayI\PennantPlus\FeatureFlagManager;
use Symfony\Component\HttpFoundation\Response;

/**
 * Base HTTP request.
 *
 * Validation rules come from the Action the request wraps, and `persist()`
 * calls that same Action. Every request is authorized against the
 * `pennantplus.ability` Gate ability when one is configured.
 */
abstract class Request extends FormRequest
{
    public function authorize(): bool
    {
        return app(FeatureFlagManager::class)->allowsManagement($this->user());
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }

    /**
     * Execute the request's use case and build the response.
     */
    abstract public function persist(): Response;

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
