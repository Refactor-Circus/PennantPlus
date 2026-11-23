<?php

declare(strict_types=1);

namespace RefactorCircus\PennantPlus\Tests;

use Dedoc\Scramble\ScrambleServiceProvider;
use Laravel\Mcp\Server\McpServiceProvider;
use Laravel\Pennant\PennantServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use RefactorCircus\PennantPlus\PennantPlusServiceProvider;
use Workbench\App\Providers\WorkbenchServiceProvider;

/**
 * The package as `testbench.yaml` boots it for `scramble:export`: the
 * package providers, Scramble and the workbench, which holds the one
 * Scramble configuration the export and the staleness test both read.
 */
abstract class OpenApiTestCase extends Orchestra
{
    /**
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            PennantServiceProvider::class,
            McpServiceProvider::class,
            PennantPlusServiceProvider::class,
            ScrambleServiceProvider::class,
            WorkbenchServiceProvider::class,
        ];
    }
}
