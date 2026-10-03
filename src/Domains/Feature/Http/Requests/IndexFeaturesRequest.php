<?php

declare(strict_types=1);

namespace JayI\PennantPlus\Domains\Feature\Http\Requests;

use Illuminate\Http\JsonResponse;
use JayI\PennantPlus\Domains\Feature\Actions\ListFeaturesAction;
use JayI\PennantPlus\Domains\Feature\Http\Resources\FeatureResource;
use JayI\PennantPlus\Http\Request;

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
