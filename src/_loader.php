<?php
/**
 * Loader File for the core sources
 */

if( !defined('ORPHEUSPATH') ) {
	// Do not load in a non-orpheus environment
	return;
}

require_once 'core.php';
require_once 'hooks.php';
require_once 'validators.php';
require_once 'exceptions.php';
