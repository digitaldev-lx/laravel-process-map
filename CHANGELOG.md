# Changelog

All notable changes to `digitaldev-lx/laravel-process-map` are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

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
