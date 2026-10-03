<?php

declare(strict_types=1);

namespace JayI\PennantPlus\Domains\Feature\Http\Requests;

use Illuminate\Http\JsonResponse;
use JayI\PennantPlus\Domains\Feature\Actions\UpdateFeatureValueAction;
use JayI\PennantPlus\Domains\Feature\Http\Resources\FeatureValueResource;
use JayI\PennantPlus\Domains\Feature\Services\FeatureFlagManager;
use JayI\PennantPlus\Http\Request;

/**
 * Store a value — true, false, or any richer JSON value — for a scope.
 */
final class UpdateFeatureValueRequest extends Request
{
    public function rules(): array
    {
        return UpdateFeatureValueAction::rules();
    }

    public function persist(): JsonResponse
    {
        /** @var array<string, mixed> $data */
        $data = $this->validated();

        $value = app(UpdateFeatureValueAction::class)->execute(
            $this->string('feature')->toString(),
            app(FeatureFlagManager::class)->scopeFromInput($data),
            $data['value'],
        );

        return (new FeatureValueResource($value))->response();
    }
}
