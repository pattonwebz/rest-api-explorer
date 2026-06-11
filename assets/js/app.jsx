import { useState, useMemo, useCallback, useEffect } from '@wordpress/element';
import Sidebar from './components/Sidebar';
import RouteDetail from './components/RouteDetail';
import HistoryPanel from './components/HistoryPanel';
import FavoritesPanel from './components/FavoritesPanel';
import SaveFavoriteModal from './components/SaveFavoriteModal';
import DocsPanel from './components/DocsPanel';

const HISTORY_KEY = 'rae_request_history';
const MAX_HISTORY  = 100;

function loadHistory() {
	try {
		return JSON.parse( localStorage.getItem( HISTORY_KEY ) || '[]' );
	} catch {
		return [];
	}
}

function saveHistory( history ) {
	localStorage.setItem( HISTORY_KEY, JSON.stringify( history.slice( 0, MAX_HISTORY ) ) );
}

// ── Favorites REST helpers ──────────────────────────────────────────────────

async function apiFetch( url, opts = {} ) {
	const res = await window.fetch( url, {
		headers: { 'X-WP-Nonce': window.restApiExplorer.nonce, 'Content-Type': 'application/json', ...opts.headers },
		...opts,
	} );
	if ( ! res.ok ) throw new Error( `HTTP ${ res.status }` );
	return res.json();
}

function favoritesUrl( suffix = '' ) {
	return `${ window.restApiExplorer.homeUrl }/rest-api-explorer/v1/favorites${ suffix }`;
}

// ── App ─────────────────────────────────────────────────────────────────────

