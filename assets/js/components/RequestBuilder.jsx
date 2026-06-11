import { useState, useEffect } from '@wordpress/element';

const METHODS = [ 'GET', 'POST', 'PUT', 'PATCH', 'DELETE' ];

const AUTH_TYPES = [
	{ value: 'none',   label: 'No Auth' },
	{ value: 'cookie', label: 'Cookie (current user)' },
	{ value: 'basic',  label: 'Basic Auth' },
	{ value: 'bearer', label: 'Bearer Token' },
];

function buildInitialParams( args ) {
	const params = {};
	for ( const arg of args ) {
		if ( arg.default !== null && arg.default !== undefined ) {
			params[ arg.name ] = String( arg.default );
		}
	}
	return params;
}

export default function RequestBuilder( {
	route, endpoint, nonce, homeUrl,
	onResponse, onAddHistory, onSaveFavorite,
	preloadedRequest,
} ) {
	const initialMethod = preloadedRequest?.method ?? endpoint.methods[ 0 ] ?? 'GET';
	const initialParams = preloadedRequest?.params ?? buildInitialParams( endpoint.args );
	const initialBody   = preloadedRequest?.body   ? JSON.stringify( preloadedRequest.body, null, 2 ) : '{}';
	const initialAuth   = preloadedRequest?.auth?.type ?? 'cookie';

	const [ method, setMethod ]     = useState( initialMethod );
	const [ params, setParams ]     = useState( initialParams );
	const [ body, setBody ]         = useState( initialBody );
	const [ bodyMode, setBodyMode ] = useState( 'form' );
	const [ authType, setAuthType ] = useState( initialAuth );
	const [ authExtra, setAuthExtra ] = useState( {
		username: preloadedRequest?.auth?.username ?? '',
		password: preloadedRequest?.auth?.password ?? '',
		token:    preloadedRequest?.auth?.token    ?? '',
	} );
	const [ loading, setLoading ]   = useState( false );

	// Re-initialize when endpoint changes (but NOT when preloadedRequest changes — that's handled via key prop)
	useEffect( () => {
		setMethod( endpoint.methods[ 0 ] || 'GET' );
		setParams( buildInitialParams( endpoint.args ) );
		setBody( '{}' );
		setAuthType( 'cookie' );
		setAuthExtra( { username: '', password: '', token: '' } );
	}, [ route.path, endpoint ] );

	const queryArgs = endpoint.args.filter( ( a ) =>
		[ 'GET', 'DELETE' ].includes( method ) || a.type !== 'object'
	);
	const bodyArgs = endpoint.args.filter( ( a ) =>
		[ 'POST', 'PUT', 'PATCH' ].includes( method )
	);
	const hasBody = [ 'POST', 'PUT', 'PATCH' ].includes( method );

	const handleParamChange = ( name, value ) => {
		setParams( ( prev ) => ( { ...prev, [ name ]: value } ) );
	};

	const handleSend = async () => {
		setLoading( true );
		onResponse( null );

		let parsedBody = {};
		if ( hasBody ) {
			if ( bodyMode === 'raw' ) {
				try {
					parsedBody = JSON.parse( body );
				} catch {
					onResponse( { success: false, error: 'Invalid JSON in request body', elapsed_ms: 0 } );
					setLoading( false );
					return;
				}
			} else {
				parsedBody = params;
			}
		}

		const cleanParams = {};
		for ( const [ k, v ] of Object.entries( params ) ) {
			if ( v !== '' && v !== null && v !== undefined ) {
				cleanParams[ k ] = v;
			}
		}

		const auth = { type: authType, ...authExtra };

		try {
			const res = await fetch( `${ homeUrl }/rest-api-explorer/v1/test-request`, {
				method: 'POST',
				headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': nonce },
				body: JSON.stringify( {
					method,
					path: route.path,
					params: hasBody ? {} : cleanParams,
					body: parsedBody,
					headers: {},
					auth,
				} ),
			} );

			const data = await res.json();
			onResponse( data );

			// Add to history
			if ( onAddHistory ) {
				onAddHistory( {
					id:         `${ Date.now() }-${ Math.random().toString( 36 ).slice( 2 ) }`,
					timestamp:  Date.now(),
					method,
					path:       route.path,
					params:     hasBody ? {} : cleanParams,
					body:       parsedBody,
					auth,
					statusCode: data.status_code ?? null,
					elapsedMs:  data.elapsed_ms ?? 0,
				} );
			}
		} catch ( err ) {
			onResponse( { success: false, error: err.message || 'Network error', elapsed_ms: 0 } );
		} finally {
			setLoading( false );
		}
	};

	const handleSave = () => {
		if ( onSaveFavorite ) {
			const cleanParams = {};
			for ( const [ k, v ] of Object.entries( params ) ) {
				if ( v !== '' && v !== null && v !== undefined ) cleanParams[ k ] = v;
			}
			onSaveFavorite( {
				method,
				path:   route.path,
				params: cleanParams,
				body:   hasBody ? ( bodyMode === 'raw' ? body : params ) : {},
				auth:   { type: authType, ...authExtra },
			} );
		}
	};

	return (
		<div className="rae-builder">
			<h3 className="rae-section-title">Request Builder</h3>

			{/* Method + URL bar */}
			<div className="rae-builder__url-bar">
				<select
					className="rae-select rae-builder__method"
					value={ method }
					onChange={ ( e ) => setMethod( e.target.value ) }
				>
					{ METHODS.filter( ( m ) => endpoint.methods.includes( m ) ).map( ( m ) => (
						<option key={ m } value={ m }>{ m }</option>
					) ) }
				</select>
				<div className="rae-builder__url">
					<span className="rae-builder__url-base">{ homeUrl }</span>
					<span className="rae-builder__url-path">{ route.path }</span>
				</div>
			</div>

			{/* Auth */}
			<div className="rae-builder__section">
				<label className="rae-label">Auth</label>
				<select
					className="rae-select"
					value={ authType }
					onChange={ ( e ) => setAuthType( e.target.value ) }
				>
					{ AUTH_TYPES.map( ( a ) => (
						<option key={ a.value } value={ a.value }>{ a.label }</option>
					) ) }
				</select>
				{ authType === 'basic' && (
					<div className="rae-builder__auth-fields">
						<input
							type="text"
							className="rae-input"
							placeholder="Username"
							value={ authExtra.username }
							onChange={ ( e ) => setAuthExtra( { ...authExtra, username: e.target.value } ) }
						/>
						<input
							type="password"
							className="rae-input"
							placeholder="Password"
							value={ authExtra.password }
							onChange={ ( e ) => setAuthExtra( { ...authExtra, password: e.target.value } ) }
						/>
					</div>
				) }
				{ authType === 'bearer' && (
					<input
						type="text"
						className="rae-input"
						placeholder="Bearer token"
						value={ authExtra.token }
						onChange={ ( e ) => setAuthExtra( { ...authExtra, token: e.target.value } ) }
					/>
				) }
				{ authType === 'cookie' && (
					<p className="rae-hint">Sends X-WP-Nonce for the currently logged-in user.</p>
				) }
			</div>

			{/* Params */}
			{ queryArgs.length > 0 && (
				<div className="rae-builder__section">
					<label className="rae-label">
						{ hasBody ? 'Query Parameters' : 'Parameters' }
					</label>
					<div className="rae-builder__params">
						{ queryArgs.map( ( arg ) => (
							<ParamField
								key={ arg.name }
								arg={ arg }
								value={ params[ arg.name ] ?? '' }
								onChange={ handleParamChange }
							/>
						) ) }
					</div>
				</div>
			) }

			{/* Body */}
			{ hasBody && (
				<div className="rae-builder__section">
					<div className="rae-builder__section-header">
						<label className="rae-label">Request Body</label>
						<div className="rae-toggle">
							<button
								className={ `rae-toggle__btn ${ bodyMode === 'form' ? 'rae-toggle__btn--active' : '' }` }
								onClick={ () => setBodyMode( 'form' ) }
								type="button"
							>
								Form
							</button>
							<button
								className={ `rae-toggle__btn ${ bodyMode === 'raw' ? 'rae-toggle__btn--active' : '' }` }
								onClick={ () => setBodyMode( 'raw' ) }
								type="button"
							>
								Raw JSON
							</button>
						</div>
					</div>

					{ bodyMode === 'form' ? (
						bodyArgs.length > 0 ? (
							<div className="rae-builder__params">
								{ bodyArgs.map( ( arg ) => (
									<ParamField
										key={ arg.name }
										arg={ arg }
										value={ params[ arg.name ] ?? '' }
										onChange={ handleParamChange }
									/>
								) ) }
							</div>
						) : (
							<p className="rae-hint">No documented body parameters for this endpoint.</p>
						)
					) : (
						<textarea
							className="rae-textarea"
							rows={ 6 }
							value={ body }
							onChange={ ( e ) => setBody( e.target.value ) }
							placeholder="{ }"
							spellCheck={ false }
						/>
					) }
				</div>
			) }

			<div className="rae-builder__actions">
				<button
					className="rae-btn rae-btn--primary"
					onClick={ handleSend }
					disabled={ loading }
					type="button"
				>
					{ loading ? 'Sending…' : 'Send Request' }
				</button>
				{ onSaveFavorite && (
					<button
						className="rae-btn rae-btn--secondary"
						onClick={ handleSave }
						type="button"
					>
						Save
					</button>
				) }
			</div>
		</div>
	);
}

