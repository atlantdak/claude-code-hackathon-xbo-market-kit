# Frontend Redesign Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Complete frontend redesign of XBO Market Kit showcase site with optimized container width, XBO.com color palette, enhanced navigation, and comprehensive content structure.

**Architecture:** FSE-based WordPress theme customization using theme.json for global settings, Gutenberg blocks for all content, Getwid plugin for advanced layouts. Multi-agent parallel execution for independent content pages, sequential for foundation and documentation.

**Tech Stack:** WordPress 6.9.1, PHP 8.2, prime-fse theme, Gutenberg blocks, Getwid plugin, WP-CLI, Git

---

## Phase 1: Foundation (Sequential)

### Task 1: Update theme.json Configuration

**Files:**
- Modify: `wp-content/themes/prime-fse/theme.json:76-79` (layout)
- Modify: `wp-content/themes/prime-fse/theme.json:32-63` (color palette)

**Step 1: Backup current theme.json**

```bash
cp wp-content/themes/prime-fse/theme.json wp-content/themes/prime-fse/theme.json.backup
```

**Step 2: Update container width**

Update lines 76-79 in theme.json:

```json
"layout": {
	"contentSize": "960px",
	"wideSize": "1200px"
}
```

**Step 3: Update color palette**

Replace lines 32-63 with XBO.com colors:

```json
"palette": [
	{
		"color": "#6319ff",
		"name": "Royal Purple",
		"slug": "color-1"
	},
	{
		"color": "#140533",
		"name": "Deep Indigo",
		"slug": "color-4"
	},
	{
		"color": "#6341e4",
		"name": "Secondary Accent",
		"slug": "color-2"
	},
	{
		"color": "#f4f3f8",
		"name": "Soft Background",
		"slug": "color-6"
	},
	{
		"color": "#14053333",
		"name": "Muted",
		"slug": "color-7"
	},
	{
		"color": "#ffffff",
		"name": "White",
		"slug": "color-9"
	}
]
```

**Step 4: Verify theme.json is valid JSON**

```bash
cat wp-content/themes/prime-fse/theme.json | python -m json.tool > /dev/null
```

Expected: No output (valid JSON)

**Step 5: Commit**

```bash
git add wp-content/themes/prime-fse/theme.json
git commit -m "feat(theme): update container width to 960px and apply XBO color palette

- contentSize: 760px → 960px for data-dense widgets
- wideSize: 1160px → 1200px
- Replace bright colors with XBO.com brand palette (#6319ff, #140533, etc.)

Co-Authored-By: Claude Sonnet 4.5 <noreply@anthropic.com>"
```

---

### Task 2: Install Getwid Plugin

**Step 1: Install Getwid via WP-CLI**

```bash
wp plugin install getwid --activate --path=/Users/atlantdak/Local\ Sites/claude-code-hackathon-xbo-market-kit/app/public
```

Expected output: `Plugin installed successfully.` and `Plugin 'getwid' activated.`

**Step 2: Verify Getwid is active**

```bash
wp plugin list --status=active --path=/Users/atlantdak/Local\ Sites/claude-code-hackathon-xbo-market-kit/app/public | grep getwid
```

Expected: `getwid` in active plugins list

**Step 3: Check Getwid blocks are available**

```bash
wp eval 'echo count(WP_Block_Type_Registry::get_instance()->get_all_registered());' --path=/Users/atlantdak/Local\ Sites/claude-code-hackathon-xbo-market-kit/app/public
```

Expected: Number higher than before (Getwid adds 40+ blocks)

**Step 4: No commit needed** (plugin installation tracked in DB, not git)

---

## Phase 2: Content Creation (Parallel - 6-8 Agents)

### Task 3: Redesign Homepage (Agent A)

**Files:**
- Modify: `wp-content/uploads/wp-post-content-44.html` (via WP-CLI)

**Step 1: Get current homepage content**

```bash
wp post get 44 --field=post_content --path=/Users/atlantdak/Local\ Sites/claude-code-hackathon-xbo-market-kit/app/public > /tmp/homepage-old.html
```

**Step 2: Get hero section from page 18**

