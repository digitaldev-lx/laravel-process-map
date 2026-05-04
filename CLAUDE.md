# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Package overview

Static-analysis Laravel package that maps the business processes inside an
analysed Laravel application. Distributed via Packagist as
`digitaldev-lx/laravel-process-map`. Repository hosted at
`git@github.com:digitaldev-lx/laravel-process-map.git`.

## Requirements

- PHP `>= 8.4`
- Laravel `^12.0` or `^13.0`

## Commands

```bash
# Run all tests
vendor/bin/pest

# Run a single test file
vendor/bin/pest tests/Unit/ServiceProviderTest.php

# Static analysis (PHPStan level 6 + Larastan)
vendor/bin/phpstan analyse

# Code style (Pint, Laravel preset + strict_types)
vendor/bin/pint            # fix
vendor/bin/pint --test     # check only
```

## Architecture (target — partially implemented)

Read-only AST scanner package. The pipeline is:

1. **Scanners** (`src/Scanners/`) walk the analysed app via `nikic/php-parser`
   and produce `Discovered*` data objects. They never execute application
   code, never query the DB and never make HTTP calls.
2. **ApplicationScanner** orchestrates all enabled scanners and aggregates
   their output into a single `ProcessMapResult`.
3. **Detectors** (`src/Detectors/`) apply heuristics on top of the result:
   `NamingConventionProcessDetector` groups classes by entity, while
   `AutomationDetector`, `BottleneckDetector`, `RiskDetector` add hedge-worded
   recommendations.
4. **Exporters** (`src/Exporters/`) serialise the result to JSON, Markdown
   and Mermaid. JSON carries a `schema_version`. Html is a stub in v0.1.
5. **ProcessMap** (`src/ProcessMap.php`) is the singleton manager bound in
   the container — entry point for both Artisan commands and userland code.

## Key invariants

- The package is **read-only**. CI lint should reject `DB::|dispatch\(|event\(|Notification::send`
  inside `src/` (excluding comments and docblocks).
- AST parsing is preferred. `SafeReflection` is a fallback for metadata
  lookups and is gated by `process-map.safe_reflection.enabled`.
- The JSON output is the canonical artefact and is schema-versioned.
- `ComposerAutoloadResolver` reads `composer.json` of the analysed app to
  support modular layouts (DDD, nWidart/laravel-modules) — do not hard-code
  PSR-4 roots.

## Service provider

`DigitaldevLx\LaravelProcessMap\Providers\ProcessMapServiceProvider`:

- merges `config/process-map.php` under the `process-map` key;
- publishes the config under the `process-map-config` tag;
- registers `ProcessMap` as a singleton;
- registers Artisan commands when running in console (added in Phase 11).

## Testing

- `tests/TestCase.php` extends Orchestra Testbench and registers the service
  provider. Pest is the test runner.
- Real fixtures live under `tests/Fixtures/app/` as a mini Laravel-like
  application **stored as plain `.php` files**. They are read by scanners as
  source code via AST — they are **not** autoloaded.
- Coverage target: 80% (matches `laravel-invoice-express`).

## Phase status

See `CHANGELOG.md`. The repository is being built in 12 commits matching the
phases in the implementation plan. Do not mix phases in a single commit.
