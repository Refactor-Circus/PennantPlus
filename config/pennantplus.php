<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Managed store
    |--------------------------------------------------------------------------
    |
    | The Pennant store the API, MCP tools and dashboard manage, null for
    | Pennant's default. Listing stored values needs a store using the
    | `database` or `pennantplus` driver.
    |
    */

    'store' => null,

    /*
    |--------------------------------------------------------------------------
    | Authorization
    |--------------------------------------------------------------------------
    |
    | An optional Gate ability checked, as the signed-in user or as a guest,
    | by every API endpoint, MCP tool and the dashboard page. Null lets the
    | route middleware (and Atrium's own gate) decide on their own.
    |
    */

    'ability' => null,

    /*
    |--------------------------------------------------------------------------
    | Global updates
    |--------------------------------------------------------------------------
    |
    | Setting a feature's global value forgets the values stored for every
    | other scope, so they follow the new global value.
    |
    */

    'purge_scopes_on_global_update' => true,

    /*
    |--------------------------------------------------------------------------
    | Feature discovery
    |--------------------------------------------------------------------------
    |
    | The directories holding class-based features, so every feature is
    | listed before Pennant has resolved it. Paths are globs, so one entry can
    | match a Features directory inside every domain.
    |
    */

    'features' => [
        // app_path('Features'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Scope models
    |--------------------------------------------------------------------------
    |
    | The models a value can be scoped to. Each entry is a model class, or a
    | class mapped to options: a "label", the "search" columns used to find a
    | model, and the "title" attribute shown for it.
    |
    */

    'scopes' => [
        // App\Models\User::class => [
        //     'label' => 'Users',
        //     'search' => ['name', 'email'],
        //     'title' => 'name',
        // ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature gate
    |--------------------------------------------------------------------------
    |
    | "global_only" lists the features (Str::is patterns) the FeatureGate
    | checks against the global scope only, never per user. Every other
    | feature must be active both globally and for the user.
    |
    */

    'gate' => [
        'global_only' => [
            '*SupportFeature',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | HTTP API routes
    |--------------------------------------------------------------------------
    |
    | The management API. Add authentication middleware (e.g. auth:sanctum)
    | before exposing it — it changes feature flags for every user.
    |
    */

    'routes' => [
        'enabled' => true,
        'prefix' => 'pennantplus',
        'middleware' => ['api'],
    ],

    /*
    |--------------------------------------------------------------------------
    | MCP server
    |--------------------------------------------------------------------------
    |
    | PennantPlus can register its MCP server for you. Both transports are
    | disabled by default; add auth middleware when enabling the web one.
    |
    */

    'mcp' => [
        'web' => [
            'enabled' => false,
            'route' => 'mcp/pennantplus',
            'middleware' => [],
        ],
        'local' => [
            'enabled' => false,
            'handle' => 'pennantplus',
        ],
    ],

];
