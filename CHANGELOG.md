# Changelog

All notable changes to `digitaldev-lx/laravel-process-map` are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.1.1] - 2026-05-05

### Fixed

- `MermaidExporter` no longer emits `@` inside HTTP edge labels.
  Recent Mermaid versions reserve `@` as a `LINK_ID` marker, which
  caused `Parse error ... got 'LINK_ID'` on the overview flowchart.
  Edge labels now use `HTTP::method` (e.g. `|HTTP::store|`).
- README Mermaid example updated to match the new edge format.

## [1.1.0] - 2026-05-05

### Added

- **MCP layer (read-only)** built on top of `laravel/mcp` v0.7. Disabled
  by default; opt in via `PROCESS_MAP_MCP_ENABLED=true`.
- 8 MCP resources under the `process-map://` scheme:
  - `process-map://summary`
  - `process-map://processes`
  - `process-map://process/{slug}` (URI template)
  - `process-map://routes`
  - `process-map://classes`
  - `process-map://risks`
  - `process-map://recommendations`
  - `process-map://mermaid`
- 11 MCP tools, every one annotated `#[IsReadOnly]`:
  `get_process_map_summary`, `list_processes`, `get_process_details`,
  `get_process_components`, `get_process_risks`,
  `get_process_recommendations`, `get_related_classes`,
  `get_route_map`, `get_mermaid_diagram`, `refresh_process_map`,
  `compare_process_maps` (gated stub).
- 6 MCP prompts: `audit_process`, `refactor_process_safely`,
  `document_process`, `find_automation_opportunities`,
  `generate_technical_handover`, `prepare_mcp_tools_from_actions`.
- New Artisan commands: `process-map:mcp-install` (instructions only,
  never edits `.env` or `routes/ai.php` automatically) and
  `process-map:mcp-status` (tabular diagnostic).
- `ProcessMapRepository`: cached, sanitised loader for
  `process-map.json`. Honours `mcp.cache.ttl_seconds`.
- `ProcessMapSanitizer`: redacts string values whose key contains
  `password|secret|token|api_key|authorization|bearer|credential|...`.
- `McpSecurityGuard`: single source of truth for the read-only
  envelope and the configurable limits (max processes/classes/routes
  returned, max related-class depth clamped to [1, 5], max response
  bytes).
- `DiscoveredProcess` now exposes a stable `slug` (kebab-cased name).
  Added to the JSON output (additive, schema stays at `0.1`) and
  surfaced in the Markdown export under each process header.
- `StrHelpers::slug()` helper used by both the detector and the MCP
  URI scheme.
- `laravel/mcp` is a regular composer requirement so installing the
  package gives users the layer for free; it stays inert until they
  flip `PROCESS_MAP_MCP_ENABLED=true`.

### Changed

- `NamingConventionProcessDetector` propagates the slug through every
  rebuilt `DiscoveredProcess` in the detector pipeline (Automation /
  Bottleneck / Risk).
- README gains a **MCP Support** section with installation,
  registration, security and Markdown-vs-MCP guidance.
- Roadmap updated: v1.1 ships the MCP layer; v1.2 picks up the HTML
  dashboard and snapshot history; v1.3 brings the GitHub Action.

## [1.0.0] - 2026-05-04

### Added

- Service provider with publishable config (`process-map-config` tag) and Artisan commands.
- Static-analysis scanners (AST via `nikic/php-parser`):
  `ModelScanner`, `ControllerScanner`, `ActionScanner`, `JobScanner`,
  `EventScanner`, `ListenerScanner`, `NotificationScanner`,
  `PolicyScanner`, `CommandScanner`.
- Framework-aware scanners:
  `RouteScanner` (uses `Router::getRoutes()` with configurable URI ignore list),
  `ScheduleScanner` (uses `Schedule::events()`),
  `BroadcastScanner` (parses configurable broadcast files; default `routes/channels.php`).
- Heuristic detectors: `NamingConventionProcessDetector` (technical suffixes
  always stripped, business suffixes configurable), `AutomationDetector`,
  `BottleneckDetector`, `RiskDetector`.
- Exporters: `JsonExporter` (privacy flags applied), `MarkdownExporter`,
  `MermaidExporter` (configurable `max_nodes` for diagram truncation). JSON
  ships with `schema_version: "0.1"`.
- Artisan commands: `process-map:install`, `process-map:scan`,
  `process-map:report`, `process-map:json`, `process-map:mermaid`.
- Read-only invariant enforced by a dedicated test that scans `src/` for
  forbidden helpers (`DB::`, `dispatch(`, `event(`, `Notification::send`,
  `Mail::send`, `Http::*`).
- HTML exporter stub — throws `ProcessMapException::exporterNotImplemented`
  until the v0.2 dashboard ships.
- `ClassMetadataVisitor` now detects `Bus::dispatch(new Job)` patterns in
  addition to `Job::dispatch()` and `dispatch(new Job)`.
- `NotificationScanner` validates `to*` methods against a whitelist of known
  Laravel channels (`toMail`, `toDatabase`, `toBroadcast`, `toVonage`, etc.)
  to avoid false positives like `toString`/`toArray`.
- Package version is now read from `Composer\InstalledVersions` at runtime
  rather than hardcoded.

### Notes

- Requires PHP `^8.4` and Laravel `^12.0 || ^13.0`.
- Schema versioning starts at `0.1`. Breaking JSON changes will bump the
  schema and be documented here.
