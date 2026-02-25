/**
 * XBO Market Kit — Slippage Calculator Interactivity API store.
 *
 * @package XboMarketKit
 */

import { store, getContext, getConfig, withScope } from '@wordpress/interactivity';

/**
 * Module-level cached pair catalog (loaded once from footer JSON).
 */
let catalogCache = null;

/**
 * Load the pair catalog from the footer JSON script tag.
 *
 * @return {Object} Catalog with pairs_map and icons.
 */
function getCatalog() {
	if ( catalogCache ) {
		return catalogCache;
	}
	const el = document.getElementById( 'xbo-mk-pairs-catalog' );
	if ( el ) {
		try {
			catalogCache = JSON.parse( el.textContent );
		} catch ( e ) {
			catalogCache = { pairs_map: {}, icons: {} };
		}
	} else {
		catalogCache = { pairs_map: {}, icons: {} };
	}
	return catalogCache;
}

const { restUrl } = getConfig( 'xbo-market-kit' );

const { state, actions } = store( 'xbo-market-kit', {
	state: {
		// --- Side toggle ---
		get slippageIsBuy() {
			return getContext().side === 'buy';
		},
		get slippageIsSell() {
			return getContext().side === 'sell';
		},

		// --- Results visibility ---
		get slippageHasResult() {
			return !! getContext().result;
		},
		get slippageHasError() {
			return !! getContext().error;
		},
		get slippageLoading() {
			return !! getContext().loading;
		},
		get slippageError() {
			return getContext().error || '';
		},

		// --- Partial fill ---
		get slippageIsPartialFill() {
			const ctx = getContext();
			if ( ! ctx.result ) return false;
			const amount = parseFloat( ctx.amount );
			return amount > 0 && ctx.result.depth_used < amount;
		},
		get slippagePartialFillText() {
			const ctx = getContext();
			if ( ! ctx.result ) return '';
			const amount = parseFloat( ctx.amount );
			return `⚠ Only ${ ctx.result.depth_used } of ${ amount } filled — insufficient liquidity`;
		},

		// --- Formatted result getters ---
		get slippageResult_avg_price() {
			const r = getContext().result;
			return r?.avg_price?.toLocaleString( 'en-US', {
				minimumFractionDigits: 2,
				maximumFractionDigits: 8,
			} ) || '--';
		},
		get slippageResult_slippage_pct() {
			const r = getContext().result;
			return r ? `${ r.slippage_pct }%` : '--';
		},
		get slippageResult_spread() {
			const r = getContext().result;
			return r?.spread?.toFixed( 8 ) || '--';
		},
		get slippageResult_spread_pct() {
			const r = getContext().result;
			return r ? `${ r.spread_pct }%` : '--';
		},
		get slippageResult_depth_used() {
			const r = getContext().result;
			return r?.depth_used?.toFixed( 8 ) || '--';
		},
		get slippageResult_total_cost() {
			const r = getContext().result;
			return r?.total_cost?.toLocaleString( 'en-US', {
				minimumFractionDigits: 2,
				maximumFractionDigits: 8,
			} ) || '--';
		},

		// --- Selector icon URLs (for trigger buttons, updated dynamically) ---
		get slippageBaseIcon() {
			const catalog = getCatalog();
			return catalog.icons[ getContext().base ] || '';
		},
		get slippageQuoteIcon() {
			const catalog = getCatalog();
			return catalog.icons[ getContext().quote ] || '';
		},
	},

	actions: {
		/**
		 * Initialize the slippage widget instance.
		 */
		initSlippage() {
			const ctx = getContext();

			// Initialize non-serializable context properties.
			ctx._abortController = null;
			ctx._debounceTimer = null;

			// Auto-calculate if amount is set.
			if ( ctx.amount && parseFloat( ctx.amount ) > 0 ) {
				actions.slippageCalculate();
			}
		},

		// --- Side toggle ---
		slippageSetBuy() {
			const ctx = getContext();
			ctx.side = 'buy';
			actions.slippageDebounceCalc();
		},
		slippageSetSell() {
			const ctx = getContext();
			ctx.side = 'sell';
			actions.slippageDebounceCalc();
		},

		// --- Amount ---
		slippageAmountChange( event ) {
			const ctx = getContext();
			ctx.amount = event.target.value;
			actions.slippageDebounceCalc();
		},

		// --- Base selector ---
		slippageToggleBase( event ) {
			event.stopPropagation();
			const ctx = getContext();
			ctx.baseOpen = ! ctx.baseOpen;
			ctx.quoteOpen = false;
			ctx.baseSearch = '';
			if ( ctx.baseOpen ) {
				// Focus search input after DOM update.
				requestAnimationFrame( () => {
					event.target
						.closest( '.xbo-mk-slippage__selector' )
						?.querySelector( '.xbo-mk-slippage__selector-search' )
						?.focus();
				} );
			}
		},
		slippageSearchBase( event ) {
			const ctx = getContext();
			ctx.baseSearch = event.target.value;
			actions.slippageFilterItems( 'base' );
		},
		slippageSelectBase( event ) {
			event.stopPropagation();
			const ctx = getContext();
			const symbol = event.target.closest( '[data-symbol]' )?.dataset?.symbol;
			if ( ! symbol ) return;

			ctx.base = symbol;
			ctx.baseOpen = false;
			ctx.baseSearch = '';

			// Validate current quote is still available for new base.
			const catalog = getCatalog();
			const available = catalog.pairs_map[ symbol ] || [];
			if ( ! available.includes( ctx.quote ) ) {
				ctx.quote = available[ 0 ] || 'USDT';
			}

			// Update quote dropdown options.
			actions.slippageUpdateQuoteOptions();
			actions.slippageDebounceCalc();
		},

		// --- Quote selector ---
		slippageToggleQuote( event ) {
			event.stopPropagation();
			const ctx = getContext();
			ctx.quoteOpen = ! ctx.quoteOpen;
			ctx.baseOpen = false;
			ctx.quoteSearch = '';
			if ( ctx.quoteOpen ) {
				requestAnimationFrame( () => {
					event.target
						.closest( '.xbo-mk-slippage__selector' )
						?.querySelector( '.xbo-mk-slippage__selector-search' )
						?.focus();
				} );
			}
		},
		slippageSearchQuote( event ) {
			const ctx = getContext();
			ctx.quoteSearch = event.target.value;
			actions.slippageFilterItems( 'quote' );
		},
		slippageSelectQuote( event ) {
			event.stopPropagation();
			const ctx = getContext();
			const symbol = event.target.closest( '[data-symbol]' )?.dataset?.symbol;
			if ( ! symbol ) return;

			ctx.quote = symbol;
			ctx.quoteOpen = false;
			ctx.quoteSearch = '';
			actions.slippageDebounceCalc();
		},

		// --- Dropdown utilities ---
		slippageCloseDropdowns() {
			const ctx = getContext();
			if ( ctx.baseOpen || ctx.quoteOpen ) {
				ctx.baseOpen = false;
				ctx.quoteOpen = false;
				ctx.baseSearch = '';
				ctx.quoteSearch = '';
			}
		},

		/**
		 * Filter dropdown items by search text via CSS class toggle.
		 *
		 * @param {string} type 'base' or 'quote'.
		 */
		slippageFilterItems( type ) {
			const ctx = getContext();
			const search = ( type === 'base' ? ctx.baseSearch : ctx.quoteSearch ).toUpperCase();

			// Find the relevant selector element.
			const selectors = document.querySelectorAll(
				'.xbo-mk-slippage__selector'
			);

			selectors.forEach( ( selector ) => {
				const items = selector.querySelectorAll(
					'.xbo-mk-slippage__selector-item'
				);
				items.forEach( ( item ) => {
					const sym = item.dataset.symbol || '';
					const match = ! search || sym.toUpperCase().includes( search );
					item.classList.toggle(
						'xbo-mk-slippage__selector-item--hidden',
						! match
					);
				} );
			} );
		},

		/**
		 * Update the quote dropdown to show only valid quotes for the selected base.
		 */
		slippageUpdateQuoteOptions() {
			const ctx = getContext();
			const catalog = getCatalog();
			const available = catalog.pairs_map[ ctx.base ] || [];

			// Hide unavailable quote items via class.
			// The quote selector is the second .xbo-mk-slippage__selector in this widget.
			const widget = document.querySelector( '.xbo-mk-slippage' );
			if ( ! widget ) return;

			const quoteSelector = widget.querySelectorAll(
				'.xbo-mk-slippage__selector'
			)[ 1 ];
			if ( ! quoteSelector ) return;

			const items = quoteSelector.querySelectorAll(
				'.xbo-mk-slippage__selector-item'
			);
			items.forEach( ( item ) => {
				const sym = item.dataset.symbol || '';
				item.classList.toggle(
					'xbo-mk-slippage__selector-item--hidden',
					! available.includes( sym )
				);
			} );
		},

		/**
		 * Handle keyboard navigation in dropdown.
		 */
		slippageSelectorKeydown( event ) {
			const list = event.target
				.closest( '.xbo-mk-slippage__selector-dropdown' )
				?.querySelector( '.xbo-mk-slippage__selector-list' );
			if ( ! list ) return;

			const visible = Array.from(
				list.querySelectorAll(
					'.xbo-mk-slippage__selector-item:not(.xbo-mk-slippage__selector-item--hidden)'
				)
			);
			if ( ! visible.length ) return;

			const highlighted = list.querySelector(
				'.xbo-mk-slippage__selector-item--highlighted'
			);
			let idx = highlighted ? visible.indexOf( highlighted ) : -1;

			if ( event.key === 'ArrowDown' ) {
				event.preventDefault();
				if ( highlighted ) {
					highlighted.classList.remove(
						'xbo-mk-slippage__selector-item--highlighted'
					);
				}
				idx = Math.min( idx + 1, visible.length - 1 );
				visible[ idx ].classList.add(
					'xbo-mk-slippage__selector-item--highlighted'
				);
				visible[ idx ].scrollIntoView( { block: 'nearest' } );
			} else if ( event.key === 'ArrowUp' ) {
				event.preventDefault();
				if ( highlighted ) {
					highlighted.classList.remove(
						'xbo-mk-slippage__selector-item--highlighted'
					);
				}
				idx = Math.max( idx - 1, 0 );
				visible[ idx ].classList.add(
					'xbo-mk-slippage__selector-item--highlighted'
				);
				visible[ idx ].scrollIntoView( { block: 'nearest' } );
			} else if ( event.key === 'Enter' ) {
				event.preventDefault();
				if ( highlighted ) {
					highlighted.click();
				}
			} else if ( event.key === 'Escape' ) {
				event.preventDefault();
				const ctx = getContext();
				ctx.baseOpen = false;
				ctx.quoteOpen = false;
			}
		},

		// --- Debounced calculation ---
		slippageDebounceCalc() {
			const ctx = getContext();
			if ( ctx._debounceTimer ) {
				clearTimeout( ctx._debounceTimer );
			}
			const scopedCalc = withScope( actions.slippageCalculate );
			ctx._debounceTimer = setTimeout( scopedCalc, 300 );
		},

		// --- API fetch ---
		async slippageCalculate() {
			const ctx = getContext();
			const symbol = `${ ctx.base }_${ ctx.quote }`;
			const { side } = ctx;
			const amount = parseFloat( ctx.amount );

			if ( ! ctx.base || ! ctx.quote || ! amount || amount <= 0 ) {
				return;
			}

			// Abort previous request.
			if ( ctx._abortController ) {
				ctx._abortController.abort();
			}
			ctx._abortController = new AbortController();
			const { signal } = ctx._abortController;

			ctx.loading = true;
			ctx.error = '';

			try {
				const url = `${ restUrl }slippage?symbol=${ encodeURIComponent( symbol ) }&side=${ side }&amount=${ amount }`;
				const res = await fetch( url, { signal } );

				if ( ! res.ok ) {
					throw new Error( `HTTP ${ res.status }` );
				}

				if ( signal.aborted ) return;

				const data = await res.json();

				if ( data.avg_price !== undefined ) {
					ctx.result = data;
					ctx.error = '';
				} else if ( data.message ) {
					ctx.error = data.message;
					ctx.result = null;
				}
			} catch ( e ) {
				if ( e.name === 'AbortError' ) return;
				ctx.error = 'Failed to calculate slippage';
				ctx.result = null;
			} finally {
				if ( ! signal.aborted ) {
					ctx.loading = false;
				}
			}
		},
	},
} );
