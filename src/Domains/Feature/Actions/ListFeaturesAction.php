<?php

declare(strict_types=1);

namespace RefactorCircus\PennantPlus\Domains\Feature\Actions;

use RefactorCircus\PennantPlus\Domains\Feature\Events\FeaturesListedActionEvent;
use RefactorCircus\PennantPlus\Domains\Feature\Events\FeaturesListingActionEvent;
use RefactorCircus\PennantPlus\Domains\Feature\Services\FeatureFlagManager;

/**
 * Every feature that is defined, discoverable, or has a stored value, with
 * its stored global value and override count.
 */
final class ListFeaturesAction
{
    public function __construct(private readonly FeatureFlagManager $features) {}

    /**
     * @return array<int, array{name: string, defined: bool, global: mixed, global_stored: bool, overrides: int}>
     */
    public function execute(): array
    {
        FeaturesListingActionEvent::dispatch();

        $features = $this->features->summaries($this->features->features());

        FeaturesListedActionEvent::dispatch();

        return $features;
    }
}
