<?php
/***************************************************************
* Copyright notice
*
* (c) 2008-2009 Saskia Metzler <saskia@merlin.owl.de> All rights reserved
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

require_once(t3lib_extMgm::extPath('oelib') . 'class.tx_oelib_testingFramework.php');
require_once(t3lib_extMgm::extPath('oelib') . 'class.tx_oelib_mailerFactory.php');

require_once(t3lib_extMgm::extPath('realty') . 'lib/tx_realty_constants.php');
require_once(t3lib_extMgm::extPath('realty') . 'pi1/class.tx_realty_pi1.php');
require_once(t3lib_extMgm::extPath('realty') . 'pi1/class.tx_realty_contactForm.php');

/**
 * Unit tests for the tx_realty_contactForm class in the 'realty' extension.
 *
 * @package		TYPO3
 * @subpackage	tx_realty
 *
 * @author		Saskia Metzler <saskia@merlin.owl.de>
 */
class tx_realty_contactForm_testcase extends tx_phpunit_testcase {
	/** contact form object to be tested */
	private $fixture;
	/** instance of tx_oelib_testingFramework */
	private $testingFramework;
	/** instance of tx_realty_pi1 */
	private $pi1;

	/** dummy realty object ID */
	private $realtyUid;
	/** title for the dummy realty object */
	private static $realtyTitle = 'test title';
	/** object number for the dummy realty object */
	private static $realtyObjectNumber = '1234567';

	/** dummy FE user ID */
	private $feUserId;
	/** title for the dummy FE user */
	private static $feUserTitle ='any frontend user';
	/** e-mail address for the dummy FE user */
	private static $feUserEmail = 'frontend-user@valid-email.org';

	public function setUp() {
		$this->testingFramework = new tx_oelib_testingFramework('tx_realty');
		$this->testingFramework->createFakeFrontEnd();

		$this->pi1 = new tx_realty_pi1();
		$this->pi1->init(array('templateFile' => 'EXT:realty/pi1/tx_realty_pi1.tpl.htm'));
		$this->pi1->getTemplateCode();
		$this->pi1->setLabels();

		$this->fixture = new tx_realty_contactForm($this->pi1);

		$this->createDummyRecords();
		$this->pi1->setConfigurationValue(
			'defaultContactEmail', 'any-default@email-address.org'
		);
		$this->pi1->setConfigurationValue('blindCarbonCopyAddress', '');
		tx_oelib_mailerFactory::getInstance()->enableTestMode();
	}

	public function tearDown() {
		$this->testingFramework->logoutFrontEndUser();
		$this->testingFramework->cleanUp();
		tx_oelib_mailerFactory::getInstance()->discardInstance();
		unset($this->fixture, $this->pi1, $this->testingFramework);
	}


	///////////////////////
	// Utility functions.
	///////////////////////

	/**
	 * Creates dummy records in the DB.
	 */
	private function createDummyRecords() {
		$this->realtyUid = $this->testingFramework->createRecord(
			REALTY_TABLE_OBJECTS,
			array(
				'title' => self::$realtyTitle,
				'object_number' => self::$realtyObjectNumber
			)
		);
		$this->feUserId = $this->testingFramework->createFrontEndUser(
			$this->testingFramework->createFrontEndUserGroup(),
			array(
				'name' => self::$feUserTitle,
				'email' => self::$feUserEmail,
				'telephone' => '7654321'
			)
		);
	}


	////////////////////////////////////////////////////////
	// Tests conerning view-dependently displayed strings.
	////////////////////////////////////////////////////////

	public function testSpecializedContactFormContainsObjectTitle() {
		$this->assertContains(
			self::$realtyTitle,
			$this->fixture->render(
				array('showUid' => $this->realtyUid)
			)
		);
	}

	public function testSpecializedContactFormContainsObjectNumber() {
		$this->assertContains(
			self::$realtyObjectNumber,
			$this->fixture->render(
				array('showUid' => $this->realtyUid)
			)
		);
	}

