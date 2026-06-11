import { useState } from '@wordpress/element';
import { MethodBadge } from './RouteItem';

export default function FavoritesPanel( { favorites, loading, onLoad, onDelete, onRename } ) {
	const [ editingId, setEditingId ]   = useState( null );
	const [ editName, setEditName ]     = useState( '' );
	const [ editFolder, setEditFolder ] = useState( '' );
	const [ search, setSearch ]         = useState( '' );

	const filtered = search
		? favorites.filter(
				( f ) =>
					f.name.toLowerCase().includes( search.toLowerCase() ) ||
					f.path.toLowerCase().includes( search.toLowerCase() )
		  )
		: favorites;

	// Group by folder
	const grouped = filtered.reduce( ( acc, fav ) => {
		const key = fav.folder || '— No Folder —';
		( acc[ key ] = acc[ key ] || [] ).push( fav );
		return acc;
	}, {} );

	const startEdit = ( fav ) => {
		setEditingId( fav.id );
		setEditName( fav.name );
		setEditFolder( fav.folder );
	};

	const cancelEdit = () => setEditingId( null );

	const submitEdit = ( id ) => {
		onRename( id, editName.trim(), editFolder.trim() );
		setEditingId( null );
	};

	if ( loading ) {
		return (
			<div className="rae-fullpanel">
				<div className="rae-fullpanel__header">
					<div className="rae-fullpanel__title"><h2>Favorites</h2></div>
				</div>
				<div className="rae-fullpanel__empty">Loading favorites…</div>
			</div>
		);
	}

	return (
		<div className="rae-fullpanel">
			<div className="rae-fullpanel__header">
				<div className="rae-fullpanel__title">
					<h2>Favorites</h2>
					<span className="rae-badge">{ favorites.length }</span>
				</div>
			</div>

			{ favorites.length > 4 && (
				<div className="rae-fullpanel__search">
					<input
						type="search"
						className="rae-search"
						placeholder="Filter favorites…"
						value={ search }
						onChange={ ( e ) => setSearch( e.target.value ) }
					/>
				</div>
			) }

			{ filtered.length === 0 ? (
				<div className="rae-fullpanel__empty">
					{ favorites.length === 0
						? 'No favorites yet — save a request from the builder.'
						: 'No favorites match your filter.' }
				</div>
			) : (
				<div className="rae-favorites-list">
					{ Object.entries( grouped ).map( ( [ folder, items ] ) => (
						<div key={ folder } className="rae-favorites-folder">
							<div className="rae-favorites-folder__name">{ folder }</div>
							{ items.map( ( fav ) =>
								editingId === fav.id ? (
									<div key={ fav.id } className="rae-fav-item rae-fav-item--editing">
										<input
											type="text"
											className="rae-input rae-input--sm"
											value={ editName }
											onChange={ ( e ) => setEditName( e.target.value ) }
											placeholder="Name"
										/>
										<input
											type="text"
											className="rae-input rae-input--sm"
											value={ editFolder }
											onChange={ ( e ) => setEditFolder( e.target.value ) }
											placeholder="Folder"
										/>
										<div className="rae-fav-item__edit-actions">
											<button
												className="rae-btn rae-btn--primary rae-btn--sm"
												onClick={ () => submitEdit( fav.id ) }
												type="button"
											>
												Save
											</button>
											<button
												className="rae-btn rae-btn--secondary rae-btn--sm"
												onClick={ cancelEdit }
												type="button"
											>
												Cancel
											</button>
										</div>
									</div>
								) : (
									<div key={ fav.id } className="rae-fav-item">
										<button
											className="rae-fav-item__load"
											onClick={ () => onLoad( fav ) }
											type="button"
											title="Load into request builder"
										>
											<MethodBadge method={ fav.method } />
											<span className="rae-fav-item__name">{ fav.name }</span>
											<span className="rae-fav-item__path">{ fav.path }</span>
										</button>
										<div className="rae-fav-item__actions">
											<button
												className="rae-icon-btn"
												onClick={ () => startEdit( fav ) }
												type="button"
												title="Rename / move"
											>
												✎
											</button>
											<button
												className="rae-icon-btn rae-icon-btn--danger"
												onClick={ () => onDelete( fav.id ) }
												type="button"
												title="Delete"
											>
												✕
											</button>
										</div>
									</div>
								)
							) }
						</div>
					) ) }
				</div>
			) }
		</div>
	);
}
