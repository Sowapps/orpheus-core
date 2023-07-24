<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace Orpheus\Formatter;

use RuntimeException;

class PhpCompiledArrayFileFormatter implements FileFormatable {
	
	public function format($data, bool $pretty = false): string {
		if( !is_array($data) ) {
			throw new RuntimeException('Data must be an array');
		}
		if( $pretty ) {
			throw new RuntimeException('Pretty mode is not implemented for this formatter yet');
		}
		$dataContents = json_encode($data);
		
		return <<<EOF
<?php return {$dataContents};
EOF;
	}
	
	public function parse(string $path): mixed {
		return include $path;
	}
	
}
