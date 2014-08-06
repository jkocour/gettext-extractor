<?php

/**
 * GettextExtractor
 * 
 * This source file is subject to the New BSD License.
 *
 * @copyright  Copyright (c) 2009 Karel Klíma
 * @license    New BSD License
 * @package    Nette Extras
 */


/**
 * Filter to fetch gettext phrases from PHP functions
 * @author Karel Klíma
 * @copyright  Copyright (c) 2009 Karel Klíma
 */
class GettextExtractor_Filters_LattePHPFilter extends GettextExtractor_Filters_AFilter implements GettextExtractor_Filters_IFilter {

	public function __construct() {		
		$this->addFunction('_');
		$this->addFunction('translate');
	}
	
    
    /**
	 * Parses given file and returns found gettext phrases
	 * @param string $file
	 * @return array
	 */
	public function extract($file)
	{
		$pInfo = pathinfo($file);
		$data = array();
		$quotedMacros = array();
		foreach (array_keys($this->functions) as $prefix) {
			$quotedMacros[] = preg_quote($prefix);
		}
		$functions = implode('|', $quotedMacros);
		
		foreach (file($file) as $line => $contents) {
			preg_match('/('.
				$functions . ')\([^)].+'. 
				'/s', $contents, $matches);
			if (empty($matches)) continue;
			if (empty($matches[0])) continue;
			
			$result = $this->getResult($matches[0], $line+1);
			if(isset($result[GettextExtractor_Extractor::SINGULAR])) {
				$data[] = $result;
			}
		}
		return $data;
	}
	
	private function getResult($code, $line) {
		$tokens = token_get_all("<?php " . $code);

		$parameterPosition = 0;
		$function = null;
		$result = array(
			GettextExtractor_Extractor::LINE => $line,
		);
		foreach($tokens as $token) {
			if(!$parameterPosition) {
				is_array($token) && $token[0] == T_STRING && $function = $this->functions[$token[1]];
				$token === '(' && $parameterPosition++;
			} else {
				if($token === ',') {
					$parameterPosition++;
					continue;
				}
				if(is_array($token) && $token[0] == T_CONSTANT_ENCAPSED_STRING) {
					$result += $this->getResultPart($function, $token, $parameterPosition);
				}
			}
		}
		return $result;
	}
	
	private function getResultPart($function, $token, $parameterPosition) {
		$result = array();
		foreach($function as $f) {
			foreach($f as $type => $position) {
				$position == $parameterPosition && $result[$type] = substr($token[1], 1, -1);
			}
		}
		return $result;
	}
}
