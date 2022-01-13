<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Orpheus\Exception;

use Exception;
use RuntimeException;
use Throwable;
use function t;

/**
 * The user exception class
 *
 * This exception is thrown when an occurred caused by the user.
 */
class UserException extends RuntimeException {
	
	/**
	 * The translation domain
	 *
	 * @var string|null
	 */
	protected ?string $domain = null;
	
	/**
	 * Constructor
	 *
	 * @param string|null $message The exception message
	 * @param string|null $domain The domain for the message, optional, allow $code
	 * @param int|null $code The code of the exception
	 * @param Throwable|null $previous The previous exception
	 */
	public function __construct(?string $message = null, ?string $domain = null, int $code = 0, ?Throwable $previous = null) {
		if( is_int($domain) ) {
			$code = $domain;
			$domain = null;
		}
		parent::__construct($message, $code, $previous);
		$this->setDomain($domain);
	}
	
	/**
	 * Get the domain
	 *
	 * @return string
	 */
	public function getDomain(): ?string {
		return $this->domain;
	}
	
	/**
	 * Set the domain
	 *
	 * @param string|null $domain The new domain
	 */
	public function setDomain(?string $domain) {
		$this->domain = $domain;
	}
	
	/**
	 * Get the report from this exception
	 *
	 * @return string The report
	 */
	public function getReport(): string {
		return $this->getText();
	}
	
	/**
	 * Get the user's message
	 *
	 * @return string The translated message from this exception
	 */
	public function getText(): string {
		return t($this->getMessage(), $this->domain);
	}
	
	/**
	 * Get the string representation of this exception
	 *
	 * @return string
	 */
	public function __toString() {
		try {
			return $this->getText();
		} catch( Exception $e ) {
			if( ERROR_LEVEL == DEV_LEVEL ) {
				die('A fatal error occurred in UserException::__toString() :<br />' . $e->getMessage());
			}
			die('A fatal error occurred, please report it to an admin.<br />Une erreur fatale est survenue, veuillez contacter un administrateur.<br />');
		}
	}
	
}
