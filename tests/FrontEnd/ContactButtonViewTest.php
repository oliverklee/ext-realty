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
class tx_realty_FrontEnd_ContactButtonViewTest extends tx_phpunit_testcase {
	/**
	 * @var tx_realty_pi1_ContactButtonView
	 */
	private $fixture;

	/**
	 * @var tx_oelib_testingFramework
	 */
	private $testingFramework;

	protected function setUp() {
		$this->testingFramework = new tx_oelib_testingFramework('tx_realty');
		$this->testingFramework->createFakeFrontEnd();

		$this->fixture = new tx_realty_pi1_ContactButtonView(
			array('templateFile' => 'EXT:realty/pi1/tx_realty_pi1.tpl.htm'),
			$GLOBALS['TSFE']->cObj
		);
		$this->fixture->setConfigurationValue(
			'contactPID', $this->testingFramework->createFrontEndPage()
		);
	}

	protected function tearDown() {
		$this->testingFramework->cleanUp();

		unset($this->fixture, $this->testingFramework);
	}


	////////////////////////////////////
	// Testing the contact button view
	////////////////////////////////////

	/**
	 * @test
	 */
	public function renderReturnsNonEmptyResultForZeroShowUid() {
		$this->assertNotEquals(
			'',
			$this->fixture->render(array('showUid' => 0))
		);
	}

	/**
	 * @test
	 */
	public function renderReturnsNonEmptyResultForShowUidOfRealtyRecordProvided() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array('title' => 'test title'));

		$this->assertNotEquals(
			'',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	/**
	 * @test
	 */
	public function renderReturnsProvidedShowUidOfRealtyRecordAsLinkParameter() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array('title' => 'test title'));

		$this->assertContains(
			'tx_realty_pi1[showUid]=' . $realtyObject->getUid(),
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	/**
	 * @test
	 */
	public function renderReturnsNoUnreplacedMarkersWhileTheResultIsNonEmpty() {
		$this->assertNotContains(
			'###',
			$this->fixture->render(array('showUid' => 0))
		);
	}

	/**
	 * @test
	 */
	public function renderReturnsEmptyResultForTheCurrentPageBeingTheSameAsTheConfiguredContactPid() {
		$this->fixture->setConfigurationValue('contactPID', $GLOBALS['TSFE']->id);

		$this->assertEquals(
			'',
			$this->fixture->render(array('showUid' => 0))
		);
	}

	/**
	 * @test
	 */
	public function renderReturnsEmptyResultForNoContactPidConfigured() {
		$this->fixture->setConfigurationValue('contactPID', '');

		$this->assertEquals(
			'',
			$this->fixture->render(array('showUid' => 0))
		);
	}
}