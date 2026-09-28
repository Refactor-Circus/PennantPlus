<?php

declare(strict_types=1);

namespace JayI\PennantPlus\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use JayI\PennantPlus\Http\Requests\DeleteFeatureValueRequest;
use JayI\PennantPlus\Http\Requests\IndexFeatureValuesRequest;
use JayI\PennantPlus\Http\Requests\UpdateFeatureValueRequest;

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
