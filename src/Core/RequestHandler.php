<?php

namespace Orpheus\Core;

/**
 * Official RequestHandler for Orpheus
 *
 * @author Florent HAZARD <florent@orpheus-framework.com>
 * 
 * We should also implement a service system to allow a class/object to provide
 * a feature that is required by other lib without they are knowning the lib implementing it
 */
abstract class RequestHandler {
	
	const TYPE_CONSOLE = 'cli';
	const TYPE_HTTP = 'http';
	
	/**
	 * @var String
	 */
	protected static $handlerClasses = array();
	// We kept a string to get it lighter, there is no need of more feature for that
	
	public static function suggestHandler($type, $class) {
		if( !isset(static::$handlerClasses[$type]) ) {
			static::setHandler($type, $class);
		}
	}
	
	public static function setHandler($type, $class) {
		if( !method_exists($class, 'handleCurrentRequest') ) {
			// Check getCurrentRoute
			throw new \Exception('The request handler class '.$class.' does not implement the handleCurrentRequest() method');
		}
		static::$handlerClasses[$type] = $class;
	}

	/**
	 * Get the current main route name
	 * 
	 * @param int $type
	 * @return string
	 * @throws \Exception
	 */
	public static function handleCurrentRequest($type) {
		if( !isset(static::$handlerClasses[$type]) ) {
			throw new \Exception('We did not find any request handler for type '.$type);
		}
		$class = static::$handlerClasses[$type];
		return $class::handleCurrentRequest();
	}
	
}

