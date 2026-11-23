<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Managed store
    |--------------------------------------------------------------------------
    |
    | The Pennant store the API endpoints and MCP tools manage, null for
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
    | by every API endpoint and MCP tool. Null lets the route and MCP
    | middleware decide on their own.
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
    | Scope type cache
    |--------------------------------------------------------------------------
    |
    | How many seconds the model types that have stored values (the
    | `GET scopes` endpoint and the list-scope-types MCP tool) are cached
    | in the default cache store. Writes through PennantPlus forget the cache at
    | once; a model type first stored elsewhere shows up when it expires.
    | 0 reads them afresh every time.
    |
    */

    'scope_types_cache_seconds' => 300,

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
    | Auditing
    |--------------------------------------------------------------------------
    |
    | PennantPlus keeps no audit log of its own. With refactor-circus/keen
    | installed, Keen records every flag change PennantPlus makes, and
    | PennantPlus serves the history on its `GET /pennantplus/history` route
    | and its history MCP tool. Without Keen none of that exists.
    |
    | enabled: Set to false to keep PennantPlus out of the audit log even
    |          while Keen is installed: Keen records nothing of PennantPlus's,
    |          and PennantPlus offers no history.
    |
    */

    'audit' => [
        'enabled' => true,
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

    /*
    |--------------------------------------------------------------------------
    | Cortex
    |--------------------------------------------------------------------------
    |
    | When refactor-circus/cortex is installed, the MCP server is registered with it, so
    | its instructions can be overridden, and the tools join its registry,
    | so Cortex agents can inspect and change feature flags. Set `tools` to
    | a list of tool names, such as ['list-features-tool',
    | 'check-feature-tool'], to offer only some of them.
    |
    */

    'cortex' => [
        'enabled' => true,
        'server' => 'pennantplus',
        'tools' => null,
    ],

];
