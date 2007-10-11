<?php
/***************************************************************
* Copyright notice
*
* (c) 2007 Oliver Klee (typo3-coding@oliverklee.de)
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

require_once(t3lib_extMgm::extPath('realty')
	.'pi1/class.tx_realty_pi1.php');

define('TX_REALTY_FIRST_PID', '100000');
define('TX_REALTY_SINGLE_PID', '100000');
define('TX_REALTY_LOGIN_PID', '100001');
define('TX_REALTY_OTHER_SINGLE_PID', '100002');
define('TX_REALTY_EXTERNAL_SINGLE_PAGE', 'www.oliverklee.de/');

class tx_realty_pi1_testcase extends tx_phpunit_testcase {
	private $fixture;

	public function setUp() {
		// Bolster up the fake front end.
		$GLOBALS['TT'] = t3lib_div::makeInstance('t3lib_timeTrack');

		$GLOBALS['TSFE']->sys_page = t3lib_div::makeInstance('t3lib_pageSelect');

		$GLOBALS['TSFE']->tmpl = t3lib_div::makeInstance('t3lib_tsparser_ext');
		$GLOBALS['TSFE']->tmpl->flattenSetup(array(), '', false);
		$GLOBALS['TSFE']->tmpl->init();
		$GLOBALS['TSFE']->tmpl->getCurrentPageData();

		$this->fixture = new tx_realty_pi1();
		$this->fixture->cObj = t3lib_div::makeInstance('tslib_cObj');
		$this->fixture->cObj->start('');

		$this->fixture->init(array());
		// We expect the single view page to be at page #1.
		$this->fixture->setConfigurationValue('singlePID', TX_REALTY_SINGLE_PID);

		$this->createDummyPages();
	}

	public function tearDown() {
		$this->deleteDummyPages();

		unset($this->fixture);
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
			TX_REALTY_SINGLE_PID,
			$this->fixture->createLinkToSingleViewPage('foo', 0)
		);
	}

	public function testLinkToSingleViewPageContainsSinglePidIfAccessDenied() {
		$this->denyAccess();
		$this->fixture->setConfigurationValue('loginPID', TX_REALTY_LOGIN_PID);
		$this->assertContains(
			TX_REALTY_SINGLE_PID,
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
		$this->fixture->setConfigurationValue('loginPID', TX_REALTY_LOGIN_PID);
		$this->assertContains(
			'<a href=', $this->fixture->createLinkToSingleViewPage('&', 0)
		);
	}

	public function testLinkToSingleViewPageLinksToLoginPageIfAccessDenied() {
		$this->denyAccess();
		$this->fixture->setConfigurationValue('loginPID', TX_REALTY_LOGIN_PID);
		$this->assertContains(
			TX_REALTY_LOGIN_PID,
			$this->fixture->createLinkToSingleViewPage('foo', 0)
		);
	}

	public function testLinkToSingleViewPageContainsRedirectUrlIfAccessDenied() {
		$this->denyAccess();
		$this->fixture->setConfigurationValue('loginPID', TX_REALTY_LOGIN_PID);
		$this->assertContains(
			'redirect_url',
			$this->fixture->createLinkToSingleViewPage('foo', 0)
		);
	}

	public function testLinkToSingleViewPageNotLinksToLoginPageIfAccessAllowed() {
		$this->allowAccess();
		$this->fixture->setConfigurationValue('loginPID', TX_REALTY_LOGIN_PID);
		$this->assertNotContains(
			TX_REALTY_LOGIN_PID,
			$this->fixture->createLinkToSingleViewPage('foo', 0)
		);
	}

	public function testLinkToSingleViewPageNotContainsRedirectUrlIfAccesAllowed() {
		$this->allowAccess();
		$this->fixture->setConfigurationValue('loginPID', TX_REALTY_LOGIN_PID);
		$this->assertNotContains(
			'redirect_url',
			$this->fixture->createLinkToSingleViewPage('foo', 0)
		);
	}

	public function testGetFieldContentCreatesLinkToSinglePageIfAccessDenied() {
		$this->denyAccess();

		$this->fixture->setConfigurationValue('loginPID', TX_REALTY_LOGIN_PID);
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


	/////////////////////////////////////////////
	// Tests concerning separate details pages.
	/////////////////////////////////////////////

	public function testLinkToSeparateSingleViewPageContainsSeparateSinglePidIfAccessAllowed() {
		$this->allowAccess();
		$this->assertContains(
			TX_REALTY_OTHER_SINGLE_PID,
			$this->fixture->createLinkToSingleViewPage(
				'foo', 0, TX_REALTY_OTHER_SINGLE_PID
			)
		);
	}

	public function testLinkToSeparateSingleViewPageContainsSeparateSinglePidIfAccessDenied() {
		$this->denyAccess();
		$this->fixture->setConfigurationValue('loginPID', TX_REALTY_LOGIN_PID);
		$this->assertContains(
			TX_REALTY_OTHER_SINGLE_PID,
			$this->fixture->createLinkToSingleViewPage(
				'foo', 0, TX_REALTY_OTHER_SINGLE_PID
			)
		);
	}

	public function testLinkToSeparateSingleViewPageContainsATagIfAccessDenied() {
		$this->denyAccess();
		$this->fixture->setConfigurationValue('loginPID', TX_REALTY_LOGIN_PID);
		$this->assertContains(
			'<a href=',
			$this->fixture->createLinkToSingleViewPage(
				'&', 0, TX_REALTY_OTHER_SINGLE_PID
			)
		);
	}

	public function testLinkToSeparateSingleViewPageLinksToLoginPageIfAccessDenied() {
		$this->denyAccess();
		$this->fixture->setConfigurationValue('loginPID', TX_REALTY_LOGIN_PID);
		$this->assertContains(
			TX_REALTY_LOGIN_PID,
			$this->fixture->createLinkToSingleViewPage(
				'foo', 0, TX_REALTY_OTHER_SINGLE_PID
			)
		);
	}

	public function testLinkToSeparateSingleViewPageContainsRedirectUrlIfAccessDenied() {
		$this->denyAccess();
		$this->fixture->setConfigurationValue('loginPID', TX_REALTY_LOGIN_PID);
		$this->assertContains(
			'redirect_url',
			$this->fixture->createLinkToSingleViewPage(
				'foo', 0, TX_REALTY_OTHER_SINGLE_PID
			)
		);
	}

	public function testLinkToSeparateSingleViewPageNotLinksToLoginPageIfAccessAllowed() {
		$this->allowAccess();
		$this->fixture->setConfigurationValue('loginPID', TX_REALTY_LOGIN_PID);
		$this->assertNotContains(
			TX_REALTY_LOGIN_PID,
			$this->fixture->createLinkToSingleViewPage(
				'foo', 0, TX_REALTY_OTHER_SINGLE_PID
			)
		);
	}

	public function testLinkToSeparateSingleViewPageNotContainsRedirectUrlIfAccesAllowed() {
		$this->allowAccess();
		$this->fixture->setConfigurationValue('loginPID', TX_REALTY_LOGIN_PID);
		$this->assertNotContains(
			'redirect_url',
			$this->fixture->createLinkToSingleViewPage(
				'foo', 0, TX_REALTY_OTHER_SINGLE_PID
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
		$this->fixture->setConfigurationValue('loginPID', TX_REALTY_LOGIN_PID);
		$this->assertContains(
			urlencode('http://'.TX_REALTY_EXTERNAL_SINGLE_PAGE),
			$this->fixture->createLinkToSingleViewPage(
				'foo', 0, TX_REALTY_EXTERNAL_SINGLE_PAGE
			)
		);
	}

	public function testLinkToExternalSingleViewPageContainsATagIfAccessDenied() {
		$this->denyAccess();
		$this->fixture->setConfigurationValue('loginPID', TX_REALTY_LOGIN_PID);
		$this->assertContains(
			'<a href=',
			$this->fixture->createLinkToSingleViewPage(
				'&', 0, TX_REALTY_EXTERNAL_SINGLE_PAGE
			)
		);
	}

	public function testLinkToExternalSingleViewPageLinksToLoginPageIfAccessDenied() {
		$this->denyAccess();
		$this->fixture->setConfigurationValue('loginPID', TX_REALTY_LOGIN_PID);
		$this->assertContains(
			TX_REALTY_LOGIN_PID,
			$this->fixture->createLinkToSingleViewPage(
				'foo', 0, TX_REALTY_EXTERNAL_SINGLE_PAGE
			)
		);
	}

	public function testLinkToExternalSingleViewPageContainsRedirectUrlIfAccessDenied() {
		$this->denyAccess();
		$this->fixture->setConfigurationValue('loginPID', TX_REALTY_LOGIN_PID);
		$this->assertContains(
			'redirect_url',
			$this->fixture->createLinkToSingleViewPage(
				'foo', 0, TX_REALTY_EXTERNAL_SINGLE_PAGE
			)
		);
	}

	public function testLinkToExternalSingleViewPageNotLinksToLoginPageIfAccessAllowed() {
		$this->allowAccess();
		$this->fixture->setConfigurationValue('loginPID', TX_REALTY_LOGIN_PID);
		$this->assertNotContains(
			TX_REALTY_LOGIN_PID,
			$this->fixture->createLinkToSingleViewPage(
				'foo', 0, TX_REALTY_EXTERNAL_SINGLE_PAGE
			)
		);
	}

	public function testLinkToExternalSingleViewPageNotContainsRedirectUrlIfAccesAllowed() {
		$this->allowAccess();
		$this->fixture->setConfigurationValue('loginPID', TX_REALTY_LOGIN_PID);
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
		if (!is_object($GLOBALS['TSFE']->fe_user)) {
			$GLOBALS['TSFE']->fe_user
				= t3lib_div::makeInstance('tslib_feUserAuth');
		}
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
		$pageUids = array(
				TX_REALTY_LOGIN_PID,
				TX_REALTY_SINGLE_PID,
				TX_REALTY_OTHER_SINGLE_PID
		);

		foreach ($pageUids as $uid) {
			$GLOBALS['TYPO3_DB']->exec_INSERTquery(
				'pages',
				array(
					'uid' => $uid,
					'pid' => 1,
					'doktype' => 1
				)
			);
		}
	}

	/**
	 * Deletes the dummy FE pages.
	 */
	private function deleteDummyPages() {
		$GLOBALS['TYPO3_DB']->exec_DELETEquery(
			'pages',
			'uid >= '.TX_REALTY_FIRST_PID
		);
	}
}

?>
