### Description

Explain the change and why it is needed.

### Fixes

Reference the issue this PR addresses (e.g. `Fixes #1`).

### Checklist

- [ ] `composer test` passes locally
- [ ] `composer analyse` passes locally
- [ ] `composer format:test` passes locally
- [ ] CHANGELOG.md updated under `## [Unreleased]`
- [ ] No code in `src/` triggers application side effects (DB queries, dispatch, event firing, HTTP)
