import { useState } from '@wordpress/element';
import { MethodBadge } from './RouteItem';
import RequestBuilder from './RequestBuilder';
import ResponseDisplay from './ResponseDisplay';

const AUTH_META = {
	public:        { label: 'Public',        color: '#00a32a' },
	authenticated: { label: 'Authenticated', color: '#0073aa' },
	admin:         { label: 'Admin',         color: '#cc1818' },
	private:       { label: 'Private',       color: '#646970' },
	custom:        { label: 'Custom',        color: '#e67e00' },
	none:          { label: 'Unknown',       color: '#aaa' },
};

export default function RouteDetail( {
	route, homeUrl, nonce,
	preloadKey, preloadedRequest,
	onAddHistory, onSaveFavorite,
} ) {
	const defaultTab = preloadedRequest ? 'test' : 'details';
	const [ mainTab, setMainTab ]         = useState( defaultTab );
	const [ endpointIdx, setEndpointIdx ] = useState( 0 );
	const [ response, setResponse ]       = useState( null );

	const endpoint     = route.endpoints[ endpointIdx ] ?? route.endpoints[ 0 ];
	const multiEndpoint = route.endpoints.length > 1;

	return (
		<div className="rae-detail">
			<div className="rae-detail__header">
				<div className="rae-detail__methods">
					{ route.methods.map( ( m ) => (
						<MethodBadge key={ m } method={ m } large />
					) ) }
				</div>
				<h2 className="rae-detail__path">{ route.path }</h2>
				<p className="rae-detail__url">{ homeUrl }{ route.path }</p>
				<div className="rae-detail__meta">
					<span className="rae-tag">{ route.namespace }</span>
				</div>
				{ route.description && (
					<p className="rae-detail__description">{ route.description }</p>
				) }
			</div>

			{/* Main tabs: Details / Test */}
			<div className="rae-tabs rae-main-tabs">
				<button
					className={ `rae-tab ${ mainTab === 'details' ? 'rae-tab--active' : '' }` }
					onClick={ () => setMainTab( 'details' ) }
					type="button"
				>
					Details
				</button>
				<button
					className={ `rae-tab ${ mainTab === 'test' ? 'rae-tab--active' : '' }` }
					onClick={ () => setMainTab( 'test' ) }
					type="button"
				>
					Test
				</button>
			</div>

			{/* Per-endpoint tabs when multiple exist */}
			{ multiEndpoint && (
				<div className="rae-tabs rae-endpoint-tabs">
					{ route.endpoints.map( ( ep, i ) => (
						<button
							key={ i }
							className={ `rae-tab rae-tab--sm ${ endpointIdx === i ? 'rae-tab--active' : '' }` }
							onClick={ () => setEndpointIdx( i ) }
							type="button"
						>
							{ ep.methods.join( ' / ' ) }
						</button>
					) ) }
				</div>
			) }

			{ mainTab === 'details' && endpoint && (
				<EndpointDetails endpoint={ endpoint } />
			) }

			{ mainTab === 'test' && endpoint && (
				<div className="rae-test-panel">
					<RequestBuilder
						key={ preloadKey }
						route={ route }
						endpoint={ endpoint }
						nonce={ nonce }
						homeUrl={ homeUrl }
						onResponse={ setResponse }
						onAddHistory={ onAddHistory }
						onSaveFavorite={ onSaveFavorite }
						preloadedRequest={ preloadedRequest }
					/>
					<ResponseDisplay response={ response } />
				</div>
			) }
		</div>
	);
}

function EndpointDetails( { endpoint } ) {
	const auth = AUTH_META[ endpoint.auth_level ] || AUTH_META.none;

	return (
		<div className="rae-endpoint">
			<div className="rae-endpoint__meta">
				<span className="rae-auth-badge" style={ { backgroundColor: auth.color } }>
					{ auth.label }
				</span>
				<span className="rae-permission-label" title="Permission callback">
					{ endpoint.permission_label }
				</span>
			</div>

			{ endpoint.description && (
				<p className="rae-endpoint__description">{ endpoint.description }</p>
			) }

			{ endpoint.args.length > 0 ? (
				<div className="rae-args">
					<h3 className="rae-section-title">Parameters</h3>
					<table className="rae-args-table widefat">
						<thead>
							<tr>
								<th>Name</th>
								<th>Type</th>
								<th>Required</th>
								<th>Default</th>
								<th>Description</th>
							</tr>
						</thead>
						<tbody>
							{ endpoint.args.map( ( arg ) => (
								<tr key={ arg.name }>
									<td>
										<code>{ arg.name }</code>
										{ arg.required && (
											<span className="rae-required" title="Required">*</span>
										) }
									</td>
									<td>
										<code>{ Array.isArray( arg.type ) ? arg.type.join( ' | ' ) : arg.type }</code>
										{ arg.enum && (
											<div className="rae-enum">
												{ arg.enum.map( ( v ) => (
													<code key={ v } className="rae-enum__value">{ String( v ) }</code>
												) ) }
											</div>
										) }
									</td>
									<td>{ arg.required ? 'Yes' : 'No' }</td>
									<td>
										{ arg.default !== null && arg.default !== undefined
											? <code>{ JSON.stringify( arg.default ) }</code>
											: <span className="rae-muted">—</span>
										}
									</td>
									<td>
										{ arg.description || <span className="rae-muted">—</span> }
										{ arg.minimum !== null && arg.minimum !== undefined && (
											<div className="rae-muted">Min: { arg.minimum }</div>
										) }
										{ arg.maximum !== null && arg.maximum !== undefined && (
											<div className="rae-muted">Max: { arg.maximum }</div>
										) }
									</td>
								</tr>
							) ) }
						</tbody>
					</table>
				</div>
			) : (
				<p className="rae-muted">This endpoint accepts no parameters.</p>
			) }
		</div>
	);
}
