<?php

declare(strict_types=1);

namespace RefactorCircus\PennantPlus\Domains\Feature\Data;

use RefactorCircus\Keystone\Data\Data;

/**
 * Input for ShowFeatureAction: the feature to show.
 */
final readonly class ShowFeatureData extends Data
{
    public function __construct(
        public string $feature,
    ) {}

    /**
     * @return array<string, array<int, string>>
     */
    public static function rules(): array
    {
        return [
            'feature' => ['required', 'string', 'max:255'],
        ];
    }
}
