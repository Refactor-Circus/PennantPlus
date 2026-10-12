<?php

declare(strict_types=1);

namespace RefactorCircus\PennantPlus\Domains\Feature\Mcp\Requests;

use Laravel\Mcp\Response;
use RefactorCircus\PennantPlus\Domains\Feature\Actions\PurgeFeatureAction;
use RefactorCircus\PennantPlus\Domains\Feature\Data\PurgeFeatureData;
use RefactorCircus\PennantPlus\Mcp\Request;

final class PurgeFeatureMcpRequest extends Request
{
    protected function rules(): array
    {
        return PurgeFeatureData::rules();
    }

    protected function handle(array $validated): Response
    {
        app(PurgeFeatureAction::class)->execute(PurgeFeatureData::fromValidated($validated));

        return Response::text('Feature purged.');
    }
}
