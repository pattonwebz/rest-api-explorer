import RouteItem from './RouteItem';

const METHODS = [ 'GET', 'POST', 'PUT', 'PATCH', 'DELETE' ];

export default function Sidebar( {
	routes,
	total,
	selectedRoute,
	onSelect,
	search,
	onSearch,
	filters,
	onFiltersChange,
	namespaces,
} ) {
	const handleMethodChange = ( e ) =>
		onFiltersChange( { ...filters, method: e.target.value } );

	const handleNamespaceChange = ( e ) =>
		onFiltersChange( { ...filters, namespace: e.target.value } );

	const hasFilters = search || filters.method || filters.namespace;

	return (
		<aside className="rae-sidebar">
			<div className="rae-sidebar__controls">
				<input
					type="search"
					className="rae-search"
					placeholder="Search routes…"
					value={ search }
					onChange={ ( e ) => onSearch( e.target.value ) }
					aria-label="Search routes"
				/>

				<div className="rae-sidebar__filters">
					<select
						className="rae-select"
						value={ filters.method }
						onChange={ handleMethodChange }
						aria-label="Filter by method"
					>
						<option value="">All Methods</option>
						{ METHODS.map( ( m ) => (
							<option key={ m } value={ m }>{ m }</option>
						) ) }
					</select>

					<select
						className="rae-select"
						value={ filters.namespace }
						onChange={ handleNamespaceChange }
						aria-label="Filter by namespace"
					>
						<option value="">All Namespaces</option>
						{ namespaces.map( ( ns ) => (
							<option key={ ns } value={ ns }>{ ns }</option>
						) ) }
					</select>
				</div>

				<div className="rae-sidebar__count">
					{ hasFilters ? (
						<>
							<span>{ routes.length } of { total }</span>
							<button
								className="rae-btn-clear"
								onClick={ () => {
									onSearch( '' );
									onFiltersChange( { method: '', namespace: '' } );
								} }
								type="button"
							>
								Clear filters
							</button>
						</>
					) : (
						<span>{ total } routes</span>
					) }
				</div>
			</div>

			<div className="rae-sidebar__list" role="list">
				{ routes.length === 0 ? (
					<div className="rae-sidebar__empty">No routes match your filters.</div>
				) : (
					routes.map( ( route ) => (
						<RouteItem
							key={ route.path }
							route={ route }
							isSelected={ selectedRoute?.path === route.path }
							onSelect={ onSelect }
						/>
					) )
				) }
			</div>
		</aside>
	);
}
