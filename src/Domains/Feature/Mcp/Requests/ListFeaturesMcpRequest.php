<?php

declare(strict_types=1);

namespace JayI\PennantPlus\Domains\Feature\Mcp\Requests;

use JayI\PennantPlus\Domains\Feature\Actions\ListFeaturesAction;
use JayI\PennantPlus\Domains\Feature\Http\Resources\FeatureResource;
use JayI\PennantPlus\Mcp\Request;
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
