<?php

declare(strict_types=1);

use JayI\Atrium\Testing\AtriumStyles;

/**
 * PennantPlus ships no stylesheet: its Atrium screen uses Atrium's components
 * and the utilities Atrium's compiled stylesheet ships, and nothing else.
 */
it('uses only atrium styles', function (): void {
    $views = dirname(__DIR__, 3).'/resources/views';

    expect(AtriumStyles::missingClasses($views))->toBe([])
        ->and(AtriumStyles::inlineStyles($views))->toBe([]);
});
