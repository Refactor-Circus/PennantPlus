<?php

declare(strict_types=1);

namespace JayI\PennantPlus\Domains\Scope\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use JayI\PennantPlus\Domains\Feature\Services\FeatureFlagManager;

/**
 * Models of a configured scope type whose key or search columns match a term.
 */
final class SearchScopeModelsAction
{
    public function __construct(private readonly FeatureFlagManager $features) {}

    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            'type' => ['required', 'string', 'max:255'],
            'q' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<int, array{id: string, title: string, scope: string}>
     *
     * @throws ModelNotFoundException when the type is not a configured scope model
     */
    public function execute(string $type, ?string $term = null): array
    {
        $scope = $this->features->scopeModel($type) ?? throw new ModelNotFoundException(sprintf('No scope model [%s] is configured.', $type));

        $term = trim((string) $term);

        if ($term === '') {
            return [];
        }

        return $scope->find($term)
            ->map(fn (Model $model): array => [
                'id' => $scope->keyOf($model),
                'title' => $scope->titleFor($model),
                'scope' => $scope->serialize($model),
            ])
            ->values()
            ->all();
    }
}
