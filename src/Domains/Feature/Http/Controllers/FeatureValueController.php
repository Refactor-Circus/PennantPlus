<?php

declare(strict_types=1);

namespace RefactorCircus\PennantPlus\Domains\Feature\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use RefactorCircus\PennantPlus\Domains\Feature\Http\Requests\DeleteFeatureValueRequest;
use RefactorCircus\PennantPlus\Domains\Feature\Http\Requests\IndexFeatureValuesRequest;
use RefactorCircus\PennantPlus\Domains\Feature\Http\Requests\UpdateFeatureValueRequest;

final class FeatureValueController
{
    public function index(IndexFeatureValuesRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function update(UpdateFeatureValueRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function destroy(DeleteFeatureValueRequest $request): Response
    {
        return $request->persist();
    }
}
