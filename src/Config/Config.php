<?php
/**
 * Config
 */

namespace Orpheus\Config;

/**
 * The config core class
 *
 * This class is the core for config classes inherited from custom configuration.
 */
abstract class Config {
	
	/**
	 * Contains the main configuration, reachable from everywhere.
	 *
	 * @var Config
	 */
	protected static $main;
	
	/**
	 * The config uses cache
	 *
	 * @var boolean
	 */
	protected static $caching = true;
	
	/**
	 * Contains the configuration for this Config Object.
	 * Must be inherited from ConfigCore.
	 *
	 * @var array
	 */
	protected $config = array();
	
	/**
	 * Get this config as array
	 *
	 * @return array
	 */
	public function asArray() {
		return $this->config;
	}
	
	/**
	 * The magic function to get config
	 *
	 * @param $key The key to get the value.
	 * @return A config value.
	 *
	 * Return the configuration item with key $key.
	 * Except for:
	 * - 'all' : It returns an array containing all configuration items.
	 */
	public function __get($key) {
		if( $key === 'all' ) {
			return $this->asArray();
		}
		return apath_get($this->config, $key);
	}
	
	/**
	 * The magic function to set config
	 * @param $key The key to set the value.
	 * @param $value The new config value.
	 *
	 * Sets the configuration item with key $key.
	 * Except for:
	 * - 'all' : It sets all the array containing all configuration items.
	 */
	public function __set($key, $value) {
		if( $key === 'all' && is_array($value) ) {
			$this->config = $value;
			return;
		}
		if( isset($this->config[$key]) ) {
			$this->config[$key] = $value;
		}
	}
	
	/**
	 * Magic isset
	 *
	 * @param $key Key of the config to check is set
	 *
	 * Checks if the config $key is set.
	 */
	public function __isset($key) {
		return isset($this->config[$key]);
	}
	
	/**
	 * Add configuration to this object
	 *
	 * @param $conf The configuration array to add to the current object.
	 *
	 * Add the configuration array $conf to this configuration.
	 */
	public function add($conf) {
		if( empty($conf) ) { return ; }
		$this->config = array_merge($this->config, $conf);
	}
	
	/**
	 * Check if source is available
	 *
	 * @param string $source An identifier to get the source.
	 * @return boolean True if source is available
	 */
	public function checkSource($source) {
		try {
			return !!static::getFilePath($source);
			// 			return is_readable($source) || is_readable(static::getFilePath($source));
		} catch( Exception $e ) {
			return false;
		}
	}
	
	/**
	 * Load new configuration from source
	 *
	 * @param string $source An identifier to get the source
	 * @param boolean $cached True if this configuration should be cached
	 * @return boolean True if this configuration was loaded successfully
	 *
	 * Load a configuration from a source identified by $source.
	 */
	public function load($source, $cached=true) {
		try {
			$path = static::getFilePath($source);
			if( class_exists('\Orpheus\Cache\FSCache', true) ) {
				// strtr fix an issue with FSCache, FSCache does not allow path, so no / and \
				$cache = new \Orpheus\Cache\FSCache('config', strtr($source, '/\\', '--'), filemtime($path));
				if( !static::$caching || !$cached || !$cache->get($parsed) ) {
					$parsed	= static::parse($path);
					$cache->set($parsed);
				}
			} else {
				$parsed	= static::parse($path);
			}
			$this->add($parsed);
			return true;
			
		} catch( CacheException $e ) {
			log_error($e, 'Caching parsed source '.$source, false);
			
		} catch( Exception $e ) {
			// If not found, we do nothing
			log_error($e, 'Caching parsed source '.$source, false);
		}
		return false;
	}
	
	/**
	 * Load new configuration from source in package
	 *
	 * @param string $package The package to include config (null to get app config)
	 * @param string $source An identifier to get the source
	 * @param boolean $cached True if this configuration should be cached
	 * @return boolean True if this configuration was loaded successfully
	 *
	 * Load a configuration from a source identified by $source.
	 */
	public function loadFrom($package, $source, $cached=true) {
		try {
			$path = static::getFilePath($source, $package);
			if( class_exists('\Orpheus\Cache\FSCache', true) ) {
				$cacheClass = ($package ? strtr($package, '/\\', '--') : 'app').'-config';
				// strtr fix an issue with FSCache, FSCache does not allow path, so no / and \
				$cache = new \Orpheus\Cache\FSCache($cacheClass, strtr($source, '/\\', '--'), filemtime($path));
				$parsed = null;
				if( !static::$caching || !$cached || !$cache->get($parsed) ) {
					$parsed	= static::parse($path);
					$cache->set($parsed);
				}
			} else {
				$parsed	= static::parse($path);
			}
			$this->add($parsed);
			return true;
			
		} catch( CacheException $e ) {
			log_error($e, 'Caching parsed source '.$source, false);
			
		} catch( Exception $e ) {
			// If not found, we do nothing
			log_error($e, 'Caching parsed source '.$source, false);
		}
		return false;
	}
	