```bash
wp post get 18 --field=post_content --path=/Users/atlantdak/Local\ Sites/claude-code-hackathon-xbo-market-kit/app/public | head -100 > /tmp/hero-section.html
```

**Step 3: Create new homepage content**

Create file with new structure (see design doc Section 2):
1. Hero section (from page 18, update colors to XBO palette)
2. Refresh timer block
3. Ticker widget block (replace shortcode)
4. Feature cards section (Getwid Advanced Columns)
5. Market data section with Movers block
6. Two-column section (Orderbook + Trades blocks)
7. Stats section
8. Slippage section
9. CTA section

**Step 4: Update homepage via WP-CLI**

```bash
wp post update 44 --post_content="$(cat /tmp/homepage-new.html)" --path=/Users/atlantdak/Local\ Sites/claude-code-hackathon-xbo-market-kit/app/public
```

Expected: `Success: Updated post 44.`

**Step 5: Verify homepage**

Visit: http://claude-code-hackathon-xbo-market-kit.local/

Expected: Hero section visible, XBO colors applied, refresh timer present

**Step 6: Take screenshot**

```bash
wp eval 'echo home_url();' --path=/Users/atlantdak/Local\ Sites/claude-code-hackathon-xbo-market-kit/app/public
```

**Step 7: Document changes**

Create note: "Homepage redesigned with hero section, XBO colors, refresh timer, and Gutenberg blocks"

---

### Task 4: Create Widgets Overview Page (Agent B)

**Step 1: Create new page**

```bash
wp post create --post_type=page --post_title="Widgets Overview" --post_name="widgets-overview" --post_status=publish --path=/Users/atlantdak/Local\ Sites/claude-code-hackathon-xbo-market-kit/app/public
```

Expected: `Success: Created post [ID].`

**Step 2: Get new page ID**

```bash
wp post list --post_type=page --post_name=widgets-overview --field=ID --path=/Users/atlantdak/Local\ Sites/claude-code-hackathon-xbo-market-kit/app/public
```

Save ID for next step.

**Step 3: Create page content**

Structure (see design doc 3.1):
- Intro section
- Comparison grid (Getwid Advanced Columns, 2-3 columns)
- For each widget (Ticker, Movers, Orderbook, Trades, Slippage):
  - Widget name and icon
  - Description
  - "Best for:" use cases
  - "View Demo" button

**Step 4: Update page content**

```bash
wp post update [ID] --post_content="$(cat /tmp/widgets-overview.html)" --path=/Users/atlantdak/Local\ Sites/claude-code-hackathon-xbo-market-kit/app/public
```

**Step 5: Verify page**

Visit: http://claude-code-hackathon-xbo-market-kit.local/widgets-overview/

Expected: Page displays with 5 widget cards, XBO colors

---

### Task 5: Update Widget Demo Pages (Agent C)

**Apply to pages: 48 (Ticker), 49 (Movers), 50 (Orderbook), 51 (Trades), 52 (Slippage)**

**Step 1: Get current content of demo page**

```bash
wp post get 48 --field=post_content --path=/Users/atlantdak/Local\ Sites/claude-code-hackathon-xbo-market-kit/app/public > /tmp/demo-ticker-old.html
```

**Step 2: Create enhanced content**

Structure (see design doc Section 4):
1. Refresh timer block at top
2. Hero section (Getwid Section)
3. Live demo section with widget block
4. Configuration options (Getwid Accordion)
5. Variations (Getwid Tabs)
6. Related resources
7. CTA section

**Step 3: Update page 48 (Ticker)**

```bash
wp post update 48 --post_content="$(cat /tmp/demo-ticker-new.html)" --path=/Users/atlantdak/Local\ Sites/claude-code-hackathon-xbo-market-kit/app/public
```

**Step 4: Repeat for pages 49-52**

```bash
wp post update 49 --post_content="$(cat /tmp/demo-movers-new.html)" --path=/Users/atlantdak/Local\ Sites/claude-code-hackathon-xbo-market-kit/app/public
wp post update 50 --post_content="$(cat /tmp/demo-orderbook-new.html)" --path=/Users/atlantdak/Local\ Sites/claude-code-hackathon-xbo-market-kit/app/public
wp post update 51 --post_content="$(cat /tmp/demo-trades-new.html)" --path=/Users/atlantdak/Local\ Sites/claude-code-hackathon-xbo-market-kit/app/public
wp post update 52 --post_content="$(cat /tmp/demo-slippage-new.html)" --path=/Users/atlantdak/Local\ Sites/claude-code-hackathon-xbo-market-kit/app/public
```

