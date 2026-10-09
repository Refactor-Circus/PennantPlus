<?php

declare(strict_types=1);

namespace RefactorCircus\PennantPlus\Domains\Feature\Mcp\Requests;

use RefactorCircus\PennantPlus\Domains\Feature\Actions\PurgeFeatureAction;
use RefactorCircus\PennantPlus\Mcp\Request;
use Laravel\Mcp\Response;

final class PurgeFeatureMcpRequest extends Request
{
    protected function rules(): array
    {
        return PurgeFeatureAction::rules();
    }

    protected function handle(array $validated): Response
    {
        app(PurgeFeatureAction::class)->execute((string) $validated['feature']);

        return Response::text('Feature purged.');
    }
}
