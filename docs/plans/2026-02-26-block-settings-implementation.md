# Block Settings: Trading Pair Selector — Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Replace manual TextControl pair input in Gutenberg blocks with searchable dropdowns backed by the live XBO API pair list.

**Architecture:** New `GET /xbo/v1/trading-pairs` REST endpoint returns a flat sorted array of all pair symbols in SLASH format. A shared `useTradingPairs()` React hook fetches pairs via `apiFetch`. Two thin React components (`PairSelector` using `ComboboxControl`, `PairsSelector` using `FormTokenField`) wrap the hook and slot into each block's `InspectorControls`. All block attributes are normalized to SLASH format; PHP `ApiClient` conversion methods already handle downstream format needs.

**Tech Stack:** PHP 8.2 / WordPress 6.9.1 / WP REST API / `@wordpress/scripts` (webpack) / `@wordpress/components` (ComboboxControl, FormTokenField) / `@wordpress/api-fetch` / PHPUnit 11

---

## Key Facts for Implementor

- **Plugin dir:** `wp-content/plugins/xbo-market-kit/`
- **Run all commands from the plugin dir** (not the WordPress root)
- **Build JS:** `npm run build` (builds `src/blocks/` → `build/blocks/`)
- **Dev JS:** `npm start` (watch mode)
- **PHP tests:** `composer run test`
- **PHP lint:** `composer run phpcs` / `composer run phpcbf`
- **PHP static:** `composer run phpstan`
- `ApiClient::get_trading_pairs()` already exists, caches 6h with key `xbo_mk_pairs`
- `ApiClient::get_orderbook()` internally calls `to_underscore_format()` — **no PHP render changes needed**
- `ApiClient::get_trades()` internally calls `to_slash_format()` — **no PHP render changes needed**
- The only PHP render that needs a format check is Slippage (passes symbol to `get_orderbook` via `SlippageController`) — already handled
- `@wordpress/api-fetch` is a global WP script available via import in any `editorScript`

---

## Task 1: TradingPairsController — PHP REST Endpoint

**Files:**
- Create: `includes/Rest/TradingPairsController.php`
- Create: `tests/Unit/Rest/TradingPairsControllerTest.php`
- Modify: `includes/Plugin.php` (register the controller)

### Step 1: Write the failing test

Create `tests/Unit/Rest/TradingPairsControllerTest.php`:

```php
<?php
declare(strict_types=1);

namespace XboMarketKit\Tests\Unit\Rest;

use PHPUnit\Framework\TestCase;
use XboMarketKit\Rest\TradingPairsController;

class TradingPairsControllerTest extends TestCase {

    public function test_extract_symbols_returns_sorted_slash_symbols(): void {
        $data = [
            [ 'symbol' => 'ETH/USDT', 'baseAsset' => 'ETH' ],
            [ 'symbol' => 'BTC/USDT', 'baseAsset' => 'BTC' ],
            [ 'symbol' => 'SOL/USDT', 'baseAsset' => 'SOL' ],
        ];
        $result = TradingPairsController::extract_symbols( $data );
        $this->assertSame( [ 'BTC/USDT', 'ETH/USDT', 'SOL/USDT' ], $result );
    }

    public function test_extract_symbols_skips_empty_symbol(): void {
        $data = [
            [ 'symbol' => 'BTC/USDT' ],
            [ 'baseAsset' => 'ETH' ],       // no symbol key
            [ 'symbol' => '' ],              // empty symbol
        ];
        $result = TradingPairsController::extract_symbols( $data );
        $this->assertSame( [ 'BTC/USDT' ], $result );
    }

    public function test_extract_symbols_empty_data(): void {
        $result = TradingPairsController::extract_symbols( [] );
        $this->assertSame( [], $result );
    }
}
```

### Step 2: Run test to verify it fails

```bash
composer run test -- --filter TradingPairsControllerTest
```
Expected output: `Error: Class "XboMarketKit\Rest\TradingPairsController" not found`

