<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Orpheus\Formatter;

interface FileFormatable {
	
	/**
	 * Format data to contents
	 */
	function format($data, bool $pretty = false): string;
	
	/**
	 * Parse file to data
	 */
	function parse(string $path): mixed;
	
}
