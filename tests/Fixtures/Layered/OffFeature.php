<?php

declare(strict_types=1);

namespace JayI\PennantPlus\Tests\Fixtures\Layered;

use JayI\PennantPlus\LayeredFeature;

class OffFeature extends LayeredFeature
{
    protected function default(): bool
    {
        return false;
    }
}
