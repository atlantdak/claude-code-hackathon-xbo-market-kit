# Cache-Timer Synchronization Worklog

## 2026-02-27

### Goal
Synchronize backend cache TTL with frontend refresh intervals.

### Implementation Summary

**Backend Changes:**
- Added `XBO_MARKET_KIT_REFRESH_INTERVAL` constant (15 seconds)
- Added `xbo_market_kit_get_refresh_interval()` helper with filter support
- Simplified CacheManager by removing TTL multiplier logic
- Updated ApiClient to use refresh interval for widget caches
- Removed cache_mode setting from admin

**Frontend Changes:**
- Updated all block.json defaults to refresh=15
- Added refresh attribute to movers block
- Updated movers.js to use configurable refresh interval
- Verified all interactivity stores use 15s fallback
- Updated all shortcodes to default refresh=15

**Content Updates:**
- Added Refresh Timer block to 5 documentation pages (Showcase, Ticker, Movers, Orderbook, Trades)
- Updated parameter tables to reflect 15s default
- Added missing refresh parameter to Movers documentation

**Testing:**
- All unit tests pass (86 tests, 880 assertions)
- PHPStan level 6 clean
- PHPCS clean
- No risky test warnings

### Files Modified
- `xbo-market-kit.php` - constant and helper
- `includes/Cache/CacheManager.php` - removed multiplier
- `includes/Api/ApiClient.php` - use refresh interval
- `includes/Admin/AdminSettings.php` - removed cache_mode
- `includes/Admin/PageManager.php` - added timers, updated docs
- `src/blocks/*/block.json` - updated defaults (4 blocks)
- `assets/js/interactivity/*.js` - standardized refresh (4 stores)
- `includes/Shortcodes/*.php` - updated defaults (4 shortcodes)
- Test files updated

### Commits
1. `5610552` - feat: add centralized refresh interval constant and helper
2. `74f6ab1` - refactor: simplify CacheManager by removing TTL multiplier
3. `512569b` - feat: use centralized refresh interval in ApiClient
4. `c4d91b0` - fix: address code review findings and remove cache_mode
5. `02eb0bb` - feat: standardize refresh interval to 15s in all blocks
6. `a707f7f` - fix: update movers.js to use configurable refresh interval
7. `1cb579a` - feat: update shortcode refresh defaults to 15s
8. `53c5089` - feat: add Refresh Timer block to all documentation pages
9. `79e97da` - docs: update parameter tables to reflect 15s refresh default

### Results
- ✅ Backend cache TTL synchronized at 15 seconds for all live widgets
- ✅ Frontend refresh intervals synchronized at 15 seconds
- ✅ Centralized management via constant + filter
- ✅ All live widgets (ticker, orderbook, trades, movers) now consistent
- ✅ Documentation updated to reflect changes
- ✅ All tests passing

### Next Steps
- Manual integration testing on local site
- Monitor performance in production
- Consider WebSocket alternative for real-time updates
- Evaluate user feedback on 15s interval
