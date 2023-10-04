<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Orpheus\Config;

use Exception;
use Orpheus\Cache\CacheException;
use Orpheus\Cache\FileSystemCache;
use Orpheus\Php\ArrayAggregate;
use RuntimeException;

/**
 * The config core class
 *
 * This class is the core for config classes inherited from custom configuration.
 */
abstract class Config extends ArrayAggregate {
	
	protected static string $extension;
	
	/**
	 * Contains the main configuration, reachable from everywhere
	 */
	protected static ?Config $main = null;
	
	/**
	 * The config uses cache
	 *
	 * @var boolean
	 */
	protected static bool $caching = true;
	
	/**
	 * The repositories
	 *
	 * @var array
	 */
	protected static array $repositories = [];
	
	/**
	 * Contains the configuration for this Config Object.
	 * Must be inherited from ConfigCore.
	 *
	 * @var array
	 */
	protected array $config = [];
	
	public function &getArray(): array {
		return $this->config;
	}
	
	/**
	 * Return the configuration item with key $key.
	 * Except for:
	 * - 'all' : It returns an array containing all configuration items.
	 *
	 * @param string $key The key to get the value
	 * @return array A config value
	 */
	public function __get(string $key) {
		if( $key === 'all' ) {
			return $this->asArray();
		}
		
		return $this->getOne($key);
	}
	
	/**
	 * Sets the configuration item with key $key.
	 * Except for:
	 * - 'all' : It sets all the array containing all configuration items.
	 *
	 * @param string $key The key to set the value
	 * @param mixed $value The new config value
	 */
	public function __set(string $key, mixed $value) {
		if( $key === 'all' && is_array($value) ) {
			$this->config = $value;
			
			return;
		}
		if( isset($this->config[$key]) ) {
			$this->config[$key] = $value;
		}
	}
	
	/**
	 * Checks if the config $key is set.
	 *
	 * @param string $key Key of the config to check is set
	 */
	public function __isset(string $key) {
		return isset($this->config[$key]);
	}
	
	/**
	 * Get this config as array
	 */
	public function asArray(): array {
		return $this->config;
	}
	
	/**
	 * Get one config value
	 */
	public function getOne(string $key, mixed $default = null): mixed {
		return array_path_get($this->config, $key, $default);
	}
	
	/**
	 * Check if source is available
	 *
	 * @param string $source An identifier to get the source
	 * @return boolean True if source is available
	 */
	public function hasSource(string $source, ?string $package = null): bool {
		try {
			return !!static::getFilePath($source, $package);
		} catch( Exception ) {
			return false;
		}
	}
	
	/**
	 * Parse configuration from given source
	 *
	 * @param string $path The path to the config file
	 * @return mixed The loaded configuration array
	 */
	public static function parse(string $path): array {
		throw new RuntimeException(sprintf('The class "%s" should override the `parse()` static method from "%s"', get_called_class(), get_class()));
	}
	
	/**
	 * Add configuration to this object
	 *
	 * @param array|null $config The configuration array to add to the current object.
	 */
	public function add(?array $config): void {
		if( !$config ) {
			return;
		}
		$this->config = array_merge($this->config, $config);
	}
	
	/**
	 * Load new configuration from source in package
	 *
	 * @param string|null $package The package to include config (null to get app config)
	 * @param string $source An identifier to get the source
	 * @param boolean $cached True if this configuration should be cached
	 * @return boolean True if this configuration was loaded successfully
	 */
	public function loadFrom(?string $package, string $source, bool $cached = true): bool {
		try {
			$path = static::getFilePath($source, $package);
			if( !$path ) {
				return false;
			}
			if( class_exists(FileSystemCache::class) ) {
				// Try to update cache even if we don't want to use it as source
				$cacheClass = ($package ? $this->normalizeName($package) : 'app') . '-config';// app-config
				// FileSystemCache does not allow path, so strtr() removes / and \
				$cacheName = $this->normalizeName($source);
				$cache = new FileSystemCache($cacheClass, $cacheName, filemtime($path));
				$parsed = null;
				if( !static::$caching || !$cached || !$cache->get($parsed) ) {
					$parsed = static::parse($path);
					$cache->set($parsed);
				}
			} else {
				$parsed = static::parse($path);
			}
			$this->add($parsed);
			
			return true;
			
		} catch( CacheException $e ) {
			log_error($e, 'Caching parsed source ' . $source);
		}
		
		return false;
	}
	
