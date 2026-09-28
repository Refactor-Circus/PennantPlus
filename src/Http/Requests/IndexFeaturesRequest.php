<?php

declare(strict_types=1);

namespace JayI\PennantPlus\Http\Requests;

use Illuminate\Http\JsonResponse;
use JayI\PennantPlus\Actions\ListFeaturesAction;
use JayI\PennantPlus\Http\Request;
use JayI\PennantPlus\Http\Resources\FeatureResource;

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
