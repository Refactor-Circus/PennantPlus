<?php

declare(strict_types=1);

use Composer\Autoload\ClassLoader;

/*
 * The WithoutAtrium suite runs as if refactor-circus/atrium were not
 * installed: the `RefactorCircus\Atrium\` PSR-4 prefix is taken out of every
 * Composer autoloader, so `class_exists()` on an Atrium class answers false.
 * Run it with `--test-directory=tests/WithoutAtrium`, so Pest boots none of
 * the main suite's files (tests/Pest.php loads tests/TestCase.php, which
 * names Atrium).
 */

require dirname(__DIR__, 2).'/vendor/autoload.php';

foreach (ClassLoader::getRegisteredLoaders() as $loader) {
    (function (string $prefix): void {
        unset($this->prefixDirsPsr4[$prefix], $this->prefixLengthsPsr4[$prefix[0]][$prefix]);
    })->call($loader, 'RefactorCircus\\Atrium\\');
}
