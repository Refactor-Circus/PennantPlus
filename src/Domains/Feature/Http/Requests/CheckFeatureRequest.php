<?php

declare(strict_types=1);

namespace RefactorCircus\PennantPlus\Domains\Feature\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\PennantPlus\Domains\Feature\Actions\CheckFeatureAction;
use RefactorCircus\PennantPlus\Domains\Feature\Data\CheckFeatureData;
use RefactorCircus\PennantPlus\Http\Request;

final class CheckFeatureRequest extends Request
{
    public function rules(): array
    {
        return CheckFeatureData::rules();
    }

    public function persist(): JsonResponse
    {
        return new JsonResponse(['data' => app(CheckFeatureAction::class)->execute(
            CheckFeatureData::fromValidated($this->validated()),
        )]);
    }

    protected function prepareForValidation(): void
    {
        $this->mergeRouteParameter('feature');
    }
}
