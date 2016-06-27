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
	
	const TYPE_CONSOLE = 1;
	const TYPE_HTTP = 2;
	
	/**
	 * @var String
	 */
	protected static $handlerClasses;
	// We kept a string to get it lighter, there is no need of more feature for that
	
	public static function suggestHandler($type, $class) {
		if( !isset(static::$handlerClasses[$type]) ) {
			static::setHandler($class);
		}
	}
	
	public static function setHandler($type, $class) {
		if( !method_exists($class, 'getRoute') ) {
			// Check getCurrentRoute
			throw new \Exception('The route handler class '.$class.' does not implement the getRoute() method');
		}
		static::$handlerClasses = $class;
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

