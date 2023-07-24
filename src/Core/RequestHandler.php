<?php
/**
 * RequestHandler
 */

namespace Orpheus\Core;

use Orpheus\InputController\InputRequest;
use RuntimeException;

/**
 * Official RequestHandler for Orpheus
 *
 * @author Florent HAZARD <f.hazard@sowapps.com>
 *
 * We should also implement a service system to allow a class/object to provide
 * a feature that is required by other lib without they are knowning the lib implementing it
 */
abstract class RequestHandler {
	
	const TYPE_CONSOLE = 'cli';
	const TYPE_HTTP = 'http';
	
	/**
	 * Handler classes by type
	 */
	protected static array $handlerClasses = [];
	
	/**
	 * Suggest handle $class for $type
	 *
	 * @param string $type
	 * @param string $class
	 * @see setHandler()
	 *
	 * The difference with setHandler() is that only set if there is no current value
	 */
	public static function suggestHandler(string $type, string $class): void {
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
	public static function setHandler(string $type, string $class): void {
		if( !method_exists($class, 'handleCurrentRequest') ) {
			// Check getCurrentRoute
			throw new RuntimeException(sprintf('The request handler class %s does not implement the handleCurrentRequest() method', $class));
		}
		static::$handlerClasses[$type] = $class;
	}
	
	/**
	 * Get the handler of $type
	 *
	 * @param string $type
	 * @return string
	 */
	public static function getHandler(string $type): string {
		if( !isset(static::$handlerClasses[$type]) ) {
			throw new RuntimeException(sprintf('We did not find any request handler for type %s', $type));
		}
		
		return static::$handlerClasses[$type];
	}
	
	/**
	 * Get the Route Class
	 *
	 * @param string $type
	 * @return string
	 */
	public static function getRouteClass(string $type): string {
		/** @var InputRequest $class */
		$class = static::getHandler($type);
		
		return $class::getRouteClass();
	}
	
	/**
	 * Handle the current request
	 *
	 * @param string $type
	 */
	public static function handleCurrentRequest(string $type) {
		/** @var InputRequest $class */
		$class = static::getHandler($type);
		
		$class::handleCurrentRequest();
	}
	
}

