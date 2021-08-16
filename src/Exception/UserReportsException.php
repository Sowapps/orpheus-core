<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Orpheus\Exception;

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
	protected $reports;
	
	/**
	 * Get all the reports
	 *
	 * @return array $reports
	 */
	public function getReports() {
		return $this->reports;
	}
	
	/**
	 * Set the reports
	 *
	 * @param array $reports
	 * @return \Orpheus\Exception\UserReportsException
	 */
	public function setReports(array $reports) {
		$this->reports = $reports;
		
		return $this;
	}
	
}
