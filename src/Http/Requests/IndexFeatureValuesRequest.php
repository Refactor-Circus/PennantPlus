<?php

declare(strict_types=1);

namespace JayI\PennantPlus\Http\Requests;

use Illuminate\Http\JsonResponse;
use JayI\PennantPlus\Actions\ListFeatureValuesAction;
use JayI\PennantPlus\Http\Request;
use JayI\PennantPlus\Http\Resources\FeatureValueResource;

final class IndexFeatureValuesRequest extends Request
{
    public function rules(): array
    {
        return ListFeatureValuesAction::rules();
    }

    public function persist(): JsonResponse
    {
        $values = app(ListFeatureValuesAction::class)->execute(
            [
                'feature' => $this->string('feature')->toString(),
                'scope' => $this->string('scope')->toString(),
                'scope_id' => $this->string('scope_id')->toString(),
            ],
            $this->filled('page') ? $this->integer('page') : null,
        );

        return FeatureValueResource::collection($values)->response();
    }
}
