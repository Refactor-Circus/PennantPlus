<?php

declare(strict_types=1);

namespace RefactorCircus\PennantPlus\Domains\Feature\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\PennantPlus\Domains\Feature\Actions\ListFeatureValuesAction;
use RefactorCircus\PennantPlus\Domains\Feature\Data\ListFeatureValuesData;
use RefactorCircus\PennantPlus\Domains\Feature\Http\Resources\FeatureValueResource;
use RefactorCircus\PennantPlus\Mcp\Request;

final class ListFeatureValuesMcpRequest extends Request
{
    protected function rules(): array
    {
        return ListFeatureValuesData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $values = app(ListFeatureValuesAction::class)->execute(ListFeatureValuesData::fromValidated($validated));

        return FeatureValueResource::collection($values)->toStructuredResponse();
    }
}
