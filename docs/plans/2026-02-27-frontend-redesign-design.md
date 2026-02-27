# Frontend Redesign Design Document

**Date:** 2026-02-27
**Author:** Claude Sonnet 4.5 (with Dmytro Kishkin)
**Status:** Approved
**Implementation Approach:** Parallel Multi-Agent

## Overview

Complete frontend redesign of XBO Market Kit WordPress plugin showcase site to improve visual appeal, consistency, and user experience. This design addresses container width optimization, color palette refinement, navigation structure, and content organization.

## Requirements Summary

From user requirements:
1. Optimize container width for data-dense crypto widgets
2. Replace bright color palette with professional XBO.com colors
3. Integrate refresh timer block for visual "live data" effect
4. Use Gutenberg blocks instead of shortcodes
5. Implement Getwid blocks for enhanced layouts
6. Reorganize navigation with mega menu structure
7. Create comprehensive documentation and demo pages
8. Clean up duplicate and legacy pages

## Design Decisions

### 1. Theme Configuration

**Container Width:**
- Current: `contentSize: 760px`, `wideSize: 1160px`
- **New: `contentSize: 960px`, `wideSize: 1200px`**
- **Rationale:** 760px too narrow for data-dense widgets (orderbook 2-column grid, trades 4-column table, ticker cards with 200px min-width). 960px provides optimal balance between readability and data display space. Aligns with modern crypto exchange UX patterns.

**Color Palette:**
Replacing prime-fse theme colors with XBO.com brand palette:

```json
"palette": [
  {
    "color": "#6319ff",
    "name": "Royal Purple (Primary CTA)",
    "slug": "color-1"
  },
  {
    "color": "#140533",
    "name": "Deep Indigo (Text/Dark)",
    "slug": "color-4"
  },
  {
    "color": "#6341e4",
    "name": "Secondary Accent Purple",
    "slug": "color-2"
  },
  {
    "color": "#f4f3f8",
    "name": "Soft Section Background",
    "slug": "color-6"
  },
  {
    "color": "#14053333",
    "name": "Muted (Opacity)",
    "slug": "color-7"
  },
  {
    "color": "#ffffff",
    "name": "White",
    "slug": "color-9"
  }
]
```

Additional XBO colors for reference:
- Heading Purple: `#270A66`
- Secondary Text: `#898299`
- Trading Green (Positive): `#49B47A`
- Trading Red (Negative): `#FD3B5E`

**Getwid Plugin:**
Install Getwid WordPress plugin for advanced blocks: accordions, tabs, advanced columns, sections with backgrounds.

### 2. Homepage Redesign

**Hero Section:**
- Source: Migrate hero section from page ID 18 (xbo-market-kit)
- Structure: Cover block with background image, 2-column layout
  - Left column: Heading, description, CTA buttons
  - Right column: Crypto coins image
- Colors:
  - Heading: `#140533`
  - Description: `#4a4458` → update to `#898299` (XBO muted)
  - CTA buttons: `#6319ff` (primary), outline style (secondary)

**Refresh Timer Integration:**
- Add `<!-- wp:xbo-market-kit/refresh-timer /-->` before "Live Market Data" section
- Position: Between hero and first widget section
- Purpose: Visual indicator that all widgets refresh in real-time

**Block Migration (Shortcodes → Gutenberg):**
- `[xbo_ticker]` → `<!-- wp:xbo-market-kit/ticker -->`
- `[xbo_movers]` → `<!-- wp:xbo-market-kit/movers -->`
- `[xbo_orderbook]` + `[xbo_trades]` → blocks in Columns block
- `[xbo_slippage]` → `<!-- wp:xbo-market-kit/slippage -->`

**Background Colors:**
- Replace gradient sections (`xbo-mk-page-section--gradient`) with `#f4f3f8`
- Replace glass effect sections with white cards with shadow
- Feature cards: white background, subtle border