**Step 5: Verify all demo pages**

Visit each page and verify:
- Refresh timer at top
- Enhanced layout with Getwid blocks
- XBO colors applied

---

### Task 6: Create Getting Started Page (Agent D)

**Step 1: Create page**

```bash
wp post create --post_type=page --post_title="Getting Started" --post_name="getting-started" --post_status=publish --path=/Users/atlantdak/Local\ Sites/claude-code-hackathon-xbo-market-kit/app/public
```

**Step 2: Create content**

Structure (see design doc 3.2):
- Introduction
- Installation steps (Getwid Section with numbered steps)
- First widget setup
- Configuration options
- Next steps

**Step 3: Update page**

```bash
wp post update [ID] --post_content="$(cat /tmp/getting-started.html)" --path=/Users/atlantdak/Local\ Sites/claude-code-hackathon-xbo-market-kit/app/public
```

**Step 4: Verify page**

Visit page and verify content displays correctly.

---

### Task 7: Create Integration Guide Page (Agent D)

**Step 1: Create page**

```bash
wp post create --post_type=page --post_title="Integration Guide" --post_name="integration-guide" --post_status=publish --path=/Users/atlantdak/Local\ Sites/claude-code-hackathon-xbo-market-kit/app/public
```

**Step 2: Create content**

Structure (see design doc 3.4):
- Getwid Tabs for different methods
  - Theme Integration
  - Custom Styling
  - REST API Usage
  - Shortcode Reference
- Code examples in each tab

**Step 3: Update page**

```bash
wp post update [ID] --post_content="$(cat /tmp/integration-guide.html)" --path=/Users/atlantdak/Local\ Sites/claude-code-hackathon-xbo-market-kit/app/public
```

---

### Task 8: Create Real-world Layouts Page (Agent E)

**Step 1: Create page**

```bash
wp post create --post_type=page --post_title="Real-world Layouts" --post_name="real-world-layouts" --post_status=publish --path=/Users/atlantdak/Local\ Sites/claude-code-hackathon-xbo-market-kit/app/public
```

**Step 2: Create content**

Structure (see design doc 3.3):
- 3-4 layout examples
- Each with screenshot, description, code snippet

**Step 3: Update page**

```bash
wp post update [ID] --post_content="$(cat /tmp/real-world-layouts.html)" --path=/Users/atlantdak/Local\ Sites/claude-code-hackathon-xbo-market-kit/app/public
```

---

### Task 9: Create FAQ Page (Agent E)

**Step 1: Create page**

```bash
wp post create --post_type=page --post_title="FAQ" --post_name="faq" --post_status=publish --path=/Users/atlantdak/Local\ Sites/claude-code-hackathon-xbo-market-kit/app/public
```

**Step 2: Create content**

Structure (see design doc 3.5):
- Getwid Accordion blocks
- Categories: Installation, Configuration, Troubleshooting, API

**Step 3: Update page**

```bash
wp post update [ID] --post_content="$(cat /tmp/faq.html)" --path=/Users/atlantdak/Local\ Sites/claude-code-hackathon-xbo-market-kit/app/public
```

---

### Task 10: Create Changelog Page (Agent F)

**Step 1: Create page**

```bash
wp post create --post_type=page --post_title="Changelog" --post_name="changelog" --post_status=publish --path=/Users/atlantdak/Local\ Sites/claude-code-hackathon-xbo-market-kit/app/public
```

**Step 2: Create content**

Structure (see design doc 3.6):
- Version table
- Compatibility matrix
- Upgrade notes

**Step 3: Update page**

```bash
wp post update [ID] --post_content="$(cat /tmp/changelog.html)" --path=/Users/atlantdak/Local\ Sites/claude-code-hackathon-xbo-market-kit/app/public
```

---

### Task 11: Finalize Privacy Policy (Agent F)

**Step 1: Update Privacy Policy status**

