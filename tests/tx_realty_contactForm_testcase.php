<?php
/***************************************************************
* Copyright notice
*
* (c) 2008-2009 Saskia Metzler <saskia@merlin.owl.de>
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
require_once(t3lib_extMgm::extPath('realty') . 'pi1/class.tx_realty_contactForm.php');

/**
 * Unit tests for the tx_realty_contactForm class in the 'realty' extension.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 */
class tx_realty_contactForm_testcase extends tx_phpunit_testcase {
	/**
	 * @var tx_realty_contactform
	 */
	private $fixture;

	/**
	 * @var tx_oelib_testingFramework
	 */
	private $testingFramework;

	/**
	 * @var integer dummy realty object ID
	 */
	private $realtyUid;

	/**
	 * @var string title for the dummy realty object
	 */
	const REALTY_TITLE = 'test title';

	/**
	 * @var string object number for the dummy realty object
	 */
	const REALTY_OBJECT_NUMBER = '1234567';

	public function setUp() {
		$this->testingFramework = new tx_oelib_testingFramework('tx_realty');
		$this->testingFramework->createFakeFrontEnd();
		$this->realtyUid = $this->testingFramework->createRecord(
			REALTY_TABLE_OBJECTS,
			array(
				'title' => self::REALTY_TITLE,
				'object_number' => self::REALTY_OBJECT_NUMBER,
			)
		);
		tx_oelib_mailerFactory::getInstance()->enableTestMode();

		$this->fixture = new tx_realty_contactForm(
			array('templateFile' => 'EXT:realty/pi1/tx_realty_pi1.tpl.htm'),
			$GLOBALS['TSFE']->cObj
		);

		$this->fixture->setConfigurationValue(
			'defaultContactEmail', 'any-default@email-address.org'
		);
		$this->fixture->setConfigurationValue('blindCarbonCopyAddress', '');
		$this->fixture->setConfigurationValue(
			'visibleContactFormFields', 'name,street,zip_and_city,telephone'
		);
		$this->fixture->setConfigurationValue('requiredContactFormFields', '');
	}

	public function tearDown() {
		$this->testingFramework->cleanUp();
		tx_oelib_mailerFactory::getInstance()->discardInstance();

		$this->fixture->__destruct();
		unset($this->fixture, $this->testingFramework);
	}


	/////////////////////////////////////////////////////////
	// Tests concerning view-dependently displayed strings.
	/////////////////////////////////////////////////////////

	public function testSpecializedContactFormContainsObjectTitle() {
		$this->assertContains(
			self::REALTY_TITLE,
			$this->fixture->render(
				array('showUid' => $this->realtyUid)
			)
		);
	}

	public function testSpecializedContactFormContainsObjectNumber() {
		$this->assertContains(
			self::REALTY_OBJECT_NUMBER,
			$this->fixture->render(
				array('showUid' => $this->realtyUid)
			)
		);
	}

	public function testGeneralContactFormDoesNotContainTitleLabelWithoutRealtyObjectSet() {
		$this->assertNotContains(
			$this->fixture->translate('label_title'),
			$this->fixture->render()
		);
	}

	public function testGeneralContactFormDoesNotContainObjectNumberLabelWithoutRealtyObjectSet() {
		$this->assertNotContains(
			$this->fixture->translate('label_object_number'),
			$this->fixture->render()
		);
	}

	public function testSpecializedContactFormHasNoDisabledFieldsIfNotLoggedIn() {
		$this->assertNotContains(
			'disabled',
			$this->fixture->render(array('showUid' => $this->realtyUid))
		);
	}

	public function testGeneralContactFormHasNoDisabledFieldsIfNotLoggedIn() {
		$this->assertNotContains(
			'disabled',
			$this->fixture->render()
		);
	}

