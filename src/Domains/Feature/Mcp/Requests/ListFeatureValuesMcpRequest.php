<?php

declare(strict_types=1);

namespace RefactorCircus\PennantPlus\Domains\Feature\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\PennantPlus\Domains\Feature\Actions\ListFeatureValuesAction;
use RefactorCircus\PennantPlus\Domains\Feature\Http\Resources\FeatureValueResource;
use RefactorCircus\PennantPlus\Mcp\Request;

final class ListFeatureValuesMcpRequest extends Request
{
    protected function rules(): array
    {
        return ListFeatureValuesAction::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $values = app(ListFeatureValuesAction::class)->execute(
            [
                'feature' => isset($validated['feature']) ? (string) $validated['feature'] : null,
                'scope' => isset($validated['scope']) ? (string) $validated['scope'] : null,
                'scope_id' => isset($validated['scope_id']) ? (string) $validated['scope_id'] : null,
            ],
            isset($validated['page']) ? (int) $validated['page'] : null,
        );

        return $this->structuredCollection(FeatureValueResource::collection($values)->resolve(), [
            'meta' => [
                'current_page' => $values->currentPage(),
                'last_page' => $values->lastPage(),
                'total' => $values->total(),
            ],
        ]);
    }
}
