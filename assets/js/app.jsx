import { useState, useMemo, useCallback } from '@wordpress/element';
import Sidebar from './components/Sidebar';
import RouteDetail from './components/RouteDetail';

export default function App( { initialRoutes, homeUrl, ajaxUrl, clearNonce, nonce } ) {
	const [ routes, setRoutes ]           = useState( initialRoutes );
	const [ selectedRoute, setSelectedRoute ] = useState( null );
	const [ search, setSearch ]           = useState( '' );
	const [ filters, setFilters ]         = useState( { method: '', namespace: '' } );
	const [ refreshing, setRefreshing ]   = useState( false );

	const namespaces = useMemo(
		() => [ ...new Set( routes.map( ( r ) => r.namespace ) ) ].sort(),
		[ routes ]
	);

	const filtered = useMemo( () => {
		const q = search.toLowerCase();
		return routes.filter( ( route ) => {
			if ( q && ! route.path.toLowerCase().includes( q ) ) return false;
			if ( filters.method && ! route.methods.includes( filters.method ) ) return false;
			if ( filters.namespace && route.namespace !== filters.namespace ) return false;
			return true;
		} );
	}, [ routes, search, filters ] );

	const handleRefresh = useCallback( async () => {
		setRefreshing( true );
		try {
			const body = new FormData();
			body.append( 'action', 'rae_clear_cache' );
			body.append( '_ajax_nonce', clearNonce );

			const res  = await fetch( ajaxUrl, { method: 'POST', body } );
			const data = await res.json();

			if ( data.success ) {
				setRoutes( data.data.routes );
				setSelectedRoute( null );
			}
		} catch ( e ) {
			// Silently fail — routes are still usable
		} finally {
			setRefreshing( false );
		}
	}, [ ajaxUrl, clearNonce ] );

	return (
		<div className="rae-app">
			<header className="rae-header">
				<h1 className="rae-header__title">REST API Explorer</h1>
				<button
					className={ `rae-btn rae-btn--secondary ${ refreshing ? 'rae-btn--loading' : '' }` }
					onClick={ handleRefresh }
					disabled={ refreshing }
					type="button"
				>
					{ refreshing ? 'Refreshing…' : '↺ Refresh Routes' }
				</button>
			</header>

			<div className="rae-layout">
				<Sidebar
					routes={ filtered }
					total={ routes.length }
					selectedRoute={ selectedRoute }
					onSelect={ setSelectedRoute }
					search={ search }
					onSearch={ setSearch }
					filters={ filters }
					onFiltersChange={ setFilters }
					namespaces={ namespaces }
				/>

				<main className="rae-main">
					{ selectedRoute ? (
						<RouteDetail route={ selectedRoute } homeUrl={ homeUrl } nonce={ nonce } />
					) : (
						<div className="rae-placeholder">
							<span className="dashicons dashicons-rest-api rae-placeholder__icon" />
							<h2>Select a route to explore</h2>
							<p>
								<strong>{ routes.length }</strong> routes registered on this site.
								Use the sidebar to browse, search, and filter.
							</p>
						</div>
					) }
				</main>
			</div>
		</div>
	);
}
