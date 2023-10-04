<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Orpheus\Core;

use Exception;

/**
 * Official Orpheus class loader
 */
class ClassLoader {
	
	/**
	 * The active autoloader
	 *
	 * @var ClassLoader
	 */
	protected static ClassLoader $loader;
	/**
	 * The known class mapping
	 *
	 * @var array
	 */
	protected array $classes;
	
	/**
	 * Constructor
	 */
	public function __construct() {
		$this->classes = [];
	}
	
	/**
	 * Get the string representation of the ClassLoader
	 */
	public function __toString(): string {
		return 'orpheus-ClassLoader';
	}
	
	/**
	 * Load class file
	 *
	 * @throws Exception
	 */
	public function loadClass(string $className): void {
		// PHP's class' names are not case-sensitive.
		$bFile = strtolower($className);
		
		// If the class file path is known
		if( !empty($this->classes[$bFile]) ) {
			$path = null;
			$path = $this->classes[$bFile];
			require_once $path;
			if( !class_exists($className, false) && !interface_exists($className, false) && !trait_exists($className, false) ) {
				throw new Exception(sprintf('Wrong use of Autoload, the class "%s" should be declared in the given file "%s". Please use Class Loader correctly.', $className, $path));
			}
		}
	}
	
	/**
	 * Set the file path to the class
	 *
	 * @throws Exception
	 */
	public function setClass(string $className, string $classPath): bool {
		$className = strtolower($className);
		$path = null;
		if(
			// Pure object naming with only lib name and exact class name
			existsPathOf(SRC_PATH . $classPath . '/' . $className . '.php', $path) ||
			// Pure object naming
			existsPathOf(SRC_PATH . '/' . $classPath . '.php', $path) ||
			// Old Orpheus naming
			existsPathOf(SRC_PATH . '/' . $classPath . '_class.php', $path) ||
			// Full path
			existsPathOf(SRC_PATH . '/' . $classPath, $path)
		) {
			$this->classes[$className] = $path;
			
		} else {
			throw new Exception(sprintf('ClassLoader : File "%s" of class "%s" not found."', $classPath, $className));
		}
		return true;
		
	}
	
	/**
	 * Get the known classes
	 */
	public function getClasses(): array {
		return $this->classes;
	}
	
	/**
	 * Get the active autoloader
	 */
	public static function get(): static {
		if( !static::$loader ) {
			static::set(new static());
		}
		return static::$loader;
	}
	
	/**
	 * Set the active autoloader
	 */
	public static function set(ClassLoader $loader): void {
		// Unregister the previous one
		static::$loader?->unregister();
		// Set the new class loader
		static::$loader = $loader;
		// Register the new one
		if( static::$loader ) {
			static::$loader?->register(true);
		}
	}
	
	/**
	 * Unregister object from the SPL
	 */
	public function unregister(): void {
		spl_autoload_unregister([$this, 'loadClass']);
	}
	
	/**
	 * Register object to the SPL
	 */
	public function register(bool $prepend = false): void {
		spl_autoload_register([$this, 'loadClass'], true, $prepend);
	}
	
	/**
	 * Check if active loader is valid
	 */
	public static function isValid(): bool {
		return !!static::$loader;
	}
}
