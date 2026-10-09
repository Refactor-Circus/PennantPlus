<?php

declare(strict_types=1);

namespace RefactorCircus\PennantPlus\Domains\Feature\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\PennantPlus\Domains\Feature\Actions\ListFeatureValuesAction;
use RefactorCircus\PennantPlus\Domains\Feature\Http\Resources\FeatureValueResource;
use RefactorCircus\PennantPlus\Http\Request;

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
