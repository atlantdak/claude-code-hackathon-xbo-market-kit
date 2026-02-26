/**
 * XBO Market Kit — Refresh Timer Interactivity API store.
 *
 * Circular countdown timer that auto-syncs with other XBO blocks'
 * refresh intervals on the page.
 *
 * @package XboMarketKit
 */

import { store, getContext } from '@wordpress/interactivity';

store( 'xbo-market-kit', {
	actions: {
		initRefreshTimer() {
			const ctx = getContext();
			let interval = ctx.interval;

			// Auto-detect from other XBO blocks on the page.
			if ( ! interval || interval <= 0 ) {
				const refreshEls = document.querySelectorAll( '[data-xbo-refresh]' );
				const intervals = [];
				refreshEls.forEach( ( el ) => {
					const val = parseInt( el.getAttribute( 'data-xbo-refresh' ), 10 );
					if ( val > 0 ) {
						intervals.push( val );
					}
				} );
				interval = intervals.length > 0 ? Math.min( ...intervals ) : 15;
			}

			ctx.interval = interval;
			ctx.remaining = interval;
			ctx.displayText = interval + 's';
			ctx.dashOffset = '0';
			const circumf = ctx.circumf;

			const tick = () => {
				ctx.remaining -= 1;
				if ( ctx.remaining <= 0 ) {
					ctx.isPulsing = true;
					ctx.remaining = interval;
					ctx.dashOffset = '0';
					ctx.displayText = interval + 's';
					setTimeout( () => {
						ctx.isPulsing = false;
					}, 600 );
				} else {
					const progress = 1 - ( ctx.remaining / interval );
					ctx.dashOffset = ( progress * circumf ).toFixed( 2 );
					ctx.displayText = ctx.remaining + 's';
				}
			};

			setInterval( tick, 1000 );
		},
	},
} );