	protected function normalizeName(string $name): string {
		$name = strtr($name, '/\\', '--');
		
		return preg_replace('#^\-+|\-+$#', '', $name);
	}
	
	/**
	 * Load new configuration from source
	 *
	 * @param string $source An identifier to get the source
	 * @param boolean $cached True if this configuration should be cached
	 * @return boolean True if this configuration was loaded successfully
	 */
	public function load(string $source, bool $cached = true): bool {
		return $this->loadFrom(null, $source, $cached);
	}
	
	/**
	 * Get the file path
	 * Get the configuration file path in CONFIG_FOLDER.
	 *
	 * @param string $source An identifier to get the source.
	 * @param string|null $package The package to get file path (null to get app file path). Default is null
	 * @return string|null The configuration file path, this file exists or an exception is thrown.
	 */
	public static function getFilePath(string $source, ?string $package = null): ?string {
		if( is_readable($source) ) {
			return $source;
		}
		$configFile = static::getSourcePath($source);
		if( $package ) {
			$path = VENDOR_PATH . '/' . $package . CONFIG_FOLDER . $configFile;
		} else {
			$path = pathOf(CONFIG_FOLDER . $configFile, true);
		}
		if( !$path || !is_file($path) || !is_readable($path) ) {
			throw new RuntimeException(sprintf('Unable to find config source "%s"%s', $source, $package ? sprintf(' in package "%s"', $package) : ''));
		}
		
		return $path;
	}
	
	protected static function getSourcePath(string $source): string {
		if( !isset(static::$extension) ) {
			throw new RuntimeException('To get file path, static string extension property must be declared');
		}
		if( $source[0] !== '/' ) {
			// Normalize to start with slash
			$source = '/' . $source;
		}
		return $source . '.' . static::$extension;
	}
	
	/**
	 * Build a configuration from $source using load() method.
	 * If it is not a minor configuration, that new configuration is added to the main configuration.
	 *
	 * @param string $source An identifier to build the source
	 * @param boolean $minor True if this is a minor configuration
	 * @param boolean $cached True if this configuration should be cached
	 */
	public static function build(string $source, bool $minor = false, bool $cached = true): ?Config {
		if( get_called_class() === get_class() ) {
			throw new RuntimeException('Use a subclass of ' . get_class() . ' to build your configuration');
		}
		if( !$minor ) {
			if( !isset(static::$main) ) {
				static::$main = new static();
				$GLOBALS['CONFIG'] = &static::$main;
			}
			static::$main->load($source, $cached);
			
			return static::$main;
		}
		$newConf = new static();
		$newConf->load($source, $cached);
		
		return $newConf;
	}
	
	/**
	 * Build new configuration source from package
	 *
	 * Build a configuration from $source using load() method.
	 * If it is not a minor configuration, that new configuration is added to the main configuration.
	 *
	 * @param string|null $package The package to include config (null to get app config)
	 * @param string $source An identifier to build the source
	 * @param boolean $cached True if this configuration should be cached
	 * @param boolean $silent True if ignoring config loading issues
	 */
	public static function buildFrom(?string $package, string $source, bool $cached = true, bool $silent = false): ?Config {
		if( get_called_class() === get_class() ) {
			throw new RuntimeException(sprintf("Use a subclass of %s to build your configuration", get_class()));
		}
		$newConf = new static();
		if( $silent && !$newConf->hasSource($source, $package) ) {
			return null;
		}
		$newConf->loadFrom($package, $source, $cached);
		
		return $newConf;
	}
	
	/**
	 * Get configuration from the main configuration object
	 *
	 * @param string $key The key to get the value
	 * @param mixed $default The default value to use
	 * @return mixed A config value
	 */
	public static function get(string $key, mixed $default = null): mixed {
		if( !isset(static::$main) ) {
			return $default;
		}
		
		return static::$main->getOne($key, $default);
	}
	
	/**
	 * Set configuration to the main configuration object
	 * Call __set() method to main configuration object.
	 *
	 * @param string $key The key to set the value
	 * @param mixed $value The new config value
	 * @throws Exception
	 */
	public static function set(string $key, mixed $value): void {
		if( !isset(static::$main) ) {
			throw new Exception('No Main Config');
		}
		static::$main->$key = $value;
	}
	
	/**
	 * Test if config is caching
	 */
	public static function isCaching(): bool {
		return self::$caching;
	}
	
	/**
	 * Set if config is caching
	 */
	public static function setCaching(bool $caching): void {
		self::$caching = $caching;
	}
	
	public static function getMain(): ?Config {
		return self::$main;
	}
	
}