### Step 3: Create TradingPairsController

Create `includes/Rest/TradingPairsController.php`:

```php
<?php
/**
 * TradingPairsController class file.
 *
 * @package XboMarketKit
 */

declare(strict_types=1);

namespace XboMarketKit\Rest;

use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * REST controller for the trading pairs list endpoint.
 *
 * Returns a sorted flat array of all available trading pair symbols
 * in slash format (e.g. BTC/USDT) for use by block editor controls.
 */
class TradingPairsController extends AbstractController {

    /**
     * Constructor. Sets the REST base path.
     */
    public function __construct() {
        $this->rest_base = 'trading-pairs';
    }

    /**
     * Register REST API routes.
     *
     * @return void
     */
    public function register_routes(): void {
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base,
            array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array( $this, 'get_items' ),
                    'permission_callback' => '__return_true',
                ),
            )
        );
    }

    /**
     * Return all trading pair symbols as a sorted flat array.
     *
     * @param WP_REST_Request $request Full details about the request.
     * @return WP_REST_Response REST response with symbol list.
     */
    public function get_items( $request ): WP_REST_Response {
        $api_response = $this->get_api_client()->get_trading_pairs();
        if ( ! $api_response->success ) {
            return $this->error_response( $api_response->error_message );
        }

        $symbols = self::extract_symbols( $api_response->data );
        return $this->success_response( $symbols );
    }

    /**
     * Extract and sort symbol strings from API trading-pairs data.
     *
     * Static to allow unit testing without mocking WP globals.
     *
     * @param array $data Raw trading pairs data from ApiClient.
     * @return string[] Sorted array of SLASH-format symbols.
     */
    public static function extract_symbols( array $data ): array {
        $symbols = [];
        foreach ( $data as $pair ) {
            $symbol = $pair['symbol'] ?? '';
            if ( '' !== $symbol ) {
                $symbols[] = $symbol;
            }
        }
        sort( $symbols );
        return $symbols;
    }
}
```

### Step 4: Run test to verify it passes

```bash
composer run test -- --filter TradingPairsControllerTest
```
Expected output: `OK (3 tests, 3 assertions)`

### Step 5: Register controller in Plugin.php

Open `includes/Plugin.php`, find `register_rest_routes()` method (line ~94).

Change:
```php
    public function register_rest_routes(): void {
        $controllers = array(
            new Rest\TickerController(),
            new Rest\MoversController(),
            new Rest\OrderbookController(),
            new Rest\TradesController(),
            new Rest\SlippageController(),
        );
```

To:
```php
    public function register_rest_routes(): void {
        $controllers = array(
            new Rest\TickerController(),
            new Rest\MoversController(),
            new Rest\OrderbookController(),
            new Rest\TradesController(),
            new Rest\SlippageController(),
            new Rest\TradingPairsController(),
        );
```

### Step 6: Run all tests to verify no regressions

```bash
composer run test
```
Expected: all existing tests pass + 3 new tests pass.

### Step 7: Run phpcs and phpstan

```bash
composer run phpcs
composer run phpstan
```
Expected: no errors.

### Step 8: Commit

```bash
git add includes/Rest/TradingPairsController.php tests/Unit/Rest/TradingPairsControllerTest.php includes/Plugin.php
git commit -m "feat(rest): add TradingPairsController endpoint GET /xbo/v1/trading-pairs"
```

---

## Task 2: Verify REST Endpoint Works

**Files:** (read only)

### Step 1: Confirm endpoint responds

Visit in browser (or curl):
```
http://claude-code-hackathon-xbo-market-kit.local/wp-json/xbo/v1/trading-pairs
```

Expected: JSON array starting with `["BTC/USDT","ETH/USDT",...]`

If you get a 404, flush rewrite rules:
```bash
wp rewrite flush --hard --path="/Users/atlantdak/Local Sites/claude-code-hackathon-xbo-market-kit/app/public"
```

