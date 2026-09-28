<?php

declare(strict_types=1);

namespace JayI\PennantPlus\Mcp\Requests;

use JayI\PennantPlus\Actions\ShowFeatureAction;
use JayI\PennantPlus\Http\Resources\FeatureResource;
use JayI\PennantPlus\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;

final class ShowFeatureMcpRequest extends Request
{
    protected function rules(): array
    {
        return ShowFeatureAction::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        return Response::structured((new FeatureResource(
            app(ShowFeatureAction::class)->execute((string) $validated['feature']),
        ))->resolve());
    }
}