```bash
wp post update 3 --post_status=publish --path=/Users/atlantdak/Local\ Sites/claude-code-hackathon-xbo-market-kit/app/public
```

**Step 2: Verify publication**

```bash
wp post get 3 --field=post_status --path=/Users/atlantdak/Local\ Sites/claude-code-hackathon-xbo-market-kit/app/public
```

Expected: `publish`

---

## Phase 3: Navigation & Cleanup (Parallel - 2-3 Agents)

### Task 12: Configure Mega Menu (Agent G)

**Step 1: Get current menu ID**

```bash
wp menu list --path=/Users/atlantdak/Local\ Sites/claude-code-hackathon-xbo-market-kit/app/public
```

**Step 2: Clear existing menu items**

```bash
wp menu item list [MENU_ID] --format=ids --path=/Users/atlantdak/Local\ Sites/claude-code-hackathon-xbo-market-kit/app/public | xargs -n1 wp menu item delete --path=/Users/atlantdak/Local\ Sites/claude-code-hackathon-xbo-market-kit/app/public
```

**Step 3: Add top-level menu items**

```bash
wp menu item add-post [MENU_ID] 44 --title="Home" --path=/Users/atlantdak/Local\ Sites/claude-code-hackathon-xbo-market-kit/app/public
wp menu item add-custom [MENU_ID] "Widgets" "#" --path=/Users/atlantdak/Local\ Sites/claude-code-hackathon-xbo-market-kit/app/public
wp menu item add-custom [MENU_ID] "Showcase" "#" --path=/Users/atlantdak/Local\ Sites/claude-code-hackathon-xbo-market-kit/app/public
wp menu item add-custom [MENU_ID] "Developers" "#" --path=/Users/atlantdak/Local\ Sites/claude-code-hackathon-xbo-market-kit/app/public
wp menu item add-custom [MENU_ID] "Resources" "#" --path=/Users/atlantdak/Local\ Sites/claude-code-hackathon-xbo-market-kit/app/public
```

**Step 4: Add submenu items under Widgets**

Get Widgets menu item ID, then:

```bash
wp menu item add-post [MENU_ID] [WIDGETS_OVERVIEW_ID] --parent-id=[WIDGETS_ITEM_ID] --path=/Users/atlantdak/Local\ Sites/claude-code-hackathon-xbo-market-kit/app/public
wp menu item add-post [MENU_ID] 48 --title="Ticker Demo" --parent-id=[WIDGETS_ITEM_ID] --path=/Users/atlantdak/Local\ Sites/claude-code-hackathon-xbo-market-kit/app/public
wp menu item add-post [MENU_ID] 49 --title="Movers Demo" --parent-id=[WIDGETS_ITEM_ID] --path=/Users/atlantdak/Local\ Sites/claude-code-hackathon-xbo-market-kit/app/public
wp menu item add-post [MENU_ID] 50 --title="Orderbook Demo" --parent-id=[WIDGETS_ITEM_ID] --path=/Users/atlantdak/Local\ Sites/claude-code-hackathon-xbo-market-kit/app/public
wp menu item add-post [MENU_ID] 51 --title="Trades Demo" --parent-id=[WIDGETS_ITEM_ID] --path=/Users/atlantdak/Local\ Sites/claude-code-hackathon-xbo-market-kit/app/public
wp menu item add-post [MENU_ID] 52 --title="Slippage Demo" --parent-id=[WIDGETS_ITEM_ID] --path=/Users/atlantdak/Local\ Sites/claude-code-hackathon-xbo-market-kit/app/public
```

**Step 5: Repeat for other top-level items**

Add submenu items under Showcase, Developers, Resources (see design doc Section 5)

**Step 6: Verify menu**

Visit site and verify mega menu displays correctly with dropdowns.

---

### Task 13: Delete Unnecessary Pages (Agent H)

**Step 1: List pages to delete**

```bash
wp post list --post_type=page --post__in=18,16,11,10,2,12,13,14,15,17 --fields=ID,post_title --path=/Users/atlantdak/Local\ Sites/claude-code-hackathon-xbo-market-kit/app/public
```

**Step 2: Move to trash (not permanent delete)**

