<?php
/***************************************************************
* Copyright notice
*
* (c) 2009 Saskia Metzler <saskia@merlin.owl.de>
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

		$this->realtyUid = $this->testingFramework->createRecord(
			REALTY_TABLE_OBJECTS, array('title' => 'test realty object')
		);

		$this->fixture = new tx_realty_pi1_SingleView(
			array('templateFile' => 'EXT:realty/pi1/tx_realty_pi1.tpl.htm'),
			$GLOBALS['TSFE']->cObj,
			true
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
		$this->assertEquals(
			'',
			$this->fixture->render(array(
				'showUid' => $this->testingFramework->createRecord(
					REALTY_TABLE_OBJECTS, array('deleted' => 1)
				)
			))
		);
	}

	public function testSingleViewReturnsEmptyResultForShowUidOfHiddenRecordAndNoUserLoggedIn() {
		$this->assertEquals(
			'',
			$this->fixture->render(array(
				'showUid' => $this->testingFramework->createRecord(
					REALTY_TABLE_OBJECTS, array('hidden' => 1)
				)
			))
		);
	}

	public function testSingleViewReturnsEmptyResultForShowUidOfHiddenRecordNonOwnerLoggedIn() {
		$this->testingFramework->createAndLoginFrontEndUser();

		$this->assertEquals(
			'',
			$this->fixture->render(array(
				'showUid' => $this->testingFramework->createRecord(
					REALTY_TABLE_OBJECTS,
					array(
						'hidden' => 1,
						'owner' => $this->testingFramework->createFrontEndUser()
					)
				)
			))
		);
	}

	public function testSingleViewReturnsNonEmptyResultForShowUidOfHiddenRecordOwnerLoggedIn() {
		$this->assertNotEquals(
			'',
			$this->fixture->render(array(
				'showUid' => $this->testingFramework->createRecord(
					REALTY_TABLE_OBJECTS,
					array(
						'hidden' => 1,
						'owner' => $this->testingFramework->createAndLoginFrontEndUser()
					)
				)
			))
		);
	}

	public function testSingleViewReturnsNonEmptyResultForShowUidOfExistingRecord() {
		$this->assertNotEquals(
			'',
			$this->fixture->render(array('showUid' => $this->realtyUid))
		);
	}

	public function testSingleViewReturnsNoUnreplacedMarkersWhileTheResultIsNonEmpty() {
		$result = $this->fixture->render(array('showUid' => $this->realtyUid));

		$this->assertNotEquals(
			'',
			$result
		);
		$this->assertNotContains(
			'###',
			$result
		);
	}


	////////////////////////////////////////////////////////////
	// Testing rendered images and the Lightbox styled gallery
	////////////////////////////////////////////////////////////

	public function testSingleViewDisplaysLinkedImage() {
		tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->find($this->realtyUid)->addImageRecord('foo', 'foo.jpg');

		$this->fixture->setConfigurationValue(
			'galleryPID', $this->testingFramework->createFrontEndPage()
		);

		$this->assertContains(
			'tx_realty_pi1[image]=0',
			$this->fixture->render(array('showUid' => $this->realtyUid))
		);
	}

	public function testSingleViewDisplaysLinkedImageWithGalleryPid() {
		tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->find($this->realtyUid)->addImageRecord('foo', 'foo.jpg');
		$galleryPid = $this->testingFramework->createFrontEndPage();

		$this->fixture->setConfigurationValue('galleryPID', $galleryPid);

		$this->assertContains(
			'?id=' . $galleryPid,
			$this->fixture->render(array('showUid' => $this->realtyUid))
		);
	}

	public function testSingleViewDisplaysLinkedImageWithCacheHashInTheLink() {
		tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->find($this->realtyUid)->addImageRecord('foo', 'foo.jpg');

		$this->fixture->setConfigurationValue(
			'galleryPID', $this->testingFramework->createFrontEndPage()
		);

		$this->assertContains(
			'cHash=',
			$this->fixture->render(array('showUid' => $this->realtyUid))
		);
	}

	public function testSingleViewDisplaysImageWithFullUrlForPopUp() {
		tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->find($this->realtyUid)->addImageRecord('foo', 'foo.jpg');

		$this->fixture->setConfigurationValue('galleryType', 'classic');
		$this->fixture->setConfigurationValue(
			'galleryPID', $this->testingFramework->createFrontEndPage()
		);
		$this->fixture->setConfigurationValue(
			'galleryPopupParameters', 'width=600,height=400'
		);

		$this->assertContains(
			'window.open(\'http://',
			$this->fixture->render(array('showUid' => $this->realtyUid))
		);
	}

	public function testSingleViewDisplaysImageForActivatedLightboxWithRelAttribute() {
		tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->find($this->realtyUid)->addImageRecord('foo', 'foo.jpg');

		$this->fixture->setConfigurationValue('galleryType', 'lightbox');
		$this->fixture->setConfigurationValue(
			'galleryPID', $this->testingFramework->createFrontEndPage()
		);

		$this->assertContains(
			'rel="lightbox[objectGallery]"',
			$this->fixture->render(array('showUid' => $this->realtyUid))
		);
	}

	public function testSingleViewDisplaysImageForDeactivatedLightboxWithoutRelAttribute() {
		tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->find($this->realtyUid)->addImageRecord('foo', 'foo.jpg');

		$this->fixture->setConfigurationValue('galleryType', 'classic');
		$this->fixture->setConfigurationValue(
			'galleryPID', $this->testingFramework->createFrontEndPage()
		);

		$this->assertNotContains(
			'rel="lightbox[objectGallery]"',
			$this->fixture->render(array('showUid' => $this->realtyUid))
		);
	}

	public function testSingleViewIncludesLightboxConfigurationForActivatedLightbox() {
		$this->fixture->setConfigurationValue(
			'galleryPID', $this->testingFramework->createFrontEndPage()
		);
		$this->fixture->setConfigurationValue('galleryType', 'lightbox');
		$this->fixture->render(array('showUid' => $this->realtyUid));

		$this->assertTrue(
			array_key_exists(
				'tx_realty_pi1_lightbox_config', $GLOBALS['TSFE']->additionalHeaderData
			)
		);
	}

	public function testSingleViewIncludesLightboxJsFileForActivatedLightbox() {
		$this->fixture->setConfigurationValue(
			'galleryPID', $this->testingFramework->createFrontEndPage()
		);
		$this->fixture->setConfigurationValue('galleryType', 'lightbox');
		$this->fixture->render(array('showUid' => $this->realtyUid));

		$this->assertTrue(
			in_array(
				'<script type="text/javascript" src="../typo3conf/ext/realty' .
					'/pi1/contrib/lightbox.js" ></script>',
				$GLOBALS['TSFE']->additionalHeaderData
			)
		);
	}

	public function testSingleViewIncludesLightboxCssFileForActivatedLightbox() {
		$this->fixture->setConfigurationValue(
			'galleryPID', $this->testingFramework->createFrontEndPage()
		);
		$this->fixture->setConfigurationValue('galleryType', 'lightbox');
		$this->fixture->render(array('showUid' => $this->realtyUid));

		$this->assertTrue(
			in_array(
				'<link rel="stylesheet" type="text/css" href="..' .
					'/typo3conf/ext/realty/pi1/contrib/lightbox.css" />',
				$GLOBALS['TSFE']->additionalHeaderData
			)
		);
	}

	public function testSingleViewIncludesPrototypeJsFileForActivatedLightbox() {
		$this->fixture->setConfigurationValue(
			'galleryPID', $this->testingFramework->createFrontEndPage()
		);
		$this->fixture->setConfigurationValue('galleryType', 'lightbox');
		$this->fixture->render(array('showUid' => $this->realtyUid));

		$this->assertTrue(
			in_array(
				'<script type="text/javascript" src="../typo3conf/ext/realty' .
					'/pi1/contrib/prototype.js"></script>',
				$GLOBALS['TSFE']->additionalHeaderData
			)
		);
	}

	public function testSingleViewIncludesScriptaculousJsFileForActivatedLightbox() {
		$this->fixture->setConfigurationValue(
			'galleryPID', $this->testingFramework->createFrontEndPage()
		);
		$this->fixture->setConfigurationValue('galleryType', 'lightbox');
		$this->fixture->render(array('showUid' => $this->realtyUid));

		$this->assertTrue(
			in_array(
				'<script type="text/javascript"src="../typo3conf/ext/realty/pi1' .
					'/contrib/scriptaculous.js?load=effects,builder"></script>',
				$GLOBALS['TSFE']->additionalHeaderData
			)
		);
	}

	public function testSingleViewNotIncludeLightboxConfigurationForDeactivatedLightboxDoes() {
		$this->fixture->setConfigurationValue(
			'galleryPID', $this->testingFramework->createFrontEndPage()
		);
		$this->fixture->setConfigurationValue('galleryType', 'classic');
		$this->fixture->render(array('showUid' => $this->realtyUid));

		$this->assertFalse(
			array_key_exists(
				'tx_realty_pi1_lightbox_config',
				$GLOBALS['TSFE']->additionalHeaderData
			)
		);
	}

	public function testSingleViewNotIncludesLightboxJsFileForDeactivatedLightbox() {
		$this->fixture->setConfigurationValue(
			'galleryPID', $this->testingFramework->createFrontEndPage()
		);
		$this->fixture->setConfigurationValue('galleryType', 'classic');
		$this->fixture->render(array('showUid' => $this->realtyUid));

		$this->assertFalse(
			in_array(
				'<script type="text/javascript" src="../typo3conf/ext/realty' .
					'/pi1/contrib/lightbox.js" ></script>',
				$GLOBALS['TSFE']->additionalHeaderData
			)
		);
	}

	public function testSingleViewNotIncludesLightboxCssFileForDeactivatedLightbox() {
		$this->fixture->setConfigurationValue(
			'galleryPID', $this->testingFramework->createFrontEndPage()
		);
		$this->fixture->setConfigurationValue('galleryType', 'classic');
		$this->fixture->render(array('showUid' => $this->realtyUid));

		$this->assertFalse(
			in_array(
				'<link rel="stylesheet" type="text/css" href="..' .
					'/typo3conf/ext/realty/pi1/contrib/lightbox.css" />',
				$GLOBALS['TSFE']->additionalHeaderData
			)
		);
	}

	public function testSingleViewNotIncludesPrototypeJsFileForDeactivatedLightbox() {
		$this->fixture->setConfigurationValue(
			'galleryPID', $this->testingFramework->createFrontEndPage()
		);
		$this->fixture->setConfigurationValue('galleryType', 'classic');
		$this->fixture->render(array('showUid' => $this->realtyUid));

		$this->assertFalse(
			in_array(
				'<script type="text/javascript" src="../typo3conf/ext/realty' .
					'/pi1/contrib/prototype.js"></script>',
				$GLOBALS['TSFE']->additionalHeaderData
			)
		);
	}

	public function testSingleViewNotIncludesScriptaculousJsFileForDeactivatedLightbox() {
		$this->fixture->setConfigurationValue(
			'galleryPID', $this->testingFramework->createFrontEndPage()
		);
		$this->fixture->setConfigurationValue('galleryType', 'classic');
		$this->fixture->render(array('showUid' => $this->realtyUid));

		$this->assertFalse(
			in_array(
				'<script type="text/javascript"src="../typo3conf/ext/realty/pi1' .
					'/contrib/scriptaculous.js?load=effects,builder"></script>',
				$GLOBALS['TSFE']->additionalHeaderData
			)
		);
	}


	//////////////////////////////////////
	// Tests concerning the contact link
	//////////////////////////////////////

	public function testSingleViewDisplaysContactButtonThisIsEnabledAndTheContactPidIsSet() {
		$this->fixture->setConfigurationValue('showContactPageLink', 1);
		$this->fixture->setConfigurationValue(
			'contactPID', $this->testingFramework->createFrontEndPage()
		);

		$this->assertContains(
			'class="button contact"',
			$this->fixture->render(array('showUid' => $this->realtyUid))
		);
	}

	public function testSingleViewDisplaysContactPidThisIsEnabledAndTheContactPidIsSet() {
		$contactPid = $this->testingFramework->createFrontEndPage();

		$this->fixture->setConfigurationValue('showContactPageLink', 1);
		$this->fixture->setConfigurationValue('contactPID', $contactPid);

		$this->assertContains(
			'?id=' . $contactPid,
			$this->fixture->render(array('showUid' => $this->realtyUid))
		);
	}

	public function testSingleViewNotDisplaysContactLinkThisIsDisabledAndTheContactPidIsSet() {
		$this->fixture->setConfigurationValue('showContactPageLink', 0);
		$this->fixture->setConfigurationValue(
			'contactPID', $this->testingFramework->createFrontEndPage()
		);

		$this->assertNotContains(
			'class="button contact"',
			$this->fixture->render(array('showUid' => $this->realtyUid))
		);
	}

	public function testSingleViewNotDisplaysContactLinkIfThisIsEnabledAndTheContactPidIsNotSet() {
		$this->fixture->setConfigurationValue('showContactPageLink', 1);
		$this->fixture->setConfigurationValue('contactPID', '');

		$this->assertNotContains(
			'class="button contact"',
			$this->fixture->render(array('showUid' => $this->realtyUid))
		);
	}

	public function testSingleViewDisplaysContactLinkWhichContainsTheObjectUid() {
		$this->fixture->setConfigurationValue('showContactPageLink', 1);
		$this->fixture->setConfigurationValue(
			'contactPID', $this->testingFramework->createFrontEndPage()
		);

		$this->assertContains(
			'tx_realty_pi1[showUid]=' . $this->realtyUid,
			 $this->fixture->render(array('showUid' => $this->realtyUid))
		);
	}

	public function testSingleViewNotDisplaysContactLinkIfTheContactFormHasTheSamePid() {
		$this->fixture->setConfigurationValue('showContactPageLink', 1);
		$this->fixture->setConfigurationValue('contactPID', $GLOBALS['TSFE']->id);

		$this->assertNotContains(
			'class="button contact"',
			$this->fixture->render(array('showUid' => $this->realtyUid))
		);
	}


	//////////////////////////////////////////////
	// Testing the displayed offerer information
	//////////////////////////////////////////////

	public function testSingleViewDisplaysContactInformationIfEnabledAndInformationIsSetInTheRealtyObject() {
		tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->find($this->realtyUid)->setProperty('contact_phone', '12345');

		$this->fixture->setConfigurationValue('displayedContactInformation', 'telephone');

		$this->assertContains(
			$this->fixture->translate('label_offerer'),
			$this->fixture->render(array('showUid' => $this->realtyUid))
		);
	}

	public function testSingleViewDisplaysPhoneNumberIfContactDataIsEnabledAndInformationIsSetInTheRealtyObject() {
		tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->find($this->realtyUid)->setProperty('contact_phone', '12345');

		$this->fixture->setConfigurationValue('displayedContactInformation', 'telephone');

		$this->assertContains(
			'12345',
			$this->fixture->render(array('showUid' => $this->realtyUid))
		);
	}

	public function testSingleViewDisplaysCompanyIfContactDataIsEnabledAndInformationIsSetInTheRealtyObject() {
		tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->find($this->realtyUid)->setProperty('employer', 'test company');

		$this->fixture->setConfigurationValue('displayedContactInformation', 'company');

		$this->assertContains(
			'test company',
			$this->fixture->render(array('showUid' => $this->realtyUid))
		);
	}

	public function testSingleViewDisplaysOwnersPhoneNumberIfContactDataIsEnabledAndContactDataMayBeTakenFromOwner() {
		$ownerUid = $this->testingFramework->createFrontEndUser(
			'', array('telephone' => '123123')
		);
		$realtyObject = tx_oelib_MapperRegistry
			::get('tx_realty_Mapper_RealtyObject')->find($this->realtyUid);
		$realtyObject->setProperty('owner', $ownerUid);
		$realtyObject->setProperty(
			'contact_data_source', REALTY_CONTACT_FROM_OWNER_ACCOUNT
		);

		$this->fixture->setConfigurationValue('displayedContactInformation', 'telephone');

		$this->assertContains(
			'123123',
			$this->fixture->render(array('showUid' => $this->realtyUid))
		);
	}

	public function testSingleViewDisplaysOwnersCompanyIfContactDataIsEnabledAndContactDataMayBeTakenFromOwner() {
		$ownerUid = $this->testingFramework->createFrontEndUser(
			'', array('company' => 'any company')
		);
		$realtyObject = tx_oelib_MapperRegistry
			::get('tx_realty_Mapper_RealtyObject')->find($this->realtyUid);
		$realtyObject->setProperty('owner', $ownerUid);
		$realtyObject->setProperty(
			'contact_data_source', REALTY_CONTACT_FROM_OWNER_ACCOUNT
		);

		$this->fixture->setConfigurationValue('displayedContactInformation', 'company');

		$this->assertContains(
			'any company',
			$this->fixture->render(array('showUid' => $this->realtyUid))
		);
	}

	public function testSingleViewNotDisplaysContactInformationIfOptionIsDisabled() {
		tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->find($this->realtyUid)->setProperty('contact_phone', '12345');

		$this->fixture->setConfigurationValue('displayedContactInformation', '');

		$this->assertNotContains(
			$this->fixture->translate('label_offerer'),
			$this->fixture->render(array('showUid' => $this->realtyUid))
		);
	}

	public function testSingleViewNotDisplaysContactInformationIfNoContactInformationAvailable() {
		$this->fixture->setConfigurationValue('displayedContactInformation', 'company');

		$this->assertNotContains(
			$this->fixture->translate('label_offerer'),
			$this->fixture->render(array('showUid' => $this->realtyUid))
		);
	}

	public function testSingleViewNotDisplaysContactInformationForEnabledOptionAndDeletedOwner() {
		$ownerUid = $this->testingFramework->createFrontEndUser(
			'', array('company' => 'any company', 'deleted' => 1)
		);
		$realtyObject = tx_oelib_MapperRegistry
			::get('tx_realty_Mapper_RealtyObject')->find($this->realtyUid);
		$realtyObject->setProperty('owner', $ownerUid);
		$realtyObject->setProperty(
			'contact_data_source', REALTY_CONTACT_FROM_OWNER_ACCOUNT
		);

		$this->fixture->setConfigurationValue('displayedContactInformation', 'company');

		$this->assertNotContains(
			$this->fixture->translate('label_offerer'),
			$this->fixture->render(array('showUid' => $this->realtyUid))
		);
	}

	public function testSingleViewNotDisplaysContactInformationForEnabledOptionAndOwnerWithoutData() {
		$ownerUid = $this->testingFramework->createFrontEndUser();
		$realtyObject = tx_oelib_MapperRegistry
			::get('tx_realty_Mapper_RealtyObject')->find($this->realtyUid);
		$realtyObject->setProperty('owner', $ownerUid);
		$realtyObject->setProperty(
			'contact_data_source', REALTY_CONTACT_FROM_OWNER_ACCOUNT
		);

		$this->fixture->setConfigurationValue('displayedContactInformation', 'company');

		$this->assertNotContains(
			$this->fixture->translate('label_offerer'),
			$this->fixture->render(array('showUid' => $this->realtyUid))
		);
	}

	public function testSingleViewDisplaysLabelForLinkToTheObjectsByOwnerListForEnabledOptionAndOwnerSet() {
		$this->fixture->setConfigurationValue('displayedContactInformation', 'offerer_label');
		$ownerUid = $this->testingFramework->createFrontEndUser(
			'', array('username' => 'foo')
		);
		$realtyObject = tx_oelib_MapperRegistry
			::get('tx_realty_Mapper_RealtyObject')->find($this->realtyUid);
		$realtyObject->setProperty('owner', $ownerUid);
		$realtyObject->setProperty(
			'contact_data_source', REALTY_CONTACT_FROM_OWNER_ACCOUNT
		);

		$this->fixture->setConfigurationValue(
			'objectsByOwnerPID', $this->testingFramework->createFrontEndPage()
		);

		$this->assertContains(
			$this->fixture->translate('label_this_owners_objects'),
			$this->fixture->render(array('showUid' => $this->realtyUid))
		);
	}

	public function testSingleViewDisplaysLabelOffererIfTheLinkToTheObjectsByOwnerListIsEnabled() {
		$ownerUid = $this->testingFramework->createFrontEndUser(
			'', array('username' => 'foo')
		);
		$realtyObject = tx_oelib_MapperRegistry
			::get('tx_realty_Mapper_RealtyObject')->find($this->realtyUid);
		$realtyObject->setProperty('owner', $ownerUid);
		$realtyObject->setProperty(
			'contact_data_source', REALTY_CONTACT_FROM_OWNER_ACCOUNT
		);

		$this->fixture->setConfigurationValue('displayedContactInformation', 'offerer_label');
		$this->fixture->setConfigurationValue(
			'objectsByOwnerPID', $this->testingFramework->createFrontEndPage()
		);

		$this->assertContains(
			$this->fixture->translate('label_offerer'),
			$this->fixture->render(array('showUid' => $this->realtyUid))
		);
	}

	public function testSingleViewDisplaysLinkToTheObjectsByOwnerListForEnabledOptionAndOwnerSet() {
		$ownerUid = $this->testingFramework->createFrontEndUser(
			'', array('username' => 'foo')
		);
		$realtyObject = tx_oelib_MapperRegistry
			::get('tx_realty_Mapper_RealtyObject')->find($this->realtyUid);
		$realtyObject->setProperty('owner', $ownerUid);
		$realtyObject->setProperty(
			'contact_data_source', REALTY_CONTACT_FROM_OWNER_ACCOUNT
		);
		$objectsByOwnerPid = $this->testingFramework->createFrontEndPage();

		$this->fixture->setConfigurationValue('displayedContactInformation', 'offerer_label');
		$this->fixture->setConfigurationValue('objectsByOwnerPID', $objectsByOwnerPid);

		$this->assertContains(
			'?id=' . $objectsByOwnerPid,
			$this->fixture->render(array('showUid' => $this->realtyUid))
		);
	}

	public function testSingleViewDisplaysOwnerUidInLinkToTheObjectsByOwnerListForEnabledOptionAndOwnerSet() {
		$ownerUid = $this->testingFramework->createFrontEndUser(
			'', array('username' => 'foo')
		);
		$realtyObject = tx_oelib_MapperRegistry
			::get('tx_realty_Mapper_RealtyObject')->find($this->realtyUid);
		$realtyObject->setProperty('owner', $ownerUid);
		$realtyObject->setProperty(
			'contact_data_source', REALTY_CONTACT_FROM_OWNER_ACCOUNT
		);

		$this->fixture->setConfigurationValue('displayedContactInformation', 'offerer_label');
		$this->fixture->setConfigurationValue(
			'objectsByOwnerPID', $this->testingFramework->createFrontEndPage()
		);

		$this->assertContains(
			'tx_realty_pi1[owner]=' . $ownerUid,
			$this->fixture->render(array('showUid' => $this->realtyUid))
		);
	}

	public function testSingleViewNotDisplaysLinkToTheObjectsByOwnerListForEnabledOptionAndNoOwnerSet() {
		$this->fixture->setConfigurationValue('displayedContactInformation', 'offerer_label');
		$this->fixture->setConfigurationValue(
			'objectsByOwnerPID', $this->testingFramework->createFrontEndPage()
		);

		$this->assertNotContains(
			$this->fixture->translate('label_this_owners_objects'),
			$this->fixture->render(array('showUid' => $this->realtyUid))
		);
	}

	public function testSingleViewNotDisplaysLinkToTheObjectsByOwnerListForDisabledContactInformationAndOwnerAndPidSet() {
		$ownerUid = $this->testingFramework->createFrontEndUser(
			'', array('username' => 'foo')
		);
		$realtyObject = tx_oelib_MapperRegistry
			::get('tx_realty_Mapper_RealtyObject')->find($this->realtyUid);
		$realtyObject->setProperty('owner', $ownerUid);
		$realtyObject->setProperty(
			'contact_data_source', REALTY_CONTACT_FROM_OWNER_ACCOUNT
		);

		$this->fixture->setConfigurationValue('displayedContactInformation', '');
		$this->fixture->setConfigurationValue(
			'objectsByOwnerPID', $this->testingFramework->createFrontEndPage()
		);

		$this->assertNotContains(
			$this->fixture->translate('label_this_owners_objects'),
			$this->fixture->render(array('showUid' => $this->realtyUid))
		);
	}

	public function testSingleViewPageNotContainsLinkToTheObjectsByOwnerListForNoObjectsByOwnerPidSetAndOwnerSet() {
		$ownerUid = $this->testingFramework->createFrontEndUser(
			'', array('username' => 'foo')
		);
		$realtyObject = tx_oelib_MapperRegistry
			::get('tx_realty_Mapper_RealtyObject')->find($this->realtyUid);
		$realtyObject->setProperty('owner', $ownerUid);
		$realtyObject->setProperty(
			'contact_data_source', REALTY_CONTACT_FROM_OWNER_ACCOUNT
		);

		$this->fixture->setConfigurationValue('displayedContactInformation', 'offerer_label');

		$this->assertNotContains(
			$this->fixture->translate('label_this_owners_objects'),
			$this->fixture->render(array('showUid' => $this->realtyUid))
		);
	}


	///////////////////////////////////////////////
	// Testing the contents of the overview table
	///////////////////////////////////////////////

	public function testSingleViewDisplaysHasAirConditioningRowForTrue() {
		tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->find($this->realtyUid)->setProperty('has_air_conditioning', '1');

		$this->fixture->setConfigurationValue(
			'fieldsInSingleViewTable', 'has_air_conditioning'
		);

		$this->assertContains(
			$this->fixture->translate('label_has_air_conditioning'),
			$this->fixture->render(array('showUid' => $this->realtyUid))
		);
	}

	public function testSingleViewNotDisplaysHasAirConditioningRowForFalse() {
		$this->fixture->setConfigurationValue(
			'fieldsInSingleViewTable', 'has_air_conditioning'
		);

		$this->assertNotContains(
			$this->fixture->translate('label_has_air_conditioning'),
			$this->fixture->render(array('showUid' => $this->realtyUid))
		);
	}

	public function testSingleViewDisplaysHasPoolRowForTrue() {
		tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->find($this->realtyUid)->setProperty('has_pool', '1');

		$this->fixture->setConfigurationValue(
			'fieldsInSingleViewTable', 'has_pool'
		);

		$this->assertContains(
			$this->fixture->translate('label_has_pool'),
			$this->fixture->render(array('showUid' => $this->realtyUid))
		);
	}

	public function testSingleViewNotDisplaysHasPoolRowForFalse() {
		$this->fixture->setConfigurationValue(
			'fieldsInSingleViewTable', 'has_pool'
		);

		$this->assertNotContains(
			$this->fixture->translate('label_has_pool'),
			$this->fixture->render(array('showUid' => $this->realtyUid))
		);
	}

	public function testSingleViewDisplaysHasCommunityPoolRowForTrue() {
		tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->find($this->realtyUid)->setProperty('has_community_pool', '1');

		$this->fixture->setConfigurationValue(
			'fieldsInSingleViewTable', 'has_community_pool'
		);

		$this->assertContains(
			$this->fixture->translate('label_has_community_pool'),
			$this->fixture->render(array('showUid' => $this->realtyUid))
		);
	}

	public function testSingleViewNotDisplaysHasCommunityPoolRowForFalse() {
		$this->fixture->setConfigurationValue(
			'fieldsInSingleViewTable', 'has_community_pool'
		);

		$this->assertNotContains(
			$this->fixture->translate('label_has_community_pool'),
			$this->fixture->render(array('showUid' => $this->realtyUid))
		);
	}

	public function testSingleViewDisplaysTheLabelForStateIfAValidStateIsSet() {
		tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->find($this->realtyUid)->setProperty('state', 8);

		$this->fixture->setConfigurationValue('fieldsInSingleViewTable', 'state');

		$this->assertContains(
			$this->fixture->translate('label_state'),
			$this->fixture->render(array('showUid' => $this->realtyUid))
		);
	}

	public function testSingleViewDisplaysTheStateIfAValidStateIsSet() {
		tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->find($this->realtyUid)->setProperty('state', 8);

		$this->fixture->setConfigurationValue('fieldsInSingleViewTable', 'state');

		$this->assertContains(
			$this->fixture->translate('label_state.8'),
			$this->fixture->render(array('showUid' => $this->realtyUid))
		);
	}

	public function testSingleViewNotDisplaysTheLabelForStateIfNoStateIsSet() {
		tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->find($this->realtyUid)->setProperty('state', 0);

		$this->fixture->setConfigurationValue('fieldsInSingleViewTable', 'state');

		$this->assertNotContains(
			$this->fixture->translate('label_state'),
			$this->fixture->render(array('showUid' => $this->realtyUid))
		);
	}

	public function testSingleViewNotDisplaysTheLabelForStateIfTheStateIsInvalid() {
		tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->find($this->realtyUid)->setProperty('state', 10000000);

		$this->fixture->setConfigurationValue('fieldsInSingleViewTable', 'state');

		$this->assertNotContains(
			$this->fixture->translate('label_state'),
			$this->fixture->render(array('showUid' => $this->realtyUid))
		);
	}

	public function testSingleViewDisplaysTheLabelForHeatingTypeIfOneValidHeatingTypeIsSet() {
		tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->find($this->realtyUid)->setProperty('heating_type', '1');

		$this->fixture->setConfigurationValue('fieldsInSingleViewTable', 'heating_type');

		$this->assertContains(
			$this->fixture->translate('label_heating_type'),
			$this->fixture->render(array('showUid' => $this->realtyUid))
		);
	}

	public function testSingleViewDisplaysTheHeatingTypeIfOneValidHeatingTypeIsSet() {
		tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->find($this->realtyUid)->setProperty('heating_type', '1');

		$this->fixture->setConfigurationValue('fieldsInSingleViewTable', 'heating_type');

		$this->assertContains(
			$this->fixture->translate('label_heating_type.1'),
			$this->fixture->render(array('showUid' => $this->realtyUid))
		);
	}

	public function testSingleViewDisplaysAHeatingTypeListIfMultipleValidHeatingTypesAreSet() {
		tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->find($this->realtyUid)->setProperty('heating_type', '1,3,4');

		$this->fixture->setConfigurationValue('fieldsInSingleViewTable', 'heating_type');

		$this->assertContains(
			$this->fixture->translate('label_heating_type.1') . ', ' .
				$this->fixture->translate('label_heating_type.3') . ', ' .
				$this->fixture->translate('label_heating_type.4'),
			$this->fixture->render(array('showUid' => $this->realtyUid))
		);
	}

	public function testSingleViewDisplaysTheHeatingTypeLabelIfOnlyAnInvalidHeatingTypeIsSet() {
		tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->find($this->realtyUid)->setProperty('heating_type', '100');

		$this->fixture->setConfigurationValue('fieldsInSingleViewTable', 'heating_type');

		$this->assertNotContains(
			$this->fixture->translate('label_heating_type'),
			$this->fixture->render(array('showUid' => $this->realtyUid))
		);
	}


	//////////////////////////////////
	// Testing the displayed address
	//////////////////////////////////

	public function testSingleViewDisplaysTheStreetIfShowAddressOfObjectsIsEnabled() {
		$realtyObject = tx_oelib_MapperRegistry
			::get('tx_realty_Mapper_RealtyObject')->find($this->realtyUid);
		$realtyObject->setProperty('street', 'Foo road 3');
		$realtyObject->setProperty('show_address', 1);

		$this->assertContains(
			'Foo road 3',
			$this->fixture->render(array('showUid' => $this->realtyUid))
		);
	}

	public function testSingleViewNotDisplaysTheStreetIfShowAddressOfObjectsIsDisabled() {
		$realtyObject = tx_oelib_MapperRegistry
			::get('tx_realty_Mapper_RealtyObject')->find($this->realtyUid);
		$realtyObject->setProperty('street', 'Foo road 3');
		$realtyObject->setProperty('show_address', 0);

		$this->assertNotContains(
			'Foo road 3',
			$this->fixture->render(array('showUid' => $this->realtyUid))
		);
	}

	public function testSingleViewDisplaysTheZipIfShowAddressOfObjectsIsEnabled() {
		$realtyObject = tx_oelib_MapperRegistry
			::get('tx_realty_Mapper_RealtyObject')->find($this->realtyUid);
		$realtyObject->setProperty('zip', '12345');
		$realtyObject->setProperty('show_address', 1);

		$this->assertContains(
			'12345',
			$this->fixture->render(array('showUid' => $this->realtyUid))
		);
	}

	public function testSingleViewDisplaysTheZipIfShowAddressOfObjectsIsDisabled() {
		$realtyObject = tx_oelib_MapperRegistry
			::get('tx_realty_Mapper_RealtyObject')->find($this->realtyUid);
		$realtyObject->setProperty('zip', '12345');
		$realtyObject->setProperty('show_address', 0);

		$this->assertContains(
			'12345',
			$this->fixture->render(array('showUid' => $this->realtyUid))
		);
	}

	public function testSingleViewDisplaysTheCountry() {
		// chosen randomly the country ID of Australia, must be different
		// from defaultCountryUid, otherwise the country would be hidden
		tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->find($this->realtyUid)->setProperty('country', '14');

		$this->assertContains(
			'Australia',
			$this->fixture->render(array('showUid' => $this->realtyUid))
		);
	}


	/////////////////////////////////////////////
	// Tests for Google Maps in the single view
	/////////////////////////////////////////////

	public function testSingleViewDisplaysMapForGoogleMapsEnabled() {
		$realtyObject = tx_oelib_MapperRegistry
			::get('tx_realty_Mapper_RealtyObject')->find($this->realtyUid);
		$realtyObject->setProperty('exact_coordinates_are_cached', 1);
		$realtyObject->setProperty('exact_latitude', '50.734343');
		$realtyObject->setProperty('exact_longitude', '7.10211');
		$realtyObject->setProperty('show_address', 1);

		$this->fixture->setConfigurationValue('showGoogleMaps', 1);

		$this->assertContains(
			'<div id="tx_realty_map"',
			$this->fixture->render(array('showUid' => $this->realtyUid))
		);
	}

	public function testSingleViewNotDisplaysMapForGoogleMapsDisabled() {
		$realtyObject = tx_oelib_MapperRegistry
			::get('tx_realty_Mapper_RealtyObject')->find($this->realtyUid);
		$realtyObject->setProperty('exact_coordinates_are_cached', 1);
		$realtyObject->setProperty('exact_latitude', 50.734343);
		$realtyObject->setProperty('exact_longitude', 7.10211);
		$realtyObject->setProperty('show_address', 1);

		$this->fixture->setConfigurationValue('showGoogleMaps', 0);

		$this->assertNotContains(
			'<div id="tx_realty_map"',
			$this->fixture->render(array('showUid' => $this->realtyUid))
		);
	}

	public function testGoogleMapsDoesNotLinkObjectTitleInMap() {
		$realtyObject = tx_oelib_MapperRegistry
			::get('tx_realty_Mapper_RealtyObject')->find($this->realtyUid);
		$realtyObject->setProperty('exact_coordinates_are_cached', 1);
		$realtyObject->setProperty('exact_latitude', 50.734343);
		$realtyObject->setProperty('exact_longitude', 7.10211);
		$realtyObject->setProperty('show_address', 1);

		$this->fixture->setConfigurationValue('showGoogleMaps', 1);

		$this->fixture->render(array('showUid' => $this->realtyUid));
		$this->assertNotContains(
			'href=',
			$GLOBALS['TSFE']->additionalHeaderData['tx_realty_pi1_maps']
		);
	}
}
?>