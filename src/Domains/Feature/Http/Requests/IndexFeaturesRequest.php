<?php

declare(strict_types=1);

namespace RefactorCircus\PennantPlus\Domains\Feature\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\PennantPlus\Domains\Feature\Actions\ListFeaturesAction;
use RefactorCircus\PennantPlus\Domains\Feature\Http\Resources\FeatureResource;
use RefactorCircus\PennantPlus\Http\Request;

final class IndexFeaturesRequest extends Request
{
    public function rules(): array
    {
        return ListFeaturesAction::rules();
    }

    public function persist(): JsonResponse
    {
        return FeatureResource::collection(app(ListFeaturesAction::class)->execute())->response();
    }
}
