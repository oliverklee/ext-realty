<?php
/***************************************************************
* Copyright notice
*
* (c) 2009-2013 Saskia Metzler <saskia@merlin.owl.de>
* All rights reserved
*
* This script is part of the TYPO3 project. The TYPO3 project is
* free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation; either version 2 of the License, or
* (at your option) any later version.
*
* The GNU General Public License can be found at
* http://www.gnu.org/copyleft/gpl.html.
*
* This script is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

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

	public function setUp() {
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

	public function tearDown() {
		$this->testingFramework->cleanUp();

		$this->fixture->__destruct();
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
?>