<?php

declare(strict_types=1);

namespace RefactorCircus\PennantPlus\Domains\Scope\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use RefactorCircus\PennantPlus\Domains\Feature\Services\FeatureFlagManager;
use RefactorCircus\PennantPlus\Domains\Scope\Data\SearchScopeModelsData;
use RefactorCircus\PennantPlus\Domains\Scope\Events\ScopeModelsSearchedActionEvent;
use RefactorCircus\PennantPlus\Domains\Scope\Events\ScopeModelsSearchingActionEvent;

/**
 * Models of a configured scope type whose key or search columns match a term.
 */
final class SearchScopeModelsAction
{
    public function __construct(private readonly FeatureFlagManager $features) {}

    /**
     * @return array<int, array{id: string, title: string, scope: string}>
     *
     * @throws ModelNotFoundException when the type is not a configured scope model
     */
    public function execute(SearchScopeModelsData $data): array
    {
        $scope = $this->features->scopeModel($data->type) ?? throw new ModelNotFoundException(sprintf('No scope model [%s] is configured.', $data->type));

        ScopeModelsSearchingActionEvent::dispatch($data);

        $term = trim((string) $data->q);

        $models = $term === '' ? [] : $scope->find($term)
            ->map(fn (Model $model): array => [
                'id' => $scope->keyOf($model),
                'title' => $scope->titleFor($model),
                'scope' => $scope->serialize($model),
            ])
            ->values()
            ->all();

        ScopeModelsSearchedActionEvent::dispatch($data);

        return $models;
    }
}