	public function testSpecializedContactFormHasDisabledNameFieldIfLoggedIn() {
		$this->testingFramework->createAndLoginFrontEndUser(
			'', array('name' => 'test user')
		);

		$this->assertContains(
			'value="test user" disabled="disabled"',
			 $this->fixture->render(array('showUid' => $this->realtyUid))
		);
	}

	public function testContactFormHasNoNameFieldIfLoggedInButNameIsDisabledByConfiguration() {
		$this->testingFramework->createAndLoginFrontEndUser(
			'', array('name' => 'test user')
		);
		$this->fixture->setConfigurationValue('visibleContactFormFields', '');

		$this->assertNotContains(
			'value="test user"',
			 $this->fixture->render(array('showUid' => $this->realtyUid))
		);
	}

	public function testSpecializedContactFormHasDisabledEmailFieldIfLoggedIn() {
		$this->testingFramework->createAndLoginFrontEndUser(
			'', array('email' => 'frontend-user@valid-email.org')
		);

		$this->assertContains(
			'value="frontend-user@valid-email.org" disabled="disabled"',
			 $this->fixture->render(array('showUid' => $this->realtyUid))
		);
	}

	public function testGeneralContactFormHasDisabledNameFieldIfLoggedIn() {
		$this->testingFramework->createAndLoginFrontEndUser(
			'', array('name' => 'test user')
		);

		$this->assertContains(
			'value="test user" disabled="disabled"',
			 $this->fixture->render()
		);
	}

	public function testGeneralContactFormHasDisabledEmailFieldIfLoggedIn() {
		$this->testingFramework->createAndLoginFrontEndUser(
			'', array('email' => 'frontend-user@valid-email.org')
		);

		$this->assertContains(
			'value="frontend-user@valid-email.org" disabled="disabled"',
			 $this->fixture->render()
		);
	}

	public function testSpecializedContactFormHasNoDisabledInfomationIfNotLoggedIn() {
		$this->assertNotContains(
			$this->fixture->translate('label_requester_data_is_uneditable'),
			$this->fixture->render(array('showUid' => $this->realtyUid))
		);
	}

	public function testGeneralContactHasNoDisabledInfomationIfNotLoggedIn() {
		$this->assertNotContains(
			$this->fixture->translate('label_requester_data_is_uneditable'),
			$this->fixture->render()
		);
	}

	public function testSpecializedContactFormHasDisabledInfomationIfLoggedIn() {
		$this->testingFramework->createAndLoginFrontEndUser(
			'', array('name' => 'test user')
		);

		$this->assertContains(
			$this->fixture->translate('label_requester_data_is_uneditable'),
			$this->fixture->render(array('showUid' => $this->realtyUid))
		);
	}

	public function testGeneralContactFormHasDisabledInfomationIfLoggedIn() {
		$this->testingFramework->createAndLoginFrontEndUser(
			'', array('name' => 'test user')
		);

		$this->assertContains(
			$this->fixture->translate('label_requester_data_is_uneditable'),
			$this->fixture->render()
		);
	}

	public function testContactFormDisplaysGeneralViewIfTheRealtyObjectUidWasNotNumeric() {
		$this->assertNotContains(
			$this->fixture->translate('label_object_number'),
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
			$this->fixture->translate('label_object_number'),
			$this->fixture->render(
				array('showUid' => $this->testingFramework->createRecord(
					REALTY_TABLE_OBJECTS, array('deleted' => 1)
				))
			)
		);
	}

	public function testSpecializedContactFormNotDisplaysTitleLabelIfRealtyObjectDoesNotExist() {
		$this->assertNotContains(
			$this->fixture->translate('label_title'),
			$this->fixture->render(
				array('showUid' => $this->testingFramework->createRecord(
					REALTY_TABLE_OBJECTS, array('deleted' => 1)
				))
			)
		);
	}

	public function testSpecializedContactFormNotDisplaysSubmitLabelIfRealtyObjectDoesNotExist() {
		$this->assertNotContains(
			$this->fixture->translate('label_submit'),
			$this->fixture->render(array(
				'showUid' => $this->testingFramework->createRecord(
					REALTY_TABLE_OBJECTS, array('deleted' => 1)
				))
			)
		);
	}

