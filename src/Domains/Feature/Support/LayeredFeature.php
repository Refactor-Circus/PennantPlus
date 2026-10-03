<?php

declare(strict_types=1);

namespace JayI\PennantPlus\Domains\Feature\Support;

use Laravel\Pennant\Feature;

/**
 * A class-based feature with one global value that every scope follows.
 *
 * The global (null) scope resolves to `default()`; any other scope resolves
 * to the current global value, so with the `pennantplus` driver nothing is
 * stored for it until it is given its own value. Extend OnLayeredFeature or
 * OffLayeredFeature rather than this class:
 *
 *     class ReportsFeature extends OnLayeredFeature {}
 *
 *     class ExternalWritesFeature extends OffLayeredFeature {}
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
    abstract protected function default(): bool;
}
