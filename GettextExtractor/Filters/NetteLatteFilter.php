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
				try {
					$macroTokens = new \Latte\MacroTokens($token->text);
					foreach($this->extractTokens($macroTokens) as $translation) {
						$translation[GettextExtractor_Extractor::LINE] = $token->line;
						$data[] = $translation;
					}
				} catch(Latte\CompileException $e) {
					
				}
			}
		}
		return $data;
	}
	
	private function extractTokens(\Latte\MacroTokens $tokens) {
		$tokens->nextToken();
		foreach($this->getTranslations($tokens) as $translation) {
			yield $translation;
		}
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

	private function getTranslations(\Latte\MacroTokens $tokens, &$parentArgument = NULL) {
		$argumentPosition = 1;
		$ternalOperator = 0;
		$currentArgument = NULL;
		$translations = [];
		$requiredArguments = [];
		$arguments = [];
		while($token = $tokens->nextToken()) {
			if($tokens->isCurrent($tokens::T_WHITESPACE)) {
				continue;
			}
			if($tokens->isCurrent(':')) {
				if(!$ternalOperator) {
					$argumentPosition++;
				} else {
					$ternalOperator--;
				}
			} elseif($tokens->isCurrent('?')) {
				$ternalOperator++;
			} elseif($tokens->isCurrent($tokens::T_SYMBOL) && !$arguments) {
				
				$requiredArguments = $this->getRequiredArguments($tokens->currentValue());
			} elseif($tokens->isCurrent('|')) {
				$tokens->nextToken();
				if(!$requiredArguments) {
					$requiredArguments = $this->getRequiredArguments($tokens->currentValue());
				}
			} elseif($tokens->isCurrent('(', '[')) {
				$translations = $this->getTranslations($tokens, $currentArgument);
				foreach($translations as $translation) {
					yield $translation;
				}
			} elseif($tokens->isCurrent(')', ']') || !$tokens->isNext()) {
				$arguments[count($arguments)+1] = $currentArgument;
				$finalTranslation = $this->getLevelTranslations($arguments, $requiredArguments);
				if(count($finalTranslation)) {
					yield $finalTranslation;
				} else {
					$parentArgument = $currentArgument;
				}
				break;
			} elseif($tokens->isCurrent(',')) {
				$arguments[count($arguments)+1] = $currentArgument;
				$argumentPosition++;
				$currentArgument = NULL;
			} elseif($tokens->isCurrent($tokens::T_STRING)) {
				$currentArgument = $tokens->currentValue();
			}
		}
		
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