	public function testGeneralContactFormDoesNotContainTitleLabelWithoutRealtySet() {
		$this->assertNotContains(
			$this->pi1->translate('label_title'),
			$this->fixture->render(array())
		);
	}

	public function testGeneralContactFormDoesNotContainObjectNumberLabelWithoutRealtySet() {
		$this->assertNotContains(
			$this->pi1->translate('label_object_number'),
			$this->fixture->render(array())
		);
	}

	public function testSpecializedContactFormHasWritableFieldsForNameAndEmailAddressIfNotLoggedIn() {
		$result = $this->fixture->render(array('showUid' => $this->realtyUid));

		$this->assertContains(
			$this->pi1->translate('label_your_name'),
			$result
		);
		$this->assertContains(
			$this->pi1->translate('label_your_email'),
			$result
		);
		$this->assertNotContains(
			'disabled',
			$result
		);
	}

	public function testGeneralContactFormHasWritableFieldsForNameAndEmailAddressIfNotLoggedIn() {
		$result = $this->fixture->render(array());

		$this->assertContains(
			$this->pi1->translate('label_your_name'),
			$result
		);
		$this->assertContains(
			$this->pi1->translate('label_your_email'),
			$result
		);
		$this->assertNotContains(
			'disabled',
			$result
		);
	}

	public function testSpecializedContactHasDisabledFieldsForNameAndEmailAddressIfLoggedIn() {
		$this->testingFramework->loginFrontendUser($this->feUserId);
		$result = $this->fixture->render(array('showUid' => $this->realtyUid));

		$this->assertContains(
			self::$feUserTitle,
			$result
		);
		$this->assertContains(
			self::$feUserEmail,
			$result
		);
		$this->assertContains(
			'disabled',
			$result
		);
	}

	public function testGeneralContactFormHasDisabledFieldsForNameAndEmailAddressIfLoggedIn() {
		$this->testingFramework->loginFrontendUser($this->feUserId);
		$result = $this->fixture->render(array());

		$this->assertContains(
			self::$feUserTitle,
			$result
		);
		$this->assertContains(
			self::$feUserEmail,
			$result
		);
		$this->assertContains(
			'disabled',
			$result
		);
	}

	public function testSpecializedContactHasNoDisabledInfomationIfNotLoggedIn() {
		$this->assertNotContains(
			$this->pi1->translate('label_requester_data_is_uneditable'),
			$this->fixture->render(array('showUid' => $this->realtyUid))
		);
	}

	public function testGeneralContactHasNoDisabledInfomationIfNotLoggedIn() {
		$this->assertNotContains(
			$this->pi1->translate('label_requester_data_is_uneditable'),
			$this->fixture->render(array())
		);
	}

	public function testSpecializedContactHasDisabledInfomationIfLoggedIn() {
		$this->testingFramework->loginFrontendUser($this->feUserId);

		$this->assertContains(
			$this->pi1->translate('label_requester_data_is_uneditable'),
			$this->fixture->render(array('showUid' => $this->realtyUid))
		);
	}

	public function testGeneralContactHasDisabledInfomationIfLoggedIn() {
		$this->testingFramework->loginFrontendUser($this->feUserId);

		$this->assertContains(
			$this->pi1->translate('label_requester_data_is_uneditable'),
			$this->fixture->render(array())
		);
	}

	public function testContactFormDisplaysGeneralViewIfTheRealtyObjectUidWasNotNumeric() {
		$this->assertNotContains(
			$this->pi1->translate('label_object_number'),
			$this->fixture->render(
				array('showUid' => 'foo')
			)
		);
	}

	public function testSpecializedContactFormNotDisplaysUnreplacedMarkersIfRealtyObjectDoesNotExist() {
		$this->assertNotContains(
			'###',
			$this->fixture->render(
				array('showUid' => $this->testingFramework->createRecord(
					REALTY_TABLE_OBJECTS, array('deleted' => 1)
				))
			)
		);
	}

	public function testSpecializedContactFormNotDisplaysObjectNumberLabelIfRealtyObjectDoesNotExist() {
		$this->assertNotContains(
			$this->pi1->translate('label_object_number'),
			$this->fixture->render(
				array('showUid' => $this->testingFramework->createRecord(
					REALTY_TABLE_OBJECTS, array('deleted' => 1)
				))
			)
		);
	}