export default function App( { initialRoutes, homeUrl, ajaxUrl, clearNonce, nonce } ) {
	const [ routes, setRoutes ]           = useState( initialRoutes );
	const [ selectedRoute, setSelectedRoute ] = useState( null );
	const [ search, setSearch ]           = useState( '' );
	const [ filters, setFilters ]         = useState( { method: '', namespace: '' } );
	const [ refreshing, setRefreshing ]   = useState( false );

	// Multi-view navigation
	const [ mainView, setMainView ]       = useState( 'routes' ); // 'routes'|'history'|'favorites'|'docs'

	// Request history (localStorage)
	const [ requestHistory, setRequestHistory ] = useState( loadHistory );

	// Favorites (server-side)
	const [ favorites, setFavorites ]     = useState( [] );

	// Pending save modal
	const [ pendingSave, setPendingSave ] = useState( null ); // { requestData } | null

	// Preloaded request from history or favorites
	const [ preloadKey, setPreloadKey ]       = useState( 0 );
	const [ preloadedRequest, setPreloadedRequest ] = useState( null );

	// ── Favorites bootstrap ────────────────────────────────────────────────

	useEffect( () => {
		apiFetch( favoritesUrl() )
			.then( ( data ) => setFavorites( data.favorites ?? [] ) )
			.catch( () => {} );
	}, [] );

	// ── Filtered routes ────────────────────────────────────────────────────

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

	// ── Callbacks ──────────────────────────────────────────────────────────

	const handleRefresh = useCallback( async () => {
		setRefreshing( true );
		try {
			const body = new FormData();
			body.append( 'action', 'rae_clear_cache' );
			body.append( '_ajax_nonce', clearNonce );

			const res  = await window.fetch( ajaxUrl, { method: 'POST', body } );
			const data = await res.json();

			if ( data.success ) {
				setRoutes( data.data.routes );
				setSelectedRoute( null );
			}
		} catch {
			// Silently fail — routes are still usable
		} finally {
			setRefreshing( false );
		}
	}, [ ajaxUrl, clearNonce ] );

	const handleAddHistory = useCallback( ( item ) => {
		setRequestHistory( ( prev ) => {
			const next = [ item, ...prev.filter( ( h ) => h.id !== item.id ) ];
			saveHistory( next );
			return next;
		} );
	}, [] );

	const handleClearHistory = useCallback( () => {
		setRequestHistory( [] );
		saveHistory( [] );
	}, [] );

	const handleLoadFromHistory = useCallback( ( historyItem ) => {
		// Find matching route in routes list
		const route = routes.find( ( r ) => r.path === historyItem.path );
		if ( route ) {
			setSelectedRoute( route );
		}
		setPreloadedRequest( historyItem );
		setPreloadKey( ( k ) => k + 1 );
		setMainView( 'routes' );
	}, [ routes ] );

	// Favorites
	const handleLoadFromFavorites = useCallback( ( fav ) => {
		const route = routes.find( ( r ) => r.path === fav.path );
		if ( route ) {
			setSelectedRoute( route );
		}
		setPreloadedRequest( fav );
		setPreloadKey( ( k ) => k + 1 );
		setMainView( 'routes' );
	}, [ routes ] );

	const handleSaveFavoriteRequest = useCallback( ( requestData ) => {
		setPendingSave( { requestData } );
	}, [] );

	const handleSaveFavoriteConfirm = useCallback( async ( name, folder ) => {
		if ( ! pendingSave ) return;
		const body = { ...pendingSave.requestData, name, folder };
		try {
			const data = await apiFetch( favoritesUrl(), {
				method: 'POST',
				body: JSON.stringify( body ),
			} );
			setFavorites( data.favorites ?? [] );
		} catch {
			// Silently fail
		} finally {
			setPendingSave( null );
		}
	}, [ pendingSave ] );

	const handleFavoriteDelete = useCallback( async ( id ) => {
		try {
			const data = await apiFetch( favoritesUrl( `/${ id }` ), { method: 'DELETE' } );
			setFavorites( data.favorites ?? [] );
		} catch {
			// Silently fail
		}
	}, [] );

	const handleFavoriteRename = useCallback( async ( id, name, folder ) => {
		try {
			const data = await apiFetch( favoritesUrl( `/${ id }` ), {
				method: 'PUT',
				body: JSON.stringify( { name, folder } ),
			} );
			setFavorites( data.favorites ?? [] );
		} catch {
			// Silently fail
		}
	}, [] );

	// ── Existing folders for the Save modal ───────────────────────────────

	const existingFolders = useMemo(
		() => [ ...new Set( favorites.map( ( f ) => f.folder ).filter( Boolean ) ) ].sort(),
		[ favorites ]
	);

	// ── Nav tabs ───────────────────────────────────────────────────────────

	const NAV_TABS = [
		{ id: 'routes',    label: 'Routes' },
		{ id: 'history',   label: `History${ requestHistory.length ? ` (${ requestHistory.length })` : '' }` },
		{ id: 'favorites', label: `Favorites${ favorites.length ? ` (${ favorites.length })` : '' }` },
		{ id: 'docs',      label: 'Docs' },
	];

	return (
		<div className="rae-app">
			<header className="rae-header">
				<h1 className="rae-header__title">REST API Explorer</h1>
				<nav className="rae-nav">
					{ NAV_TABS.map( ( tab ) => (
						<button
							key={ tab.id }
							className={ `rae-nav__tab ${ mainView === tab.id ? 'rae-nav__tab--active' : '' }` }
							onClick={ () => setMainView( tab.id ) }
							type="button"
						>
							{ tab.label }
						</button>
					) ) }
				</nav>
				<button
					className={ `rae-btn rae-btn--secondary ${ refreshing ? 'rae-btn--loading' : '' }` }
					onClick={ handleRefresh }
					disabled={ refreshing }
					type="button"
				>
					{ refreshing ? 'Refreshing…' : '↺ Refresh Routes' }
				</button>
			</header>

			{ mainView === 'routes' && (
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
							<RouteDetail
								key={ selectedRoute.path }
								route={ selectedRoute }
								homeUrl={ homeUrl }
								nonce={ nonce }
								preloadKey={ preloadKey }
								preloadedRequest={ preloadedRequest }
								onAddHistory={ handleAddHistory }
								onSaveFavorite={ handleSaveFavoriteRequest }
							/>
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
			) }

			{ mainView === 'history' && (
				<main className="rae-main rae-main--full">
					<HistoryPanel
						history={ requestHistory }
						onLoad={ handleLoadFromHistory }
						onClear={ handleClearHistory }
					/>
				</main>
			) }

			{ mainView === 'favorites' && (
				<main className="rae-main rae-main--full">
					<FavoritesPanel
						favorites={ favorites }
						onLoad={ handleLoadFromFavorites }
						onDelete={ handleFavoriteDelete }
						onRename={ handleFavoriteRename }
					/>
				</main>
			) }

			{ mainView === 'docs' && (
				<main className="rae-main rae-main--full">
					<DocsPanel routes={ routes } homeUrl={ homeUrl } />
				</main>
			) }

			{ pendingSave && (
				<SaveFavoriteModal
					requestData={ pendingSave.requestData }
					existingFolders={ existingFolders }
					onSave={ handleSaveFavoriteConfirm }
					onClose={ () => setPendingSave( null ) }
				/>
			) }
		</div>
	);
}
