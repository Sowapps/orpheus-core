<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Orpheus\Core;

use Orpheus\InputController\ControllerRoute;
use Orpheus\InputController\InputRequest;
use Orpheus\Service\ApplicationKernel;

class AbstractOrpheusLibrary {
	
	protected ApplicationKernel $kernel;
	
	/**
	 * AbstractOrpheusLibrary constructor
	 */
	public function __construct(ApplicationKernel $kernel) {
		$this->kernel = $kernel;
	}
	
	
	public function configure(): void {
		// Nothing
	}
	
	public function start(): void {
		// Nothing
	}
	
	public function configureMainRequest(InputRequest $request): InputRequest {
		return $request;
	}
	
	public function formatRoutePath(ControllerRoute &$route, string &$path, array $parameters = []): void {
	}
	
}
