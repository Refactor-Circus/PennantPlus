<?php

declare(strict_types=1);

namespace RefactorCircus\PennantPlus\Domains\Scope\Data;

use RefactorCircus\Keystone\Data\Data;

/**
 * Input for SearchScopeModelsAction: the configured scope type, and the term
 * matched against its key and search columns. A blank term finds nothing.
 */
final readonly class SearchScopeModelsData extends Data
{
    public function __construct(
        public string $type,
        public ?string $q = null,
    ) {}

    /**
     * @return array<string, array<int, string>>
     */
    public static function rules(): array
    {
        return [
            'type' => ['required', 'string', 'max:255'],
            'q' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}
