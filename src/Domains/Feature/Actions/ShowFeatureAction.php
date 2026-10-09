<?php

declare(strict_types=1);

namespace RefactorCircus\PennantPlus\Domains\Feature\Actions;

use Laravel\Pennant\Feature;
use RefactorCircus\PennantPlus\Domains\Feature\Services\FeatureFlagManager;

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
