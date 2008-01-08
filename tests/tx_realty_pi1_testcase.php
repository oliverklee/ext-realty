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

require_once(t3lib_extMgm::extPath('realty').'pi1/class.tx_realty_pi1.php');
require_once(t3lib_extMgm::extPath('oelib').'class.tx_oelib_testingFramework.php');

define('TX_REALTY_FIRST_OBJECT_NUMBER', '1');
define('TX_REALTY_SECOND_OBJECT_NUMBER', '2');
define('TX_REALTY_FIRST_TITLE', 'a title');
define('TX_REALTY_SECOND_TITLE', 'another title');
define('TX_REALTY_EXTERNAL_SINGLE_PAGE', 'www.oliverklee.de/');

class tx_realty_pi1_testcase extends tx_phpunit_testcase {
	private $fixture;

	private $testingFramework;

	private $loginPid = 0;
	private $listViewPid = 0;
	private $singlePid = 0;
	private $systemFolderPid = 0;
	private $otherSinglePid = 0;
	private $firstRealtyUid = 0;
	private $secondRealtyUid = 0;

	public function setUp() {
		// Bolster up the fake front end.
		$GLOBALS['TSFE']->tmpl = t3lib_div::makeInstance('t3lib_tsparser_ext');
		$GLOBALS['TSFE']->tmpl->flattenSetup(array(), '', false);
		$GLOBALS['TSFE']->tmpl->init();
		$GLOBALS['TSFE']->tmpl->getCurrentPageData();

		if (!is_object($GLOBALS['TSFE']->fe_user)) {
			$GLOBALS['TSFE']->fe_user = t3lib_div::makeInstance('tslib_feUserAuth');
		}

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
			'pidList' => $this->systemFolderPid
		));

		// Ensures an empty favorites list.
		$this->fixture->storeFavorites(array());
	}

	public function tearDown() {
		$this->testingFramework->cleanUp();
		unset($this->testingFramework);
		unset($this->fixture);
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

	public function testFeUserLogin() {
		$this->fakeFeUserLogin();
		$this->assertTrue(
			$this->fixture->isLoggedIn()
		);
	}

	public function testFeUserLogoff() {
		$this->logoffFeUser();
		$this->assertFalse(
			$this->fixture->isLoggedIn()
		);
	}

	public function testFeUserLoginAndLogoff() {
		$this->fakeFeUserLogin();
		$this->logoffFeUser();
		$this->assertFalse(
			$this->fixture->isLoggedIn()
		);
	}

	public function testAccessToSingleViewIsAllowedWithoutLoginPerDefault() {
		$this->logoffFeUser();
		$this->assertTrue(
			$this->fixture->isAccessToSingleViewPageAllowed()
		);
	}

	public function testAccessToSingleViewIsAllowedWithLoginPerDefault() {
		$this->fakeFeUserLogin();
		$this->assertTrue(
			$this->fixture->isAccessToSingleViewPageAllowed()
		);
	}

	public function testAccessToSingleViewIsAllowedWithoutLoginIfNotDeniedPerConfiguration() {
		$this->fixture->setConfigurationValue('requireLoginForSingleViewPage', 0);
		$this->logoffFeUser();
		$this->assertTrue(
			$this->fixture->isAccessToSingleViewPageAllowed()
		);
	}

	public function testAccessToSingleViewIsAllowedWithLoginIfNotDeniedPerConfiguration() {
		$this->fixture->setConfigurationValue('requireLoginForSingleViewPage', 0);
		$this->fakeFeUserLogin();
		$this->assertTrue(
			$this->fixture->isAccessToSingleViewPageAllowed()
		);
	}

	public function testAccessToSingleViewIsDeniedWithoutLoginIfDeniedPerConfiguration() {
		$this->fixture->setConfigurationValue('requireLoginForSingleViewPage', 1);
		$this->logoffFeUser();
		$this->assertFalse(
			$this->fixture->isAccessToSingleViewPageAllowed()
		);
	}

	public function testAccessToSingleViewIsAllowedWithLoginIfDeniedPerConfiguration() {
		$this->fixture->setConfigurationValue('requireLoginForSingleViewPage', 1);
		$this->fakeFeUserLogin();
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

	public function testCreateListViewReturnsHtmlListOfTableEntries() {
		$this->fixture->setConfigurationValue('what_to_display', 'realty_list');

		$this->assertContains(
			TX_REALTY_FIRST_TITLE,
			$this->fixture->main('', array())
		);
		$this->assertContains(
			TX_REALTY_SECOND_TITLE,
			$this->fixture->main('', array())
		);
	}

	public function testCreateSummaryStringOfFavoritesContainsDataFromOneObject() {
		$this->fixture->addToFavorites(array($this->firstRealtyUid));

		$this->assertContains(
			'* '.TX_REALTY_FIRST_OBJECT_NUMBER.' '.TX_REALTY_FIRST_TITLE.chr(10),
			$this->fixture->createSummaryStringOfFavorites()
		);
		$this->assertNotContains(
			'* '.TX_REALTY_SECOND_OBJECT_NUMBER.' '.TX_REALTY_SECOND_TITLE.chr(10),
			$this->fixture->createSummaryStringOfFavorites()
		);
	}

	public function testCreateSummaryStringOfFavoritesContainsDataFromTwoObjects() {
		$this->fixture->addToFavorites(array($this->firstRealtyUid));
		$this->fixture->addToFavorites(array($this->secondRealtyUid));

		$this->assertContains(
			'* '.TX_REALTY_FIRST_OBJECT_NUMBER.' '.TX_REALTY_FIRST_TITLE.chr(10),
			$this->fixture->createSummaryStringOfFavorites()
		);
		$this->assertContains(
			'* '.TX_REALTY_SECOND_OBJECT_NUMBER.' '.TX_REALTY_SECOND_TITLE.chr(10),
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
			'* '.TX_REALTY_FIRST_OBJECT_NUMBER.' '.TX_REALTY_FIRST_TITLE.chr(10),
			$sessionData
		);
	}

	public function testWriteSummaryStringOfFavoritesToDatabaseIfFeUserIsLoggedIn() {
		$this->fakeFeUserLogin();

		$this->fixture->addToFavorites(array($this->firstRealtyUid));
		$this->fixture->writeSummaryStringOfFavoritesToSession();
		$sessionData = $GLOBALS['TSFE']->fe_user->getKey(
				'ses',
				'summaryStringOfFavorites'
		);
		$this->assertContains(
			'* '.TX_REALTY_FIRST_OBJECT_NUMBER.' '.TX_REALTY_FIRST_TITLE.chr(10),
			$sessionData
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


	///////////////////////
	// Utility functions.
	///////////////////////

	/**
	 * Fakes that a FE user has logged in.
	 */
	private function fakeFeUserLogin() {
		$GLOBALS['TSFE']->fe_user->createUserSession(array());
		$GLOBALS['TSFE']->loginUser = 1;
	}

	/**
	 * Logs off the current FE user (if he/she is logged in).
	 */
	private function logoffFeUser() {
		if (is_object($GLOBALS['TSFE']->fe_user)) {
			$GLOBALS['TSFE']->fe_user->logoff();
		}
		unset($GLOBALS['TSFE']->loginUser);
	}

	/**
	 * Denies access to the details page by requiring logon to display that page
	 * and then logging out any logged-in FE users.
	 */
	private function denyAccess() {
		$this->fixture->setConfigurationValue('requireLoginForSingleViewPage', 1);
		$this->logoffFeUser();
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
		$this->systemFolderPid = $this->testingFramework->createSystemFolder();
	}

	/**
	 * Creates dummy realty objects in the DB.
	 */
	private function createDummyObjects() {
		$this->firstRealtyUid = $this->testingFramework->createRecord(
			'tx_realty_objects',
			array(
				'title' => TX_REALTY_FIRST_TITLE,
				'object_number' => TX_REALTY_FIRST_OBJECT_NUMBER,
				'pid' => $this->systemFolderPid
			)
		);
		$this->secondRealtyUid = $this->testingFramework->createRecord(
			'tx_realty_objects',
			array(
				'title' => TX_REALTY_SECOND_TITLE,
				'object_number' => TX_REALTY_SECOND_OBJECT_NUMBER,
				'pid' => $this->systemFolderPid
			)
		);
	}
}

?>