	public function testSpecializedContactFormNotDisplaysTitleLabelIfRealtyObjectDoesNotExist() {
		$this->assertNotContains(
			$this->pi1->translate('label_title'),
			$this->fixture->render(
				array('showUid' => $this->testingFramework->createRecord(
					REALTY_TABLE_OBJECTS, array('deleted' => 1)
				))
			)
		);
	}

	public function testSpecializedContactFormNotDisplaysSubmitLabelIfRealtyObjectDoesNotExist() {
		$this->assertNotContains(
			$this->pi1->translate('label_submit'),
			$this->fixture->render(array(
				'showUid' => $this->testingFramework->createRecord(
					REALTY_TABLE_OBJECTS, array('deleted' => 1)
				))
			)
		);
	}

	public function testSpecializedContactFormNotDisplaysYourNameLabelIfRealtyObjectDoesNotExist() {
		$this->assertNotContains(
			$this->pi1->translate('label_your_name'),
			$this->fixture->render(array(
				'showUid' => $this->testingFramework->createRecord(
					REALTY_TABLE_OBJECTS, array('deleted' => 1)
				))
			)
		);
	}


	///////////////////////////////////////
	// Tests concerning (error) messages.
	///////////////////////////////////////

	public function testSpecializedContactFormDisplaysAnErrorIfRealtyObjectDoesNotExist() {
		$this->assertContains(
			$this->pi1->translate('message_noResultsFound_contact_form'),
			$this->fixture->render(
				array('showUid' => $this->testingFramework->createRecord(
					REALTY_TABLE_OBJECTS, array('deleted' => 1)
				))
			)
		);
	}

	public function testSpecializedContactFormDisplaysErrorAfterSubmittingIfNoValidEmailAddressWasProvided() {
		$this->assertContains(
			$this->pi1->translate('label_set_valid_email_address'),
			$this->fixture->render(
				array(
					'showUid' => $this->realtyUid,
					'isSubmitted' => true,
					'requesterName' => 'any name',
					'requesterEmail' => 'requester-invalid-email',
					'request' => 'the request'

				)
			)
		);
	}

	public function testGeneralContactFormDisplaysErrorAfterSubmittingIfNoValidEmailAddressWasProvided() {
		$this->assertContains(
			$this->pi1->translate('label_set_valid_email_address'),
			$this->fixture->render(
				array(
					'isSubmitted' => true,
					'requesterName' => 'any name',
					'requesterEmail' => 'requester-invalid-email',
					'request' => 'the request'
				)
			)
		);
	}

	public function testSpecializedContactFormDisplaysErrorAfterSubmittingIfHeaderInjectionWasAttemptedInTheEmailField() {
		$this->assertContains(
			$this->pi1->translate('label_set_valid_email_address'),
			$this->fixture->render(
				array(
					'showUid' => $this->realtyUid,
					'isSubmitted' => true,
					'requesterName' => 'any name',
					'requesterEmail' => 'requester@valid-email.org'.LF.'anything',
					'request' => 'the request'

				)
			)
		);
	}

	public function testSpecializedContactFormDisplaysErrorAfterSubmittingIfHeaderInjectionWasAttemptedInTheNameField() {
		$this->assertContains(
			$this->pi1->translate('label_set_name'),
			$this->fixture->render(
				array(
					'showUid' => $this->realtyUid,
					'isSubmitted' => true,
					'requesterName' => 'any name'.LF.'anything',
					'requesterEmail' => 'requester@valid-email.org',
					'request' => 'the request'

				)
			)
		);
	}

	public function testSpecializedContactFormDisplaysErrorAfterSubmittingIfNoNameWasProvided() {
		$this->assertContains(
			$this->pi1->translate('label_set_name'),
			$this->fixture->render(
				array(
					'showUid' => $this->realtyUid,
					'isSubmitted' => true,
					'requesterName' => '',
					'requesterEmail' => 'requester@valid-email.org',
					'request' => 'the request'
				)
			)
		);
	}

