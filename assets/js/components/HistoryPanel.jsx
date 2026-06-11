import { useState } from '@wordpress/element';
import { MethodBadge } from './RouteItem';

function statusClass( code ) {
	if ( ! code ) return 'none';
	if ( code >= 200 && code < 300 ) return 'ok';
	if ( code >= 400 && code < 500 ) return 'client-err';
	if ( code >= 500 ) return 'server-err';
	return 'other';
}

function formatTime( ts ) {
	return new Date( ts ).toLocaleTimeString( [], { hour: '2-digit', minute: '2-digit', second: '2-digit' } );
}

export default function HistoryPanel( { history, onLoad, onClear } ) {
	const [ search, setSearch ] = useState( '' );

	const filtered = search
		? history.filter( ( item ) => item.path.toLowerCase().includes( search.toLowerCase() ) )
		: history;

	return (
		<div className="rae-fullpanel">
			<div className="rae-fullpanel__header">
				<div className="rae-fullpanel__title">
					<h2>Request History</h2>
					<span className="rae-badge">{ history.length }</span>
				</div>
				{ history.length > 0 && (
					<button className="rae-btn rae-btn--secondary rae-btn--sm" onClick={ onClear } type="button">
						Clear All
					</button>
				) }
			</div>

			{ history.length > 0 && (
				<div className="rae-fullpanel__search">
					<input
						type="search"
						className="rae-search"
						placeholder="Filter by path…"
						value={ search }
						onChange={ ( e ) => setSearch( e.target.value ) }
					/>
				</div>
			) }

			{ filtered.length === 0 ? (
				<div className="rae-fullpanel__empty">
					{ history.length === 0
						? 'No requests yet — send one from the Routes tab.'
						: 'No history matches your filter.' }
				</div>
			) : (
				<div className="rae-history-list">
					{ filtered.map( ( item ) => (
						<button
							key={ item.id }
							className="rae-history-item"
							onClick={ () => onLoad( item ) }
							type="button"
							title="Click to re-open in request builder"
						>
							<div className="rae-history-item__row">
								<MethodBadge method={ item.method } />
								<span className={ `rae-status-code rae-status-code--${ statusClass( item.statusCode ) }` }>
									{ item.statusCode || '—' }
								</span>
								<span className="rae-history-item__timing">{ item.elapsedMs }ms</span>
								<span className="rae-history-item__time">{ formatTime( item.timestamp ) }</span>
							</div>
							<div className="rae-history-item__path">{ item.path }</div>
						</button>
					) ) }
				</div>
			) }
		</div>
	);
}
