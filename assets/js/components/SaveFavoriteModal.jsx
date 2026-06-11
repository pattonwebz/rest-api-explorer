import { useState, useEffect, useRef } from '@wordpress/element';

export default function SaveFavoriteModal( { requestData, existingFolders, onSave, onClose } ) {
	const [ name, setName ]         = useState( '' );
	const [ folder, setFolder ]     = useState( '' );
	const [ newFolder, setNewFolder ] = useState( '' );
	const [ mode, setMode ]         = useState( 'existing' ); // 'existing' | 'new'
	const [ saving, setSaving ]     = useState( false );
	const nameRef = useRef( null );

	useEffect( () => {
		nameRef.current?.focus();
	}, [] );

	const effectiveFolder = mode === 'new' ? newFolder.trim() : folder;

	const handleSubmit = async ( e ) => {
		e.preventDefault();
		if ( ! name.trim() ) return;
		setSaving( true );
		await onSave( name.trim(), effectiveFolder );
		setSaving( false );
	};

	const handleKeyDown = ( e ) => {
		if ( e.key === 'Escape' ) onClose();
	};

	return (
		<div className="rae-modal-overlay" onClick={ onClose } onKeyDown={ handleKeyDown } role="dialog" aria-modal="true">
			<div className="rae-modal" onClick={ ( e ) => e.stopPropagation() }>
				<div className="rae-modal__header">
					<h3>Save Request</h3>
					<button className="rae-modal__close" onClick={ onClose } type="button" aria-label="Close">✕</button>
				</div>

				<div className="rae-modal__preview">
					<span className="rae-method-badge" style={ { backgroundColor: methodColor( requestData.method ) } }>
						{ requestData.method }
					</span>
					<code>{ requestData.path }</code>
				</div>

				<form className="rae-modal__body" onSubmit={ handleSubmit }>
					<label className="rae-label">Name</label>
					<input
						ref={ nameRef }
						type="text"
						className="rae-input"
						placeholder="e.g. Get all posts"
						value={ name }
						onChange={ ( e ) => setName( e.target.value ) }
						required
					/>

					<label className="rae-label" style={ { marginTop: 12 } }>Folder</label>
					<div className="rae-folder-row">
						{ existingFolders.length > 0 && (
							<>
								<button
									type="button"
									className={ `rae-toggle__btn ${ mode === 'existing' ? 'rae-toggle__btn--active' : '' }` }
									onClick={ () => setMode( 'existing' ) }
								>
									Existing
								</button>
								<button
									type="button"
									className={ `rae-toggle__btn ${ mode === 'new' ? 'rae-toggle__btn--active' : '' }` }
									onClick={ () => setMode( 'new' ) }
								>
									New
								</button>
							</>
						) }
					</div>

					{ mode === 'existing' && existingFolders.length > 0 ? (
						<select
							className="rae-select"
							value={ folder }
							onChange={ ( e ) => setFolder( e.target.value ) }
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
						/>
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

const METHOD_COLORS = { GET: '#00a32a', POST: '#0073aa', PUT: '#e67e00', PATCH: '#6f42c1', DELETE: '#cc1818' };
const methodColor = ( m ) => METHOD_COLORS[ m ] || '#646970';
