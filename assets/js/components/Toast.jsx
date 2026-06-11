import { useEffect, useRef } from '@wordpress/element';

const ICONS = { success: '✓', error: '✕', info: 'ℹ' };

function ToastItem( { toast, onRemove } ) {
	const timerRef = useRef( null );

	const dismiss = () => {
		clearTimeout( timerRef.current );
		onRemove( toast.id );
	};

	return (
		<div
			className={ `rae-toast rae-toast--${ toast.type }` }
			role="alert"
			aria-live="polite"
		>
			<span className="rae-toast__icon">{ ICONS[ toast.type ] }</span>
			<span className="rae-toast__message">{ toast.message }</span>
			<button
				className="rae-toast__close"
				onClick={ dismiss }
				type="button"
				aria-label="Dismiss"
			>
				✕
			</button>
		</div>
	);
}

export default function ToastStack( { toasts, onRemove } ) {
	if ( ! toasts.length ) return null;

	return (
		<div className="rae-toast-stack" aria-label="Notifications">
			{ toasts.map( ( t ) => (
				<ToastItem key={ t.id } toast={ t } onRemove={ onRemove } />
			) ) }
		</div>
	);
}
