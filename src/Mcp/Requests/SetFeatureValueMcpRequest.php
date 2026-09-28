<?php

declare(strict_types=1);

namespace JayI\PennantPlus\Mcp\Requests;

use JayI\PennantPlus\Actions\UpdateFeatureValueAction;
use JayI\PennantPlus\Http\Resources\FeatureValueResource;
use JayI\PennantPlus\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;

/**
 * The value arrives JSON-encoded, so booleans and richer values share one
 * string argument.
 */
final class SetFeatureValueMcpRequest extends Request
{
    protected function rules(): array
    {
        return [
            ...UpdateFeatureValueAction::rules(),
            'value' => ['required', 'json'],
        ];
    }

    protected function handle(array $validated): ResponseFactory
    {
        $value = app(UpdateFeatureValueAction::class)->execute(
            (string) $validated['feature'],
            $this->features()->scopeFromInput($validated),
            json_decode((string) $validated['value'], true, flags: JSON_THROW_ON_ERROR),
        );

        return Response::structured((new FeatureValueResource($value))->resolve());
    }
}