	public function testSpecializedContactFormNotDisplaysYourNameLabelIfRealtyObjectDoesNotExist() {
		$this->assertNotContains(
			$this->fixture->translate('label_your_name'),
			$this->fixture->render(array(
				'showUid' => $this->testingFramework->createRecord(
					REALTY_TABLE_OBJECTS, array('deleted' => 1)
				))
			)
		);
	}

	public function testContactFormDisplaysYourNameLabelIfThisIsConfigured() {
		$this->assertContains(
			$this->fixture->translate('label_your_name'),
			$this->fixture->render()
		);
	}

	public function testContactFormNotDisplaysYourNameLabelIfThisIsNotConfigured() {
		$this->fixture->setConfigurationValue('visibleContactFormFields', '');

		$this->assertNotContains(
			$this->fixture->translate('label_your_name'),
			$this->fixture->render()
		);
	}


	///////////////////////////////////////
	// Tests concerning (error) messages.
	///////////////////////////////////////

	public function testSpecializedContactFormDisplaysAnErrorIfRealtyObjectDoesNotExist() {
		$this->assertContains(
			$this->fixture->translate('message_noResultsFound_contact_form'),
			$this->fixture->render(
				array('showUid' => $this->testingFramework->createRecord(
					REALTY_TABLE_OBJECTS, array('deleted' => 1)
				))
			)
		);
	}

	public function testSpecializedContactFormDisplaysErrorAfterSubmittingIfNoValidEmailAddressWasProvided() {
		$this->assertContains(
			$this->fixture->translate('label_set_valid_email_address'),
			$this->fixture->render(
				array(
					'showUid' => $this->realtyUid,
					'isSubmitted' => true,
					'requesterName' => 'any name',
					'requesterEmail' => 'requester-invalid-email',
					'request' => 'the request',

				)
			)
		);
	}

	public function testGeneralContactFormDisplaysErrorAfterSubmittingIfNoValidEmailAddressWasProvided() {
		$this->assertContains(
			$this->fixture->translate('label_set_valid_email_address'),
			$this->fixture->render(
				array(
					'isSubmitted' => true,
					'requesterName' => 'any name',
					'requesterEmail' => 'requester-invalid-email',
					'request' => 'the request',
				)
			)
		);
	}

	public function testSpecializedContactFormDisplaysErrorAfterSubmittingIfHeaderInjectionWasAttemptedInTheEmailField() {
		$this->assertContains(
			$this->fixture->translate('label_set_valid_email_address'),
			$this->fixture->render(
				array(
					'showUid' => $this->realtyUid,
					'isSubmitted' => true,
					'requesterName' => 'any name',
					'requesterEmail' => 'requester@valid-email.org' . LF . 'anything',
					'request' => 'the request',

				)
			)
		);
	}

	public function testSpecializedContactFormDisplaysErrorAfterSubmittingIfHeaderInjectionWasAttemptedInTheNameField() {
		$this->assertContains(
			$this->fixture->translate('label_set_name'),
			$this->fixture->render(
				array(
					'showUid' => $this->realtyUid,
					'isSubmitted' => true,
					'requesterName' => 'any name' . LF . 'anything',
					'requesterEmail' => 'requester@valid-email.org',
					'request' => 'the request',

				)
			)
		);
	}

	public function testContactFormDisplaysErrorAfterSubmittingIfAngleBracketsAreSetInTheNameField() {
		$this->assertContains(
			$this->fixture->translate('label_set_name'),
			$this->fixture->render(
				array(
					'showUid' => $this->realtyUid,
					'isSubmitted' => true,
					'requesterName' => 'any name < anything',
					'requesterEmail' => 'requester@valid-email.org',
					'request' => 'the request',

				)
			)
		);
	}

