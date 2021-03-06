<?php
/**
 * Route
 */

namespace Orpheus\Core;

/**
 * Official Route for Orpheus
 *
 * @author Florent HAZARD <florent@orpheus-framework.com>
 * 
 * We should also implement a service system to allow a class/object to provide
 * a feature that is required by other lib without they are knowning the lib implementing it
 */
abstract class Route {
	
	/**
	 * Test if the route is accessible in the current context using restrictTo
	 * This method is sensitive to CHECK_MODULE_ACCESS constant, a true value make it always accessible, never use it in production
	 * 
	 * @return boolean
	 */
	public abstract function isAccessible();
	
	/**
	 * Get the link to this route
	 * 
	 * @return boolean
	 */
	public abstract function getLink();
	
	/**
	 * Get the name
	 * 
	 * @return string
	 */
	public abstract function getName();
	
	/**
	 * Class of the resolver
	 * 
	 * @var string
	 */
	protected static $resolverClass;
	// We kept a string to get it lighter, there is no need of more feature for that

	/**
	 * Suggest resolve $class
	 * 
	 * @param string $class
	 * @see setResolver()
	 *
	 * The difference with setResolver() is that only set if there is no current value
	 */
	public static function suggestResolver($class) {
		if( !static::$resolverClass ) {
			static::setResolver($class);
		}
	}

	/**
	 * Set the resolver class
	 *
	 * @param string $class
	 */
	public static function setResolver($class) {
		if( !method_exists($class, 'getRoute') ) {
			// Check getCurrentRoute
			throw new \Exception('The route resolver class '.$class.' does not implement the getRoute() method');
		}
		static::$resolverClass = $class;
	}

	/**
	 * Get Route object for this name
	 * 
	 * @param string $name
	 * @return Route
	 * @throws \Exception
	 */
	public static function getRoute($name) {
		if( !static::$resolverClass ) {
			throw new \Exception('We did not find any route resolver');
		}
		$class = static::$resolverClass;
		return $class::getRoute($name);
	}

	/**
	 * Get the current main route name
	 * 
	 * @return srting
	 * @throws \Exception
	 */
	public static function getCurrentRouteName() {
		if( !static::$resolverClass ) {
			throw new \Exception('We did not find any route resolver');
		}
		$class = static::$resolverClass;
		return $class::getCurrentRouteName();
	}
	
}

