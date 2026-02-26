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
