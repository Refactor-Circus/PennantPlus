<?php

declare(strict_types=1);

namespace RefactorCircus\PennantPlus\Domains\Feature\Mcp\Requests;

use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use RefactorCircus\PennantPlus\Domains\Feature\Actions\CheckFeatureAction;
use RefactorCircus\PennantPlus\Domains\Feature\Data\CheckFeatureData;
use RefactorCircus\PennantPlus\Mcp\Request;

final class CheckFeatureMcpRequest extends Request
{
    protected function rules(): array
    {
        return CheckFeatureData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        return Response::structured(app(CheckFeatureAction::class)->execute(CheckFeatureData::fromValidated($validated)));
    }
}