	public function testContactFormDisplaysErrorAfterSubmittingIfQuotationMarksAreSetInTheNameField() {
		$this->assertContains(
			$this->fixture->translate('label_set_name'),
			$this->fixture->render(
				array(
					'showUid' => $this->realtyUid,
					'isSubmitted' => true,
					'requesterName' => 'any name " anything',
					'requesterEmail' => 'requester@valid-email.org',
					'request' => 'the request',

				)
			)
		);
	}

	public function testSpecializedContactFormDisplaysErrorAfterSubmittingIfNoNameWasProvidedButIsRequired() {
		$this->fixture->setConfigurationValue('requiredContactFormFields', 'name');

		$this->assertContains(
			$this->fixture->translate('message_required_field'),
			$this->fixture->render(
				array(
					'showUid' => $this->realtyUid,
					'isSubmitted' => true,
					'requesterName' => '',
					'requesterEmail' => 'requester@valid-email.org',
					'request' => 'the request',
				)
			)
		);
	}

	public function testGeneralContactFormDisplaysErrorAfterSubmittingIfNoNameWasProvidedButIsRequired() {
		$this->fixture->setConfigurationValue('requiredContactFormFields', 'name');

		$this->assertContains(
			$this->fixture->translate('message_required_field'),
			$this->fixture->render(
				array(
					'isSubmitted' => true,
					'requesterName' => '',
					'requesterEmail' => 'requester@valid-email.org',
					'request' => 'the request',
				)
			)
		);
	}

	public function testSpecializedContactFormDisplaysErrorAfterSubmittingIfTheRequestWasEmpty() {
		$this->assertContains(
			$this->fixture->translate('label_no_empty_textarea'),
			$this->fixture->render(
				array(
					'showUid' => $this->realtyUid,
					'isSubmitted' => true,
					'requesterName' => 'any name',
					'requesterEmail' => 'requester@valid-email.org',
					'request' => '',
				)
			)
		);
	}

	public function testGeneralContactFormDisplaysErrorAfterSubmittingIfTheRequestWasEmpty() {
		$this->assertContains(
			$this->fixture->translate('label_no_empty_textarea'),
			$this->fixture->render(
				array(
					'isSubmitted' => true,
					'requesterName' => 'any name',
					'requesterEmail' => 'requester@valid-email.org',
					'request' => '',
				)
			)
		);
	}

	public function testSpecializedContactFormDisplaysErrorAfterSubmittingIfTheObjectHasNoContactDataAndNoDefaultEmailWasSet() {
		$this->fixture->setConfigurationValue('defaultContactEmail', '');

		$this->assertContains(
			$this->fixture->translate('label_no_contact_person'),
			$this->fixture->render(
				array(
					'showUid' => $this->realtyUid,
					'isSubmitted' => true,
					'requesterName' => 'any name',
					'requesterEmail' => 'requester@valid-email.org',
					'request' => 'the request',
				)
			)
		);
	}

	public function testGeneralContactFormDisplaysAnErrorAfterSubmittingIfNoDefaultEmailAddressWasSet() {
		$this->fixture->setConfigurationValue('defaultContactEmail', '');

		$this->assertContains(
			$this->fixture->translate('label_no_contact_person'),
			$this->fixture->render(
				array(
					'isSubmitted' => true,
					'requesterName' => 'any name',
					'requesterEmail' => 'requester@valid-email.org',
					'request' => 'the request',
				)
			)
		);
	}

	public function testContactFormDisplaysTwoErrorMessagesIfNameAndStreetAreRequiredButEmpty() {
		$this->fixture->setConfigurationValue('requiredContactFormFields', 'name,street');

		$this->assertEquals(
			2,
			substr_count(
				 $this->fixture->render(
					array(
						'isSubmitted' => true,
						'requesterName' => '',
						'requesterEmail' => 'requester@valid-email.org',
						'request' => 'foo',
					)
				),
				$this->fixture->translate('message_required_field')
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
				'request' => '',
			)
		);

		$this->assertContains(
			$this->fixture->translate('label_no_empty_textarea'),
			$result
		);
		$this->assertContains(
			self::REALTY_TITLE,
			$result
		);
		$this->assertContains(
			$this->fixture->translate('label_your_request'),
			$result
		);
	}

