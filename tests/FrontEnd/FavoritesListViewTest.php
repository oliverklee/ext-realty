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

require_once(t3lib_extMgm::extPath('realty') . 'lib/tx_realty_constants.php');

/**
 * Test case.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 * @author Bernd Schönbach <bernd@oliverklee.de>
 */
class tx_realty_FrontEnd_FavoritesListViewTest extends tx_phpunit_testcase {
	/**
	 * @var tx_realty_pi1_FavoritesListView
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
	 * @var integer a city to relate realty objects to
	 */
	private $cityUid = 0;

	/**
	 * @var integer PID of the favorites page
	 */
	private $favoritesPid = 0;

	/**
	 * @var tx_oelib_FakeSession a fake session
	 */
	private $session;

	public function setUp() {
		$this->testingFramework = new tx_oelib_testingFramework('tx_realty');
		$this->testingFramework->createFakeFrontEnd();

		$this->createDummyPages();
		$this->createDummyObjects();

		$this->session = new tx_oelib_FakeSession();
		// Ensures an empty favorites list.
		$this->session->setAsString(
			tx_realty_pi1_FavoritesListView::FAVORITES_SESSION_KEY, ''
		);
		tx_oelib_Session::setInstance(
			tx_oelib_Session::TYPE_TEMPORARY, $this->session
		);

		$this->fixture = new tx_realty_pi1_FavoritesListView(
			array(
				'templateFile' => 'EXT:realty/pi1/tx_realty_pi1.tpl.htm',
				'favoritesPID' => $this->favoritesPid,
				'pages' => $this->systemFolderPid,
				'showGoogleMaps' => 0,
			),
			$GLOBALS['TSFE']->cObj,
			TRUE
		);
	}

	public function tearDown() {
		$this->testingFramework->cleanUp();

		$this->fixture->__destruct();
		unset($this->fixture, $this->session, $this->testingFramework);
	}


	//////////////////////
	// Utility functions
	//////////////////////

	/**
	 * Creates dummy FE pages for favorites list and a system folder for the
	 * storage of realty objects.
	 *
	 * @return void
	 */
	private function createDummyPages() {
		$this->favoritesPid = $this->testingFramework->createFrontEndPage();
		$this->systemFolderPid = $this->testingFramework->createSystemFolder(1);
	}

	/**
	 * Creates dummy realty objects in the DB.
	 *
	 * @return void
	 */
	private function createDummyObjects() {
		$this->cityUid = $this->testingFramework->createRecord(
			REALTY_TABLE_CITIES,
			array('title' => 'Foo City')
		);
		$this->firstRealtyUid = $this->testingFramework->createRecord(
			REALTY_TABLE_OBJECTS,
			array(
				'title' => self::$firstObjectTitle,
				'object_number' => self::$firstObjectNumber,
				'pid' => $this->systemFolderPid,
				'city' => $this->cityUid,
			)
		);
	}


	/////////////////////////////////////////
	// Tests concerning the basic functions
	/////////////////////////////////////////

