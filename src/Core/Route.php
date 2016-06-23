<?php

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
	
	public function isAccessible();
	public function getLink();
	
	/**
	 * @var String
	 */
	protected static $resolverClass;
	// We kept a string to get it lighter, there is no need of more feature for that
	
	public static function suggestResolver($class) {
		if( !static::$resolverClass ) {
			static::setResolver($class);
		}
	}
	
	public static function setResolver($class) {
		if( !method_exists($class, 'getRoute') ) {
			throw new \Exception('The route resolver class '.$class.' does not implement the getRoute() method');
		}
		static::$resolverClass = $class;
	}

	/**
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
	
}

