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
class tx_realty_FrontEnd_FormTest extends Tx_Phpunit_TestCase {
	/**
	 * @var tx_realty_frontEndForm object to be tested
	 */
	private $fixture = NULL;
	/**
	 * @var Tx_Oelib_TestingFramework
	 */
	private $testingFramework = NULL;

	/**
	 * @var int dummy FE user UID
	 */
	private $feUserUid;
	/**
	 * @var int UID of the dummy object
	 */
	private $dummyObjectUid = 0;

	protected function setUp() {
		Tx_Oelib_HeaderProxyFactory::getInstance()->enableTestMode();
		$this->testingFramework = new Tx_Oelib_TestingFramework('tx_realty');
		$this->testingFramework->createFakeFrontEnd();

		$configuration = new tx_oelib_Configuration();
		$configuration->setData(
			array(
				'feEditorTemplateFile'
					=> 'EXT:realty/pi1/tx_realty_frontEndEditor.html',
			)
		);
		tx_oelib_ConfigurationRegistry::getInstance()->set(
			'plugin.tx_realty_pi1', $configuration
		);

		$this->createDummyRecords();

		/** @var TypoScriptFrontendController $frontEndController */
		$frontEndController = $GLOBALS['TSFE'];
		$this->fixture = new tx_realty_frontEndEditor(
			array(),
			$frontEndController->cObj,
			0,
			'',
			TRUE
		);
	}

	protected function tearDown() {
		$this->testingFramework->cleanUp();
	}


	///////////////////////
	// Utility functions.
	///////////////////////

	/**
	 * Creates dummy records in the DB.
	 *
	 * @return void
	 */
	private function createDummyRecords() {
		$this->feUserUid = $this->testingFramework->createFrontEndUser();
		$this->dummyObjectUid = $this->testingFramework->createRecord(
			'tx_realty_objects'
		);
	}


	//////////////////////////////////////
	// Functions to be used by the form.
	//////////////////////////////////////
	// * getRedirectUrl().
	////////////////////////

	/**
	 * @test
	 */
	public function getRedirectUrlReturnsUrlWithRedirectPidForConfiguredRedirectPid() {
		$fePageUid = $this->testingFramework->createFrontEndPage();
		$this->fixture->setConfigurationValue('feEditorRedirectPid', $fePageUid);

		self::assertContains(
			'?id=' . $fePageUid,
			$this->fixture->getRedirectUrl()
		);
	}

	/**
	 * @test
	 */
	public function getRedirectUrlReturnsUrlWithoutRedirectPidForMisconfiguredRedirectPid() {
		$nonExistingFePageUid = $this->testingFramework->createFrontEndPage(
			0, array('deleted' => 1)
		);
		$this->fixture->setConfigurationValue(
			'feEditorRedirectPid', $nonExistingFePageUid
		);

		self::assertNotContains(
			'?id=' . $nonExistingFePageUid,
			$this->fixture->getRedirectUrl()
		);
	}

	/**
	 * @test
	 */
	public function getRedirectUrlReturnsUrlWithoutRedirectPidForNonConfiguredRedirectPid() {
		$this->fixture->setConfigurationValue('feEditorRedirectPid', '0');

		self::assertNotContains(
			'?id=0',
			$this->fixture->getRedirectUrl()
		);
	}


	///////////////////////////////////////
	// Tests concerning the HTML template
	///////////////////////////////////////

	/**
	 * @test
	 */
	public function getTemplatePathReturnsAbsolutePathFromTheConfiguration() {
		self::assertRegExp(
			'/\/typo3conf\/ext\/realty\/pi1\/tx_realty_frontEndEditor\.html$/',
			tx_realty_frontEndForm::getTemplatePath()
		);
	}
}