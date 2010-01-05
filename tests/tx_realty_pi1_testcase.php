<?php
/***************************************************************
* Copyright notice
*
* (c) 2007-2010 Oliver Klee (typo3-coding@oliverklee.de)
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
require_once(t3lib_extMgm::extPath('realty') . 'pi1/class.tx_realty_pi1.php');

define('TX_REALTY_EXTERNAL_SINGLE_PAGE', 'www.oliverklee.de/');

/**
 * Testcase for the tx_realty_pi1 class in the 'realty' extension.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class tx_realty_pi1_testcase extends tx_phpunit_testcase {
	/**
	 * @var tx_realty_pi1
	 */
	private $fixture;

	/**
	 * @var tx_oelib_testingFramework
	 */
	private $testingFramework;

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
	 * @var integer static_info_tables UID of Germany
	 */
	const DE = 54;

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
			'pidList' => $this->systemFolderPid,
			'showGoogleMaps' => 0,
			'defaultCountryUID' => self::DE,
			'displayedContactInformation' => 'company,offerer_label,telephone',
		));
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
	 * Creates dummy FE pages (like login and single view).
	 */
	private function createDummyPages() {
		$this->loginPid = $this->testingFramework->createFrontEndPage();
		$this->systemFolderPid = $this->testingFramework->createSystemFolder(1);
		$this->subSystemFolderPid = $this->testingFramework->createSystemFolder(
			$this->systemFolderPid
		);
	}

	/**
	 * Creates dummy realty objects in the DB.
	 */
	private function createDummyObjects() {
		$this->firstRealtyUid = $this->testingFramework->createRecord(
			REALTY_TABLE_OBJECTS,
			array(
				'title' => self::$firstObjectTitle,
				'object_number' => self::$firstObjectNumber,
				'pid' => $this->systemFolderPid,
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
				'object_type' => REALTY_FOR_SALE,
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


	////////////////////////////////////////////////
	// Tests for the access-restricted single view
	////////////////////////////////////////////////

	public function testAccessToSingleViewIsAllowedWithoutLoginPerDefault() {
		$this->testingFramework->logoutFrontEndUser();

		$this->assertTrue(
			$this->fixture->isAccessToSingleViewPageAllowed()
		);
	}

	public function testAccessToSingleViewIsAllowedWithLoginPerDefault() {
		$this->testingFramework->createAndLoginFrontEndUser();

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
		$this->testingFramework->createAndLoginFrontEndUser();

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
		$this->testingFramework->createAndLoginFrontEndUser();

		$this->assertTrue(
			$this->fixture->isAccessToSingleViewPageAllowed()
		);
	}


	////////////////////////////
	// Testing the single view
	////////////////////////////

	public function testSingleViewIsDisplayedForValidRealtyObjectAndAccessAllowed() {
		$this->fixture->setConfigurationValue('what_to_display', 'single_view');
		$this->fixture->piVars['showUid'] = $this->firstRealtyUid;

		$this->assertContains(
			'class="single-view"',
			$this->fixture->main('', array())
		);
	}

	public function testNoResultViewIsDisplayedForRenderingTheSingleViewOfNonExistentObject() {
		$this->fixture->setConfigurationValue('what_to_display', 'single_view');
		$this->fixture->piVars['showUid'] = $this->testingFramework->createRecord(
			REALTY_TABLE_OBJECTS, array('deleted' => 1)
		);

		$this->assertContains(
			'class="noresults"',
			$this->fixture->main('', array())
		);
	}

	public function testErrorMessageIsDisplayedForRenderingTheSingleViewOfNonExistentObject() {
		$this->fixture->setConfigurationValue('what_to_display', 'single_view');
		$this->fixture->piVars['showUid'] = $this->testingFramework->createRecord(
			REALTY_TABLE_OBJECTS, array('deleted' => 1)
		);

		$this->assertContains(
			$this->fixture->translate('message_noResultsFound_single_view'),
			$this->fixture->main('', array())
		);
	}

	public function testErrorMessageIsDisplayedForRenderingTheSingleViewOfHiddenObject() {
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

	public function testErrorMessageIsDisplayedForRenderingTheSingleViewOfDeletedObject() {
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

	public function testErrorMessageIsDisplayedForRenderingTheSingleViewOfHiddenObjectForLoggedInNonOwner() {
		$this->testingFramework->createAndLoginFrontEndUser();
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->firstRealtyUid,
			array(
				'hidden' => 1,
				'owner' => $this->testingFramework->createFrontEndUser(),
			)
		);
		$this->fixture->setConfigurationValue('what_to_display', 'single_view');
		$this->fixture->piVars['showUid'] = $this->firstRealtyUid;

		$this->assertContains(
			$this->fixture->translate('message_noResultsFound_single_view'),
			$this->fixture->main('', array())
		);
	}

	public function testHeaderIsSetIfRenderingTheSingleViewLeadsToNoResultsMessage() {
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

	public function testGalleryDisplaysNoWarningWithAllParameterForHiddenObjectWhenOwnerLoggedIn() {
		$this->testingFramework->createRecord(
			REALTY_TABLE_IMAGES,
			array('realty_object_uid' => $this->firstRealtyUid)
		);
		$feUserId = $this->testingFramework->createAndLoginFrontEndUser();
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
		$feUserId = $this->testingFramework->createFrontEndUser();
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
		$feUserId = $this->testingFramework->createAndLoginFrontEndUser();
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

	public function testGalleryHasOnclickHandlerForFullsizeImageIfAllParametersAreProvided() {
		$this->testingFramework->createRecord(
			REALTY_TABLE_IMAGES,
			array('realty_object_uid' => $this->firstRealtyUid)
		);
		$this->fixture->setConfigurationValue('what_to_display', 'gallery');
		$this->fixture->piVars['showUid'] = $this->firstRealtyUid;
		$this->fixture->piVars['image'] = 0;

		$this->assertContains(
			'onclick="showFullsizeImage(',
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

	public function testJavaScriptForGalleryGetsIncludedIfWhatToDisplayIsGallery() {
		$this->fixture->setConfigurationValue('what_to_display', 'gallery');
		$this->fixture->main('', array());

		$this->assertEquals(
			'<script src="' . t3lib_extMgm::extRelPath('realty') .
				'pi1/tx_realty_pi1.js" type="text/javascript">' .
				'</script>',
			$GLOBALS['TSFE']->additionalHeaderData['tx_realty_pi1']
		);
	}


	/////////////////////////////////////////////////////////
	// Tests concering the access to the "my objects" list.
	/////////////////////////////////////////////////////////

	public function testAccessToMyObjectsViewIsForbiddenForNotLoggedInUser() {
		$this->fixture->setConfigurationValue('what_to_display', 'my_objects');
		$this->fixture->setConfigurationValue('loginPID', $this->loginPid);

		$output = $this->fixture->main('', array());
		$this->assertContains(
			$this->fixture->translate('message_please_login'),
			$output
		);

		$this->assertContains(
			'?id=' . $this->loginPid,
			$output
		);
	}

	public function testAccessToMyObjectsViewContainsRedirectUrlWithPidIfAccessDenied() {
		$myObjectsPid = $this->testingFramework->createFrontEndPage();
		$this->testingFramework->createFakeFrontEnd($myObjectsPid);
		$this->fixture->setConfigurationValue('what_to_display', 'my_objects');
		$this->fixture->setConfigurationValue('loginPID', $this->loginPid);

		$output = $this->fixture->main('', array());
		$this->assertContains(
			'redirect_url',
			$output
		);

		$this->assertContains(
			urlencode('?id=' . $myObjectsPid),
			$output
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


	/////////////////////////////
	// Testing the offerer list
	/////////////////////////////

	public function testOffererListIsDisplayedIfWhatToDisplayIsOffererList() {
		$groupId = $this->testingFramework->createFrontEndUserGroup();
		$this->testingFramework->createFrontEndUser($groupId);

		$this->fixture->setConfigurationValue('what_to_display', 'offerer_list');
		$this->fixture->setConfigurationValue('userGroupsForOffererList', $groupId);

		$this->assertContains(
			'offerer-list',
			$this->fixture->main('', array())
		);
	}


	////////////////////////////////////
	// Tests concerning the list views
	////////////////////////////////////

	/**
	 * @test
	 */
	public function forNoWhatToDisplaySetRealtyListViewWillBeRendered() {
		$this->fixture->setConfigurationValue('what_to_display', '');

		$this->assertContains(
			$this->fixture->translate('label_weofferyou'),
			$this->fixture->main('', array())
		);
	}

	/**
	 * @test
	 */
	public function realtyListViewCanBeRendered() {
		$this->fixture->setConfigurationValue('what_to_display', 'realty_list');

		$this->assertContains(
			$this->fixture->translate('label_weofferyou'),
			$this->fixture->main('', array())
		);
	}

	/**
	 * @test
	 */
	public function favoritesViewCanBeRendered() {
		$this->fixture->setConfigurationValue('what_to_display', 'favorites');

		$this->assertContains(
			$this->fixture->translate('label_yourfavorites'),
			$this->fixture->main('', array())
		);
	}

	/**
	 * @test
	 */
	public function myObjectsViewCanBeRendered() {
		$this->fixture->setConfigurationValue('what_to_display', 'my_objects');
		$this->testingFramework->createAndLoginFrontEndUser();

		$this->assertContains(
			$this->fixture->translate('label_your_objects'),
			$this->fixture->main('', array())
		);
	}

	/**
	 * @test
	 */
	public function objectByOwnerViewCanBeRendered() {
		$this->fixture->setConfigurationValue(
			'what_to_display', 'objects_by_owner'
		);

		$this->assertContains(
			$this->fixture->translate('label_sorry'),
			$this->fixture->main('', array())
		);
	}
}
?>