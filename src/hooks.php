<?php
/**
 * The core hooks
 *
 * Declare some core hooks
 */

use Orpheus\Hook\Hook;

/**
 * Callback for Hook 'runModule'
 */
Hook::register(HOOK_RUNMODULE, function ($Module) {
	if( defined('TERMINAL') ) {
		return;
	}
	$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
	$isNotRoot = !empty($path) && $path[strlen($path) - 1] != '/';
	
	//If user try to override url rewriting and the requested page is not root.
	if( $Module !== 'remote' && empty($_SERVER['REDIRECT_rewritten']) && empty($_SERVER['REDIRECT_URL']) && $isNotRoot ) {
		permanentRedirectTo(u($Module));
	}
	// If the module is the default but with wrong link.
	// REDIRECT_rewritten is essential to allow rewritten url to default mod
	if( $Module === DEFAULT_ROUTE && empty($GLOBALS['Action']) && empty($_SERVER['REDIRECT_rewritten']) && $isNotRoot ) {
		permanentRedirectTo(WEB_ROOT);
	}
});
