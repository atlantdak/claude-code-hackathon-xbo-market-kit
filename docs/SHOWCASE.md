# XBO Market Kit — Visual Showcase

**Live Demo:** [https://kishkin.dev](https://kishkin.dev) | **GitHub:** [atlantdak/claude-code-hackathon-xbo-market-kit](https://github.com/atlantdak/claude-code-hackathon-xbo-market-kit)

**Version:** 1.0.0 | Screenshots taken at 1728x1117 (MacBook 16" viewport).

---

## Site Pages

### Home Page
Full showcase landing page with live widgets, statistics, quick start guide, and documentation links.
![Home Page](screenshots/frontend-home.png)

### Showcase
Featured integration examples with all 5 widgets in action.
![Showcase](screenshots/frontend-showcase.png)

### Block Demos
All Gutenberg blocks demonstrated with configuration options and shortcode examples.
![Block Demos](screenshots/frontend-block-demos.png)

### Widgets Overview
Comparison of all 5 widgets with features and use cases.
![Widgets Overview](screenshots/frontend-widgets-overview.png)

---

## Widget Demo Pages

### Live Ticker
Real-time cryptocurrency prices with 24-hour changes and mini sparkline charts.
![Ticker Frontend](screenshots/frontend-ticker.png)

### Top Movers
Biggest gainers and losers by 24-hour percentage change across 280+ trading pairs.
![Movers Frontend](screenshots/frontend-movers.png)

### Order Book
Live bid/ask depth with spread visualization for any trading pair.
![Orderbook Frontend](screenshots/frontend-orderbook.png)

### Recent Trades
Live feed of executed trades with price, amount, and buy/sell direction.
![Trades Frontend](screenshots/frontend-trades.png)

### Slippage Calculator
Calculate average execution price and slippage based on real-time order book depth.
![Slippage Frontend](screenshots/frontend-slippage.png)

---

## Documentation Pages

### Getting Started
Quick start guide: install, configure, add your first widget in minutes.
![Getting Started](screenshots/frontend-getting-started.png)

### API Documentation
REST API reference with endpoints, parameters, and live API explorer.
![API Docs](screenshots/frontend-api-docs.png)

### Integration Guide
Custom theme integration, shortcode reference, CSS customization, REST API usage.
![Integration Guide](screenshots/frontend-integration-guide.png)

### Real-world Layouts
Production-ready layout templates: news blog, education page, portfolio, dashboard.
![Real-world Layouts](screenshots/frontend-real-world-layouts.png)

### FAQ
15 frequently asked questions across 4 categories with expandable answers.
![FAQ](screenshots/frontend-faq.png)

### Changelog
Version history, compatibility matrix, browser support, and roadmap.
![Changelog](screenshots/frontend-changelog.png)

---

## Gutenberg Block Editor

Each block provides InspectorControls (sidebar settings panel) with live ServerSideRender preview. Trading pair selection uses searchable dropdowns powered by the `/xbo/v1/trading-pairs` REST endpoint (280 pairs).

### Ticker Block
Settings: Trading Pairs (multi-select), Refresh Interval (5-60s slider), Columns (1-4).
![Ticker Editor](screenshots/editor-ticker.png)

### Top Movers Block
Settings: Count (5-20), Refresh Interval (5-60s slider).
![Movers Editor](screenshots/editor-movers.png)

### Order Book Block
Settings: Trading Pair (searchable dropdown), Depth (10-50), Refresh Interval.
![Orderbook Editor](screenshots/editor-orderbook.png)

### Recent Trades Block
Settings: Trading Pair (searchable dropdown), Limit (10-50), Refresh Interval.
![Trades Editor](screenshots/editor-trades.png)

### Slippage Calculator Block
Settings: Trading Pair (searchable dropdown), Default Amount, Refresh Interval.
![Slippage Editor](screenshots/editor-slippage.png)

---

## WordPress Admin

### Plugin Settings
Settings > XBO Market Kit: Default trading pairs configuration and cache management.
![Admin Settings](screenshots/admin-settings.png)

### Pages Management
16 published pages organized in a hierarchical structure with Block Demos as parent for 5 widget demo pages.
![Admin Pages](screenshots/admin-pages.png)

### Installed Plugins
XBO Market Kit + supporting plugins (Getwid, Getwid MegaMenu, Breadcrumb NavXT, SVG Support, LiteSpeed Cache).
![Admin Plugins](screenshots/admin-plugins.png)
