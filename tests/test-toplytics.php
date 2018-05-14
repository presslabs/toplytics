<?php

use function Patchwork\always;
use function Patchwork\redefine;
use function Patchwork\restoreAll;


class ToplyticsTest extends WP_UnitTestCase {
	public function tearDown() {
		restoreAll();
		parent::tearDown();
	}

}
