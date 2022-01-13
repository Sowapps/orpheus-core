<?php
/**
 * Route
 */

namespace Orpheus\Core;

use Exception;
use Orpheus\InputController\ControllerRoute;
use RuntimeException;

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
	 * Class of the resolver
	 *
	 * @var string
	 */
	protected static ?string $resolverClass = null;
	
	/**
	 * Test if the route is accessible in the current context using restrictTo
	 * This method is sensitive to CHECK_MODULE_ACCESS constant, a true value make it always accessible, never use it in production
	 *
	 * @return boolean
	 */
	public abstract function isAccessible(): bool;
	
	/**
	 * Get the link to this route
	 *
	 * @return boolean
	 */
	public abstract function getLink(): bool;
	
	/**
	 * Get the name
	 *
	 * @return string
	 */
	public abstract function getName(): string;
	// We kept a string to get it lighter, there is no need of more feature for that
	
	/**
	 * Suggest resolve $class
	 *
	 * @param string $class
	 * @see setResolver()
	 *
	 * The difference with setResolver() is that only set if there is no current value
	 */
	public static function suggestResolver(string $class) {
		if( !static::$resolverClass ) {
			static::setResolver($class);
		}
	}
	
	/**
	 * Set the resolver class
	 *
	 * @param string $class
	 */
	public static function setResolver(string $class) {
		if( !method_exists($class, 'getRoute') ) {
			// Check getCurrentRoute
			throw new RuntimeException('The route resolver class ' . $class . ' does not implement the getRoute() method');
		}
		static::$resolverClass = $class;
	}
	
	/**
	 * Get Route object for this name
	 *
	 * @param string $name
	 * @return Route|null
	 * @throws Exception
	 */
	public static function getRoute(string $name): ?Route {
		if( !static::$resolverClass ) {
			throw new Exception('We did not find any route resolver');
		}
		/** @var ControllerRoute $class */
		$class = static::$resolverClass;
		
		return $class::getRoute($name);
	}
	
	/**
	 * Get the current main route name
	 *
	 * @return string
	 * @throws Exception
	 */
	public static function getCurrentRouteName(): string {
		if( !static::$resolverClass ) {
			throw new Exception('We did not find any route resolver');
		}
		/** @var ControllerRoute $class */
		$class = static::$resolverClass;
		
		return $class::getCurrentRouteName();
	}
	
}

