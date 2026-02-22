# ADR-002: Whitelist .gitignore Strategy

**Date:** 2026-02-22
**Status:** Accepted

## Context

WordPress core files, uploads, and local configs should not be in the repository. The git root is at `app/public/` which contains WordPress core.

## Decision

Use a whitelist `.gitignore` that ignores everything by default (`*`) and explicitly allows project files with `!` rules. This is safer than blacklisting individual WP core directories.

## Consequences

### Positive
- New WP core files are automatically ignored
- Cannot accidentally commit WP core or uploads
- Clean git status

### Negative
- New project files must be explicitly allowed
- Slightly unusual pattern for developers unfamiliar with whitelist gitignore

### Neutral
- Requires updating .gitignore when adding new project directories