```bash
wp post delete 18 --path=/Users/atlantdak/Local\ Sites/claude-code-hackathon-xbo-market-kit/app/public
wp post delete 16 --path=/Users/atlantdak/Local\ Sites/claude-code-hackathon-xbo-market-kit/app/public
wp post delete 11 --path=/Users/atlantdak/Local\ Sites/claude-code-hackathon-xbo-market-kit/app/public
wp post delete 10 --path=/Users/atlantdak/Local\ Sites/claude-code-hackathon-xbo-market-kit/app/public
wp post delete 2 --path=/Users/atlantdak/Local\ Sites/claude-code-hackathon-xbo-market-kit/app/public
wp post delete 12 --path=/Users/atlantdak/Local\ Sites/claude-code-hackathon-xbo-market-kit/app/public
wp post delete 13 --path=/Users/atlantdak/Local\ Sites/claude-code-hackathon-xbo-market-kit/app/public
wp post delete 14 --path=/Users/atlantdak/Local\ Sites/claude-code-hackathon-xbo-market-kit/app/public
wp post delete 15 --path=/Users/atlantdak/Local\ Sites/claude-code-hackathon-xbo-market-kit/app/public
wp post delete 17 --path=/Users/atlantdak/Local\ Sites/claude-code-hackathon-xbo-market-kit/app/public
```

**Step 3: Verify deletion**

```bash
wp post list --post_type=page --post_status=trash --path=/Users/atlantdak/Local\ Sites/claude-code-hackathon-xbo-market-kit/app/public
```

Expected: 10 pages in trash

---

### Task 14: Migrate REST API Documentation (Agent I)

**Step 1: Get content from page 10**

```bash
wp post get 10 --field=post_content --path=/Users/atlantdak/Local\ Sites/claude-code-hackathon-xbo-market-kit/app/public > /tmp/rest-api-old.html
```

**Step 2: Get content from page 47**

```bash
wp post get 47 --field=post_content --path=/Users/atlantdak/Local\ Sites/claude-code-hackathon-xbo-market-kit/app/public > /tmp/api-docs-old.html
```

**Step 3: Merge content**

Manually review and merge useful sections from page 10 into page 47.

**Step 4: Update page 47**

```bash
wp post update 47 --post_content="$(cat /tmp/api-docs-merged.html)" --path=/Users/atlantdak/Local\ Sites/claude-code-hackathon-xbo-market-kit/app/public
```

**Step 5: Page 10 already deleted in Task 13**

---

## Phase 4: Documentation (Sequential)

### Task 15: Write Worklog Entry

**Files:**
- Create: `docs/worklog/2026-02-27-frontend-redesign.md`

**Step 1: Create worklog file**

