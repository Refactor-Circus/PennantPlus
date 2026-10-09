<?php

declare(strict_types=1);

use Laravel\Mcp\Server\Transport\FakeTransporter;
use RefactorCircus\Keystone\Cortex\CortexIntegration;
use RefactorCircus\Keystone\Packages\PackageRegistry;
use RefactorCircus\PennantPlus\Domains\Feature\Mcp\Tools\ListFeaturesTool;
use RefactorCircus\PennantPlus\Mcp\PennantPlusServer;

it('serves the declared instructions and descriptions without Cortex loaded', function (): void {
    expect(CortexIntegration::for(app(PackageRegistry::class)->get('pennantplus'))->active())->toBeFalse()
        ->and((new PennantPlusServer(new FakeTransporter))->createContext()->instructions)->toStartWith('Manage Laravel Pennant feature flags')
        ->and(app(ListFeaturesTool::class)->description())->toStartWith('List every feature flag');
});
