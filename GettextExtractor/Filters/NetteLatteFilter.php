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

	public function __construct(private \Nette\Bridges\ApplicationLatte\TemplateFactory $templateFactory) {
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
		$template = $this->templateFactory->createTemplate();
		$latte = $template->getLatte();
		$data = [];
		// přidáme vlastní Extension pro AST průchod
		$latte->addExtension(new class($data) extends \Latte\Extension {
			public function __construct(private array &$collected) {}

			public function getPasses(): array {
				return [
					'extract' => $this->extract(...),
				];
			}

			private function extract(\Latte\Compiler\Nodes\TemplateNode $node) {
				(new \Latte\Compiler\NodeTraverser())->traverse(
					$node,
					enter: function ($currentNode) {
						if ($currentNode instanceof \Fregis\Localization\TranslationNode) {
							if (in_array($currentNode->mode, ['_', 'n_'], true)) {
								$text = reset($currentNode->args->items)->value;
								if($text instanceof \Latte\Compiler\Nodes\Php\Scalar\StringNode) {
									$this->stringNodeToRow($text, $currentNode->mode === 'n_');
								}
							}
						}

						if ($currentNode instanceof Latte\Essential\Nodes\PrintNode && $currentNode->modifier && $currentNode->modifier->filters) {
							$translationFilter = null;
							foreach($currentNode->modifier->filters as $filter) {
								if ($filter instanceof \Latte\Compiler\Nodes\Php\FilterNode && $filter->name instanceof \Latte\Compiler\Nodes\Php\IdentifierNode && in_array($filter->name->name, ['translate', 'ntranslate'], true)) {
									$translationFilter = $filter->name->name;
									break;
								}
							}
							if ($translationFilter) {
								$value = $currentNode->expression;
								if ($value instanceof \Latte\Compiler\Nodes\Php\Scalar\StringNode) {
									$this->stringNodeToRow($value, $translationFilter === 'ntranslate');
								}
							}
						}

						if($currentNode instanceof \Latte\Compiler\Nodes\Php\Expression\FilterCallNode
							&& $currentNode->expr instanceof \Latte\Compiler\Nodes\Php\Scalar\StringNode
							&& $currentNode->filter instanceof \Latte\Compiler\Nodes\Php\FilterNode
							&& $currentNode->filter->name instanceof \Latte\Compiler\Nodes\Php\IdentifierNode
							&& in_array($currentNode->filter->name->name, ['translate', 'ntranslate'], true)
						) {
							$this->stringNodeToRow($currentNode->expr, $currentNode->filter->name->name === 'ntranslate');
						}
					}
				);
			}
			private function stringNodeToRow(\Latte\Compiler\Nodes\Php\Scalar\StringNode $node, bool $plural = false) {
				$row = [
					GettextExtractor_Extractor::SINGULAR => $node->value,
					GettextExtractor_Extractor::LINE => $node->position->line ?? null,
				];
				if($plural) {
					$row[GettextExtractor_Extractor::PLURAL] = $node->value;
				}
				$this->collected[] = $row;
			}
		});

		$latte->compile($file);
		return $data;
	}
}
