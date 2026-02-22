# ADR-003: Tailwind CSS via CDN

**Date:** 2026-02-22
**Status:** Accepted

## Context

The hackathon timeline is 7 days. Setting up a proper CSS build pipeline (PostCSS, npm, Tailwind CLI) would consume significant time.

## Decision

Use Tailwind CSS via CDN for rapid prototyping. Custom plugin-specific styles use the `.xbo-mk-` prefix.

## Consequences

### Positive
- No npm/node required
- Instant access to all Tailwind utilities
- Faster development during hackathon

### Negative
- CDN adds ~300KB to page load
- Not suitable for production deployment
- Cannot tree-shake unused styles

### Neutral
- Can be migrated to Tailwind CLI build in post-hackathon optimization
