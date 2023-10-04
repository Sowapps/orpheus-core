<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Orpheus\Service;

use Orpheus\Core\AbstractOrpheusLibrary;
use Orpheus\Core\Route;
use Orpheus\InputController\ControllerRoute;
use Orpheus\InputController\InputRequest;
use RuntimeException;

class ApplicationKernel {
	
	const ENVIRONMENT_DEVELOPMENT = 'dev';
	const ENVIRONMENT_TEST = 'test';
	const ENVIRONMENT_STAGING = 'staging';
	const ENVIRONMENT_PRODUCTION = 'prod';
	
	private static ?ApplicationKernel $instance = null;
	
	/** @var AbstractOrpheusLibrary[]  */
	protected array $libraries = [];
	
	protected string $environment = self::ENVIRONMENT_PRODUCTION;
	
	public function isDebugEnabled(): bool {
		return $this->environment !== self::ENVIRONMENT_PRODUCTION;
	}
	
	public function configure(): void {
		// Load Orpheus extensions
		$compiler = OrpheusPhpCompiler::get();
		$libraries = $compiler->parseArrayFile('orpheus-libraries');
		$this->libraries = [];
		foreach( $libraries ?? [] as $class ) {
			$library = new $class($this);
			if( !($library instanceof AbstractOrpheusLibrary) ) {
				throw new RuntimeException(sprintf('Library class "%s" must inherit from "%s"', $class, AbstractOrpheusLibrary::class));
			}
			$this->libraries[] = $library;
		}
		// Start extensions
		foreach( $this->libraries as $library ) {
			$library->configure();
		}
	}
	
	public function start(): void {
		// Start extensions
		foreach( $this->libraries as $library ) {
			$library->start();
		}
	}
	
	public function configureMainRequest(InputRequest $request): InputRequest {
		// Configure main request through all libraries
		foreach( $this->libraries as $library ) {
			$request = $library->configureMainRequest($request);
		}
		
		return $request;
	}
	
	public function formatRoutePath(ControllerRoute &$route, string &$path, array $parameters = []): void {
		// Route is going to be formatted to a URL
		// Format route through all libraries
		foreach( $this->libraries as $library ) {
			$library->formatRoutePath($route, $path, $parameters);
		}
	}
	
	public function getEnvironment(): string {
		return $this->environment;
	}
	
	public function setEnvironment(string $environment): void {
		$this->environment = $environment;
	}
	
	public static function get(): static {
		if( !static::$instance ) {
			static::$instance = new static();
		}
		
		return static::$instance;
	}
	
}
