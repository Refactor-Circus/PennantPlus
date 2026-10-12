<?php

declare(strict_types=1);

namespace RefactorCircus\PennantPlus\Domains\Feature\Data;

use RefactorCircus\Keystone\Data\Data;

/**
 * Input for PurgeFeatureAction: the feature whose stored values to forget.
 */
final readonly class PurgeFeatureData extends Data
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
