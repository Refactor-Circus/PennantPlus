<?php

declare(strict_types=1);

namespace RefactorCircus\PennantPlus\Atrium\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RefactorCircus\PennantPlus\Atrium\FeatureFlagAccess;
use RefactorCircus\PennantPlus\Atrium\Http\Requests\DeleteFeatureValueRequest;
use RefactorCircus\PennantPlus\Atrium\Http\Requests\PurgeFeatureRequest;
use RefactorCircus\PennantPlus\Atrium\Http\Requests\UpdateFeatureValueRequest;
use RefactorCircus\PennantPlus\Domains\Feature\Actions\ListFeatureValuesAction;
use RefactorCircus\PennantPlus\Domains\Feature\Data\ListFeatureValuesData;
use RefactorCircus\PennantPlus\Domains\Feature\Services\FeatureFlagManager;
use RefactorCircus\PennantPlus\Domains\Scope\Actions\SearchScopeModelsAction;
use RefactorCircus\PennantPlus\Domains\Scope\Data\SearchScopeModelsData;

class FeatureFlagController
{
    public function __construct(protected FeatureFlagManager $features) {}

    public function index(Request $request): View
    {
        // Asked once: the page's controls are shown on the same answer.
        $manages = FeatureFlagAccess::allows($request);

        abort_unless($manages, 403);

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
            'manages' => $manages,
            'filters' => $filters,
            'features' => $this->features->features(),
            'scopeModels' => $this->features->scopeModels(),
            'scopeFilterOptions' => $supported ? $this->features->scopeFilterOptions() : [],
            'values' => $supported
                ? app(ListFeatureValuesAction::class)->execute(ListFeatureValuesData::from($request->all()))->withQueryString()
                : null,
        ]);
    }

    /**
     * Models of a configured scope type matching a search term, for picking
     * the model a value is scoped to.
     */
    public function scopes(Request $request): JsonResponse
    {
        $this->authorize($request);

        return new JsonResponse([
            'data' => app(SearchScopeModelsAction::class)->execute(SearchScopeModelsData::from($request->all())),
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
