<?php
/***************************************************************
* Copyright notice
*
* (c) 2008-2012 Saskia Metzler <saskia@merlin.owl.de>
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

require_once(t3lib_extMgm::extPath('oelib') . 'class.tx_oelib_Autoloader.php');

require_once(t3lib_extMgm::extPath('realty') . 'lib/tx_realty_constants.php');

/**
 * Unit tests for the tx_realty_frontEndForm class in the "realty" extension.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 */
class tx_realty_FrontEnd_FormTest extends tx_phpunit_testcase {
	/**
	 * @var tx_realty_frontEndForm object to be tested
	 */
	private $fixture;
	/**
	 * @var tx_oelib_testingFramework
	 */
	private $testingFramework;

	/**
	 * @var integer dummy FE user UID
	 */
	private $feUserUid;
	/**
	 * @var integer UID of the dummy object
	 */
	private $dummyObjectUid = 0;

	public function setUp() {
		tx_oelib_headerProxyFactory::getInstance()->enableTestMode();
		$this->testingFramework = new tx_oelib_testingFramework('tx_realty');
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

		$this->fixture = new tx_realty_frontEndEditor(
			array(),
			$GLOBALS['TSFE']->cObj,
			0,
			'',
			TRUE
		);
	}

	public function tearDown() {
		$this->testingFramework->cleanUp();

		$this->fixture->__destruct();
		unset($this->fixture, $this->testingFramework);
	}


	///////////////////////
	// Utility functions.
	///////////////////////

	/**
	 * Creates dummy records in the DB.
	 */
	private function createDummyRecords() {
		$this->feUserUid = $this->testingFramework->createFrontEndUser();
		$this->dummyObjectUid = $this->testingFramework->createRecord(
			REALTY_TABLE_OBJECTS
		);
	}


	//////////////////////////////////////
	// Functions to be used by the form.
	//////////////////////////////////////
	// * getRedirectUrl().
	////////////////////////

	public function testGetRedirectUrlReturnsUrlWithRedirectPidForConfiguredRedirectPid() {
		$fePageUid = $this->testingFramework->createFrontEndPage();
		$this->fixture->setConfigurationValue('feEditorRedirectPid', $fePageUid);

		$this->assertContains(
			'?id=' . $fePageUid,
			$this->fixture->getRedirectUrl()
		);
	}

	public function testGetRedirectUrlReturnsUrlWithoutRedirectPidForMisconfiguredRedirectPid() {
		$nonExistingFePageUid = $this->testingFramework->createFrontEndPage(
			0, array('deleted' => 1)
		);
		$this->fixture->setConfigurationValue(
			'feEditorRedirectPid', $nonExistingFePageUid
		);

		$this->assertNotContains(
			'?id=' . $nonExistingFePageUid,
			$this->fixture->getRedirectUrl()
		);
	}

	public function testGetRedirectUrlReturnsUrlWithoutRedirectPidForNonConfiguredRedirectPid() {
		$this->fixture->setConfigurationValue('feEditorRedirectPid', '0');

		$this->assertNotContains(
			'?id=0',
			$this->fixture->getRedirectUrl()
		);
	}


	///////////////////////////////////////
	// Tests concerning the HTML template
	///////////////////////////////////////

	public function testGetTemplatePathReturnsAbsolutePathFromTheConfiguration() {
		$this->assertRegExp(
			'/\/typo3conf\/ext\/realty\/pi1\/tx_realty_frontEndEditor\.html$/',
			tx_realty_frontEndForm::getTemplatePath()
		);
	}
}
?>