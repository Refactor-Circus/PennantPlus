<?php

declare(strict_types=1);

namespace JayI\PennantPlus\Domains\Feature\Actions;

use JayI\PennantPlus\Domains\Feature\Services\FeatureFlagManager;
use Laravel\Pennant\Feature;

/**
 * One feature: its stored summary plus its resolved global value.
 */
final class ShowFeatureAction
{
    public function __construct(private readonly FeatureFlagManager $features) {}

    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            'feature' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * @return array{name: string, defined: bool, global: mixed, global_stored: bool, overrides: int, value: mixed}
     */
    public function execute(string $feature): array
    {
        $value = $this->features->store()->for([null])->value($feature);

        return [...$this->features->summary($feature), 'value' => $value];
    }
}
