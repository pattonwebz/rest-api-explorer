import { useState, useCallback } from '@wordpress/element';

let nextId = 0;

export function useToast() {
	const [ toasts, setToasts ] = useState( [] );

	const addToast = useCallback( ( message, type = 'info', duration = 4000 ) => {
		const id = ++nextId;
		setToasts( ( prev ) => [ ...prev, { id, message, type } ] );
		if ( duration > 0 ) {
			setTimeout( () => {
				setToasts( ( prev ) => prev.filter( ( t ) => t.id !== id ) );
			}, duration );
		}
		return id;
	}, [] );

	const removeToast = useCallback( ( id ) => {
		setToasts( ( prev ) => prev.filter( ( t ) => t.id !== id ) );
	}, [] );

	const toast = {
		success: ( msg, duration ) => addToast( msg, 'success', duration ),
		error:   ( msg, duration ) => addToast( msg, 'error',   duration ?? 6000 ),
		info:    ( msg, duration ) => addToast( msg, 'info',    duration ),
	};

	return { toasts, toast, removeToast };
}