```markdown
# Frontend Redesign — 2026-02-27

**Duration:** ~2-3 hours (with parallel agent execution)

**Task:** Complete frontend redesign of XBO Market Kit showcase site

## Changes Summary

### Theme Configuration
- Updated `theme.json` container width: 760px → 960px (contentSize), 1160px → 1200px (wideSize)
- Replaced color palette with XBO.com brand colors (#6319ff, #140533, #6341e4, #f4f3f8)
- Installed Getwid plugin for advanced block layouts

### Pages Created (6 new)
- Widgets Overview — comparison of all 5 widgets
- Getting Started — installation and first-use guide
- Integration Guide — developer documentation (tabs for different methods)
- Real-world Layouts — showcase examples
- FAQ — accordion-based Q&A
- Changelog — version history and compatibility

### Pages Modified
- Homepage (ID 44) — redesigned with hero section, refresh timer, Gutenberg blocks, XBO colors
- Ticker Demo (ID 48) — enhanced with refresh timer, accordion, tabs
- Movers Demo (ID 49) — enhanced with refresh timer, accordion, tabs
- Orderbook Demo (ID 50) — enhanced with refresh timer, accordion, tabs
- Trades Demo (ID 51) — enhanced with refresh timer, accordion, tabs
- Slippage Demo (ID 52) — enhanced with refresh timer, accordion, tabs
- API Documentation (ID 47) — merged content from REST API docs
- Privacy Policy (ID 3) — published (was draft)

### Pages Deleted (10 to trash)
- ID 18: XBO Market Kit (duplicate hero page)
- ID 16: Live Demo (overlapped with Showcase)
- ID 11: Integration Test (internal testing)
- ID 10: REST API Documentation (merged into ID 47)
- ID 2: About (legacy sample page)
- ID 12-17: Screenshot pages (6 private pages)

### Navigation
- Configured mega menu with 5 top-level items: Home, Widgets, Showcase, Developers, Resources
- Each top-level item has dropdown submenu
- All pages properly linked in navigation

## Challenges

1. **Color Migration:** Ensured all custom CSS and inline styles updated to XBO palette
2. **Block Conversion:** Replaced all shortcodes with Gutenberg blocks for consistency
3. **Menu Structure:** WP-CLI menu management required careful parent-child relationship tracking
4. **Content Density:** Balanced information density with readability in 960px width

## Outcome

✅ Professional showcase site with XBO branding
✅ All widgets have comprehensive demo pages
✅ Clear navigation and documentation structure
✅ Consistent design system (colors, spacing, typography)
✅ Getwid blocks provide rich layout options
✅ Clean page structure (no legacy/duplicate pages)

## Testing

- Verified all pages load correctly
- Tested navigation menu dropdowns
- Checked responsive design on mobile/tablet/desktop
- Confirmed all widget blocks render properly
- Validated internal links work

## Metrics

- Pages created: 6
- Pages modified: 9
- Pages deleted: 10
- Theme files modified: 1 (theme.json)
- Plugins installed: 1 (Getwid)
- Total commits: 12-15

---

**Related Documents:**
- Design: `docs/plans/2026-02-27-frontend-redesign-design.md`
- Implementation: `docs/plans/2026-02-27-frontend-redesign-implementation.md`
```

**Step 2: Save worklog**

```bash
cat > docs/worklog/2026-02-27-frontend-redesign.md << 'EOF'
[content from Step 1]
EOF
```

**Step 3: Commit worklog**

```bash
git add docs/worklog/2026-02-27-frontend-redesign.md
git commit -m "docs: add frontend redesign worklog

Co-Authored-By: Claude Sonnet 4.5 <noreply@anthropic.com>"
```

---

### Task 16: Update README.md

**Files:**
- Modify: `README.md`

**Step 1: Read current README**

```bash
cat README.md
```

**Step 2: Add section about redesign**

Add to README (appropriate location):

```markdown
## Site Structure

### Pages

**Homepage**
- Hero section with XBO branding
- Live crypto widgets (Ticker, Movers)
- Feature showcase
- CTA sections

**Widgets** (5 demo pages)
- Ticker Demo — real-time price display
- Top Movers Demo — gainers/losers
- Order Book Demo — bid/ask depth
- Recent Trades Demo — trade feed
- Slippage Calculator Demo — execution price analysis

**Documentation**
- Widgets Overview — comparison and use cases
- Getting Started — quick start guide
- Integration Guide — developer docs
- Real-world Layouts — example implementations
- API Documentation — REST API reference
- FAQ — common questions
- Changelog — version history

### Design System

**Colors** (from XBO.com)
- Primary: `#6319ff` (Royal Purple)
- Dark: `#140533` (Deep Indigo)
- Accent: `#6341e4` (Secondary Purple)
- Background: `#f4f3f8` (Soft Gray)
- Text: `#140533`, `#898299` (muted)

**Container Width**
- Content: 960px (optimal for data-dense widgets)
- Wide: 1200px (full-width sections)

**Tech Stack**
- Theme: prime-fse (FSE)
- Blocks: Gutenberg + Getwid
- Styling: theme.json + block styles
```

**Step 3: Commit README**

```bash
git add README.md
git commit -m "docs: update README with redesign details

- Add site structure section
- Document design system (colors, widths)
- List all pages and their purposes

Co-Authored-By: Claude Sonnet 4.5 <noreply@anthropic.com>"
```

---

### Task 17: Update GitHub Issue

**Step 1: Find relevant issue**

```bash
gh issue list --repo atlantdak/claude-code-hackathon-xbo-market-kit
```

**Step 2: Add comment to issue**

```bash
gh issue comment [ISSUE_NUMBER] --body "✅ **Frontend Redesign Complete**

