<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Orpheus\Exception;

use Throwable;

/**
 * The User Reports Exception class
 *
 * This exception is thrown when we get multiple reports, this class is used instead of UserException
 */
class UserReportsException extends UserException {
	
	/**
	 * The reports
	 *
	 * @var array
	 */
	protected array $reports;
	
	public function __construct(array $reports, string $message = null, ?string $domain = null) {
		parent::__construct($message, $domain);
		$this->setReports($reports);
	}
	
	/**
	 * Get all the reports
	 *
	 * @return array $reports
	 */
	public function getReports(): array {
		return $this->reports;
	}
	
	/**
	 * Set the reports
	 */
	public function setReports(array $reports): self {
		$this->reports = $reports;
		
		return $this;
	}
	
}