**Layout Structure:**
1. Hero section (full-width cover)
2. Ticker widget (wide alignment)
3. Feature cards (3-column Getwid Advanced Columns)
4. Refresh timer
5. Live market data section (Movers widget)
6. Two-column section (Orderbook + Trades in Columns)
7. Stats section (Getwid Section with custom HTML or icons)
8. Slippage calculator section
9. CTA section (full-width with #f4f3f8 background)

### 3. New Pages Structure

**3.1 Widgets Overview** (new page)
- Purpose: Comparison of all 5 widgets with use cases
- Layout: Getwid Advanced Columns (2-3 columns)
- Content per widget:
  - Widget name and icon
  - Description (2-3 sentences)
  - "Best for:" use cases
  - Screenshot or live preview
  - "View Demo" button → links to individual demo page
- Background: White cards on `#f4f3f8` section background

**3.2 Getting Started** (new page)
- Purpose: Quick start guide for plugin users
- Structure:
  1. Introduction section
  2. Installation steps (Getwid Section with numbered steps)
  3. First widget setup (with code examples in Code blocks)
  4. Configuration options
  5. Next steps (links to demos, docs)
- Add refresh timer at top
- Use Getwid Section blocks with icons for steps

**3.3 Real-world Layouts** (new page)
- Purpose: Showcase integration examples
- Content: 3-4 layout examples:
  1. Blog Post with ticker
  2. Landing Page with movers
  3. Portfolio with multiple widgets
  4. Exchange-style dashboard
- For each example:
  - Screenshot
  - Description
  - Use case explanation
  - Code snippet or pattern reference
- Use prime-fse patterns + XBO blocks

**3.4 Integration Guide** (new page)
- Purpose: Developer documentation for custom integration
- Structure: Getwid Tabs for different integration methods:
  - Tab 1: Theme Integration
  - Tab 2: Custom Styling
  - Tab 3: REST API Usage
  - Tab 4: Shortcode Reference
- Each tab: explanations, code examples, best practices
- Code blocks with syntax highlighting

**3.5 FAQ** (new page)
- Purpose: Answer common questions
- Structure: Getwid Accordion blocks
- Categories:
  - Installation & Setup
  - Configuration
  - Troubleshooting
  - API & Development
- Each accordion item: question + detailed answer

**3.6 Changelog** (new page)
- Purpose: Version history and compatibility
- Structure:
  1. Version table (latest first)
  2. Compatibility matrix (WP/PHP versions)
  3. Upgrade notes
- Use Table block or custom HTML table
- Highlight breaking changes

### 4. Widget Demo Pages Update

Apply to all 5 demo pages (ID 48-52: Ticker, Movers, Orderbook, Trades, Slippage):

**Page Structure:**

1. **Refresh Timer Block** (top of page)
   ```
   <!-- wp:xbo-market-kit/refresh-timer /-->
   ```

2. **Hero Section** (Getwid Section):
   - Widget name (H1)
   - Short description (paragraph)
   - "Best for: [use cases]" (emphasized text)
   - Background: `#f4f3f8` or gradient

3. **Live Demo Section**:
   - Section heading: "Live Demo"
   - Widget block with default parameters
   - Background: white card with shadow

4. **Configuration Options** (Getwid Accordion):
   - Accordion item per attribute/parameter
   - Description, accepted values, default
   - Code snippet example

5. **Variations** (Getwid Tabs):
   - Tab 1: Default View (live widget block)
   - Tab 2: Custom Styling (widget with custom CSS example)
   - Tab 3: API Integration (REST API example with code)
   - Each tab shows working example

6. **Related Resources Section**:
   - Links to Widgets Overview, API Docs, Integration Guide
   - 2-3 column layout with icon + text

7. **CTA Section** (full-width, `#f4f3f8` background):
   - "Try in Live Demo" button
   - "View API Documentation" button
   - GitHub link

**Color Scheme:**
- Section backgrounds: `#f4f3f8`
- Cards: white with `box-shadow`
- Buttons: `#6319ff` (primary), outline (secondary)
- Text: `#140533` (headings), `#898299` (body)

### 5. Navigation & Menu Structure

**Primary Navigation (Mega Menu):**

```
Home
Widgets ▼
  └─ Widgets Overview
  └─ Ticker Demo
  └─ Top Movers Demo
  └─ Order Book Demo
  └─ Recent Trades Demo
  └─ Slippage Calculator Demo
Showcase ▼
  └─ Showcase
  └─ Block Demos
  └─ Real-world Layouts
Developers ▼
  └─ Getting Started
  └─ API Documentation
  └─ Integration Guide
Resources ▼
  └─ FAQ
  └─ Changelog
```

**Footer Navigation:**
- Privacy Policy
- Contact (from footer content)

**Implementation:**
- Appearance → Editor → Navigation
- Update Primary Menu with new structure
- Navigation block with submenu items
- Styling: `#140533` text, `#6319ff` hover, `font-weight: 600`

### 6. Cleanup & Migration

**Pages to DELETE (trash):**
- ID 18: XBO Market Kit (xbo-market-kit) - duplicate hero page
- ID 16: Live Demo — XBO Market Kit Widgets - overlaps with Showcase
- ID 11: Integration Test — All Blocks - internal testing page
- ID 10: XBO Market Kit — REST API Documentation - duplicate docs
- ID 2: About (sample-page) - legacy sample page
- ID 12-17: Screenshot pages (all private) - admin clutter

**Content Migration:**
- Before deleting ID 10 (REST API Documentation):
  - Review content
  - Migrate useful sections to ID 47 (API Documentation)
  - Set up 301 redirect if needed (via plugin or .htaccess)

**Pages to FINALIZE:**
- ID 3: Privacy Policy - change from draft to published
- Add to footer navigation

**Post-cleanup Verification:**
- Check all internal links still work
- Verify navigation menu items point to correct pages
- Test all widget demos load correctly
- Confirm sitemap updated (automatic via WP)

## Technical Implementation Notes

### FSE (Full Site Editing)
- All customizations use FSE through Site Editor
- Template parts: header, footer (already exist in prime-fse)
- Page templates: use default page.html, override per-page via editor

### Block Editor
- All content created/edited via Block Editor (Gutenberg)
- No custom HTML where blocks available
- Getwid blocks for advanced layouts
- XBO Market Kit custom blocks for widgets

### Color Application
- Use theme.json color slugs for consistency
- Custom colors only for specific XBO trading colors (green/red)
- Test color contrast for accessibility (WCAG AA minimum)

### Responsive Design
- All layouts must work on mobile/tablet/desktop
- Getwid blocks include responsive options
- Widget blocks already have responsive CSS
- Test on common breakpoints: 320px, 768px, 1024px, 1440px

### Performance
- Optimize images (WebP format, lazy loading)
- Minimize custom CSS
- Leverage theme.json for styling
- Use WordPress core features over plugins where possible

## Success Criteria

1. ✅ Container width optimized (960px) for widget display
2. ✅ XBO.com color palette consistently applied
3. ✅ Refresh timer visible on homepage, demo pages, live demo
4. ✅ All shortcodes replaced with Gutenberg blocks
5. ✅ Getwid plugin installed and blocks utilized
6. ✅ Mega menu with logical navigation structure
7. ✅ 6 new documentation/showcase pages created
8. ✅ 5 widget demo pages enhanced with rich content
9. ✅ All duplicate/legacy pages removed
10. ✅ Site loads correctly on mobile/desktop
11. ✅ All internal links functional
12. ✅ Worklog entry created
13. ✅ README.md updated
14. ✅ GitHub issue updated

## Documentation Requirements

### Worklog Entry
Create entry in `docs/worklog/2026-02-27-frontend-redesign.md`:
- Date and duration
- Summary of changes
- Pages created/modified/deleted
- Challenges encountered
- Final outcome

### README.md Update
Update project README with:
- New page structure
- Navigation changes
- Design system notes (colors, width)
- Screenshots if applicable

### GitHub Issue
Update relevant issue(s) with:
- Completion status
- Link to design doc
- Link to worklog
- Screenshots/demo link

## Implementation Plan

**Phase 1: Foundation** (sequential, ~5 min)
1. Update theme.json (width + colors)
2. Install Getwid plugin via WP-CLI

**Phase 2: Content Creation** (parallel, 6-8 agents, ~20-30 min)
- Agent A: Redesign Homepage
- Agent B: Create Widgets Overview
- Agent C: Update 5 demo pages
- Agent D: Create Getting Started + Integration Guide
- Agent E: Create Real-world Layouts + FAQ
- Agent F: Create Changelog + finalize Privacy Policy

**Phase 3: Navigation & Cleanup** (parallel, 2-3 agents, ~10 min)
- Agent G: Configure mega menu
- Agent H: Delete unnecessary pages
- Agent I: Migrate REST API docs content

**Phase 4: Documentation** (sequential, ~10 min)
- Write worklog entry
- Update README.md
- Update GitHub issue
- Final verification

## Risks & Mitigations

**Risk:** Multi-agent work may cause style inconsistencies
**Mitigation:** Clear color/layout guidelines in this doc; final review pass

**Risk:** Page deletion may break existing links
**Mitigation:** 301 redirects for important pages; verify all internal links

**Risk:** Getwid plugin conflicts with theme/other plugins
**Mitigation:** Test in staging environment first; have rollback plan

**Risk:** Container width change affects existing custom CSS
**Mitigation:** Review plugin CSS, test all widgets after change

## Approval

✅ **Design approved by:** Dmytro Kishkin
✅ **Date:** 2026-02-27
✅ **Ready for implementation:** Yes

---

**Next Step:** Transition to writing-plans skill for detailed implementation plan.
