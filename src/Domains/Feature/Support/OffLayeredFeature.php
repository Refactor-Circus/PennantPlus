<?php

declare(strict_types=1);

namespace RefactorCircus\PennantPlus\Domains\Feature\Support;

/**
 * A layered feature that is off globally until its global value is set.
 */
abstract class OffLayeredFeature extends LayeredFeature
{
    protected function default(): bool
    {
        return false;
    }
}
