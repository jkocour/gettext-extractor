<?php

require_once dirname(__FILE__).'/FilterTest.php';

/**
 * Test class for GettextExtractor_Filters_NetteLatteFilter.
 * Generated by PHPUnit on 2010-12-15 at 21:59:47.
 */
class GettextExtractor_Filters_NetteLatteFilterTest extends GettextExtractor_Filters_FilterTest {

	protected function setUp() {
		$this->object = new GettextExtractor_Filters_NetteLatteFilter();
		$this->file = dirname(__FILE__) . '/../../data/default.latte';
	}

	public function testFunctionCallWithVariables() {
		$messages = $this->object->extract($this->file);

		$this->assertNotContains(array(
			GettextExtractor_Extractor::LINE => 7,
			GettextExtractor_Extractor::SINGULAR => '$foo'
		),$messages);

		$this->assertNotContains(array(
			GettextExtractor_Extractor::LINE => 8,
			GettextExtractor_Extractor::SINGULAR => '$bar',
			GettextExtractor_Extractor::CONTEXT => 'context'
		),$messages);

		$this->assertNotContains(array(
			GettextExtractor_Extractor::LINE => 9,
			GettextExtractor_Extractor::SINGULAR => 'I see %d little indian!',
			GettextExtractor_Extractor::PLURAL => 'I see %d little indians!',
			GettextExtractor_Extractor::CONTEXT => '$baz'
		),$messages);
	}

	public function testConstantsArrayMethodsAndFunctions() {
		$messages = $this->object->extract(dirname(__FILE__) . '/../../data/test.latte');

		$this->assertContains(array(
			GettextExtractor_Extractor::LINE => 1,
			GettextExtractor_Extractor::SINGULAR => 'Testovaci retezec'
		),$messages);

		$this->assertNotContains(array(
			GettextExtractor_Extractor::LINE => 3,
			GettextExtractor_Extractor::SINGULAR => '69'
		),$messages);

		$this->assertNotContains(array(
			GettextExtractor_Extractor::LINE => 4,
			GettextExtractor_Extractor::SINGULAR => 'CONSTANT'
		),$messages);

		$this->assertNotContains(array(
			GettextExtractor_Extractor::LINE => 5,
			GettextExtractor_Extractor::SINGULAR => 'Class::CONSTANT'
		),$messages);

		$this->assertNotContains(array(
			GettextExtractor_Extractor::LINE => 6,
			GettextExtractor_Extractor::SINGULAR => 'Class::method()'
		),$messages);

		$this->assertNotContains(array(
			GettextExtractor_Extractor::LINE => 7,
			GettextExtractor_Extractor::SINGULAR => '$array[0]'
		),$messages);

		$this->assertNotContains(array(
			GettextExtractor_Extractor::LINE => 8,
			GettextExtractor_Extractor::SINGULAR => '$varFunc()'
		),$messages);

		$this->assertNotContains(array(
			GettextExtractor_Extractor::LINE => 9,
			GettextExtractor_Extractor::SINGULAR => '$object->method()'
		),$messages);

		$this->assertNotContains(array(
			GettextExtractor_Extractor::LINE => 10,
			GettextExtractor_Extractor::SINGULAR => 'function()'
		),$messages);

		$this->assertNotContains(array(
			GettextExtractor_Extractor::LINE => 11,
			GettextExtractor_Extractor::SINGULAR => 'function()->fluent()'
		),$messages);

		$this->assertNotContains(array(
			GettextExtractor_Extractor::LINE => 12,
			GettextExtractor_Extractor::SINGULAR => 'Class::$var[0][\'key\']($arg)->method()->method()'
		),$messages);
	}
}