---

## Task 3: Create useTradingPairs Hook

**Files:**
- Create: `src/blocks/shared/useTradingPairs.js`

### Step 1: Create the hook

Create `src/blocks/shared/useTradingPairs.js`:

```js
/**
 * Custom hook to fetch trading pairs from the XBO REST API.
 *
 * Returns pairs as a sorted flat array of SLASH-format symbols.
 * Results are cached for the lifetime of the editor session.
 */
import { useState, useEffect } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

let cachedPairs = null;

export default function useTradingPairs() {
	const [ pairs, setPairs ] = useState( cachedPairs ?? [] );
	const [ loading, setLoading ] = useState( null === cachedPairs );
	const [ error, setError ] = useState( null );

	useEffect( () => {
		if ( null !== cachedPairs ) {
			return;
		}
		apiFetch( { path: '/xbo/v1/trading-pairs' } )
			.then( ( data ) => {
				cachedPairs = data;
				setPairs( data );
				setLoading( false );
			} )
			.catch( ( err ) => {
				setError( err.message ?? 'Failed to load trading pairs.' );
				setLoading( false );
			} );
	}, [] );

	return { pairs, loading, error };
}
```

**Note:** The module-level `cachedPairs` variable caches across all block instances in the same editor session — no duplicate network requests.

### Step 2: No PHP tests needed for JS. Verify by importing in Task 4.

---

## Task 4: Create PairSelector Component

**Files:**
- Create: `src/blocks/shared/PairSelector.js`

### Step 1: Create PairSelector

Create `src/blocks/shared/PairSelector.js`:

```js
/**
 * PairSelector component.
 *
 * Renders a searchable ComboboxControl populated with all available
 * XBO trading pairs. Used in blocks that need a single pair setting.
 */
import { ComboboxControl, Spinner } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import useTradingPairs from './useTradingPairs';

/**
 * @param {Object}   props
 * @param {string}   props.value       Current symbol value (e.g. "BTC/USDT").
 * @param {Function} props.onChange    Called with new symbol string on change.
 * @param {string}   [props.label]     Control label. Defaults to "Trading Pair".
 */
export default function PairSelector( { value, onChange, label } ) {
	const { pairs, loading } = useTradingPairs();

	const options = pairs.map( ( symbol ) => ( {
		value: symbol,
		label: symbol,
	} ) );

	if ( loading ) {
		return <Spinner />;
	}

	return (
		<ComboboxControl
			label={ label ?? __( 'Trading Pair', 'xbo-market-kit' ) }
			value={ value }
			options={ options }
			onChange={ ( val ) => onChange( val ?? value ) }
			__next40pxDefaultSize
			__nextHasNoMarginBottom
		/>
	);
}
```

---

## Task 5: Create PairsSelector Component

**Files:**
- Create: `src/blocks/shared/PairsSelector.js`

### Step 1: Create PairsSelector

Create `src/blocks/shared/PairsSelector.js`:

```js
/**
 * PairsSelector component.
 *
 * Renders a FormTokenField for selecting multiple trading pairs.
 * Used by the Ticker block which supports a comma-separated list.
 *
 * Storage format: comma-separated string "BTC/USDT,ETH/USDT"
 */
import { FormTokenField } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import useTradingPairs from './useTradingPairs';

/**
 * @param {Object}   props
 * @param {string}   props.value     Comma-separated symbols string.
 * @param {Function} props.onChange  Called with new comma-separated string.
 * @param {string}   [props.label]   Control label.
 */
export default function PairsSelector( { value, onChange, label } ) {
	const { pairs } = useTradingPairs();

	// Convert storage string to token array for FormTokenField.
	const tokens = value
		? value.split( ',' ).map( ( s ) => s.trim() ).filter( Boolean )
		: [];

	const handleChange = ( newTokens ) => {
		// Validate each token against known pairs (case-insensitive).
		const upperPairs = pairs.map( ( p ) => p.toUpperCase() );
		const valid = newTokens.filter( ( t ) =>
			upperPairs.includes( t.toUpperCase() )
		);
		onChange( valid.join( ',' ) );
	};

	return (
		<FormTokenField
			label={ label ?? __( 'Trading Pairs', 'xbo-market-kit' ) }
			value={ tokens }
			suggestions={ pairs }
			onChange={ handleChange }
			__next40pxDefaultSize
			__nextHasNoMarginBottom
			__experimentalExpandOnFocus
		/>
	);
}
```