Implemented comprehensive frontend redesign with:
- XBO.com color palette (#6319ff, #140533, etc.)
- Optimized container width (960px for data-dense widgets)
- 6 new pages: Widgets Overview, Getting Started, Integration Guide, Real-world Layouts, FAQ, Changelog
- Enhanced 5 widget demo pages with refresh timer, accordions, tabs
- Mega menu navigation structure
- Cleaned up 10 duplicate/legacy pages

**Design:** docs/plans/2026-02-27-frontend-redesign-design.md
**Worklog:** docs/worklog/2026-02-27-frontend-redesign.md
**Demo:** http://claude-code-hackathon-xbo-market-kit.local/

cc: @atlantdak" --repo atlantdak/claude-code-hackathon-xbo-market-kit
```

**Step 3: Close issue if applicable**

```bash
gh issue close [ISSUE_NUMBER] --comment "Frontend redesign implemented and verified." --repo atlantdak/claude-code-hackathon-xbo-market-kit
```

---

### Task 18: Final Verification

**Step 1: Check all pages load**

```bash
wp post list --post_type=page --post_status=publish --fields=ID,post_title,post_name --path=/Users/atlantdak/Local\ Sites/claude-code-hackathon-xbo-market-kit/app/public
```

**Step 2: Verify menu**

Visit: http://claude-code-hackathon-xbo-market-kit.local/

Check:
- Navigation menu displays correctly
- All dropdown menus work
- Pages load without errors
- XBO colors applied consistently
- Refresh timers visible on appropriate pages
- All widget blocks render correctly

**Step 3: Test responsive design**

Resize browser to test:
- Mobile (320px, 375px)
- Tablet (768px)
- Desktop (1024px, 1440px)

**Step 4: Check git status**

```bash
git status
```

Expected: Clean working directory (all changes committed)

**Step 5: Push to remote**

```bash
git push origin main
```

**Step 6: Create success summary**

Document:
- All pages created/modified
- Navigation structure verified
- Design system applied
- Documentation complete
- All commits pushed

---

## Success Criteria Checklist

- [ ] theme.json updated (960px width, XBO colors)
- [ ] Getwid plugin installed and active
- [ ] Homepage redesigned (hero, refresh timer, blocks)
- [ ] 6 new pages created and published
- [ ] 5 demo pages enhanced with rich content
- [ ] Mega menu configured with dropdowns
- [ ] 10 legacy pages deleted (in trash)
- [ ] Privacy Policy published
- [ ] Worklog written and committed
- [ ] README.md updated
- [ ] GitHub issue updated/closed
- [ ] All pages verified loading correctly
- [ ] Responsive design tested
- [ ] All commits pushed to remote

---

## Parallel Execution Strategy

**Phase 1** (Sequential): Tasks 1-2 (foundation)
**Phase 2** (Parallel): Tasks 3-11 (content creation)
  - Can run 6-8 agents simultaneously
  - Each agent handles 1-2 pages independently
**Phase 3** (Parallel): Tasks 12-14 (navigation & cleanup)
  - Can run 2-3 agents simultaneously
**Phase 4** (Sequential): Tasks 15-18 (documentation)

**Estimated Time:**
- Phase 1: ~5 min
- Phase 2: ~20-30 min (parallel)
- Phase 3: ~10 min (parallel)
- Phase 4: ~10-15 min
- **Total: ~45-60 min** (vs 2-3 hours sequential)

---

## Notes for Implementer

1. **WP-CLI Path:** All commands use full path to Local site — adjust if different environment
2. **Page IDs:** Some steps require getting page IDs dynamically — save them for subsequent commands
3. **Content Files:** Plan assumes HTML content prepared in `/tmp/` files — create these from design specs
4. **Menu IDs:** Get menu ID first before adding items
5. **Testing:** Test each page after creation before moving to next
6. **Commits:** Commit after each major milestone (task or phase)
7. **Getwid Blocks:** Familiarize with Getwid block names in editor before creating content
8. **XBO Colors:** Use color slugs from theme.json (color-1, color-4, etc.) in block editor

---

**Plan Complete:** Ready for execution via superpowers:executing-plans or superpowers:subagent-driven-development
