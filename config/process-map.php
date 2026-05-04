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

    /*
    |--------------------------------------------------------------------------
    | MCP layer
    |--------------------------------------------------------------------------
    |
    | The package can expose the process map through a read-only Model
    | Context Protocol (MCP) server backed by laravel/mcp. The layer is
    | OFF by default. Enable it explicitly when you want Claude Code (or
    | any MCP-compatible client) to query your process map dynamically.
    |
    | The implementation is strictly read-only: no shell, no SQL, no
    | external HTTP, no .env exposure. See the README "MCP Support"
    | section for the full security envelope.
    |
    */

    'mcp' => [
        'enabled' => env('PROCESS_MAP_MCP_ENABLED', false),

        'read_only' => true,

        'server' => [
            'name' => env('PROCESS_MAP_MCP_NAME', 'Laravel Process Map'),
            'version' => '1.1.0',
            'description' => 'Read-only MCP interface for Laravel application process maps.',
            'instructions' => 'Use the available resources, tools and prompts to inspect the application\'s business processes. Every interaction is read-only.',
        ],

        // Where Mcp::local() / Mcp::web() registration is expected. The
        // commands print the snippet for routes/ai.php — they never edit
        // application files automatically.
        'registration' => [
            'transport' => env('PROCESS_MAP_MCP_TRANSPORT', 'local'),
            'handle' => env('PROCESS_MAP_MCP_HANDLE', 'process-map'),
            'web_path' => env('PROCESS_MAP_MCP_PATH', '/mcp/process-map'),
        ],

        'resources' => [
            'enabled' => true,
            'include_markdown' => true,
            'include_json' => true,
        ],

        'tools' => [
            'enabled' => true,
            'allow_refresh_scan' => env('PROCESS_MAP_MCP_ALLOW_REFRESH', true),
            'allow_compare_scans' => false,
            'max_processes_returned' => 100,
            'max_classes_returned' => 250,
            'max_routes_returned' => 500,
            'max_related_depth' => 3,
            'max_response_bytes' => 256_000,
        ],

        'prompts' => [
            'enabled' => true,
        ],

        'security' => [
            'expose_file_paths' => true,
            'expose_method_names' => true,
            'expose_private_properties' => false,
            'expose_docblocks' => false,
            'redact_sensitive_values' => true,
            'allow_external_http' => false,
            'allow_shell_execution' => false,
            'allow_database_queries' => false,
            'allow_code_modification' => false,
        ],

        'cache' => [
            'enabled' => true,
            'ttl_seconds' => 300,
        ],
    ],

];
