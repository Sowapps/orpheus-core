<?php
/**
 * ClassLoader
 */

namespace Orpheus\Core;

use Exception;

/**
 * Official Orpheus class loader
 *
 * @author Florent HAZARD <florent@orpheus-framework.com>
 */
class ClassLoader {
	
	/**
	 * The active autoloader
	 *
	 * @var ClassLoader
	 */
	protected static $loader;
	/**
	 * The known class mapping
	 *
	 * @var array
	 */
	protected $classes;
	
	/**
	 * Constructor
	 */
	public function __construct() {
		$this->classes = [];
	}
	
	/**
	 * Get the string representation of the ClassLoader
	 *
	 * @return string
	 */
	public function __toString() {
		return 'orpheus-ClassLoader';
	}
	
	/**
	 * Load class file
	 *
	 * @param string $className
	 * @throws Exception
	 */
	public function loadClass($className) {
		// PHP's class' names are not case sensitive.
		$bFile = strtolower($className);
		
		// If the class file path is known in the our array
		if( !empty($this->classes[$bFile]) ) {
			$path = null;
			$path = $this->classes[$bFile];
			require_once $path;
			if( !class_exists($className, false) && !interface_exists($className, false) && !trait_exists($className, false) ) {
				throw new Exception('Wrong use of Autoload, the class "' . $className . '" should be declared in the given file "' . $path . '". Please use Class Loader correctly.');
			}
		}
	}
	
	/**
	 * Set the file path to the class
	 *
	 * @param string $className
	 * @param string $classPath
	 * @return boolean
	 * @throws Exception
	 */
	public function setClass($className, $classPath) {
		$className = strtolower($className);
		$path = null;
		if(
			// Pure object naming with only lib name and exact class name
			existsPathOf(LIBRARY_FOLDER . '/' . $classPath . '/' . $className . '.php', $path) ||
			// Pure object naming
			existsPathOf(LIBRARY_FOLDER . '/' . $classPath . '.php', $path) ||
			// Old Orpheus naming
			existsPathOf(LIBRARY_FOLDER . '/' . $classPath . '_class.php', $path) ||
			// Full path
			existsPathOf(LIBRARY_FOLDER . '/' . $classPath, $path) ||
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
			throw new Exception("ClassLoader : File \"{$classPath}\" of class \"{$className}\" not found.");
		}
		return true;
		
	}
	
	/**
	 * Get the known classes
	 *
	 * @return array
	 */
	public function getClasses() {
		return $this->classes;
	}
	
	/**
	 * Get the active autoloader
	 *
	 * @return ClassLoader
	 */
	public static function get() {
		if( !static::$loader ) {
			static::set(new static());
		}
		return static::$loader;
	}
	
	/**
	 * Set the active autoloader
	 *
	 * @param ClassLoader
	 */
	public static function set(ClassLoader $loader) {
		// Unregister the previous one
		if( static::$loader ) {
			static::$loader->unregister();
		}
		// Set the new class loader
		static::$loader = $loader;
		// Register the new one
		if( static::$loader ) {
			static::$loader->register(true);
		}
	}
	
	/**
	 * Unregister object from the SPL
	 */
	public function unregister() {
		spl_autoload_unregister([$this, 'loadClass']);
	}
	
	/**
	 * Register object to the SPL
	 *
	 * @param boolean
	 */
	public function register($prepend = false) {
		spl_autoload_register([$this, 'loadClass'], true, $prepend);
	}
	
	/**
	 * Check if active loader is valid
	 *
	 * @return boolean
	 */
	public static function isValid() {
		return !!static::$loader;
	}
}
