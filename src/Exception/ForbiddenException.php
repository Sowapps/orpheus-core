<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Orpheus\Exception;

/**
 * The Forbidden Exception class
 *
 * This exception is thrown when something requested require an authentication or more permissions.
 */
class ForbiddenException extends UserException {
	
	/**
	 * Constructor
	 *
	 * @param string $message The exception message
	 * @param string $domain The domain for the message
	 * @param Throwable $previous The previous exception
	 */
	public function __construct($message = null, $domain = null, $previous = null) {
		parent::__construct($message ? $message : 'forbidden', $domain, defined('HTTP_FORBIDDEN') ? HTTP_FORBIDDEN : null, $previous);
	}
	
}
