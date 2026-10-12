<?php

declare(strict_types=1);

namespace RefactorCircus\PennantPlus\Domains\Feature\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\PennantPlus\Domains\Feature\Actions\UpdateFeatureValueAction;
use RefactorCircus\PennantPlus\Domains\Feature\Data\UpdateFeatureValueData;
use RefactorCircus\PennantPlus\Domains\Feature\Http\Resources\FeatureValueResource;
use RefactorCircus\PennantPlus\Http\Request;

/**
 * Store a value — true, false, or any richer JSON value — for a scope.
 */
final class UpdateFeatureValueRequest extends Request
{
    public function rules(): array
    {
        return UpdateFeatureValueData::rules();
    }

    public function persist(): JsonResponse
    {
        $value = app(UpdateFeatureValueAction::class)->execute(UpdateFeatureValueData::fromValidated($this->validated()));

        return (new FeatureValueResource($value))->response();
    }
}
