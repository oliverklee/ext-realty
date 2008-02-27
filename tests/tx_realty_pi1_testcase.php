<?php
/***************************************************************
* Copyright notice
*
* (c) 2007-2008 Oliver Klee (typo3-coding@oliverklee.de)
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
 * Testcase for the tx_realty_pi1 class in the 'realty' extension.
 *
 * @package		TYPO3
 * @subpackage	tx_realty
 * @author		Oliver Klee <typo3-coding@oliverklee.de>
 */

require_once(PATH_tslib.'class.tslib_content.php');
require_once(PATH_tslib.'class.tslib_feuserauth.php');
require_once(PATH_t3lib.'class.t3lib_timetrack.php');

require_once(t3lib_extMgm::extPath('oelib').'tx_oelib_commonConstants.php');
require_once(t3lib_extMgm::extPath('oelib').'class.tx_oelib_testingFramework.php');

require_once(t3lib_extMgm::extPath('realty').'pi1/class.tx_realty_pi1.php');

define('TX_REALTY_EXTERNAL_SINGLE_PAGE', 'www.oliverklee.de/');

class tx_realty_pi1_testcase extends tx_phpunit_testcase {
	private $fixture;

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
		// Bolster up the fake front end.
		$GLOBALS['TSFE']->tmpl = t3lib_div::makeInstance('t3lib_tsparser_ext');
		$GLOBALS['TSFE']->tmpl->flattenSetup(array(), '', false);
		$GLOBALS['TSFE']->tmpl->init();
		$GLOBALS['TSFE']->tmpl->getCurrentPageData();
		// This object is required to be initialized before
		// $GLOBALS['TSFE']->initUserGroups() can be called.
		if (!is_object($GLOBALS['TSFE']->fe_user)) {
			$GLOBALS['TSFE']->fe_user = t3lib_div::makeInstance('tslib_feUserAuth');
		}
		// This initialization ensures dummy system folders get recognized as
		// "enabled". This affects the usage of sub-system folders in the tests.
		$GLOBALS['TSFE']->initUserGroups();

		$this->testingFramework = new tx_oelib_testingFramework('tx_realty');
		$this->createDummyPages();
		$this->createDummyObjects();

