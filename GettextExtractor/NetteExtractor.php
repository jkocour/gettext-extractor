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
 * @copyright Copyright (c) 2009 Karel Klima
 * @copyright Copyright (c) 2010 Ondřej Vodáček
 * @license New BSD License
 * @package Nette Extras
 */

/**
 * NetteGettextExtractor tool - designed specially for use with Nette Framework
 *
 * @author Karel Klima
 * @author Ondřej Vodáček
 * @package Nette Extras
 */
class GettextExtractor_NetteExtractor extends GettextExtractor_Extractor {

	/**
	 * Setup mandatory filters
	 *
	 * @param string|bool $logToFile
	 */
	public function __construct($logToFile = false) {
		parent::__construct($logToFile);

		// Clean up...
		$this->removeAllFilters();

		// Set basic filters
		$this->setFilter('php', 'PHP')
				->setFilter('php', 'Annotation')
				->setFilter('phtml', 'PHP')
				->setFilter('phtml', 'NetteLatte')
				->setFilter('latte', 'NetteLatte');


		$this->setFilter('js', 'JS');
		$this->addFilter('JS', new  GettextExtractor_Filters_JsFilter());

		$this->addFilter('NetteLatte', new GettextExtractor_Filters_NetteLatteFilter());
		$this->addFilter('Annotation', new GettextExtractor_Filters_AnnotationFilter());

		$this->getFilter('PHP')
				->addFunction('translate')
				->addFunction('ntranslate', 1, 1)
				->addFunction('plural', 1, 1)
				->addFunction('MenuNode', 2)
				->addFunction('setLabel');

		$this->getFilter('NetteLatte')
				->addPrefix('!_')
				->addPrefix('_')
				->addPrefix('!n_', 1, 1)
				->addPrefix('n_', 1, 1)
				->addPrefix('translate')
				->addPrefix('ntranslate');
	}

	/**
	 * Optional setup of Forms translations
	 *
	 * @return NetteGettextExtractor
	 */
	public function setupForms() {
		$php = $this->getFilter('PHP');
		$php->addFunction('setText')
				->addFunction('setEmptyValue')
				->addFunction('setValue')
				->addFunction('addButton', 2)
				->addFunction('addCheckbox', 2)
				->addFunction('addError')
				->addFunction('addPrice', 2)
				->addFunction('addRemoteSelect', 2)
				->addFunction('addFile', 2) // Nette 0.9
				->addFunction('addGroup')
				->addFunction('addImage', 3)
				->addFunction('addMultiSelect', 2)
				->addFunction('addMultiSelect', 3)
				->addFunction('addPassword', 2)
				->addFunction('addRadioList', 2)
				->addFunction('addRadioList', 3)
				->addFunction('addRule', 2)
				->addFunction('addSelect', 2)
				->addFunction('addSelect', 3)
				->addFunction('addSubmit', 2)
				->addFunction('addText', 2)
				->addFunction('addTextArea', 2)
				->addFunction('addUpload', 2) // Nette 2.0
				->addFunction('addDatePicker', 2)
				->addFunction('addTimePicker', 2)
				->addFunction('addDateTimePicker', 2)
				->addFunction('addTagSelect', 2)
				->addFunction('addTimeSpinner', 2)
				->addFunction('addMultiRadioList', 2)
				->addFunction('addMultiRadioList', 3)
				->addFunction('addMultiCheckboxList', 2)
				->addFunction('addMultiCheckboxList', 3)
				->addFunction('setRequired')
				->addFunction('setDefaultValue')
				->addFunction('setPrompt')
				->addFunction('addProtection')
				->addFunction('allowUpload');

		return $this;
	}

	/**
	 * Optional setup of DataGrid component translations
	 *
	 * @return NetteGettextExtractor
	 */
	public function setupDataGrid() {
		$php = $this->getFilter('PHP');
		$php->addFunction('addColumn', 2)
				->addFunction('addNumericColumn', 2)
				->addFunction('addDateColumn', 2)
				->addFunction('addCheckboxColumn', 2)
				->addFunction('addImageColumn', 2)
				->addFunction('addFileColumn', 2)
				->addFunction('addPriceColumn', 2)
				->addFunction('addOrderColumn', 3)
				->addFunction('addPositionColumn', 2)
				->addFunction('addActionColumn')
				->addFunction('addAction')
				->addFunction('setLabel');

		return $this;
	}

	/**
	 * Return data for translation, please run scan method first
	 * @return array
	 */
	public function getData() {
		return $this->data;
	}

	public function addData($data) {
		foreach ($data as $msg => $position) {
			if (!isset($this->data[$msg])) {
				$this->data[$msg] = [];
			}
			$this->data[$msg] = Nette\Utils\Arrays::mergeTree($this->data[$msg], $position);
		}
		return $this;
	}

}
