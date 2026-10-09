<?php

declare(strict_types=1);

namespace RefactorCircus\PennantPlus\Domains\Feature\Mcp\Requests;

use RefactorCircus\PennantPlus\Domains\Feature\Actions\CheckFeatureAction;
use RefactorCircus\PennantPlus\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;

final class CheckFeatureMcpRequest extends Request
{
    protected function rules(): array
    {
        return CheckFeatureAction::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        return Response::structured(app(CheckFeatureAction::class)->execute(
            (string) $validated['feature'],
            $this->features()->scopeFromInput($validated),
        ));
    }
}
