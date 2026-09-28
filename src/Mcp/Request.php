<?php

declare(strict_types=1);

namespace JayI\PennantPlus\Mcp;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Validator;
use JayI\PennantPlus\FeatureFlagManager;
use Laravel\Mcp\Request as McpRequest;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;

/**
 * Base MCP request: mirrors the HTTP FormRequest `persist()` pattern so tools
 * stay thin and both surfaces resolve the same Actions.
 */
abstract class Request extends McpRequest
{
    final public function persist(): Response|ResponseFactory
    {
        try {
            if (! $this->authorize()) {
                return Response::error('Unauthorized.');
            }

            return $this->handle($this->validated());
        } catch (ModelNotFoundException) {
            return Response::error('Not found.');
        }
    }

    /**
     * Handle the validated tool call and return a response.
     *
     * @param  array<string, mixed>  $validated
     */
    abstract protected function handle(array $validated): Response|ResponseFactory;

    /**
     * Wrap a list in a `data` envelope: `Response::structured([])` throws, so
     * an empty list must ship inside a non-empty payload.
     *
     * @param  array<int, mixed>  $items
     * @param  array<string, mixed>  $extra
     */
    protected function structuredCollection(array $items, array $extra = []): ResponseFactory
    {
        return Response::structured(['data' => $items, ...$extra]);
    }

    /**
     * Validation rules for the tool's input.
     *
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [];
    }

    /**
     * Authorize the tool call against the `pennantplus.ability` Gate ability
     * when one is configured.
     */
    protected function authorize(): bool
    {
        return $this->features()->allowsManagement($this->user());
    }

    protected function features(): FeatureFlagManager
    {
        return app(FeatureFlagManager::class);
    }

    /**
     * The validated, safe input.
     *
     * @return array<string, mixed>
     */
    protected function validated(): array
    {
        return Validator::validate($this->all(), $this->rules());
    }
}
