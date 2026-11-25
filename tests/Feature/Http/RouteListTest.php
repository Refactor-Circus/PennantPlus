<?php

declare(strict_types=1);

use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Route;

it('registers exactly the pennantplus api routes, with their verbs and uris', function (): void {
    $routes = [];

    /** @var RoutingRoute $route */
    foreach (Route::getRoutes()->getRoutes() as $route) {
        $name = (string) $route->getName();

        if (str_starts_with($name, 'pennantplus.')) {
            $routes[$name] = implode('|', $route->methods()).' '.$route->uri();
        }
    }

    ksort($routes);

    expect($routes)->toBe([
        'pennantplus.features.check' => 'GET|HEAD pennantplus/features/{feature}/check',
        'pennantplus.features.destroy' => 'DELETE pennantplus/features/{feature}',
        'pennantplus.features.index' => 'GET|HEAD pennantplus/features',
        'pennantplus.features.show' => 'GET|HEAD pennantplus/features/{feature}',
        'pennantplus.history.index' => 'GET|HEAD pennantplus/history',
        'pennantplus.scopes.index' => 'GET|HEAD pennantplus/scopes',
        'pennantplus.scopes.models' => 'GET|HEAD pennantplus/scopes/{type}/models',
        'pennantplus.values.destroy' => 'DELETE pennantplus/values',
        'pennantplus.values.index' => 'GET|HEAD pennantplus/values',
        'pennantplus.values.update' => 'PUT pennantplus/values',
    ]);
});
