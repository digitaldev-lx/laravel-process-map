<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Paths
    |--------------------------------------------------------------------------
    |
    | Filesystem locations the package will inspect. By default we look at the
    | application code under "app/" and the route files under "routes/".
    |
    */

    'paths' => [
        'app' => app_path(),
        'routes' => base_path('routes'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Output path
    |--------------------------------------------------------------------------
    |
    | Where the exporters write their files. The directory is created on first
    | run if it does not exist.
    |
    */

    'output_path' => storage_path('app/process-map'),

    /*
    |--------------------------------------------------------------------------
    | Scanners
    |--------------------------------------------------------------------------
    |
    | Toggle individual scanners. Disable any scanner you do not want to run.
    |
    */

    'scan' => [
        'models' => true,
        'controllers' => true,
        'actions' => true,
        'jobs' => true,
        'events' => true,
        'listeners' => true,
        'notifications' => true,
        'policies' => true,
        'commands' => true,
        'routes' => true,
        'schedule' => true,
        'broadcasting' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Directories
    |--------------------------------------------------------------------------
    |
    | Source folders, relative to the application root, where the package
    | searches for each kind of class. Apps with non-standard layouts (modules,
    | DDD) can override these or add additional roots.
    |
    */

    'directories' => [
        'models' => ['app/Models'],
        'controllers' => ['app/Http/Controllers'],
        'actions' => ['app/Actions'],
        'jobs' => ['app/Jobs'],
        'events' => ['app/Events'],
        'listeners' => ['app/Listeners'],
        'notifications' => ['app/Notifications'],
        'policies' => ['app/Policies'],
        'commands' => ['app/Console/Commands'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Routes
    |--------------------------------------------------------------------------
    |
    | Registered routes whose URI starts with one of these prefixes are
    | excluded from the scan. Useful to keep noise from third-party packages
    | (Telescope, Horizon, Pulse, Debugbar, Ignition, etc.) out of the map.
    |
    */

    'routes' => [
        'ignore_uri_prefixes' => [
            '_ignition',
            '_debugbar',
            'telescope',
            'horizon',
            'pulse',
            'log-viewer',
            'sanctum/csrf-cookie',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Broadcasts
    |--------------------------------------------------------------------------
    |
    | Files scanned for `Broadcast::channel(...)` declarations. Apps with
    | modular layouts can append additional files here.
    |
    */

    'broadcasts' => [
        'files' => [
            'routes/channels.php',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Process detection
    |--------------------------------------------------------------------------
    |
    | Heuristic naming-convention detector. Classes are grouped into business
    | processes by stripping the listed verbs and suffixes and clustering by
    | the remaining entity name.
    |
    */

    'process_detection' => [
        'enabled' => true,

        'keywords' => [
            'create',
            'update',
            'delete',
            'approve',
            'reject',
            'publish',
            'send',
            'notify',
            'import',
            'export',
            'sync',
            'assign',
            'complete',
            'cancel',
            'pay',
            'invoice',
            'book',
            'schedule',
            'generate',
            'review',
            'moderate',
        ],

        // Business suffixes are stripped on top of the always-stripped technical
        // ones (Controller, Notification, Event, Listener, Policy). Add your
        // own conventions here (e.g. "UseCase", "Handler").
        'business_suffixes' => [
            'Action',
            'Job',
            'Command',
            'Service',
            'Workflow',
            'Process',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Mermaid output limits
    |--------------------------------------------------------------------------
    |
    | Mermaid diagrams become unreadable beyond a few hundred nodes. When the
    | overview diagram would exceed `max_nodes`, the exporter emits a
    | truncated diagram with a comment explaining the cut-off. Per-process
    | diagrams are unaffected.
    |
    */

    'mermaid' => [
        'max_nodes' => 200,
    ],

    /*
    |--------------------------------------------------------------------------
    | Exports
    |--------------------------------------------------------------------------
    |
    | Output formats produced by `process-map:scan` when no explicit format
    | flag is supplied. The HTML exporter is experimental and disabled by
    | default in v0.1.
    |
    */

    'exports' => [
        'json' => true,
        'markdown' => true,
        'mermaid' => true,
        'html' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Privacy
    |--------------------------------------------------------------------------
    |
    | Controls what level of information lands in the exported artefacts. By
    | default we exclude properties and docblocks because they are likely to
    | contain business secrets or credentials. File paths are kept relative to
    | the application root.
    |
    */

    'privacy' => [
        'include_method_names' => true,
        'include_properties' => false,
        'include_docblocks' => false,
        'include_file_paths' => true,
        'redact_env_values' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Reflection fallback
    |--------------------------------------------------------------------------
    |
    | The package prefers AST parsing via nikic/php-parser to keep itself
    | strictly read-only. Reflection is used only for safe metadata lookups
    | (interfaces, traits) and can be disabled entirely if the analysed
    | application has constructors with side effects.
    |
    */

    'safe_reflection' => [
        'enabled' => true,
    ],

];
