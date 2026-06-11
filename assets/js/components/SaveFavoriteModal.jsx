import { useState, useEffect, useRef } from '@wordpress/element';

const METHOD_COLORS = { GET: '#00a32a', POST: '#0073aa', PUT: '#e67e00', PATCH: '#6f42c1', DELETE: '#cc1818' };
const methodColor   = ( m ) => METHOD_COLORS[ m ] || '#646970';

function getFocusable( container ) {
	return [
		...container.querySelectorAll(
			'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
		),
	];
}

export default function SaveFavoriteModal( { requestData, existingFolders, onSave, onClose } ) {
	const [ name, setName ]             = useState( '' );
	const [ folder, setFolder ]         = useState( '' );
	const [ newFolder, setNewFolder ]   = useState( '' );
	const [ mode, setMode ]             = useState( existingFolders.length > 0 ? 'existing' : 'new' );
	const [ saving, setSaving ]         = useState( false );
	const [ error, setError ]           = useState( '' );
	const nameRef   = useRef( null );
	const modalRef  = useRef( null );
	const prevFocus = useRef( null );

	useEffect( () => {
		prevFocus.current = document.activeElement;
		nameRef.current?.focus();
		return () => { prevFocus.current?.focus(); };
	}, [] );

	const handleKeyDown = ( e ) => {
		if ( e.key === 'Escape' ) {
			e.preventDefault();
			onClose();
			return;
		}
		if ( e.key !== 'Tab' || ! modalRef.current ) return;
		const focusable = getFocusable( modalRef.current );
		if ( ! focusable.length ) return;
		const first = focusable[ 0 ];
		const last  = focusable[ focusable.length - 1 ];
		if ( e.shiftKey && document.activeElement === first ) {
			e.preventDefault();
			last.focus();
		} else if ( ! e.shiftKey && document.activeElement === last ) {
			e.preventDefault();
			first.focus();
		}
	};

	const effectiveFolder = mode === 'new' ? newFolder.trim() : folder;

	const handleSubmit = async ( e ) => {
		e.preventDefault();
		if ( ! name.trim() ) return;
		setSaving( true );
		setError( '' );
		try {
			await onSave( name.trim(), effectiveFolder );
		} catch {
			setError( 'Failed to save — please try again.' );
			setSaving( false );
		}
	};

	return (
		<div
			className="rae-modal-overlay"
			onClick={ onClose }
			onKeyDown={ handleKeyDown }
			role="presentation"
		>
			<div
				ref={ modalRef }
				className="rae-modal"
				onClick={ ( e ) => e.stopPropagation() }
				role="dialog"
				aria-modal="true"
				aria-labelledby="rae-modal-title"
			>
				<div className="rae-modal__header">
					<h3 id="rae-modal-title">Save Request</h3>
					<button className="rae-modal__close" onClick={ onClose } type="button" aria-label="Close dialog">✕</button>
				</div>

				<div className="rae-modal__preview">
					<span
						className="rae-method-badge"
						style={ { backgroundColor: methodColor( requestData.method ) } }
					>
						{ requestData.method }
					</span>
					<code>{ requestData.path }</code>
				</div>

				<form className="rae-modal__body" onSubmit={ handleSubmit }>
					<label className="rae-label" htmlFor="rae-fav-name">Name</label>
					<input
						id="rae-fav-name"
						ref={ nameRef }
						type="text"
						className="rae-input"
						placeholder="e.g. Get all posts"
						value={ name }
						onChange={ ( e ) => setName( e.target.value ) }
						required
						autoComplete="off"
					/>

					<label className="rae-label" style={ { marginTop: 12 } }>Folder</label>

					{ existingFolders.length > 0 && (
						<div className="rae-folder-row" role="group" aria-label="Folder mode">
							<button
								type="button"
								className={ `rae-toggle__btn ${ mode === 'existing' ? 'rae-toggle__btn--active' : '' }` }
								onClick={ () => setMode( 'existing' ) }
								aria-pressed={ mode === 'existing' }
							>
								Existing
							</button>
							<button
								type="button"
								className={ `rae-toggle__btn ${ mode === 'new' ? 'rae-toggle__btn--active' : '' }` }
								onClick={ () => setMode( 'new' ) }
								aria-pressed={ mode === 'new' }
							>
								New
							</button>
						</div>
					) }

					{ mode === 'existing' && existingFolders.length > 0 ? (
						<select
							className="rae-select"
							value={ folder }
							onChange={ ( e ) => setFolder( e.target.value ) }
							aria-label="Choose folder"
						>
							<option value="">No folder</option>
							{ existingFolders.map( ( f ) => (
								<option key={ f } value={ f }>{ f }</option>
							) ) }
						</select>
					) : (
						<input
							type="text"
							className="rae-input"
							placeholder="Folder name (optional)"
							value={ newFolder }
							onChange={ ( e ) => setNewFolder( e.target.value ) }
							autoComplete="off"
							aria-label="New folder name"
						/>
					) }

					{ error && (
						<p className="rae-modal__error" role="alert">{ error }</p>
					) }

					<div className="rae-modal__actions">
						<button type="button" className="rae-btn rae-btn--secondary" onClick={ onClose }>
							Cancel
						</button>
						<button type="submit" className="rae-btn rae-btn--primary" disabled={ ! name.trim() || saving }>
							{ saving ? 'Saving…' : 'Save' }
						</button>
					</div>
				</form>
			</div>
		</div>
	);
}
