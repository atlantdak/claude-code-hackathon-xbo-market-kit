/**
 * XBO Market Kit — Ticker widget Interactivity API store.
 *
 * @package XboMarketKit
 */

import { store, getContext } from '@wordpress/interactivity';

const { state, actions } = store( 'xbo-market-kit', {
	state: {
		get tickerItems() {
			return state._tickerItems || [];
		},
	},
	actions: {
		initTicker() {
			const ctx = getContext();
			const symbols = ctx.symbols || 'BTC/USDT,ETH/USDT';
			const refresh = ( ctx.refresh || 15 ) * 1000;

			const fetchTicker = async () => {
				try {
					const res = await fetch(
						`${ window.location.origin }/wp-json/xbo/v1/ticker?symbols=${ encodeURIComponent( symbols ) }`
					);
					const data = await res.json();
					if ( Array.isArray( data ) ) {
						state._tickerItems = data;
						data.forEach( ( item ) => {
							const key = item.symbol.replace( /[^a-zA-Z0-9]/g, '' ).toLowerCase();
							const prevPrice = state[ `tickerPrice_${ key }` ];
							const newPrice = parseFloat( item.last_price ).toLocaleString( 'en-US', {
								minimumFractionDigits: 2,
								maximumFractionDigits: 8,
							} );
							state[ `tickerPrice_${ key }` ] = `$${ newPrice }`;
							state[ `tickerChange_${ key }` ] = `${ item.change_pct_24h >= 0 ? '+' : '' }${ parseFloat( item.change_pct_24h ).toFixed( 2 ) }%`;
							state[ `tickerUp_${ key }` ] = item.change_pct_24h >= 0;
							state[ `tickerDown_${ key }` ] = item.change_pct_24h < 0;

							// Flash animation.
							if ( prevPrice && prevPrice !== `$${ newPrice }` ) {
								const cards = document.querySelectorAll( '.xbo-mk-ticker-card' );
								cards.forEach( ( card ) => {
									if ( card.textContent.includes( item.symbol ) ) {
										const cls = item.change_pct_24h >= 0 ? 'xbo-mk-price-up' : 'xbo-mk-price-down';
										card.classList.add( cls );
										setTimeout( () => card.classList.remove( cls ), 500 );
									}
								} );
							}
						} );
					}
				} catch ( e ) {
					// Silently fail, will retry on next interval.
				}
			};

			fetchTicker();
			setInterval( fetchTicker, refresh );
		},
	},
} );
