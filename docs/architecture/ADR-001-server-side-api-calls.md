# ADR-001: Server-Side API Calls Only

**Date:** 2026-02-22
**Status:** Accepted

## Context

The XBO Public API (api.xbo.com) does not send CORS headers. Browser-based JavaScript cannot directly call the API.

## Decision

All XBO API calls go through the WordPress backend. The plugin provides WP REST API endpoints that proxy requests to the XBO API. Frontend JavaScript fetches data from the WP REST endpoints, never from api.xbo.com directly.

## Consequences

### Positive
- No CORS issues
- Server-side caching via WordPress transients
- API key management stays server-side (future-proof)

### Negative
- Extra hop adds latency
- WordPress server must handle all API traffic

### Neutral
- Standard pattern for WordPress plugins that consume external APIs
