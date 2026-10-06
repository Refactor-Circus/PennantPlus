<?php

declare(strict_types=1);

use JayI\Foundation\Cortex\CortexIntegration;
use JayI\Foundation\Packages\PackageRegistry;
use JayI\PennantPlus\Domains\Feature\Mcp\Tools\ListFeaturesTool;
use JayI\PennantPlus\Mcp\PennantPlusServer;
use Laravel\Mcp\Server\Transport\FakeTransporter;

it('serves the declared instructions and descriptions without Cortex loaded', function (): void {
    expect(CortexIntegration::for(app(PackageRegistry::class)->get('pennantplus'))->active())->toBeFalse()
        ->and((new PennantPlusServer(new FakeTransporter))->createContext()->instructions)->toStartWith('Manage Laravel Pennant feature flags')
        ->and(app(ListFeaturesTool::class)->description())->toStartWith('List every feature flag');
});
