<?php
/***************************************************************
* Copyright notice
*
* (c) 2009-2013 Bernd Schönbach <bernd@oliverklee.de>
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
 * Unit tests for the tx_realty_pi1_NextPreviousButtonsView class in the
 * "realty" extension.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Bernd Schönbach <bernd@oliverklee.de>
 */
class tx_realty_FrontEnd_NextPreviousButtonsViewTest extends tx_phpunit_testcase {
	/**
	 * @var tx_realty_pi1_NextPreviousButtonsView
	 */
	private $fixture;

	/**
	 * @var tx_oelib_testingFramework
	 */
	private $testingFramework;

	/**
	 * @var integer the UID of the "list view" content object.
	 */
	private $listViewUid = 0;

	/**
	 * the UID of a dummy city for the object records
	 *
	 * @var integer
	 */
	private $dummyCityUid = 0;

	public function setUp() {
		$this->testingFramework = new tx_oelib_testingFramework('tx_realty');
		$this->testingFramework->createFakeFrontEnd();
		$GLOBALS['TSFE']->cObj->data['pid']
			= $this->testingFramework->createFrontEndPage();
		$this->listViewUid = $this->testingFramework->createContentElement();
		$this->dummyCityUid = $this->testingFramework->createRecord(
			REALTY_TABLE_CITIES
		);

		$this->fixture = new tx_realty_pi1_NextPreviousButtonsView(
			array('templateFile' => 'EXT:realty/pi1/tx_realty_pi1.tpl.htm'),
			$GLOBALS['TSFE']->cObj
		);

		$this->fixture->setConfigurationValue('enableNextPreviousButtons', TRUE);
	}

	public function tearDown() {
		$this->testingFramework->cleanUp();

		$this->fixture->__destruct();
		unset($this->fixture, $this->testingFramework);
	}


	//////////////////////
	// Utility Functions
	//////////////////////

	/**
	 * Creates a realty object with a city.
	 *
	 * @return integer the UID of the created realty object, will be > 0
	 */
	private function createRealtyRecordWithCity() {
		return $this->testingFramework->createRecord(
			REALTY_TABLE_OBJECTS, array('city' => $this->dummyCityUid)
		);
	}


	///////////////////////////////////////////
	// Tests concerning the utility functions
	///////////////////////////////////////////

	/**
	 * @test
	 */
	public function createRealtyRecordWithCityReturnsNonZeroUid() {
		$this->assertTrue(
			$this->createRealtyRecordWithCity() > 0
		);
	}

	/**
	 * @test
	 */
	public function createRealtyRecordWithCityRunTwiceCreatesTwoDifferentRecords() {
		$this->assertTrue(
			$this->createRealtyRecordWithCity() != $this->createRealtyRecordWithCity()
		);
	}

	/**
	 * @test
	 */
	public function createRealtyRecordWithCityCreatesRealtyObjectRecord() {
		$objectUid = $this->createRealtyRecordWithCity();
		$this->assertTrue(
			$this->testingFramework->existsRecord(
				REALTY_TABLE_OBJECTS,
				'uid = ' . $objectUid . ' AND is_dummy_record = 1'
			)
		);
	}

	/**
	 * @test
	 */
	public function createRealtyRecordWithCityAddsCityToRealtyObjectRecord() {
		$objectUid = $this->createRealtyRecordWithCity();
		$this->assertTrue(
			$this->testingFramework->existsRecord(
				REALTY_TABLE_OBJECTS,
				'uid = ' . $objectUid . ' AND city > 0 AND is_dummy_record = 1'
			)
		);
	}


	////////////////////////////////
	// Testing the basic functions
	////////////////////////////////

