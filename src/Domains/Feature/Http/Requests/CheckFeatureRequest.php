<?php

declare(strict_types=1);

namespace JayI\PennantPlus\Domains\Feature\Http\Requests;

use Illuminate\Http\JsonResponse;
use JayI\PennantPlus\Domains\Feature\Actions\CheckFeatureAction;
use JayI\PennantPlus\Domains\Feature\Services\FeatureFlagManager;
use JayI\PennantPlus\Http\Request;

final class CheckFeatureRequest extends Request
{
    public function rules(): array
    {
        return CheckFeatureAction::rules();
    }

    public function persist(): JsonResponse
    {
        /** @var array<string, mixed> $data */
        $data = $this->validated();

        return new JsonResponse(['data' => app(CheckFeatureAction::class)->execute(
            $this->string('feature')->toString(),
            app(FeatureFlagManager::class)->scopeFromInput($data),
        )]);
    }

    protected function prepareForValidation(): void
    {
        $this->mergeRouteParameter('feature');
    }
}
