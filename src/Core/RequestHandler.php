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
	
	/**
	 * @var String
	 */
	protected static $handlerClass;
	// We kept a string to get it lighter, there is no need of more feature for that
	
	public static function suggestHandler($class) {
		if( !static::$handlerClass ) {
			static::setHandler($class);
		}
	}
	
	public static function setHandler($class) {
		if( !method_exists($class, 'getRoute') ) {
			// Check getCurrentRoute
			throw new \Exception('The route handler class '.$class.' does not implement the getRoute() method');
		}
		static::$handlerClass = $class;
	}

	/**
	 * Get the current main route name
	 * 
	 * @return srting
	 * @throws \Exception
	 */
	public static function handleCurrentRequest() {
		if( !static::$handlerClass ) {
			throw new \Exception('We did not find any request handler');
		}
		$class = static::$handlerClass;
		return $class::handleCurrentRequest();
	}
	
}

