<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Orpheus\Core;

use Orpheus\Config\EnvConfig;
use Orpheus\Config\IniConfig;

class OrpheusCoreLibrary extends AbstractOrpheusLibrary {
	
	public function start(): void {
		// Load core configuration
		EnvConfig::buildAll();
		IniConfig::build('engine', false);// Some libs should require to get some configuration
	}
	
}
