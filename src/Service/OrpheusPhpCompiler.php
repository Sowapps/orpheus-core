<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Orpheus\Service;

use Orpheus\Formatter\PhpCompiledArrayFileFormatter;
use SplFileObject;

class OrpheusPhpCompiler {
	
	private static ?OrpheusPhpCompiler $instance = null;
	
	private ?string $compilerPath = null;
	
	/**
	 * OrpheusPhpCompiler constructor
	 *
	 * @param string|null $compilerPath
	 */
	public function __construct(?string $compilerPath = null) {
		$this->compilerPath = $compilerPath ?? STORE_PATH . '/compiler';
	}
	
	protected function checkFileSystem(): void {
		if( !is_dir($this->compilerPath) ) {
			mkdir($this->compilerPath, 0777, true);
		}
	}
	
	
	public function compileArray(string $name, array $data, bool $pretty = false): void {
		$this->checkFileSystem();
		$formatter = new PhpCompiledArrayFileFormatter();
		$contents = $formatter->format($data);
		$file = $this->getFile($name, false);
		$file->fwrite($contents);
	}
	
	public function parseArrayFile(string $name): ?array {
		$file = $this->getFile($name, true);
		if( !$file->isFile() ) {
			return null;
		}
		$formatter = new PhpCompiledArrayFileFormatter();
		
		return $formatter->parse($file->getRealPath());
	}
	
	protected function getFile(string $name, bool $readOnly): SplFileObject {
		return new SplFileObject(sprintf('%s/%s.php', $this->compilerPath, $name), $readOnly ? 'r' : 'w+');
	}
	
	public static function initialize(?string $path): void {
		static::$instance = new static($path);
	}
	
	public static function get(): static {
		if( !static::$instance ) {
			self::initialize(null);
		}
		
		return static::$instance;
	}
	
}