		$this->fixture = new tx_realty_pi1();
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
		$this->testingFramework->cleanUp();
		unset($this->fixture, $this->testingFramework);
	}


	public function testConfigurationCheckIsActiveWhenEnabled() {
		$GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['realty']
			= serialize(array('enableConfigCheck' => 1));
		// $this->fixture needs to be initialized again as the configuration is
		// checked during initialization
		unset($this->fixture);
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
		$GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['realty']
			= serialize(array('enableConfigCheck' => 0));
		// $this->fixture needs to be initialized again as the configuration is
		// checked during initialization
		unset($this->fixture);
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

	public function testPi1MustBeInitialized() {
		$this->assertNotNull(
			$this->fixture
		);
		$this->assertTrue(
			$this->fixture->isInitialized()
		);
	}

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

	public function testLinkToSingleViewPageContainsSinglePidIfAccessAllowed() {
		$this->allowAccess();
		$this->assertContains(
			(string) $this->singlePid,
			$this->fixture->createLinkToSingleViewPage('foo', 0)
		);
	}

	public function testLinkToSingleViewPageContainsSinglePidIfAccessDenied() {
		$this->denyAccess();
		$this->fixture->setConfigurationValue('loginPID', $this->loginPid);
		$this->assertContains(
			(string) $this->singlePid,
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
			(string) $this->loginPid,
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
			$this->loginPid,
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

	public function testCreateListViewReturnsPricesWithTheCurrencyUnitSetInTsSetup() {
		$this->testingFramework->changeRecord(
			'tx_realty_objects',
			$this->firstRealtyUid,
			array('buying_price' => '9', 'object_type' => '1')
		);
		$this->fixture->setConfigurationValue('what_to_display', 'realty_list');
		$this->fixture->setConfigurationValue('currencyUnit', '&euro;');

		$this->assertContains(
			'&euro;',
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

	public function testCreateListViewReturnsMainSysFolderRecordsAndSubFolderRecordsIfRecusionIsEnabled() {
		$this->fixture->setConfigurationValue('what_to_display', 'realty_list');
		$this->fixture->setConfigurationValue('recursive', '1');

		$this->testingFramework->changeRecord(
			'tx_realty_objects',
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
			'tx_realty_objects',
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


	//////////////////////////////////
	// Tests concerning the sorting.
	//////////////////////////////////

	public function testListViewIsSortedAscendinglyByObjectNumberWhenNumbersToSortAreIntegers() {
		$this->fixture->setConfigurationValue('what_to_display', 'realty_list');
		$this->fixture->conf['listView.']['orderBy'] = 'object_number';
		$this->fixture->conf['listView.']['descFlag'] = 0;

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
		$this->fixture->conf['listView.']['orderBy'] = 'object_number';
		$this->fixture->conf['listView.']['descFlag'] = 1;

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
			'tx_realty_objects',
			$this->firstRealtyUid,
			array('object_number' => '9')
		);
		$this->testingFramework->changeRecord(
			'tx_realty_objects',
			$this->secondRealtyUid,
			array('object_number' => '11')
		);

		$this->fixture->setConfigurationValue('what_to_display', 'realty_list');
		$this->fixture->conf['listView.']['orderBy'] = 'object_number';
		$this->fixture->conf['listView.']['descFlag'] = 0;

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
			'tx_realty_objects',
			$this->firstRealtyUid,
			array('object_number' => '9')
		);
		$this->testingFramework->changeRecord(
			'tx_realty_objects',
			$this->secondRealtyUid,
			array('object_number' => '11')
		);

		$this->fixture->setConfigurationValue('what_to_display', 'realty_list');
		$this->fixture->conf['listView.']['orderBy'] = 'object_number';
		$this->fixture->conf['listView.']['descFlag'] = 1;

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
			'tx_realty_objects',
			$this->firstRealtyUid,
			array('object_number' => '12.34')
		);
		$this->testingFramework->changeRecord(
			'tx_realty_objects',
			$this->secondRealtyUid,
			array('object_number' => '4.10')
		);

		$this->fixture->setConfigurationValue('what_to_display', 'realty_list');
		$this->fixture->conf['listView.']['orderBy'] = 'object_number';
		$this->fixture->conf['listView.']['descFlag'] = 0;

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
			'tx_realty_objects',
			$this->firstRealtyUid,
			array('object_number' => '12.34')
		);
		$this->testingFramework->changeRecord(
			'tx_realty_objects',
			$this->secondRealtyUid,
			array('object_number' => '4.10')
		);

		$this->fixture->setConfigurationValue('what_to_display', 'realty_list');
		$this->fixture->conf['listView.']['orderBy'] = 'object_number';
		$this->fixture->conf['listView.']['descFlag'] = 1;

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
			'tx_realty_objects',
			$this->firstRealtyUid,
			array('object_number' => '12.34')
		);
		$this->testingFramework->changeRecord(
			'tx_realty_objects',
			$this->secondRealtyUid,
			array('object_number' => '12.00')
		);

		$this->fixture->setConfigurationValue('what_to_display', 'realty_list');
		$this->fixture->conf['listView.']['orderBy'] = 'object_number';
		$this->fixture->conf['listView.']['descFlag'] = 0;

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
			'tx_realty_objects',
			$this->firstRealtyUid,
			array('object_number' => '12.34')
		);
		$this->testingFramework->changeRecord(
			'tx_realty_objects',
			$this->secondRealtyUid,
			array('object_number' => '12.00')
		);

		$this->fixture->setConfigurationValue('what_to_display', 'realty_list');
		$this->fixture->conf['listView.']['orderBy'] = 'object_number';
		$this->fixture->conf['listView.']['descFlag'] = 1;

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
			'tx_realty_objects',
			$this->firstRealtyUid,
			array('object_number' => '12,34')
		);
		$this->testingFramework->changeRecord(
			'tx_realty_objects',
			$this->secondRealtyUid,
			array('object_number' => '4,10')
		);

		$this->fixture->setConfigurationValue('what_to_display', 'realty_list');
		$this->fixture->conf['listView.']['orderBy'] = 'object_number';
		$this->fixture->conf['listView.']['descFlag'] = 0;

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
			'tx_realty_objects',
			$this->firstRealtyUid,
			array('object_number' => '12,34')
		);
		$this->testingFramework->changeRecord(
			'tx_realty_objects',
			$this->secondRealtyUid,
			array('object_number' => '4,10')
		);

		$this->fixture->setConfigurationValue('what_to_display', 'realty_list');
		$this->fixture->conf['listView.']['orderBy'] = 'object_number';
		$this->fixture->conf['listView.']['descFlag'] = 1;

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
			'tx_realty_objects',
			$this->firstRealtyUid,
			array('buying_price' => '9', 'object_type' => '1')
		);
		$this->testingFramework->changeRecord(
			'tx_realty_objects',
			$this->secondRealtyUid,
			array('buying_price' => '11', 'object_type' => '1')
		);

		$this->fixture->setConfigurationValue('what_to_display', 'realty_list');
		$this->fixture->conf['listView.']['orderBy'] = 'buying_price';
		$this->fixture->conf['listView.']['descFlag'] = 0;

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
			'tx_realty_objects',
			$this->firstRealtyUid,
			array('rent_excluding_bills' => '9', 'object_type' => '0')
		);
		$this->testingFramework->changeRecord(
			'tx_realty_objects',
			$this->secondRealtyUid,
			array('rent_excluding_bills' => '11', 'object_type' => '0')
		);

		$this->fixture->setConfigurationValue('what_to_display', 'realty_list');
		$this->fixture->conf['listView.']['orderBy'] = 'rent_excluding_bills';
		$this->fixture->conf['listView.']['descFlag'] = 0;

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
			'tx_realty_objects',
			$this->firstRealtyUid,
			array('number_of_rooms' => '9')
		);
		$this->testingFramework->changeRecord(
			'tx_realty_objects',
			$this->secondRealtyUid,
			array('number_of_rooms' => '11')
		);

		$this->fixture->setConfigurationValue('what_to_display', 'realty_list');
		$this->fixture->conf['listView.']['orderBy'] = 'number_of_rooms';
		$this->fixture->conf['listView.']['descFlag'] = 0;

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
			'tx_realty_objects',
			$this->firstRealtyUid,
			array('living_area' => '9')
		);
		$this->testingFramework->changeRecord(
			'tx_realty_objects',
			$this->secondRealtyUid,
			array('living_area' => '11')
		);

		$this->fixture->setConfigurationValue('what_to_display', 'realty_list');
		$this->fixture->conf['listView.']['orderBy'] = 'number_of_rooms';
		$this->fixture->conf['listView.']['descFlag'] = 0;

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
		$this->fixture->conf['listView.']['orderBy'] = 'city';
		$this->fixture->conf['listView.']['descFlag'] = 0;

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
		$this->fixture->conf['listView.']['orderBy'] = 'city';
		$this->fixture->conf['listView.']['descFlag'] = 1;

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
			'tx_realty_objects',
			$this->firstRealtyUid,
			array('street' => '11')
		);
		$this->testingFramework->changeRecord(
			'tx_realty_objects',
			$this->secondRealtyUid,
			array('street' => '9')
		);

		$this->fixture->setConfigurationValue('what_to_display', 'realty_list');
		$this->fixture->conf['listView.']['orderBy'] = 'street';
		$this->fixture->conf['listView.']['descFlag'] = 1;

		// Links inside the tags might contain numbers which could influence the
		// result. Therefore the tags are stripped.
		$result = strip_tags($this->fixture->main('', array()));
		$this->assertGreaterThan(
			strpos($result, self::$firstCityTitle),
			strpos($result, self::$secondCityTitle)
		);
	}


	/////////////////////////////////////////////////////
	// Tests concerning the summary sting of favorites.
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


	///////////////////////////////////////
	// Tests concerning the contact link.
	///////////////////////////////////////

	public function testContactLinkIsDisplayedInTheFavoritesViewIfTheContactPidIsSet() {
		$this->fixture->setConfigurationValue('what_to_display', 'favorites');
		$this->fixture->setConfigurationValue('contactPID', $this->otherSinglePid);
		$result = $this->fixture->main('', array());

		$this->assertContains(
			(string) $this->otherSinglePid,
			$result
		);
		$this->assertContains(
			'class="button contact"',
			$result
		);
	}

	public function testContactLinkIsNotDisplayedInTheFavoritesViewIfTheContactPidIsNotSet() {
		$this->fixture->setConfigurationValue('what_to_display', 'favorites');
		$this->fixture->setConfigurationValue('contactPID', '');

		$this->assertNotContains(
			'class="button contact"',
			$this->fixture->main('', array())
		);
	}


	/////////////////////////////////////////////
	// Tests concerning separate details pages.
	/////////////////////////////////////////////

	public function testLinkToSeparateSingleViewPageContainsSeparateSinglePidIfAccessAllowed() {
		$this->allowAccess();
		$this->assertContains(
			(string) $this->otherSinglePid,
			$this->fixture->createLinkToSingleViewPage(
				'foo', 0, $this->otherSinglePid
			)
		);
	}

	public function testLinkToSeparateSingleViewPageContainsSeparateSinglePidIfAccessDenied() {
		$this->denyAccess();
		$this->fixture->setConfigurationValue('loginPID', $this->loginPid);
		$this->assertContains(
			(string) $this->otherSinglePid,
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
			(string) $this->loginPid,
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
			(string) $this->loginPid,
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

	public function testGetFieldContentOfEstateSize() {
		$this->testingFramework->changeRecord(
			'tx_realty_objects',
			$this->firstRealtyUid,
			array('estate_size' => '12345')
		);
		$this->fixture->setConfigurationValue('what_to_display', 'single_view');
		$this->fixture->piVars['showUid'] = $this->firstRealtyUid;
		// $this->createListView() is called indirectly here. It sets the correct
		// values for $this->internal.
		$this->fixture->main('', array());

		$this->assertContains(
			'12345',
			$this->fixture->getFieldContent('estate_size')
		);
	}

	public function testDetailPageDisplaysTheZip() {
		$this->testingFramework->changeRecord(
			'tx_realty_objects',
			$this->firstRealtyUid,
			array('zip' => '12345')
		);
		$this->fixture->setConfigurationValue('what_to_display', 'single_view');
		$this->fixture->piVars['showUid'] = $this->firstRealtyUid;

		$this->assertContains(
			'12345',
			$this->fixture->main('', array())
		);
	}

	public function testDetailPageDisplaysTheZipIfShowAddressOfObjectsIsDisabled() {
		$this->testingFramework->changeRecord(
			'tx_realty_objects',
			$this->firstRealtyUid,
			array('zip' => '12345')
		);
		$this->fixture->setConfigurationValue('showAddressOfObjects', false);
		$this->fixture->setConfigurationValue('what_to_display', 'single_view');
		$this->fixture->piVars['showUid'] = $this->firstRealtyUid;

		$this->assertContains(
			'12345',
			$this->fixture->main('', array())
		);
	}


	/////////////////////////////////////////////
	// Tests concerning external details pages.
	/////////////////////////////////////////////

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
			(string) $this->loginPid,
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
			(string) $this->loginPid,
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

		// This will create a "Cannot modify header information - headers
		// already sent by" warning because the called function sets a HTTP
		// header. This is no error.
		// The warning will go away once bug 1650 is fixed.
		// @see https://bugs.oliverklee.com/show_bug.cgi?id=1650
		$this->assertContains(
			$this->fixture->translate('message_invalidImage'),
			$this->fixture->main('', array())
		);
	}

	public function testGalleryShowsWarningWithMissingImageParameter() {
		$this->fixture->setConfigurationValue('what_to_display', 'gallery');
		$this->fixture->piVars['showUid'] = $this->firstRealtyUid;

		// This will create a "Cannot modify header information - headers
		// already sent by" warning because the called function sets a HTTP
		// header. This is no error.
		// The warning will go away once bug 1650 is fixed.
		// @see https://bugs.oliverklee.com/show_bug.cgi?id=1650
		$this->assertContains(
			$this->fixture->translate('message_invalidImage'),
			$this->fixture->main('', array())
		);
	}

	public function testGalleryShowsNoWarningWithAllParameter() {
		$imageUid = $this->testingFramework->createRecord('tx_realty_images');
		$this->testingFramework->createRelation(
			'tx_realty_objects_images_mm',
			$this->firstRealtyUid, $imageUid
		);

		$this->fixture->setConfigurationValue('what_to_display', 'gallery');
		$this->fixture->piVars['showUid'] = $this->firstRealtyUid;
		$this->fixture->piVars['image'] = 0;

		$this->assertNotContains(
			$this->fixture->translate('message_invalidImage'),
			$this->fixture->main('', array())
		);
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
			'tx_realty_cities',
			array('title' => self::$firstCityTitle)
		);
		$this->secondCityUid = $this->testingFramework->createRecord(
			'tx_realty_cities',
			array('title' => self::$secondCityTitle)
		);
	}

	/**
	 * Creates dummy realty objects in the DB.
	 */
	private function createDummyObjects() {
		$this->createDummyCities();
		$this->firstRealtyUid = $this->testingFramework->createRecord(
			'tx_realty_objects',
			array(
				'title' => self::$firstObjectTitle,
				'object_number' => self::$firstObjectNumber,
				'pid' => $this->systemFolderPid,
				'city' => $this->firstCityUid
			)
		);
		$this->secondRealtyUid = $this->testingFramework->createRecord(
			'tx_realty_objects',
			array(
				'title' => self::$secondObjectTitle,
				'object_number' => self::$secondObjectNumber,
				'pid' => $this->systemFolderPid,
				'city' => $this->secondCityUid
			)
		);
	}
}

?>
