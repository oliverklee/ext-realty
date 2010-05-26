<?php
/***************************************************************
* Copyright notice
*
* (c) 2009-2010 Saskia Metzler <saskia@merlin.owl.de>
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
 * Unit tests for the tx_realty_pi1_SingleView class in the 'realty'
 * extension.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 */
class tx_realty_pi1_SingleView_testcase extends tx_phpunit_testcase {
	/**
	 * @var tx_realty_pi1_SingleView
	 */
	private $fixture;

	/**
	 * @var tx_oelib_testingFramework
	 */
	private $testingFramework;

	/**
	 * @var integer UID of the  dummy realty object
	 */
	private $realtyUid = 0;

	public function setUp() {
		$this->testingFramework = new tx_oelib_testingFramework('tx_realty');
		$this->testingFramework->createFakeFrontEnd();

		$this->fixture = new tx_realty_pi1_SingleView(
			array('templateFile' => 'EXT:realty/pi1/tx_realty_pi1.tpl.htm'),
			$GLOBALS['TSFE']->cObj,
			TRUE
		);
		$this->fixture->setConfigurationValue(
			'singleViewPartsToDisplay',
			'heading,address,description,furtherDescription,price,overviewTable,' .
				'imageThumbnails,addToFavoritesButton,contactButton,offerer,' .
				'backButton,printPageButton'
		);
	}

	public function tearDown() {
		$this->testingFramework->cleanUp();

		$this->fixture->__destruct();
		unset($this->fixture, $this->testingFramework);
	}


	/////////////////////////////////////////////////////
	// Testing the conditions to render the single view
	/////////////////////////////////////////////////////

	public function testSingleViewReturnsEmptyResultForZeroShowUid() {
		$this->assertEquals(
			'',
			$this->fixture->render(array('showUid' => 0))
		);
	}

	public function testSingleViewReturnsEmptyResultForShowUidOfDeletedRecord() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getNewGhost();
		$realtyObject->setToDeleted();

		$this->assertEquals(
			'',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	public function testSingleViewReturnsEmptyResultForShowUidOfHiddenRecordAndNoUserLoggedIn() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array('hidden' => 1));
		$this->assertEquals(
			'',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	public function testSingleViewReturnsEmptyResultForShowUidOfHiddenRecordNonOwnerLoggedIn() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array(
				'hidden' => 1,
				'owner' => $this->testingFramework->createFrontEndUser()
		));

		$this->testingFramework->createAndLoginFrontEndUser();

		$this->assertEquals(
			'',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	public function testSingleViewReturnsNonEmptyResultForShowUidOfHiddenRecordOwnerLoggedIn() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array(
				'hidden' => 1,
				'owner' => $this->testingFramework->createAndLoginFrontEndUser()
		));

		$this->assertNotEquals(
			'',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	public function testSingleViewReturnsNonEmptyResultForShowUidOfExistingRecord() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array('title' => 'foo'));

		$this->assertNotEquals(
			'',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	public function testSingleViewReturnsNoUnreplacedMarkersWhileTheResultIsNonEmpty() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array('title' => 'foo'));

		$this->assertNotContains(
			'###',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}


	///////////////////////////////////////////////
	// Testing the different view parts displayed
	///////////////////////////////////////////////

