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
 * Unit tests for the tx_realty_pi1_NextPreviousButtonsView class in the "realty"
 * extension.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Bernd Schönbach <bernd@oliverklee.de>
 */
class tx_realty_pi1_NextPreviousButtonsView_testcase extends tx_phpunit_testcase {
	/**
	 * @var tx_realty_pi1_NextPreviousButtonsView
	 */
	private $fixture;

	/**
	 * @var tx_oelib_testingFramework
	 */
	private $testingFramework;

	public function setUp() {
		$this->testingFramework = new tx_oelib_testingFramework('tx_realty');
		$this->testingFramework->createFakeFrontEnd();
		$GLOBALS['TSFE']->cObj->data['pid']
			= $this->testingFramework->createFrontEndPage();

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

		$piVars = array(
			'showUid' => $realtyObject->getUid(),
			'recordPosition' => 0,
			'listViewType' => 'realty_list',
		);

		$this->assertEquals(
			'',
			$this->fixture->render($piVars)
		);
	}

	/**
	 * @test
	 */
	public function renderForEnabledNextPreviousButtonsAndOnlyOneRecordReturnsEmptyString() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array('object_number' => 'ABC112'));

		$piVars = array(
			'showUid' => $realtyObject->getUid(),
			'recordPosition' => 0,
			'listViewLimitation' => base64_encode(
				serialize(array('objectNumber' => 'ABC112'))
			),
			'listViewType' => 'realty_list',
		);

		$this->assertEquals(
			'',
			$this->fixture->render($piVars)
		);
	}

	/**
	 * @test
	 */
	public function renderForEnabledNextPreviousButtonsAndMultipleRecordsReturnsNextLink() {
		$objectUid = $this->testingFramework->createRecord(REALTY_TABLE_OBJECTS);
		$this->testingFramework->createRecord(REALTY_TABLE_OBJECTS);

		$this->assertContains(
			'nextPage',
			$this->fixture->render(
				array(
					'showUid' => $objectUid,
					'recordPosition' => 0,
					'listViewType' => 'realty_list',
				)
			)
		);
	}

	/**
	 * @test
	 */
	public function renderForRecordPositionZeroNotReturnsPreviousButton() {
		$objectUid = $this->testingFramework->createRecord(
			REALTY_TABLE_OBJECTS,
			array('city' => $this->testingFramework->createRecord(REALTY_TABLE_CITIES))
		);
		$this->testingFramework->createRecord(
			REALTY_TABLE_OBJECTS,
			array('city' => $this->testingFramework->createRecord(REALTY_TABLE_CITIES))
		);

		$this->assertNotContains(
			'previousPage',
			$this->fixture->render(
				array(
					'showUid' => $objectUid,
					'recordPosition' => 0,
					'listViewType' => 'realty_list',
				)
			)
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

		$piVars = array(
			'showUid' => $objectUid,
			'recordPosition' => 1,
			'listViewLimitation'
				=> base64_encode(serialize(array('objectNumber' => 'foo'))),
			'listViewType' => 'realty_list',
		);

		$this->assertNotContains(
			'nextPage',
			$this->fixture->render($piVars)
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

		$piVars = array(
			'showUid' => $objectUid2,
			'recordPosition' => 1,
			'listViewType' => 'realty_list',
			'listViewLimitation'
				=> base64_encode(serialize(array('objectNumber' => 'foo'))),
		);

		$this->assertContains(
			'showUid]=' . $objectUid1,
			$this->fixture->render($piVars)
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

		$piVars = array(
			'showUid' => $objectUid1,
			'recordPosition' => 0,
			'listViewType' => 'realty_list',
			'listViewLimitation'
				=> base64_encode(serialize(array('objectNumber' => 'foo'))),
		);

		$this->assertContains(
			'showUid]=' . $objectUid2,
			$this->fixture->render($piVars)
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
		$objectUid = $this->testingFramework->createRecord(REALTY_TABLE_OBJECTS);
		$this->testingFramework->createRecord(REALTY_TABLE_OBJECTS);

		$piVars = array(
			'showUid' => $objectUid,
			'recordPosition' => 1,
			'listUid' => 42,
			'listViewType' => 'realty_list',
		);

		$this->assertContains(
			'listUid]=42',
			$this->fixture->render($piVars)
		);
	}

	/**
	 * @test
	 */
	public function renderAddsListViewTypeToNextButton() {
		$objectUid = $this->testingFramework->createRecord(REALTY_TABLE_OBJECTS);
		$this->testingFramework->createRecord(REALTY_TABLE_OBJECTS);

		$piVars = array(
			'showUid' => $objectUid,
			'recordPosition' => 1,
			'listViewType' => 'favorites',
		);

		$this->assertContains(
			'listViewType]=favorites',
			$this->fixture->render($piVars)
		);
	}

	/**
	 * @test
	 */
	public function renderAddsListViewLimitationToNextLink() {
		$objectUid = $this->testingFramework->createRecord(REALTY_TABLE_OBJECTS);
		$this->testingFramework->createRecord(REALTY_TABLE_OBJECTS);

		$listViewLimitation = base64_encode(serialize(array('objectNumber' => 'foo')));

		$piVars = array(
			'showUid' => $objectUid,
			'recordPosition' => 1,
			'listViewType' => 'favorites',
			'listViewLimitation' => $listViewLimitation,
		);

		$this->assertContains(
			'listViewLimitation]=' . $listViewLimitation,
			$this->fixture->render($piVars)
		);
	}

	/**
	 * @test
	 */
	public function renderForNoListViewTypeReturnsEmptyString() {
		$objectUid = $this->testingFramework->createRecord(REALTY_TABLE_OBJECTS);

		$this->assertEquals(
			'',
			$this->fixture->render(array(
				'showUid' => $objectUid,
				'recordPosition' => 1,
			))
		);
	}

	/**
	 * @test
	 */
	public function renderForInvalidListViewTypeReturnsString() {
		$objectUid = $this->testingFramework->createRecord(REALTY_TABLE_OBJECTS);

		$this->assertEquals(
			'',
			$this->fixture->render(array(
				'showUid' => $objectUid,
				'recordPosition' => 1,
				'listViewType' => 'foo',
			))
		);
	}

	/**
	 * @test
	 */
	public function renderForNegativeRecordPositionReturnsEmptyString() {
		$objectUid = $this->testingFramework->createRecord(REALTY_TABLE_OBJECTS);

		$this->assertEquals(
			'',
			$this->fixture->render(
				array(
					'showUid' => $objectUid,
					'recordPosition' => -1,
					'listViewType' => 'realty_list',
				)
			)
		);
	}

	/**
	 * @test
	 */
	public function renderForRecordPositionStringAddsRecordPositionOnetoNextLink() {
		$objectUid = $this->testingFramework->createRecord(REALTY_TABLE_OBJECTS);

		$this->assertContains(
			'recordPosition]=1',
			$this->fixture->render(
				array(
					'showUid' => $objectUid,
					'recordPosition' => 'foo',
					'listViewType' => 'realty_list',
				)
			)
		);
	}

	/**
	 * @test
	 */
	public function renderForRecordPositionStringHidesPreviousButton() {
		$objectUid = $this->testingFramework->createRecord(REALTY_TABLE_OBJECTS);

		$this->assertNotContains(
			'previousPage',
			$this->fixture->render(
				array(
					'showUid' => $objectUid,
					'recordPosition' => 'foo',
					'listViewType' => 'realty_list',)
			)
		);
	}

	/**
	 * @test
	 */
	public function renderForListUidNegativeReturnsEmptyString() {
		$objectUid = $this->testingFramework->createRecord(REALTY_TABLE_OBJECTS);

		$this->assertEquals(
			'',
			$this->fixture->render(
				array(
					'showUid' => $objectUid,
					'listUid' => -1,
					'listViewType' => 'realty_list',
				)
			)
		);
	}
}
?>