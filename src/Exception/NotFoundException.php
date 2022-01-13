<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Orpheus\Exception;

/**
 * The Not Found Exception class
 *
 * This exception is thrown when something requested is not found.
 */
class NotFoundException extends UserException {
	
	/**
	 * Constructor
	 *
	 * @param string $message The exception message
	 * @param string $domain The domain for the message
	 * @param Throwable $previous The previous exception
	 */
	public function __construct($message = null, $domain = null, $previous = null) {
		parent::__construct($message ? $message : 'notFound', $domain, defined('HTTP_NOT_FOUND') ? HTTP_NOT_FOUND : null, $previous);
	}
	
}
