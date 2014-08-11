<?php
class Toplytics_UnitTestCase extends WP_UnitTestCase {
	protected $_plugin_filename = 'toplytics/toplytics.php';

	public function setup() {
	}

	public function teardown() {
	}

	function test_true() {
		$this->assertTrue( true );
	}
}
