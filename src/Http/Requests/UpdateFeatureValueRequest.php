<?php

declare(strict_types=1);

namespace JayI\PennantPlus\Http\Requests;

use Illuminate\Http\JsonResponse;
use JayI\PennantPlus\Actions\UpdateFeatureValueAction;
use JayI\PennantPlus\FeatureFlagManager;
use JayI\PennantPlus\Http\Request;
use JayI\PennantPlus\Http\Resources\FeatureValueResource;

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