	public function testContactFormStillDisplaysGeneralViewOfTheFormIfAnErrorOccurs() {
		$result = $this->fixture->render(
			array(
				'isSubmitted' => true,
				'requesterName' => 'any name',
				'requesterEmail' => 'requester@valid-email.org',
				'request' => '',
			)
		);

		$this->assertContains(
			$this->fixture->translate('label_no_empty_textarea'),
			$result
		);
		$this->assertNotContains(
			self::REALTY_TITLE,
			$result
		);
		$this->assertContains(
			$this->fixture->translate('label_your_request'),
			$result
		);
	}

	public function testSpecializedContactFormShowsSubmittedMessageIfAllContentIsValid() {
		$this->assertContains(
			$this->fixture->translate('label_message_sent'),
			$this->fixture->render(
				array(
					'showUid' => $this->realtyUid,
					'isSubmitted' => true,
					'requesterName' => 'any name',
					'requesterEmail' => 'requester@valid-email.org',
					'request' => 'the request',
				)
			)
		);
	}

	public function testGeneralContactFormShowsSubmittedMessageIfAllContentIsValid() {
		$this->assertContains(
			$this->fixture->translate('label_message_sent'),
			$this->fixture->render(
				array(
					'isSubmitted' => true,
					'requesterName' => 'any name',
					'requesterEmail' => 'requester@valid-email.org',
					'request' => 'the request',
				)
			)
		);
	}

	public function testContactFormDisplaysErrorMessageForEmptyRequiredStreetField() {
		$this->fixture->setConfigurationValue('requiredContactFormFields', 'street');

		$this->assertContains(
			$this->fixture->translate('message_required_field'),
			$this->fixture->render(
				array(
					'isSubmitted' => true,
					'requesterEmail' => 'requester@valid-email.org',
					'request' => 'foo bar',
					'requesterStreet' => '',
				)
			)
		);
	}

	public function testContactFormDisplaysErrorMessageForEmptyRequiredCityField() {
		$this->fixture->setConfigurationValue('requiredContactFormFields', 'city');

		$this->assertContains(
			$this->fixture->translate('message_required_field_requesterCity'),
			$this->fixture->render(
				array(
					'isSubmitted' => true,
					'requesterEmail' => 'requester@valid-email.org',
					'request' => 'foo bar',
					'requesterCity' => '',
				)
			)
		);
	}

	public function testContactFormDisplaysNoErrorMessageForNonEmptyRequiredField() {
		$this->fixture->setConfigurationValue('requiredContactFormFields', 'street');

		$this->assertNotContains(
			$this->fixture->translate('message_required_field'),
			$this->fixture->render(
				array(
					'isSubmitted' => true,
					'requesterEmail' => 'requester@valid-email.org',
					'request' => 'foo bar',
					'requesterStreet' => 'main street',
				)
			)
		);
	}


	///////////////////////////////////////////
	// Tests for generally displayed strings.
	///////////////////////////////////////////

	public function testFormWithMinimalContentDoesNotContainUnreplacedMarkers() {
		$this->assertNotContains(
			'###',
			$this->fixture->render()
		);
	}

	public function testFormHasInputFieldForStreet() {
		$this->assertContains(
			'tx_realty_pi1[requesterStreet]',
			$this->fixture->render()
		);
	}

	public function testFormHasInputFieldForZip() {
		$this->assertContains(
			'tx_realty_pi1[requesterStreet]',
			$this->fixture->render()
		);
	}

	public function testFormHasInputFieldForCity() {
		$this->assertContains(
			'tx_realty_pi1[requesterStreet]',
			$this->fixture->render()
		);
	}


