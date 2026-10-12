<?php

declare(strict_types=1);

namespace RefactorCircus\PennantPlus\Domains\Feature\Actions;

use RefactorCircus\PennantPlus\Domains\Feature\Data\ShowFeatureData;
use RefactorCircus\PennantPlus\Domains\Feature\Events\FeatureShowingActionEvent;
use RefactorCircus\PennantPlus\Domains\Feature\Events\FeatureShownActionEvent;
use RefactorCircus\PennantPlus\Domains\Feature\Services\FeatureFlagManager;

/**
 * One feature: its stored summary plus its resolved global value.
 */
final class ShowFeatureAction
{
    public function __construct(private readonly FeatureFlagManager $features) {}

    /**
     * @return array{name: string, defined: bool, global: mixed, global_stored: bool, overrides: int, value: mixed}
     */
    public function execute(ShowFeatureData $data): array
    {
        FeatureShowingActionEvent::dispatch($data);

        $value = $this->features->store()->for([null])->value($data->feature);

        $feature = [...$this->features->summary($data->feature), 'value' => $value];

        FeatureShownActionEvent::dispatch($data);

        return $feature;
    }
}
