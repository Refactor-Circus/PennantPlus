<?php

declare(strict_types=1);

namespace JayI\PennantPlus\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use JayI\PennantPlus\Http\Requests\CheckFeatureRequest;
use JayI\PennantPlus\Http\Requests\IndexFeaturesRequest;
use JayI\PennantPlus\Http\Requests\PurgeFeatureRequest;
use JayI\PennantPlus\Http\Requests\ShowFeatureRequest;

final class FeatureController
{
    public function index(IndexFeaturesRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function show(ShowFeatureRequest $request, string $feature): JsonResponse
    {
        return $request->persist();
    }

    public function check(CheckFeatureRequest $request, string $feature): JsonResponse
    {
        return $request->persist();
    }

    public function destroy(PurgeFeatureRequest $request, string $feature): Response
    {
        return $request->persist();
    }
}
