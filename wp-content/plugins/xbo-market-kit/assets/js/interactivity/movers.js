/**
 * XBO Market Kit — Movers widget Interactivity API store.
 *
 * @package XboMarketKit
 */

import { store, getContext } from '@wordpress/interactivity';

const { state, actions } = store( 'xbo-market-kit', {
	state: {
		get moversIsGainers() {
			return ( state._moversMode || 'gainers' ) === 'gainers';
		},
		get moversIsLosers() {
			return ( state._moversMode || 'gainers' ) === 'losers';
		},
		get moversItems() {
			return state._moversItems || [];
		},
	},
	actions: {
		initMovers() {
			const ctx = getContext();
			state._moversMode = ctx.mode || 'gainers';
			state._moversLimit = ctx.limit || 10;

			actions.fetchMovers();
			setInterval( actions.fetchMovers, 30000 );
		},
		setMoversGainers() {
			state._moversMode = 'gainers';
			actions.fetchMovers();
		},
		setMoversLosers() {
			state._moversMode = 'losers';
			actions.fetchMovers();
		},
		async fetchMovers() {
			try {
				const mode = state._moversMode || 'gainers';
				const limit = state._moversLimit || 10;
				const res = await fetch(
					`${ window.location.origin }/wp-json/xbo/v1/movers?mode=${ mode }&limit=${ limit }`
				);
				const data = await res.json();
				if ( Array.isArray( data ) ) {
					state._moversItems = data.map( ( item ) => ( {
						symbol: item.symbol,
						firstLetter: item.base ? item.base[ 0 ] : '?',
						price: `$${ parseFloat( item.last_price ).toLocaleString( 'en-US', { minimumFractionDigits: 2, maximumFractionDigits: 8 } ) }`,
						change: `${ item.change_pct_24h >= 0 ? '+' : '' }${ parseFloat( item.change_pct_24h ).toFixed( 2 ) }%`,
						isUp: item.change_pct_24h >= 0,
					} ) );
				}
			} catch ( e ) {
				// Silently fail.
			}
		},
	},
} );
