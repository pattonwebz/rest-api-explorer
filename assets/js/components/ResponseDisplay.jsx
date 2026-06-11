import { useState } from '@wordpress/element';

const STATUS_COLOR = ( code ) => {
	if ( code >= 200 && code < 300 ) return '#00a32a';
	if ( code >= 300 && code < 400 ) return '#e67e00';
	if ( code >= 400 && code < 500 ) return '#cc1818';
	if ( code >= 500 ) return '#8b0000';
	return '#646970';
};

export default function ResponseDisplay( { response } ) {
	const [ tab, setTab ] = useState( 'body' );

	if ( ! response ) {
		return null;
	}

	if ( ! response.success && ! response.status_code ) {
		return (
			<div className="rae-response rae-response--error">
				<div className="rae-response__error-header">
					<span className="rae-response__error-icon">✕</span>
					<strong>Request Failed</strong>
				</div>
				<p className="rae-response__error-msg">{ response.error || 'Unknown error' }</p>
			</div>
		);
	}

	const body     = response.body;
	const bodyStr  = typeof body === 'string' ? body : JSON.stringify( body, null, 2 );
	const headers  = response.headers || {};
	const request  = response.request_sent || {};

	const isJson   = typeof body !== 'string';
	const bodyDisplay = isJson ? JSON.stringify( body, null, 2 ) : body;

	return (
		<div className="rae-response">
			<div className="rae-response__status-bar">
				<span
					className="rae-response__status-code"
					style={ { color: STATUS_COLOR( response.status_code ) } }
				>
					{ response.status_code }
				</span>
				<span className="rae-response__status-text">{ response.status_text }</span>
				<span className="rae-response__timing">{ response.elapsed_ms }ms</span>
			</div>

			{ ( response.status_code === 401 || response.status_code === 403 ) && (
				<div className="rae-response__auth-hint">
					<strong>Auth required.</strong> The endpoint requires authentication. Try switching to
					"Cookie (current user)" auth if you're logged in as an admin.
				</div>
			) }

			{ response.status_code >= 400 && response.status_code < 500 && response.status_code !== 401 && response.status_code !== 403 && (
				<div className="rae-response__validation-hint">
					<strong>Bad request.</strong> Check the parameters — the API returned a validation
					error.
				</div>
			) }

			{ response.status_code >= 500 && (
				<div className="rae-response__server-hint">
					<strong>Server error.</strong> Something went wrong on the server. Check the WordPress
					error log for details.
				</div>
			) }

			<div className="rae-tabs rae-response__tabs">
				{ [ 'body', 'headers', 'request' ].map( ( t ) => (
					<button
						key={ t }
						className={ `rae-tab ${ tab === t ? 'rae-tab--active' : '' }` }
						onClick={ () => setTab( t ) }
						type="button"
					>
						{ t.charAt( 0 ).toUpperCase() + t.slice( 1 ) }
						{ t === 'headers' && ` (${ Object.keys( headers ).length })` }
					</button>
				) ) }
			</div>

			<div className="rae-response__content">
				{ tab === 'body' && (
					<pre className="rae-code">{ bodyDisplay || '(empty)' }</pre>
				) }

				{ tab === 'headers' && (
					<table className="rae-args-table widefat">
						<thead>
							<tr>
								<th>Header</th>
								<th>Value</th>
							</tr>
						</thead>
						<tbody>
							{ Object.entries( headers ).map( ( [ k, v ] ) => (
								<tr key={ k }>
									<td><code>{ k }</code></td>
									<td><code>{ Array.isArray( v ) ? v.join( ', ' ) : v }</code></td>
								</tr>
							) ) }
						</tbody>
					</table>
				) }

				{ tab === 'request' && (
					<div className="rae-response__request">
						<div className="rae-response__request-line">
							<strong>{ request.method }</strong> { request.url }
						</div>
						{ request.headers && Object.keys( request.headers ).length > 0 && (
							<>
								<div className="rae-response__subheading">Headers</div>
								<pre className="rae-code">{ JSON.stringify( request.headers, null, 2 ) }</pre>
							</>
						) }
						{ request.body && (
							<>
								<div className="rae-response__subheading">Body</div>
								<pre className="rae-code">{ request.body }</pre>
							</>
						) }
					</div>
				) }
			</div>
		</div>
	);
}