---

## Task 6: Update block.json Defaults

**Files:**
- Modify: `src/blocks/orderbook/block.json`
- Modify: `src/blocks/slippage/block.json`

### Step 1: Fix OrderBook default

In `src/blocks/orderbook/block.json`, change:
```json
"symbol": { "type": "string", "default": "BTC_USDT" },
```
To:
```json
"symbol": { "type": "string", "default": "BTC/USDT" },
```

### Step 2: Fix Slippage default

In `src/blocks/slippage/block.json`, change:
```json
"symbol": { "type": "string", "default": "BTC_USDT" },
```
To:
```json
"symbol": { "type": "string", "default": "BTC/USDT" },
```

### Step 3: Commit

```bash
git add src/blocks/orderbook/block.json src/blocks/slippage/block.json
git commit -m "fix(blocks): normalize symbol defaults to SLASH format (BTC/USDT)"
```

---

## Task 7: Update Ticker Block

**Files:**
- Modify: `src/blocks/ticker/index.js`

### Step 1: Update ticker/index.js

Replace the full contents of `src/blocks/ticker/index.js`:

```js
import { registerBlockType } from '@wordpress/blocks';
import { RangeControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import metadata from './block.json';
import XboBlockEdit from '../shared/XboBlockEdit';
import PairsSelector from '../shared/PairsSelector';

registerBlockType( metadata.name, {
	edit: ( { attributes, setAttributes } ) => {
		const { symbols, refresh, columns } = attributes;

		return (
			<XboBlockEdit
				blockName={ metadata.name }
				attributes={ attributes }
				setAttributes={ setAttributes }
				title={ __( 'XBO Ticker', 'xbo-market-kit' ) }
				icon="chart-line"
				inspectorControls={
					<>
						<PairsSelector
							value={ symbols }
							onChange={ ( val ) =>
								setAttributes( { symbols: val } )
							}
						/>
						<RangeControl
							label={ __(
								'Refresh Interval (seconds)',
								'xbo-market-kit'
							) }
							value={ parseInt( refresh, 10 ) }
							onChange={ ( val ) =>
								setAttributes( { refresh: String( val ) } )
							}
							min={ 5 }
							max={ 60 }
							__next40pxDefaultSize
							__nextHasNoMarginBottom
						/>
						<RangeControl
							label={ __( 'Columns', 'xbo-market-kit' ) }
							value={ parseInt( columns, 10 ) }
							onChange={ ( val ) =>
								setAttributes( { columns: String( val ) } )
							}
							min={ 1 }
							max={ 4 }
							__next40pxDefaultSize
							__nextHasNoMarginBottom
						/>
					</>
				}
			/>
		);
	},
} );
```

---

## Task 8: Update OrderBook Block

**Files:**
- Modify: `src/blocks/orderbook/index.js`

### Step 1: Update orderbook/index.js

Replace the full contents of `src/blocks/orderbook/index.js`:

```js
import { registerBlockType } from '@wordpress/blocks';
import { RangeControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import metadata from './block.json';
import XboBlockEdit from '../shared/XboBlockEdit';
import PairSelector from '../shared/PairSelector';

registerBlockType( metadata.name, {
	edit: ( { attributes, setAttributes } ) => {
		const { symbol, depth, refresh } = attributes;

		return (
			<XboBlockEdit
				blockName={ metadata.name }
				attributes={ attributes }
				setAttributes={ setAttributes }
				title={ __( 'XBO Order Book', 'xbo-market-kit' ) }
				icon="book"
				inspectorControls={
					<>
						<PairSelector
							value={ symbol }
							onChange={ ( val ) =>
								setAttributes( { symbol: val } )
							}
						/>
						<RangeControl
							label={ __( 'Depth', 'xbo-market-kit' ) }
							value={ parseInt( depth, 10 ) }
							onChange={ ( val ) =>
								setAttributes( { depth: String( val ) } )
							}
							min={ 1 }
							max={ 250 }
							__next40pxDefaultSize
							__nextHasNoMarginBottom
						/>
						<RangeControl
							label={ __(
								'Refresh Interval (seconds)',
								'xbo-market-kit'
							) }
							value={ parseInt( refresh, 10 ) }
							onChange={ ( val ) =>
								setAttributes( { refresh: String( val ) } )
							}
							min={ 1 }
							max={ 60 }
							__next40pxDefaultSize
							__nextHasNoMarginBottom
						/>
					</>
				}
			/>
		);
	},
} );
```

---

## Task 9: Update Trades Block

**Files:**
- Modify: `src/blocks/trades/index.js`

### Step 1: Update trades/index.js

Replace the full contents of `src/blocks/trades/index.js`:

```js
import { registerBlockType } from '@wordpress/blocks';
import { RangeControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import metadata from './block.json';
import XboBlockEdit from '../shared/XboBlockEdit';
import PairSelector from '../shared/PairSelector';

registerBlockType( metadata.name, {
	edit: ( { attributes, setAttributes } ) => {
		const { symbol, limit, refresh } = attributes;

		return (
			<XboBlockEdit
				blockName={ metadata.name }
				attributes={ attributes }
				setAttributes={ setAttributes }
				title={ __( 'XBO Recent Trades', 'xbo-market-kit' ) }
				icon="list-view"
				inspectorControls={
					<>
						<PairSelector
							value={ symbol }
							onChange={ ( val ) =>
								setAttributes( { symbol: val } )
							}
						/>
						<RangeControl
							label={ __(
								'Number of Trades',
								'xbo-market-kit'
							) }
							value={ parseInt( limit, 10 ) }
							onChange={ ( val ) =>
								setAttributes( { limit: String( val ) } )
							}
							min={ 1 }
							max={ 100 }
							__next40pxDefaultSize
							__nextHasNoMarginBottom
						/>
						<RangeControl
							label={ __(
								'Refresh Interval (seconds)',
								'xbo-market-kit'
							) }
							value={ parseInt( refresh, 10 ) }
							onChange={ ( val ) =>
								setAttributes( { refresh: String( val ) } )
							}
							min={ 1 }
							max={ 60 }
							__next40pxDefaultSize
							__nextHasNoMarginBottom
						/>
					</>
				}
			/>
		);
	},
} );
```

---

## Task 10: Update Slippage Block

**Files:**
- Modify: `src/blocks/slippage/index.js`

### Step 1: Update slippage/index.js

Replace the full contents of `src/blocks/slippage/index.js`:

```js
import { registerBlockType } from '@wordpress/blocks';
import { SelectControl, TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import metadata from './block.json';
import XboBlockEdit from '../shared/XboBlockEdit';
import PairSelector from '../shared/PairSelector';

registerBlockType( metadata.name, {
	edit: ( { attributes, setAttributes } ) => {
		const { symbol, side, amount } = attributes;

		return (
			<XboBlockEdit
				blockName={ metadata.name }
				attributes={ attributes }
				setAttributes={ setAttributes }
				title={ __( 'XBO Slippage Calculator', 'xbo-market-kit' ) }
				icon="calculator"
				inspectorControls={
					<>
						<PairSelector
							value={ symbol }
							onChange={ ( val ) =>
								setAttributes( { symbol: val } )
							}
						/>
						<SelectControl
							label={ __( 'Side', 'xbo-market-kit' ) }
							value={ side }
							options={ [
								{
									label: __(
										'Buy',
										'xbo-market-kit'
									),
									value: 'buy',
								},
								{
									label: __(
										'Sell',
										'xbo-market-kit'
									),
									value: 'sell',
								},
							] }
							onChange={ ( val ) =>
								setAttributes( { side: val } )
							}
							__next40pxDefaultSize
							__nextHasNoMarginBottom
						/>
						<TextControl
							label={ __(
								'Default Amount',
								'xbo-market-kit'
							) }
							help={ __(
								'Leave empty to let user input amount on frontend',
								'xbo-market-kit'
							) }
							value={ amount }
							onChange={ ( val ) =>
								setAttributes( { amount: val } )
							}
							__next40pxDefaultSize
							__nextHasNoMarginBottom
						/>
					</>
				}
			/>
		);
	},
} );
```

---

## Task 11: Build JS and Commit

**Files:** build output

### Step 1: Build

```bash
cd wp-content/plugins/xbo-market-kit
npm run build
```

Expected: No errors. Check output for each block:
```
asset ticker/index.js ...
asset orderbook/index.js ...
asset trades/index.js ...
asset slippage/index.js ...
```

### Step 2: If build errors occur

Common issues:
- `Module not found: @wordpress/api-fetch` → Add to package.json devDependencies: `"@wordpress/api-fetch": "^7.0.0"` then `npm install`
- JSX syntax error → Check that the file uses proper JSX (no unclosed tags)

### Step 3: Run PHP tests one final time

```bash
composer run test
```
Expected: All tests pass.

### Step 4: Commit everything

```bash
git add src/blocks/shared/useTradingPairs.js \
        src/blocks/shared/PairSelector.js \
        src/blocks/shared/PairsSelector.js \
        src/blocks/ticker/index.js \
        src/blocks/orderbook/index.js \
        src/blocks/trades/index.js \
        src/blocks/slippage/index.js \
        build/blocks/
git commit -m "feat(blocks): add PairSelector and PairsSelector shared editor components"
```

---

## Task 12: Manual Verification in Block Editor

**No files to change. Verify correct behavior.**

### Step 1: Open Block Editor

Navigate to: `http://claude-code-hackathon-xbo-market-kit.local/wp-admin/post-new.php`

### Step 2: Test each block

For each block (Ticker, Order Book, Recent Trades, Slippage Calculator):
1. Insert block via "+" button → search for block name
2. Open the **Settings** panel on the right (Inspector)
3. Verify the trading pair control loads (may show spinner briefly)
4. Type "BTC" in the control — verify autocomplete suggestions appear
5. Select "BTC/USDT" — verify the block preview refreshes with that pair

For **Ticker** specifically:
1. The FormTokenField should show existing tokens as chips
2. Type "ETH" — select "ETH/USDT" — it should add as a second chip
3. Remove a chip by clicking "×" — verify it's removed from the attribute

### Step 3: Verify Movers block is unchanged

Movers block should have no pair selector — only the "Default Tab" and count settings.

---

## Summary

| Task | What | Test |
|------|------|------|
| 1 | TradingPairsController + register | `composer run test` |
| 2 | Verify REST endpoint in browser | Manual |
| 3 | useTradingPairs hook | N/A (no unit test for JS) |
| 4 | PairSelector (ComboboxControl) | N/A |
| 5 | PairsSelector (FormTokenField) | N/A |
| 6 | Fix block.json defaults | N/A |
| 7 | Ticker block update | `npm run build` |
| 8 | OrderBook block update | `npm run build` |
| 9 | Trades block update | `npm run build` |
| 10 | Slippage block update | `npm run build` |
| 11 | Build + full test + commit | Both |
| 12 | Manual editor verification | Manual |
