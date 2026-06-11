export const METHOD_COLORS = {
	GET:    '#00a32a',
	POST:   '#0073aa',
	PUT:    '#e67e00',
	PATCH:  '#6f42c1',
	DELETE: '#cc1818',
};

export function MethodBadge( { method, large = false } ) {
	return (
		<span
			className={ `rae-method-badge ${ large ? 'rae-method-badge--lg' : '' }` }
			style={ { backgroundColor: METHOD_COLORS[ method ] || '#646970' } }
		>
			{ method }
		</span>
	);
}

export default function RouteItem( { route, isSelected, onSelect } ) {
	return (
		<button
			className={ `rae-route-item ${ isSelected ? 'rae-route-item--selected' : '' }` }
			onClick={ () => onSelect( route ) }
			type="button"
			title={ route.path }
		>
			<div className="rae-route-item__methods">
				{ route.methods.map( ( m ) => (
					<MethodBadge key={ m } method={ m } />
				) ) }
			</div>
			<div className="rae-route-item__path">{ route.path }</div>
			{ route.description && (
				<div className="rae-route-item__desc">{ route.description }</div>
			) }
		</button>
	);
}
