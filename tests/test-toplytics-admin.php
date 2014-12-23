<?php

require_once 'toplytics-unittestcase.php';
require_once dirname( __FILE__ ) . '/../toplytics/inc/class-toplytics-admin.php';

class Test_Toplytics_Admin extends Toplytics_UnitTestCase {
	private $toplytics;

	function setup() {
		global $toplytics;
		$this->toplytics = $toplytics;
	}

	function teardown() {
	}

	function test_toplytics_plugin_basename() {
		$this->assertSame( 'toplytics/toplytics.php', $this->toplytics->plugin_basename() );
	}
}