	//////////////////////////////////////////////
	// Tests concerning the form fields' values.
	//////////////////////////////////////////////

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
					'request' => '<fieldset />the request<script />',
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
				'request' => 'the request',
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
				'contact_email' => 'any-valid@email-address.org',
			)
		);
		$this->fixture->render(
			array(
				'showUid' => $this->realtyUid,
				'isSubmitted' => true,
				'requesterName' => 'any name',
				'requesterEmail' => 'requester@valid-email.org',
				'request' => 'the request',
			)
		);

		$this->assertEquals(
			'any-valid@email-address.org',
			tx_oelib_mailerFactory::getInstance()->getMailer()->getLastRecipient()
		);
	}

	public function testSpecializedContactFormUsesTheDefaultEmailAddressEmailIfTheOwnersAddressWasNotValid() {
		$ownerUid = $this->testingFramework->createFrontEndUser(
			'', array('email' => 'invalid-address')
		);
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->realtyUid,
			array(
				'contact_data_source' => REALTY_CONTACT_FROM_OWNER_ACCOUNT,
				'owner' => $ownerUid,
			)
		);
		$this->fixture->render(
			array(
				'showUid' => $this->realtyUid,
				'isSubmitted' => true,
				'requesterName' => 'any name',
				'requesterEmail' => 'requester@valid-email.org',
				'request' => 'the request',
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
				'contact_email' => 'invalid-address',
			)
		);
		$this->fixture->render(
			array(
				'showUid' => $this->realtyUid,
				'isSubmitted' => true,
				'requesterName' => 'any name',
				'requesterEmail' => 'requester@valid-email.org',
				'request' => 'the request',
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
				'owner' => $this->testingFramework->createFrontEndUser(
					'', array('email' => 'frontend-user@valid-email.org')
				),
			)
		);

		$this->fixture->render(
			array(
				'showUid' => $this->realtyUid,
				'isSubmitted' => true,
				'requesterName' => 'any name',
				'requesterEmail' => 'requester@valid-email.org',
				'request' => 'the request',
			)
		);

		$this->assertEquals(
			'frontend-user@valid-email.org',
			tx_oelib_mailerFactory::getInstance()->getMailer()->getLastRecipient()
		);
	}

	public function testSpecializedContactFormUsesDefaultEmailAddressWhenDataSourceIsDeletedOwner() {
		$deletedUserUid = $this->testingFramework->createFrontEndUser(
			'',
			array(
				'name' => 'deleted user',
				'email' => 'deleted-user@valid-email.org',
				'telephone' => '7654321',
				'deleted' => 1,
			)
		);
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->realtyUid,
			array(
				'contact_data_source' => REALTY_CONTACT_FROM_OWNER_ACCOUNT,
				'owner' => $deletedUserUid,
			)
		);
		$this->fixture->render(
			array(
				'showUid' => $this->realtyUid,
				'isSubmitted' => true,
				'requesterName' => 'any name',
				'requesterEmail' => 'requester@valid-email.org',
				'request' => 'the request',
			)
		);

		$this->assertEquals(
			'any-default@email-address.org',
			tx_oelib_mailerFactory::getInstance()->getMailer()->getLastRecipient()
		);
	}

	public function testSpecializedContactFormUsesDefaultEmailAddressForInvalidAddressFromOwnerAccount() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->realtyUid,
			array(
				'contact_data_source' => REALTY_CONTACT_FROM_OWNER_ACCOUNT,
				'owner' => $this->testingFramework->createFrontEndUser(
					'', array('email' => 'invalid-email')
				),
			)
		);
		$this->fixture->render(
			array(
				'showUid' => $this->realtyUid,
				'isSubmitted' => true,
				'requesterName' => 'any name',
				'requesterEmail' => 'requester@valid-email.org',
				'request' => 'the request',
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
				'request' => 'the request',
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
				'request' => 'the request',
			)
		);

		$this->assertContains(
			'"any name" <requester@valid-email.org>',
			tx_oelib_mailerFactory::getInstance()->getMailer()->getLastHeaders()
		);
	}

	public function testNameAndEmailAddressAreFetchedAutomaticallyAsSenderIfAFeUserIsLoggedIn() {
		$this->testingFramework->createAndLoginFrontEndUser(
			'',
			array(
				'name' => 'test user',
				'email' => 'frontend-user@valid-email.org',
			)
		);
		$this->fixture->render(
			array(
				'showUid' => $this->realtyUid,
				'isSubmitted' => true,
				'request' => 'the request',
			)
		);

		$this->assertContains(
			'"test user" <frontend-user@valid-email.org>',
			tx_oelib_mailerFactory::getInstance()->getMailer()->getLastHeaders()
		);
	}

	public function testEmailAddressIsFetchedAutomaticallyAsSenderIfAFeUserIsLoggedInAndNoUserNameSet() {
		$this->testingFramework->createAndLoginFrontEndUser(
			'',
			array(
				'name' => '',
				'email' => 'frontend-user@valid-email.org',
			)
		);
		$this->fixture->render(
			array(
				'showUid' => $this->realtyUid,
				'isSubmitted' => true,
				'request' => 'the request',
			)
		);

		$this->assertContains(
			'frontend-user@valid-email.org',
			tx_oelib_mailerFactory::getInstance()->getMailer()->getLastHeaders()
		);
	}

	public function testSenderDoesNotContainTheNameIfAFeUserIsLoggedAndUserNameVisibilityDisabled() {
		$this->testingFramework->createAndLoginFrontEndUser(
			'',
			array(
				'name' => 'user name',
				'email' => 'frontend-user@valid-email.org',
			)
		);

		$this->fixture->setConfigurationValue('visibleContactFormFields', '');

		$this->fixture->render(
			array(
				'showUid' => $this->realtyUid,
				'isSubmitted' => true,
				'request' => 'the request',
			)
		);

		$this->assertNotContains(
			'test user',
			tx_oelib_mailerFactory::getInstance()->getMailer()->getLastHeaders()
		);
	}

	public function testNoQuotesAreSetForTheSenderIfAFeUserIsLoggedInAndNoUserNameSet() {
		$this->testingFramework->createAndLoginFrontEndUser(
			'',
			array(
				'name' => '',
				'email' => 'frontend-user@valid-email.org',
			)
		);
		$this->fixture->render(
			array(
				'showUid' => $this->realtyUid,
				'isSubmitted' => true,
				'request' => 'the request',
			)
		);

		$this->assertNotContains(
			'"',
			tx_oelib_mailerFactory::getInstance()->getMailer()->getLastHeaders()
		);
	}

	public function testNoAngleBracketsAreSetForTheSenderIfAFeUserIsLoggedInAndNoUserNameSet() {
		$this->testingFramework->createAndLoginFrontEndUser(
			'',
			array(
				'name' => '',
				'email' => 'frontend-user@valid-email.org',
			)
		);
		$this->fixture->render(
			array(
				'showUid' => $this->realtyUid,
				'isSubmitted' => true,
				'request' => 'the request',
			)
		);

		$this->assertNotContains(
			'<',
			tx_oelib_mailerFactory::getInstance()->getMailer()->getLastHeaders()
		);
		$this->assertNotContains(
			'>',
			tx_oelib_mailerFactory::getInstance()->getMailer()->getLastHeaders()
		);
	}

	public function testTheHeaderContainsABccAddressIfThisWasConfigured() {
		$this->fixture->setConfigurationValue(
			'blindCarbonCopyAddress', 'bcc-address@valid-email.org'
		);
		$this->fixture->render(
			array(
				'isSubmitted' => true,
				'requesterName' => 'any name',
				'requesterEmail' => 'requester@valid-email.org',
				'request' => 'the request',
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
				'request' => 'the request',
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
				'requesterName' => 'any name',
			)
		);

		$this->assertEquals(
			array(),
			tx_oelib_mailerFactory::getInstance()->getMailer()->getLastEmail()
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

	public function testEmailContainsTheTitleOfTheRequestedObjectIfASpecializedContactFormWasSubmitted() {
		$this->fixture->render(
			array(
				'showUid' => $this->realtyUid,
				'isSubmitted' => true,
				'requesterName' => 'any name',
				'requesterEmail' => 'requester@valid-email.org',
				'request' => 'the request',
			)
		);

		$this->assertContains(
			self::REALTY_TITLE,
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
				'request' => 'the request',
			)
		);

		$this->assertContains(
			self::REALTY_OBJECT_NUMBER,
			tx_oelib_mailerFactory::getInstance()->getMailer()->getLastBody()
		);
	}

	public function testEmailFromGeneralContactFormContainsASummaryStringOfTheFavoritesList() {
		$this->fixture->render(
			array(
				'isSubmitted' => true,
				'requesterName' => 'a name of a requester',
				'requesterEmail' => 'requester@valid-email.org',
				'request' => 'the request',
				'summaryStringOfFavorites' => 'summary of favorites',
			)
		);

		$this->assertContains(
			'summary of favorites',
			tx_oelib_mailerFactory::getInstance()->getMailer()->getLastBody()
		);
	}

	public function testEmailBodyContainsTheRequestersName() {
		$this->fixture->render(
			array(
				'isSubmitted' => true,
				'requesterName' => 'a name of a requester',
				'requesterEmail' => 'requester@valid-email.org',
				'request' => 'the request',
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
				'request' => 'the request',
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
				'request' => 'the request',
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
				'request' => 'the request',
			)
		);

		$this->assertNotContains(
			$this->fixture->translate('label_requester_phone'),
			tx_oelib_mailerFactory::getInstance()->getMailer()->getLastBody()
		);
	}

	public function testEmailBodyContainsTheRequestersStreet() {
		$this->fixture->render(
			array(
				'isSubmitted' => true,
				'requesterName' => 'a name of a requester',
				'requesterEmail' => 'requester@valid-email.org',
				'requesterStreet' => 'main street',
				'request' => 'the request',
			)
		);

		$this->assertContains(
			'main street',
			tx_oelib_mailerFactory::getInstance()->getMailer()->getLastBody()
		);
	}

	public function testEmailBodyContainsTheRequestersZip() {
		$this->fixture->render(
			array(
				'isSubmitted' => true,
				'requesterName' => 'a name of a requester',
				'requesterEmail' => 'requester@valid-email.org',
				'requesterZip' => '12345',
				'request' => 'the request',
			)
		);

		$this->assertContains(
			'12345',
			tx_oelib_mailerFactory::getInstance()->getMailer()->getLastBody()
		);
	}

	public function testEmailBodyContainsTheRequestersCity() {
		$this->fixture->render(
			array(
				'isSubmitted' => true,
				'requesterName' => 'a name of a requester',
				'requesterEmail' => 'requester@valid-email.org',
				'requesterCity' => 'a city',
				'request' => 'the request',
			)
		);

		$this->assertContains(
			'a city',
			tx_oelib_mailerFactory::getInstance()->getMailer()->getLastBody()
		);
	}

	public function testEmailSubjectIsGeneralForTheGeneralForm() {
		$this->fixture->render(
			array(
				'isSubmitted' => true,
				'requesterName' => 'a name of a requester',
				'requesterEmail' => 'requester@valid-email.org',
				'request' => 'the request',
			)
		);

		$this->assertContains(
			$this->fixture->translate('label_email_subject_general'),
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
				'request' => 'the request',
			)
		);

		$this->assertContains(
			self::REALTY_OBJECT_NUMBER,
			tx_oelib_mailerFactory::getInstance()->getMailer()->getLastSubject()
		);
	}
}
?>