<?php

declare(strict_types=1);

namespace JayI\PennantPlus\Http\Requests;

use Illuminate\Http\JsonResponse;
use JayI\PennantPlus\Actions\ShowFeatureAction;
use JayI\PennantPlus\Http\Request;
use JayI\PennantPlus\Http\Resources\FeatureResource;

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
