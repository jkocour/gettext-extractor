<?php

require_once dirname(__FILE__).'/FilterTest.php';
require_once dirname(__FILE__).'/../../Filters/NetteLatteFilter.php';

/**
 * Test class for NetteLatteFilter.
 * Generated by PHPUnit on 2010-12-15 at 21:59:47.
 */
class NetteLatteFilterTest extends FilterTest {

	protected function setUp() {
		$this->object = new NetteLatteFilter();
		$this->file = dirname(__FILE__) . '/../data/default.latte';
	}

	public function testFunctionCallWithVariables() {
		$messages = $this->object->extract($this->file);
		var_dump($messages);

		$this->assertNotContains(array(
			iFilter::LINE => 7,
			iFilter::SINGULAR => '$foo'
		),$messages);

		$this->assertNotContains(array(
			iFilter::LINE => 8,
			iFilter::SINGULAR => '$bar',
			iFilter::CONTEXT => 'context'
		),$messages);

		$this->assertNotContains(array(
			iFilter::LINE => 9,
			iFilter::SINGULAR => 'I see %d little indian!',
			iFilter::PLURAL => 'I see %d little indians!',
			iFilter::CONTEXT => '$baz'
		),$messages);
	}
}