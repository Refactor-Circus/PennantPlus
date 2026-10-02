<?php

declare(strict_types=1);

namespace JayI\PennantPlus\Atrium\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use JayI\PennantPlus\Atrium\FeatureFlagAccess;
use JayI\PennantPlus\Atrium\Http\Requests\DeleteFeatureValueRequest;
use JayI\PennantPlus\Atrium\Http\Requests\PurgeFeatureRequest;
use JayI\PennantPlus\Atrium\Http\Requests\UpdateFeatureValueRequest;
use JayI\PennantPlus\FeatureFlagManager;

class FeatureFlagController
{
    public function __construct(protected FeatureFlagManager $features) {}

    public function index(Request $request): View
    {
        $this->authorize($request);

        $filters = [
            'feature' => $request->string('feature')->toString(),
            'scope' => $request->string('scope')->toString(),
            'scope_id' => $request->string('scope_id')->toString(),
        ];

        $supported = $this->features->supportsListing();

        /** @var view-string $view */
        $view = 'pennantplus::atrium.index';

        return view($view, [
            'supported' => $supported,
            'filters' => $filters,
            'features' => $this->features->features(),
            'scopeModels' => $this->features->scopeModels(),
            'scopeFilterOptions' => $supported ? $this->features->scopeFilterOptions() : [],
            'values' => $supported ? $this->features->paginate($filters)->withQueryString() : null,
        ]);
    }

    /**
     * Models of a configured scope type matching a search term, for picking
     * the model a value is scoped to.
     */
    public function scopes(Request $request): JsonResponse
    {
        $this->authorize($request);

        $scope = $this->features->scopeModel($request->string('type')->toString());

        abort_if($scope === null, 404);

        $term = trim($request->string('q')->toString());

        return new JsonResponse([
            'data' => $term === '' ? [] : $scope->find($term)
                ->map(fn (Model $model): array => [
                    'id' => $scope->keyOf($model),
                    'title' => $scope->titleFor($model),
                ])
                ->values()
                ->all(),
        ]);
    }

    public function update(UpdateFeatureValueRequest $request): RedirectResponse
    {
        return $request->persist();
    }

    public function destroy(DeleteFeatureValueRequest $request): RedirectResponse
    {
        return $request->persist();
    }

    public function purge(PurgeFeatureRequest $request): RedirectResponse
    {
        return $request->persist();
    }

    protected function authorize(Request $request): void
    {
        abort_unless(FeatureFlagAccess::allows($request), 403);
    }
}
