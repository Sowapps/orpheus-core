<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Orpheus\Core;

use InvalidArgumentException;
use Orpheus\Config\EnvConfig;
use Orpheus\Config\IniConfig;
use Orpheus\Service\ApplicationKernel;

class OrpheusCoreLibrary extends AbstractOrpheusLibrary {
	
	public function configure(): void {
		// Constants that could be overridden
		defifn('LOGFILE_SYSTEM', 'system.log');
		defifn('LOGFILE_NOT_FOUND', 'not-found.log');
		defifn('LOGFILE_DEBUG', 'debug.log');
		defifn('LOGFILE_HACK', 'hack.log');
		defifn('LOGFILE_SQL', 'sql.log');
		
		defifn('DOMAIN_CACHE', 'cache');
		defifn('DOMAIN_LOGS', 'logs');
		defifn('DOMAIN_TEST', 'test');
		
		defifn('SESSION_SHARE_ACROSS_SUBDOMAIN', false);
		define('SESSION_WITH_COOKIE', 1 << 0);
		define('SESSION_WITH_HTTP_TOKEN', 1 << 1);
	}
	
	public function start(): void {
		// Load core configuration
		$envConfig = EnvConfig::buildAll();
		IniConfig::build('engine');// Some libs should require to get some configuration
		
		// Determine environment
		$environment = ApplicationKernel::ENVIRONMENT_PRODUCTION;
		if( defined('PHPUNIT_COMPOSER_INSTALL') || defined('PHPUNIT_RUNNING') ) {
			$environment = ApplicationKernel::ENVIRONMENT_TEST;
		} elseif( safeConstant('DEV_VERSION') || safeConstant('DEBUG_ENABLED') ) {
			$environment = ApplicationKernel::ENVIRONMENT_DEVELOPMENT;
		} elseif( isset($envConfig['APP_ENV']) ) {
			$environment = $envConfig['APP_ENV'];
			if(!in_array($environment, [ApplicationKernel::ENVIRONMENT_DEVELOPMENT, ApplicationKernel::ENVIRONMENT_TEST, ApplicationKernel::ENVIRONMENT_STAGING, ApplicationKernel::ENVIRONMENT_PRODUCTION])) {
				throw new InvalidArgumentException(sprintf('Invalid environment "%s" in APP_ENV key of .env file', $environment));
			}
		}
		$this->kernel->setEnvironment($environment);
		$debugEnabled = $this->kernel->isDebugEnabled();
		
		// Set global constants
		defifn('DEV_VERSION', $debugEnabled);
		defifn('DEBUG_ENABLED', $debugEnabled);
		
		error_reporting($debugEnabled ? ERROR_DEBUG_LEVEL : ERROR_PROD_LEVEL);
		
		// Default locale
		if( isset($envConfig['LOCALE']) && !defined('DEFAULT_LOCALE') ) {
			define('DEFAULT_LOCALE', $envConfig['LOCALE']);
		}
	}
	
}
