<?php

declare(strict_types=1);

namespace RefactorCircus\PennantPlus\Domains\Feature\Http\Requests;

use Illuminate\Http\Response;
use RefactorCircus\PennantPlus\Domains\Feature\Actions\PurgeFeatureAction;
use RefactorCircus\PennantPlus\Domains\Feature\Data\PurgeFeatureData;
use RefactorCircus\PennantPlus\Http\Request;

final class PurgeFeatureRequest extends Request
{
    public function rules(): array
    {
        return PurgeFeatureData::rules();
    }

    public function persist(): Response
    {
        app(PurgeFeatureAction::class)->execute(PurgeFeatureData::fromValidated($this->validated()));

        return response()->noContent();
    }

    protected function prepareForValidation(): void
    {
        $this->mergeRouteParameter('feature');
    }
}
