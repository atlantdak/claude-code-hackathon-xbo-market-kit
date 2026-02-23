/**
 * XBO Market Kit — Orderbook widget Interactivity API store.
 *
 * @package XboMarketKit
 */

import { store, getContext } from '@wordpress/interactivity';

const { state, actions } = store( 'xbo-market-kit', {
	state: {
		get orderbookBids() {
			return state._orderbookBids || [];
		},
		get orderbookAsks() {
			return state._orderbookAsks || [];
		},
		get orderbookSpread() {
			return state._orderbookSpread || '--';
		},
	},
	actions: {
		initOrderbook() {
			const ctx = getContext();
			state._obSymbol = ctx.symbol || 'BTC_USDT';
			state._obDepth = ctx.depth || 20;
			const refresh = ( ctx.refresh || 5 ) * 1000;

			actions.fetchOrderbook();
			setInterval( actions.fetchOrderbook, refresh );
		},
		async fetchOrderbook() {
			try {
				const symbol = state._obSymbol;
				const depth = state._obDepth;
				const res = await fetch(
					`${ window.location.origin }/wp-json/xbo/v1/orderbook?symbol=${ encodeURIComponent( symbol ) }&depth=${ depth }`
				);
				const data = await res.json();
				if ( data.bids && data.asks ) {
					const maxBidAmt = Math.max( ...data.bids.map( ( b ) => b.amount ), 0.001 );
					const maxAskAmt = Math.max( ...data.asks.map( ( a ) => a.amount ), 0.001 );

					state._orderbookBids = data.bids.map( ( b ) => ( {
						price: parseFloat( b.price ).toFixed( 2 ),
						amount: parseFloat( b.amount ).toFixed( 6 ),
						depthPct: `${ ( ( b.amount / maxBidAmt ) * 100 ).toFixed( 1 ) }%`,
					} ) );

					state._orderbookAsks = data.asks.map( ( a ) => ( {
						price: parseFloat( a.price ).toFixed( 2 ),
						amount: parseFloat( a.amount ).toFixed( 6 ),
						depthPct: `${ ( ( a.amount / maxAskAmt ) * 100 ).toFixed( 1 ) }%`,
					} ) );

					state._orderbookSpread = `${ parseFloat( data.spread ).toFixed( 2 ) } (${ parseFloat( data.spread_pct ).toFixed( 4 ) }%)`;
				}
			} catch ( e ) {
				// Silently fail.
			}
		},
	},
} );
