<?php

declare(strict_types=1);

namespace RefactorCircus\PennantPlus\Domains\Feature\Data;

use RefactorCircus\Keystone\Data\Data;
use RefactorCircus\Keystone\Pagination\Pagination;

/**
 * Input for ListFeatureValuesAction: the filters and the page to show. The
 * scope filter is `global`, `other`, or a model type as Pennant stores it;
 * `scope_id` narrows a model type to one key, or `other` to one string.
 */
final readonly class ListFeatureValuesData extends Data
{
    public function __construct(
        public ?string $feature = null,
        public ?string $scope = null,
        public ?string $scopeId = null,
        public int $perPage = 25,
        public ?int $page = null,
    ) {}

    /**
     * How the list pages; its tool states the same limits.
     */
    public static function pagination(): Pagination
    {
        return Pagination::pages(default: 25);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'feature' => ['sometimes', 'nullable', 'string', 'max:255'],
            'scope' => ['sometimes', 'nullable', 'string', 'max:255'],
            'scope_id' => ['sometimes', 'nullable', 'string', 'max:255'],
        ] + self::pagination()->rules();
    }
}
