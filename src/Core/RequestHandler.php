<?php
/**
 * RequestHandler
 */

namespace Orpheus\Core;

use Exception;

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
	 * Handler classes by type
	 *
	 * @var string
	 */
	protected static $handlerClasses = array();
	// We kept a string to get it lighter, there is no need of more feature for that
	
	/**
	 * Suggest handle $class for $type
	 *
	 * @param string $type
	 * @param string $class
	 * @see setHandler()
	 *
	 * The difference with setHandler() is that only set if there is no current value
	 */
	public static function suggestHandler($type, $class) {
		if( !isset(static::$handlerClasses[$type]) ) {
			static::setHandler($type, $class);
		}
	}
	
	/**
	 * Set handle $class for $type
	 *
	 * @param string $type
	 * @param string $class
	 */
	public static function setHandler($type, $class) {
		if( !method_exists($class, 'handleCurrentRequest') ) {
			// Check getCurrentRoute
			throw new Exception('The request handler class ' . $class . ' does not implement the handleCurrentRequest() method');
		}
		static::$handlerClasses[$type] = $class;
	}
	
	/**
	 * Get the handler of $type
	 *
	 * @param string $type
	 * @return string
	 * @throws Exception
	 */
	public static function getHandler($type) {
		if( !isset(static::$handlerClasses[$type]) ) {
			throw new Exception('We did not find any request handler for type ' . $type);
		}
		return static::$handlerClasses[$type];
	}
	
	/**
	 * Get the Route Class
	 *
	 * @param string $type
	 * @return string
	 * @throws Exception
	 */
	public static function getRouteClass($type) {
		$class = static::getHandler($type);
		return $class::getRouteClass();
	}
	
	/**
	 * Handle the current request
	 *
	 * @param string $type
	 * @return string
	 * @throws Exception
	 */
	public static function handleCurrentRequest($type) {
		$class = static::getHandler($type);
		return $class::handleCurrentRequest();
	}
	
}

