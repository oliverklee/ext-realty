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

require_once(t3lib_extMgm::extPath('realty') . 'lib/tx_realty_constants.php');

/**
 * Unit tests for the tx_realty_pi1_AbstractListView class in the 'realty'
 * extension.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 * @author Bernd Schönbach <bernd@oliverklee.de>
 */
class tx_realty_pi1_AbstractListView_testcase extends tx_phpunit_testcase {
	/**
	 * @var tx_realty_tests_fixtures_testingListView
	 */
	private $fixture;

	/**
	 * @var tx_oelib_testingFramework
	 */
	private $testingFramework;

	/**
	 * @var integer UID of the first dummy realty object
	 */
	private $firstRealtyUid = 0;
	/**
	 * @var string object number for the first dummy realty object
	 */
	private static $firstObjectNumber = '1';
	/**
	 * @var string title for the first dummy realty object
	 */
	private static $firstObjectTitle = 'a title';

	/**
	 * @var integer second dummy realty object
	 */
	private $secondRealtyUid = 0;
	/**
	 * @var string object number for the second dummy realty object
	 */
	private static $secondObjectNumber = '2';
	/**
	 * @var string title for the second dummy realty object
	 */
	private static $secondObjectTitle = 'another title';

	/**
	 * @var integer first dummy city UID
	 */
	private $firstCityUid = 0;
	/**
	 * @var string title for the first dummy city
	 */
	private static $firstCityTitle = 'Bonn';

	/**
	 * @var integer second dummy city UID
	 */
	private $secondCityUid = 0;
	/**
	 * @var string title for the second dummy city
	 */
	private static $secondCityTitle = 'bar city';

	/**
	 * @var integer PID of the single view page
	 */
	private $singlePid = 0;
	/**
	 * @var integer PID of the alternate single view page
	 */
	private $otherSinglePid = 0;
	/**
	 * @var integer PID of the favorites page
	 */
	private $favoritesPid = 0;
	/**
	 * @var integer login PID
	 */
	private $loginPid = 0;
	/**
	 * @var integer system folder PID
	 */
	private $systemFolderPid = 0;
	/**
	 * @var integer sub-system folder PID
	 */
	private $subSystemFolderPid = 0;

	/**
	 * @var integer static_info_tables UID of Germany
	 */
	const DE = 54;

	public function setUp() {
		tx_oelib_headerProxyFactory::getInstance()->enableTestMode();
		$this->testingFramework = new tx_oelib_testingFramework('tx_realty');
		$this->testingFramework->createFakeFrontEnd();
		$this->createDummyPages();
		$this->createDummyObjects();

		$this->fixture = new tx_realty_tests_fixtures_TestingListView(
			array(
				'templateFile' => 'EXT:realty/pi1/tx_realty_pi1.tpl.htm',
				'singlePID' => $this->singlePid,
				'favoritesPID' => $this->favoritesPid,
				'pidList' => $this->systemFolderPid,
				'showGoogleMaps' => 0,
				'defaultCountryUID' => self::DE,
			),
			$GLOBALS['TSFE']->cObj,
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
	 * Creates dummy realty objects in the DB.
	 */
	private function createDummyObjects() {
		$this->createDummyCities();
		$this->firstRealtyUid = $this->testingFramework->createRecord(
			REALTY_TABLE_OBJECTS,
			array(
				'title' => self::$firstObjectTitle,
				'object_number' => self::$firstObjectNumber,
				'pid' => $this->systemFolderPid,
				'city' => $this->firstCityUid,
				'teaser' => '',
				'has_air_conditioning' => '0',
				'has_pool' => '0',
				'has_community_pool' => '0',
				'object_type' => REALTY_FOR_RENTING,
			)
		);
		$this->secondRealtyUid = $this->testingFramework->createRecord(
			REALTY_TABLE_OBJECTS,
			array(
				'title' => self::$secondObjectTitle,
				'object_number' => self::$secondObjectNumber,
				'pid' => $this->systemFolderPid,
				'city' => $this->secondCityUid,
				'object_type' => REALTY_FOR_SALE,
			)
		);
	}

	/**
	 * Creates dummy city records in the DB.
	 */
	private function createDummyCities() {
		$this->firstCityUid = $this->testingFramework->createRecord(
			REALTY_TABLE_CITIES,
			array('title' => self::$firstCityTitle)
		);
		$this->secondCityUid = $this->testingFramework->createRecord(
			REALTY_TABLE_CITIES,
			array('title' => self::$secondCityTitle)
		);
	}

	/**
	 * Creates dummy FE pages (like login and single view).
	 */
	private function createDummyPages() {
		$this->loginPid = $this->testingFramework->createFrontEndPage();
		$this->singlePid = $this->testingFramework->createFrontEndPage();
		$this->otherSinglePid = $this->testingFramework->createFrontEndPage();
		$this->favoritesPid = $this->testingFramework->createFrontEndPage();
		$this->systemFolderPid = $this->testingFramework->createSystemFolder(1);
		$this->subSystemFolderPid = $this->testingFramework->createSystemFolder(
			$this->systemFolderPid
		);
	}

	/**
	 * Denies access to the details page by requiring logon to display that page
	 * and then logging out any logged-in FE users.
	 */
	private function denyAccess() {
		$this->fixture->setConfigurationValue(
			'requireLoginForSingleViewPage', 1
		);
		$this->testingFramework->logoutFrontEndUser();
	}

	/**
	 * Allows access to the details page by not requiring logon to display that
	 * page.
	 */
	private function allowAccess() {
		$this->fixture->setConfigurationValue(
			'requireLoginForSingleViewPage', 0
		);
	}


	////////////////////////////////////
	// Tests concerning the pagination
	////////////////////////////////////

	/**
	 * @test
	 */
	public function internalResultCounterForListOfTwoIsTwo() {
		$this->fixture->render();

		$this->assertEquals(
			2,
			$this->fixture->internal['res_count']
		);
	}

	/**
	 * @test
	 */
	public function paginationIsNotEmptyForMoreObjectsThanFitOnOnePage() {
		$this->fixture->setConfigurationValue(
			'listView.', array('results_at_a_time' => 1)
		);
		$this->fixture->render();

		$this->assertNotEquals(
			'',
			$this->fixture->getSubpart('PAGINATION')
		);
	}

	/**
	 * @test
	 */
	public function paginationIsEmptyIfObjectsJustFitOnOnePage() {
		$this->fixture->setConfigurationValue(
			'listView.', array('results_at_a_time' => 2)
		);
		$this->fixture->render();

		$this->assertEquals(
			'',
			$this->fixture->getSubpart('PAGINATION')
		);
	}

	/**
	 * @test
	 */
	public function paginationIsEmptyIfObjectsNotFillOnePage() {
		$this->fixture->setConfigurationValue(
			'listView.', array('results_at_a_time' => 3)
		);
		$this->fixture->render();

		$this->assertEquals(
			'',
			$this->fixture->getSubpart('PAGINATION')
		);
	}

	/**
	 * @test
	 */
	public function paginationForMoreThanOnePageContainsNumberOfTotalResults() {
		$this->fixture->setConfigurationValue(
			'listView.', array('results_at_a_time' => 1)
		);
		$this->fixture->render();

		$this->assertContains(
			'(2 ',
			$this->fixture->getSubpart('PAGINATION')
		);
	}




	//////////////////////////////////////////
	// Tests for the images in the list view
	//////////////////////////////////////////

	public function testListViewContainsEnabledImage() {
		$this->testingFramework->createRecord(
			REALTY_TABLE_IMAGES,
			array(
				'caption' => 'test image',
				'realty_object_uid' => $this->firstRealtyUid,
			)
		);

		$this->assertContains(
			'test image',
			$this->fixture->render()
		);
	}

	public function testListViewDoesNotContainDeletedImage() {
		$this->testingFramework->createRecord(
			REALTY_TABLE_IMAGES,
			array(
				'caption' => 'test image',
				'realty_object_uid' => $this->firstRealtyUid,
				'deleted' => 1,
			)
		);
		$this->assertNotContains(
			'test image',
			$this->fixture->render()
		);
	}

	public function testListViewDoesNotContainHiddenImage() {
		$this->testingFramework->createRecord(
			REALTY_TABLE_IMAGES,
			array(
				'caption' => 'test image',
				'realty_object_uid' => $this->firstRealtyUid,
				'hidden' => 1,
			)
		);
		$this->assertNotContains(
			'test image',
			$this->fixture->render()
		);
	}

	public function testImagesInTheListViewAreLinkedToTheSingleView() {
		// Titles are set to '' to ensure there are no other links to the
		// single view page in the result.
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->secondRealtyUid,
			array('title' => '')
		);
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->firstRealtyUid,
			array('images' => '1', 'title' => '')
		);
		$this->testingFramework->createRecord(
			REALTY_TABLE_IMAGES,
			array('caption' => 'foo', 'realty_object_uid' => $this->firstRealtyUid)
		);
		$output = $this->fixture->render();

		$this->assertContains(
			'tx_realty_pi1[showUid]='.$this->firstRealtyUid,
			$output
		);
		$this->assertContains(
			'?id=' . $this->singlePid,
			$output
		);
	}

	public function testImagesInTheListViewDoNotContainPopUpJavaScriptCode() {
		// This test asserts that linked images in the list view do no longer
		// lead to the gallery.
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->firstRealtyUid,
			array('images' => '1')
		);
		$this->testingFramework->createRecord(
			REALTY_TABLE_IMAGES,
			array('caption' => 'foo', 'realty_object_uid' => $this->firstRealtyUid)
		);
		// this enables the gallery popup window
		$this->fixture->setConfigurationValue(
			'galleryPopupParameters',
			'width=600,height=400,resizable=no,toolbar=no,'
			.'location=no,directories=no,status=no,menubar=no'
		);

