/**
 * XBO Market Kit — Ticker widget Interactivity API store.
 *
 * @package XboMarketKit
 */

import { store, getContext } from '@wordpress/interactivity';

/**
 * Render price array to SVG points strings.
 *
 * @param {number[]} prices     Array of price values.
 * @param {number}   viewWidth  SVG viewBox width.
 * @param {number}   viewHeight SVG viewBox height.
 * @return {{ polyline: string, polygon: string }}
 */
function renderSvgPoints( prices, viewWidth = 100, viewHeight = 30 ) {
	const count = prices.length;
	if ( count < 2 ) {
		return { polyline: '', polygon: '' };
	}

	let bufMin = prices[ 0 ];
	let bufMax = prices[ 0 ];
	for ( let i = 1; i < count; i++ ) {
		if ( prices[ i ] < bufMin ) bufMin = prices[ i ];
		if ( prices[ i ] > bufMax ) bufMax = prices[ i ];
	}

	const range = bufMax - bufMin;
	const points = [];

	if ( range < 1e-12 ) {
		const mid = viewHeight / 2;
		for ( let i = 0; i < count; i++ ) {
			const x = ( i * viewWidth ) / ( count - 1 );
			points.push( `${ x.toFixed( 1 ) },${ mid.toFixed( 1 ) }` );
		}
	} else {
		const padding = range * 0.1;
		const yMin = bufMin - padding;
		const yRange = ( bufMax + padding ) - yMin;

		for ( let i = 0; i < count; i++ ) {
			const x = ( i * viewWidth ) / ( count - 1 );
			let y = viewHeight - ( ( prices[ i ] - yMin ) / yRange ) * viewHeight;
			y = Math.max( 0, Math.min( viewHeight, y ) );
			points.push( `${ x.toFixed( 1 ) },${ y.toFixed( 1 ) }` );
		}
	}

	const polyline = points.join( ' ' );
	const polygon = `${ polyline } ${ viewWidth },${ viewHeight } 0,${ viewHeight }`;
	return { polyline, polygon };
}

// Sparkline buffers keyed by symbol key.
const sparklineBuffers = {};

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
			const refresh = ( parseInt( ctx.refresh, 10 ) || 15 ) * 1000;
			const sparklineData = ctx.sparklineData || {};

			// Initialize ring buffers from server-rendered data.
			Object.entries( sparklineData ).forEach( ( [ key, data ] ) => {
				sparklineBuffers[ key ] = {
					prices: [ ...( data.prices || [] ) ],
					realCount: 0,
					lastUpdatedAt: data.updatedAt || 0,
				};
			} );

			const fetchTicker = async () => {
				try {
					const res = await fetch(
						`${ window.location.origin }/wp-json/xbo/v1/ticker?symbols=${ encodeURIComponent( symbols ) }`
					);
					const data = await res.json();
					if ( ! Array.isArray( data ) ) return;

					state._tickerItems = data;
					const now = Math.floor( Date.now() / 1000 );

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
							const cards = document.querySelectorAll( '.xbo-mk-ticker__card' );
							cards.forEach( ( card ) => {
								if ( card.textContent.includes( item.symbol ) ) {
									const cls = item.change_pct_24h >= 0 ? 'xbo-mk-price-up' : 'xbo-mk-price-down';
									card.classList.add( cls );
									setTimeout( () => card.classList.remove( cls ), 2000 );
								}
							} );
						}

						// Update sparkline buffer.
						const buf = sparklineBuffers[ key ];
						if ( ! buf ) return;

						const lastPrice = parseFloat( item.last_price );
						if ( ! isFinite( lastPrice ) || lastPrice <= 0 ) return;

						// Skip stale snapshots (same cached response).
						if ( buf.lastUpdatedAt === now ) return;
						buf.lastUpdatedAt = now;

						// Push real point, shift left.
						buf.prices.push( lastPrice );
						if ( buf.prices.length > 40 ) {
							buf.prices.shift();
						}
						buf.realCount = Math.min( buf.realCount + 1, 40 );

						// Re-render SVG.
						const svg = document.querySelector(
							`.xbo-mk-ticker__sparkline[data-symbol="${ key }"]`
						);
						if ( ! svg ) return;

						const { polyline, polygon } = renderSvgPoints( buf.prices );
						const polylineEl = svg.querySelector( '.xbo-mk-ticker__sparkline-line' );
						const polygonEl = svg.querySelector( '.xbo-mk-ticker__sparkline-fill' );
						if ( polylineEl ) polylineEl.setAttribute( 'points', polyline );
						if ( polygonEl ) polygonEl.setAttribute( 'points', polygon );

						// Update trend color class.
						const trend = item.change_pct_24h >= 0 ? 'positive' : 'negative';
						svg.classList.remove( 'xbo-mk-ticker__sparkline--positive', 'xbo-mk-ticker__sparkline--negative' );
						svg.classList.add( `xbo-mk-ticker__sparkline--${ trend }` );
					} );
				} catch ( e ) {
					// Silently fail, will retry on next interval.
				}
			};

			fetchTicker();
			setInterval( fetchTicker, refresh );
		},
	},
} );
