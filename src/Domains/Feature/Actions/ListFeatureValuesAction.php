<?php

declare(strict_types=1);

namespace RefactorCircus\PennantPlus\Domains\Feature\Actions;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use RefactorCircus\PennantPlus\Domains\Feature\Data\ListFeatureValuesData;
use RefactorCircus\PennantPlus\Domains\Feature\Data\StoredFeatureValue;
use RefactorCircus\PennantPlus\Domains\Feature\Events\FeatureValuesListedActionEvent;
use RefactorCircus\PennantPlus\Domains\Feature\Events\FeatureValuesListingActionEvent;
use RefactorCircus\PennantPlus\Domains\Feature\Services\FeatureFlagManager;

/**
 * Stored values, newest first, narrowed by feature and scope.
 */
final class ListFeatureValuesAction
{
    public function __construct(private readonly FeatureFlagManager $features) {}

    /**
     * @return LengthAwarePaginator<int, StoredFeatureValue>
     */
    public function execute(ListFeatureValuesData $filters = new ListFeatureValuesData): LengthAwarePaginator
    {
        FeatureValuesListingActionEvent::dispatch($filters);

        $values = $this->features->paginate([
            'feature' => $filters->feature,
            'scope' => $filters->scope,
            'scope_id' => $filters->scopeId,
        ], $filters->perPage, $filters->page);

        FeatureValuesListedActionEvent::dispatch($filters);

        return $values;
    }
}
