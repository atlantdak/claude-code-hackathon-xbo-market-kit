/**
 * XBO Market Kit — Slippage Calculator Interactivity API store.
 *
 * @package XboMarketKit
 */

import { store, getContext } from '@wordpress/interactivity';

let debounceTimer = null;

const { state, actions } = store( 'xbo-market-kit', {
	state: {
		get slippageIsBuy() {
			return ( state._slippageSide || 'buy' ) === 'buy';
		},
		get slippageIsSell() {
			return ( state._slippageSide || 'buy' ) === 'sell';
		},
		get slippageHasResult() {
			return !! state._slippageResult;
		},
		get slippageLoading() {
			return !! state._slippageLoading;
		},
		get slippageResult_avg_price() {
			return state._slippageResult?.avg_price?.toLocaleString( 'en-US', { minimumFractionDigits: 2, maximumFractionDigits: 8 } ) || '--';
		},
		get slippageResult_slippage_pct() {
			return state._slippageResult ? `${ state._slippageResult.slippage_pct }%` : '--';
		},
		get slippageResult_spread() {
			return state._slippageResult?.spread?.toFixed( 8 ) || '--';
		},
		get slippageResult_spread_pct() {
			return state._slippageResult ? `${ state._slippageResult.spread_pct }%` : '--';
		},
		get slippageResult_depth_used() {
			return state._slippageResult?.depth_used?.toFixed( 8 ) || '--';
		},
		get slippageResult_total_cost() {
			return state._slippageResult?.total_cost?.toLocaleString( 'en-US', { minimumFractionDigits: 2, maximumFractionDigits: 8 } ) || '--';
		},
	},
	actions: {
		initSlippage() {
			const ctx = getContext();
			state._slippageSymbol = ctx.symbol || 'BTC_USDT';
			state._slippageSide = ctx.side || 'buy';
			state._slippageAmount = ctx.amount || '';
			state._slippageResult = null;
			state._slippageLoading = false;
		},
		slippageSymbolChange( event ) {
			state._slippageSymbol = event.target.value;
			actions.slippageDebounceCalc();
		},
		slippageSetBuy() {
			state._slippageSide = 'buy';
			actions.slippageDebounceCalc();
		},
		slippageSetSell() {
			state._slippageSide = 'sell';
			actions.slippageDebounceCalc();
		},
		slippageAmountChange( event ) {
			state._slippageAmount = event.target.value;
			actions.slippageDebounceCalc();
		},
		slippageDebounceCalc() {
			if ( debounceTimer ) {
				clearTimeout( debounceTimer );
			}
			debounceTimer = setTimeout( actions.slippageCalculate, 300 );
		},
		async slippageCalculate() {
			const symbol = state._slippageSymbol;
			const side = state._slippageSide;
			const amount = parseFloat( state._slippageAmount );

			if ( ! symbol || ! amount || amount <= 0 ) {
				return;
			}

			state._slippageLoading = true;
			try {
				const res = await fetch(
					`${ window.location.origin }/wp-json/xbo/v1/slippage?symbol=${ encodeURIComponent( symbol ) }&side=${ side }&amount=${ amount }`
				);
				const data = await res.json();
				if ( data.avg_price !== undefined ) {
					state._slippageResult = data;
				}
			} catch ( e ) {
				// Silently fail.
			} finally {
				state._slippageLoading = false;
			}
		},
	},
} );
