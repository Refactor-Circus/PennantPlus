<?php

declare(strict_types=1);

namespace JayI\PennantPlus;

/**
 * A layered feature that is on globally until its global value is set.
 */
abstract class OnLayeredFeature extends LayeredFeature
{
    protected function default(): bool
    {
        return true;
    }
}
