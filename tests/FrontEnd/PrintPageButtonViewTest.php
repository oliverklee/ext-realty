<?php
/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * Test case.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Bernd Schönbach <bernd@oliverklee.de>
 */
class tx_realty_FrontEnd_PrintPageButtonViewTest extends tx_phpunit_testcase {
	/**
	 * @var tx_realty_pi1_PrintPageButtonView
	 */
	private $fixture;

	/**
	 * @var tx_oelib_testingFramework
	 */
	private $testingFramework;

	protected function setUp() {
		$this->testingFramework = new tx_oelib_testingFramework('tx_realty');
		$this->testingFramework->createFakeFrontEnd();

		$this->fixture = new tx_realty_pi1_PrintPageButtonView(
			array('templateFile' => 'EXT:realty/pi1/tx_realty_pi1.tpl.htm'),
			$GLOBALS['TSFE']->cObj
		);
	}

	protected function tearDown() {
		$this->testingFramework->cleanUp();

		unset($this->fixture, $this->testingFramework);
	}


	////////////////////////////////////
	// Testing the basic functionality
	////////////////////////////////////

	/**
	 * @test
	 */
	public function renderReturnsButtonPrint() {
		$this->assertContains(
			'class="button printPage"',
			$this->fixture->render(array('showUid' => 0))
		);
	}
}