	/**
	 * @test
	 */
	public function renderForEmptyRenderedObjectHasNoUnreplacedMarkers() {
		$systemFolder = $this->testingFramework->createSystemFolder();
		$this->session->setAsInteger(
			tx_realty_pi1_FavoritesListView::FAVORITES_SESSION_KEY,
			$this->testingFramework->createRecord(
				REALTY_TABLE_OBJECTS,
				array(
					'city' => $this->cityUid,
					'pid' => $systemFolder,
				)
			)
		);

		$this->fixture->setConfigurationValue('pages', $systemFolder);

		$this->assertNotContains(
			'###',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function renderForNoFavoritesShowsEmptyResultMessage() {
		$this->assertContains(
			$this->fixture->translate('message_noResultsFound_favorites'),
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function renderForOneFavoriteShowsFavoriteTitle() {
		$this->fixture->addToFavorites(array($this->firstRealtyUid));

		$this->assertContains(
			self::$firstObjectTitle,
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function renderForTwoFavoritesShowsBothFavoritesTitles() {
		$secondRealtyUid = $this->testingFramework->createRecord(
			REALTY_TABLE_OBJECTS,
			array(
				'title' => 'another object',
				'pid' => $this->systemFolderPid,
				'city' => $this->cityUid,
			)
		);

		$this->fixture->addToFavorites(
			array($this->firstRealtyUid, $secondRealtyUid)
		);
		$output = $this->fixture->render();

		$this->assertContains(
			self::$firstObjectTitle,
			$output
		);
		$this->assertContains(
			'another object',
			$output
		);
	}

	/**
	 * @test
	 */
	public function renderForOneFavoriteShowsDeleteFavoriteLink() {
		$this->fixture->addToFavorites(array($this->firstRealtyUid));
		$this->fixture->render();

		$this->fixture->isSubpartVisible('remove_from_favorites_button');
	}

	/**
	 * @test
	 */
	public function favoriteCanBeDeleted() {
		$this->fixture->addToFavorites(array($this->firstRealtyUid));

		$this->assertNotContains(
			self::$firstObjectTitle,
			$this->fixture->render(
				array(
					'favorites' => array($this->firstRealtyUid),
					'remove' => 1,
				)
			)
		);
	}

	/**
	 * @test
	 */
	public function twoFavoritesCanBeDeletedAtOnce() {
		$secondRealtyUid = $this->testingFramework->createRecord(
			REALTY_TABLE_OBJECTS,
			array(
				'title' => 'another object',
				'pid' => $this->systemFolderPid,
				'city' => $this->cityUid,
			)
		);
		$this->fixture->addToFavorites(array(
			$this->firstRealtyUid, $secondRealtyUid)
		);

		$output = $this->fixture->render(
			array(
				'favorites' => array($this->firstRealtyUid, $secondRealtyUid),
				'remove' => 1,
			)
		);

		$this->assertContains(
			$this->fixture->translate('message_noResultsFound_favorites'),
			$output
		);
	}

	/**
	 * @test
	 */
	public function renderForEnabledContactLinkAndSetContactPidShowsContactLink() {
		$this->fixture->setConfigurationValue('showContactPageLink', 1);
		$contactPid = $this->testingFramework->createFrontEndPage();
		$this->fixture->setConfigurationValue('contactPID', $contactPid);
		$result = $this->fixture->render();

		$this->assertContains(
			'?id=' . $contactPid,
			$result
		);
		$this->assertContains(
			'class="button listViewContact"',
			$result
		);
	}

	/**
	 * @test
	 */
	public function renderForDisabledContactLinkNotShowsContactLink() {
		$this->fixture->setConfigurationValue('showContactPageLink', 0);
		$this->fixture->setConfigurationValue(
			'contactPID', $this->testingFramework->createFrontEndPage()
		);

		$this->assertNotContains(
			'class="button listViewContact"',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function renderForEnabledContactLinkButNotSetContactPidNotShowsContactLink() {
		$this->fixture->setConfigurationValue('showContactPageLink', 1);
		$this->fixture->setConfigurationValue('contactPID', '');

		$this->assertNotContains(
			'class="button listViewContact"',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function renderForContactFormPidSameAsFavoritePidNotShowsContactLink() {
		$this->fixture->setConfigurationValue('showContactPageLink', 1);
		$this->fixture->setConfigurationValue('contactPID', $this->favoritesPid);

		$this->assertNotContains(
			'class="button listViewContact"',
			$this->fixture->render()
		);
	}


	////////////////////////////////////
	// Tests concerning addToFavorites
	////////////////////////////////////

	/**
	 * @test
	 */
	public function addToFavoritesWithNewItemCanAddItemToEmptySession() {
		$this->fixture->addToFavorites(array($this->firstRealtyUid));

		$this->assertEquals(
			array($this->firstRealtyUid),
			$this->session->getAsIntegerArray(
				tx_realty_pi1_FavoritesListView::FAVORITES_SESSION_KEY
			)
		);
	}

	/**
	 * @test
	 */
	public function addToFavoritesWithTwoNewItemsCanAddBothItemsToEmptySession() {
		$secondRealtyUid = $this->testingFramework->createRecord(
			REALTY_TABLE_OBJECTS,
			array(
				'title' => 'another object',
				'pid' => $this->systemFolderPid,
				'city' => $this->cityUid,
			)
		);

		$this->fixture->addToFavorites(
			array($this->firstRealtyUid, $secondRealtyUid)
		);

		$this->assertEquals(
			array($this->firstRealtyUid, $secondRealtyUid),
			$this->session->getAsIntegerArray(
				tx_realty_pi1_FavoritesListView::FAVORITES_SESSION_KEY
			)
		);
	}

	/**
	 * @test
	 */
	public function addToFavoritesWithNewItemCanAddItemToNonEmptySession() {
		$this->session->setAsInteger(
			tx_realty_pi1_FavoritesListView::FAVORITES_SESSION_KEY, $this->firstRealtyUid
		);
		$secondRealtyUid = $this->testingFramework->createRecord(
			REALTY_TABLE_OBJECTS,
			array(
				'title' => 'another object',
				'pid' => $this->systemFolderPid,
				'city' => $this->cityUid,
			)
		);

		$this->fixture->addToFavorites(array($secondRealtyUid));

		$this->assertEquals(
			array($this->firstRealtyUid, $secondRealtyUid),
			$this->session->getAsIntegerArray(
				tx_realty_pi1_FavoritesListView::FAVORITES_SESSION_KEY
			)
		);
	}

	/**
	 * @test
	 */
	public function addToFavoritesWithExistingItemDoesNotAddToSession() {
		$this->session->setAsInteger(
			tx_realty_pi1_FavoritesListView::FAVORITES_SESSION_KEY, $this->firstRealtyUid
		);

		$this->fixture->addToFavorites(array($this->firstRealtyUid));

		$this->assertEquals(
			array($this->firstRealtyUid),
			$this->session->getAsIntegerArray(
				tx_realty_pi1_FavoritesListView::FAVORITES_SESSION_KEY
			)
		);
	}


	/////////////////////////////////////////////////////
	// Tests for writeSummaryStringOfFavoritesToSession
	/////////////////////////////////////////////////////

	/**
	 * @test
	 */
	public function writeSummaryStringOfFavoritesToSessionForOneItemWritesItemsNumberAndTitleToSession() {
		$this->session->setAsInteger(
			tx_realty_pi1_FavoritesListView::FAVORITES_SESSION_KEY, $this->firstRealtyUid
		);
		$this->fixture->writeSummaryStringOfFavoritesToSession();

		$this->assertContains(
			'* ' . self::$firstObjectNumber . ' ' . self::$firstObjectTitle,
			$this->session->getAsString('summaryStringOfFavorites')
		);
	}

	/**
	 * @test
	 */
	public function writeSummaryStringOfFavoritesToSessionForLoggedInFrontEndUserWritesDataToTemporarySession() {
		tx_oelib_FrontEndLoginManager::getInstance()
			->logInUser(new tx_realty_Model_FrontEndUser());

		$this->session->setAsInteger(
			tx_realty_pi1_FavoritesListView::FAVORITES_SESSION_KEY, $this->firstRealtyUid
		);
		$this->fixture->writeSummaryStringOfFavoritesToSession();

		$this->assertContains(
			'* ' . self::$firstObjectNumber . ' ' . self::$firstObjectTitle,
			tx_oelib_Session::getInstance(tx_oelib_Session::TYPE_TEMPORARY)
				->getAsString('summaryStringOfFavorites')
		);
	}


	////////////////////////////////////////////////////
	// Tests concerning createSummaryStringOfFavorites
	////////////////////////////////////////////////////

	/**
	 * @test
	 */
	public function createSummaryStringOfFavoritesForNoFavoritesSetReturnsEmptyString() {
		$this->assertEquals(
			'',
			$this->fixture->createSummaryStringOfFavorites()
		);
	}

	/**
	 * @test
	 */
	public function createSummaryStringOfFavoritesForOneStoredFavoriteReturnsTitleOfFavorite() {
		$this->fixture->addToFavorites(array($this->firstRealtyUid));

		$this->assertContains(
			self::$firstObjectTitle,
			$this->fixture->createSummaryStringOfFavorites()
		);
	}

	/**
	 * @test
	 */
	public function createSummaryStringOfFavoritesForOneStoredFavoriteReturnsObjectNumberOfFavorite() {
		$this->fixture->addToFavorites(array($this->firstRealtyUid));

		$this->assertContains(
			self::$firstObjectNumber,
			$this->fixture->createSummaryStringOfFavorites()
		);
	}

	/**
	 * @test
	 */
	public function createSummaryStringOfFavoritesForTwoStoredFavoritesReturnsBothFavorites() {
		$secondRealtyUid = $this->testingFramework->createRecord(
			REALTY_TABLE_OBJECTS,
			array(
				'title' => 'another object',
				'pid' => $this->systemFolderPid,
				'city' => $this->cityUid,
			)
		);
		$this->fixture->addToFavorites(array($this->firstRealtyUid, $secondRealtyUid));

		$result = $this->fixture->createSummaryStringOfFavorites();

		$this->assertContains(
			self::$firstObjectTitle,
			$result
		);
		$this->assertContains(
			'another object',
			$result
		);
	}


	/////////////////////////////////////////////////////////
	// Tests concerning the favorites fields in the session
	/////////////////////////////////////////////////////////

	/**
	 * @test
	 */
	public function favoriteFieldsInSessionForTitleDoesNotCrash() {
		$this->fixture->addToFavorites(array($this->firstRealtyUid));
		$this->fixture->setConfigurationValue('favoriteFieldsInSession', 'title');

		$this->fixture->render();
	}
}