<?php

declare(strict_types=1);

namespace RefactorCircus\PennantPlus\Domains\Scope\Http\Controllers;

use Illuminate\Http\JsonResponse;
use RefactorCircus\PennantPlus\Domains\Scope\Http\Requests\IndexScopeTypesRequest;
use RefactorCircus\PennantPlus\Domains\Scope\Http\Requests\SearchScopeModelsRequest;

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
