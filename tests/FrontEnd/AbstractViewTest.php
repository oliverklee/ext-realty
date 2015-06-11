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
 * @author Saskia Metzler <saskia@merlin.owl.de>
 */
class tx_realty_FrontEnd_AbstractViewTest extends Tx_Phpunit_TestCase {
	/**
	 * @var tx_realty_tests_fixtures_testingFrontEndView the fixture to test
	 */
	private $fixture = NULL;

	/**
	 * @var Tx_Oelib_TestingFramework
	 */
	private $testingFramework = NULL;

	protected function setUp() {
		$this->testingFramework = new Tx_Oelib_TestingFramework('tx_realty');
		$this->testingFramework->createFakeFrontEnd();
		/** @var tslib_fe $frontEndController */
		$frontEndController = $GLOBALS['TSFE'];
		$this->fixture = new tx_realty_tests_fixtures_testingFrontEndView(array(), $frontEndController->cObj);
	}

	protected function tearDown() {
		$this->testingFramework->cleanUp();
	}

	/**
	 * @test
	 */
	public function renderCanReturnAViewsContent() {
		self::assertEquals(
			'Hi, I am the testingFrontEndView!',
			$this->fixture->render()
		);
	}
}