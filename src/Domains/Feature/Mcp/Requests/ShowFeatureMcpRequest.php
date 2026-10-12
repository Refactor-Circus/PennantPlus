<?php

declare(strict_types=1);

namespace RefactorCircus\PennantPlus\Domains\Feature\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\PennantPlus\Domains\Feature\Actions\ShowFeatureAction;
use RefactorCircus\PennantPlus\Domains\Feature\Data\ShowFeatureData;
use RefactorCircus\PennantPlus\Domains\Feature\Http\Resources\FeatureResource;
use RefactorCircus\PennantPlus\Mcp\Request;

final class ShowFeatureMcpRequest extends Request
{
    protected function rules(): array
    {
        return ShowFeatureData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        return (new FeatureResource(
            app(ShowFeatureAction::class)->execute(ShowFeatureData::fromValidated($validated)),
        ))->toStructuredResponse();
    }
}
