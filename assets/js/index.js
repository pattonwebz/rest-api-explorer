import { createRoot } from '@wordpress/element';
import App from './app';

const container = document.getElementById( 'rest-api-explorer-app' );
if ( container && window.restApiExplorer ) {
	createRoot( container ).render(
		<App
			initialRoutes={ window.restApiExplorer.routes }
			homeUrl={ window.restApiExplorer.homeUrl }
			ajaxUrl={ window.restApiExplorer.ajaxUrl }
			clearNonce={ window.restApiExplorer.clearNonce }
		/>
	);
}