		$this->assertNotContains(
			'onclick="window.open(',
			$this->fixture->render()
		);
	}

	public function testImagesInTheListViewDoNotContainLinkToGallery() {
		// This test asserts that linked images in the list view do no longer
		// lead to the gallery.
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->firstRealtyUid,
			array('images' => '1')
		);
		$this->testingFramework->createRecord(
			REALTY_TABLE_IMAGES,
			array('realty_object_uid' => $this->firstRealtyUid, 'caption' => 'foo')
		);
		$galleryPid = $this->testingFramework->createFrontEndPage();
		$this->fixture->setConfigurationValue('galleryPID', $galleryPid);

		$this->assertNotContains(
			'?id=' . $galleryPid,
			$this->fixture->render()
		);
	}


	////////////////////////////////////
	// Tests for data in the list view
	////////////////////////////////////

	public function testListViewDisplaysNoMarkersForEmptyRenderedObject() {
		$systemFolder = $this->testingFramework->createSystemFolder();
		$this->testingFramework->createRecord(
			REALTY_TABLE_OBJECTS,
			array(
				'city' => $this->firstCityUid,
				'pid' => $systemFolder
			)
		);

		$this->fixture->setConfigurationValue('pidList', $systemFolder);

		$this->assertNotContains(
			'###',
			$this->fixture->render()
		);
	}

	public function testListViewHtmlSpecialCharsObjectTitles() {
		$systemFolder = $this->testingFramework->createSystemFolder();
		$this->testingFramework->createRecord(
			REALTY_TABLE_OBJECTS,
			array(
				'city' => $this->firstCityUid,
				'pid' => $systemFolder,
				'title' => 'a & " >',
			)
		);

		$this->fixture->setConfigurationValue('pidList', $systemFolder);

		$this->assertContains(
			'a &amp; &quot; &gt;',
			$this->fixture->render()
		);
	}

	public function testListViewFillsMarkerForObjectNumber() {
		$this->fixture->setConfigurationValue('orderBy', 'object_number');
		$this->fixture->setConfigurationValue('listView.', array('descFlag' => 0));
		$this->fixture->render();

		$this->assertEquals(
			self::$secondObjectNumber,
			$this->fixture->getMarker('object_number')
		);
	}

	public function testCreateListViewReturnsPricesWithTheCurrencyProvidedByTheObjectIfNoCurrencyIsSetInTsSetup() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->firstRealtyUid,
			array('buying_price' => '9', 'object_type' => REALTY_FOR_SALE, 'currency' => '&euro;',)
		);

		$this->assertContains(
			'&euro;',
			$this->fixture->render()
		);
	}

	public function testCreateListViewReturnsPricesWithTheCurrencyProvidedByTheObjectAlthoughCurrencyIsSetInTsSetup() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->firstRealtyUid,
			array('buying_price' => '9', 'object_type' => REALTY_FOR_SALE, 'currency' => '&euro;',)
		);
		$this->fixture->setConfigurationValue('currencyUnit', 'foo');

		$this->assertContains(
			'&euro;',
			$this->fixture->render()
		);
	}

	public function testCreateListViewReturnsPricesWithTheCurrencyFromTsSetupIfTheObjectDoesNotProvideACurrency() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->firstRealtyUid,
			array('buying_price' => '9', 'object_type' => REALTY_FOR_SALE)
		);
		$this->fixture->setConfigurationValue('currencyUnit', '&euro;');

		$this->assertContains(
			'&euro;',
			$this->fixture->render()
		);
	}

	public function testListViewFormatsPriceUsingSpaceAsThousandsSeparator() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->firstRealtyUid,
			array('buying_price' => '1234567', 'object_type' => REALTY_FOR_SALE)
		);

		$this->assertContains(
			'1 234 567',
			$this->fixture->render()
		);
	}

	public function testCreateListViewReturnsListOfRecords() {
		$output = $this->fixture->render();

		$this->assertContains(
			self::$firstObjectTitle,
			$output
		);
		$this->assertContains(
			self::$secondObjectTitle,
			$output
		);
	}

	public function testCreateListViewReturnsMainSysFolderRecordsAndSubFolderRecordsIfRecursionIsEnabled() {
		$this->fixture->setConfigurationValue('recursive', '1');

		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->firstRealtyUid,
			array('pid' => $this->subSystemFolderPid)
		);

		$output = $this->fixture->render();

		$this->assertContains(
			self::$firstObjectTitle,
			$output
		);
		$this->assertContains(
			self::$secondObjectTitle,
			$output
		);
	}

	public function testCreateListViewNotReturnsSubFolderRecordsIfRecursionIsDisabled() {
		$this->fixture->setConfigurationValue('recursive', '0');

		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->firstRealtyUid,
			array('pid' => $this->subSystemFolderPid)
		);

		$output = $this->fixture->render();

		$this->assertNotContains(
			self::$firstObjectTitle,
			$output
		);
		$this->assertContains(
			self::$secondObjectTitle,
			$output
		);
	}

	public function testListViewForNonEmptyTeaserShowsTeaserText() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->firstRealtyUid,
			array('teaser' => 'teaser text')
		);

		$this->assertContains(
			'teaser text',
			$this->fixture->render()
		);
	}

	public function testListViewForEmptyTeaserHidesTeaserSubpart() {
		$this->assertNotContains(
			'###TEASER###',
			$this->fixture->render()
		);
	}

	public function testListViewDisplaysTheSecondObjectsTeaserIfTheFirstOneDoesNotHaveATeaser() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS, $this->secondRealtyUid, array('teaser' => 'test teaser')
		);

		$this->assertContains(
			'test teaser',
			$this->fixture->render()
		);
	}

	public function testListViewDisplaysFeatureParagraphForListItemWithFeatures() {
		// Among other things, the object number is rendered within this paragraph.
		$this->assertContains(
			'<p class="details">',
			$this->fixture->render()
		);
	}

	public function testListViewDoesNotDisplayFeatureParagraphForListItemWithoutFeatures() {
		$systemFolder = $this->testingFramework->createSystemFolder();
		$this->testingFramework->createRecord(
			REALTY_TABLE_OBJECTS,
			array(
				'city' => $this->firstCityUid,
				'pid' => $systemFolder
			)
		);

		$this->fixture->setConfigurationValue('pidList', $systemFolder);

		$this->assertNotContains(
			'<p class="details">',
			$this->fixture->render()
		);
	}

	public function testListViewWithOneRecordDueToTheAppliedUidFilterRedirectsToSingleView() {
		$this->fixture->render(array('uid' => $this->firstRealtyUid));

		$this->assertContains(
			'Location:',
			tx_oelib_headerProxyFactory::getInstance()->getHeaderProxy()->getLastAddedHeader()
		);
	}

	public function testListViewWithOneRecordDueToTheAppliedObjectNumberFilterRedirectsToSingleViewForNumericObjectNumber() {
		$this->fixture->render(array('objectNumber' => self::$firstObjectNumber));

		$this->assertContains(
			'Location:',
			tx_oelib_headerProxyFactory::getInstance()->getHeaderProxy()->getLastAddedHeader()
		);
	}

	public function testListViewWithOneRecordDueToTheAppliedObjectNumberFilterRedirectsToSingleViewForNonNumericObjectNumber() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->firstRealtyUid,
			array('object_number' => 'object number')
		);
		$this->fixture->render(array('objectNumber' => 'object number'));

		$this->assertContains(
			'Location:',
			tx_oelib_headerProxyFactory::getInstance()->getHeaderProxy()->getLastAddedHeader()
		);
	}

	public function testListViewWithOneRecordDueToTheAppliedObjectNumberFilterRedirectsToSingleViewWithTheCorrectPid() {
		$this->fixture->render(array('objectNumber' => self::$firstObjectNumber));

		$this->assertContains(
			'?id=' . $this->singlePid,
			tx_oelib_headerProxyFactory::getInstance()->getHeaderProxy()->getLastAddedHeader()
		);
	}


	public function testListViewWithOneRecordDueToTheAppliedObjectNumberFilterRedirectsToSingleViewWithTheCorrectShowUid() {
		$this->fixture->render(array('objectNumber' => self::$firstObjectNumber));

		$this->assertContains(
			'tx_realty_pi1[showUid]=' . $this->firstRealtyUid,
			tx_oelib_headerProxyFactory::getInstance()->getHeaderProxy()->getLastAddedHeader()
		);
	}

	public function testListViewWithOneRecordDueToTheAppliedObjectNumberFilterRedirectsToSingleViewAnProvidesAChash() {
		$this->fixture->render(array('objectNumber' => self::$firstObjectNumber));

		$this->assertContains(
			'cHash=',
			tx_oelib_headerProxyFactory::getInstance()->getHeaderProxy()->getLastAddedHeader()
		);
	}

	public function testListViewWithOneRecordNotCausedByTheIdFilterNotRedirectsToSingleView() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_CITIES, $this->firstCityUid, array('title' => 'foo-bar')
		);
		$this->fixture->render(array('site' => 'foo'));

		$this->assertEquals(
			'',
			tx_oelib_headerProxyFactory::getInstance()->getHeaderProxy()->getLastAddedHeader()
		);
	}

	public function testListViewWithTwoRecordsNotRedirectsToSingleView() {
		$this->fixture->render();

		$this->assertEquals(
			'',
			tx_oelib_headerProxyFactory::getInstance()->getHeaderProxy()->getLastAddedHeader()
		);
	}

	public function testListViewCropsObjectTitleLongerThan75Characters() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->firstRealtyUid,
			array(
				'title' => 'This title is longer than 75 Characters, so the' .
					' rest should be cropped and be replaced with dots'
			)
		);

		$this->assertContains(
			'This title is longer than 75 Characters, so the rest should be' .
				' cropped and…',
			$this->fixture->render()
		);
	}

	public function testCreateListViewShowsValueForOldOrNewBuilding() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->firstRealtyUid,
			array('old_or_new_building' => '1')
		);

		$this->assertContains(
			$this->fixture->translate('label_old_or_new_building_1'),
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function listViewForDisabledEnableNextPreviousButtonsDoesNotAddListUidToSingleViewLink() {
		$this->fixture->setConfigurationValue('enableNextPreviousButtons', 0);
		$output = $this->fixture->render();

		$this->assertNotContains(
		   'listUid',
			$output
		);
	}

	/**
	 * @test
	 */
	public function listViewForEnabledEnableNextPreviousButtonsAddsListUidToSingleViewLink() {
		$this->fixture->setConfigurationValue('enableNextPreviousButtons', 1);
		$output = $this->fixture->render();

		$this->assertContains(
		   'listUid',
			$output
		);
	}

	/**
	 * @test
	 */
	public function listViewForDisabledEnableNextPreviousButtonsDoesNotAddListTypeToSingleViewLink() {
		$this->fixture->setConfigurationValue('enableNextPreviousButtons', 0);
		$output = $this->fixture->render();

		$this->assertNotContains(
		   'listViewType',
			$output
		);
	}

	/**
	 * @test
	 */
	public function listViewForEnabledEnableNextPreviousButtonsAddsListTypeToSingleViewLink() {
		$this->fixture->setConfigurationValue('enableNextPreviousButtons', 1);
		$output = $this->fixture->render();

		$this->assertContains(
		   'listViewType',
			$output
		);
	}

	/**
	 * @test
	 */
	public function listViewForEnabledEnableNextPreviousButtonsAndListTypeRealtyListAddsCorrectListViewTypeToLink() {
		// TODO: Move this test into a testing class for the abstract list view,
		// when Bug# 3075 is finished
		$this->fixture->setConfigurationValue('enableNextPreviousButtons', 1);
		$output = $this->fixture->render();

		$this->assertContains(
		   'listViewType]=realty_list',
			$output
		);
	}

	/**
	 * @test
	 */
	public function listViewForDisabledEnableNextPreviousButtonsDoesNotAddRecordPositionToSingleViewLink() {
		$this->fixture->setConfigurationValue('enableNextPreviousButtons', 0);

		$this->assertNotContains(
		   'recordPosition',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function listViewForEnabledEnableNextPreviousButtonsAddsRecordPositionToSingleViewLink() {
		$this->fixture->setConfigurationValue('enableNextPreviousButtons', 1);

		$this->assertContains(
		   'recordPosition',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function listViewRecordPositionSingleViewLinkParameterTakesSortingIntoAccount() {
		$this->fixture->setConfigurationValue('enableNextPreviousButtons', 1);

		$this->assertRegExp(
			'/' . $this->secondRealtyUid . '[^>]+recordPosition]=0/',
			$this->fixture->render(array('orderBy' => 'title', 'descFlag' => 1))
		);
	}

	/**
	 * @test
	 */
	public function listViewForTwoRecordsAddsRecordPositionZeroToSingleViewLinkOfFirstRecord() {
		$this->fixture->setConfigurationValue('enableNextPreviousButtons', 1);

		$this->assertRegExp(
			'/' . $this->firstRealtyUid . '[^>]+recordPosition]=0/',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function listViewForTwoRecordsOnOnePageAddsRecordPositionOneToSingleViewLinkOfSecondRecord() {
		$this->fixture->setConfigurationValue('enableNextPreviousButtons', 1);

		$this->assertRegExp(
			'/' . $this->secondRealtyUid . '[^>]+recordPosition]=1/',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function listViewForTwoRecordsOnTwoPagesAddsRecordPositionOneToSingleViewLinkOfRecordOnSecondPage() {
		$this->fixture->setConfigurationValue('enableNextPreviousButtons', 1);
		$this->fixture->setConfigurationValue(
			'listView.', array('results_at_a_time' => 1)
		);

		$this->assertContains(
		   'recordPosition]=1',
			$this->fixture->render(array('pointer' => 1))
		);
	}

	/**
	 * @test
	 */
	public function listViewForEnabledNextPreviousButtonsAddsListViewLimitationToSingleViewLink() {
		$this->fixture->setConfigurationValue('enableNextPreviousButtons', 1);

		$this->assertContains(
		   'listViewLimitation',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function listViewForDisabledNextPreviousButtonsNotAddsListViewLimitationToSingleViewLink() {
		$this->fixture->setConfigurationValue('enableNextPreviousButtons', 1);

		$this->assertContains(
		   'listViewLimitation',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function listViewForEnabledNextPreviousButtonsBase64EncodesListViewLimitationValue() {
		$this->fixture->setConfigurationValue('enableNextPreviousButtons', 1);
		$listViewLimitation = array();
		preg_match(
			'/listViewLimitation]=([^&]*)/',
			$this->fixture->render(),
			$listViewLimitation
		);

		$this->assertNotEquals(
		   '',
			base64_decode($listViewLimitation[1])
		);
	}

	/**
	 * @test
	 */
	public function listViewForEnabledNextPreviousButtonsSerializesListViewLimitationValue() {
		$this->fixture->setConfigurationValue('enableNextPreviousButtons', 1);
		$listViewLimitation = array();
		preg_match(
			'/listViewLimitation]=([^&]*)/',
			$this->fixture->render(array('orderBy' => 'foo')),
			$listViewLimitation
		);

		$this->assertNotEquals(
			'',
			unserialize(base64_decode($listViewLimitation[1]))
		);
	}

	/**
	 * @test
	 */
	public function listViewForEnabledNextPreviousButtonsForSetOrderByContainsOrderByValue() {
		$this->fixture->setConfigurationValue('enableNextPreviousButtons', 1);
		$listViewLimitation = array();
		preg_match(
			'/listViewLimitation]=([^&]*)/',
			$this->fixture->render(array('orderBy' => 'foo')),
			$listViewLimitation
		);
		$this->assertContains(
			'foo',
			unserialize(base64_decode($listViewLimitation[1]))
		);
	}

	/**
	 * @test
	 */
	public function listViewForEnabledNextPreviousButtonsForSetDescFlagContainsDescFlagValue() {
		$this->fixture->setConfigurationValue('enableNextPreviousButtons', 1);
		$listViewLimitation = array();
		preg_match(
			'/listViewLimitation]=([^&]*)/',
			$this->fixture->render(array('orderBy' => 'foo', 'descFlag' => 1)),
			$listViewLimitation
		);

		$this->assertContains(
			1,
			unserialize(base64_decode($listViewLimitation[1]))
		);
	}

	/**
	 * @test
	 */
	public function listViewForEnabledNextPreviousButtonsForSetSearchContainsSearchValue() {
		$this->fixture->setConfigurationValue('enableNextPreviousButtons', 1);
		$listViewLimitation = array();
		preg_match(
			'/listViewLimitation]=([^&]*)/',
			$this->fixture->render(array('search' => array('0' => '42'))),
			$listViewLimitation
		);
		$this->assertContains(
			array('0' => '42'),
			unserialize(base64_decode($listViewLimitation[1]))
		);
	}

	/**
	 * @test
	 */
	public function listViewFilteredBySiteForEnabledNextPreviousButtonsContainsFilteredSite() {
		$this->fixture->setConfigurationValue('enableNextPreviousButtons', 1);
		$listViewLimitation = array();
		preg_match(
			'/listViewLimitation]=([^&]*)/',
			$this->fixture->render(array('site' => self::$firstCityTitle)),
			$listViewLimitation
		);

		$this->assertContains(
			self::$firstCityTitle,
			unserialize(base64_decode($listViewLimitation[1]))
		);
	}


	////////////////////////////////////////////////////
	// Tests concerning additional header in list view
	////////////////////////////////////////////////////

	public function testCreateListViewForNoPostDataSentDoesNotAddCacheControlHeader() {
		$this->fixture->render();

		$this->assertNotEquals(
			tx_oelib_headerProxyFactory::getInstance()
				->getHeaderProxy()->getLastAddedHeader(),
			'Cache-Control: max-age=86400, must-revalidate'
		);
	}

	public function testCreateListViewForPostDataSentAddsCacheControlHeader() {
		$_POST['tx_realty_pi1'] = 'foo';
		$this->fixture->render();
		unset($_POST['tx_realty_pi1']);

		$this->assertEquals(
			tx_oelib_headerProxyFactory::getInstance()
				->getHeaderProxy()->getLastAddedHeader(),
			'Cache-Control: max-age=86400, must-revalidate'
		);
	}


	/////////////////////////////////
	// Testing filtered list views.
	/////////////////////////////////

	public function testListViewFilteredByPriceDisplaysRealtyObjectWithBuyingPriceGreaterThanTheLowerLimit() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->firstRealtyUid,
			array('buying_price' => 11)
		);

		$this->assertContains(
			self::$firstObjectTitle,
			$this->fixture->render(array('priceRange' => '10-'))
		);
	}

	public function testListViewFilteredByPriceDisplaysRealtyObjectWithBuyingPriceLowerThanTheGreaterLimit() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->firstRealtyUid,
			array('buying_price' => 1)
		);

		$this->assertContains(
			self::$firstObjectTitle,
			$this->fixture->render(array('priceRange' => '-10'))
		);
	}

	public function testListViewFilteredByPriceDisplaysRealtyObjectWithZeroBuyingPriceAndZeroRentForNoLowerLimitSet() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->firstRealtyUid,
			array('buying_price' => 0, 'rent_excluding_bills' => 0)
		);

		$this->assertContains(
			self::$firstObjectTitle,
			$this->fixture->render(array('priceRange' => '-10'))
		);
	}

	public function testListViewFilteredByPriceNotDisplaysRealtyObjectWithZeroBuyingPriceAndRentOutOfRangeForNoLowerLimitSet() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->firstRealtyUid,
			array('buying_price' => 0, 'rent_excluding_bills' => 11)
		);

		$this->assertNotContains(
			self::$firstObjectTitle,
			$this->fixture->render(array('priceRange' => '-10'))
		);
	}

	public function testListViewFilteredByPriceDoesNotDisplayRealtyObjectBelowRangeLimit() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->secondRealtyUid,
			array('buying_price' => 9)
		);

		$this->assertNotContains(
			self::$secondObjectTitle,
			$this->fixture->render(array('priceRange' => '10-100'))
		);
	}

	public function testListViewFilteredByPriceDoesNotDisplayRealtyObjectSuperiorToRangeLimit() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->secondRealtyUid,
			array('buying_price' => 101)
		);

		$this->assertNotContains(
			self::$secondObjectTitle,
			$this->fixture->render(array('priceRange' => '10-100'))
		);
	}

	public function testListViewFilteredByPriceDisplaysRealtyObjectWithPriceOfLowerRangeLimit() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->firstRealtyUid,
			array('buying_price' => 10)
		);

		$this->assertContains(
			self::$firstObjectTitle,
			$this->fixture->render(array('priceRange' => '10-20'))
		);
	}

	public function testListViewFilteredByPriceDisplaysRealtyObjectWithPriceOfUpperRangeLimit() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->firstRealtyUid,
			array('buying_price' => 20)
		);

		$this->assertContains(
			self::$firstObjectTitle,
			$this->fixture->render(array('priceRange' => '10-20'))
		);
	}

	public function testListViewFilteredByPriceCanDisplayTwoRealtyObjectsWithABuyingPriceInRange() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->firstRealtyUid,
			array('buying_price' => 9)
		);
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->secondRealtyUid,
			array('buying_price' => 1)
		);
		$output = $this->fixture->render(array('priceRange' => '-10'));

		$this->assertContains(
			self::$firstObjectTitle,
			$output
		);
		$this->assertContains(
			self::$secondObjectTitle,
			$output
		);
	}

	public function testListViewFilteredByPriceCanDisplayTwoRealtyObjectsWithARentInRange() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->firstRealtyUid,
			array('rent_excluding_bills' => 9)
		);
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->secondRealtyUid,
			array('rent_excluding_bills' => 1)
		);
		$output = $this->fixture->render(array('priceRange' => '-10'));

		$this->assertContains(
			self::$firstObjectTitle,
			$output
		);
		$this->assertContains(
			self::$secondObjectTitle,
			$output
		);
	}

	public function testListViewFilteredBySiteDisplaysObjectWithMatchingZip() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS, $this->firstRealtyUid, array('zip' => '12345')
		);
		$this->fixture->setConfigurationValue('showSiteSearchInFilterForm', 'show');

		$this->assertContains(
			self::$firstObjectTitle,
			$this->fixture->render(array('site' => '12345'))
		);
	}

	public function testListViewFilteredBySiteDisplaysObjectWithMatchingCity() {
		$this->fixture->setConfigurationValue('showSiteSearchInFilterForm', 'show');

		$this->assertContains(
			self::$firstObjectTitle,
			$this->fixture->render(array('site' => self::$firstCityTitle))
		);
	}

	public function testListViewFilteredBySiteDisplaysObjectWithPartlyMatchingZip() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS, $this->firstRealtyUid, array('zip' => '12345')
		);
		$this->fixture->setConfigurationValue('showSiteSearchInFilterForm', 'show');

		$this->assertContains(
			self::$firstObjectTitle,
			$this->fixture->render(array('site' => '12000'))
		);
	}

	public function testListViewFilteredBySiteDisplaysObjectWithPartlyMatchingCity() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_CITIES, $this->firstCityUid, array('title' => 'foo-bar')
		);
		$this->fixture->setConfigurationValue('showSiteSearchInFilterForm', 'show');

		$this->assertContains(
			self::$firstObjectTitle,
			$this->fixture->render(array('site' => 'foo'))
		);
	}

	public function testListViewFilteredBySiteNotDisplaysObjectWithNonMatchingZip() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS, $this->firstRealtyUid, array('zip' => '12345')
		);
		$this->fixture->setConfigurationValue('showSiteSearchInFilterForm', 'show');

		$this->assertNotContains(
			self::$firstObjectTitle,
			$this->fixture->render(array('site' => '34'))
		);
	}

	public function testListViewFilteredBySiteNotDisplaysObjectWithNonMatchingCity() {
		$this->fixture->setConfigurationValue('showSiteSearchInFilterForm', 'show');

		$this->assertNotContains(
			self::$firstObjectTitle,
			$this->fixture->render(array('site' => self::$firstCityTitle . '-foo'))
		);
	}

	public function testListViewFilteredBySiteDisplaysAllObjectsForAnEmptyString() {
		$this->fixture->setConfigurationValue('showSiteSearchInFilterForm', 'show');

		$output = $this->fixture->render(array('site' => ''));

		$this->assertContains(
			self::$firstObjectTitle,
			$output
		);
		$this->assertContains(
			self::$secondObjectTitle,
			$output
		);
	}

	public function testListViewFilteredBySiteAndPriceDisplaysObjectInPriceRangeWithMatchingCity() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->firstRealtyUid,
			array('buying_price' => 50)
		);
		$this->fixture->setConfigurationValue('showSiteSearchInFilterForm', 'show');

		$this->assertContains(
			self::$firstObjectTitle,
			$this->fixture->render(array(
				'priceRange' => '10-100', 'site' => self::$firstCityTitle
			))
		);
	}

	public function testListViewFilteredBySiteAndPriceDisplaysObjectInPriceRangeWithMatchingZip() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->firstRealtyUid,
			array('buying_price' => 50, 'zip' => '12345')
		);
		$this->fixture->setConfigurationValue('showSiteSearchInFilterForm', 'show');

		$this->assertContains(
			self::$firstObjectTitle,
			$this->fixture->render(
				array('priceRange' => '10-100', 'site' => '12345')
			)
		);
	}

	public function testListViewFilteredBySiteAndPriceNotDisplaysObjectInPriceRangeWithNonMatchingCity() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->firstRealtyUid,
			array('buying_price' => 50)
		);
		$this->fixture->setConfigurationValue('showSiteSearchInFilterForm', 'show');

		$this->assertNotContains(
			self::$firstObjectTitle,
			$this->fixture->render(array(
				'priceRange' => '10-100',
				'site' => self::$firstCityTitle . '-foo'
			))
		);
	}

	public function testListViewFilteredBySiteAndPriceNotDisplaysObjectInPriceRangeWithNonMatchingZip() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->firstRealtyUid,
			array('buying_price' => 50, 'zip' => '12345')
		);
		$this->fixture->setConfigurationValue('showSiteSearchInFilterForm', 'show');

		$this->assertNotContains(
			self::$firstObjectTitle,
			$this->fixture->render(
				array('priceRange' => '10-100', 'site' => '34')
			)
		);
	}

	public function testListViewFilteredBySiteAndPriceNotDisplaysObjectOutOfPriceRangeWithMatchingCity() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->firstRealtyUid,
			array('buying_price' => 150)
		);
		$this->fixture->setConfigurationValue('showSiteSearchInFilterForm', 'show');

		$this->assertNotContains(
			self::$firstObjectTitle,
			$this->fixture->render(
				array('priceRange' => '10-100', 'site' => self::$firstCityTitle)
			)
		);
	}

	public function testListViewFilteredBySiteAndPriceNotDisplaysObjectOutOfPriceRangeWithMatchingZip() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->firstRealtyUid,
			array('buying_price' => 150, 'zip' => '12345')
		);
		$this->fixture->setConfigurationValue('showSiteSearchInFilterForm', 'show');

		$this->assertNotContains(
			self::$firstObjectTitle,
			$this->fixture->render(
				array('priceRange' => '10-100', 'site' => '12345')
			)
		);
	}

	public function testListViewContainsMatchingRecordWhenFilteredByObjectNumber() {
		$this->assertContains(
			self::$firstObjectNumber,
			$this->fixture->render(
				array('objectNumber' => self::$firstObjectNumber)
			)
		);
	}

	public function testListViewNotContainsMismatchingRecordWhenFilteredByObjectNumber() {
		$this->assertNotContains(
			self::$secondObjectTitle,
			$this->fixture->render(
				array('objectNumber' => self::$firstObjectNumber)
			)
		);
	}

	public function testListViewContainsMatchingRecordWhenFilteredByUid() {
		$this->assertContains(
			self::$firstObjectTitle,
			$this->fixture->render(array('uid' => $this->firstRealtyUid))
		);
	}

	public function testListViewNotContainsMismatchingRecordWhenFilteredByUid() {
		$this->assertNotContains(
			self::$secondObjectTitle,
			$this->fixture->render(array('uid' => $this->firstRealtyUid))
		);
	}

	public function test_ListView_FilteredByRentStatus_DisplaysObjectsForRenting() {
		$this->assertContains(
			self::$firstObjectTitle,
			$this->fixture->render(array('objectType' => 'forRent'))
		);
	}

	public function test_ListView_FilteredByRentStatus_DoesNotDisplaysObjectsForSale() {
		$this->assertNotContains(
			self::$secondObjectTitle,
			$this->fixture->render(array('objectType' => 'forRent'))
		);
	}

	public function test_ListView_FilteredBySaleStatus_DisplaysObjectsForSale() {
		$this->assertContains(
			self::$secondObjectTitle,
			$this->fixture->render(array('objectType' => 'forSale'))
		);
	}

	public function test_ListView_FilteredBySaleStatus_DoesNotDisplaysObjectsForRenting() {
		$this->assertNotContains(
			self::$firstObjectTitle,
			$this->fixture->render(array('objectType' => 'forSale'))
		);
	}

	public function test_ListViewFilteredByLivingArea_AndSetLowerLimit_DisplaysRealtyObjectWithLivingAreaGreaterThanTheLowerLimit() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->firstRealtyUid,
			array('living_area' => 11)
		);

		$this->assertContains(
			self::$firstObjectTitle,
			$this->fixture->render(array('livingAreaFrom' => '10'))
		);
	}

	public function test_ListViewFilteredByLivingArea_AndSetUpperLimit_DisplaysRealtyObjectWithLivingAreaLowerThanTheGreaterLimit() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->firstRealtyUid,
			array('living_area' => 1)
		);

		$this->assertContains(
			self::$firstObjectTitle,
			$this->fixture->render(array('livingAreaTo' => '10'))
		);
	}

	public function test_ListViewFilteredByLivingArea_ForSetUpperLimitAndNotSetLowerLimit_DisplaysRealtyObjectWithLivingAreaZero() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->firstRealtyUid,
			array('living_area' => 0)
		);

		$this->assertContains(
			self::$firstObjectTitle,
			$this->fixture->render(array('livingAreaTo' => '10'))
		);
	}

	public function test_ListViewFilteredByLivingArea_ForUpperAndLowerLimitSet_DoesNotDisplayRealtyObjectBelowLivingAreaLimit() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->secondRealtyUid,
			array('living_area' => 9)
		);

		$this->assertNotContains(
			self::$secondObjectTitle,
			$this->fixture->render(
				array('livingAreaFrom' => '10', 'livingAreaTo' => '100')
			)
		);
	}

	public function test_ListViewFilteredByLivingArea_ForUpperAndLowerLimitSet_DoesNotDisplayRealtyObjectWithLivingAreaGreaterThanLimit() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->secondRealtyUid,
			array('living_area' => 101)
		);

		$this->assertNotContains(
			self::$secondObjectTitle,
			$this->fixture->render(
				array('livingAreaFrom' => '10', 'livingAreaTo' => '100')
			)
		);
	}

	public function test_ListViewFilteredByLivingArea_ForUpperAndLowerLimitSet_DisplaysRealtyObjectWithLivingAreaEqualToLowerLimit() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->firstRealtyUid,
			array('living_area' => 10)
		);

		$this->assertContains(
			self::$firstObjectTitle,
			$this->fixture->render(
				array('livingAreaFrom' => '10', 'livingAreaTo' => '20')
			)
		);
	}

	public function test_ListViewFilteredByLivingArea_ForUpperAndLowerLimitSet_DisplaysRealtyObjectWithLivingAreaEqualToUpperLimit() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->firstRealtyUid,
			array('living_area' => 20)
		);

		$this->assertContains(
			self::$firstObjectTitle,
			$this->fixture->render(
				array('livingAreaFrom' => '10', 'livingAreaTo' => '20')
			)
		);
	}

	public function test_ListViewFilteredByLivingArea_ForUpperLimitSet_CanDisplayTwoRealtyObjectsWithTheLivingAreaInRange() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->firstRealtyUid,
			array('living_area' => 9)
		);
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->secondRealtyUid,
			array('living_area' => 1)
		);
		$output = $this->fixture->render(array('livingAreaTo' => '10'));

		$this->assertContains(
			self::$firstObjectTitle,
			$output
		);
		$this->assertContains(
			self::$secondObjectTitle,
			$output
		);
	}


	///////////////////////////////////////////////////////////////
	// Tests concerning the list view filtered by number of rooms
	///////////////////////////////////////////////////////////////

	public function test_ListViewFilteredByNumberOfRoomsAndSetLowerLimit_DisplaysRealtyObjectWithNumberOfRoomsGreaterThanTheLowerLimit() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->firstRealtyUid,
			array('number_of_rooms' => 11)
		);

		$this->assertContains(
			self::$firstObjectTitle,
			$this->fixture->render(array('numberOfRoomsFrom' => '10'))
		);
	}

	public function test_ListViewFilteredByNumberOfRoomsAndSetUpperLimit_DisplaysRealtyObjectWithNumberOfRoomsLowerThanTheGreaterLimit() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->firstRealtyUid,
			array('number_of_rooms' => 1)
		);

		$this->assertContains(
			self::$firstObjectTitle,
			$this->fixture->render(array('numberOfRoomsTo' => '2'))
		);
	}

	public function test_ListViewFilteredByNumberOfRoomsForSetUpperLimitAndNotSetLowerLimit_DisplaysRealtyObjectWithNumberOfRoomsZero() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->firstRealtyUid,
			array('number_of_rooms' => 0)
		);

		$this->assertContains(
			self::$firstObjectTitle,
			$this->fixture->render(array('numberOfRoomsTo' => '10'))
		);
	}

	public function test_ListViewFilteredByNumberOfRoomsForUpperAndLowerLimitSet_DoesNotDisplayRealtyObjectBelowNumberOfRoomsLimit() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->secondRealtyUid,
			array('number_of_rooms' => 9)
		);

		$this->assertNotContains(
			self::$secondObjectTitle,
			$this->fixture->render(
				array('numberOfRoomsFrom' => '10', 'numberOfRoomsTo' => '100')
			)
		);
	}

	public function test_ListViewFilteredByNumberOfRoomsForUpperAndLowerLimitSet_DoesNotDisplayRealtyObjectWithNumberOfRoomsGreaterThanLimit() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->secondRealtyUid,
			array('number_of_rooms' => 101)
		);

		$this->assertNotContains(
			self::$secondObjectTitle,
			$this->fixture->render(
				array('numberOfRoomsFrom' => '10', 'numberOfRoomsTo' => '100')
			)
		);
	}

	public function test_ListViewFilteredByNumberOfRoomsForUpperAndLowerLimitSet_DisplaysRealtyObjectWithNumberOfRoomsEqualToLowerLimit() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->firstRealtyUid,
			array('number_of_rooms' => 10)
		);

		$this->assertContains(
			self::$firstObjectTitle,
			$this->fixture->render(
				array('numberOfRoomsFrom' => '10', 'numberOfRoomsTo' => '20')
			)
		);
	}

	public function test_ListViewFilteredByNumberOfRoomsForUpperAndLowerLimitSet_DisplaysRealtyObjectWithNumberOfRoomsEqualToUpperLimit() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->firstRealtyUid,
			array('number_of_rooms' => 20)
		);

		$this->assertContains(
			self::$firstObjectTitle,
			$this->fixture->render(
				array('numberOfRoomsFrom' => '10', 'numberOfRoomsTo' => '20')
			)
		);
	}

	public function test_ListViewFilteredByNumberOfRoomsForUpperLimitSet_CanDisplayTwoRealtyObjectsWithTheNumberOfRoomsInRange() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->firstRealtyUid,
			array('number_of_rooms' => 9)
		);
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->secondRealtyUid,
			array('number_of_rooms' => 1)
		);
		$output = $this->fixture->render(array('numberOfRoomsTo' => '10'));

		$this->assertContains(
			self::$firstObjectTitle,
			$output
		);
		$this->assertContains(
			self::$secondObjectTitle,
			$output
		);
	}

	public function test_ListViewFilteredByNumberOfRoomsForUpperAndLowerLimitEqual_HidesRealtyObjectWithNumberOfRoomsHigherThanLimit() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->firstRealtyUid,
			array('number_of_rooms' => 5)
		);

		$this->assertNotContains(
			self::$firstObjectTitle,
			$this->fixture->render(
				array('numberOfRoomsFrom' => '4.5', 'numberOfRoomsTo' => '4.5')
			)
		);
	}

	public function test_ListViewFilteredByNumberOfRoomsForUpperAndLowerLimitEqualAndCommaAsDecimalSeparator_HidesRealtyObjectWithNumberOfRoomsLowerThanLimit() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->firstRealtyUid,
			array('number_of_rooms' => 4)
		);

		$this->assertNotContains(
			self::$firstObjectTitle,
			$this->fixture->render(
				array('numberOfRoomsFrom' => '4,5', 'numberOfRoomsTo' => '4,5')
			)
		);
	}

	public function test_ListViewFilteredByNumberOfRoomsForUpperAndLowerLimitFourPointFive_DisplaysObjectWithFourPointFiveRooms() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->firstRealtyUid,
			array('number_of_rooms' => 4.5)
		);

		$this->assertContains(
			self::$firstObjectTitle,
			$this->fixture->render(
				array('numberOfRoomsFrom' => '4.5', 'numberOfRoomsTo' => '4.5')
			)
		);
	}


	//////////////////////////////////////////////////
	// Tests concerning the sorting in the list view
	//////////////////////////////////////////////////

	public function testListViewIsSortedAscendinglyByObjectNumberWhenNumbersToSortAreIntegers() {
		$this->fixture->setConfigurationValue('orderBy', 'object_number');
		$this->fixture->setConfigurationValue('listView.', array('descFlag' => 0));

		// Links inside the tags might contain numbers which could influence the
		// result. Therefore the tags are stripped.
		$result = strip_tags($this->fixture->render());

		$this->assertGreaterThan(
			strpos($result, self::$firstObjectNumber),
			strpos($result, self::$secondObjectNumber)
		);
	}

	public function testListViewIsSortedDescendinglyByObjectNumberWhenNumbersToSortAreIntegers() {
		$this->fixture->setConfigurationValue('orderBy', 'object_number');
		$this->fixture->setConfigurationValue('listView.', array('descFlag' => 1));

		// Links inside the tags might contain numbers which could influence the
		// result. Therefore the tags are stripped.
		$result = strip_tags($this->fixture->render());

		$this->assertGreaterThan(
			strpos($result, self::$secondObjectNumber),
			strpos($result, self::$firstObjectNumber)
		);
	}

	public function testListViewIsSortedAscendinglyByObjectNumberWhenTheLowerNumbersFirstDigitIsHigherThanTheHigherNumber() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->firstRealtyUid,
			array('object_number' => '9')
		);
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->secondRealtyUid,
			array('object_number' => '11')
		);

		$this->fixture->setConfigurationValue('orderBy', 'object_number');
		$this->fixture->setConfigurationValue('listView.', array('descFlag' => 0));

		// Links inside the tags might contain numbers which could influence the
		// result. Therefore the tags are stripped.
		$result = strip_tags($this->fixture->render());

		$this->assertGreaterThan(
			strpos($result, '9'),
			strpos($result, '11')
		);
	}

	public function testListViewIsSortedDescendinglyByObjectNumberWhenTheLowerNumbersFirstDigitIsHigherThanTheHigherNumber() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->firstRealtyUid,
			array('object_number' => '9')
		);
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->secondRealtyUid,
			array('object_number' => '11')
		);

		$this->fixture->setConfigurationValue('orderBy', 'object_number');
		$this->fixture->setConfigurationValue('listView.', array('descFlag' => 1));

		// Links inside the tags might contain numbers which could influence the
		// result. Therefore the tags are stripped.
		$result = strip_tags($this->fixture->render());

		$this->assertGreaterThan(
			strpos($result, '11'),
			strpos($result, '9')
		);
	}

	public function testListViewIsSortedAscendinglyByObjectNumberWhenNumbersToSortHaveDots() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->firstRealtyUid,
			array('object_number' => '12.34')
		);
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->secondRealtyUid,
			array('object_number' => '4.10')
		);

		$this->fixture->setConfigurationValue('orderBy', 'object_number');
		$this->fixture->setConfigurationValue('listView.', array('descFlag' => 0));

		// Links inside the tags might contain numbers which could influence the
		// result. Therefore the tags are stripped.
		$result = strip_tags($this->fixture->render());

		$this->assertGreaterThan(
			strpos($result, '4.10'),
			strpos($result, '12.34')
		);
	}

	public function testListViewIsSortedDescendinglyByObjectNumberWhenNumbersToSortHaveDots() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->firstRealtyUid,
			array('object_number' => '12.34')
		);
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->secondRealtyUid,
			array('object_number' => '4.10')
		);

		$this->fixture->setConfigurationValue('orderBy', 'object_number');
		$this->fixture->setConfigurationValue('listView.', array('descFlag' => 1));

		// Links inside the tags might contain numbers which could influence the
		// result. Therefore the tags are stripped.
		$result = strip_tags($this->fixture->render());

		$this->assertGreaterThan(
			strpos($result, '12.34'),
			strpos($result, '4.10')
		);
	}

	public function testListViewIsSortedAscendinglyByObjectNumberWhenNumbersToSortHaveDotsAndDifferOnlyInDecimals() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->firstRealtyUid,
			array('object_number' => '12.34')
		);
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->secondRealtyUid,
			array('object_number' => '12.00')
		);

		$this->fixture->setConfigurationValue('orderBy', 'object_number');
		$this->fixture->setConfigurationValue('listView.', array('descFlag' => 0));

		// Links inside the tags might contain numbers which could influence the
		// result. Therefore the tags are stripped.
		$result = strip_tags($this->fixture->render());

		$this->assertGreaterThan(
			strpos($result, '12.00'),
			strpos($result, '12.34')
		);
	}

	public function testListViewIsSortedDescendinglyByObjectNumberWhenNumbersToSortHaveDotsAndDifferOnlyInDecimals() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->firstRealtyUid,
			array('object_number' => '12.34')
		);
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->secondRealtyUid,
			array('object_number' => '12.00')
		);

		$this->fixture->setConfigurationValue('orderBy', 'object_number');
		$this->fixture->setConfigurationValue('listView.', array('descFlag' => 1));

		// Links inside the tags might contain numbers which could influence the
		// result. Therefore the tags are stripped.
		$result = strip_tags($this->fixture->render());

		$this->assertGreaterThan(
			strpos($result, '12.34'),
			strpos($result, '12.00')
		);
	}

	public function testListViewIsSortedAscendinglyByObjectNumberWhenNumbersToSortHaveCommasAndDifferBeforeTheComma() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->firstRealtyUid,
			array('object_number' => '12,34')
		);
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->secondRealtyUid,
			array('object_number' => '4,10')
		);

		$this->fixture->setConfigurationValue('orderBy', 'object_number');
		$this->fixture->setConfigurationValue('listView.', array('descFlag' => 0));

		// Links inside the tags might contain numbers which could influence the
		// result. Therefore the tags are stripped.
		$result = strip_tags($this->fixture->render());

		$this->assertGreaterThan(
			strpos($result, '4,10'),
			strpos($result, '12,34')
		);
	}

	public function testListViewIsSortedDescendinglyByObjectNumberWhenNumbersToSortHaveCommasAndDifferBeforeTheComma() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->firstRealtyUid,
			array('object_number' => '12,34')
		);
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->secondRealtyUid,
			array('object_number' => '4,10')
		);

		$this->fixture->setConfigurationValue('orderBy', 'object_number');
		$this->fixture->setConfigurationValue('listView.', array('descFlag' => 1));

		// Links inside the tags might contain numbers which could influence the
		// result. Therefore the tags are stripped.
		$result = strip_tags($this->fixture->render());

		$this->assertGreaterThan(
			strpos($result, '12,34'),
			strpos($result, '4,10')
		);
	}

	public function testListViewIsSortedAscendinglyByBuyingPrice() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->firstRealtyUid,
			array('buying_price' => '9', 'object_type' => REALTY_FOR_SALE)
		);
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->secondRealtyUid,
			array('buying_price' => '11', 'object_type' => REALTY_FOR_SALE)
		);

		$this->fixture->setConfigurationValue('orderBy', 'buying_price');
		$this->fixture->setConfigurationValue('listView.', array('descFlag' => 0));

		// Links inside the tags might contain numbers which could influence the
		// result. Therefore the tags are stripped.
		$result = strip_tags($this->fixture->render());

		$this->assertGreaterThan(
			strpos($result, '9'),
			strpos($result, '11')
		);
	}

	public function testListViewIsSortedAscendinglyByRent() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->firstRealtyUid,
			array('rent_excluding_bills' => '9', 'object_type' => REALTY_FOR_RENTING)
		);
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->secondRealtyUid,
			array('rent_excluding_bills' => '11', 'object_type' => REALTY_FOR_RENTING)
		);

		$this->fixture->setConfigurationValue('orderBy', 'rent_excluding_bills');
		$this->fixture->setConfigurationValue('listView.', array('descFlag' => 0));

		// Links inside the tags might contain numbers which could influence the
		// result. Therefore the tags are stripped.
		$result = strip_tags($this->fixture->render());

		$this->assertGreaterThan(
			strpos($result, '9'),
			strpos($result, '11')
		);
	}

	public function testListViewIsSortedAscendinglyByNumberOfRooms() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->firstRealtyUid,
			array('number_of_rooms' => 9)
		);
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->secondRealtyUid,
			array('number_of_rooms' => 11)
		);

		$this->fixture->setConfigurationValue('orderBy', 'number_of_rooms');
		$this->fixture->setConfigurationValue('listView.', array('descFlag' => 0));

		// Links inside the tags might contain numbers which could influence the
		// result. Therefore the tags are stripped.
		$result = strip_tags($this->fixture->render());

		$this->assertGreaterThan(
			strpos($result, '9'),
			strpos($result, '11')
		);
	}

	public function testListViewIsSortedAscendinglyByLivingArea() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->firstRealtyUid,
			array('living_area' => '9')
		);
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->secondRealtyUid,
			array('living_area' => '11')
		);

		$this->fixture->setConfigurationValue('orderBy', 'living_area');
		$this->fixture->setConfigurationValue('listView.', array('descFlag' => 0));

		// Links inside the tags might contain numbers which could influence the
		// result. Therefore the tags are stripped.
		$result = strip_tags($this->fixture->render());

		$this->assertGreaterThan(
			strpos($result, '9'),
			strpos($result, '11')
		);
	}

	public function testListViewIsSortedAscendinglyByTheCitiesTitles() {
		$this->fixture->setConfigurationValue('orderBy', 'city');
		$this->fixture->setConfigurationValue('listView.', array('descFlag' => 0));

		// Links inside the tags might contain numbers which could influence the
		// result. Therefore the tags are stripped.
		$result = strip_tags($this->fixture->render());

		$this->assertGreaterThan(
			strpos($result, self::$secondCityTitle),
			strpos($result, self::$firstCityTitle)
		);
	}

	public function testListViewIsSortedDescendinglyByTheCitiesTitles() {
		$this->fixture->setConfigurationValue('orderBy', 'city');
		$this->fixture->setConfigurationValue('listView.', array('descFlag' => 1));

		// Links inside the tags might contain numbers which could influence the
		// result. Therefore the tags are stripped.
		$result = strip_tags($this->fixture->render());

		$this->assertGreaterThan(
			strpos($result, self::$firstCityTitle),
			strpos($result, self::$secondCityTitle)
		);
	}

	public function testListViewIsSortedByUidIfAnInvalidSortCriterionWasSet() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS, $this->firstRealtyUid, array('street' => '11')
		);
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS, $this->secondRealtyUid, array('street' => '9')
		);

		$this->fixture->setConfigurationValue('orderBy', 'street');
		$this->fixture->setConfigurationValue('listView.', array('descFlag' => 1));

		// Links inside the tags might contain numbers which could influence the
		// result. Therefore the tags are stripped.
		$result = strip_tags($this->fixture->render());

		$this->assertGreaterThan(
			strpos($result, self::$firstCityTitle),
			strpos($result, self::$secondCityTitle)
		);
	}

	public function testListViewIsSortedAscendinglyBySortingFieldForNonZeroSortingFields() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS, $this->firstRealtyUid, array('sorting' => '11')
		);
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS, $this->secondRealtyUid, array('sorting' => '9')
		);

		$this->fixture->setConfigurationValue('listView.', array('descFlag' => 0));

		// Links inside the tags might contain numbers which could influence the
		// result. Therefore the tags are stripped.
		$result = strip_tags($this->fixture->render());

		$this->assertGreaterThan(
			strpos($result, self::$secondCityTitle),
			strpos($result, self::$firstCityTitle)
		);
	}

	public function testListViewIsSortedAscendinglyBySortingFieldWithTheZeroEntryBeingAfterTheNonZeroEntry() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS, $this->firstRealtyUid, array('sorting' => '0')
		);
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS, $this->secondRealtyUid, array('sorting' => '9')
		);

		$this->fixture->setConfigurationValue('listView.', array('descFlag' => 0));

		// Links inside the tags might contain numbers which could influence the
		// result. Therefore the tags are stripped.
		$result = strip_tags($this->fixture->render());

		$this->assertGreaterThan(
			strpos($result, self::$secondCityTitle),
			strpos($result, self::$firstCityTitle)
		);
	}

	public function testListViewIsSortedAscendinglyBySortingFieldAlthoughAnotherOrderByOptionWasSet() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->firstRealtyUid,
			array('sorting' => '11', 'living_area' => '9')
		);
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->secondRealtyUid,
			array('sorting' => '9', 'living_area' => '11')
		);

		$this->fixture->setConfigurationValue('orderBy', 'living_area');
		$this->fixture->setConfigurationValue('listView.', array('descFlag' => 0));

		// Links inside the tags might contain numbers which could influence the
		// result. Therefore the tags are stripped.
		$result = strip_tags($this->fixture->render());

		$this->assertGreaterThan(
			strpos($result, self::$secondCityTitle),
			strpos($result, self::$firstCityTitle)
		);
	}

	public function testListViewIsSortedAscendinglyBySortingFieldAlthoughTheDescendingFlagWasSet() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS, $this->firstRealtyUid, array('sorting' => '11')
		);
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS, $this->secondRealtyUid, array('sorting' => '9')
		);

		$this->fixture->setConfigurationValue('listView.', array('descFlag' => 1));

		// Links inside the tags might contain numbers which could influence the
		// result. Therefore the tags are stripped.
		$result = strip_tags($this->fixture->render());

		$this->assertGreaterThan(
			strpos($result, self::$secondCityTitle),
			strpos($result, self::$firstCityTitle)
		);
	}


	///////////////////////////////////////////
	// Tests for Google Maps in the list view
	///////////////////////////////////////////

	public function testListViewContainsMapForGoogleMapsEnabled() {
		$this->fixture->setConfigurationValue('showGoogleMaps', 1);
		$coordinates = array(
			'exact_coordinates_are_cached' => 1,
			'exact_latitude' => 50.734343,
			'exact_longitude' => 7.10211,
			'show_address' => 1,
		);
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS, $this->firstRealtyUid, $coordinates
		);
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS, $this->secondRealtyUid, $coordinates
		);

		$this->assertContains(
			'<div id="tx_realty_map"',
			$this->fixture->render()
		);
	}

	public function testListViewDoesNotContainMapForGoogleMapsDisabled() {
		$this->fixture->setConfigurationValue('showGoogleMaps', 0);
		$coordinates = array(
			'exact_coordinates_are_cached' => 1,
			'exact_latitude' => 50.734343,
			'exact_longitude' => 7.10211,
			'show_address' => 1,
		);
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS, $this->firstRealtyUid, $coordinates
		);
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS, $this->secondRealtyUid, $coordinates
		);

		$this->assertNotContains(
			'<div id="tx_realty_map"',
			$this->fixture->render()
		);
	}

	public function testListViewDoesNotContainMapIfAllObjectsHaveEmptyCachedCoordinates() {
		$this->fixture->setConfigurationValue('showGoogleMaps', 1);
		$coordinates = array(
			'exact_coordinates_are_cached' => 1,
			'exact_latitude' => '',
			'exact_longitude' => '',
			'show_address' => 1,
		);
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS, $this->firstRealtyUid, $coordinates
		);
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS, $this->secondRealtyUid, $coordinates
		);

		$this->assertNotContains(
			'<div id="tx_realty_map"',
			$this->fixture->render()
		);
	}

	public function testListViewDoesNotContainMapIfObjectOnCurrentPageHasEmptyCachedCoordinatesAndObjectWithCoordinatesIsOnNextPage() {
		$this->fixture->setConfigurationValue('showGoogleMaps', 1);
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS, $this->firstRealtyUid,
			array(
				'exact_coordinates_are_cached' => 1,
				'exact_latitude' => '',
				'exact_longitude' => '',
				'show_address' => 1,
			)
		);
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS, $this->secondRealtyUid,
			array(
				'exact_coordinates_are_cached' => 1,
				'exact_latitude' => 50.734343,
				'exact_longitude' => 7.10211,
				'show_address' => 1,
			)
		);

		$this->fixture->setConfigurationValue('orderBy', 'object_number');
		$this->fixture->setConfigurationValue(
			'listView.', array('descFlag' => 0, 'results_at_a_time' => 1)
		);

		$this->assertNotContains(
			'<div id="tx_realty_map"',
			$this->fixture->render()
		);
	}

	public function testListViewContainsLinkToSingleViewPageInHtmlHeader() {
		$this->fixture->setConfigurationValue('showGoogleMaps', 1);
		$coordinates = array(
			'exact_coordinates_are_cached' => 1,
			'exact_latitude' => 50.734343,
			'exact_longitude' => 7.10211,
			'show_address' => 1,
		);
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS, $this->firstRealtyUid, $coordinates
		);
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS, $this->secondRealtyUid, $coordinates
		);

		$this->fixture->render();

		$this->assertRegExp(
			'/href="\?id=' . $this->singlePid . '/',
			$GLOBALS['TSFE']->additionalHeaderData['tx_realty_pi1_maps']
		);
	}

	public function test_ListView_forActivatedGoogleMapsAndNoEntry_HidesGoogleMapsSubpart() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS, $this->firstRealtyUid, array('zip' => '53111')
		);
		$this->fixture->setConfigurationValue('showSiteSearchInFilterForm', 'show');
		$this->fixture->setConfigurationValue('showGoogleMaps', true);

		$this->fixture->render();

		$this->assertNotContains(
			'tx_realty_map',
			$this->fixture->render(array('site' => '8888'))
		);
	}


	/////////////////////////////////////////////////////
	// Tests concerning links to external details pages
	/////////////////////////////////////////////////////

	public function testLinkToExternalSingleViewPageContainsExternalUrlIfAccessAllowed() {
		$this->allowAccess();

		$this->assertContains(
			'http://' . TX_REALTY_EXTERNAL_SINGLE_PAGE,
			$this->fixture->createLinkToSingleViewPage(
				'foo', 0, TX_REALTY_EXTERNAL_SINGLE_PAGE
			)
		);
	}

	public function testLinkToExternalSingleViewPageContainsExternalUrlIfAccessDenied() {
		$this->denyAccess();
		$this->fixture->setConfigurationValue('loginPID', $this->loginPid);

		$this->assertContains(
			urlencode('http://' . TX_REALTY_EXTERNAL_SINGLE_PAGE),
			$this->fixture->createLinkToSingleViewPage(
				'foo', 0, TX_REALTY_EXTERNAL_SINGLE_PAGE
			)
		);
	}

	public function testLinkToExternalSingleViewPageContainsATagIfAccessDenied() {
		$this->denyAccess();
		$this->fixture->setConfigurationValue('loginPID', $this->loginPid);

		$this->assertContains(
			'<a href=',
			$this->fixture->createLinkToSingleViewPage(
				'&', 0, TX_REALTY_EXTERNAL_SINGLE_PAGE
			)
		);
	}

	public function testLinkToExternalSingleViewPageLinksToLoginPageIfAccessDenied() {
		$this->denyAccess();
		$this->fixture->setConfigurationValue('loginPID', $this->loginPid);

		$this->assertContains(
			'?id=' . $this->loginPid,
			$this->fixture->createLinkToSingleViewPage(
				'foo', 0, TX_REALTY_EXTERNAL_SINGLE_PAGE
			)
		);
	}

	public function testLinkToExternalSingleViewPageContainsRedirectUrlIfAccessDenied() {
		$this->denyAccess();
		$this->fixture->setConfigurationValue('loginPID', $this->loginPid);

		$this->assertContains(
			'redirect_url',
			$this->fixture->createLinkToSingleViewPage(
				'foo', 0, TX_REALTY_EXTERNAL_SINGLE_PAGE
			)
		);
	}

	public function testLinkToExternalSingleViewPageNotLinksToLoginPageIfAccessAllowed() {
		$this->allowAccess();
		$this->fixture->setConfigurationValue('loginPID', $this->loginPid);

		$this->assertNotContains(
			'?id=' . $this->loginPid,
			$this->fixture->createLinkToSingleViewPage(
				'foo', 0, TX_REALTY_EXTERNAL_SINGLE_PAGE
			)
		);
	}

	public function testLinkToExternalSingleViewPageNotContainsRedirectUrlIfAccesAllowed() {
		$this->allowAccess();
		$this->fixture->setConfigurationValue('loginPID', $this->loginPid);

		$this->assertNotContains(
			'redirect_url',
			$this->fixture->createLinkToSingleViewPage(
				'foo', 0, TX_REALTY_EXTERNAL_SINGLE_PAGE
			)
		);
	}


	/////////////////////////////////////////////////////
	// Tests concerning links to separate details pages
	/////////////////////////////////////////////////////

	public function testLinkToSeparateSingleViewPageLinksToSeparateSinglePidIfAccessAllowed() {
		$this->allowAccess();

		$this->assertContains(
			'?id=' . $this->otherSinglePid,
			$this->fixture->createLinkToSingleViewPage(
				'foo', 0, $this->otherSinglePid
			)
		);
	}

	public function testLinkToSeparateSingleViewPageHasSeparateSinglePidInRedirectUrlIfAccessDenied() {
		$this->testingFramework->createFakeFrontEnd($this->otherSinglePid);
		$this->denyAccess();
		$this->fixture->setConfigurationValue('loginPID', $this->loginPid);

		$this->assertContains(
			urlencode('?id=' . $this->otherSinglePid),
			$this->fixture->createLinkToSingleViewPage(
				'foo', 0, $this->otherSinglePid
			)
		);
	}

	public function testLinkToSeparateSingleViewPageContainsATagIfAccessDenied() {
		$this->denyAccess();
		$this->fixture->setConfigurationValue('loginPID', $this->loginPid);

		$this->assertContains(
			'<a href=',
			$this->fixture->createLinkToSingleViewPage(
				'&', 0, $this->otherSinglePid
			)
		);
	}

	public function testLinkToSeparateSingleViewPageLinksToLoginPageIfAccessDenied() {
		$this->denyAccess();
		$this->fixture->setConfigurationValue('loginPID', $this->loginPid);

		$this->assertContains(
			'?id=' . $this->loginPid,
			$this->fixture->createLinkToSingleViewPage(
				'foo', 0, $this->otherSinglePid
			)
		);
	}

	public function testLinkToSeparateSingleViewPageContainsRedirectUrlIfAccessDenied() {
		$this->denyAccess();
		$this->fixture->setConfigurationValue('loginPID', $this->loginPid);

		$this->assertContains(
			'redirect_url',
			$this->fixture->createLinkToSingleViewPage(
				'foo', 0, $this->otherSinglePid
			)
		);
	}

	public function testLinkToSeparateSingleViewPageNotLinksToLoginPageIfAccessAllowed() {
		$this->allowAccess();
		$this->fixture->setConfigurationValue('loginPID', $this->loginPid);

		$this->assertNotContains(
			'?id=' . $this->loginPid,
			$this->fixture->createLinkToSingleViewPage(
				'foo', 0, $this->otherSinglePid
			)
		);
	}

	public function testLinkToSeparateSingleViewPageNotContainsRedirectUrlIfAccesAllowed() {
		$this->allowAccess();
		$this->fixture->setConfigurationValue('loginPID', $this->loginPid);

		$this->assertNotContains(
			'redirect_url',
			$this->fixture->createLinkToSingleViewPage(
				'foo', 0, $this->otherSinglePid
			)
		);
	}

	public function testLinkToSingleViewPageIsEmptyForEmptyLinkText() {
		$this->assertEquals(
			'', $this->fixture->createLinkToSingleViewPage('', 0)
		);
		$this->allowAccess();

		$this->assertEquals(
			'',
			$this->fixture->createLinkToSingleViewPage('', 0)
		);
	}

	public function testLinkToSingleViewPageContainsLinkText() {
		$this->assertContains(
			'foo',
			$this->fixture->createLinkToSingleViewPage('foo', 0)
		);
	}

	public function testLinkToSingleViewPageHtmlSpecialCharsLinkText() {
		$this->assertContains(
			'a &amp; &quot; &gt;',
			$this->fixture->createLinkToSingleViewPage('a & " >', 0)
		);
	}

	public function testLinkToSingleViewPageHasSinglePidAsLinkTargetIfAccessAllowed() {
		$this->allowAccess();

		$this->assertContains(
			'?id=' . $this->singlePid,
			$this->fixture->createLinkToSingleViewPage('foo', 0)
		);
	}

	public function testLinkToSingleViewPageContainsSinglePidInRedirectUrlIfAccessDenied() {
		$this->testingFramework->createFakeFrontEnd($this->singlePid);
		$this->denyAccess();
		$this->fixture->setConfigurationValue('loginPID', $this->loginPid);

		$this->assertContains(
			urlencode('?id=' . $this->singlePid),
			$this->fixture->createLinkToSingleViewPage('foo', 0)
		);
	}

	public function testLinkToSingleViewPageEscapesAmpersandsIfAccessAllowed() {
		$this->allowAccess();

		$this->assertContains(
			'&amp;', $this->fixture->createLinkToSingleViewPage('&', 0)
		);
	}

	public function testLinkToSingleViewPageEscapesAmpersandsIfAccessDenied() {
		$this->denyAccess();

		$this->assertContains(
			'&amp;', $this->fixture->createLinkToSingleViewPage('&', 0)
		);
	}

	public function testLinkToSingleViewPageContainsATagIfAccessAllowed() {
		$this->allowAccess();

		$this->assertContains(
			'<a href=', $this->fixture->createLinkToSingleViewPage('&', 0)
		);
	}

	public function testLinkToSingleViewPageContainsATagIfAccessDenied() {
		$this->denyAccess();
		$this->fixture->setConfigurationValue('loginPID', $this->loginPid);

		$this->assertContains(
			'<a href=', $this->fixture->createLinkToSingleViewPage('&', 0)
		);
	}

	public function testLinkToSingleViewPageLinksToLoginPageIfAccessDenied() {
		$this->denyAccess();
		$this->fixture->setConfigurationValue('loginPID', $this->loginPid);

		$this->assertContains(
			'?id=' . $this->loginPid,
			$this->fixture->createLinkToSingleViewPage('foo', 0)
		);
	}

	public function testLinkToSingleViewPageContainsRedirectUrlIfAccessDenied() {
		$this->denyAccess();
		$this->fixture->setConfigurationValue('loginPID', $this->loginPid);

		$this->assertContains(
			'redirect_url',
			$this->fixture->createLinkToSingleViewPage('foo', 0)
		);
	}

	public function testLinkToSingleViewPageNotLinksToLoginPageIfAccessAllowed() {
		$this->allowAccess();
		$this->fixture->setConfigurationValue('loginPID', $this->loginPid);

		$this->assertNotContains(
			'?id=' . $this->loginPid,
			$this->fixture->createLinkToSingleViewPage('foo', 0)
		);
	}

	public function testLinkToSingleViewPageNotContainsRedirectUrlIfAccesAllowed() {
		$this->allowAccess();
		$this->fixture->setConfigurationValue('loginPID', $this->loginPid);

		$this->assertNotContains(
			'redirect_url',
			$this->fixture->createLinkToSingleViewPage('foo', 0)
		);
	}


	///////////////////////////////////////////
	// Tests concerning getUidForRecordNumber
	///////////////////////////////////////////

	/**
	 * @test
	 */
	public function getUidForRecordNumberNegativeRecordNumberThrowsException() {
		$this->setExpectedException(
			'Exception', 'The record position must be a non-negative integer.'
		);

		$this->fixture->getUidForRecordNumber(-1);
	}

	/**
	 * @test
	 */
	public function getUidForRecordNumberZeroReturnsFirstRecordsUid() {
		$this->assertEquals(
			$this->firstRealtyUid,
			$this->fixture->getUidForRecordNumber(0)
		);
	}

	/**
	 * @test
	 */
	public function getUidForRecordNumberForOneReturnsSecondRecordsUid() {
		$this->assertEquals(
			$this->secondRealtyUid,
			$this->fixture->getUidForRecordNumber(1)
		);
	}

	/**
	 * @test
	 */
	public function getUidForRecordNumberForNoObjectForGivenRecordNumberReturnsZero() {
		$this->fixture->setPiVars(array('numberOfRoomsFrom' => 41));

		$this->assertEquals(
			0,
			$this->fixture->getUidForRecordNumber(0)
		);
	}

	/**
	 * @test
	 */
	public function getUidForRecordNumberForFilteringSetInPiVarsConsidersFilterOptions() {
		$this->fixture->setPiVars(array('numberOfRoomsFrom' => 41));
		$fittingRecordUid = $this->testingFramework->createRecord(
			REALTY_TABLE_OBJECTS,
			array(
				'title' => self::$firstObjectTitle,
				'object_number' => self::$firstObjectNumber,
				'pid' => $this->systemFolderPid,
				'city' => $this->firstCityUid,
				'object_type' => REALTY_FOR_RENTING,
				'number_of_rooms' => 42,
			)
		);

		$this->assertEquals(
			$fittingRecordUid,
			$this->fixture->getUidForRecordNumber(0)
		);
	}
}
?>