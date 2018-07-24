<?php
/**
 * GettextExtractor
 *
 * Cool tool for automatic extracting gettext strings for translation
 *
 * Works best with Nette Framework
 *
 * This source file is subject to the New BSD License.
 *
 * @copyright Copyright (c) 2009 Karel Klíma
 * @copyright Copyright (c) 2010 Ondřej Vodáček
 * @license New BSD License
 * @package Nette Extras
 */

/**
 * Filter to parse curly brackets syntax in Nette Framework templates
 * @author Karel Klíma
 * @author Ondřej Vodáček
 */
class GettextExtractor_Filters_NetteLatteFilter extends GettextExtractor_Filters_AFilter implements GettextExtractor_Filters_IFilter {

	public function __construct() {
		$this->addFunction('_');
		$this->addFunction('!_');
	}

	/**
	 * Includes a prefix to match in { }
	 * Alias for AFilter::addFunction
	 *
	 * @param $prefix string
	 * @param $singular int
	 * @param $plural int|null
	 * @param $context int|null
	 * @return self
	 */
	public function addPrefix($prefix, $singular = 1, $plural = null, $context = null) {
		parent::addFunction($prefix, $singular, $plural, $context);
		return $this;
	}

	/**
	 * Excludes a prefix from { }
	 * Alias for AFilter::removeFunction
	 *
	 * @param string $prefix
	 * @return self
	 */
	public function removePrefix($prefix) {
		parent::removeFunction($prefix);
		return $this;
	}

	/**
	 * Parses given file and returns found gettext phrases
	 *
	 * @param string $file
	 * @return array
	 */
	public function extract($file) {
		if (count($this->functions) === 0) {
			return;
		}
		$data = [];
		
		$regexp = '/' . implode('|', array_keys($this->functions)) . '/';
		$latte = new \Latte\Parser();
		foreach($latte->parse(file_get_contents($file)) as $token) {
			if(preg_match($regexp, $token->name) || preg_match($regexp, $token->text)) {
				$macroTokens = new \Latte\MacroTokens($token->text);
				foreach($this->extractTokens($macroTokens) as $translation) {
					$translation[GettextExtractor_Extractor::LINE] = $token->line;
					$data[] = $translation;
				}
			}
		}
		return $data;
	}
	
	private function extractTokens(\Latte\MacroTokens $tokens) {
		$data = [];
		$tokens->nextToken();
		$tokens->nextToken();
		$nthArguments = $this->getRequiredArguments($tokens->currentValue());
		$data = array_merge($data, $this->addNthArgument($tokens, $nthArguments));
		return $data;
	}
	
	private function getRequiredArguments($function) {
		$requiredArguments = [];
		if(isset($this->functions[$function])) {
			foreach($this->functions[$function] as $definition) {
				$requiredArguments[GettextExtractor_Extractor::SINGULAR] = $definition[GettextExtractor_Extractor::SINGULAR];
				if(isset($definition[GettextExtractor_Extractor::PLURAL])) {
					$requiredArguments[GettextExtractor_Extractor::PLURAL] = $definition[GettextExtractor_Extractor::PLURAL];
				}
			}
		}
		return $requiredArguments;
	}

	private function addNthArgument(\Latte\MacroTokens $tokens, array $nthArguments) {
		$argumentPosition = 1;
		$level = 0;
		$levelArguments = [0 => []];
		$levelRequiredArguments = [0 => $nthArguments];
		$levelTernalOperator = [0 => 0];
		$foundedTranslations = [];
		while($tokens->nextToken()) {
			if($tokens->isCurrent($tokens::T_WHITESPACE)) {
				continue;
			}
			if($tokens->isCurrent(':')) {
				if(!$levelTernalOperator[$level]) {
					$argumentPosition++;
				} else {
					$levelTernalOperator--;
				}
			} elseif($tokens->isCurrent('?')) {
				$levelTernalOperator[$level]++;
			} elseif($tokens->isCurrent('|')) {
				$tokens->nextToken();
				$levelRequiredArguments[$level] = $this->getRequiredArguments($tokens->currentValue());
			} elseif($tokens->isCurrent(['(', '['])) {
				$level++;
				$levelArguments[$level] = [];
			} elseif($tokens->isCurrent([')', ']']) || !$tokens->isNext()) {
				$finalTranslation = $this->getLevelTranslations($levelArguments[$level], $levelRequiredArguments[$level]);
				if(count($finalTranslation)) {
					$foundedTranslations[] = $finalTranslation;
				}
				$level--;
			} elseif($tokens->isCurrent(',')) {
				$argumentPosition++;
			} elseif($tokens->isCurrent($tokens::T_STRING)) {
				$levelArguments[$level][$argumentPosition] = $tokens->currentValue();
			}
		}
		return $foundedTranslations;
	}
	
	private function getLevelTranslations($levelArguments, $requiredArguments) {
		$translation = [];
		if(count($levelArguments) && count($requiredArguments)) {
			foreach($requiredArguments as $key => $requiredArgument) {
				 $translation[$key] = $this->stripQuotes($this->fixEscaping($levelArguments[$requiredArgument])); 
			}
		}
		return $translation;
	}
}
