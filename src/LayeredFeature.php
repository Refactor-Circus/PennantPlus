<?php

declare(strict_types=1);

namespace JayI\PennantPlus;

use Laravel\Pennant\Feature;

/**
 * A class-based feature with one global value that every scope follows.
 *
 * The global (null) scope resolves to `default()`; any other scope resolves
 * to the current global value, so with the `pennantplus` driver nothing is
 * stored for it until it is given its own value. Extend it and, for a
 * feature that must start off, override `default()`:
 *
 *     class ReportsFeature extends LayeredFeature {}
 *
 *     class HubWritesFeature extends LayeredFeature
 *     {
 *         protected function default(): bool
 *         {
 *             return false;
 *         }
 *     }
 */
abstract class LayeredFeature
{
    public function resolve(mixed $scope): bool
    {
        return $scope === null
            ? $this->default()
            : Feature::for(null)->active(static::class);
    }

    /**
     * The global value before anything has been stored for it.
     */
    protected function default(): bool
    {
        return true;
    }
}
