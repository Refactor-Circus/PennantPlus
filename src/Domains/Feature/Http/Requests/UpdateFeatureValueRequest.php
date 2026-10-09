<?php

declare(strict_types=1);

namespace RefactorCircus\PennantPlus\Domains\Feature\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\PennantPlus\Domains\Feature\Actions\UpdateFeatureValueAction;
use RefactorCircus\PennantPlus\Domains\Feature\Http\Resources\FeatureValueResource;
use RefactorCircus\PennantPlus\Domains\Feature\Services\FeatureFlagManager;
use RefactorCircus\PennantPlus\Http\Request;

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
