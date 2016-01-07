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
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Test case.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 */
class tx_realty_FrontEnd_ContactButtonViewTest extends Tx_Phpunit_TestCase {
	/**
	 * @var tx_realty_pi1_ContactButtonView
	 */
	private $fixture = NULL;

	/**
	 * @var Tx_Oelib_TestingFramework
	 */
	private $testingFramework = NULL;

	protected function setUp() {
		$this->testingFramework = new Tx_Oelib_TestingFramework('tx_realty');
		$this->testingFramework->createFakeFrontEnd();

		$this->fixture = new tx_realty_pi1_ContactButtonView(
			array('templateFile' => 'EXT:realty/pi1/tx_realty_pi1.tpl.htm'), $this->getFrontEndController()->cObj
		);
		$this->fixture->setConfigurationValue(
			'contactPID', $this->testingFramework->createFrontEndPage()
		);
	}

	protected function tearDown() {
		$this->testingFramework->cleanUp();
	}

	/**
	 * Returns the current front-end instance.
	 *
	 * @return TypoScriptFrontendController
	 */
	private function getFrontEndController() {
		return $GLOBALS['TSFE'];
	}

	////////////////////////////////////
	// Testing the contact button view
	////////////////////////////////////

	/**
	 * @test
	 */
	public function renderReturnsNonEmptyResultForZeroShowUid() {
		self::assertNotEquals(
			'',
			$this->fixture->render(array('showUid' => 0))
		);
	}

	/**
	 * @test
	 */
	public function renderReturnsNonEmptyResultForShowUidOfRealtyRecordProvided() {
		$realtyObject = Tx_Oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array('title' => 'test title'));

		self::assertNotEquals(
			'',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	/**
	 * @test
	 */
	public function renderReturnsProvidedShowUidOfRealtyRecordAsLinkParameter() {
		$realtyObject = Tx_Oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array('title' => 'test title'));

		self::assertContains(
			'tx_realty_pi1[showUid]=' . $realtyObject->getUid(),
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	/**
	 * @test
	 */
	public function renderReturnsNoUnreplacedMarkersWhileTheResultIsNonEmpty() {
		self::assertNotContains(
			'###',
			$this->fixture->render(array('showUid' => 0))
		);
	}

	/**
	 * @test
	 */
	public function renderReturnsEmptyResultForTheCurrentPageBeingTheSameAsTheConfiguredContactPid() {
		$this->fixture->setConfigurationValue('contactPID', $this->getFrontEndController()->id);

		self::assertEquals(
			'',
			$this->fixture->render(array('showUid' => 0))
		);
	}

	/**
	 * @test
	 */
	public function renderReturnsEmptyResultForNoContactPidConfigured() {
		$this->fixture->setConfigurationValue('contactPID', '');

		self::assertEquals(
			'',
			$this->fixture->render(array('showUid' => 0))
		);
	}
}