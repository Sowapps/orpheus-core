<?php

namespace Orpheus\Config;

/**
 * The ini config class
 *
 * This class uses ini files to get configuration.
 */
class IniConfig extends Config {

	/**
	 * Extension for this config files
	 *
	 * @var string
	 */
	protected static string $extension = 'ini';

	/**
	 * Parse configuration from given source.
	 *  If an identifier, loads a configuration from a .ini file in CONFIG_FOLDER.
	 *  Else $source is a full path to the ini configuration file.
	 *
	 * @param string $path The path to the config file
	 * @return mixed The loaded configuration array
	 */
	public static function parse(string $path): array {
		return parse_ini_file($path, true);
	}
}
