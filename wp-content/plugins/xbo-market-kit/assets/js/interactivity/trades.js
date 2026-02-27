/**
 * XBO Market Kit — Trades widget Interactivity API store.
 *
 * @package XboMarketKit
 */

import { store, getContext } from '@wordpress/interactivity';

const { state, actions } = store( 'xbo-market-kit', {
	state: {
		get tradesItems() {
			return state._tradesItems || [];
		},
	},
	actions: {
		initTrades() {
			const ctx = getContext();
			state._tradesSymbol = ctx.symbol || 'BTC/USDT';
			state._tradesLimit = ctx.limit || 20;
			const refresh = ( parseInt( ctx.refresh, 10 ) || 15 ) * 1000;

			actions.fetchTrades();
			setInterval( actions.fetchTrades, refresh );
		},
		async fetchTrades() {
			try {
				const symbol = state._tradesSymbol;
				const limit = state._tradesLimit;
				const res = await fetch(
					`${ window.location.origin }/wp-json/xbo/v1/trades?symbol=${ encodeURIComponent( symbol ) }&limit=${ limit }`
				);
				const data = await res.json();
				if ( Array.isArray( data ) ) {
					state._tradesItems = data.map( ( t ) => {
						const date = t.timestamp ? new Date( t.timestamp ) : new Date();
						return {
							time: date.toLocaleTimeString( 'en-US', { hour12: false } ),
							side: t.side,
							sideLabel: t.side === 'buy' ? 'Buy' : 'Sell',
							isBuy: t.side === 'buy',
							price: parseFloat( t.price ).toLocaleString( 'en-US', { minimumFractionDigits: 2, maximumFractionDigits: 8 } ),
							amount: parseFloat( t.amount ).toFixed( 6 ),
						};
					} );
				}
			} catch ( e ) {
				// Silently fail.
			}
		},
	},
} );
