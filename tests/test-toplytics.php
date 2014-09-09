<?php

require_once 'toplytics-unittestcase.php';

class Test_Toplytics extends Toplytics_UnitTestCase {
	function setup() {
		set_transient(
			'toplytics.cache',
			array(
				'_ts'    => '1407490430',
				'month'  => array( '166384' => '864', '166369' => '84' ),
				'today'  => array( '166384' => '100', '166369' => '10' ),
				'2weeks' => array( '166384' => '400', '166369' => '40' ),
				'week'   => array( '166384' => '300', '166369' => '30' ),
			)
		);
	}

	function teardown() {
	}

	function test_toplytics_is_activated() {
		$this->assertTrue( is_plugin_active( $this->_plugin_filename ) );
	}

	function test_toplytics_get_results_case_1() {
		$args   = array( 'period' => 'today', 'numberposts' => 1 );
		$assert = toplytics_get_results( $args ) == array(
			'166384' => '100',
		);
		$this->assertTrue( $assert );
	}

	function test_toplytics_get_results_case_2() {
		set_transient( 'toplytics.cache', array( '_ts' => '1407490430' ) );
		$args   = array( 'period' => 'today', 'numberposts' => 1 );
		$assert = toplytics_get_results( $args ) == false;
		$this->assertTrue( $assert );
	}

	function test_toplytics_get_results_case_3() {
		set_transient( 'toplytics.cache', array() );
		$args   = array( 'period' => 'today', 'numberposts' => 1 );
		$assert = toplytics_get_results( $args ) == false;
		$this->assertTrue( $assert );
	}

	function test_toplytics_get_results_case_4() {
		set_transient( 'toplytics.cache', null );
		$args   = array( 'period' => 'today', 'numberposts' => 1 );
		$assert = toplytics_get_results( $args ) == false;
		$this->assertTrue( $assert );
	}

	function test_toplytics_get_results_case_5() {
		set_transient( 'toplytics.cache', array() );
		$args   = array();
		$assert = toplytics_get_results( $args ) == false;
		$this->assertTrue( $assert );
	}

	function test_toplytics_get_results_case_6() {
		set_transient( 'toplytics.cache', '' );
		$args   = array( 'period' => 'today', 'numberposts' => 1 );
		$assert = toplytics_get_results( $args ) == false;
		$this->assertTrue( $assert );
	}

	function test_toplytics_get_results_case_7() {
		set_transient( 'toplytics.cache', '' );
		$assert = toplytics_get_results( null ) == false;
		$this->assertTrue( $assert );
	}

	function test_toplytics_get_results_case_8() {
		set_transient( 'toplytics.cache', '' );
		$assert = toplytics_get_results() == false;
		$this->assertTrue( $assert );
	}

	function test_toplytics_get_results_case_9() {
		$args   = array( 'period' => '2weeks', 'numberposts' => 1 );
		$assert = toplytics_get_results( $args ) == array( '166384' => 400 );
		$this->assertTrue( $assert );
	}

	function test_toplytics_get_results_case_10() {
		$args   = array( 'period' => '2weeks', 'numberposts' => 2 );
		$assert = toplytics_get_results( $args ) == array( '166384' => 400, '166369' => 40 );
		$this->assertTrue( $assert );
	}

	function test_get_results() {
		$result = Toplytics_Statistics::get_results();
		$this->assertEquals( get_transient( 'toplytics.cache' ), $result );
	}

	function test_set_token_and_secret() {
		$auth = new Toplytics_Auth();
		$auth->set_token_and_secret( 'token','secret' );
		$this->assertEquals( get_option( 'toplytics_oa_anon_token' ), 'token' );
		$this->assertEquals( get_option( 'toplytics_oa_anon_secret' ), 'secret' );
	}

	function test_remove_token_and_secret() {
		$auth = new Toplytics_Auth();

		$auth->set_token_and_secret( 'token','secret' );
		$this->assertEquals( get_option( 'toplytics_oa_anon_token' ), 'token' );
		$this->assertEquals( get_option( 'toplytics_oa_anon_secret' ), 'secret' );

		$auth->remove_token_and_secret();
		$this->assertEquals( get_option( 'toplytics_oa_anon_token' ), false );
		$this->assertEquals( get_option( 'toplytics_oa_anon_secret' ), false );
	}

	function test_split_params() {
		$auth = new Toplytics_Auth();

		$this->assertEquals(
			$auth->split_params( 'foo=100&bar=200&goo=http://www.example.com/' ),
			array( 'foo' => urldecode( 100 ), 'bar' => urldecode( 200 ), 'goo' => urldecode( 'http://www.example.com/' ) )
		);
	}

	function test_filter_all_posts() {
		$results       = array();
		$return_values = get_transient( 'toplytics.cache' );
		Toplytics_Statistics::filter_all_posts( $return_values, $results, 'month' );
		$this->assertEquals( $results, array() );
	}
}
