<?php

declare(strict_types=1);

namespace JayI\PennantPlus\Actions;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use JayI\PennantPlus\FeatureFlagManager;
use JayI\PennantPlus\StoredFeatureValue;

/**
 * Stored values, newest first, narrowed by feature and scope.
 */
final class ListFeatureValuesAction
{
    public function __construct(private readonly FeatureFlagManager $features) {}

    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            'feature' => ['sometimes', 'nullable', 'string', 'max:255'],
            'scope' => ['sometimes', 'nullable', 'string', 'max:255'],
            'scope_id' => ['sometimes', 'nullable', 'string', 'max:255'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    /**
     * The scope filter is `global`, `other`, or a model type as Pennant
     * stores it.
     *
     * @param  array{feature?: string|null, scope?: string|null, scope_id?: string|null}  $filters
     * @return LengthAwarePaginator<int, StoredFeatureValue>
     */
    public function execute(array $filters = [], ?int $page = null): LengthAwarePaginator
    {
        return $this->features->paginate($filters, page: $page);
    }
}
