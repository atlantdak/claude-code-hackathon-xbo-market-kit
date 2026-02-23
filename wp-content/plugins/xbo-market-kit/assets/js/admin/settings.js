/**
 * XBO Market Kit — Admin settings JavaScript.
 *
 * @package XboMarketKit
 */

( function () {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', function () {
		const btn = document.getElementById( 'xbo-mk-clear-cache' );
		const status = document.getElementById( 'xbo-mk-cache-status' );

		if ( ! btn || ! status ) {
			return;
		}

		btn.addEventListener( 'click', function () {
			btn.disabled = true;
			status.textContent = 'Clearing...';

			const data = new FormData();
			data.append( 'action', 'xbo_market_kit_clear_cache' );
			data.append( 'nonce', window.xboMarketKit.nonce );

			fetch( window.xboMarketKit.ajaxUrl, {
				method: 'POST',
				body: data,
				credentials: 'same-origin',
			} )
				.then( ( res ) => res.json() )
				.then( ( res ) => {
					status.textContent = res.success ? res.data : 'Error: ' + ( res.data || 'Unknown error' );
					btn.disabled = false;
				} )
				.catch( () => {
					status.textContent = 'Request failed.';
					btn.disabled = false;
				} );
		} );
	} );
} )();