	/**
	 * Build new configuration source from package
	 *
	 * @param $package The package to include config (null to get app config)
	 * @param string $source An identifier to build the source
	 * @param boolean $cached True if this configuration should be cached
	 * @return Config
	 *
	 * Build a configuration from $source using load() method.
	 * If it is not a minor configuration, that new configuration is added to the main configuration.
	 */
	public static function buildFrom($package, $source, $cached=true) {
		if( get_called_class() === get_class() ) {
			throw new \Exception('Use a subclass of '.get_class().' to build your configuration');
		}
		$newConf = new static();
		$newConf->loadFrom($package, $source, $cached);
		return $newConf;
	}
	
	/**
	 * Build new configuration source
	 *
	 * @param string $source An identifier to build the source
	 * @param boolean $minor True if this is a minor configuration
	 * @param boolean $cached True if this configuration should be cached
	 * @return Config
	 *
	 * Build a configuration from $source using load() method.
	 * If it is not a minor configuration, that new configuration is added to the main configuration.
	 */
	public static function build($source, $minor=false, $cached=true) {
		if( get_called_class() === get_class() ) {
			throw new \Exception('Use a subclass of '.get_class().' to build your configuration');
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
	 * Get configuration from the main configuration object
	 *
	 * @param string $key The key to get the value.
	 * @param mixed $default The default value to use.
	 * @return string A config value.
	 *
	 * Calls __get() method from main configuration object.
	 */
	public static function get($key, $default=null) {
		if( !isset(static::$main) ) {
			return $default;
		}
		return isset(static::$main->$key) ? static::$main->$key : $default;
	}
	
	/**
	 * Set configuration to the main configuration object
	 *
	 * @param string $key The key to set the value.
	 * @param mixed $value The new config value.
	 *
	 * Call __set() method to main configuration object.
	 */
	public static function set($key, $value) {
		if( !isset(static::$main) ) {
			throw new \Exception('No Main Config');
		}
		static::$main->$key = $value;
	}
	
	/**
	 * The repositories
	 *
	 * @var array
	 */
	protected static $repositories	= array();
	
	/**
	 * Add a repository to load configs
	 *
	 * @param mixed $repos The repository to add. Commonly a path to a directory.
	 */
	public static function addRepository($repos) {
		static::$repositories[]	= $repos;
	}
	
	/**
	 * Add a repository library to load configs
	 *
	 * @param string $library The library folder
	 */
	public static function addRepositoryLibrary($library) {
		static::addRepository(pathOf(LIBSDIR.$library).CONFDIR);
	}
	
	// 	protected static $testCounter = 0;
	
	/**
	 * Get the file path
	 *
	 * @param string $source An identifier to get the source.
	 * @param string $package The package to get file path (null to get app file path). Default is null
	 * @return string The configuration file path, this file exists or an exception is thrown.
	 * @exception \Exception if file is not found
	 *
	 * Get the configuration file path in CONFDIR.
	 */
	public static function getFilePath($source, $package=null) {
		if( is_readable($source) ) {
			return $source;
		}
		// Remove it if you protected app against infinite loop
		// 		static::$testCounter++;
		// 		if( static::$testCounter > 10 ) {
		// 			echo new \Exception();
		// 			die();
		// 		}
		$configFile	= $source.'.'.static::$extension;
		$path = null;
		if( $package ) {
			$path = VENDORPATH.$package.'/'.CONFDIR.$configFile;
		} else {
			foreach( static::$repositories as $repos ) {
				if( is_readable($repos.$configFile) ) {
					$path = $repos.$configFile;
				}
			}
			if( !$path) {
				$path = pathOf(CONFDIR.$configFile, true);
			}
		}
		if( !is_file($path) || !is_readable($path) ) {
			throw new \Exception('Unable to find config source "'.$source.'"');
		}
		return $path;
	}
	
	
	/**
	 * Parse configuration from given source
	 *
	 * @param string $path The path to the config file
	 * @return mixed The loaded configuration array
	 *
	 * Before 25/03/2017, was taking $source in parameter, now taking a path
	 */
	public static function parse($path) {
		throw new \Exception('The class "'.get_called_class().'" should override the `parse()` static method from "'.get_class().'"');
	}
	
	/**
	 * Test if config is caching
	 *
	 * @return boolean
	 */
	public static function isCaching() {
		return self::$caching;
	}
	
	/**
	 * Set if config is caching
	 *
	 * @param boolean $caching
	 */
	public static function setCaching($caching) {
		self::$caching = $caching;
	}
}