	/**
	 * @test
	 */
	public function renderForDisabledNextPreviousButtonsReturnsEmptyString() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array('object_number' => 'ABC112'));

		$this->fixture->setConfigurationValue('enableNextPreviousButtons', FALSE);

		$this->fixture->piVars = array(
			'showUid' => $realtyObject->getUid(),
			'recordPosition' => 0,
			'listViewType' => 'realty_list',
			'listUid' => $this->listViewUid,
		);

		$this->assertEquals(
			'',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function renderForEnabledNextPreviousButtonsAndOnlyOneRecordReturnsEmptyString() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array('object_number' => 'ABC112'));

		$this->fixture->piVars = array(
			'showUid' => $realtyObject->getUid(),
			'recordPosition' => 0,
			'listViewLimitation' => json_encode(array('objectNumber' => 'ABC112')),
			'listViewType' => 'realty_list',
			'listUid' => $this->listViewUid,
		);

		$this->assertEquals(
			'',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function renderForEnabledNextPreviousButtonsAndMultipleRecordsReturnsNextLink() {
		$objectUid = $this->createRealtyRecordWithCity();
		$this->createRealtyRecordWithCity();

		$this->fixture->piVars = array(
			'showUid' => $objectUid,
			'recordPosition' => 0,
			'listViewType' => 'realty_list',
			'listUid' => $this->listViewUid,
		);

		$this->assertContains(
			'nextPage',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function renderForRecordPositionZeroNotReturnsPreviousButton() {
		$objectUid = $this->createRealtyRecordWithCity();
		$this->createRealtyRecordWithCity();

		$this->fixture->piVars = array(
			'showUid' => $objectUid,
			'recordPosition' => 0,
			'listViewType' => 'realty_list',
			'listUid' => $this->listViewUid,
		);

		$this->assertNotContains(
			'previousPage',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function renderForRecordPostionOneAndTwoRecordsNotReturnsNextButton() {
		$this->testingFramework->createRecord(
			REALTY_TABLE_OBJECTS,
			array(
				'object_number' => 'foo',
				'city' => $this->testingFramework->createRecord(REALTY_TABLE_CITIES)
			)
		);
		$objectUid = $this->testingFramework->createRecord(
			REALTY_TABLE_OBJECTS,
			array(
				'object_number' => 'foo',
				'city' => $this->testingFramework->createRecord(REALTY_TABLE_CITIES)
			)
		);

		$this->fixture->piVars = array(
			'showUid' => $objectUid,
			'recordPosition' => 1,
			'listViewLimitation' => json_encode(array('objectNumber' => 'foo')),
			'listViewType' => 'realty_list',
			'listUid' => $this->listViewUid,
		);

		$this->assertNotContains(
			'nextPage',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function renderAddsUidOfPreviousRecordToPreviousLink() {
		$objectUid1 = $this->testingFramework->createRecord(
			REALTY_TABLE_OBJECTS,
			array(
				'object_number' => 'foo',
				'city' => $this->testingFramework->createRecord(REALTY_TABLE_CITIES)
			)
		);
		$objectUid2 = $this->testingFramework->createRecord(
			REALTY_TABLE_OBJECTS,
			array(
				'object_number' => 'foo',
				'city' => $this->testingFramework->createRecord(REALTY_TABLE_CITIES)
			)
		);

		$this->fixture->piVars = array(
			'showUid' => $objectUid2,
			'recordPosition' => 1,
			'listViewType' => 'realty_list',
			'listViewLimitation' => json_encode(array('objectNumber' => 'foo')),
			'listUid' => $this->listViewUid,
		);

		$this->assertContains(
			'showUid]=' . $objectUid1,
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function renderAddsUidOfNextRecordToNextLink() {
		$objectUid1 = $this->testingFramework->createRecord(
			REALTY_TABLE_OBJECTS,
			array(
				'object_number' => 'foo',
				'city' => $this->testingFramework->createRecord(REALTY_TABLE_CITIES)
			)
		);
		$objectUid2 = $this->testingFramework->createRecord(
			REALTY_TABLE_OBJECTS,
			array(
				'object_number' => 'foo',
				'city' => $this->testingFramework->createRecord(REALTY_TABLE_CITIES)
			)
		);

		$this->fixture->piVars = array(
			'showUid' => $objectUid1,
			'recordPosition' => 0,
			'listViewType' => 'realty_list',
			'listViewLimitation' => json_encode(array('objectNumber' => 'foo')),
			'listUid' => $this->listViewUid,
		);

		$this->assertContains(
			'showUid]=' . $objectUid2,
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function renderForEnabledNextPreviousButtonsAndOnlyOneRecordOnListViewPageReturnsEmptyString() {
		$sysFolder = $this->testingFramework->createSystemFolder();
		$flexforms = '<?xml version="1.0" encoding="utf-8" standalone="yes" ?>' .
			'<T3FlexForms>' .
				'<data>' .
					'<sheet index="sDEF">' .
						'<language index="lDEF">' .
							'<field index="pages">' .
								'<value index="vDEF">' . $sysFolder . '</value>' .
							'</field>' .
						'</language>' .
					'</sheet>' .
				'</data>' .
			'</T3FlexForms>';
		$listViewUid = $this->testingFramework->createContentElement(
			0, array('pi_flexform' => $flexforms)
		);

		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array('pid' => $sysFolder));

		$this->fixture->piVars = array(
			'showUid' => $realtyObject,
			'recordPosition' => 0,
			'listViewType' => 'realty_list',
			'listUid' => $listViewUid,
		);

		$this->assertEquals(
			'',
			$this->fixture->render()
		);
	}


	//////////////////////////////////////////////////////////////////
	// Tests concerning the URL of the "next" and "previous" buttons
	//////////////////////////////////////////////////////////////////
	//
	// The following tests only test the "next" button, since the link creation
	// for the "previous" button works the same.

	/**
	 * @test
	 */
	public function renderAddsListViewUidToNextButton() {
		$objectUid = $this->createRealtyRecordWithCity();
		$this->createRealtyRecordWithCity();

		$this->fixture->piVars = array(
			'showUid' => $objectUid,
			'recordPosition' => 1,
			'listUid' => $this->listViewUid,
			'listViewType' => 'realty_list',
		);

		$this->assertContains(
			'listUid]=' . $this->listViewUid,
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function renderAddsListViewTypeToNextButton() {
		$objectUid = $this->createRealtyRecordWithCity();
		$this->createRealtyRecordWithCity();

		$this->fixture->piVars = array(
			'showUid' => $objectUid,
			'recordPosition' => 1,
			'listViewType' => 'favorites',
			'listUid' => $this->listViewUid,
		);

		$this->assertContains(
			'listViewType]=favorites',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function renderAddsListViewLimitationToNextLink() {
		$objectUid = $this->createRealtyRecordWithCity();
		$this->createRealtyRecordWithCity();

		$listViewLimitation = json_encode(array('objectNumber' => 'foo'));

		$this->fixture->piVars = array(
			'showUid' => $objectUid,
			'recordPosition' => 1,
			'listViewType' => 'favorites',
			'listViewLimitation' => $listViewLimitation,
			'listUid' => $this->listViewUid,
		);

		$this->assertContains(
			'listViewLimitation]=' . urlencode($listViewLimitation),
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function renderForNoListViewTypeReturnsEmptyString() {
		$this->fixture->piVars = array(
			'showUid' => $this->createRealtyRecordWithCity(),
			'recordPosition' => 1,
			'listUid' => $this->listViewUid,
		);

		$this->assertEquals(
			'',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function renderForInvalidListViewTypeReturnsString() {
		$this->fixture->piVars = array(
			'showUid' => $this->createRealtyRecordWithCity(),
			'recordPosition' => 1,
			'listViewType' => 'foo',
			'listUid' => $this->listViewUid,
		);

		$this->assertEquals(
			'',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function renderForNegativeRecordPositionReturnsEmptyString() {
		$this->fixture->piVars = array(
			'showUid' => $this->createRealtyRecordWithCity(),
			'recordPosition' => -1,
			'listViewType' => 'realty_list',
			'listUid' => $this->listViewUid,
		);

		$this->assertEquals(
			'',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function renderForRecordPositionStringAddsRecordPositionOnetoNextLink() {
		$objectUid = $this->createRealtyRecordWithCity();
		$this->createRealtyRecordWithCity();
		$this->fixture->piVars = array(
			'showUid' => $objectUid,
			'recordPosition' => 'foo',
			'listViewType' => 'realty_list',
			'listUid' => $this->listViewUid,
		);

		$this->assertContains(
			'recordPosition]=1',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function renderForRecordPositionStringHidesPreviousButton() {
		$this->fixture->piVars = array(
			'showUid' => $this->createRealtyRecordWithCity(),
			'recordPosition' => 'foo',
			'listViewType' => 'realty_list',
			'listUid' => $this->listViewUid,
		);

		$this->assertNotContains(
			'previousPage',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function renderForListUidNegativeReturnsEmptyString() {
		$this->fixture->piVars = array(
			'showUid' => $this->createRealtyRecordWithCity(),
			'recordPosition' => 0,
			'listUid' => -1,
			'listViewType' => 'realty_list',
		);

		$this->assertEquals(
			'',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function renderForListUidPointingToNonExistingContentElementReturnsEmptyString() {
		$this->fixture->piVars = array(
			'showUid' => $this->createRealtyRecordWithCity(),
			'recordPosition' => 0,
			'listUid' => $this->testingFramework->getAutoIncrement('tt_content'),
			'listViewType' => 'realty_list',
		);

		$this->assertEquals(
			'',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function renderForNoListUidSetInPiVarsReturnsEmptyString() {
		$this->fixture->piVars = array(
			'showUid' => $this->createRealtyRecordWithCity(),
			'recordPosition' => 0,
			'listViewType' => 'realty_list',
		);

		$this->assertEquals(
			'',
			$this->fixture->render()
		);
	}
}
?>