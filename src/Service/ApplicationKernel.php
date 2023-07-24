<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Orpheus\Service;

use Orpheus\Core\OrpheusCoreLibrary;
use RuntimeException;

class ApplicationKernel {
	
	private static ?ApplicationKernel $instance = null;
	
	protected array $libraries = [];
	
	public function start(): void {
		// Load Orpheus extensions
		$compiler = OrpheusPhpCompiler::get();
		$libraries = $compiler->parseArrayFile('orpheus-libraries');
		$this->libraries = [];
		foreach( $libraries ?? [] as $class ) {
			$library = new $class;
			if( !($library instanceof OrpheusCoreLibrary) ) {
				throw new RuntimeException(sprintf('Extension class "%s" must inherit from "%s"', $class, OrpheusCoreLibrary::class));
			}
			$this->libraries[] = new $library;
		}
		// Start extensions
		foreach( $this->libraries as $library ) {
			$library->start();
		}
	}
	
	public static function get(): static {
		if( !static::$instance ) {
			static::$instance = new static();
		}
		
		return static::$instance;
	}
	
}
