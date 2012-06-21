<?php
/***************************************************************
* Copyright notice
*
* (c) 2008-2012 Saskia Metzler <saskia@merlin.owl.de>
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
 * Unit tests for the tx_realty_contactForm class in the "realty" extension.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class tx_realty_FrontEnd_ContactFormTest extends tx_phpunit_testcase {
	/**
	 * @var tx_realty_contactForm
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
			$this->createContentMock()
		);

		$this->fixture->setConfigurationValue(
			'defaultContactEmail', 'any-default@email-address.org'
		);
		$this->fixture->setConfigurationValue('blindCarbonCopyAddress', '');
		$this->fixture->setConfigurationValue(
			'visibleContactFormFields',
			'name,street,zip_and_city,telephone,request,viewing,information,callback'
		);
		$this->fixture->setConfigurationValue(
			'requiredContactFormFields', 'request'
		);
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
	 * Creates a mock content object that can create URLs in the following
	 * form:
	 *
	 * index.php?id=42
	 *
	 * The page ID isn't checked for existence. So any page ID can be used.
	 *
	 * @return tslib_cObj a mock content object
	 */
	private function createContentMock() {
		$mock = $this->getMock('tslib_cObj', array('getTypoLink_URL'));
		$mock->expects($this->any())->method('getTypoLink_URL')
			->will($this->returnCallback(array($this, 'getTypoLinkUrl')));

		return $mock;
	}

	/**
	 * Callback function for creating mock typolink URLs.
	 *
	 * @param integer $pageId the page ID to link to, must be >= 0
	 *
	 * @return string faked URL, will not be empty
	 */
	public function getTypoLinkUrl($pageId) {
		return 'index.php?id=' . $pageId;
	}


	/////////////////////////////////////
	// Tests for the utility functions.
	/////////////////////////////////////

	/**
	 * @test
	 */
	public function createContentMockCreatesContentInstance() {
		$this->assertTrue(
			$this->createContentMock() instanceof tslib_cObj
		);
	}

	/**
	 * @test
	 */
	public function createTypoLinkInContentMockCreatesUrlToPageId() {
		$contentMock = $this->createContentMock();

		$this->assertContains(
			'index.php?id=42',
			$contentMock->getTypoLink_URL(42)
		);
	}


	/////////////////////////////////////////////////////////
	// Tests concerning view-dependently displayed strings.
	/////////////////////////////////////////////////////////

	/**
	 * @test
	 */
	public function specializedContactFormContainsObjectTitle() {
		$this->assertContains(
			self::REALTY_TITLE,
			$this->fixture->render(
				array('showUid' => $this->realtyUid)
			)
		);
	}

	/**
	 * @test
	 */
	public function specializedContactFormContainsObjectNumber() {
		$this->assertContains(
			self::REALTY_OBJECT_NUMBER,
			$this->fixture->render(
				array('showUid' => $this->realtyUid)
			)
		);
	}

	/**
	 * @test
	 */
	public function generalContactFormDoesNotContainTitleLabelWithoutRealtyObjectSet() {
		$this->assertNotContains(
			$this->fixture->translate('label_title'),
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function generalContactFormDoesNotContainObjectNumberLabelWithoutRealtyObjectSet() {
		$this->assertNotContains(
			$this->fixture->translate('label_object_number'),
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function specializedContactFormHasNoDisabledFieldsIfNotLoggedIn() {
		$this->assertNotContains(
			'disabled',
			$this->fixture->render(array('showUid' => $this->realtyUid))
		);
	}

	/**
	 * @test
	 */
	public function generalContactFormHasNoDisabledFieldsIfNotLoggedIn() {
		$this->assertNotContains(
			'disabled',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function specializedContactFormHasDisabledNameFieldIfLoggedIn() {
		$user = new tx_realty_Model_FrontEndUser();
		$user->setData(array('name' => 'test user'));
		tx_oelib_FrontEndLoginManager::getInstance()->logInUser($user);

		$this->assertContains(
			'value="test user" disabled="disabled"',
			 $this->fixture->render(array('showUid' => $this->realtyUid))
		);
	}

	/**
	 * @test
	 */
	public function contactFormHasNoNameFieldIfLoggedInButNameIsDisabledByConfiguration() {
		$user = new tx_realty_Model_FrontEndUser();
		$user->setData(array('name' => 'test user'));
		tx_oelib_FrontEndLoginManager::getInstance()->logInUser($user);

		$this->fixture->setConfigurationValue('visibleContactFormFields', '');

		$this->assertNotContains(
			'value="test user"',
			 $this->fixture->render(array('showUid' => $this->realtyUid))
		);
	}

	/**
	 * @test
	 */
	public function specializedContactFormHasDisabledEmailFieldIfLoggedIn() {
		$user = new tx_realty_Model_FrontEndUser();
		$user->setData(array('email' => 'frontend-user@valid-email.org'));
		tx_oelib_FrontEndLoginManager::getInstance()->logInUser($user);

		$this->assertContains(
			'value="frontend-user@valid-email.org" disabled="disabled"',
			 $this->fixture->render(array('showUid' => $this->realtyUid))
		);
	}

	/**
	 * @test
	 */
	public function generalContactFormHasDisabledNameFieldIfLoggedIn() {
		$user = new tx_realty_Model_FrontEndUser();
		$user->setData(array('name' => 'test user'));
		tx_oelib_FrontEndLoginManager::getInstance()->logInUser($user);

		$this->assertContains(
			'value="test user" disabled="disabled"',
			 $this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function generalContactFormHasDisabledEmailFieldIfLoggedIn() {
		$user = new tx_realty_Model_FrontEndUser();
		$user->setData(array('email' => 'frontend-user@valid-email.org'));
		tx_oelib_FrontEndLoginManager::getInstance()->logInUser($user);

		$this->assertContains(
			'value="frontend-user@valid-email.org" disabled="disabled"',
			 $this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function specializedContactFormHasNoDisabledInfomationIfNotLoggedIn() {
		$this->assertNotContains(
			$this->fixture->translate('label_requester_data_is_uneditable'),
			$this->fixture->render(array('showUid' => $this->realtyUid))
		);
	}

	/**
	 * @test
	 */
	public function generalContactHasNoDisabledInfomationIfNotLoggedIn() {
		$this->assertNotContains(
			$this->fixture->translate('label_requester_data_is_uneditable'),
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function specializedContactFormHasDisabledInfomationIfLoggedIn() {
		$user = new tx_realty_Model_FrontEndUser();
		$user->setData(array('name' => 'test user'));
		tx_oelib_FrontEndLoginManager::getInstance()->logInUser($user);

		$this->assertContains(
			$this->fixture->translate('label_requester_data_is_uneditable'),
			$this->fixture->render(array('showUid' => $this->realtyUid))
		);
	}

	/**
	 * @test
	 */
	public function generalContactFormHasDisabledInfomationIfLoggedIn() {
		$user = new tx_realty_Model_FrontEndUser();
		$user->setData(array('name' => 'test user'));
		tx_oelib_FrontEndLoginManager::getInstance()->logInUser($user);

		$this->assertContains(
			$this->fixture->translate('label_requester_data_is_uneditable'),
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function contactFormDisplaysGeneralViewIfTheRealtyObjectUidWasNotNumeric() {
		$this->assertNotContains(
			$this->fixture->translate('label_object_number'),
			$this->fixture->render(
				array('showUid' => 'foo')
			)
		);
	}

	/**
	 * @test
	 */
	public function specializedContactFormNotDisplaysUnreplacedMarkersIfRealtyObjectDoesNotExist() {
		$this->assertNotContains(
			'###',
			$this->fixture->render(
				array('showUid' => $this->testingFramework->createRecord(
					REALTY_TABLE_OBJECTS, array('deleted' => 1)
				))
			)
		);
	}

	/**
	 * @test
	 */
	public function specializedContactFormNotDisplaysObjectNumberLabelIfRealtyObjectDoesNotExist() {
		$this->assertNotContains(
			$this->fixture->translate('label_object_number'),
			$this->fixture->render(
				array('showUid' => $this->testingFramework->createRecord(
					REALTY_TABLE_OBJECTS, array('deleted' => 1)
				))
			)
		);
	}

	/**
	 * @test
	 */
	public function specializedContactFormNotDisplaysTitleLabelIfRealtyObjectDoesNotExist() {
		$this->assertNotContains(
			$this->fixture->translate('label_title'),
			$this->fixture->render(
				array('showUid' => $this->testingFramework->createRecord(
					REALTY_TABLE_OBJECTS, array('deleted' => 1)
				))
			)
		);
	}

	/**
	 * @test
	 */
	public function specializedContactFormNotDisplaysSubmitLabelIfRealtyObjectDoesNotExist() {
		$this->assertNotContains(
			$this->fixture->translate('label_submit'),
			$this->fixture->render(array(
				'showUid' => $this->testingFramework->createRecord(
					REALTY_TABLE_OBJECTS, array('deleted' => 1)
				))
			)
		);
	}

	/**
	 * @test
	 */
	public function specializedContactFormNotDisplaysYourNameLabelIfRealtyObjectDoesNotExist() {
		$this->assertNotContains(
			$this->fixture->translate('label_your_name'),
			$this->fixture->render(array(
				'showUid' => $this->testingFramework->createRecord(
					REALTY_TABLE_OBJECTS, array('deleted' => 1)
				))
			)
		);
	}

	/**
	 * @test
	 */
	public function contactFormDisplaysYourNameLabelIfThisIsConfigured() {
		$this->assertContains(
			$this->fixture->translate('label_your_name'),
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function contactFormNotDisplaysYourNameLabelIfThisIsNotConfigured() {
		$this->fixture->setConfigurationValue('visibleContactFormFields', '');

		$this->assertNotContains(
			$this->fixture->translate('label_your_name'),
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function contactFormDisplaysRequestFieldIfThisIsConfigured() {
		$this->assertContains(
			'name="tx_realty_pi1[request]"',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function contactFormNotDisplaysRequestFieldIfThisIsNotConfigured() {
		$this->fixture->setConfigurationValue('visibleContactFormFields', 'name');

		$this->assertNotContains(
			'name="tx_realty_pi1[request]"',
			$this->fixture->render()
		);
	}
	/**
	 * @test
	 */
	public function contactFormDisplaysViewingFieldIfThisIsConfigured() {
		$this->fixture->setConfigurationValue(
			'visibleContactFormFields', 'viewing'
		);

		$this->assertContains(
			'name="tx_realty_pi1[viewing]"',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function contactFormNotDisplaysViewingFieldIfThisIsNotConfigured() {
		$this->fixture->setConfigurationValue('visibleContactFormFields', 'name');

		$this->assertNotContains(
			'name="tx_realty_pi1[viewing]"',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function contactFormDisplaysInformationFieldIfThisIsConfigured() {
		$this->fixture->setConfigurationValue(
			'visibleContactFormFields', 'information'
		);

		$this->assertContains(
			'name="tx_realty_pi1[information]"',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function contactFormNotDisplaysInformationFieldIfThisIsNotConfigured() {
		$this->fixture->setConfigurationValue('visibleContactFormFields', 'name');

		$this->assertNotContains(
			'name="tx_realty_pi1[information]"',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function contactFormDisplaysCallbackFieldIfThisIsConfigured() {
		$this->fixture->setConfigurationValue(
			'visibleContactFormFields', 'callback'
		);

		$this->assertContains(
			'name="tx_realty_pi1[callback]"',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function contactFormNotDisplaysCallbackFieldIfThisIsNotConfigured() {
		$this->fixture->setConfigurationValue('visibleContactFormFields', 'name');

		$this->assertNotContains(
			'name="tx_realty_pi1[callback]"',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function contactFormDisplaysCallbackAsteriskIfCallbackAndLawTextAreVisible() {
		$this->fixture->setConfigurationValue(
			'visibleContactFormFields', 'callback,law'
		);

		$this->assertContains(
			'class="tx-realty-pi1-law-asterisk"',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function contactFormNotDisplaysCallbackAsteriskIfCallbackIsVisibleAndLawTextIsNotVisible() {
		$this->fixture->setConfigurationValue(
			'visibleContactFormFields', 'callback'
		);

		$this->assertNotContains(
			'class="tx-realty-pi1-law-asterisk"',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function contactFormDisplaysTermsFieldIfThisIsConfigured() {
		$this->fixture->setConfigurationValue('visibleContactFormFields', 'terms');

		$this->assertContains(
			'name="tx_realty_pi1[terms]"',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function contactFormNotDisplaysTermsFieldIfThisIsNotConfigured() {
		$this->fixture->setConfigurationValue('visibleContactFormFields', 'name');

		$this->assertNotContains(
			'name="tx_realty_pi1[terms]"',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function contactFormDisplaysLawTextIfThisIsConfigured() {
		$this->fixture->setConfigurationValue('visibleContactFormFields', 'law');

		$this->assertContains(
			'class="tx-realty-pi1-law"',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function contactFormNotDisplaysLawTextIfThisIsNotConfigured() {
		$this->fixture->setConfigurationValue('visibleContactFormFields', 'name');

		$this->assertNotContains(
			'class="tx-realty-pi1-law"',
			$this->fixture->render()
		);
	}


	//////////////////////////////////////////
	// Test concerning the link to the terms
	//////////////////////////////////////////

	/**
	 * @test
	 */
	public function termsLabelContainsLinkToTermsPage() {
		$termsPid = 1337;
		$this->fixture->setConfigurationValue('termsPID', $termsPid);
		$this->fixture->setConfigurationValue('visibleContactFormFields', 'terms');

		$this->assertContains(
			'a href="index.php?id=' . $termsPid,
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function termsLabelContainsTermsPagePopup() {
		$termsPid = 1337;
		$this->fixture->setConfigurationValue('termsPID', $termsPid);
		$this->fixture->setConfigurationValue('visibleContactFormFields', 'terms');

		$this->assertContains(
			'onclick="window.open(\'index.php?id=' . $termsPid,
			$this->fixture->render()
		);
	}

	///////////////////////////////////////
	// Tests concerning (error) messages.
	///////////////////////////////////////

	/**
	 * @test
	 */
	public function specializedContactFormDisplaysAnErrorIfRealtyObjectDoesNotExist() {
		$this->assertContains(
			$this->fixture->translate('message_noResultsFound_contact_form'),
			$this->fixture->render(
				array('showUid' => $this->testingFramework->createRecord(
					REALTY_TABLE_OBJECTS, array('deleted' => 1)
				))
			)
		);
	}

	/**
	 * @test
	 */
	public function specializedContactFormDisplaysErrorAfterSubmittingIfNoValidEmailAddressWasProvided() {
		$this->assertContains(
			$this->fixture->translate('label_set_valid_email_address'),
			$this->fixture->render(
				array(
					'showUid' => $this->realtyUid,
					'isSubmitted' => TRUE,
					'requesterName' => 'any name',
					'requesterEmail' => 'requester-invalid-email',
					'request' => 'the request',
				)
			)
		);
	}

	/**
	 * @test
	 */
	public function generalContactFormDisplaysErrorAfterSubmittingIfNoValidEmailAddressWasProvided() {
		$this->assertContains(
			$this->fixture->translate('label_set_valid_email_address'),
			$this->fixture->render(
				array(
					'isSubmitted' => TRUE,
					'requesterName' => 'any name',
					'requesterEmail' => 'requester-invalid-email',
					'request' => 'the request',
				)
			)
		);
	}

	/**
	 * @test
	 */
	public function specializedContactFormDisplaysErrorAfterSubmittingIfHeaderInjectionWasAttemptedInTheEmailField() {
		$this->assertContains(
			$this->fixture->translate('label_set_valid_email_address'),
			$this->fixture->render(
				array(
					'showUid' => $this->realtyUid,
					'isSubmitted' => TRUE,
					'requesterName' => 'any name',
					'requesterEmail' => 'requester@valid-email.org' . LF . 'anything',
					'request' => 'the request',
				)
			)
		);
	}

	/**
	 * @test
	 */
	public function specializedContactFormDisplaysErrorAfterSubmittingIfHeaderInjectionWasAttemptedInTheNameField() {
		$this->assertContains(
			$this->fixture->translate('label_set_name'),
			$this->fixture->render(
				array(
					'showUid' => $this->realtyUid,
					'isSubmitted' => TRUE,
					'requesterName' => 'any name' . LF . 'anything',
					'requesterEmail' => 'requester@valid-email.org',
					'request' => 'the request',
				)
			)
		);
	}

	/**
	 * @test
	 */
	public function contactFormDisplaysErrorAfterSubmittingIfAngleBracketsAreSetInTheNameField() {
		$this->assertContains(
			$this->fixture->translate('label_set_name'),
			$this->fixture->render(
				array(
					'showUid' => $this->realtyUid,
					'isSubmitted' => TRUE,
					'requesterName' => 'any name < anything',
					'requesterEmail' => 'requester@valid-email.org',
					'request' => 'the request',
				)
			)
		);
	}

	/**
	 * @test
	 */
	public function contactFormDisplaysErrorAfterSubmittingIfQuotationMarksAreSetInTheNameField() {
		$this->assertContains(
			$this->fixture->translate('label_set_name'),
			$this->fixture->render(
				array(
					'showUid' => $this->realtyUid,
					'isSubmitted' => TRUE,
					'requesterName' => 'any name " anything',
					'requesterEmail' => 'requester@valid-email.org',
					'request' => 'the request',
				)
			)
		);
	}

	/**
	 * @test
	 */
	public function specializedContactFormDisplaysErrorAfterSubmittingIfNoNameWasProvidedButIsRequired() {
		$this->fixture->setConfigurationValue('requiredContactFormFields', 'name');

		$this->assertContains(
			$this->fixture->translate('message_required_field'),
			$this->fixture->render(
				array(
					'showUid' => $this->realtyUid,
					'isSubmitted' => TRUE,
					'requesterName' => '',
					'requesterEmail' => 'requester@valid-email.org',
					'request' => 'the request',
				)
			)
		);
	}

	/**
	 * @test
	 */
	public function generalContactFormDisplaysErrorAfterSubmittingIfNoNameWasProvidedButIsRequired() {
		$this->fixture->setConfigurationValue('requiredContactFormFields', 'name');

		$this->assertContains(
			$this->fixture->translate('message_required_field'),
			$this->fixture->render(
				array(
					'isSubmitted' => TRUE,
					'requesterName' => '',
					'requesterEmail' => 'requester@valid-email.org',
					'request' => 'the request',
				)
			)
		);
	}

	/**
	 * @test
	 */
	public function specializedContactFormForRequiredMessageDisplaysErrorAfterSubmittingIfTheRequestWasEmpty() {
		$this->fixture->setConfigurationValue(
			'requiredContactFormFields', 'request'
		);

		$this->assertContains(
			$this->fixture->translate('message_required_field_request'),
			$this->fixture->render(
				array(
					'showUid' => $this->realtyUid,
					'isSubmitted' => TRUE,
					'requesterName' => 'any name',
					'requesterEmail' => 'requester@valid-email.org',
					'request' => '',
				)
			)
		);
	}

	/**
	 * @test
	 */
	public function generalContactFormForRequiredMessageDisplaysErrorAfterSubmittingIfTheRequestWasEmpty() {
		$this->fixture->setConfigurationValue(
			'requiredContactFormFields', 'request'
		);

		$this->assertContains(
			$this->fixture->translate('message_required_field_request'),
			$this->fixture->render(
				array(
					'isSubmitted' => TRUE,
					'requesterName' => 'any name',
					'requesterEmail' => 'requester@valid-email.org',
					'request' => '',
				)
			)
		);
	}

	/**
	 * @test
	 */
	public function generalContactFormWithoutRequiredMessageNotDisplaysErrorAfterSubmittingIfTheRequestWasEmpty() {
		$this->fixture->setConfigurationValue('requiredContactFormFields', '');

		$this->assertNotContains(
			$this->fixture->translate('message_required_field_request'),
			$this->fixture->render(
				array(
					'isSubmitted' => TRUE,
					'requesterName' => 'any name',
					'requesterEmail' => 'requester@valid-email.org',
					'request' => '',
				)
			)
		);
	}
	/**
	 * @test
	 */
	public function specializedContactFormDisplaysErrorAfterSubmittingIfTheObjectHasNoContactDataAndNoDefaultEmailWasSet() {
		$this->fixture->setConfigurationValue('defaultContactEmail', '');

		$this->assertContains(
			$this->fixture->translate('label_no_contact_person'),
			$this->fixture->render(
				array(
					'showUid' => $this->realtyUid,
					'isSubmitted' => TRUE,
					'requesterName' => 'any name',
					'requesterEmail' => 'requester@valid-email.org',
					'request' => 'the request',
				)
			)
		);
	}

	/**
	 * @test
	 */
	public function generalContactFormDisplaysAnErrorAfterSubmittingIfNoDefaultEmailAddressWasSet() {
		$this->fixture->setConfigurationValue('defaultContactEmail', '');

		$this->assertContains(
			$this->fixture->translate('label_no_contact_person'),
			$this->fixture->render(
				array(
					'isSubmitted' => TRUE,
					'requesterName' => 'any name',
					'requesterEmail' => 'requester@valid-email.org',
					'request' => 'the request',
				)
			)
		);
	}

	/**
	 * @test
	 */
	public function contactFormDisplaysTwoErrorMessagesIfNameAndStreetAreRequiredButEmpty() {
		$this->fixture->setConfigurationValue('requiredContactFormFields', 'name,street');

		$this->assertEquals(
			2,
			substr_count(
				 $this->fixture->render(
					array(
						'isSubmitted' => TRUE,
						'requesterName' => '',
						'requesterEmail' => 'requester@valid-email.org',
						'request' => 'foo',
					)
				),
				$this->fixture->translate('message_required_field')
			)
		);
	}

	/**
	 * @test
	 */
	public function specializedContactFormStillDisplaysTheFormIfAnErrorOccurs() {
		$result = $this->fixture->render(
			array(
				'showUid' => $this->realtyUid,
				'isSubmitted' => TRUE,
				'requesterName' => 'any name',
				'requesterEmail' => 'requester@valid-email.org',
				'request' => '',
			)
		);

		$this->assertContains(
			$this->fixture->translate('message_required_field_request'),
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

	/**
	 * @test
	 */
	public function contactFormStillDisplaysGeneralViewOfTheFormIfAnErrorOccurs() {
		$result = $this->fixture->render(
			array(
				'isSubmitted' => TRUE,
				'requesterName' => 'any name',
				'requesterEmail' => 'requester@valid-email.org',
				'request' => '',
			)
		);

		$this->assertContains(
			$this->fixture->translate('message_required_field_request'),
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

	/**
	 * @test
	 */
	public function specializedContactFormShowsSubmittedMessageIfAllContentIsValid() {
		$this->assertContains(
			$this->fixture->translate('label_message_sent'),
			$this->fixture->render(
				array(
					'showUid' => $this->realtyUid,
					'isSubmitted' => TRUE,
					'requesterName' => 'any name',
					'requesterEmail' => 'requester@valid-email.org',
					'request' => 'the request',
				)
			)
		);
	}

	/**
	 * @test
	 */
	public function generalContactFormShowsSubmittedMessageIfAllContentIsValid() {
		$this->assertContains(
			$this->fixture->translate('label_message_sent'),
			$this->fixture->render(
				array(
					'isSubmitted' => TRUE,
					'requesterName' => 'any name',
					'requesterEmail' => 'requester@valid-email.org',
					'request' => 'the request',
				)
			)
		);
	}

	/**
	 * @test
	 */
	public function contactFormDisplaysErrorMessageForEmptyRequiredStreetField() {
		$this->fixture->setConfigurationValue('requiredContactFormFields', 'street');

		$this->assertContains(
			$this->fixture->translate('message_required_field'),
			$this->fixture->render(
				array(
					'isSubmitted' => TRUE,
					'requesterEmail' => 'requester@valid-email.org',
					'request' => 'foo bar',
					'requesterStreet' => '',
				)
			)
		);
	}

	/**
	 * @test
	 */
	public function contactFormDisplaysErrorMessageForEmptyRequiredCityField() {
		$this->fixture->setConfigurationValue('requiredContactFormFields', 'city');

		$this->assertContains(
			$this->fixture->translate('message_required_field_requesterCity'),
			$this->fixture->render(
				array(
					'isSubmitted' => TRUE,
					'requesterEmail' => 'requester@valid-email.org',
					'request' => 'foo bar',
					'requesterCity' => '',
				)
			)
		);
	}

	/**
	 * @test
	 */
	public function contactFormDisplaysNoErrorMessageForNonEmptyRequiredField() {
		$this->fixture->setConfigurationValue('requiredContactFormFields', 'street');

		$this->assertNotContains(
			$this->fixture->translate('message_required_field'),
			$this->fixture->render(
				array(
					'isSubmitted' => TRUE,
					'requesterEmail' => 'requester@valid-email.org',
					'request' => 'foo bar',
					'requesterStreet' => 'main street',
				)
			)
		);
	}

	/**
	 * @test
	 */
	public function contactFormForVisibleAndNotSubmittedTermsFieldDisplaysErrorMessage() {
		$this->fixture->setConfigurationValue('visibleContactFormFields', 'terms');
		$this->fixture->setConfigurationValue('requiredContactFormFields', '');

		$this->assertContains(
			$this->fixture->translate('message_required_field_terms'),
			$this->fixture->render(
				array(
					'isSubmitted' => TRUE,
					'requesterEmail' => 'requester@valid-email.org',
				)
			)
		);
	}

	/**
	 * @test
	 */
	public function contactFormForVisibleAndFilledTermsFieldNotDisplaysErrorMessage() {
		$this->fixture->setConfigurationValue('visibleContactFormFields', 'terms');
		$this->fixture->setConfigurationValue('requiredContactFormFields', '');

		$this->assertNotContains(
			$this->fixture->translate('message_required_field_terms'),
			$this->fixture->render(
				array(
					'isSubmitted' => TRUE,
					'requesterEmail' => 'requester@valid-email.org',
					'terms' => '1',
				)
			)
		);
	}

	/**
	 * @test
	 */
	public function contactFormForNotVisibleAndNotSubmittedTermsFieldNotDisplaysErrorMessage() {
		$this->fixture->setConfigurationValue('visibleContactFormFields', '');
		$this->fixture->setConfigurationValue('requiredContactFormFields', '');

		$this->assertNotContains(
			$this->fixture->translate('message_required_field_terms'),
			$this->fixture->render(
				array(
					'isSubmitted' => TRUE,
					'requesterEmail' => 'requester@valid-email.org',
				)
			)
		);
	}


	///////////////////////////////////////////
	// Tests for generally displayed strings.
	///////////////////////////////////////////

	/**
	 * @test
	 */
	public function formWithMinimalContentDoesNotContainUnreplacedMarkers() {
		$this->assertNotContains(
			'###',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function formHasInputFieldForStreet() {
		$this->assertContains(
			'tx_realty_pi1[requesterStreet]',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function formHasInputFieldForZip() {
		$this->assertContains(
			'tx_realty_pi1[requesterStreet]',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function formHasInputFieldForCity() {
		$this->assertContains(
			'tx_realty_pi1[requesterStreet]',
			$this->fixture->render()
		);
	}


	//////////////////////////////////////////////
	// Tests concerning the form fields' values.
	//////////////////////////////////////////////

	/**
	 * @test
	 */
	public function notSuccessfullySubmittedFormStillContainsSubmittedValueForRequest() {
		$this->assertContains(
			'>the request</textarea>',
			$this->fixture->render(
				array(
					'isSubmitted' => TRUE,
					'request' => 'the request'
				)
			)
		);
	}

	/**
	 * @test
	 */
	public function notSuccessfullySubmittedFormStillContainsSubmittedValueForName() {
		$this->assertContains(
			'value="any name"',
			$this->fixture->render(
				array(
					'isSubmitted' => TRUE,
					'requesterName' => 'any name',
				)
			)
		);
	}

	/**
	 * @test
	 */
	public function notSuccessfullySubmittedFormStillContainsSubmittedValueForPhone() {
		$this->assertContains(
			'value="1234567"',
			$this->fixture->render(
				array(
					'isSubmitted' => TRUE,
					'requesterPhone' => '1234567',
				)
			)
		);
	}

	/**
	 * @test
	 */
	public function notSuccessfullySubmittedFormStillContainsSubmittedValueOfEmail() {
		$this->assertContains(
			'value="requester@valid-email.org"',
			$this->fixture->render(
				array(
					'isSubmitted' => TRUE,
					'requesterEmail' => 'requester@valid-email.org',
				)
			)
		);
	}

	/**
	 * @test
	 */
	public function notSuccessfullySubmittedFormStillContainsSubmittedValueOfFalseEmail() {
		$this->assertContains(
			'value="requester-invalid-email"',
			$this->fixture->render(
				array(
					'isSubmitted' => TRUE,
					'requesterEmail' => 'requester-invalid-email',
				)
			)
		);
	}

	/**
	 * @test
	 */
	public function notSuccessfullySubmittedFormStillContainsSubmittedValueWithHtmlSpecialCharedTags() {
		$this->assertContains(
			'>&lt;fieldset /&gt;the request&lt;script /&gt;</textarea>',
			$this->fixture->render(
				array(
					'isSubmitted' => TRUE,
					'request' => '<fieldset />the request<script />',
				)
			)
		);
	}


	/////////////////////////////////////////////
	// Tests concerning the content of e-mails.
	/////////////////////////////////////////////

	/**
	 * @test
	 */
	public function specializedContactFormUsesDefaultEmailAddressIfTheObjectHasNoContactData() {
		$this->fixture->render(
			array(
				'showUid' => $this->realtyUid,
				'isSubmitted' => TRUE,
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

	/**
	 * @test
	 */
	public function specializedContactFormUsesTheCorrectContactDataWhenDataSourceIsSetToRealtyObject() {
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
				'isSubmitted' => TRUE,
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

	/**
	 * @test
	 */
	public function specializedContactFormUsesTheDefaultEmailAddressEmailIfTheOwnersAddressWasNotValid() {
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
				'isSubmitted' => TRUE,
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

	/**
	 * @test
	 */
	public function specializedContactFormUsesTheDefaultEmailAddressIfTheContactPersonsAddressIsInvalid() {
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
				'isSubmitted' => TRUE,
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

	/**
	 * @test
	 */
	public function specializedContactFormUsesTheCorrectContactDataWhenDataSourceIsSetToOwner() {
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
				'isSubmitted' => TRUE,
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

	/**
	 * @test
	 */
	public function specializedContactFormUsesDefaultEmailAddressWhenDataSourceIsDeletedOwner() {
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
				'isSubmitted' => TRUE,
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

	/**
	 * @test
	 */
	public function specializedContactFormUsesDefaultEmailAddressForInvalidAddressFromOwnerAccount() {
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
				'isSubmitted' => TRUE,
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

	/**
	 * @test
	 */
	public function generalContactFormUsesTheDefaultEmailAddress() {
		$this->fixture->render(
			array(
				'isSubmitted' => TRUE,
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

	/**
	 * @test
	 */
	public function headerContainsNameAndEmailAddress() {
		$this->fixture->render(
			array(
				'isSubmitted' => TRUE,
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

	/**
	 * @test
	 */
	public function nameAndEmailAddressAreFetchedAutomaticallyAsSenderIfAFeUserIsLoggedIn() {
		$user = new tx_realty_Model_FrontEndUser();
		$user->setData(
			array(
				'name' => 'test user',
				'email' => 'frontend-user@valid-email.org',
			)
		);
		tx_oelib_FrontEndLoginManager::getInstance()->logInUser($user);

		$this->fixture->render(
			array(
				'showUid' => $this->realtyUid,
				'isSubmitted' => TRUE,
				'request' => 'the request',
			)
		);

		$this->assertContains(
			'"test user" <frontend-user@valid-email.org>',
			tx_oelib_mailerFactory::getInstance()->getMailer()->getLastHeaders()
		);
	}

	/**
	 * @test
	 */
	public function emailAddressIsFetchedAutomaticallyAsSenderIfAFeUserIsLoggedInAndNoUserNameSet() {
		$user = new tx_realty_Model_FrontEndUser();
		$user->setData(
			array(
				'name' => '',
				'email' => 'frontend-user@valid-email.org',
			)
		);
		tx_oelib_FrontEndLoginManager::getInstance()->logInUser($user);

		$this->fixture->render(
			array(
				'showUid' => $this->realtyUid,
				'isSubmitted' => TRUE,
				'request' => 'the request',
			)
		);

		$this->assertContains(
			'frontend-user@valid-email.org',
			tx_oelib_mailerFactory::getInstance()->getMailer()->getLastHeaders()
		);
	}

	/**
	 * @test
	 */
	public function senderDoesNotContainTheNameIfAFeUserIsLoggedAndUserNameVisibilityDisabled() {
		$user = new tx_realty_Model_FrontEndUser();
		$user->setData(
			array(
				'name' => 'test user',
				'email' => 'frontend-user@valid-email.org',
			)
		);
		tx_oelib_FrontEndLoginManager::getInstance()->logInUser($user);

		$this->fixture->setConfigurationValue('visibleContactFormFields', '');

		$this->fixture->render(
			array(
				'showUid' => $this->realtyUid,
				'isSubmitted' => TRUE,
				'request' => 'the request',
			)
		);

		$this->assertNotContains(
			'test user',
			tx_oelib_mailerFactory::getInstance()->getMailer()->getLastHeaders()
		);
	}

	/**
	 * @test
	 */
	public function noQuotesAreSetForTheSenderIfAFeUserIsLoggedInAndNoUserNameSet() {
		$user = new tx_realty_Model_FrontEndUser();
		$user->setData(
			array(
				'name' => '',
				'email' => 'frontend-user@valid-email.org',
			)
		);
		tx_oelib_FrontEndLoginManager::getInstance()->logInUser($user);

		$this->fixture->render(
			array(
				'showUid' => $this->realtyUid,
				'isSubmitted' => TRUE,
				'request' => 'the request',
			)
		);

		$this->assertNotContains(
			'"',
			tx_oelib_mailerFactory::getInstance()->getMailer()->getLastHeaders()
		);
	}

	/**
	 * @test
	 */
	public function noAngleBracketsAreSetForTheSenderIfAFeUserIsLoggedInAndNoUserNameSet() {
		$user = new tx_realty_Model_FrontEndUser();
		$user->setData(
			array(
				'name' => '',
				'email' => 'frontend-user@valid-email.org',
			)
		);
		tx_oelib_FrontEndLoginManager::getInstance()->logInUser($user);

		$this->fixture->render(
			array(
				'showUid' => $this->realtyUid,
				'isSubmitted' => TRUE,
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

	/**
	 * @test
	 */
	public function headerContainsABccAddressIfThisWasConfigured() {
		$this->fixture->setConfigurationValue(
			'blindCarbonCopyAddress', 'bcc-address@valid-email.org'
		);
		$this->fixture->render(
			array(
				'isSubmitted' => TRUE,
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

	/**
	 * @test
	 */
	public function headerContainsNoBccLineIfNoAddressWasConfigured() {
		$this->fixture->render(
			array(
				'isSubmitted' => TRUE,
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

	/**
	 * @test
	 */
	public function noEmailIsSentIfTheContactFormWasNotFilledCorrectly() {
		$this->fixture->render(
			array(
				'isSubmitted' => TRUE,
				'requesterName' => 'any name',
			)
		);

		$this->assertEquals(
			array(),
			tx_oelib_mailerFactory::getInstance()->getMailer()->getLastEmail()
		);
	}

	/**
	 * @test
	 */
	public function emailWithMinimumContentContainsNoUnreplacedMarkers() {
		$this->fixture->render(
			array(
				'showUid' => $this->realtyUid,
				'isSubmitted' => TRUE,
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

	/**
	 * @test
	 */
	public function emailWithNonEmptyRequestContainsRequestIntro() {
		$this->fixture->setConfigurationValue('requiredContactFormFields', 'name');
		$this->fixture->render(
			array(
				'showUid' => $this->realtyUid,
				'isSubmitted' => TRUE,
				'requesterName' => 'any name',
				'requesterEmail' => 'requester@valid-email.org',
				'request' => 'Bonjour!',
			)
		);

		$this->assertContains(
			$this->fixture->translate('label_has_request'),
			tx_oelib_mailerFactory::getInstance()->getMailer()->getLastBody()
		);
	}

	/**
	 * @test
	 */
	public function emailWithEmptyRequestNotContainsRequestIntro() {
		$this->fixture->setConfigurationValue('requiredContactFormFields', 'name');
		$this->fixture->render(
			array(
				'showUid' => $this->realtyUid,
				'isSubmitted' => TRUE,
				'requesterName' => 'any name',
				'requesterEmail' => 'requester@valid-email.org',
				'request' => '',
			)
		);

		$this->assertNotContains(
			$this->fixture->translate('label_has_request'),
			tx_oelib_mailerFactory::getInstance()->getMailer()->getLastBody()
		);
	}

	/**
	 * @test
	 */
	public function emailWithMissingRequestNotContainsRequestIntro() {
		$this->fixture->setConfigurationValue('requiredContactFormFields', 'name');
		$this->fixture->render(
			array(
				'showUid' => $this->realtyUid,
				'isSubmitted' => TRUE,
				'requesterName' => 'any name',
				'requesterEmail' => 'requester@valid-email.org',
			)
		);

		$this->assertNotContains(
			$this->fixture->translate('label_has_request'),
			tx_oelib_mailerFactory::getInstance()->getMailer()->getLastBody()
		);
	}

	/**
	 * @test
	 */
	public function emailContainsTheTitleOfTheRequestedObjectIfASpecializedContactFormWasSubmitted() {
		$this->fixture->render(
			array(
				'showUid' => $this->realtyUid,
				'isSubmitted' => TRUE,
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

	/**
	 * @test
	 */
	public function emailContainsTheObjectNumberOfTheRequestedObjectIfASpecializedContactFormWasSubmitted() {
		$this->fixture->render(
			array(
				'showUid' => $this->realtyUid,
				'isSubmitted' => TRUE,
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

	/**
	 * @test
	 */
	public function emailFromGeneralContactFormContainsASummaryStringOfTheFavoritesList() {
		$this->fixture->render(
			array(
				'isSubmitted' => TRUE,
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

	/**
	 * @test
	 */
	public function emailBodyContainsTheRequestersName() {
		$this->fixture->render(
			array(
				'isSubmitted' => TRUE,
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

	/**
	 * @test
	 */
	public function emailBodyContainsTheRequestersEmailAddress() {
		$this->fixture->render(
			array(
				'isSubmitted' => TRUE,
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

	/**
	 * @test
	 */
	public function emailBodyContainsTheRequestersPhoneNumber() {
		$this->fixture->render(
			array(
				'isSubmitted' => TRUE,
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

	/**
	 * @test
	 */
	public function emailBodyNotContainsThePhoneNumberLabelIfNoPhoneNumberWasSet() {
		$this->fixture->render(
			array(
				'isSubmitted' => TRUE,
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

	/**
	 * @test
	 */
	public function emailBodyContainsTheRequestersStreet() {
		$this->fixture->render(
			array(
				'isSubmitted' => TRUE,
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

	/**
	 * @test
	 */
	public function emailBodyContainsTheRequestersZip() {
		$this->fixture->render(
			array(
				'isSubmitted' => TRUE,
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

	/**
	 * @test
	 */
	public function emailBodyContainsTheRequestersCity() {
		$this->fixture->render(
			array(
				'isSubmitted' => TRUE,
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

	/**
	 * @test
	 */
	public function emailSubjectIsGeneralForTheGeneralForm() {
		$this->fixture->render(
			array(
				'isSubmitted' => TRUE,
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

	/**
	 * @test
	 */
	public function emailSubjectIsSpecializedForTheSpecializedForm() {
		$this->fixture->render(
			array(
				'showUid' => $this->realtyUid,
				'isSubmitted' => TRUE,
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


	////////////////////////////////////////////////////////////////
	// Tests concerning the monkey functionality of the checkboxes
	////////////////////////////////////////////////////////////////

	/**
	 * @test
	 */
	public function viewingCheckboxNotSubmittedIsNotMarkedAsChecked() {
		$this->fixture->setConfigurationValue(
			'visibleContactFormFields',
			'viewing'
		);

		$this->assertNotContains(
			'checked="checked" name="tx_realty_pi1[viewing]"',
			$this->fixture->render(
				array(
					'showUid' => $this->realtyUid,
					'isSubmitted' => TRUE,
					'requesterEmail' => 'requester@valid-email.org',
				)
			)
		);
	}

	/**
	 * @test
	 */
	public function viewingCheckboxSubmittedCheckedIsMarkedAsChecked() {
		$this->fixture->setConfigurationValue(
			'visibleContactFormFields',
			'viewing'
		);

		$this->assertContains(
			'checked="checked" name="tx_realty_pi1[viewing]"',
			$this->fixture->render(
				array(
					'showUid' => $this->realtyUid,
					'isSubmitted' => TRUE,
					'requesterEmail' => 'requester@valid-email.org',
					'viewing' => '1',
				)
			)
		);
	}

	/**
	 * @test
	 */
	public function informationCheckboxNotSubmittedIsNotMarkedAsChecked() {
		$this->fixture->setConfigurationValue(
			'visibleContactFormFields',
			'information'
		);

		$this->assertNotContains(
			'checked="checked" name="tx_realty_pi1[information]"',
			$this->fixture->render(
				array(
					'showUid' => $this->realtyUid,
					'isSubmitted' => TRUE,
					'requesterEmail' => 'requester@valid-email.org',
				)
			)
		);
	}

	/**
	 * @test
	 */
	public function informationCheckboxSubmittedCheckedIsMarkedAsChecked() {
		$this->fixture->setConfigurationValue(
			'visibleContactFormFields',
			'information'
		);

		$this->assertContains(
			'checked="checked" name="tx_realty_pi1[information]"',
			$this->fixture->render(
				array(
					'showUid' => $this->realtyUid,
					'isSubmitted' => TRUE,
					'requesterEmail' => 'requester@valid-email.org',
					'information' => '1',
				)
			)
		);
	}

	/**
	 * @test
	 */
	public function callbackCheckboxNotSubmittedIsNotMarkedAsChecked() {
		$this->fixture->setConfigurationValue(
			'visibleContactFormFields',
			'callback'
		);

		$this->assertNotContains(
			'checked="checked" name="tx_realty_pi1[callback]"',
			$this->fixture->render(
				array(
					'showUid' => $this->realtyUid,
					'isSubmitted' => TRUE,
					'requesterEmail' => 'requester@valid-email.org',
				)
			)
		);
	}

	/**
	 * @test
	 */
	public function callbackCheckboxSubmittedCheckedIsMarkedAsChecked() {
		$this->fixture->setConfigurationValue(
			'visibleContactFormFields',
			'callback'
		);

		$this->assertContains(
			'checked="checked" name="tx_realty_pi1[callback]"',
			$this->fixture->render(
				array(
					'showUid' => $this->realtyUid,
					'isSubmitted' => TRUE,
					'requesterEmail' => 'requester@valid-email.org',
					'callback' => '1',
				)
			)
		);
	}

	/**
	 * @test
	 */
	public function termsCheckboxNotSubmittedIsNotMarkedAsChecked() {
		$this->fixture->setConfigurationValue(
			'visibleContactFormFields',
			'terms'
		);

		$this->assertNotContains(
			'checked="checked" name="tx_realty_pi1[terms]"',
			$this->fixture->render(
				array(
					'showUid' => $this->realtyUid,
					'isSubmitted' => TRUE,
					'requesterEmail' => 'requester@valid-email.org',
				)
			)
		);
	}

	/**
	 * @test
	 */
	public function termsCheckboxSubmittedCheckedIsMarkedAsChecked() {
		$this->fixture->setConfigurationValue(
			'visibleContactFormFields',
			'terms'
		);

		$this->assertContains(
			'checked="checked" name="tx_realty_pi1[terms]"',
			$this->fixture->render(
				array(
					'showUid' => $this->realtyUid,
					'isSubmitted' => TRUE,
					'requesterEmail' => 'requester@valid-email.org',
					'terms' => '1',
				)
			)
		);
	}


	//////////////////////////////////////////////////////
	// Tests concerning the checkbox texts in the e-mail
	//////////////////////////////////////////////////////

	/**
	 * @test
	 */
	public function emailForCheckedViewingCheckboxContainsViewingCheckboxText() {
		$this->fixture->setConfigurationValue(
			'visibleContactFormFields', 'viewing'
		);
		$this->fixture->setConfigurationValue(
			'requiredContactFormFields', ''
		);
		$this->fixture->render(
			array(
				'isSubmitted' => TRUE,
				'requesterName' => 'a name of a requester',
				'requesterEmail' => 'requester@valid-email.org',
				'viewing' => '1',
			)
		);

		$this->assertContains(
			$this->fixture->translate('label_viewing'),
			tx_oelib_mailerFactory::getInstance()->getMailer()->getLastBody()
		);
	}

	/**
	 * @test
	 */
	public function emailForVisibleAndNotCheckedViewingCheckboxNotContainsViewingCheckboxText() {
		$this->fixture->setConfigurationValue(
			'visibleContactFormFields', 'viewing'
		);
		$this->fixture->setConfigurationValue(
			'requiredContactFormFields', ''
		);
		$this->fixture->render(
			array(
				'isSubmitted' => TRUE,
				'requesterName' => 'a name of a requester',
				'requesterEmail' => 'requester@valid-email.org',
				'viewing' => '',
			)
		);

		$this->assertNotContains(
			$this->fixture->translate('label_viewing'),
			tx_oelib_mailerFactory::getInstance()->getMailer()->getLastBody()
		);
	}

	/**
	 * @test
	 */
	public function emailForCheckedInformationCheckboxContainsInformationCheckboxText() {
		$this->fixture->setConfigurationValue(
			'visibleContactFormFields', 'information'
		);
		$this->fixture->setConfigurationValue(
			'requiredContactFormFields', ''
		);
		$this->fixture->render(
			array(
				'isSubmitted' => TRUE,
				'requesterName' => 'a name of a requester',
				'requesterEmail' => 'requester@valid-email.org',
				'information' => '1',
			)
		);

		$this->assertContains(
			$this->fixture->translate('label_information'),
			tx_oelib_mailerFactory::getInstance()->getMailer()->getLastBody()
		);
	}

	/**
	 * @test
	 */
	public function emailForVisibleAndNotCheckedInformationCheckboxNotContainsInformationCheckboxText() {
		$this->fixture->setConfigurationValue(
			'visibleContactFormFields', 'information'
		);
		$this->fixture->setConfigurationValue(
			'requiredContactFormFields', ''
		);
		$this->fixture->render(
			array(
				'isSubmitted' => TRUE,
				'requesterName' => 'a name of a requester',
				'requesterEmail' => 'requester@valid-email.org',
				'information' => '',
			)
		);

		$this->assertNotContains(
			$this->fixture->translate('label_information'),
			tx_oelib_mailerFactory::getInstance()->getMailer()->getLastBody()
		);
	}

	/**
	 * @test
	 */
	public function emailForCheckedCallbackCheckboxContainsCallbackCheckboxText() {
		$this->fixture->setConfigurationValue(
			'visibleContactFormFields', 'callback'
		);
		$this->fixture->setConfigurationValue(
			'requiredContactFormFields', ''
		);
		$this->fixture->render(
			array(
				'isSubmitted' => TRUE,
				'requesterName' => 'a name of a requester',
				'requesterEmail' => 'requester@valid-email.org',
				'callback' => '1',
			)
		);

		$this->assertContains(
			$this->fixture->translate('label_callback'),
			tx_oelib_mailerFactory::getInstance()->getMailer()->getLastBody()
		);
	}

	/**
	 * @test
	 */
	public function emailForVisibleAndNotCheckedCallbackCheckboxNotContainsCallbackCheckboxText() {
		$this->fixture->setConfigurationValue(
			'visibleContactFormFields', 'callback'
		);
		$this->fixture->setConfigurationValue(
			'requiredContactFormFields', ''
		);
		$this->fixture->render(
			array(
				'isSubmitted' => TRUE,
				'requesterName' => 'a name of a requester',
				'requesterEmail' => 'requester@valid-email.org',
				'callback' => '',
			)
		);

		$this->assertNotContains(
			$this->fixture->translate('label_callback'),
			tx_oelib_mailerFactory::getInstance()->getMailer()->getLastBody()
		);
	}

	/**
	 * @test
	 */
	public function emailForCheckedTermsCheckboxContainsStrippedTermsCheckboxText() {
		$this->fixture->setConfigurationValue(
			'visibleContactFormFields', 'terms'
		);
		$this->fixture->setConfigurationValue(
			'requiredContactFormFields', ''
		);
		$this->fixture->render(
			array(
				'isSubmitted' => TRUE,
				'requesterName' => 'a name of a requester',
				'requesterEmail' => 'requester@valid-email.org',
				'terms' => '1',
			)
		);

		$label = strip_tags(
			str_replace(' %s', '', $this->fixture->translate('label_terms'))
		);
		$this->assertContains(
			$label,
			tx_oelib_mailerFactory::getInstance()->getMailer()->getLastBody()
		);
	}

	/**
	 * @test
	 */
	public function emailForVisibleAndNotCheckedTermsCheckboxNotContainsStrippedTermsCheckboxText() {
		$this->fixture->setConfigurationValue(
			'visibleContactFormFields', 'terms'
		);
		$this->fixture->setConfigurationValue(
			'requiredContactFormFields', ''
		);
		$this->fixture->render(
			array(
				'isSubmitted' => TRUE,
				'requesterName' => 'a name of a requester',
				'requesterEmail' => 'requester@valid-email.org',
				'terms' => '',
			)
		);

		$label = strip_tags(
			str_replace(' %s', '', $this->fixture->translate('label_terms'))
		);
		$this->assertNotContains(
			$label,
			tx_oelib_mailerFactory::getInstance()->getMailer()->getLastBody()
		);
	}

	/**
	 * @test
	 */
	public function emailForCheckedInformationAndCallbackCheckboxContainsBothCheckboxTexts() {
		$this->fixture->setConfigurationValue(
			'visibleContactFormFields', 'information,callback'
		);
		$this->fixture->setConfigurationValue(
			'requiredContactFormFields', ''
		);
		$this->fixture->render(
			array(
				'isSubmitted' => TRUE,
				'requesterName' => 'a name of a requester',
				'requesterEmail' => 'requester@valid-email.org',
				'information' => '1',
				'callback' => '1',
			)
		);

		$emailBody = tx_oelib_mailerFactory::getInstance()->getMailer()
			->getLastBody();
		$this->assertContains(
			$this->fixture->translate('label_information'),
			$emailBody
		);
		$this->assertContains(
			$this->fixture->translate('label_callback'),
			$emailBody
		);
	}
}
?>