	public function testGeneralContactFormDisplaysErrorAfterSubmittingIfNoNameWasProvided() {
		$this->assertContains(
			$this->pi1->translate('label_set_name'),
			$this->fixture->render(
				array(
					'isSubmitted' => true,
					'requesterName' => '',
					'requesterEmail' => 'requester@valid-email.org',
					'request' => 'the request'
				)
			)
		);
	}

	public function testSpecializedContactFormDisplaysErrorAfterSubmittingIfTheRequestWasEmpty() {
		$this->assertContains(
			$this->pi1->translate('label_no_empty_textarea'),
			$this->fixture->render(
				array(
					'showUid' => $this->realtyUid,
					'isSubmitted' => true,
					'requesterName' => 'any name',
					'requesterEmail' => 'requester@valid-email.org',
					'request' => ''
				)
			)
		);
	}

	public function testGeneralContactFormDisplaysErrorAfterSubmittingIfTheRequestWasEmpty() {
		$this->assertContains(
			$this->pi1->translate('label_no_empty_textarea'),
			$this->fixture->render(
				array(
					'isSubmitted' => true,
					'requesterName' => 'any name',
					'requesterEmail' => 'requester@valid-email.org',
					'request' => ''
				)
			)
		);
	}

	public function testSpecializedContactFormDisplaysErrorAfterSubmittingIfTheObjectHasNoContactDataAndNoDefaultEmailWasSet() {
		$this->pi1->setConfigurationValue('defaultContactEmail', '');

		$this->assertContains(
			$this->pi1->translate('label_no_contact_person'),
			$this->fixture->render(
				array(
					'showUid' => $this->realtyUid,
					'isSubmitted' => true,
					'requesterName' => 'any name',
					'requesterEmail' => 'requester@valid-email.org',
					'request' => 'the request'
				)
			)
		);
	}

	public function testGeneralContactFormDisplaysAnErrorAfterSubmittingIfNoDefaultEmailAddressWasSet() {
		$this->pi1->setConfigurationValue('defaultContactEmail', '');

		$this->assertContains(
			$this->pi1->translate('label_no_contact_person'),
			$this->fixture->render(
				array(
					'isSubmitted' => true,
					'requesterName' => 'any name',
					'requesterEmail' => 'requester@valid-email.org',
					'request' => 'the request'
				)
			)
		);
	}

	public function testContactFormDisplaysSeveralErrorMessagesIfNoNameNoEmailAndNorRequestAreProvided() {
		$this->assertContains(
			$this->pi1->translate('label_set_name').'<br />'
				.$this->pi1->translate('label_set_valid_email_address').'<br />'
				.$this->pi1->translate('label_no_empty_textarea'),
			$this->fixture->render(
				array(
					'isSubmitted' => true,
					'requesterName' => '',
					'requesterEmail' => '',
					'request' => ''
				)
			)
		);
	}

	public function testSpecializedContactFormStillDisplaysTheFormIfAnErrorOccurs() {
		$result = $this->fixture->render(
			array(
				'showUid' => $this->realtyUid,
				'isSubmitted' => true,
				'requesterName' => 'any name',
				'requesterEmail' => 'requester@valid-email.org',
				'request' => ''
			)
		);

		$this->assertContains(
			$this->pi1->translate('label_no_empty_textarea'),
			$result
		);
		$this->assertContains(
			self::$realtyTitle,
			$result
		);
		$this->assertContains(
			$this->pi1->translate('label_your_request'),
			$result
		);
	}

	public function testContactFormStillDisplaysGeneralViewOfTheFormIfAnErrorOccurs() {
		$result = $this->fixture->render(
			array(
				'isSubmitted' => true,
				'requesterName' => 'any name',
				'requesterEmail' => 'requester@valid-email.org',
				'request' => ''
			)
		);

		$this->assertContains(
			$this->pi1->translate('label_no_empty_textarea'),
			$result
		);
		$this->assertNotContains(
			self::$realtyTitle,
			$result
		);
		$this->assertContains(
			$this->pi1->translate('label_your_request'),
			$result
		);
	}

