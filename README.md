# Laravel Process Map

[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE.md)
[![PHP](https://img.shields.io/badge/php-%5E8.4-777bb4)](composer.json)
[![Laravel](https://img.shields.io/badge/laravel-12%20%7C%2013-ff2d20)](composer.json)
[![Tests](https://img.shields.io/badge/tests-Pest%204-fb503b)](composer.json)
[![Static analysis](https://img.shields.io/badge/phpstan-level%206-7986cb)](phpstan.neon.dist)

> Static-analysis tool that maps the business processes inside any Laravel application — without ever executing it.

`digitaldev-lx/laravel-process-map` reads your application's source code and registered Laravel runtime metadata (routes, schedule, broadcast channels) and produces a machine- and human-readable map of the processes it can identify: **who calls what, which models are mutated where, which jobs/events/listeners participate in each flow, and which parts of the codebase look fully automated versus manual**.

The output is useful for:

- **Developers** onboarding a new project — get a feel for the architecture in minutes instead of days.
- **Tech leads & consultants** auditing a codebase — see automation gaps, bottlenecks and risks without instrumenting anything.
- **Documentation** — generate a versioned snapshot of the application's process map and commit it to the repo.
- **AI agents** (v0.3+) — expose the process map as an MCP resource so agents can reason about your domain.

## Table of contents

- [Status](#status)
- [How it works](#how-it-works)
- [Requirements](#requirements)
- [Installation](#installation)
- [Quick start](#quick-start)
- [Artisan commands](#artisan-commands)
- [Output formats](#output-formats)
  - [JSON](#json)
  - [Markdown](#markdown)
  - [Mermaid](#mermaid)
- [Designed for LLM consumption](#designed-for-llm-consumption)
- [Configuration](#configuration)
- [Available scanners](#available-scanners)
- [Process detection](#process-detection)
- [Programmatic API](#programmatic-api)
- [Safety guarantees](#safety-guarantees)
- [Limitations](#limitations)
- [Roadmap](#roadmap)
- [Contributing](#contributing)
- [License](#license)

## Status

Stable (`1.0.x`). The public Artisan signatures, exporter formats and JSON schema (`schema_version: "0.1"`) are committed to. Breaking JSON changes will bump the `schema_version` field and be documented in [CHANGELOG.md](CHANGELOG.md).

## How it works

```
┌────────────────────────────────────────────────────────────────────┐
│                        ApplicationScanner                          │
│                                                                    │
│  ┌─────────────┐   ┌─────────────┐   ┌─────────────┐               │
│  │  AST scan   │   │  Router     │   │  Schedule   │               │
│  │ (php-parser)│   │ (read-only) │   │ (read-only) │               │
│  └──────┬──────┘   └──────┬──────┘   └──────┬──────┘               │
│         │                 │                 │                      │
│         └─────────────────┴─────────────────┘                      │
│                           │                                        │
│                           ▼                                        │
│              ┌──────────────────────────┐                          │
│              │   ProcessMapResult       │                          │
│              │ (DTOs, schema-versioned) │                          │
│              └────────────┬─────────────┘                          │
│                           │                                        │
│       ┌───────────────────┼────────────────────┐                   │
│       ▼                   ▼                    ▼                   │
│  ┌─────────┐      ┌──────────────┐    ┌─────────────────┐          │
│  │ Naming  │      │ Automation   │    │ Bottleneck/Risk │          │
│  │ cluster │      │ scoring      │    │  detectors      │          │
│  └────┬────┘      └──────┬───────┘    └────────┬────────┘          │
│       └──────────────────┴─────────────────────┘                   │
│                          │                                         │
│                          ▼                                         │
│            ┌─────────────────────────┐                             │
│            │  Json / Markdown /      │                             │
│            │  Mermaid exporters      │                             │
│            └─────────────────────────┘                             │
└────────────────────────────────────────────────────────────────────┘
```

Source files are parsed via [`nikic/php-parser`](https://github.com/nikic/PHP-Parser) into an AST. Class metadata (extends, implements, traits, public methods, calls to `dispatch()`/`event()`/`Notification::send`, etc.) is collected by a single visitor and decorated per scanner type.

Routes, scheduled tasks and broadcast channels come from Laravel's runtime APIs (`Router::getRoutes()`, `Schedule::events()`, `routes/channels.php`) — read-only access only.

A heuristic clustering step then groups classes into business processes by stripping verbs and technical suffixes from class names, and a set of detectors annotates each process with automation level, potential bottlenecks and risks.

## Requirements

| Dependency | Version |
| --- | --- |
| PHP | `^8.4` |
| Laravel | `^12.0 \|\| ^13.0` |
| `nikic/php-parser` | `^5.0` |
| `symfony/finder` | `^7.0` |

The package is tested against the matrix above on every push (see `.github/workflows/tests.yml`).

## Installation

Install as a **dev dependency** — the package is a developer/consulting tool, not a runtime concern:

```bash
composer require digitaldev-lx/laravel-process-map --dev
```

Run the install command to publish the configuration and create the output directory:

```bash
php artisan process-map:install
```

To install **and** scan in one go:

```bash
php artisan process-map:install --scan
```

The service provider is auto-discovered via `extra.laravel.providers`, so no manual registration is required.

## Quick start

```bash
php artisan process-map:scan --all
```

This produces, in `storage/app/process-map/`:

- `process-map.json` — full structured data, schema-versioned.
- `process-map.md` — human-readable Markdown report.
- `process-map.mmd` — Mermaid flowchart of the discovered processes.

A typical terminal summary:

```
Scanning application...

Scanned:
  - Models: 14
  - Controllers: 22
  - Actions: 31
  - Jobs: 9
  - Events: 12
  - Listeners: 15
  - Notifications: 8
  - Policies: 6
  - Commands: 5
  - Routes: 84

Detected processes:
  - Booking Management (automation: high)
  - Lead Management (automation: high)
  - Payment Handling (automation: medium)
  - User Onboarding (automation: low)

Outputs:
  - storage/app/process-map/process-map.json
  - storage/app/process-map/process-map.md
  - storage/app/process-map/process-map.mmd
```

## Artisan commands

| Command | What it does |
| --- | --- |
| `process-map:install [--scan]` | Publishes the config and creates the output directory. With `--scan`, runs a full scan immediately. |
| `process-map:scan [flags]` | Runs every enabled scanner and exporter. See flags below. |
| `process-map:report [--output=…]` | Generates only the Markdown report. |
| `process-map:json [--output=…]` | Generates only the JSON artefact. |
| `process-map:mermaid [--output=…]` | Generates only the Mermaid diagram. |

`process-map:scan` accepts:

- `--json`, `--markdown`, `--mermaid` — generate the corresponding format.
- `--all` — generate every default format (JSON + Markdown + Mermaid).
- `--output=path` — override the output directory for this run.
- `--no-routes` — skip the route scanner.
- `--no-process-detection` — skip the heuristic clustering.

If no format flag is passed, the command falls back to `config('process-map.exports.*')`.

## Output formats

### JSON

Canonical artefact, schema-versioned. Excerpt:

```json
{
  "schema_version": "0.1",
  "generated_at": "2026-05-04T18:00:00+00:00",
  "package": "digitaldev-lx/laravel-process-map",
  "version": "0.1.0",
  "app": {
    "name": "Example App",
    "environment": "local",
    "laravel_version": "13.0",
    "php_version": "8.4.1"
  },
  "summary": {
    "models": 14, "controllers": 22, "actions": 31,
    "jobs": 9, "events": 12, "listeners": 15,
    "notifications": 8, "policies": 6, "commands": 5,
    "routes": 84, "processes": 5
  },
  "classes": [
    {
      "type": "action",
      "class_name": "App\\Actions\\CreateLeadAction",
      "short_name": "CreateLeadAction",
      "namespace": "App\\Actions",
      "file_path": "app/Actions/CreateLeadAction.php",
      "methods": ["__invoke"],
      "traits": [],
      "interfaces": [],
      "references": [
        "App\\Jobs\\SendLeadFollowUpJob",
        "App\\Events\\LeadCreated",
        "App\\Models\\Lead"
      ],
      "metadata": {
        "main_method": "__invoke",
        "dispatches_jobs": ["App\\Jobs\\SendLeadFollowUpJob"],
        "fires_events": ["App\\Events\\LeadCreated"]
      }
    }
  ],
  "routes": [
    {
      "methods": ["POST"],
      "uri": "leads",
      "name": "leads.store",
      "action": "App\\Http\\Controllers\\LeadController@store",
      "controller_class": "App\\Http\\Controllers\\LeadController",
      "controller_method": "store",
      "middleware": ["web", "auth"],
      "domain": null
    }
  ],
  "processes": [
    {
      "name": "Lead Management",
      "entity": "Lead",
      "automation_level": "high",
      "components": {
        "models": ["App\\Models\\Lead"],
        "actions": ["App\\Actions\\CreateLeadAction"],
        "jobs": ["App\\Jobs\\SendLeadFollowUpJob"],
        "events": ["App\\Events\\LeadCreated"],
        "listeners": ["App\\Listeners\\SendLeadCreatedNotification"],
        "notifications": ["App\\Notifications\\LeadAssignedNotification"]
      },
      "potential_bottlenecks": [],
      "risks": [
        "Process 'Lead Management' has no associated policy: authorisation may be missing."
      ],
      "recommendations": [
        "Add a policy for the Lead model."
      ]
    }
  ]
}
```

### Markdown

The Markdown exporter produces a **dense, single-pass document** built so a
human (or an LLM) can grasp the architecture in one read. Sections produced
in `process-map.md`:

```
# Process Map: <App Name>
> header — generated_at · PHP · Laravel · Schema · Package version

## Summary                  — counts table (models / controllers / … / routes / processes / bottlenecks / risks)

## Processes                — one block per detected process
### N. <Process Name>
- Entity / Automation level
#### Members                — table: Role · Class (FQCN) · File · Notes
#### Flow                   — explicit trace: route → controller → action → job/event → listener → notification
#### Findings               — ⚠ Bottleneck / 🛡 Risk / 💡 Recommendation lines

## Unattached Components    — classes not bound to any process
## Routes Index             — Method · URI · Name · Controller@method · Middleware
## Scheduled Tasks          — Cron · Command · Description · Flags
## Broadcast Channels       — channel name + auth callback presence
```

A real, fully-rendered example produced from a small fixture app
(2 models, 1 controller, 1 action, 2 jobs, 1 event, 1 listener,
1 notification, 1 policy, 1 command, 3 routes):

```markdown
# Process Map: Demo App

> Static analysis snapshot — read-only.
> Generated: `2026-05-04T21:49:56+00:00` · PHP `8.4.1` · Laravel `13.7.0` · Schema `0.1` · Package `digitaldev-lx/laravel-process-map@0.1.0`

## Summary

| Metric | Count |
| --- | ---: |
| Models | 2 |
| Controllers | 1 |
| Actions | 1 |
| Jobs | 2 |
| Events | 1 |
| Listeners | 1 |
| Notifications | 1 |
| Policies | 1 |
| Commands | 1 |
| Routes | 3 |
| Detected processes | 1 |
| Potential bottlenecks | 1 |
| Risks flagged | 0 |

## Processes

### 1. Lead Management

- **Entity:** `Lead`
- **Automation:** high

#### Members

| Role | Class | File | Notes |
| --- | --- | --- | --- |
| Model | `App\Models\Lead` | `app/Models/Lead.php` | table=`leads` · softDeletes · factory |
| Controller | `App\Http\Controllers\LeadController` | `app/Http/Controllers/LeadController.php` | actions: index/store/destroy · requests: 1 |
| Action | `App\Actions\CreateLeadAction` | `app/Actions/CreateLeadAction.php` | entry=`__invoke` · dispatches: 1 · fires: 1 |
| Job | `App\Jobs\SendLeadFollowUpJob` | `app/Jobs/SendLeadFollowUpJob.php` | **sync** (no ShouldQueue) |
| Event | `App\Events\LeadCreated` | `app/Events/LeadCreated.php` |  |
| Listener | `App\Listeners\SendLeadCreatedNotification` | `app/Listeners/SendLeadCreatedNotification.php` | listens=`LeadCreated` · queued |
| Notification | `App\Notifications\LeadAssignedNotification` | `app/Notifications/LeadAssignedNotification.php` | channels: mail, database · queued |
| Policy | `App\Policies\LeadPolicy` | `app/Policies/LeadPolicy.php` | standard: viewAny/update · custom: assignToAgent |

#### Flow

- `POST /leads` → `LeadController@store`
- `GET,HEAD /leads` → `LeadController@index`
- `DELETE /leads/{lead}` → `LeadController@destroy`
- `LeadController` → invokes `CreateLeadAction`
- `CreateLeadAction` → dispatches `SendLeadFollowUpJob`
- `CreateLeadAction` → fires `LeadCreated`
- `LeadCreated` → handled by `SendLeadCreatedNotification` [queued]
- `SendLeadCreatedNotification` → sends `LeadAssignedNotification`

#### Findings
- ⚠ Bottleneck: Job 'SendLeadFollowUpJob' does not implement ShouldQueue: it may run synchronously.

## Unattached Components

| Type | Class | File | Notes |
| --- | --- | --- | --- |
| Model | `App\Models\Booking` | `app/Models/Booking.php` |  |
| Job | `App\Jobs\ProcessHeavyReportJob` | `app/Jobs/ProcessHeavyReportJob.php` | queued · queue=`reports` · tries=5 · timeout=120s |
| Command | `App\Console\Commands\SyncLeadsCommand` | `app/Console/Commands/SyncLeadsCommand.php` | `leads:sync {--since=}` |

## Routes Index

| Method | URI | Name | Controller@method | Middleware |
| --- | --- | --- | --- | --- |
| POST | /leads | leads.store | `LeadController@store` | web, auth |
| GET,HEAD | /leads | leads.index | `LeadController@index` | — |
| DELETE | /leads/{lead} | leads.destroy | `LeadController@destroy` | — |

## Broadcast Channels

- `App.Models.User.{id}` (with auth callback)
- `leads` (with auth callback)
```

That fixture renders to **85 lines (~2.6 KB)**. A typical ~150-class
Laravel application produces ~600–800 lines (~20 KB) — well inside any
modern context window.

### Mermaid

Generates one overview flowchart plus one diagram per detected process:

```mermaid
flowchart TD
    route_0["POST leads"]
    App_Http_Controllers_LeadController["LeadController"]
    route_0 --> |HTTP@store| App_Http_Controllers_LeadController
    App_Actions_CreateLeadAction --> |dispatches| App_Jobs_SendLeadFollowUpJob
    App_Actions_CreateLeadAction --> |fires| App_Events_LeadCreated
    App_Events_LeadCreated --> |handled by| App_Listeners_SendLeadCreatedNotification
    App_Listeners_SendLeadCreatedNotification --> |sends| App_Notifications_LeadAssignedNotification
```

Render with the [Mermaid Live Editor](https://mermaid.live), the GitHub-flavoured Markdown viewer, or VS Code's Mermaid Preview extension.

## Designed for LLM consumption

The Markdown report is the canonical artefact for **AI-assisted development**.
Drop `process-map.md` into Claude Code, ChatGPT, Cursor, Aider or any other
LLM tool and you get an instantly navigable map of the application — without
the LLM having to grep, open dozens of files, or guess how the pieces fit
together.

Concrete advantages when fed to an LLM:

1. **File path on every row.** The Members and Unattached tables include the
   relative path of each class, so the LLM can open the right file directly
   instead of running a search.
2. **One canonical place per class.** Every class appears in exactly one
   spot — either inside the Process block it belongs to or inside
   *Unattached Components*. No duplicate listings, no wasted tokens.
3. **Dense `Notes` column.** Operational facts are inline:
   `queue=reports · tries=5 · timeout=120s`,
   `channels: mail, database · queued`,
   `**sync** (no ShouldQueue)`. The LLM skips reading the source for
   the most common questions.
4. **Explicit `Flow` trace.** The `route → controller → action → job/event
   → listener → notification` sequence is spelled out as bullet points so
   the request lifecycle is comprehensible in one read.
5. **Reference-aware clustering.** Classes that don't match a naming
   convention are still attached to their process via static-call
   references (e.g. `CreateLeadAction → dispatches SendLeadFollowUpJob`
   pulls the job into the *Lead Management* block). Fewer orphans, cleaner
   reasoning.
6. **Hedge wording on findings.** Bottlenecks/risks/recommendations are
   prefixed with `⚠ Bottleneck:`, `🛡 Risk:`, `💡 Recommendation:` — the LLM
   treats them as investigation hints, not as facts.
7. **Schema-versioned header.** `Schema 0.1` lets you build reproducible
   prompts: pin the schema version in your tooling and your prompts keep
   working when you upgrade the package.
8. **Token-efficient.** ~85 lines / ~2.6 KB for a 12-class fixture;
   ~600–800 lines / ~20 KB for a typical ~150-class Laravel app. Plenty of
   room left for the LLM's own reasoning and tool calls.

### Quick recipe — feed it to Claude Code

```bash
php artisan process-map:scan --markdown
# then, inside Claude Code:
#   /add-dir storage/app/process-map
# or paste the file:
cat storage/app/process-map/process-map.md | pbcopy
```

A useful starter prompt once the file is in context:

> *"Use `process-map.md` as the architectural map of this app. Don't grep
> unless I ask — pull file paths and references from the report. When you
> see ⚠/🛡/💡 markers, treat them as starting points for deeper review,
> not as confirmed bugs."*

## Configuration

Publish the configuration:

```bash
php artisan vendor:publish --tag=process-map-config
```

The published file lives at `config/process-map.php` and exposes the following groups:

```php
return [
    'paths' => [
        'app' => app_path(),
        'routes' => base_path('routes'),
    ],

    'output_path' => storage_path('app/process-map'),

    'scan' => [
        'models' => true, 'controllers' => true, 'actions' => true,
        'jobs' => true, 'events' => true, 'listeners' => true,
        'notifications' => true, 'policies' => true, 'commands' => true,
        'routes' => true, 'schedule' => true, 'broadcasting' => true,
    ],

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

    'process_detection' => [
        'enabled' => true,
        'keywords' => ['create', 'update', 'delete', /* … */],
        'business_suffixes' => ['Action', 'Job', 'Command', 'Service', 'Workflow', 'Process'],
    ],

    'exports' => [
        'json' => true, 'markdown' => true, 'mermaid' => true, 'html' => false,
    ],

    'privacy' => [
        'include_method_names' => true,
        'include_properties' => false,
        'include_docblocks' => false,
        'include_file_paths' => true,
        'redact_env_values' => true,
    ],
];
```

For modular layouts (DDD, `nwidart/laravel-modules`), add additional roots under `directories`:

```php
'directories' => [
    'actions' => ['app/Actions', 'modules/*/Actions'],
    'models' => ['app/Models', 'modules/*/Domain/Models'],
],
```

## Available scanners

| Scanner | What it produces |
| --- | --- |
| `ModelScanner` | Eloquent models with table, fillable, casts, traits, soft-delete and factory flags |
| `ControllerScanner` | Controllers with public actions, form requests, dispatched events |
| `ActionScanner` | Action classes with main entry method (`handle`/`execute`/`__invoke`/`run`), dispatched jobs, fired events |
| `JobScanner` | Jobs with `ShouldQueue`, `$queue`, `$tries`, `$timeout`, `$backoff` |
| `EventScanner` | Events with broadcasting flags and `Dispatchable`/`SerializesModels` traits |
| `ListenerScanner` | Listeners with the event they handle and queue flag |
| `NotificationScanner` | Notifications with channels (`via()`) and `to*` delivery methods |
| `PolicyScanner` | Policies split into standard abilities (`view`, `create`, …) and custom abilities, mapped to a model name |
| `CommandScanner` | Artisan commands with signature and description |
| `RouteScanner` | Registered routes with controller, methods, name, middleware, domain |
| `ScheduleScanner` | Scheduled tasks with cron expression, command, mutex, timezone, `withoutOverlapping`, `onOneServer` |
| `BroadcastScanner` | Channels declared via `Broadcast::channel()` in `routes/channels.php` |

Each scanner can be turned off in `config('process-map.scan.*')`.

## Process detection

The `NamingConventionProcessDetector` is intentionally simple:

1. Walk every `DiscoveredClass` of a participating type.
2. Strip the **business suffix** (e.g. `Action`, `Job`).
3. Strip the leading **verb** (e.g. `Create`, `Send`).
4. Strip common **participles** (e.g. `Created`, `Updated`).
5. Cluster by what remains. Clusters with at least 2 classes become a process.

Then the detectors annotate:

- `AutomationDetector` — assigns `none`/`low`/`medium`/`high` based on which kinds of components a process ships (jobs and schedule weigh more than plain models).
- `BottleneckDetector` — surfaces hedge-worded hints like *"Notifications in 'Lead Management' may run synchronously"*.
- `RiskDetector` — flags missing policies, jobs without `$tries`/`$timeout`, destructive command signatures.

The output is **always best-effort**. Hedge wording (`Potential…`, `May indicate…`) is intentional and propagated to the Markdown report.

## Programmatic API

Resolve the singleton `ProcessMap` from the container:

```php
use DigitaldevLx\LaravelProcessMap\ProcessMap;

$processMap = app(ProcessMap::class);

$result = $processMap->scan();          // returns ProcessMapResult

$json = $processMap->exportJson($result, storage_path('app/snapshot.json'));
$md   = $processMap->exportMarkdown($result);   // dense, LLM-ready string
$mmd  = $processMap->exportMermaid($result);
```

The string returned by `exportMarkdown()` is identical to the file written
by `process-map:report` and is safe to drop straight into an LLM prompt
(see [Designed for LLM consumption](#designed-for-llm-consumption)).

`ProcessMapResult` is a typed, readonly DTO:

```php
$result->summary->models;        // int
$result->classes;                // list<DiscoveredClass>
$result->routes;                 // list<DiscoveredRoute>
$result->processes;              // list<DiscoveredProcess>
$result->toArray();              // recursively-serialised array
```

Use cases:

- Diff two snapshots in CI to detect architectural drift.
- Pipe the JSON into Backstage / TechDocs / your internal wiki.
- Feed the JSON to an LLM as context for code-review prompts.

## Safety guarantees

The package is **strictly read-only**. It never:

- executes business code, jobs, events or notifications;
- queries the database;
- makes HTTP calls;
- writes anywhere outside the configured `output_path`;
- exposes the contents of `.env` or other secrets.

Analysis is performed via AST parsing. Reflection is opt-in through `process-map.safe_reflection.enabled` and only used for safe metadata lookups (interfaces, traits, parent class).

A dedicated test (`tests/Unit/ReadOnlyInvariantTest.php`) scans the package's own `src/` directory for forbidden helpers (`DB::`, `dispatch(`, `event(`, `Notification::send`, `Mail::send`, `Http::*`) and fails the build if any are introduced.

## Limitations

- **Heuristic detection**: process clustering uses naming conventions. Apps that don't follow Laravel's standard structure may need custom suffixes/keywords in the config.
- **AST scope**: only top-level classes are inspected. Anonymous classes, interfaces and traits are skipped (intentional — they're not addressable as process participants).
- **Lazy routes**: routes registered conditionally at runtime (after `RouteServiceProvider::boot`) are detected; routes registered through middleware or rare runtime paths may not be.
- **Schedule introspection**: depends on Laravel's `Schedule::events()` API. Closure-based schedules expose only the cron expression and timezone.
- **HTML exporter**: stub in v0.1, scheduled for v0.2.

## Roadmap

| Version | Highlights |
| --- | --- |
| **v1.0** *(current)* | Stable initial release — static scan, JSON/Markdown/Mermaid exporters, heuristic process detection with reference-aware clustering, dense LLM-ready Markdown. |
| **v1.1** | HTML dashboard, AST-based reference resolution improvements, automation scoring refinements. |
| **v1.2** | MCP server resource — expose the same LLM-ready report (and JSON) as a tool an AI agent can pull on demand. |
| **v1.3** | GitHub Action, scan diff between commits, architectural regression detection. |
| **v2.0** | Plugin SPI for custom scanners and detectors; potential schema upgrade. |

## Contributing

Issues and pull requests are welcome at [github.com/digitaldev-lx/laravel-process-map](https://github.com/digitaldev-lx/laravel-process-map).

Before opening a PR, run the quality checks locally:

```bash
composer test       # Pest
composer analyse    # PHPStan level 6 + Larastan
composer format     # Pint (Laravel preset + strict_types)
```

Bug reports should include:

- PHP version
- Laravel version
- Package version
- A minimal reproduction (sample app folder structure and the offending output)

See [`.github/ISSUE_TEMPLATE`](.github/ISSUE_TEMPLATE) for the templates.

## License

MIT — see [LICENSE.md](LICENSE.md).

Built by [DigitalDev](https://digitaldev.pt).
