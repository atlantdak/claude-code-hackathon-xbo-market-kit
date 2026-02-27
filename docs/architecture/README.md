# Architecture Decisions

Architecture Decision Records (ADRs) for XBO Market Kit.

## Index

| ADR | Title | Status | Date |
|-----|-------|--------|------|
| [ADR-001](ADR-001-server-side-api-calls.md) | Server-side API calls only | Accepted | 2026-02-22 |
| [ADR-002](ADR-002-whitelist-gitignore.md) | Whitelist .gitignore strategy | Accepted | 2026-02-22 |
| [ADR-003](ADR-003-tailwind-cdn.md) | Tailwind CSS via CDN (later replaced by BEM + CSS Custom Properties) | Superseded | 2026-02-22 |

## Key Architectural Decisions (Not Formalized as ADRs)

| Decision | Context | Date |
|----------|---------|------|
| BEM + CSS Custom Properties over Tailwind | Widget styling redesign for maintainability | 2026-02-25 |
| Local SVG icons over CDN | 205 crypto icons bundled, zero external dependency | 2026-02-25 |
| PageManager over DemoPage | Multi-page system (16 pages) replaces single demo page | 2026-02-27 |
| Interactivity API for Refresh Timer | WordPress-native reactivity instead of custom JS | 2026-02-27 |
| Bash deploy script over CI/CD | Manual deployment fits hackathon workflow | 2026-02-27 |
| Style variation over theme.json edit | Non-destructive XBO branding, original theme preserved | 2026-02-27 |

---

*ADRs follow the template in [ADR-template.md](ADR-template.md).*
