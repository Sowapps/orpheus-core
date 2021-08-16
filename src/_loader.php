<?php
/**
 * Loader File for the core sources
 */

if( !defined('ORPHEUSPATH') ) {
	// Do not load in a non-orpheus environment
	return;
}

defifn('LOGFILE_SYSTEM', 'system.log');
defifn('LOGFILE_DEBUG', 'debug.log');
defifn('LOGFILE_HACK', 'hack.log');
defifn('LOGFILE_SQL', 'sql.log');

defifn('DOMAIN_LOGS', 'logs');

require_once 'core.php';
require_once 'hooks.php';
require_once 'validators.php';