	public function testSpecializedContactFormShowsSubmittedMessageIfAllContentIsValid() {
		$this->assertContains(
			$this->pi1->translate('label_message_sent'),
			$this->fixture->render(
				array(
					'showUid' => $this->realtyUid,
					'isSubmitted' => true,
					'requesterName' => 'any name',
					'requesterEmail' => 'requester@valid-email.org',
					'request' => 'the request'
				)
			)
		);
	}

	public function testGeneralContactFormShowsSubmittedMessageIfAllContentIsValid() {
		$this->assertContains(
			$this->pi1->translate('label_message_sent'),
			$this->fixture->render(
				array(
					'isSubmitted' => true,
					'requesterName' => 'any name',
					'requesterEmail' => 'requester@valid-email.org',
					'request' => 'the request'
				)
			)
		);
	}


	//////////////////////////////////////////////
	// Tests concerning the form fields' values.
	//////////////////////////////////////////////

	public function testUnsubmittedFormDoesNotContainMarkers() {
		$this->assertNotContains(
			'###',
			$this->fixture->render(array('isSubmitted' => false))
		);
	}

	public function testNotSuccessfullySubmittedFormStillContainsSubmittedValueForRequest() {
		$this->assertContains(
			'>the request</textarea>',
			$this->fixture->render(
				array(
					'isSubmitted' => true,
					'request' => 'the request'
				)
			)
		);
	}

	public function testNotSuccessfullySubmittedFormStillContainsSubmittedValueForName() {
		$this->assertContains(
			'value="any name"',
			$this->fixture->render(
				array(
					'isSubmitted' => true,
					'requesterName' => 'any name',
				)
			)
		);
	}

	public function testNotSuccessfullySubmittedFormStillContainsSubmittedValueForPhone() {
		$this->assertContains(
			'value="1234567"',
			$this->fixture->render(
				array(
					'isSubmitted' => true,
					'requesterPhone' => '1234567',
				)
			)
		);
	}

	public function testNotSuccessfullySubmittedFormStillContainsSubmittedValueOfEmail() {
		$this->assertContains(
			'value="requester@valid-email.org"',
			$this->fixture->render(
				array(
					'isSubmitted' => true,
					'requesterEmail' => 'requester@valid-email.org',
				)
			)
		);
	}

	public function testNotSuccessfullySubmittedFormStillContainsSubmittedValueOfFalseEmail() {
		$this->assertContains(
			'value="requester-invalid-email"',
			$this->fixture->render(
				array(
					'isSubmitted' => true,
					'requesterEmail' => 'requester-invalid-email',
				)
			)
		);
	}

	public function testNotSuccessfullySubmittedFormStillContainsSubmittedValueWithHtmlSpecialCharedTags() {
		$this->assertContains(
			'>&lt;fieldset /&gt;the request&lt;script /&gt;</textarea>',
			$this->fixture->render(
				array(
					'isSubmitted' => true,
					'request' => '<fieldset />the request<script />'
				)
			)
		);
	}


	/////////////////////////////////////////////
	// Tests concerning the content of e-mails.
	/////////////////////////////////////////////

	public function testSpecializedContactFormUsesDefaultEmailAddressIfTheObjectHasNoContactData() {
		$this->fixture->render(
			array(
				'showUid' => $this->realtyUid,
				'isSubmitted' => true,
				'requesterName' => 'any name',
				'requesterEmail' => 'requester@valid-email.org',
				'request' => 'the request'
			)
		);

		$this->assertEquals(
			'any-default@email-address.org',
			tx_oelib_mailerFactory::getInstance()->getMailer()->getLastRecipient()
		);
	}

