<?php

require_once 'toplytics-unittestcase.php';

class Test_Toplytics extends Toplytics_UnitTestCase {
	private $toplytics;

	function setup() {
		global $toplytics;
		$this->toplytics = $toplytics;

		set_transient(
			'toplytics_cached_results',
			array(
				'_ts'     => time(),
				'monthly' => array( '166384' => '864', '166369' => '84' ),
				'2weeks'  => array( '166384' => '400', '166369' => '40' ),
				'weekly'  => array( '166384' => '300', '166369' => '30' ),
				'daily'   => array( '166384' => '100', '166369' => '10' ),
			)
		);
	}

	function teardown() {
	}

	function test_toplytics_is_activated() {
		$this->assertTrue( is_plugin_active( $this->_plugin_filename ) );
	}

	function test_toplytics_get_data_case_1() {
		$assert = $this->toplytics->get_data( 'daily' ) == array(
			'166384' => '100', '166369' => '10',
		);
		$this->assertTrue( $assert );
	}

	function test_toplytics_get_data_case_2() {
		$assert = $this->toplytics->get_data( 'weekly' ) == array(
			'166384' => '300', '166369' => '30',
		);
		$this->assertTrue( $assert );
	}

	function test_toplytics_get_data_case_3() {
		$assert = $this->toplytics->get_data( '2weeks' ) == array(
			'166384' => '400', '166369' => '40',
		);
		$this->assertTrue( $assert );
	}

	function test_toplytics_get_data_case_4() {
		$assert = $this->toplytics->get_data( 'monthly' ) == array(
			'166384' => '864', '166369' => '84',
		);
		$this->assertTrue( $assert );
	}
}
