<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Orpheus\Exception;

use Orpheus\InputController\ControllerRoute;
use Throwable;

/**
 * The Forbidden Route Exception class
 *
 * This exception is thrown when accessing a route that require an authentication or more permissions.
 */
class ForbiddenRouteException extends ForbiddenException {
	
	private ControllerRoute $route;
	
	/**
	 * Constructor
	 *
	 * @param string|null $message The exception message
	 * @param string|null $domain The domain for the message
	 * @param Throwable|null $previous The previous exception
	 */
	public function __construct(ControllerRoute $route, ?string $message = null, ?string $domain = null, Throwable $previous = null) {
		parent::__construct($message, $domain, $previous);
		
		$this->route = $route;
	}
	
	public function getRoute(): ControllerRoute {
		return $this->route;
	}
	
}
