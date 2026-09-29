<?php

declare(strict_types=1);

use JayI\PennantPlus\Cortex\CortexIntegration;
use JayI\PennantPlus\Mcp\PennantPlusServer;
use JayI\PennantPlus\Mcp\Tools\ListFeaturesTool;
use Laravel\Mcp\Server\Transport\FakeTransporter;

it('serves the declared instructions and descriptions without Cortex loaded', function (): void {
    expect(app(CortexIntegration::class)->active())->toBeFalse()
        ->and((new PennantPlusServer(new FakeTransporter))->createContext()->instructions)->toStartWith('Manage Laravel Pennant feature flags')
        ->and(app(ListFeaturesTool::class)->description())->toStartWith('List every feature flag');
});