	public function testSingleViewDisplaysTheTitleOfARealtyObjectIfEnabled() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array('title' => 'foo'));

		$this->assertContains(
			'foo',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	public function testSingleViewNotDisplaysTheTitleOfARealtyObjectIfDisabled() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array('title' => 'foo'));

		$this->fixture->setConfigurationValue(
			'singleViewPartsToDisplay', 'description'
		);

		$this->assertNotContains(
			'foo',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	public function testSingleViewDisplaysTheDescriptionOfARealtyObjectIfEnabled() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array('description' => 'foo'));

		$this->assertContains(
			'foo',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	public function testSingleViewNotDisplaysTheDescriptionOfARealtyObjectIfDisabled() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array('description' => 'foo'));

		$this->fixture->setConfigurationValue(
			'singleViewPartsToDisplay', 'heading'
		);

		$this->assertNotContains(
			'foo',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	public function testSingleViewDisplaysThePriceOfARealtyObjectIfEnabled() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array(
				'object_type' => REALTY_FOR_SALE,
				'buying_price' => '123',
		));

		$this->assertContains(
			'123',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	public function testSingleViewNotDisplaysThePriceOfARealtyObjectIfDisabled() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array(
				'object_type' => REALTY_FOR_SALE,
				'buying_price' => '123',
		));

		$this->fixture->setConfigurationValue(
			'singleViewPartsToDisplay', 'heading'
		);

		$this->assertNotContains(
			'123',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	public function testSingleViewDisplaysTheEquipmentDescriptionOfARealtyObjectIfEnabled() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array('equipment' => 'foo'));

		$this->assertContains(
			'foo',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	public function testSingleViewNotDisplaysTheEquipmentDescriptionOfARealtyObjectIfDisabled() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array('equipment' => 'foo'));

		$this->fixture->setConfigurationValue(
			'singleViewPartsToDisplay', 'heading'
		);

		$this->assertNotContains(
			'foo',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	/**
	 * @test
	 */
	public function singleViewDisplaysTheAddToFavoritesButtonIfEnabled() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array('title' => 'foo'));

		$this->assertContains(
			'class="button singleViewAddToFavorites"',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	/**
	 * @test
	 */
	public function singleViewNotDisplaysTheAddToFavoritesButtonIfDisabled() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array('title' => 'foo'));

		$this->fixture->setConfigurationValue(
			'singleViewPartsToDisplay', 'backButton'
		);

		$this->assertNotContains(
			'class="button singleViewAddToFavorites"',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	/**
	 * @test
	 */
	public function singleViewDisplaysThePrintPageButtonIfEnabled() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array('title' => 'foo'));

		$this->assertContains(
			'class="button printPage"',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	/**
	 * @test
	 */
	public function singleViewNotDisplaysThePrintPageButtonIfDisabled() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array('title' => 'foo'));

		$this->fixture->setConfigurationValue(
			'singleViewPartsToDisplay', 'addToFavoritesButton'
		);

		$this->assertNotContains(
			'class="button printPage"',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	/**
	 * @test
	 */
	public function singleViewDisplaysTheBackButtonIfEnabled() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array('title' => 'foo'));

		$this->assertContains(
			'class="button singleViewBack"',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	/**
	 * @test
	 */
	public function singleViewNotDisplaysTheBackButtonIfDisabled() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array('title' => 'foo'));

		$this->fixture->setConfigurationValue(
			'singleViewPartsToDisplay', 'printPageButton'
		);

		$this->assertNotContains(
			'class="button singleViewBack"',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	/**
	 * @test
	 */
	public function singleViewNotDisplayingAnyOfTheActionButtonsHidesActionSubpart() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array('title' => 'foo'));

		$this->fixture->setConfigurationValue(
			'singleViewPartsToDisplay', 'header'
		);
		$this->fixture->render(array('showUid' => $realtyObject->getUid()));

		$this->assertFalse(
			$this->fixture->isSubpartVisible('FIELD_WRAPPER_ACTIONBUTTONS')
		);
	}

	public function testSingleViewDisplaysLinkedImageIfEnabled() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getNewGhost();
		$realtyObject->addImageRecord('foo', 'foo.jpg');

		$this->fixture->setConfigurationValue(
			'galleryPID', $this->testingFramework->createFrontEndPage()
		);

		$this->assertContains(
			'tx_realty_pi1[image]=0',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	public function testSingleViewNotDisplaysLinkedImageIfDisabled() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getNewGhost();
		$realtyObject->addImageRecord('foo', 'foo.jpg');

		$this->fixture->setConfigurationValue(
			'singleViewPartsToDisplay', 'heading'
		);
		$this->fixture->setConfigurationValue(
			'galleryPID', $this->testingFramework->createFrontEndPage()
		);

		$this->assertNotContains(
			'tx_realty_pi1[image]=0',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	public function testSingleViewNotDisplaysTextPaneDivIfOnlyImagesShouldBeDisplayed() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array('title' => 'foo'));

		$this->fixture->setConfigurationValue(
			'singleViewPartsToDisplay', 'imageThumbnails'
		);

		$this->assertNotContains(
			'<div class="text-pane',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	public function testSingleViewDisplaysTextPaneDivAndWithImagesClassNameImagesAndTextShouldBeDisplayed() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array('title' => 'foo'));

		$this->fixture->setConfigurationValue(
			'singleViewPartsToDisplay', 'heading,imageThumbnails'
		);

		$this->assertContains(
			'<div class="text-pane with-images',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	public function testSingleViewNotDisplaysWithImagesClassNameIfOnlyTextShouldBeDisplayed() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array('title' => 'foo'));

		$this->fixture->setConfigurationValue(
			'singleViewPartsToDisplay', 'heading'
		);

		$this->assertNotContains(
			'with-images',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}


	public function testSingleViewDisplaysContactButtonIfThisIsEnabled() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array('title' => 'test title'));

		$this->fixture->setConfigurationValue(
			'contactPID', $this->testingFramework->createFrontEndPage()
		);

		$this->assertContains(
			'class="button singleViewContact"',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	public function testSingleViewNotDisplaysContactButtonIfThisIsDisabled() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array('title' => 'test title'));

		$this->fixture->setConfigurationValue(
			'singleViewPartsToDisplay', 'heading'
		);
		$this->fixture->setConfigurationValue(
			'contactPID', $this->testingFramework->createFrontEndPage()
		);

		$this->assertNotContains(
			'class="button singleViewContact"',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	public function testSingleViewDisplaysOffererInformationIfThisIsEnabled() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array('contact_phone' => '12345'));

		$this->fixture->setConfigurationValue(
			'singleViewPartsToDisplay', 'offerer'
		);
		$this->fixture->setConfigurationValue(
			'displayedContactInformation', 'telephone'
		);

		$this->assertContains(
			$this->fixture->translate('label_offerer'),
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	public function testSingleViewDisplaysOffererInformationIfThisIsDisabled() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array('contact_phone' => '12345'));

		$this->fixture->setConfigurationValue(
			'singleViewPartsToDisplay', 'heading'
		);
		$this->fixture->setConfigurationValue(
			'displayedContactInformation', 'telephone'
		);

		$this->assertNotContains(
			$this->fixture->translate('label_offerer'),
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	public function testSingleViewDisplaysOverviewTableRowIfEnabled() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array('has_air_conditioning' => '1'));

		$this->fixture->setConfigurationValue(
			'fieldsInSingleViewTable', 'has_air_conditioning'
		);

		$this->assertContains(
			$this->fixture->translate('label_has_air_conditioning'),
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	public function testSingleViewNotDisplaysOverviewTableRowIfDisabled() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array('has_air_conditioning' => '1'));

		$this->fixture->setConfigurationValue(
			'singleViewPartsToDisplay', 'heading'
		);
		$this->fixture->setConfigurationValue(
			'fieldsInSingleViewTable', 'has_air_conditioning'
		);

		$this->assertNotContains(
			$this->fixture->translate('label_has_air_conditioning'),
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	public function testSingleViewDisplaysTheAddressOfARealtyObjectIfEnabled() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array('zip' => '12345'));

		$this->assertContains(
			'12345',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	public function testSingleViewNotDisplaysTheAddressOfARealtyObjectIfDisabled() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array('zip' => '12345'));

		$this->fixture->setConfigurationValue(
			'singleViewPartsToDisplay', 'heading'
		);

		$this->assertNotContains(
			'12345',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}


	/////////////////////////////////////////////
	// Tests for Google Maps in the single view
	/////////////////////////////////////////////

	public function testSingleViewDisplaysMapForGoogleMapsEnabled() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array(
				'exact_coordinates_are_cached' => 1,
				'exact_latitude' => '50.734343',
				'exact_longitude' => '7.10211',
				'show_address' => 1,
		));
		$this->fixture->setConfigurationValue(
			'singleViewPartsToDisplay', 'googleMaps'
		);

		$this->assertContains(
			'<div id="tx_realty_map"',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	public function testSingleViewNotDisplaysMapForGoogleMapsDisabled() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array(
				'exact_coordinates_are_cached' => 1,
				'exact_latitude' => 50.734343,
				'exact_longitude' => 7.10211,
				'show_address' => 1,
		));

		$this->assertNotContains(
			'<div id="tx_realty_map"',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	public function testGoogleMapsDoesNotLinkObjectTitleInMap() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array(
				'exact_coordinates_are_cached' => 1,
				'exact_latitude' => 50.734343,
				'exact_longitude' => 7.10211,
				'show_address' => 1,
		));

		$this->fixture->setConfigurationValue(
			'singleViewPartsToDisplay', 'googleMaps'
		);

		$this->fixture->render(array('showUid' => $realtyObject->getUid()));
		$this->assertNotContains(
			'href=',
			$GLOBALS['TSFE']->additionalHeaderData['tx_realty_pi1_maps']
		);
	}

	public function test_singleViewForActivatedListViewGooglemaps_DoesNotShowGoogleMapsByDefault() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array(
				'exact_coordinates_are_cached' => 1,
				'exact_latitude' => 50.734343,
				'exact_longitude' => 7.10211,
				'show_address' => 1,
		));

		$this->fixture->setConfigurationValue('showGoogleMaps', 1);

		$this->assertNotContains(
			'<div id="tx_realty_map"',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}


	///////////////////////////////////////////////////
	// Tests concerning the next and previous buttons
	///////////////////////////////////////////////////

	/**
	 * @test
	 */
	public function singleViewForEnabledNextPreviousButtonsShowsNextPreviousButtonsSubpart() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array());
		$GLOBALS['TSFE']->cObj->data['pid'] = $this->testingFramework->createFrontEndPage();

		$this->fixture->setConfigurationValue(
			'enableNextPreviousButtons', TRUE
		);
		$this->fixture->setConfigurationValue(
			'singleViewPartsToDisplay', 'nextPreviousButtons'
		);

		$this->assertContains(
			'previousNextButtons',
			$this->fixture->render(array(
				'showUid' => $realtyObject->getUid(),
				'recordPosition' => 0,
				'listViewType' => 'realty_list',
				'listUid' => $this->testingFramework->createContentElement(),
			))
		);
	}

	/**
	 * @test
	 */
	public function singleViewForEnabledNextPreviousButtonsButNotSetDisplayPartHidesNextPreviousButtonsSubpart() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array());
		$GLOBALS['TSFE']->cObj->data['pid'] = $this->testingFramework->createFrontEndPage();

		$this->fixture->setConfigurationValue(
			'enableNextPreviousButtons', TRUE
		);
		$this->fixture->setConfigurationValue(
			'singleViewPartsToDisplay', ''
		);

		$this->assertNotContains(
			'previousNextButtons',
			$this->fixture->render(array(
				'showUid' => $realtyObject->getUid(),
				'recordPosition' => 0,
				'listViewType' => 'realty_list',
				'listUid' => $this->testingFramework->createContentElement(),
			))
		);
	}

	/**
	 * @test
	 */
	public function singleViewForDisabledNextPreviousButtonsHidesNextPreviousButtonsSubpart() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array());
		$GLOBALS['TSFE']->cObj->data['pid'] = $this->testingFramework->createFrontEndPage();

		$this->fixture->setConfigurationValue(
			'enableNextPreviousButtons', FALSE
		);
		$this->fixture->setConfigurationValue(
			'singleViewPartsToDisplay', 'nextPreviousButtons'
		);

		$this->assertNotContains(
			'previousNextButtons',
			$this->fixture->render(array(
				'showUid' => $realtyObject->getUid(),
				'recordPosition' => 0,
				'listViewType' => 'realty_list',
			))
		);
	}
}
?>