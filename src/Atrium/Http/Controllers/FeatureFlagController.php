<?php

declare(strict_types=1);

namespace RefactorCircus\PennantPlus\Atrium\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use RefactorCircus\Atrium\Domains\Screens\Data\Screen;
use RefactorCircus\Atrium\Domains\Screens\Services\ScreenExtensions;
use RefactorCircus\PennantPlus\Atrium\Data\FeatureValueRow;
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
    public function __construct(
        protected FeatureFlagManager $features,
        protected ScreenExtensions $screens,
    ) {}

    public function index(Request $request): View
    {
        // Asked once: the page's controls are shown on the same answer.
        $manages = FeatureFlagAccess::allows($request);

        abort_unless($manages, 403);

        // Only once the page itself is allowed do other packages' tabs and
        // sections get asked about.
        $resolved = $this->screens->resolve($this->screen(), $request);

        $filters = [
            'feature' => $request->string('feature')->toString(),
            'scope' => $request->string('scope')->toString(),
            'scope_id' => $request->string('scope_id')->toString(),
        ];

        $supported = $this->features->supportsListing();

        $values = $supported
            ? app(ListFeatureValuesAction::class)->execute(ListFeatureValuesData::from($request->all()))->withQueryString()
            : null;

        /** @var view-string $view */
        $view = 'pennantplus::atrium.index';

        return view($view, [
            'resolved' => $resolved,
            'supported' => $supported,
            'manages' => $manages,
            'filters' => $filters,
            'features' => $this->features->features(),
            'scopeModels' => $this->features->scopeModels(),
            'scopeFilterOptions' => $supported ? $this->features->scopeFilterOptions() : [],
            'values' => $values,
            // The current page's values as the models their `row.actions`
            // sections receive; the pagination stays on `values`.
            'rows' => $values === null ? [] : array_map(FeatureValueRow::for(...), $values->items()),
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

    /**
     * The feature flags list, declared to Atrium's screen extensions
     * registry. Feature flags have no policies, so extensions'
     * `permission()`s are Gate abilities, asked about the row when there is
     * one; the page's own check (`pennantplus.ability`) is one too.
     */
    protected function screen(): Screen
    {
        return Screen::standard('pennantplus.features', Screen::LIST)
            ->access(fn (string $permission, Request $request, ?Model $record): bool => Gate::forUser($request->user())
                ->allows($permission, $record === null ? [] : [$record]));
    }
}
