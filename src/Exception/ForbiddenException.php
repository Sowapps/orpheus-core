<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Orpheus\Exception;

use Throwable;

/**
 * The Forbidden Exception class
 *
 * This exception is thrown when something requested require an authentication or more permissions.
 */
class ForbiddenException extends UserException {
	
	/**
	 * Constructor
	 *
	 * @param string|null $message The exception message
	 * @param string|null $domain The domain for the message
	 * @param Throwable|null $previous The previous exception
	 */
	public function __construct($message = null, $domain = null, $previous = null) {
		parent::__construct($message ?? 'forbidden', $domain, defined('HTTP_FORBIDDEN') ? HTTP_FORBIDDEN : null, $previous);
	}
	
}
