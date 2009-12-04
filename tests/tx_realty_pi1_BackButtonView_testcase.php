<?php
/***************************************************************
* Copyright notice
*
* (c) 2009 Bernd Schönbach <bernd@oliverklee.de>
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

/**
 * Unit tests for the tx_realty_pi1_BackButtonView class in the "realty"
 * extension.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Bernd Schönbach <bernd@oliverklee.de>
 */
class tx_realty_pi1_BackButtonView_testcase extends tx_phpunit_testcase {
	/**
	 * @var tx_realty_pi1_BackButtonView
	 */
	private $fixture;

	/**
	 * @var tx_oelib_testingFramework
	 */
	private $testingFramework;

	public function setUp() {
		$this->testingFramework = new tx_oelib_testingFramework('tx_realty');
		$this->testingFramework->createFakeFrontEnd();

		$this->fixture = new tx_realty_pi1_BackButtonView(
			array('templateFile' => 'EXT:realty/pi1/tx_realty_pi1.tpl.htm'),
			$GLOBALS['TSFE']->cObj
		);
	}

	public function tearDown() {
		$this->testingFramework->cleanUp();

		$this->fixture->__destruct();
		unset($this->fixture, $this->testingFramework);
	}


	////////////////////////////////////
	// Testing the basic functionality
	////////////////////////////////////

	/**
	 * @test
	 */
	public function renderReturnsButtonBack() {
		$this->assertContains(
			'class="button singleViewBack"',
			$this->fixture->render(array('showUid' => 0))
		);
	}


	//////////////////////////////
	// Tests concerning the link
	//////////////////////////////

	/**
	 * @test
	 */
	public function forPreviousNextButtonsDisabledAndNoListUidViewIsJavaScriptBack() {
		$this->fixture->setConfigurationValue(
			'enableNextPreviousButtons', FALSE
		);

		$this->assertContains(
			'history.back();',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function forPreviousNextButtonsDisabledAndListUidViewIsJavaScriptBack() {
		$this->fixture->setConfigurationValue(
			'enableNextPreviousButtons', FALSE
		);
		$listUid = $this->testingFramework->createContentElement(
				$this->testingFramework->createFrontEndPage()
		);
		$this->fixture->piVars['listUid'] = $listUid;

		$this->assertContains(
			'history.back();',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function forPreviousNextButtonsDisabledAndSingleViewPartNextPreviousButtonEnabledIsJavaScriptBack() {
		$this->fixture->setConfigurationValue(
			'enableNextPreviousButtons', FALSE
		);
		$this->fixture->setConfigurationValue(
			'singleViewPartsToDisplay', 'nextPreviousButtons'
		);
		$listUid = $this->testingFramework->createContentElement(
				$this->testingFramework->createFrontEndPage()
		);
		$this->fixture->piVars['listUid'] = $listUid;

		$this->assertContains(
			'history.back();',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function forPreviousNextButtonsEnabledAndNoListUidViewIsJavaScriptBack() {
		$this->fixture->setConfigurationValue(
			'enableNextPreviousButtons', TRUE
		);

		$this->assertContains(
			'history.back();',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function forPreviousNextButtonsEnabledAndSingleViewPartNotContainsNextPreviousButtonIsJavaScriptBack() {
		$this->fixture->setConfigurationValue(
			'enableNextPreviousButtons', TRUE
		);
		$this->fixture->setConfigurationValue(
			'singleViewPartsToDisplay', 'backButton'
		);
		$listUid = $this->testingFramework->createContentElement(
			$this->testingFramework->createFrontEndPage()
		);
		$this->fixture->piVars['listUid'] = $listUid;

		$this->assertContains(
			'history.back();',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function forPreviousNextButtonsEnabledAndListUidViewNotIsJavaScriptBack() {
		$this->fixture->setConfigurationValue(
			'enableNextPreviousButtons', TRUE
		);
		$this->fixture->setConfigurationValue(
			'singleViewPartsToDisplay', 'nextPreviousButtons'
		);

		$listViewPageUid = $this->testingFramework->createFrontEndPage();
		$listUid = $this->testingFramework->createContentElement(
			$listViewPageUid
		);
		$this->fixture->piVars['listUid'] = $listUid;


		$this->assertNotContains(
			'history.back();',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function forPreviousNextButtonsEnabledAndListUidViewContainsLinkToListViewPage() {
		$this->fixture->setConfigurationValue(
			'enableNextPreviousButtons', TRUE
		);
		$this->fixture->setConfigurationValue(
			'singleViewPartsToDisplay', 'nextPreviousButtons'
		);
		$listViewPageUid = $this->testingFramework->createFrontEndPage();
		$listUid = $this->testingFramework->createContentElement(
			$listViewPageUid
		);
		$this->fixture->piVars['listUid'] = $listUid;

		$this->assertContains(
			'?id=' . $listViewPageUid,
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function forPreviousNextButtonsEnabledAndListViewLimitationSetAddsListViewLimitationDecodedToPiVar() {
		$this->fixture->setConfigurationValue(
			'enableNextPreviousButtons', TRUE
		);
		$this->fixture->setConfigurationValue(
			'singleViewPartsToDisplay', 'nextPreviousButtons'
		);
		$listViewPageUid = $this->testingFramework->createFrontEndPage();
		$listUid = $this->testingFramework->createContentElement(
			$listViewPageUid
		);
		$listViewLimitation = base64_encode(
			serialize(array('objectNumber' => 'foo')));
		$this->fixture->piVars['listUid'] = $listUid;
		$this->fixture->piVars['listViewLimitation'] = $listViewLimitation;

		$this->assertContains(
			'objectNumber]=foo',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function forPreviousNextButtonsEnabledAndFooSetAsPiVarNotAddsFooToBackLink() {
		$this->fixture->setConfigurationValue(
			'enableNextPreviousButtons', TRUE
		);
		$this->fixture->setConfigurationValue(
			'singleViewPartsToDisplay', 'nextPreviousButtons'
		);
		$listViewPageUid = $this->testingFramework->createFrontEndPage();
		$listUid = $this->testingFramework->createContentElement(
			$listViewPageUid
		);
		$this->fixture->piVars['listUid'] = $listUid;
		$this->fixture->piVars['foo'] = 'bar';

		$this->assertNotContains(
			'foo',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function forPreviousNextButtonsEnabledAndListUidSetToStringDoesNotAddListUidStringToLink() {
		$this->fixture->setConfigurationValue(
			'enableNextPreviousButtons', TRUE
		);
		$this->fixture->setConfigurationValue(
			'singleViewPartsToDisplay', 'nextPreviousButtons'
		);
		$listViewPageUid = $this->testingFramework->createFrontEndPage();
		$listUid = $this->testingFramework->createContentElement(
			$listViewPageUid
		);
		$this->fixture->piVars['listUid'] = 'fooo';

		$this->assertNotContains(
			'fooo',
			$this->fixture->render()
		);
	}
}
?>