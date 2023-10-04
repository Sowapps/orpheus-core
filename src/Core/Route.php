<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Orpheus\Core;

/**
 * Route for Orpheus.
 */
abstract class Route {
	
	/**
	 * Test if the route is accessible in the current context using restrictTo
	 * This method is sensitive to CHECK_MODULE_ACCESS constant, a true value make it always accessible, never use it in production
	 */
	public abstract function isAccessible(): bool;
	
	/**
	 * Get the link to this route
	 */
	public abstract function getLink(): string;
	
	/**
	 * Get the name
	 */
	public abstract function getName(): string;
	
}

