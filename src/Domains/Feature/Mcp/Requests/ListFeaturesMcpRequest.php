<?php

declare(strict_types=1);

namespace RefactorCircus\PennantPlus\Domains\Feature\Mcp\Requests;

use RefactorCircus\PennantPlus\Domains\Feature\Actions\ListFeaturesAction;
use RefactorCircus\PennantPlus\Domains\Feature\Http\Resources\FeatureResource;
use RefactorCircus\PennantPlus\Mcp\Request;
use Laravel\Mcp\ResponseFactory;

final class ListFeaturesMcpRequest extends Request
{
    protected function rules(): array
    {
        return ListFeaturesAction::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        return $this->structuredCollection(
            FeatureResource::collection(app(ListFeaturesAction::class)->execute())->resolve(),
        );
    }
}
