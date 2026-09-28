<?php

declare(strict_types=1);

namespace JayI\PennantPlus\Actions;

use JayI\PennantPlus\FeatureFlagManager;

/**
 * Every feature that is defined, discoverable, or has a stored value, with
 * its stored global value and override count.
 */
final class ListFeaturesAction
{
    public function __construct(private readonly FeatureFlagManager $features) {}

    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [];
    }

    /**
     * @return array<int, array{name: string, defined: bool, global: mixed, global_stored: bool, overrides: int}>
     */
    public function execute(): array
    {
        return array_map($this->features->summary(...), $this->features->features());
    }
}
