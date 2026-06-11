import { createRoot } from '@wordpress/element';
import App from './app';

const container = document.getElementById( 'rest-api-explorer-app' );
if ( container && window.restApiExplorer ) {
	const { routes, homeUrl, ajaxUrl, clearNonce, nonce } = window.restApiExplorer;
	createRoot( container ).render(
		<App
			initialRoutes={ routes }
			homeUrl={ homeUrl }
			ajaxUrl={ ajaxUrl }
			clearNonce={ clearNonce }
			nonce={ nonce }
		/>
	);
}
