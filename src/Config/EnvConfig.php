<?php

namespace Orpheus\Config;

use RuntimeException;

/**
 * The ini config class
 *
 * This class uses ini files to get configuration.
 */
class EnvConfig extends IniConfig {
	
	/**
	 * Contains the main ENV configuration
	 */
	protected static ?Config $main = null;
	
	public static function buildAll(): ?Config {
		$config = static::getConfig();
		$config->load('.env');
		$config->load('.env.local');
		
		return $config;
	}
	
	public static function build(string $source, bool $minor = false, bool $cached = true): ?Config {
		throw new RuntimeException('Unable to build an EnvConfig');
	}
	
	public static function buildFrom(?string $package, string $source, bool $cached = true, bool $silent = false): ?Config {
		throw new RuntimeException('Unable to build an EnvConfig');
	}
	
	/**
	 * Parse configuration from given source.
	 *
	 * @param string $path The path to the config file
	 * @return mixed The loaded configuration array
	 */
	public static function parse(string $path): array {
		return parse_ini_file($path, true);
	}
	
	public static function getFilePath(string $source, ?string $package = null): ?string {
		if( is_readable($source) ) {
			return $source;
		}
		$configFile = '/' . $source;
		if( $package ) {
			$path = VENDOR_PATH . '/' . $package . $configFile;
		} else {
			$path = pathOf($configFile, true);
		}
		//		if( !$path || !is_file($path) || !is_readable($path) ) {
		//			throw new Exception('Unable to find config source "' . $source . '"');
		//		}
		
		return $path;
	}
	
	public static function getConfig(): ?Config {
		if( !isset(static::$main) ) {
			static::$main = new static();
		}
		
		return static::$main;
	}
	
}
