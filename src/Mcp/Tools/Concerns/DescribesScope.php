<?php

declare(strict_types=1);

namespace JayI\PennantPlus\Mcp\Tools\Concerns;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;

/**
 * The shared scope arguments: a serialized `scope`, or a `scope_type` with its
 * `scope_id`.
 */
trait DescribesScope
{
    /**
     * @return array<string, Type>
     */
    protected function scopeSchema(JsonSchema $schema): array
    {
        return [
            'scope' => $schema->string()->description('The scope exactly as Pennant stores it, e.g. "__laravel_null" (global) or "App\\Models\\User|5". Alternative to scope_type/scope_id.'),
            'scope_type' => $schema->string()->description('The scope type: "global", "other" (a plain string scope), or a model type from list-scope-types.'),
            'scope_id' => $schema->string()->description('The model key for a model scope type, or the string for "other". Omit for "global".'),
        ];
    }
}
