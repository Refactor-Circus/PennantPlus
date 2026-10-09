<?php

declare(strict_types=1);

namespace RefactorCircus\PennantPlus\Domains\Feature\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\PennantPlus\Domains\Feature\Actions\ShowFeatureAction;
use RefactorCircus\PennantPlus\Domains\Feature\Http\Resources\FeatureResource;
use RefactorCircus\PennantPlus\Http\Request;

final class ShowFeatureRequest extends Request
{
    public function rules(): array
    {
        return ShowFeatureAction::rules();
    }

    public function persist(): JsonResponse
    {
        return (new FeatureResource(
            app(ShowFeatureAction::class)->execute($this->string('feature')->toString()),
        ))->response();
    }

    protected function prepareForValidation(): void
    {
        $this->mergeRouteParameter('feature');
    }
}
