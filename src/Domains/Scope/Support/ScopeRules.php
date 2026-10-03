<?php

declare(strict_types=1);

namespace JayI\PennantPlus\Domains\Scope\Support;

use Illuminate\Validation\Rule;
use JayI\PennantPlus\Domains\Feature\Services\FeatureFlagManager;

/**
 * Validation for picking a scope: `scope` as Pennant serializes it, or a
 * `scope_type` (`global`, `other`, a configured model) with its `scope_id`.
 */
final class ScopeRules
{
    /**
     * A scope is required.
     *
     * @return array<string, mixed>
     */
    public static function required(): array
    {
        return [
            'scope' => ['required_without:scope_type', 'string', 'max:255'],
            'scope_type' => ['required_without:scope', ...self::type()],
            'scope_id' => self::id(),
        ];
    }

    /**
     * A scope may be picked; without one the global scope is used.
     *
     * @return array<string, mixed>
     */
    public static function optional(): array
    {
        return [
            'scope' => ['sometimes', 'string', 'max:255'],
            'scope_type' => ['sometimes', ...self::type()],
            'scope_id' => self::id(),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    private static function type(): array
    {
        return [
            'string',
            Rule::in([
                FeatureFlagManager::GLOBAL,
                FeatureFlagManager::OTHER,
                ...array_keys(app(FeatureFlagManager::class)->scopeModels()),
            ]),
        ];
    }

    /**
     * @return array<int, string>
     */
    private static function id(): array
    {
        return [
            'exclude_if:scope_type,'.FeatureFlagManager::GLOBAL,
            'required_with:scope_type',
            'string',
            'max:255',
        ];
    }
}