function ParamField( { arg, value, onChange } ) {
	const inputType = arg.type === 'integer' || arg.type === 'number' ? 'number' : 'text';

	return (
		<div className="rae-param-field">
			<label className="rae-param-field__label">
				<span className="rae-param-field__name">{ arg.name }</span>
				{ arg.required && <span className="rae-required">*</span> }
				<span className="rae-param-field__type">{ arg.type }</span>
			</label>

			{ arg.enum ? (
				<select
					className="rae-select"
					value={ value }
					onChange={ ( e ) => onChange( arg.name, e.target.value ) }
				>
					<option value="">— select —</option>
					{ arg.enum.map( ( v ) => (
						<option key={ v } value={ v }>{ String( v ) }</option>
					) ) }
				</select>
			) : arg.type === 'boolean' ? (
				<select
					className="rae-select"
					value={ value }
					onChange={ ( e ) => onChange( arg.name, e.target.value ) }
				>
					<option value="">— select —</option>
					<option value="true">true</option>
					<option value="false">false</option>
				</select>
			) : (
				<input
					type={ inputType }
					className="rae-input"
					value={ value }
					onChange={ ( e ) => onChange( arg.name, e.target.value ) }
					placeholder={ arg.description || arg.name }
					min={ arg.minimum ?? undefined }
					max={ arg.maximum ?? undefined }
				/>
			) }

			{ arg.description && (
				<span className="rae-param-field__desc">{ arg.description }</span>
			) }
		</div>
	);
}
