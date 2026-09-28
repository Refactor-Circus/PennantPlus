<?php

declare(strict_types=1);

namespace JayI\PennantPlus\Http\Controllers;

use Illuminate\Http\JsonResponse;
use JayI\PennantPlus\Http\Requests\IndexScopeTypesRequest;
use JayI\PennantPlus\Http\Requests\SearchScopeModelsRequest;

final class ScopeController
{
    public function index(IndexScopeTypesRequest $request): JsonResponse
    {
        return $request->persist();
    }

    public function models(SearchScopeModelsRequest $request, string $type): JsonResponse
    {
        return $request->persist();
    }
}
