<?php

require_once 'toplytics-unittestcase.php';

class Test_Toplytics extends Toplytics_UnitTestCase {
	private $toplytics;

	function setup() {
		global $toplytics;
		$this->toplytics = $toplytics;

		add_option( 'toplytics_result_today', array( 'result' => array( '166384' => '100', '166369' => '10' ) ) );
		add_option( 'toplytics_result_month', array( 'result' => array( '166384' => '864', '166369' => '84' ) ) );
		add_option( 'toplytics_result_week', array( 'result' => array( '166384' => '300', '166369' => '30' ) ) );
		add_option( 'toplytics_result_2weeks', array( 'result' => array( '166384' => '400', '166369' => '40' ) ) );
	}

	function teardown() {
	}

	function test_toplytics_is_activated() {
		$this->assertTrue( is_plugin_active( $this->_plugin_filename ) );
	}

	function test_toplytics_get_result_case_1() {
		$assert = $this->toplytics->get_result( 'today' ) == array(
			'166384' => '100', '166369' => '10',
		);
		$this->assertTrue( $assert );
	}

	function test_toplytics_get_result_case_2() {
		$assert = $this->toplytics->get_result( 'week' ) == array(
			'166384' => '300', '166369' => '30',
		);
		$this->assertTrue( $assert );
	}

	function test_toplytics_get_result_case_3() {
		$assert = $this->toplytics->get_result( '2weeks' ) == array(
			'166384' => '400', '166369' => '40',
		);
		$this->assertTrue( $assert );
	}

	function test_toplytics_get_result_case_4() {
		$assert = $this->toplytics->get_result( 'month' ) == array(
			'166384' => '864', '166369' => '84',
		);
		$this->assertTrue( $assert );
	}
}
