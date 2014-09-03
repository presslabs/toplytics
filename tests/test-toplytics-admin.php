<?php
require_once 'toplytics-unittestcase.php';
require_once dirname( __FILE__ ) . '/../toplytics/toplytics-admin.php';

class Test_Toplytics_Admin extends Toplytics_UnitTestCase {
	function setup() {
	}

	function teardown() {
	}

	function test_toplytics_plugin_basename() {
		$this->assertSame( 'toplytics/toplytics.php', toplytics_plugin_basename() );
	}

	function test_toplytics_validate_args_case_1() {
		$input  = null;
		$output = array(
			'showviews'   => false,
			'period'      => 'month',
			'numberposts' => TOPLYTICS_DEFAULT_POSTS,
		);
		$this->assertTrue( $output == toplytics_validate_args( $input ) );
	}

	function test_toplytics_validate_args_case_2() {
		$input = array(
			'showviews'   => 100,
		);
		$output = array(
			'showviews'   => true,
			'period'      => 'month',
			'numberposts' => TOPLYTICS_DEFAULT_POSTS,
		);
		$this->assertTrue( $output == toplytics_validate_args( $input ) );
	}

	function test_toplytics_validate_args_case_3() {
		$input = array(
			'showviews'   => false,
			'period'      => 'month',
		);
		$output = array(
			'showviews'   => true,
			'period'      => 'month',
			'numberposts' => TOPLYTICS_DEFAULT_POSTS,
		);
		$this->assertTrue( $output == toplytics_validate_args( $input ) );
	}

	function test_toplytics_validate_args_case_4() {
		$input = array(
			'showviews'   => 'yes',
			'period'      => 'today',
			'numberposts' => 5,
		);
		$output = array(
			'showviews'   => true,
			'period'      => 'today',
			'numberposts' => 5,
		);
		$this->assertTrue( $output == toplytics_validate_args( $input ) );
	}

	function test_toplytics_validate_args_case_5() {
		$input = array(
			'showviews'   => 100,
			'period'      => '2weeks',
			'numberposts' => 1,
		);
		$output = array(
			'showviews'   => true,
			'period'      => '2weeks',
			'numberposts' => TOPLYTICS_MIN_POSTS,
		);
		$this->assertTrue( $output == toplytics_validate_args( $input ) );
	}

	function test_toplytics_validate_args_case_6() {
		$input = array(
			'showviews'   => 100,
			'period'      => 'week',
			'numberposts' => TOPLYTICS_MIN_POSTS - 1,
		);
		$output = array(
			'showviews'   => true,
			'period'      => 'week',
			'numberposts' => TOPLYTICS_MIN_POSTS,
		);
		$this->assertTrue( $output == toplytics_validate_args( $input ) );
	}

	function test_toplytics_validate_args_case_7() {
		$input = array(
			'period'      => 'today',
			'numberposts' => TOPLYTICS_MAX_POSTS + 1,
		);
		$output = array(
			'showviews'   => false,
			'period'      => 'today',
			'numberposts' => TOPLYTICS_MAX_POSTS,
		);
		$this->assertTrue( $output == toplytics_validate_args( $input ) );
	}

	function test_toplytics_validate_args_case_8() {
		$input = array(
			'showviews'   => 100,
			'period'      => 'month',
			'numberposts' => 1,
		);
		$output = array(
			'showviews'   => true,
			'period'      => 'month',
			'numberposts' => TOPLYTICS_MIN_POSTS,
		);
		$this->assertTrue( $output == toplytics_validate_args( $input ) );
	}

	function test_toplytics_validate_args_case_9() {
		$input = array(
			'showviews'   => 100,
			'period'      => 'month',
			'numberposts' => 0,
		);
		$output = array(
			'showviews'   => true,
			'period'      => 'month',
			'numberposts' => TOPLYTICS_MIN_POSTS,
		);
		$this->assertTrue( $output == toplytics_validate_args( $input ) );
	}

	function test_toplytics_validate_args_case_10() {
		$input = array(
			'showviews'   => 100,
			'period'      => 'month',
			'numberposts' => -1,
		);
		$output = array(
			'showviews'   => true,
			'period'      => 'month',
			'numberposts' => TOPLYTICS_DEFAULT_POSTS,
		);
		$this->assertTrue( $output == toplytics_validate_args( $input ) );
	}

}