	public function testSpecializedContactFormUsesTheCorrectContactDataWhenDataSourceIsSetToRealtyObject() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->realtyUid,
			array(
				'contact_data_source' => REALTY_CONTACT_FROM_REALTY_OBJECT,
				'contact_person' => 'any contact person',
				'contact_email' => 'any-valid@email-address.org'
			)
		);
		$this->fixture->render(
			array(
				'showUid' => $this->realtyUid,
				'isSubmitted' => true,
				'requesterName' => 'any name',
				'requesterEmail' => 'requester@valid-email.org',
				'request' => 'the request'
			)
		);

		$this->assertEquals(
			'any-valid@email-address.org',
			tx_oelib_mailerFactory::getInstance()->getMailer()->getLastRecipient()
		);
	}

	public function testSpecializedContactFormUsesTheDefaultEmailAddressEmailIfTheOwnersAddressWasNotValid() {
		$ownerUid = $this->testingFramework->createFrontEndUser(
			$this->testingFramework->createFrontEndUserGroup(),
			array('email' => 'invalid-address')
		);
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->realtyUid,
			array(
				'contact_data_source' => REALTY_CONTACT_FROM_OWNER_ACCOUNT,
				'owner' => $ownerUid
			)
		);
		$this->fixture->render(
			array(
				'showUid' => $this->realtyUid,
				'isSubmitted' => true,
				'requesterName' => 'any name',
				'requesterEmail' => 'requester@valid-email.org',
				'request' => 'the request'
			)
		);

		$this->assertEquals(
			'any-default@email-address.org',
			tx_oelib_mailerFactory::getInstance()->getMailer()->getLastRecipient()
		);
	}

	public function testSpecializedContactFormUsesTheDefaultEmailAddressIfTheContactPersonsAddressIsInvalid() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->realtyUid,
			array(
				'contact_person' => 'Mr.Contact',
				'contact_email' => 'invalid-address'
			)
		);
		$this->fixture->render(
			array(
				'showUid' => $this->realtyUid,
				'isSubmitted' => true,
				'requesterName' => 'any name',
				'requesterEmail' => 'requester@valid-email.org',
				'request' => 'the request'
			)
		);

		$this->assertEquals(
			'any-default@email-address.org',
			tx_oelib_mailerFactory::getInstance()->getMailer()->getLastRecipient()
		);
	}

	public function testSpecializedContactFormUsesTheCorrectContactDataWhenDataSourceIsSetToOwner() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->realtyUid,
			array(
				'contact_data_source' => REALTY_CONTACT_FROM_OWNER_ACCOUNT,
				'owner' => $this->feUserId
			)
		);
		$this->fixture->render(
			array(
				'showUid' => $this->realtyUid,
				'isSubmitted' => true,
				'requesterName' => 'any name',
				'requesterEmail' => 'requester@valid-email.org',
				'request' => 'the request'
			)
		);

		$this->assertEquals(
			'frontend-user@valid-email.org',
			tx_oelib_mailerFactory::getInstance()->getMailer()->getLastRecipient()
		);
	}

	public function testSpecializedContactFormUsesDefaultEmailAddressWhenDataSourceIsDeletedOwner() {
		$deletedUserUid = $this->testingFramework->createFrontEndUser(
			$this->testingFramework->createFrontEndUserGroup(),
			array(
				'name' => 'deleted user',
				'email' => 'deleted-user@valid-email.org',
				'telephone' => '7654321',
				'deleted' => 1
			)
		);
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->realtyUid,
			array(
				'contact_data_source' => REALTY_CONTACT_FROM_OWNER_ACCOUNT,
				'owner' => $deletedUserUid
			)
		);
		$this->fixture->render(
			array(
				'showUid' => $this->realtyUid,
				'isSubmitted' => true,
				'requesterName' => 'any name',
				'requesterEmail' => 'requester@valid-email.org',
				'request' => 'the request'
			)
		);

		$this->assertEquals(
			'any-default@email-address.org',
			tx_oelib_mailerFactory::getInstance()->getMailer()->getLastRecipient()
		);
	}

	public function testSpecializedContactFormUsesDefaultEmailAddressForInvalidAddressFromOwnerAccount() {
		$this->testingFramework->changeRecord(
			'fe_users', $this->feUserId, array('email' => 'invalid-email')
		);
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->realtyUid,
			array(
				'contact_data_source' => REALTY_CONTACT_FROM_OWNER_ACCOUNT,
				'owner' => $this->feUserId
			)
		);
		$this->fixture->render(
			array(
				'showUid' => $this->realtyUid,
				'isSubmitted' => true,
				'requesterName' => 'any name',
				'requesterEmail' => 'requester@valid-email.org',
				'request' => 'the request'
			)
		);

		$this->assertEquals(
			'any-default@email-address.org',
			tx_oelib_mailerFactory::getInstance()->getMailer()->getLastRecipient()
		);
	}

	public function testGeneralContactFormUsesTheDefaultEmailAddress() {
		$this->fixture->render(
			array(
				'isSubmitted' => true,
				'requesterName' => 'any name',
				'requesterEmail' => 'requester@valid-email.org',
				'request' => 'the request'
			)
		);

		$this->assertEquals(
			'any-default@email-address.org',
			tx_oelib_mailerFactory::getInstance()->getMailer()->getLastRecipient()
		);
	}

	public function testHeaderContainsNameAndEmailAddress() {
		$this->fixture->render(
			array(
				'isSubmitted' => true,
				'requesterName' => 'any name',
				'requesterEmail' => 'requester@valid-email.org',
				'request' => 'the request'
			)
		);

		$this->assertContains(
			'"any name" <requester@valid-email.org>',
			tx_oelib_mailerFactory::getInstance()->getMailer()->getLastHeaders()
		);
	}

	public function testNameAndEmailAddressAreFetchedAutomaticallyIfAFeUserIsLoggedIn() {
		$this->testingFramework->loginFrontendUser($this->feUserId);
		$this->fixture->render(
			array(
				'showUid' => $this->realtyUid,
				'isSubmitted' => true,
				'request' => 'the request'
			)
		);

		$this->assertContains(
			'"'.self::$feUserTitle.'" <'.self::$feUserEmail.'>',
			tx_oelib_mailerFactory::getInstance()->getMailer()->getLastHeaders()
		);
	}

	public function testTheHeaderContainsABccAddressIfThisWasConfigured() {
		$this->pi1->setConfigurationValue(
			'blindCarbonCopyAddress', 'bcc-address@valid-email.org'
		);
		$this->fixture->render(
			array(
				'isSubmitted' => true,
				'requesterName' => 'any name',
				'requesterEmail' => 'requester@valid-email.org',
				'request' => 'the request'
			)
		);

		$this->assertContains(
			'Bcc: bcc-address@valid-email.org',
			tx_oelib_mailerFactory::getInstance()->getMailer()->getLastHeaders()
		);
	}

	public function testTheHeaderContainsNoBccLineIfNoAddressWasConfigured() {
		$this->fixture->render(
			array(
				'isSubmitted' => true,
				'requesterName' => 'any name',
				'requesterEmail' => 'requester@valid-email.org',
				'request' => 'the request'
			)
		);

		$this->assertNotContains(
			'Bcc:',
			tx_oelib_mailerFactory::getInstance()->getMailer()->getLastHeaders()
		);
	}

	public function testNoEmailIsSentIfTheContactFormWasNotFilledCorrectly() {
		$this->fixture->render(
			array(
				'isSubmitted' => true,
				'requesterName' => 'any name'
			)
		);

		$this->assertEquals(
			array(),
			tx_oelib_mailerFactory::getInstance()->getMailer()->getLastEmail()
		);
	}

	public function testEmailContainsTheTitleOfTheRequestedObjectIfASpecializedContactFormWasSubmitted() {
		$this->fixture->render(
			array(
				'showUid' => $this->realtyUid,
				'isSubmitted' => true,
				'requesterName' => 'any name',
				'requesterEmail' => 'requester@valid-email.org',
				'request' => 'the request'
			)
		);

		$this->assertContains(
			self::$realtyTitle,
			tx_oelib_mailerFactory::getInstance()->getMailer()->getLastBody()
		);
	}

	public function testEmailContainsTheObjectNumberOfTheRequestedObjectIfASpecializedContactFormWasSubmitted() {
		$this->fixture->render(
			array(
				'showUid' => $this->realtyUid,
				'isSubmitted' => true,
				'requesterName' => 'any name',
				'requesterEmail' => 'requester@valid-email.org',
				'request' => 'the request'
			)
		);

		$this->assertContains(
			self::$realtyObjectNumber,
			tx_oelib_mailerFactory::getInstance()->getMailer()->getLastBody()
		);
	}

	public function testEmailFromGeneralContactFormContainsASummaryStringOfTheFavoritesList() {
		$this->fixture->render(
			array(
				'isSubmitted' => true,
				'requesterName' => 'a name of a requester',
				'requesterEmail' => 'requester@valid-email.org',
				'request' => 'the request'
			),
			'summary of favorites'
		);

		$this->assertContains(
			'summary of favorites',
			tx_oelib_mailerFactory::getInstance()->getMailer()->getLastBody()
		);
	}

	public function testEmailWithMinimumContentContainsNoUnreplacedMarkers() {
		$this->fixture->render(
			array(
				'showUid' => $this->realtyUid,
				'isSubmitted' => true,
				'requesterName' => 'any name',
				'requesterEmail' => 'requester@valid-email.org',
				'request' => 'the request',
			)
		);

		$this->assertNotContains(
			'###',
			tx_oelib_mailerFactory::getInstance()->getMailer()->getLastBody()
		);
	}

	public function testEmailBodyContainsTheRequestersName() {
		$this->fixture->render(
			array(
				'isSubmitted' => true,
				'requesterName' => 'a name of a requester',
				'requesterEmail' => 'requester@valid-email.org',
				'request' => 'the request'
			)
		);

		$this->assertContains(
			'a name of a requester',
			tx_oelib_mailerFactory::getInstance()->getMailer()->getLastBody()
		);
	}

	public function testEmailBodyContainsTheRequestersEmailAddress() {
		$this->fixture->render(
			array(
				'isSubmitted' => true,
				'requesterName' => 'a name of a requester',
				'requesterEmail' => 'requester@valid-email.org',
				'request' => 'the request'
			)
		);

		$this->assertContains(
			'requester@valid-email.org',
			tx_oelib_mailerFactory::getInstance()->getMailer()->getLastBody()
		);
	}

	public function testEmailBodyContainsTheRequestersPhoneNumber() {
		$this->fixture->render(
			array(
				'isSubmitted' => true,
				'requesterName' => 'a name of a requester',
				'requesterEmail' => 'requester@valid-email.org',
				'requesterPhone' => '1234567',
				'request' => 'the request'
			)
		);

		$this->assertContains(
			'1234567',
			tx_oelib_mailerFactory::getInstance()->getMailer()->getLastBody()
		);
	}

	public function testEmailBodyNotContainsThePhoneNumberLabelIfNoPhoneNumberWasSet() {
		$this->fixture->render(
			array(
				'isSubmitted' => true,
				'requesterName' => 'a name of a requester',
				'requesterEmail' => 'requester@valid-email.org',
				'request' => 'the request'
			)
		);

		$this->assertNotContains(
			$this->pi1->translate('label_requester_phone'),
			tx_oelib_mailerFactory::getInstance()->getMailer()->getLastBody()
		);
	}

	public function testEmailSubjectIsGeneralForTheGeneralForm() {
		$this->fixture->render(
			array(
				'isSubmitted' => true,
				'requesterName' => 'a name of a requester',
				'requesterEmail' => 'requester@valid-email.org',
				'request' => 'the request'
			)
		);

		$this->assertContains(
			$this->pi1->translate('label_email_subject_general'),
			tx_oelib_mailerFactory::getInstance()->getMailer()->getLastSubject()
		);
	}

	public function testEmailSubjectIsSpecializedForTheSpecializedForm() {
		$this->fixture->render(
			array(
				'showUid' => $this->realtyUid,
				'isSubmitted' => true,
				'requesterName' => 'any name',
				'requesterEmail' => 'requester@valid-email.org',
				'request' => 'the request'
			)
		);

		$this->assertContains(
			self::$realtyObjectNumber,
			tx_oelib_mailerFactory::getInstance()->getMailer()->getLastSubject()
		);
	}
}
?>