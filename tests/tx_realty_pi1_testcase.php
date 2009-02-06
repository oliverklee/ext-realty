<?php
/***************************************************************
* Copyright notice
*
* (c) 2007-2009 Oliver Klee (typo3-coding@oliverklee.de)
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

require_once(PATH_tslib . 'class.tslib_content.php');
require_once(PATH_tslib . 'class.tslib_feuserauth.php');
require_once(PATH_t3lib . 'class.t3lib_timetrack.php');

require_once(t3lib_extMgm::extPath('oelib') . 'class.tx_oelib_testingFramework.php');
require_once(t3lib_extMgm::extPath('oelib') . 'class.tx_oelib_headerProxyFactory.php');
require_once(t3lib_extMgm::extPath('oelib') . 'class.tx_oelib_configurationProxy.php');

require_once(t3lib_extMgm::extPath('realty') . 'lib/tx_realty_constants.php');
require_once(t3lib_extMgm::extPath('realty') . 'pi1/class.tx_realty_pi1.php');

define('TX_REALTY_EXTERNAL_SINGLE_PAGE', 'www.oliverklee.de/');

/**
 * Testcase for the tx_realty_pi1 class in the 'realty' extension.
 *
 * @package		TYPO3
 * @subpackage	tx_realty
 *
 * @author		Oliver Klee <typo3-coding@oliverklee.de>
 */
class tx_realty_pi1_testcase extends tx_phpunit_testcase {
	/** @var	tx_realty_pi1 */
	private $fixture;

	/** @var	tx_oelib_testingFramework */
	private $testingFramework;

	private $loginPid = 0;
	private $listViewPid = 0;
	private $singlePid = 0;
	private $otherSinglePid = 0;
	private $favoritesPid = 0;
	private $systemFolderPid = 0;
	private $subSystemFolderPid = 0;

	/** first dummy realty object */
	private $firstRealtyUid = 0;
	/** object number for the first dummy realty object */
	private static $firstObjectNumber = '1';
	/** title for the first dummy realty object */
	private static $firstObjectTitle = 'a title';

	/** second dummy realty object */
	private $secondRealtyUid = 0;
	/** object number for the second dummy realty object */
	private static $secondObjectNumber = '2';
	/** title for the second dummy realty object */
	private static $secondObjectTitle = 'another title';

	/** first dummy city UID */
	private $firstCityUid = 0;
	/** title for the first dummy city */
	private static $firstCityTitle = 'foo city';

	/** second dummy city UID */
	private $secondCityUid = 0;
	/** title for the second dummy city */
	private static $secondCityTitle = 'bar city';

	public function setUp() {
		tx_oelib_headerProxyFactory::getInstance()->enableTestMode();
		$this->testingFramework = new tx_oelib_testingFramework('tx_realty');
		$this->testingFramework->createFakeFrontEnd();

		$this->createDummyPages();
		$this->createDummyObjects();

		// True enables the test mode which inhibits the FE editors FORMidable
		// object from being created.
		$this->fixture = new tx_realty_pi1(true);
		// This passed array with configuration values becomes part of
		// $this->fixture->conf. "conf" is inherited from tslib_pibase and needs
		// to contain "pidList". "pidList" is none of our configuration values
		// but if cObj->currentRecord is set, "pidList" is set to our
		// configuration value "pages".
		// As we are in BE mode, "pidList" needs to be set directly.
		// The template file also needs to be included explicitly.
		$this->fixture->init(array(
			'templateFile' => 'EXT:realty/pi1/tx_realty_pi1.tpl.htm',
			'singlePID' => $this->singlePid,
			'favoritesPID' => $this->favoritesPid,
			'pidList' => $this->systemFolderPid
		));

		// Ensures an empty favorites list.
		$this->fixture->storeFavorites(array());
	}

	public function tearDown() {
		tx_oelib_headerProxyFactory::getInstance()->discardInstance();
		$this->testingFramework->cleanUp();
		unset($this->fixture, $this->testingFramework);
	}


	///////////////////////
	// Utility functions.
	///////////////////////

	/**
	 * Denies access to the details page by requiring logon to display that page
	 * and then logging out any logged-in FE users.
	 */
	private function denyAccess() {
		$this->fixture->setConfigurationValue('requireLoginForSingleViewPage', 1);
		$this->testingFramework->logoutFrontEndUser();
	}

	/**
	 * Allows access to the details page by not requiring logon to display that
	 * page.
	 */
	private function allowAccess() {
		$this->fixture->setConfigurationValue('requireLoginForSingleViewPage', 0);
	}

