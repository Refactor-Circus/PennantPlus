<?php

declare(strict_types=1);

namespace RefactorCircus\PennantPlus\Domains\Feature\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\PennantPlus\Domains\Feature\Actions\UpdateFeatureValueAction;
use RefactorCircus\PennantPlus\Domains\Feature\Data\UpdateFeatureValueData;
use RefactorCircus\PennantPlus\Domains\Feature\Http\Resources\FeatureValueResource;
use RefactorCircus\PennantPlus\Mcp\Request;

/**
 * The value arrives JSON-encoded, so booleans and richer values share one
 * string argument.
 */
final class SetFeatureValueMcpRequest extends Request
{
    protected function rules(): array
    {
        return UpdateFeatureValueData::rules(encoded: true);
    }

    protected function handle(array $validated): ResponseFactory
    {
        $value = app(UpdateFeatureValueAction::class)->execute(UpdateFeatureValueData::fromEncoded($validated));

        return (new FeatureValueResource($value))->toStructuredResponse();
    }
}