	/**
	 * Creates dummy FE pages (like login and single view).
	 */
	private function createDummyPages() {
		$this->loginPid = $this->testingFramework->createFrontEndPage();
		$this->listViewPid = $this->testingFramework->createFrontEndPage();
		$this->singlePid = $this->testingFramework->createFrontEndPage();
		$this->otherSinglePid = $this->testingFramework->createFrontEndPage();
		$this->favoritesPid = $this->testingFramework->createFrontEndPage();
		$this->systemFolderPid = $this->testingFramework->createSystemFolder(1);
		$this->subSystemFolderPid = $this->testingFramework->createSystemFolder(
			$this->systemFolderPid
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
			)
		);
		$this->secondRealtyUid = $this->testingFramework->createRecord(
			REALTY_TABLE_OBJECTS,
			array(
				'title' => self::$secondObjectTitle,
				'object_number' => self::$secondObjectNumber,
				'pid' => $this->systemFolderPid,
				'city' => $this->secondCityUid,
			)
		);
	}


	//////////////////////////////////////
	// Tests for the configuration check
	//////////////////////////////////////

	public function testConfigurationCheckIsActiveWhenEnabled() {
		// The configuration check is created during initialization, therefore
		// the object to test is recreated for this test.
		unset($this->fixture);
		tx_oelib_configurationProxy::getInstance('realty')
			->setConfigurationValueBoolean('enableConfigCheck', true);
		$this->fixture = new tx_realty_pi1();
		$this->fixture->init(array(
			'templateFile' => 'EXT:realty/pi1/tx_realty_pi1.tpl.htm',
			'pidList' => $this->systemFolderPid
		));
		// ensures there is at least one configuration error to report
		$this->fixture->setConfigurationValue('numberOfDecimals', -1);

		$this->assertContains(
			'Configuration check warning',
			$this->fixture->main('', array())
		);
	}

	public function testConfigurationCheckIsNotActiveWhenDisabled() {
		// The configuration check is created during initialization, therefore
		// the object to test is recreated for this test.
		unset($this->fixture);
		tx_oelib_configurationProxy::getInstance('realty')
			->setConfigurationValueBoolean('enableConfigCheck', false);
		$this->fixture = new tx_realty_pi1();
		$this->fixture->init(array(
			'templateFile' => 'EXT:realty/pi1/tx_realty_pi1.tpl.htm',
			'pidList' => $this->systemFolderPid
		));
		// ensures there is at least one configuration error to report
		$this->fixture->setConfigurationValue('numberOfDecimals', -1);

		$this->assertNotContains(
			'Configuration check warning',
			$this->fixture->main('', array())
		);
	}


	//////////////////////////////////////
	// Tests for the basic functionality
	//////////////////////////////////////

	public function testPi1MustBeInitialized() {
		$this->assertNotNull(
			$this->fixture
		);
		$this->assertTrue(
			$this->fixture->isInitialized()
		);
	}


	/////////////////////////////////////////////////////////////////////////////
	// Tests for the access-restricted single view and links to the single view
	/////////////////////////////////////////////////////////////////////////////

	public function testAccessToSingleViewIsAllowedWithoutLoginPerDefault() {
		$this->testingFramework->logoutFrontEndUser();

		$this->assertTrue(
			$this->fixture->isAccessToSingleViewPageAllowed()
		);
	}

	public function testAccessToSingleViewIsAllowedWithLoginPerDefault() {
		$feUserId = $this->testingFramework->createFrontEndUser(
			$this->testingFramework->createFrontEndUserGroup()
		);
		$this->testingFramework->loginFrontEndUser($feUserId);

		$this->assertTrue(
			$this->fixture->isAccessToSingleViewPageAllowed()
		);
	}

	public function testAccessToSingleViewIsAllowedWithoutLoginIfNotDeniedPerConfiguration() {
		$this->fixture->setConfigurationValue('requireLoginForSingleViewPage', 0);
		$this->testingFramework->logoutFrontEndUser();

		$this->assertTrue(
			$this->fixture->isAccessToSingleViewPageAllowed()
		);
	}

	public function testAccessToSingleViewIsAllowedWithLoginIfNotDeniedPerConfiguration() {
		$this->fixture->setConfigurationValue('requireLoginForSingleViewPage', 0);
		$feUserId = $this->testingFramework->createFrontEndUser(
			$this->testingFramework->createFrontEndUserGroup()
		);
		$this->testingFramework->loginFrontEndUser($feUserId);

		$this->assertTrue(
			$this->fixture->isAccessToSingleViewPageAllowed()
		);
	}

	public function testAccessToSingleViewIsDeniedWithoutLoginIfDeniedPerConfiguration() {
		$this->fixture->setConfigurationValue('requireLoginForSingleViewPage', 1);
		$this->testingFramework->logoutFrontEndUser();

		$this->assertFalse(
			$this->fixture->isAccessToSingleViewPageAllowed()
		);
	}

	public function testAccessToSingleViewIsAllowedWithLoginIfDeniedPerConfiguration() {
		$this->fixture->setConfigurationValue('requireLoginForSingleViewPage', 1);
		$feUserId = $this->testingFramework->createFrontEndUser(
			$this->testingFramework->createFrontEndUserGroup()
		);
		$this->testingFramework->loginFrontEndUser($feUserId);

		$this->assertTrue(
			$this->fixture->isAccessToSingleViewPageAllowed()
		);
	}

	public function testLinkToSingleViewPageIsEmptyForEmptyLinkText() {
		$this->assertEquals(
			'', $this->fixture->createLinkToSingleViewPage('', 0)
		);
		$this->allowAccess();
		$this->assertEquals(
			'', $this->fixture->createLinkToSingleViewPage('', 0)
		);
	}

	public function testLinkToSingleViewPageContainsLinkText() {
		$this->assertContains(
			'foo', $this->fixture->createLinkToSingleViewPage('foo', 0)
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


	//////////////////////////////////////////////////////////
	// Tests for the images in the list view and detail view
	//////////////////////////////////////////////////////////

	public function testListViewContainsEnabledImage() {
		$this->testingFramework->createRecord(
			REALTY_TABLE_IMAGES,
			array(
				'caption' => 'test image',
				'realty_object_uid' => $this->firstRealtyUid,
			)
		);
		$this->fixture->setConfigurationValue('what_to_display', 'realty_list');

		$this->assertContains(
			'test image',
			$this->fixture->main('', array())
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
		$this->fixture->setConfigurationValue('what_to_display', 'realty_list');

		$this->assertNotContains(
			'test image',
			$this->fixture->main('', array())
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
		$this->fixture->setConfigurationValue('what_to_display', 'realty_list');

		$this->assertNotContains(
			'test image',
			$this->fixture->main('', array())
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
		$this->fixture->setConfigurationValue('what_to_display', 'realty_list');

		$this->assertContains(
			'tx_realty_pi1[showUid]='.$this->firstRealtyUid,
			$this->fixture->main('', array())
		);
		$this->assertContains(
			'?id=' . $this->singlePid,
			$this->fixture->main('', array())
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
		$this->fixture->setConfigurationValue('what_to_display', 'realty_list');
		// this enables the gallery popup window
		$this->fixture->setConfigurationValue(
			'galleryPopupParameters',
			'width=600,height=400,resizable=no,toolbar=no,'
			.'location=no,directories=no,status=no,menubar=no'
		);
		$this->assertNotContains(
			'onclick="window.open(',
			$this->fixture->main('', array())
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
		$this->fixture->setConfigurationValue('what_to_display', 'realty_list');
		$this->fixture->setConfigurationValue('galleryPID', $galleryPid);
		$this->assertNotContains(
			'?id=' . $galleryPid,
			$this->fixture->main('', array())
		);
	}

	public function testImageInTheDetailViewUsesFullUrlForPopUp() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->firstRealtyUid,
			array('images' => '1')
		);
		$this->testingFramework->createRecord(
			REALTY_TABLE_IMAGES,
			array(
				'caption' => 'foo',
				'realty_object_uid' => $this->firstRealtyUid,
			)
		);

		$this->fixture->setConfigurationValue(
			'galleryPID', $this->testingFramework->createFrontEndPage()
		);
		$this->fixture->setConfigurationValue(
			'galleryPopupParameters', 'width=600,height=400'
		);
		$this->fixture->setConfigurationValue('what_to_display', 'single_view');
		$this->fixture->piVars['showUid'] = $this->firstRealtyUid;

		$this->assertContains(
			'window.open(\'http://',
			$this->fixture->main('', array())
		);
	}


	////////////////////////////////////
	// Tests for data in the list view
	////////////////////////////////////

	public function testListViewFillsMarkerForObjectNumber() {
		$this->fixture->setConfigurationValue('what_to_display', 'realty_list');
		$this->fixture->setConfigurationValue(
			'listView.',
			array('orderBy' => 'object_number', 'descFlag' => 0)
		);
		$this->fixture->main('', array());

		$this->assertEquals(
			self::$secondObjectNumber,
			$this->fixture->getMarker('object_number')
		);
	}

	public function testCreateListViewReturnsPricesWithTheCurrencyUnitSetInTsSetup() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->firstRealtyUid,
			array('buying_price' => '9', 'object_type' => REALTY_FOR_SALE)
		);
		$this->fixture->setConfigurationValue('what_to_display', 'realty_list');
		$this->fixture->setConfigurationValue('currencyUnit', '&euro;');

		$this->assertContains(
			'&euro;',
			$this->fixture->main('', array())
		);
	}

	public function testListViewFormatsPriceUsingSpaceAsThousandsSeparator() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->firstRealtyUid,
			array('buying_price' => '1234567', 'object_type' => REALTY_FOR_SALE)
		);
		$this->fixture->setConfigurationValue('what_to_display', 'realty_list');

		$this->assertContains(
			'1 234 567',
			$this->fixture->main('', array())
		);
	}

	public function testCreateListViewReturnsListOfRecords() {
		$this->fixture->setConfigurationValue('what_to_display', 'realty_list');

		$this->assertContains(
			self::$firstObjectTitle,
			$this->fixture->main('', array())
		);
		$this->assertContains(
			self::$secondObjectTitle,
			$this->fixture->main('', array())
		);
	}

	public function testCreateListViewReturnsMainSysFolderRecordsAndSubFolderRecordsIfRecursionIsEnabled() {
		$this->fixture->setConfigurationValue('what_to_display', 'realty_list');
		$this->fixture->setConfigurationValue('recursive', '1');

		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->firstRealtyUid,
			array('pid' => $this->subSystemFolderPid)
		);

		$result = $this->fixture->main('', array());
		$this->assertContains(
			self::$firstObjectTitle,
			$result
		);
		$this->assertContains(
			self::$secondObjectTitle,
			$result
		);
	}

	public function testCreateListViewNotReturnsSubFolderRecordsIfRecursionIsDisabled() {
		$this->fixture->setConfigurationValue('what_to_display', 'realty_list');
		$this->fixture->setConfigurationValue('recursive', '0');

		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->firstRealtyUid,
			array('pid' => $this->subSystemFolderPid)
		);

		$result = $this->fixture->main('', array());
		$this->assertNotContains(
			self::$firstObjectTitle,
			$result
		);
		$this->assertContains(
			self::$secondObjectTitle,
			$result
		);
	}

	public function testTheResultIsCountedCorrectly() {
		$this->fixture->setConfigurationValue('what_to_display', 'realty_list');
		$this->fixture->main('', array());

		$this->assertEquals(
			2,
			$this->fixture->internal['res_count']
		);
	}


	////////////////////////////////////////////////////
	// Tests concerning additional header in list view
	////////////////////////////////////////////////////

	public function testCreateListViewForNoPostDataSentDoesNotAddCacheControlHeader() {
		$this->fixture->setConfigurationValue('what_to_display', 'realty_list');

		$this->fixture->main('', array());

		$this->assertNotEquals(
			tx_oelib_headerProxyFactory::getInstance()
				->getHeaderProxy()->getLastAddedHeader(),
			'Cache-Control: max-age=86400, must-revalidate'
		);
	}

	public function testCreateListViewForPostDataSentAddsCacheControlHeader() {
		$this->fixture->setConfigurationValue('what_to_display', 'realty_list');

		$_POST['tx_realty_pi1'] = 'foo';
		$this->fixture->main('', array());
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
		$this->fixture->setConfigurationValue('what_to_display', 'realty_list');
		$this->fixture->piVars = array('priceRange' => '10-');

		$this->assertContains(
			self::$firstObjectTitle,
			$this->fixture->main('', array())
		);
	}

	public function testListViewFilteredByPriceDisplaysRealtyObjectWithBuyingPriceLowerThanTheGreaterLimit() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->firstRealtyUid,
			array('buying_price' => 1)
		);
		$this->fixture->setConfigurationValue('what_to_display', 'realty_list');
		$this->fixture->piVars = array('priceRange' => '-10');

		$this->assertContains(
			self::$firstObjectTitle,
			$this->fixture->main('', array())
		);
	}

	public function testListViewFilteredByPriceDisplaysRealtyObjectWithZeroBuyingPriceAndZeroRentForNoLowerLimitSet() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->firstRealtyUid,
			array('buying_price' => 0, 'rent_excluding_bills' => 0)
		);
		$this->fixture->setConfigurationValue('what_to_display', 'realty_list');
		$this->fixture->piVars = array('priceRange' => '-10');

		$this->assertContains(
			self::$firstObjectTitle,
			$this->fixture->main('', array())
		);
	}

	public function testListViewFilteredByPriceNotDisplaysRealtyObjectWithZeroBuyingPriceAndRentOutOfRangeForNoLowerLimitSet() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->firstRealtyUid,
			array('buying_price' => 0, 'rent_excluding_bills' => 11)
		);
		$this->fixture->setConfigurationValue('what_to_display', 'realty_list');
		$this->fixture->piVars = array('priceRange' => '-10');

		$this->assertNotContains(
			self::$firstObjectTitle,
			$this->fixture->main('', array())
		);
	}

	public function testListViewFilteredByPriceDoesNotDisplayRealtyObjectBelowRangeLimit() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->secondRealtyUid,
			array('buying_price' => 9)
		);
		$this->fixture->setConfigurationValue('what_to_display', 'realty_list');
		$this->fixture->piVars = array('priceRange' => '10-100');

		$this->assertNotContains(
			self::$secondObjectTitle,
			$this->fixture->main('', array())
		);
	}

	public function testListViewFilteredByPriceDoesNotDisplayRealtyObjectSuperiorToRangeLimit() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->secondRealtyUid,
			array('buying_price' => 101)
		);
		$this->fixture->setConfigurationValue('what_to_display', 'realty_list');
		$this->fixture->piVars = array('priceRange' => '10-100');

		$this->assertNotContains(
			self::$secondObjectTitle,
			$this->fixture->main('', array())
		);
	}

	public function testListViewFilteredByPriceDisplaysRealtyObjectWithPriceOfLowerRangeLimit() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->firstRealtyUid,
			array('buying_price' => 10)
		);
		$this->fixture->setConfigurationValue('what_to_display', 'realty_list');
		$this->fixture->piVars = array('priceRange' => '10-20');

		$this->assertContains(
			self::$firstObjectTitle,
			$this->fixture->main('', array())
		);
	}

	public function testListViewFilteredByPriceDisplaysRealtyObjectWithPriceOfUpperRangeLimit() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->firstRealtyUid,
			array('buying_price' => 20)
		);
		$this->fixture->setConfigurationValue('what_to_display', 'realty_list');
		$this->fixture->piVars = array('priceRange' => '10-20');

		$this->assertContains(
			self::$firstObjectTitle,
			$this->fixture->main('', array())
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
		$this->fixture->setConfigurationValue('what_to_display', 'realty_list');
		$this->fixture->piVars = array('priceRange' => '-10');

		$this->assertContains(
			self::$firstObjectTitle,
			$this->fixture->main('', array())
		);
		$this->assertContains(
			self::$secondObjectTitle,
			$this->fixture->main('', array())
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
		$this->fixture->setConfigurationValue('what_to_display', 'realty_list');
		$this->fixture->piVars = array('priceRange' => '-10');

		$this->assertContains(
			self::$firstObjectTitle,
			$this->fixture->main('', array())
		);
		$this->assertContains(
			self::$secondObjectTitle,
			$this->fixture->main('', array())
		);
	}

	public function testListViewFilteredBySiteDisplaysObjectWithMatchingZip() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS, $this->firstRealtyUid, array('zip' => '12345')
		);
		$this->fixture->setConfigurationValue('what_to_display', 'realty_list');
		$this->fixture->setConfigurationValue('showSiteSearchInFilterForm', 'show');
		$this->fixture->piVars = array('site' => '12345');

		$this->assertContains(
			self::$firstObjectTitle,
			$this->fixture->main('', array())
		);
	}

	public function testListViewFilteredBySiteDisplaysObjectWithMatchingCity() {
		$this->fixture->setConfigurationValue('what_to_display', 'realty_list');
		$this->fixture->setConfigurationValue('showSiteSearchInFilterForm', 'show');
		$this->fixture->piVars = array('site' => self::$firstCityTitle);

		$this->assertContains(
			self::$firstObjectTitle,
			$this->fixture->main('', array())
		);
	}

	public function testListViewFilteredBySiteDisplaysObjectWithPartlyMatchingZip() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS, $this->firstRealtyUid, array('zip' => '12345')
		);
		$this->fixture->setConfigurationValue('what_to_display', 'realty_list');
		$this->fixture->setConfigurationValue('showSiteSearchInFilterForm', 'show');
		$this->fixture->piVars = array('site' => '12000');

		$this->assertContains(
			self::$firstObjectTitle,
			$this->fixture->main('', array())
		);
	}

	public function testListViewFilteredBySiteDisplaysObjectWithPartlyMatchingCity() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_CITIES, $this->firstCityUid, array('title' => 'foo-bar')
		);
		$this->fixture->setConfigurationValue('what_to_display', 'realty_list');
		$this->fixture->setConfigurationValue('showSiteSearchInFilterForm', 'show');
		$this->fixture->piVars = array('site' => 'foo');

		$this->assertContains(
			self::$firstObjectTitle,
			$this->fixture->main('', array())
		);
	}

	public function testListViewFilteredBySiteNotDisplaysObjectWithNonMatchingZip() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS, $this->firstRealtyUid, array('zip' => '12345')
		);
		$this->fixture->setConfigurationValue('what_to_display', 'realty_list');
		$this->fixture->setConfigurationValue('showSiteSearchInFilterForm', 'show');
		$this->fixture->piVars = array('site' => '34');

		$this->assertNotContains(
			self::$firstObjectTitle,
			$this->fixture->main('', array())
		);
	}

	public function testListViewFilteredBySiteNotDisplaysObjectWithNonMatchingCity() {
		$this->fixture->setConfigurationValue('what_to_display', 'realty_list');
		$this->fixture->setConfigurationValue('showSiteSearchInFilterForm', 'show');
		$this->fixture->piVars = array('site' => self::$firstCityTitle . '-foo');

		$this->assertNotContains(
			self::$firstObjectTitle,
			$this->fixture->main('', array())
		);
	}

	public function testListViewFilteredBySiteDisplaysAllObjectsForAnEmptyString() {
		$this->fixture->setConfigurationValue('what_to_display', 'realty_list');
		$this->fixture->setConfigurationValue('showSiteSearchInFilterForm', 'show');
		$this->fixture->piVars = array('site' => '');

		$this->assertContains(
			self::$firstObjectTitle,
			$this->fixture->main('', array())
		);
		$this->assertContains(
			self::$secondObjectTitle,
			$this->fixture->main('', array())
		);
	}

	public function testListViewFilteredBySiteAndPriceDisplaysObjectInPriceRangeWithMatchingCity() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->firstRealtyUid,
			array('buying_price' => 50)
		);
		$this->fixture->setConfigurationValue('what_to_display', 'realty_list');
		$this->fixture->setConfigurationValue('showSiteSearchInFilterForm', 'show');
		$this->fixture->piVars = array('priceRange' => '10-100', 'site' => self::$firstCityTitle);

		$this->assertContains(
			self::$firstObjectTitle,
			$this->fixture->main('', array())
		);
	}

	public function testListViewFilteredBySiteAndPriceDisplaysObjectInPriceRangeWithMatchingZip() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->firstRealtyUid,
			array('buying_price' => 50, 'zip' => '12345')
		);
		$this->fixture->setConfigurationValue('what_to_display', 'realty_list');
		$this->fixture->setConfigurationValue('showSiteSearchInFilterForm', 'show');
		$this->fixture->piVars = array('priceRange' => '10-100', 'site' => '12345');

		$this->assertContains(
			self::$firstObjectTitle,
			$this->fixture->main('', array())
		);
	}

	public function testListViewFilteredBySiteAndPriceNotDisplaysObjectInPriceRangeWithNonMatchingCity() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->firstRealtyUid,
			array('buying_price' => 50)
		);
		$this->fixture->setConfigurationValue('what_to_display', 'realty_list');
		$this->fixture->setConfigurationValue('showSiteSearchInFilterForm', 'show');
		$this->fixture->piVars = array('priceRange' => '10-100', 'site' => self::$firstCityTitle . '-foo'
		);

		$this->assertNotContains(
			self::$firstObjectTitle,
			$this->fixture->main('', array())
		);
	}

	public function testListViewFilteredBySiteAndPriceNotDisplaysObjectInPriceRangeWithNonMatchingZip() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->firstRealtyUid,
			array('buying_price' => 50, 'zip' => '12345')
		);
		$this->fixture->setConfigurationValue('what_to_display', 'realty_list');
		$this->fixture->setConfigurationValue('showSiteSearchInFilterForm', 'show');
		$this->fixture->piVars = array('priceRange' => '10-100', 'site' => '34');

		$this->assertNotContains(
			self::$firstObjectTitle,
			$this->fixture->main('', array())
		);
	}

	public function testListViewFilteredBySiteAndPriceNotDisplaysObjectOutOfPriceRangeWithMatchingCity() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->firstRealtyUid,
			array('buying_price' => 150)
		);
		$this->fixture->setConfigurationValue('what_to_display', 'realty_list');
		$this->fixture->setConfigurationValue('showSiteSearchInFilterForm', 'show');
		$this->fixture->piVars = array('priceRange' => '10-100', 'site' => self::$firstCityTitle);

		$this->assertNotContains(
			self::$firstObjectTitle,
			$this->fixture->main('', array())
		);
	}

	public function testListViewFilteredBySiteAndPriceNotDisplaysObjectOutOfPriceRangeWithMatchingZip() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->firstRealtyUid,
			array('buying_price' => 150, 'zip' => '12345')
		);
		$this->fixture->setConfigurationValue('what_to_display', 'realty_list');
		$this->fixture->setConfigurationValue('showSiteSearchInFilterForm', 'show');
		$this->fixture->piVars = array('priceRange' => '10-100', 'site' => '12345');

		$this->assertNotContains(
			self::$firstObjectTitle,
			$this->fixture->main('', array())
		);
	}


	//////////////////////////////////////////
	// Tests for the list filter checkboxes.
	//////////////////////////////////////////

	public function testListFilterIsVisibleIfCheckboxesFilterSetToDistrictAndCitySelectorIsInactive() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->firstRealtyUid,
			array('district' => $this->testingFramework->createRecord(
				REALTY_TABLE_DISTRICTS, array('title' => 'test district')
			))
		);
		$this->fixture->setConfigurationValue('what_to_display', 'realty_list');
		$this->fixture->setConfigurationValue('checkboxesFilter', 'district');

		$this->assertContains(
			'id="tx_realty_pi1_search"',
			$this->fixture->main('', array())
		);
	}

	public function testCheckboxesFilterDoesNotHaveUnreplacedMarkersForMinimalContent() {
		$systemFolder = $this->testingFramework->createSystemFolder();
		$this->testingFramework->createRecord(
			REALTY_TABLE_OBJECTS,
			array(
				// A city is the minimum requirement for an object to be displayed,
				// though the object is rendered empty because the city has no title.
				'city' => $this->testingFramework->createRecord(REALTY_TABLE_CITIES),
				'pid' => $systemFolder
			)
		);

		$this->fixture->setConfigurationValue('what_to_display', 'realty_list');
		$this->fixture->setConfigurationValue('checkboxesFilter', 'city');
		$this->fixture->setConfigurationValue('pidList', $systemFolder);

		$this->assertContains(
			'id="tx_realty_pi1_search"',
			$this->fixture->main('', array())
		);
		$this->assertNotContains(
			'###',
			$this->fixture->main('', array())
		);
	}

	public function testListFilterIsVisibleIfCheckboxesFilterIsSetToDistrictAndCitySelectorIsActive() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->firstRealtyUid,
			array('district' => $this->testingFramework->createRecord(
				REALTY_TABLE_DISTRICTS, array('title' => 'test district')
			))
		);
		$this->fixture->setConfigurationValue('what_to_display', 'realty_list');
		$this->fixture->setConfigurationValue('checkboxesFilter', 'district');
		$this->fixture->piVars['city'] = $this->firstCityUid;

		$this->assertContains(
			'id="tx_realty_pi1_search"',
			$this->fixture->main('', array())
		);
	}

	public function testListFilterIsInvisibleIfCheckboxesFilterSetToDistrictAndNoRecordIsLinkedToADistrict() {
		$this->testingFramework->createRecord(
			REALTY_TABLE_DISTRICTS, array('title' => 'test district')
		);
		$this->fixture->setConfigurationValue('what_to_display', 'realty_list');
		$this->fixture->setConfigurationValue('checkboxesFilter', 'district');

		$this->assertNotContains(
			'id="tx_realty_pi1_search"',
			$this->fixture->main('', array())
		);
	}

	public function testListFilterIsInvisibleIfCheckboxesFilterSetToDistrictAndNoDistrictsExists() {
		$this->fixture->setConfigurationValue('what_to_display', 'realty_list');
		$this->fixture->setConfigurationValue('checkboxesFilter', 'district');

		$this->assertNotContains(
			'id="tx_realty_pi1_search"',
			$this->fixture->main('', array())
		);
	}

	public function testListFilterIsVisibleIfCheckboxesFilterSetToCityAndCitySelectorIsInactive() {
		$this->fixture->setConfigurationValue('what_to_display', 'realty_list');
		$this->fixture->setConfigurationValue('checkboxesFilter', 'city');

		$this->assertContains(
			'id="tx_realty_pi1_search"',
			$this->fixture->main('', array())
		);
	}

	public function testListFilterIsInvisibleIfCheckboxesFilterIsSetToCityAndCitySelectorIsActive() {
		$this->fixture->setConfigurationValue('what_to_display', 'realty_list');
		$this->fixture->setConfigurationValue('checkboxesFilter', 'city');
		$this->fixture->piVars['city'] = $this->firstCityUid;

		$this->assertNotContains(
			'id="tx_realty_pi1_search"',
			$this->fixture->main('', array())
		);
	}

	public function testListFilterIsInvisibleIfCheckboxesFilterNotSet() {
		$this->fixture->setConfigurationValue('what_to_display', 'realty_list');

		$this->assertNotContains(
			'id="tx_realty_pi1_search"',
			$this->fixture->main('', array())
		);
	}

	public function testListFilterDoesNotDisplayUnlinkedCity() {
		$this->testingFramework->createRecord(
			REALTY_TABLE_CITIES, array('title' => 'unlinked city')
		);
		$this->fixture->setConfigurationValue('what_to_display', 'realty_list');
		$this->fixture->setConfigurationValue('checkboxesFilter', 'city');

		$this->assertContains(
			'id="tx_realty_pi1_search"',
			$this->fixture->main('', array())
		);
		$this->assertNotContains(
			'unlinked city',
			$this->fixture->main('', array())
		);
	}

	public function testListFilterDoesNotDisplayDeletedCity() {
		$deletedCityUid = $this->testingFramework->createRecord(
			REALTY_TABLE_CITIES, array('title' => 'deleted city', 'deleted' => 1)
		);
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS, $this->firstRealtyUid, array('city' => $deletedCityUid)
		);
		$this->fixture->setConfigurationValue('what_to_display', 'realty_list');
		$this->fixture->setConfigurationValue('checkboxesFilter', 'city');

		$output = $this->fixture->main('', array());
		$this->assertContains(
			'id="tx_realty_pi1_search"',
			$output
		);
		$this->assertNotContains(
			'deleted city',
			$output
		);
	}

	public function testListIsFilteredForOneCriterion() {
		$this->fixture->setConfigurationValue('what_to_display', 'realty_list');
		$this->fixture->setConfigurationValue('checkboxesFilter', 'city');
		$piVars = array('search' => array($this->firstCityUid));

		// The city's title will occur twice if it is within the list view and
		// within the list filter. It will occur once if it is only a filter
		// criterion.
		// piVars would usually be set by each submit of the list filter.
		$this->fixture->piVars = $piVars;

		$output = $this->fixture->main('', array());
		$this->assertEquals(
			2,
			substr_count($output, self::$firstCityTitle)
		);
		$this->assertEquals(
			1,
			substr_count($output, self::$secondCityTitle)
		);
	}

	public function testListIsFilteredForTwoCriteria() {
		$this->fixture->setConfigurationValue('what_to_display', 'realty_list');
		$this->fixture->setConfigurationValue('checkboxesFilter', 'city');
		$piVars = array('search' => array(
			$this->firstCityUid, $this->secondCityUid
		));

		// The city's title will occur twice if it is within the list view and
		// within the list filter. It will occur once if it is only a filter
		// criterion.
		// piVars would usually be set by each submit of the list filter.
		$this->fixture->piVars = $piVars;
		$output = $this->fixture->main('', array());
		$this->assertEquals(
			2,
			substr_count($output, self::$firstCityTitle)
		);
		$this->assertEquals(
			2,
			substr_count($output, self::$secondCityTitle)
		);
	}

	public function testTheListFilterLinksToTheSelfUrl() {
		$this->fixture->setConfigurationValue('what_to_display', 'realty_list');
		$this->fixture->setConfigurationValue('checkboxesFilter', 'city');

		$this->assertContains(
			'?id=' . $GLOBALS['TSFE']->id,
			$this->fixture->main('', array())
		);
	}

	public function testTheListFiltersLinkDoesNoContainPiVars() {
		$this->fixture->setConfigurationValue('what_to_display', 'realty_list');
		$this->fixture->setConfigurationValue('checkboxesFilter', 'city');
		$this->fixture->piVars['search'] = array($this->firstCityUid);

		$this->assertNotContains(
			'tx_realty_pi1[search][0]=' . $this->firstCityUid,
			$this->fixture->main('', array())
		);
	}


	//////////////////////////////////////////////////
	// Tests concerning the sorting in the list view
	//////////////////////////////////////////////////

	public function testCreateListViewShowsValueForOldOrNewBuilding() {
		$this->testingFramework->changeRecord(
			'tx_realty_objects',
			$this->firstRealtyUid,
			array('old_or_new_building' => '1')
		);
		$this->fixture->setConfigurationValue('what_to_display', 'realty_list');

		$this->assertContains(
			$this->fixture->translate('label_old_or_new_building_1'),
			$this->fixture->main('', array())
		);
	}

	public function testListViewIsSortedAscendinglyByObjectNumberWhenNumbersToSortAreIntegers() {
		$this->fixture->setConfigurationValue('what_to_display', 'realty_list');
		$this->fixture->setConfigurationValue(
			'listView.',
			array('orderBy' => 'object_number', 'descFlag' => 0)
		);

		// Links inside the tags might contain numbers which could influence the
		// result. Therefore the tags are stripped.
		$result = strip_tags($this->fixture->main('', array()));
		$this->assertGreaterThan(
			strpos($result, self::$firstObjectNumber),
			strpos($result, self::$secondObjectNumber)
		);
	}

	public function testListViewIsSortedDescendinglyByObjectNumberWhenNumbersToSortAreIntegers() {
		$this->fixture->setConfigurationValue('what_to_display', 'realty_list');
		$this->fixture->setConfigurationValue(
			'listView.',
			array('orderBy' => 'object_number', 'descFlag' => 1)
		);

		// Links inside the tags might contain numbers which could influence the
		// result. Therefore the tags are stripped.
		$result = strip_tags($this->fixture->main('', array()));
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

		$this->fixture->setConfigurationValue('what_to_display', 'realty_list');
		$this->fixture->setConfigurationValue(
			'listView.',
			array('orderBy' => 'object_number', 'descFlag' => 0)
		);

		// Links inside the tags might contain numbers which could influence the
		// result. Therefore the tags are stripped.
		$result = strip_tags($this->fixture->main('', array()));
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

		$this->fixture->setConfigurationValue('what_to_display', 'realty_list');
		$this->fixture->setConfigurationValue(
			'listView.',
			array('orderBy' => 'object_number', 'descFlag' => 1)
		);

		// Links inside the tags might contain numbers which could influence the
		// result. Therefore the tags are stripped.
		$result = strip_tags($this->fixture->main('', array()));
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

		$this->fixture->setConfigurationValue('what_to_display', 'realty_list');
		$this->fixture->setConfigurationValue(
			'listView.',
			array('orderBy' => 'object_number', 'descFlag' => 0)
		);

		// Links inside the tags might contain numbers which could influence the
		// result. Therefore the tags are stripped.
		$result = strip_tags($this->fixture->main('', array()));
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

		$this->fixture->setConfigurationValue('what_to_display', 'realty_list');
		$this->fixture->setConfigurationValue(
			'listView.',
			array('orderBy' => 'object_number', 'descFlag' => 1)
		);

		// Links inside the tags might contain numbers which could influence the
		// result. Therefore the tags are stripped.
		$result = strip_tags($this->fixture->main('', array()));
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

		$this->fixture->setConfigurationValue('what_to_display', 'realty_list');
		$this->fixture->setConfigurationValue(
			'listView.',
			array('orderBy' => 'object_number', 'descFlag' => 0)
		);

		// Links inside the tags might contain numbers which could influence the
		// result. Therefore the tags are stripped.
		$result = strip_tags($this->fixture->main('', array()));
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

		$this->fixture->setConfigurationValue('what_to_display', 'realty_list');
		$this->fixture->setConfigurationValue(
			'listView.',
			array('orderBy' => 'object_number', 'descFlag' => 1)
		);

		// Links inside the tags might contain numbers which could influence the
		// result. Therefore the tags are stripped.
		$result = strip_tags($this->fixture->main('', array()));
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

		$this->fixture->setConfigurationValue('what_to_display', 'realty_list');
		$this->fixture->setConfigurationValue(
			'listView.',
			array('orderBy' => 'object_number', 'descFlag' => 0)
		);

		// Links inside the tags might contain numbers which could influence the
		// result. Therefore the tags are stripped.
		$result = strip_tags($this->fixture->main('', array()));
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

		$this->fixture->setConfigurationValue('what_to_display', 'realty_list');
		$this->fixture->setConfigurationValue(
			'listView.',
			array('orderBy' => 'object_number', 'descFlag' => 1)
		);

		// Links inside the tags might contain numbers which could influence the
		// result. Therefore the tags are stripped.
		$result = strip_tags($this->fixture->main('', array()));
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

		$this->fixture->setConfigurationValue('what_to_display', 'realty_list');
		$this->fixture->setConfigurationValue(
			'listView.',
			array('orderBy' => 'buying_price', 'descFlag' => 0)
		);

		// Links inside the tags might contain numbers which could influence the
		// result. Therefore the tags are stripped.
		$result = strip_tags($this->fixture->main('', array()));
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

		$this->fixture->setConfigurationValue('what_to_display', 'realty_list');
		$this->fixture->setConfigurationValue(
			'listView.',
			array('orderBy' => 'rent_excluding_bills', 'descFlag' => 0)
		);

		// Links inside the tags might contain numbers which could influence the
		// result. Therefore the tags are stripped.
		$result = strip_tags($this->fixture->main('', array()));
		$this->assertGreaterThan(
			strpos($result, '9'),
			strpos($result, '11')
		);
	}

	public function testListViewIsSortedAscendinglyByNumberOfRooms() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->firstRealtyUid,
			array('number_of_rooms' => '9')
		);
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->secondRealtyUid,
			array('number_of_rooms' => '11')
		);

		$this->fixture->setConfigurationValue('what_to_display', 'realty_list');
		$this->fixture->setConfigurationValue(
			'listView.',
			array('orderBy' => 'number_of_rooms', 'descFlag' => 0)
		);

		// Links inside the tags might contain numbers which could influence the
		// result. Therefore the tags are stripped.
		$result = strip_tags($this->fixture->main('', array()));
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

		$this->fixture->setConfigurationValue('what_to_display', 'realty_list');
		$this->fixture->setConfigurationValue(
			'listView.',
			array('orderBy' => 'living_area', 'descFlag' => 0)
		);

		// Links inside the tags might contain numbers which could influence the
		// result. Therefore the tags are stripped.
		$result = strip_tags($this->fixture->main('', array()));
		$this->assertGreaterThan(
			strpos($result, '9'),
			strpos($result, '11')
		);
	}

	public function testListViewIsSortedAscendinglyByTheCitiesTitles() {
		$this->fixture->setConfigurationValue('what_to_display', 'realty_list');
		$this->fixture->setConfigurationValue(
			'listView.',
			array('orderBy' => 'city', 'descFlag' => 0)
		);

		// The result would be inverted if cities are sorted by their UID because
		// the following can be asserted:
		$this->assertTrue(
			$this->firstCityUid < $this->secondCityUid
		);

		// Links inside the tags might contain numbers which could influence the
		// result. Therefore the tags are stripped.
		$result = strip_tags($this->fixture->main('', array()));
		$this->assertGreaterThan(
			strpos($result, self::$secondCityTitle),
			strpos($result, self::$firstCityTitle)
		);
	}

	public function testListViewIsSortedDescendinglyByTheCitiesTitles() {
		$this->fixture->setConfigurationValue('what_to_display', 'realty_list');
		$this->fixture->setConfigurationValue(
			'listView.',
			array('orderBy' => 'city', 'descFlag' => 1)
		);

		// The result would be inverted if cities are sorted by their UID because
		// the following can be asserted:
		$this->assertTrue(
			$this->firstCityUid < $this->secondCityUid
		);

		// Links inside the tags might contain numbers which could influence the
		// result. Therefore the tags are stripped.
		$result = strip_tags($this->fixture->main('', array()));
		$this->assertGreaterThan(
			strpos($result, self::$firstCityTitle),
			strpos($result, self::$secondCityTitle)
		);
	}

	public function testListViewIsSortedByUidIfAnInvalidSortCriterionWasSet() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->firstRealtyUid,
			array('street' => '11')
		);
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->secondRealtyUid,
			array('street' => '9')
		);

		$this->fixture->setConfigurationValue('what_to_display', 'realty_list');
		$this->fixture->setConfigurationValue(
			'listView.',
			array('orderBy' => 'street', 'descFlag' => 1)
		);

		// Links inside the tags might contain numbers which could influence the
		// result. Therefore the tags are stripped.
		$result = strip_tags($this->fixture->main('', array()));
		$this->assertGreaterThan(
			strpos($result, self::$firstCityTitle),
			strpos($result, self::$secondCityTitle)
		);
	}


	/////////////////////////////////////////////////////
	// Tests concerning the summary string of favorites
	/////////////////////////////////////////////////////

	public function testCreateSummaryStringOfFavoritesContainsDataFromOneObject() {
		$this->fixture->addToFavorites(array($this->firstRealtyUid));

		$this->assertContains(
			'* '.self::$firstObjectNumber.' '.self::$firstObjectTitle.LF,
			$this->fixture->createSummaryStringOfFavorites()
		);
		$this->assertNotContains(
			'* '.self::$secondObjectNumber.' '.self::$secondObjectTitle.LF,
			$this->fixture->createSummaryStringOfFavorites()
		);
	}

	public function testCreateSummaryStringOfFavoritesContainsDataFromTwoObjects() {
		$this->fixture->addToFavorites(array($this->firstRealtyUid));
		$this->fixture->addToFavorites(array($this->secondRealtyUid));

		$this->assertContains(
			'* '.self::$firstObjectNumber.' '.self::$firstObjectTitle.LF,
			$this->fixture->createSummaryStringOfFavorites()
		);
		$this->assertContains(
			'* '.self::$secondObjectNumber.' '.self::$secondObjectTitle.LF,
			$this->fixture->createSummaryStringOfFavorites()
		);
	}

	public function testCreateSummaryStringOfFavoritesIsEmptyWithoutData() {
		$this->assertEquals(
			'',
			$this->fixture->createSummaryStringOfFavorites()
		);
	}

	public function testWriteSummaryStringOfFavoritesToDatabase() {
		$this->fixture->addToFavorites(array($this->firstRealtyUid));
		$this->fixture->writeSummaryStringOfFavoritesToSession();
		$sessionData = $GLOBALS['TSFE']->fe_user->getKey(
				'ses',
				'summaryStringOfFavorites'
		);
		$this->assertContains(
			'* '.self::$firstObjectNumber.' '.self::$firstObjectTitle.LF,
			$sessionData
		);
	}

	public function testWriteSummaryStringOfFavoritesToDatabaseIfFeUserIsLoggedIn() {
		$feUserId = $this->testingFramework->createFrontEndUser(
			$this->testingFramework->createFrontEndUserGroup()
		);
		$this->testingFramework->loginFrontEndUser($feUserId);

		$this->fixture->addToFavorites(array($this->firstRealtyUid));
		$this->fixture->writeSummaryStringOfFavoritesToSession();
		$sessionData = $GLOBALS['TSFE']->fe_user->getKey(
			'ses',
			'summaryStringOfFavorites'
		);
		$this->assertContains(
			'* '.self::$firstObjectNumber.' '.self::$firstObjectTitle.LF,
			$sessionData
		);
	}


	////////////////////////////////////////
	// Tests concerning the favorites list
	////////////////////////////////////////

	public function testFavoritesViewContainsObjectWhichWasAddedToTheFavorites() {
		$this->fixture->addToFavorites(array($this->firstRealtyUid));
		$this->fixture->setConfigurationValue('what_to_display', 'favorites');

		$this->assertContains(
			self::$firstObjectTitle,
			$this->fixture->main('', array())
		);
	}

	public function testFavoritesViewHasNoUnreplacedMarkersForEmptyRenderedObject() {
		$systemFolder = $this->testingFramework->createSystemFolder();
		$this->fixture->addToFavorites(array($this->testingFramework->createRecord(
			REALTY_TABLE_OBJECTS,
			array(
				// A city is the minimum requirement for an object to be displayed,
				// though the object is rendered empty because the city has no title.
				'city' => $this->testingFramework->createRecord(REALTY_TABLE_CITIES),
				'pid' => $systemFolder,
			)
		)));

		$this->fixture->setConfigurationValue('what_to_display', 'favorites');
		$this->fixture->setConfigurationValue('pidList', $systemFolder);

		$this->assertNotContains(
			'###',
			$this->fixture->main('', array())
		);
	}


	///////////////////////////////////////
	// Tests concerning the contact link.
	///////////////////////////////////////

	public function testContactLinkIsDisplayedInTheDetailViewIfDirectRequestsAreAllowedAndTheContactPidIsSet() {
		$this->fixture->setConfigurationValue('what_to_display', 'realty_list');
		$this->fixture->setConfigurationValue('allowDirectRequestsForObjects', 1);
		$this->fixture->setConfigurationValue('contactPID', $this->otherSinglePid);
		$this->fixture->piVars['showUid'] = $this->secondRealtyUid;
		$result = $this->fixture->main('', array());

		$this->assertContains(
			'?id=' . $this->otherSinglePid,
			$result
		);
		$this->assertContains(
			'class="button contact"',
			$result
		);
	}

	public function testContactLinkIsNotDisplayedInTheDetailViewIfDirectRequestsAreNotAllowedAndTheContactPidIsSet() {
		$this->fixture->setConfigurationValue('what_to_display', 'realty_list');
		$this->fixture->setConfigurationValue('allowDirectRequestsForObjects', 0);
		$this->fixture->setConfigurationValue('contactPID', $this->otherSinglePid);
		$this->fixture->piVars['showUid'] = $this->secondRealtyUid;

		$this->assertNotContains(
			'class="button contact"',
			$this->fixture->main('', array())
		);
	}

	public function testContactLinkIsNotDisplayedInTheDetailViewIfDirectRequestsAreAllowedAndTheContactPidIsNotSet() {
		$this->fixture->setConfigurationValue('what_to_display', 'realty_list');
		$this->fixture->setConfigurationValue('allowDirectRequestsForObjects', 1);
		$this->fixture->setConfigurationValue('contactPID', '');
		$this->fixture->piVars['showUid'] = $this->secondRealtyUid;

		$this->assertNotContains(
			'class="button contact"',
			$this->fixture->main('', array())
		);
	}

	public function testContactLinkIsDisplayedInTheFavoritesViewIfDirectRequestsAreNotAllowedAndTheContactPidIsSet() {
		$this->fixture->setConfigurationValue('what_to_display', 'favorites');
		$this->fixture->setConfigurationValue('allowDirectRequestsForObjects', 0);
		$this->fixture->setConfigurationValue('contactPID', $this->otherSinglePid);
		$result = $this->fixture->main('', array());

		$this->assertContains(
			'?id=' . $this->otherSinglePid,
			$result
		);
		$this->assertContains(
			'class="button contact"',
			$result
		);
	}

	public function testContactLinkIsNotDisplayedInTheInTheFavoritesViewIfDirectRequestsAreAllowedAndTheContactPidIsSet() {
		$this->fixture->setConfigurationValue('what_to_display', 'favorites');
		$this->fixture->setConfigurationValue('allowDirectRequestsForObjects', 1);
		$this->fixture->setConfigurationValue('contactPID', $this->otherSinglePid);

		$this->assertNotContains(
			'class="button contact"',
			$this->fixture->main('', array())
		);
	}

	public function testContactLinkIsNotDisplayedInTheFavoritesViewIfDirectRequestsAreNotAllowedAndTheContactPidIsNotSet() {
		$this->fixture->setConfigurationValue('what_to_display', 'favorites');
		$this->fixture->setConfigurationValue('allowDirectRequestsForObjects', 0);
		$this->fixture->setConfigurationValue('contactPID', '');

		$this->assertNotContains(
			'class="button contact"',
			$this->fixture->main('', array())
		);
	}

	public function testContactLinkIsDisplayedInTheDetailViewAndContainsTheObjectUid() {
		$this->fixture->setConfigurationValue('what_to_display', 'realty_list');
		$this->fixture->setConfigurationValue('allowDirectRequestsForObjects', 1);
		$this->fixture->setConfigurationValue('contactPID', $this->otherSinglePid);
		$this->fixture->piVars['showUid'] = $this->secondRealtyUid;
		$result = $this->fixture->main('', array());
		$this->assertContains(
			'class="button contact"',
			$result
		);
		$this->assertContains(
			'?id=' . $this->otherSinglePid,
			$result
		);
		$this->assertContains(
			'tx_realty_pi1[showUid]='.$this->secondRealtyUid,
			$result
		);
	}

	public function testContactLinkIsNotDisplayedInTheDetailViewIfTheContactFormHasTheSamePid() {
		$this->fixture->setConfigurationValue('what_to_display', 'realty_list');
		$this->fixture->setConfigurationValue('allowDirectRequestsForObjects', 1);
		$this->fixture->setConfigurationValue('contactPID', $this->singlePid);
		$this->fixture->piVars['showUid'] = $this->secondRealtyUid;

		$this->assertNotContains(
			'class="button contact"',
			$this->fixture->main('', array())
		);
	}

	public function testContactLinkIsNotDisplayedInTheFavoritesViewIfTheContactFormHasTheSamePid() {
		$this->fixture->setConfigurationValue('what_to_display', 'favorites');
		$this->fixture->setConfigurationValue('allowDirectRequestsForObjects', 0);
		$this->fixture->setConfigurationValue('contactPID', $this->favoritesPid);

		$this->assertNotContains(
			'class="button contact"',
			$this->fixture->main('', array())
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


	/////////////////////////////////////
	// Tests concerning the single view
	/////////////////////////////////////

	public function testDetailPageContainsContactInformationWithPhoneNumberFromRecord() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->firstRealtyUid,
			array('contact_phone' => '12345')
		);
		$this->fixture->setConfigurationValue('showContactInformation', true);
		$this->fixture->setConfigurationValue('what_to_display', 'single_view');
		$this->fixture->piVars['showUid'] = $this->firstRealtyUid;

		$this->assertContains(
			$this->fixture->translate('label_offerer'),
			$this->fixture->main('', array())
		);
		$this->assertContains(
			'12345',
			$this->fixture->main('', array())
		);
	}

	public function testDetailPageContainsContactInformationWithCompanyFromRecord() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->firstRealtyUid,
			array('employer' => 'test company')
		);
		$this->fixture->setConfigurationValue('showContactInformation', true);
		$this->fixture->setConfigurationValue('what_to_display', 'single_view');
		$this->fixture->piVars['showUid'] = $this->firstRealtyUid;

		$this->assertContains(
			$this->fixture->translate('label_offerer'),
			$this->fixture->main('', array())
		);
		$this->assertContains(
			'test company',
			$this->fixture->main('', array())
		);
	}

	public function testDetailPageContainsContactInformationWithPhoneNumberFromOwner() {
		$ownerUid = $this->testingFramework->createFrontEndUser(
			$this->testingFramework->createFrontEndUserGroup(),
			array('telephone' => '123123')
		);
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->firstRealtyUid,
			array(
				'contact_data_source' => REALTY_CONTACT_FROM_OWNER_ACCOUNT,
				'owner' => $ownerUid
			)
		);
		$this->fixture->setConfigurationValue('showContactInformation', true);
		$this->fixture->setConfigurationValue('what_to_display', 'single_view');
		$this->fixture->piVars['showUid'] = $this->firstRealtyUid;

		$this->assertContains(
			$this->fixture->translate('label_offerer'),
			$this->fixture->main('', array())
		);
		$this->assertContains(
			'123123',
			$this->fixture->main('', array())
		);
	}

	public function testDetailPageContainsContactInformationWithCompanyFromOwner() {
		$ownerUid = $this->testingFramework->createFrontEndUser(
			$this->testingFramework->createFrontEndUserGroup(),
			array('company' => 'any company')
		);
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->firstRealtyUid,
			array(
				'contact_data_source' => REALTY_CONTACT_FROM_OWNER_ACCOUNT,
				'owner' => $ownerUid
			)
		);
		$this->fixture->setConfigurationValue('showContactInformation', true);
		$this->fixture->setConfigurationValue('what_to_display', 'single_view');
		$this->fixture->piVars['showUid'] = $this->firstRealtyUid;

		$this->assertContains(
			$this->fixture->translate('label_offerer'),
			$this->fixture->main('', array())
		);
		$this->assertContains(
			'any company',
			$this->fixture->main('', array())
		);
	}

	public function testDetailPageNotContainsContactInformationIfOptionIsDisabled() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->firstRealtyUid,
			array(
				'employer' => 'test company',
				'contact_phone' => '12345'
			)
		);
		$this->fixture->setConfigurationValue('showContactInformation', false);
		$this->fixture->setConfigurationValue('what_to_display', 'single_view');
		$this->fixture->piVars['showUid'] = $this->firstRealtyUid;

		$this->assertNotContains(
			$this->fixture->translate('label_offerer'),
			$this->fixture->main('', array())
		);
		$this->assertNotContains(
			'test company',
			$this->fixture->main('', array())
		);
		$this->assertNotContains(
			'12345',
			$this->fixture->main('', array())
		);
	}

	public function testDetailPageNotContainsContactInformationIfNoContactInformationAvailable() {
		$this->fixture->setConfigurationValue('showContactInformation', true);
		$this->fixture->setConfigurationValue('what_to_display', 'single_view');
		$this->fixture->piVars['showUid'] = $this->firstRealtyUid;

		$this->assertNotContains(
			$this->fixture->translate('label_offerer'),
			$this->fixture->main('', array())
		);
	}

	public function testDetailPageNotContainsContactInformationForEnabledOptionAndDeletedOwner() {
		$ownerUid = $this->testingFramework->createFrontEndUser(
			$this->testingFramework->createFrontEndUserGroup(),
			array('company' => 'any company', 'deleted' => 1)
		);
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->firstRealtyUid,
			array(
				'contact_data_source' => REALTY_CONTACT_FROM_OWNER_ACCOUNT,
				'owner' => $ownerUid
			)
		);
		$this->fixture->setConfigurationValue('showContactInformation', true);
		$this->fixture->setConfigurationValue('what_to_display', 'single_view');
		$this->fixture->piVars['showUid'] = $this->firstRealtyUid;

		$this->assertNotContains(
			$this->fixture->translate('label_offerer'),
			$this->fixture->main('', array())
		);
	}

	public function testDetailPageNotContainsContactInformationForEnabledOptionAndOwnerWithoutData() {
		$ownerUid = $this->testingFramework->createFrontEndUser(
			$this->testingFramework->createFrontEndUserGroup(), array()
		);
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->firstRealtyUid,
			array(
				'contact_data_source' => REALTY_CONTACT_FROM_OWNER_ACCOUNT,
				'owner' => $ownerUid
			)
		);
		$this->fixture->setConfigurationValue('showContactInformation', true);
		$this->fixture->setConfigurationValue('what_to_display', 'single_view');
		$this->fixture->piVars['showUid'] = $this->firstRealtyUid;

		$this->assertNotContains(
			$this->fixture->translate('label_offerer'),
			$this->fixture->main('', array())
		);
	}

	public function testSingleViewFormatsPriceUsingSpaceAsThousandsSeparator() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->firstRealtyUid,
			array('buying_price' => '1234567', 'object_type' => REALTY_FOR_SALE)
		);
		$this->fixture->piVars['showUid'] = $this->firstRealtyUid;
		$this->fixture->setConfigurationValue(
			'fieldsInSingleViewTable', 'buying_price'
		);
		$this->fixture->setConfigurationValue('what_to_display', 'single_view');

		$this->assertContains(
			'1 234 567',
			$this->fixture->main('', array())
		);
	}

	public function testSingleViewHasLinkedImage() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->firstRealtyUid,
			array('images' => '1')
		);
		$this->testingFramework->createRecord(
			REALTY_TABLE_IMAGES,
			array(
				'realty_object_uid' => $this->firstRealtyUid,
				'image' => 'foo.jpg',
				'caption' => 'foo',
			)
		);

		$this->fixture->setConfigurationValue(
			'galleryPID', $this->testingFramework->createFrontEndPage()
		);
		$this->fixture->setConfigurationValue('what_to_display', 'single_view');
		$this->fixture->piVars['showUid'] = $this->firstRealtyUid;

		$this->assertContains(
			'tx_realty_pi1[image]=0',
			$this->fixture->main('', array())
		);
	}

	public function testSingleViewHasLinkedImageWithGalleryPid() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->firstRealtyUid,
			array('images' => '1')
		);
		$this->testingFramework->createRecord(
			REALTY_TABLE_IMAGES,
			array(
				'realty_object_uid' => $this->firstRealtyUid,
				'image' => 'foo.jpg',
				'caption' => 'foo',
			)
		);
		$galleryPid = $this->testingFramework->createFrontEndPage();

		$this->fixture->setConfigurationValue('galleryPID', $galleryPid);
		$this->fixture->setConfigurationValue('what_to_display', 'single_view');
		$this->fixture->piVars['showUid'] = $this->firstRealtyUid;

		$this->assertContains(
			'?id=' . $galleryPid,
			$this->fixture->main('', array())
		);
	}

	public function testSingleViewHasLinkedImageWithCacheHashInTheLink() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->firstRealtyUid,
			array('images' => '1')
		);
		$this->testingFramework->createRecord(
			REALTY_TABLE_IMAGES,
			array(
				'realty_object_uid' => $this->firstRealtyUid,
				'image' => 'foo.jpg',
				'caption' => 'foo',
			)
		);

		$this->fixture->setConfigurationValue(
			'galleryPID', $this->testingFramework->createFrontEndPage()
		);
		$this->fixture->setConfigurationValue('what_to_display', 'single_view');
		$this->fixture->piVars['showUid'] = $this->firstRealtyUid;

		$this->assertContains(
			'cHash=',
			$this->fixture->main('', array())
		);
	}

	public function testSingleViewHasNoUnreplacedMarkersForMinimalContent() {
		$systemFolder = $this->testingFramework->createSystemFolder();
		$this->fixture->piVars['showUid'] = $this->testingFramework->createRecord(
			REALTY_TABLE_OBJECTS,
			array(
				// A city is the minimum requirement for an object to be displayed,
				// though the object is rendered empty because the city has no title.
				'city' => $this->testingFramework->createRecord(REALTY_TABLE_CITIES),
				'pid' => $systemFolder
			)
		);

		$this->fixture->setConfigurationValue('what_to_display', 'single_view');
		$this->fixture->setConfigurationValue('pidList', $systemFolder);

		$this->assertNotContains(
			'###',
			$this->fixture->main('', array())
		);
	}


	/////////////////////////////////////
	// Tests concerning getFieldContent
	/////////////////////////////////////

	public function testGetFieldContentOfEstateSize() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->firstRealtyUid,
			array('estate_size' => '12345')
		);
		$this->fixture->setConfigurationValue('what_to_display', 'single_view');
		$this->fixture->piVars['showUid'] = $this->firstRealtyUid;
		// $this->createListView() is called indirectly here. It sets the correct
		// values for $this->internal.
		$this->fixture->main('', array());

		$this->assertContains(
			'12 345',
			$this->fixture->getFieldContent('estate_size')
		);
	}

	public function testGetFieldContentCreatesLinkToSinglePageIfAccessDenied() {
		$this->denyAccess();

		$this->fixture->setConfigurationValue('loginPID', $this->loginPid);
		$this->fixture->setCurrentRow(array(
			'title' => 'foo',
			'uid' => 0
		));

		$this->assertEquals(
			$this->fixture->createLinkToSingleViewPage('foo', 0),
			$this->fixture->getFieldContent('linked_title')
		);
	}

	public function testGetFieldContentCreatesLinkToSinglePageIfAccessAllowed() {
		$this->allowAccess();

		$this->fixture->setCurrentRow(array(
			'title' => 'foo',
			'uid' => 0
		));

		$this->assertEquals(
			$this->fixture->createLinkToSingleViewPage('foo', 0),
			$this->fixture->getFieldContent('linked_title')
		);
	}


	public function testDetailPageDisplaysTheStreetIfShowAddressOfObjectsIsEnabled() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->firstRealtyUid,
			array('street' => 'Foo road 3', 'show_address' => 1)
		);
		$this->fixture->setConfigurationValue('what_to_display', 'single_view');
		$this->fixture->piVars['showUid'] = $this->firstRealtyUid;

		$this->assertContains(
			'Foo road 3',
			$this->fixture->main('', array())
		);
	}

	public function testDetailPageOmitsTheStreetIfShowAddressOfObjectsIsDisabled() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->firstRealtyUid,
			array('street' => 'Foo road 3', 'show_address' => 0)
		);
		$this->fixture->setConfigurationValue('what_to_display', 'single_view');
		$this->fixture->piVars['showUid'] = $this->firstRealtyUid;

		$this->assertNotContains(
			'Foo road 3',
			$this->fixture->main('', array())
		);
	}

	public function testDetailPageDisplaysTheZipIfShowAddressOfObjectsIsEnabled() {
			$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->firstRealtyUid,
			array('zip' => '12345')
		);
		$this->fixture->setConfigurationValue('showAddressOfObjects', 1);
		$this->fixture->setConfigurationValue('what_to_display', 'single_view');
		$this->fixture->piVars['showUid'] = $this->firstRealtyUid;

		$this->assertContains(
			'12345',
			$this->fixture->main('', array())
		);
	}

	public function testDetailPageDisplaysTheZipIfShowAddressOfObjectsIsDisabled() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->firstRealtyUid,
			array('zip' => '12345', 'show_address' => 0)
		);
		$this->fixture->setConfigurationValue('what_to_display', 'single_view');
		$this->fixture->piVars['showUid'] = $this->firstRealtyUid;

		$this->assertContains(
			'12345',
			$this->fixture->main('', array())
		);
	}

	public function testDetailPageDisplaysTheLabelForStateIfAValidStateIsSet() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->firstRealtyUid,
			array('state' => 8)
		);
		$this->fixture->setConfigurationValue('what_to_display', 'single_view');
		$this->fixture->setConfigurationValue('fieldsInSingleViewTable', 'state');
		$this->fixture->piVars['showUid'] = $this->firstRealtyUid;

		$this->assertContains(
			$this->fixture->translate('label_state'),
			$this->fixture->main('', array())
		);
	}

	public function testDetailPageDisplaysTheObjectsStateIfItIsValid() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->firstRealtyUid,
			array('state' => 8)
		);
		$this->fixture->setConfigurationValue('what_to_display', 'single_view');
		$this->fixture->setConfigurationValue('fieldsInSingleViewTable', 'state');
		$this->fixture->piVars['showUid'] = $this->firstRealtyUid;

		$this->assertContains(
			$this->fixture->translate('label_state.8'),
			$this->fixture->main('', array())
		);
	}

	public function testDetailPageNotDisplaysTheLabelForStateIfTheStateIsNotSet() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->firstRealtyUid,
			array('state' => 0)
		);
		$this->fixture->setConfigurationValue('what_to_display', 'single_view');
		$this->fixture->setConfigurationValue('fieldsInSingleViewTable', 'state');
		$this->fixture->piVars['showUid'] = $this->firstRealtyUid;

		$this->assertNotContains(
			$this->fixture->translate('label_state'),
			$this->fixture->main('', array())
		);
	}

	public function testDetailPageNotDisplaysTheLabelForStateIfItIsInvalid() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->firstRealtyUid,
			array('state' => 1000000)
		);
		$this->fixture->setConfigurationValue('what_to_display', 'single_view');
		$this->fixture->setConfigurationValue('fieldsInSingleViewTable', 'state');
		$this->fixture->piVars['showUid'] = $this->firstRealtyUid;

		$this->assertNotContains(
			$this->fixture->translate('label_state'),
			$this->fixture->main('', array())
		);
	}

	public function testDetailPageDisplaysTheLabelForHeatingTypeIfOneValidHeatingTypeIsSet() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->firstRealtyUid,
			array('heating_type' => '1')
		);
		$this->fixture->setConfigurationValue('what_to_display', 'single_view');
		$this->fixture->setConfigurationValue('fieldsInSingleViewTable', 'heating_type');
		$this->fixture->piVars['showUid'] = $this->firstRealtyUid;

		$this->assertContains(
			$this->fixture->translate('label_heating_type'),
			$this->fixture->main('', array())
		);
	}

	public function testDetailPageDisplaysTheHeatingTypeIfOneValidHeatingTypeIsSet() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->firstRealtyUid,
			array('heating_type' => '1')
		);
		$this->fixture->setConfigurationValue('what_to_display', 'single_view');
		$this->fixture->setConfigurationValue('fieldsInSingleViewTable', 'heating_type');
		$this->fixture->piVars['showUid'] = $this->firstRealtyUid;

		$this->assertContains(
			$this->fixture->translate('label_heating_type.1'),
			$this->fixture->main('', array())
		);
	}

	public function testDetailPageDisplaysAHeatingTypeListIfMultipleValidHeatingTypesAreSet() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->firstRealtyUid,
			array('heating_type' => '1,3,4')
		);
		$this->fixture->setConfigurationValue('what_to_display', 'single_view');
		$this->fixture->setConfigurationValue('fieldsInSingleViewTable', 'heating_type');
		$this->fixture->piVars['showUid'] = $this->firstRealtyUid;

		$this->assertContains(
			$this->fixture->translate('label_heating_type.1') . ', ' .
				$this->fixture->translate('label_heating_type.3') . ', ' .
				$this->fixture->translate('label_heating_type.4'),
			$this->fixture->main('', array())
		);
	}

	public function testDetailPageDoesNotDisplayTheHeatingLabelTypeIfOnlyAnInvalidHeatingTypeIsSet() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->firstRealtyUid,
			array('heating_type' => '100')
		);
		$this->fixture->setConfigurationValue('what_to_display', 'single_view');
		$this->fixture->setConfigurationValue('fieldsInSingleViewTable', 'heating_type');
		$this->fixture->piVars['showUid'] = $this->firstRealtyUid;

		$this->assertNotContains(
			$this->fixture->translate('label_heating_type'),
			$this->fixture->main('', array())
		);
	}

	public function testDetailViewDisplaysHiddenObjectForLoggedInOwner() {
		$feUserUid = $this->testingFramework->createFrontEndUser(
			$this->testingFramework->createFrontEndUserGroup()
		);
		$this->testingFramework->loginFrontEndUser($feUserUid);
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->firstRealtyUid,
			array('owner' => $feUserUid, 'hidden' => 1)
		);
		$this->fixture->setConfigurationValue('what_to_display', 'single_view');
		$this->fixture->piVars['showUid'] = $this->firstRealtyUid;

		$this->assertContains(
			self::$firstObjectTitle,
			$this->fixture->main('', array())
		);
	}

	public function testDetailViewNotDisplaysHiddenObjectForNonOwner() {
		$feUserUid = $this->testingFramework->createFrontEndUser(
			$this->testingFramework->createFrontEndUserGroup()
		);
		$this->testingFramework->loginFrontEndUser($feUserUid);
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->firstRealtyUid,
			array('owner' => ($feUserUid + 1), 'hidden' => 1)
		);
		$this->fixture->setConfigurationValue('what_to_display', 'single_view');
		$this->fixture->piVars['showUid'] = $this->firstRealtyUid;

		$this->assertNotContains(
			self::$firstObjectTitle,
			$this->fixture->main('', array())
		);
	}

	public function testDetailViewNotDisplaysHiddenObjectWithoutOwnerForNotLoggedInUser() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->firstRealtyUid,
			array('owner' => 0, 'hidden' => 1)
		);
		$this->fixture->setConfigurationValue('what_to_display', 'single_view');
		$this->fixture->piVars['showUid'] = $this->firstRealtyUid;

		$this->assertNotContains(
			self::$firstObjectTitle,
			$this->fixture->main('', array())
		);
	}

	public function testDetailViewDisplaysVisibleObjectForLoggedInNonOwner() {
		$feUserUid = $this->testingFramework->createFrontEndUser(
			$this->testingFramework->createFrontEndUserGroup()
		);
		$this->testingFramework->loginFrontEndUser($feUserUid);
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->firstRealtyUid,
			array('owner' => ($feUserUid + 1))
		);
		$this->fixture->setConfigurationValue('what_to_display', 'single_view');
		$this->fixture->piVars['showUid'] = $this->firstRealtyUid;

		$this->assertContains(
			self::$firstObjectTitle,
			$this->fixture->main('', array())
		);
	}

	public function testDetailViewDisplaysVisibleObjectWithoutOwnerForLoggedInUser() {
		$this->testingFramework->loginFrontEndUser(
			$this->testingFramework->createFrontEndUser(
				$this->testingFramework->createFrontEndUserGroup()
			)
		);
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->firstRealtyUid,
			array('owner' => 0)
		);
		$this->fixture->setConfigurationValue('what_to_display', 'single_view');
		$this->fixture->piVars['showUid'] = $this->firstRealtyUid;

		$this->assertContains(
			self::$firstObjectTitle,
			$this->fixture->main('', array())
		);
	}

	public function testDetailViewDisplaysErrorMessageForNonExistentObject() {
		$this->fixture->setConfigurationValue('what_to_display', 'single_view');
		$this->fixture->piVars['showUid'] = $this->testingFramework->createRecord(
			REALTY_TABLE_OBJECTS, array('deleted' => 1)
		);

		$this->assertContains(
			$this->fixture->translate('message_noResultsFound_single_view'),
			$this->fixture->main('', array())
		);
	}

	public function testDetailViewDisplaysErrorMessageForHiddenObject() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS, $this->firstRealtyUid, array('hidden' => 1)
		);
		$this->fixture->setConfigurationValue('what_to_display', 'single_view');
		$this->fixture->piVars['showUid'] = $this->firstRealtyUid;

		$this->assertContains(
			$this->fixture->translate('message_noResultsFound_single_view'),
			$this->fixture->main('', array())
		);
	}

	public function testDetailViewDisplaysErrorMessageForDeletedObject() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS, $this->firstRealtyUid, array('deleted' => 1)
		);
		$this->fixture->setConfigurationValue('what_to_display', 'single_view');
		$this->fixture->piVars['showUid'] = $this->firstRealtyUid;

		$this->assertContains(
			$this->fixture->translate('message_noResultsFound_single_view'),
			$this->fixture->main('', array())
		);
	}

	public function testDetailViewDisplaysErrorMessageForLoggedInUserWhenObjectIsHiddenByForeignUser() {
		$this->testingFramework->loginFrontEndUser(
			$this->testingFramework->createFrontEndUser(
				$this->testingFramework->createFrontEndUserGroup()
			)
		);
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->firstRealtyUid,
			array(
				'hidden' => 1,
				'owner' =>$this->testingFramework->createFrontEndUser(
					$this->testingFramework->createFrontEndUserGroup()
				)
			)
		);
		$this->fixture->setConfigurationValue('what_to_display', 'single_view');
		$this->fixture->piVars['showUid'] = $this->firstRealtyUid;

		$this->assertContains(
			$this->fixture->translate('message_noResultsFound_single_view'),
			$this->fixture->main('', array())
		);
	}

	public function testHeaderIsSetIfDetailViewDisplaysNoResultsMessage() {
		$this->fixture->setConfigurationValue('what_to_display', 'single_view');
		$this->fixture->piVars['showUid'] = $this->testingFramework->createRecord(
			REALTY_TABLE_OBJECTS, array('deleted' => 1)
		);
		$this->fixture->main('', array());

		$this->assertEquals(
			'Status: 404 Not Found',
			tx_oelib_headerProxyFactory::getInstance()->getHeaderProxy()->getLastAddedHeader()
		);
	}

	public function testHeaderIsSetIfDetailViewDisplaysAccessDeniedMessage() {
		$this->fixture->setConfigurationValue('what_to_display', 'single_view');
		$this->fixture->piVars['showUid'] = $this->firstRealtyUid;
		$this->denyAccess();
		$this->fixture->main('', array());

		$this->assertEquals(
			'Status: 403 Forbidden',
			tx_oelib_headerProxyFactory::getInstance()->getHeaderProxy()->getLastAddedHeader()
		);
	}


	/////////////////////////////////////////////////////
	// Tests concerning links to external details pages
	/////////////////////////////////////////////////////

	public function testLinkToExternalSingleViewPageContainsExternalUrlIfAccessAllowed() {
		$this->allowAccess();
		$this->assertContains(
			'http://'.TX_REALTY_EXTERNAL_SINGLE_PAGE,
			$this->fixture->createLinkToSingleViewPage(
				'foo', 0, TX_REALTY_EXTERNAL_SINGLE_PAGE
			)
		);
	}

	public function testLinkToExternalSingleViewPageContainsExternalUrlIfAccessDenied() {
		$this->denyAccess();
		$this->fixture->setConfigurationValue('loginPID', $this->loginPid);
		$this->assertContains(
			urlencode('http://'.TX_REALTY_EXTERNAL_SINGLE_PAGE),
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


	///////////////////////////
	// Tests for the gallery.
	///////////////////////////

	public function testGalleryShowsWarningWithMissingShowUidParameter() {
		$this->fixture->setConfigurationValue('what_to_display', 'gallery');

		$this->assertContains(
			$this->fixture->translate('message_invalidImage'),
			$this->fixture->main('', array())
		);
	}

	public function testGalleryShowsWarningWithMissingImageParameter() {
		$this->fixture->setConfigurationValue('what_to_display', 'gallery');
		$this->fixture->piVars['showUid'] = $this->firstRealtyUid;

		$this->assertContains(
			$this->fixture->translate('message_invalidImage'),
			$this->fixture->main('', array())
		);
	}

	public function testHeaderIsSendWhenGalleryShowsInvalidImageWarning() {
		$this->fixture->setConfigurationValue('what_to_display', 'gallery');

		$this->assertContains(
			$this->fixture->translate('message_invalidImage'),
			$this->fixture->main('', array())
		);
		$this->assertEquals(
			'Status: 404 Not Found',
			tx_oelib_headerProxyFactory::getInstance()->getHeaderProxy()->getLastAddedHeader()
		);
	}

	public function testGalleryShowsNoWarningWithAllParameter() {
		$this->testingFramework->createRecord(
			REALTY_TABLE_IMAGES,
			array('realty_object_uid' => $this->firstRealtyUid)
		);
		$this->fixture->setConfigurationValue('what_to_display', 'gallery');
		$this->fixture->piVars['showUid'] = $this->firstRealtyUid;
		$this->fixture->piVars['image'] = 0;

		$this->assertNotContains(
			$this->fixture->translate('message_invalidImage'),
			$this->fixture->main('', array())
		);
	}

	public function testGalleryHasNoUnreplacedMarkersForOnlyOneImage() {
		$this->testingFramework->createRecord(
			REALTY_TABLE_IMAGES, array(
				'realty_object_uid' => $this->firstRealtyUid,
				'image' => 'foo.jpg',
			)
		);

		$this->fixture->setConfigurationValue('what_to_display', 'gallery');
		$this->fixture->piVars['showUid'] = $this->firstRealtyUid;

		$this->assertNotContains(
			'###',
			$this->fixture->main('', array())
		);
	}

	public function testGalleryDisplaysNoWarningWithAllParameterForHiddenObjectWhenOwnerLoggedIn() {
		$this->testingFramework->createRecord(
			REALTY_TABLE_IMAGES,
			array('realty_object_uid' => $this->firstRealtyUid)
		);
		$feUserId = $this->testingFramework->createFrontEndUser(
			$this->testingFramework->createFrontEndUserGroup()
		);
		$this->testingFramework->loginFrontEndUser($feUserId);
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->firstRealtyUid,
			array('owner' => $feUserId, 'hidden' => 1)
		);

		$this->fixture->setConfigurationValue('what_to_display', 'gallery');
		$this->fixture->piVars['showUid'] = $this->firstRealtyUid;
		$this->fixture->piVars['image'] = 0;

		$this->assertNotContains(
			$this->fixture->translate('message_invalidImage'),
			$this->fixture->main('', array())
		);
	}

	public function testGalleryDisplaysWarningWithAllParameterForHiddenObjectWhenNoUserLoggedIn() {
		$this->testingFramework->createRecord(
			REALTY_TABLE_IMAGES,
			array('realty_object_uid' => $this->firstRealtyUid)
		);
		$feUserId = $this->testingFramework->createFrontEndUser(
			$this->testingFramework->createFrontEndUserGroup()
		);
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->firstRealtyUid,
			array('owner' => $feUserId, 'hidden' => 1)
		);

		$this->fixture->setConfigurationValue('what_to_display', 'gallery');
		$this->fixture->piVars['showUid'] = $this->firstRealtyUid;
		$this->fixture->piVars['image'] = 0;

		$this->assertContains(
			$this->fixture->translate('message_invalidImage'),
			$this->fixture->main('', array())
		);
	}

	public function testGalleryDisplaysWarningWithAllParameterForHiddenObjectForLoggedInNonOwner() {
		$this->testingFramework->createRecord(
			REALTY_TABLE_IMAGES,
			array('realty_object_uid' => $this->firstRealtyUid)
		);
		$feUserId = $this->testingFramework->createFrontEndUser(
			$this->testingFramework->createFrontEndUserGroup()
		);
		$this->testingFramework->loginFrontEndUser($feUserId);
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->firstRealtyUid,
			array('owner' => $feUserId + 1, 'hidden' => 1)
		);

		$this->fixture->setConfigurationValue('what_to_display', 'gallery');
		$this->fixture->piVars['showUid'] = $this->firstRealtyUid;
		$this->fixture->piVars['image'] = 0;

		$this->assertContains(
			$this->fixture->translate('message_invalidImage'),
			$this->fixture->main('', array())
		);
	}

	public function testGalleryDisplaysWarningForInvalidObjectUid() {
		$deletedObjectUid = $this->testingFramework->createRecord(
			REALTY_TABLE_OBJECTS, array('deleted' => 1)
		);
		$this->testingFramework->createRecord(
			REALTY_TABLE_IMAGES,
			array('realty_object_uid' => $deletedObjectUid)
		);
		$this->fixture->setConfigurationValue('what_to_display', 'gallery');
		$this->fixture->piVars['showUid'] = $deletedObjectUid;
		$this->fixture->piVars['image'] = 0;

		$this->assertContains(
			$this->fixture->translate('message_invalidImage'),
			$this->fixture->main('', array())
		);
	}

	public function testGalleryHasThumbnailLinkWithCacheHash() {
		$this->testingFramework->createRecord(
			REALTY_TABLE_IMAGES,
			array('realty_object_uid' => $this->firstRealtyUid)
		);
		$this->fixture->setConfigurationValue('what_to_display', 'gallery');
		$this->fixture->piVars['showUid'] = $this->firstRealtyUid;

		$this->assertContains(
			'cHash=',
			$this->fixture->main('', array())
		);
	}

	public function testGalleryDisplaysOnlyTwoImageContainerForTwoImages() {
		$this->testingFramework->createRecord(
			REALTY_TABLE_IMAGES, array(
				'realty_object_uid' => $this->firstRealtyUid,
				'image' => 'foo.jpg',
			)
		);
		$this->testingFramework->createRecord(
			REALTY_TABLE_IMAGES, array(
				'realty_object_uid' => $this->firstRealtyUid,
				'image' => 'bar.jpg',
			)
		);

		$this->fixture->setConfigurationValue('what_to_display', 'gallery');
		$this->fixture->piVars['showUid'] = $this->firstRealtyUid;

		$this->assertEquals(
			2,
			substr_count($this->fixture->main('', array()), '<td class="image">')
		);
	}

	public function testGalleryDisplaysOnlyOneImageContainerForTwoImagesWhenOneIsDeleted() {
		$this->testingFramework->createRecord(
			REALTY_TABLE_IMAGES, array(
				'realty_object_uid' => $this->firstRealtyUid,
				'image' => 'foo.jpg',
			)
		);
		$this->testingFramework->createRecord(
			REALTY_TABLE_IMAGES, array(
				'realty_object_uid' => $this->firstRealtyUid,
				'image' => 'bar.jpg',
				'deleted' => 1,
			)
		);

		$this->fixture->setConfigurationValue('what_to_display', 'gallery');
		$this->fixture->piVars['showUid'] = $this->firstRealtyUid;

		$this->assertEquals(
			1,
			substr_count($this->fixture->main('', array()), '<td class="image">')
		);
	}


	///////////////////////////////////////////
	// Tests concering the "my objects" list.
	///////////////////////////////////////////

	public function testAccessToMyObjectsViewIsForbiddenForNotLoggedInUser() {
		$this->fixture->setConfigurationValue('what_to_display', 'my_objects');
		$this->fixture->setConfigurationValue('loginPID', $this->loginPid);

		$this->assertContains(
			$this->fixture->translate('message_please_login'),
			$this->fixture->main('', array())
		);
		$this->assertContains(
			'?id=' . $this->loginPid,
			$this->fixture->main('', array())
		);
	}

	public function testAccessToMyObjectsViewContainsRedirectUrlWithPidIfAccessDenied() {
		$myObjectsPid = $this->testingFramework->createFrontEndPage();
		$this->testingFramework->createFakeFrontEnd($myObjectsPid);
		$this->fixture->setConfigurationValue('what_to_display', 'my_objects');
		$this->fixture->setConfigurationValue('loginPID', $this->loginPid);

		$this->assertContains(
			'redirect_url',
			$this->fixture->main('', array())
		);
		$this->assertContains(
			urlencode('?id=' . $myObjectsPid),
			$this->fixture->main('', array())
		);
	}

	public function testHeaderIsSentWhenTheMyObjectsViewShowsPleaseLoginMessage() {
		$this->fixture->setConfigurationValue('what_to_display', 'my_objects');
		$this->fixture->setConfigurationValue('loginPID', $this->loginPid);

		$this->assertContains(
			$this->fixture->translate('message_please_login'),
			$this->fixture->main('', array())
		);

		$this->assertEquals(
			'Status: 403 Forbidden',
			tx_oelib_headerProxyFactory::getInstance()->getHeaderProxy()->getLastAddedHeader()
		);
	}

	public function testNoResultsFoundMessageIsDisplayedForLoggedInUserWhoHasNoObjects() {
		$feUserId = $this->testingFramework->createFrontEndUser(
			$this->testingFramework->createFrontEndUserGroup()
		);
		$this->testingFramework->loginFrontEndUser($feUserId);
		$this->fixture->setConfigurationValue('what_to_display', 'my_objects');

		$this->assertContains(
			$this->fixture->translate('message_noResultsFound_my_objects'),
			$this->fixture->main('', array())
		);
	}

	public function testOnlyObjectsTheLoggedInUserOwnsAreDisplayed() {
		$feUserId = $this->testingFramework->createFrontEndUser(
			$this->testingFramework->createFrontEndUserGroup()
		);
		$this->testingFramework->loginFrontEndUser($feUserId);
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->firstRealtyUid,
			array('owner' => $feUserId)
		);
		$this->fixture->setConfigurationValue('what_to_display', 'my_objects');

		$this->assertContains(
			self::$firstObjectTitle,
			$this->fixture->main('', array())
		);
		$this->assertNotContains(
			self::$secondObjectTitle,
			$this->fixture->main('', array())
		);
	}

	public function testMyObjectsViewHasNoUnreplacedMarkers() {
		$feUserId = $this->testingFramework->createFrontEndUser(
			$this->testingFramework->createFrontEndUserGroup()
		);
		$this->testingFramework->loginFrontEndUser($feUserId);
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->firstRealtyUid,
			array('owner' => $feUserId)
		);
		$this->fixture->setConfigurationValue('what_to_display', 'my_objects');

		$this->assertNotContains(
			'###',
			$this->fixture->main('', array())
		);
	}

	public function testMyObjectsViewContainsEditButton() {
		$feUserId = $this->testingFramework->createFrontEndUser(
			$this->testingFramework->createFrontEndUserGroup()
		);
		$this->testingFramework->loginFrontEndUser($feUserId);
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->firstRealtyUid,
			array('owner' => $feUserId)
		);
		$this->fixture->setConfigurationValue('what_to_display', 'my_objects');
		$this->fixture->setConfigurationValue(
			'editorPID', $this->testingFramework->createFrontEndPage()
		);

		$this->assertContains(
			'button edit',
			$this->fixture->main('', array())
		);
	}

	public function testEditButtonInTheMyObjectsViewIsLinkedToTheFeEditor() {
		$feUserId = $this->testingFramework->createFrontEndUser(
			$this->testingFramework->createFrontEndUserGroup()
		);
		$this->testingFramework->loginFrontEndUser($feUserId);
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->firstRealtyUid,
			array('owner' => $feUserId)
		);
		$editorPid = $this->testingFramework->createFrontEndPage();
		$this->fixture->setConfigurationValue('what_to_display', 'my_objects');
		$this->fixture->setConfigurationValue('editorPID', $editorPid);

		$this->assertContains(
			'?id=' . $editorPid,
			$this->fixture->main('', array())
		);
	}

	public function testEditButtonInTheMyObjectsViewContainsTheRecordUid() {
		$feUserId = $this->testingFramework->createFrontEndUser(
			$this->testingFramework->createFrontEndUserGroup()
		);
		$this->testingFramework->loginFrontEndUser($feUserId);
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->firstRealtyUid,
			array('owner' => $feUserId)
		);
		$this->fixture->setConfigurationValue('what_to_display', 'my_objects');
		$this->fixture->setConfigurationValue(
			'editorPID', $this->testingFramework->createFrontEndPage()
		);

		// The title linked to the gallery will also contain this UID.
		$this->assertEquals(
			2,
			substr_count(
				$this->fixture->main('', array()),
				'tx_realty_pi1[showUid]='.$this->firstRealtyUid
			)
		);
	}

	public function testDeleteObjectFromMyObjectsList() {
		$feUserId = $this->testingFramework->createFrontEndUser(
			$this->testingFramework->createFrontEndUserGroup()
		);
		$this->testingFramework->loginFrontEndUser($feUserId);
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->firstRealtyUid,
			array('owner' => $feUserId)
		);
		$this->fixture->setConfigurationValue('what_to_display', 'my_objects');

		$this->assertContains(
			self::$firstObjectTitle,
			$this->fixture->main('', array())
		);

		$this->fixture->piVars['delete'] = $this->firstRealtyUid;

		$this->assertNotContains(
			self::$firstObjectTitle,
			$this->fixture->main('', array())
		);
		$this->assertEquals(
			0,
			$this->testingFramework->countRecords(
				REALTY_TABLE_OBJECTS,
				'uid='.$this->firstRealtyUid
					.$this->fixture->enableFields(REALTY_TABLE_OBJECTS)
			)
		);
	}

	public function testMyObjectsViewContainsCreateNewObjectLink() {
		$feUserId = $this->testingFramework->createFrontEndUser(
			$this->testingFramework->createFrontEndUserGroup()
		);
		$this->testingFramework->loginFrontEndUser($feUserId);
		$this->fixture->setConfigurationValue('what_to_display', 'my_objects');
		$this->fixture->setConfigurationValue(
			'editorPID',
			$this->testingFramework->createFrontEndPage()
		);

		$this->assertContains(
			'button newRecord',
			$this->fixture->main('', array())
		);
	}

	public function testCreateNewObjectLinkInTheMyObjectsViewContainsTheEditorPid() {
		$feUserId = $this->testingFramework->createFrontEndUser(
			$this->testingFramework->createFrontEndUserGroup()
		);
		$this->testingFramework->loginFrontEndUser($feUserId);
		$editorPid = $this->testingFramework->createFrontEndPage();
		$this->fixture->setConfigurationValue('what_to_display', 'my_objects');
		$this->fixture->setConfigurationValue('editorPID', $editorPid);

		$this->assertContains(
			'?id=' . $editorPid,
			$this->fixture->main('', array())
		);
	}

	public function testMyObjectsViewDisplaysStatePublished() {
		$feUserId = $this->testingFramework->createFrontEndUser(
			$this->testingFramework->createFrontEndUserGroup()
		);
		$this->testingFramework->loginFrontEndUser($feUserId);
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->firstRealtyUid,
			array('owner' => $feUserId)
		);
		$this->fixture->setConfigurationValue('what_to_display', 'my_objects');

		$this->assertContains(
			$this->fixture->translate('label_published'),
			$this->fixture->main('', array())
		);
	}

	public function testMyObjectsViewDisplaysStatePending() {
		$feUserId = $this->testingFramework->createFrontEndUser(
			$this->testingFramework->createFrontEndUserGroup()
		);
		$this->testingFramework->loginFrontEndUser($feUserId);
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->firstRealtyUid,
			array('owner' => $feUserId, 'hidden' => 1)
		);
		$this->fixture->setConfigurationValue('what_to_display', 'my_objects');

		$this->assertContains(
			$this->fixture->translate('label_pending'),
			$this->fixture->main('', array())
		);
	}

	//////////////////////////////
	// Testing the city selector
	//////////////////////////////

	public function testCitySelectorHasLinkToCitySelectorTargetPid() {
		$this->fixture->setConfigurationValue('what_to_display', 'city_selector');
		$this->fixture->setConfigurationValue('filterTargetPID', $this->listViewPid);

		$this->assertContains(
			'?id=' . $this->listViewPid,
			$this->fixture->main('', array())
		);
	}

	public function tesCitySelectorHasNoUnreplacedMarkers() {
		$this->fixture->setConfigurationValue('what_to_display', 'city_selector');
		$this->fixture->setConfigurationValue('filterTargetPID', $this->listViewPid);

		$this->assertNotContains(
			'###',
			$this->fixture->main('', array())
		);
	}
}
?>