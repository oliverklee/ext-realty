<?php
/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * Test case.
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
	private $fixture = NULL;

	/**
	 * @var tx_oelib_testingFramework
	 */
	private $testingFramework = NULL;

	/**
	 * @var int dummy realty object ID
	 */
	private $realtyUid = NULL;

	/**
	 * @var string title for the dummy realty object
	 */
	const REALTY_TITLE = 'test title';

	/**
	 * @var string object number for the dummy realty object
	 */
	const REALTY_OBJECT_NUMBER = '1234567';

	/**
	 * @var t3lib_mail_Message
	 */
	private $message = NULL;

	protected function setUp() {
		$this->testingFramework = new tx_oelib_testingFramework('tx_realty');
		$this->testingFramework->createFakeFrontEnd();
		$this->realtyUid = $this->testingFramework->createRecord(
			'tx_realty_objects',
			array(
				'title' => self::REALTY_TITLE,
				'object_number' => self::REALTY_OBJECT_NUMBER,
			)
		);

		$this->fixture = new tx_realty_contactForm(
			array('templateFile' => 'EXT:realty/pi1/tx_realty_pi1.tpl.htm'),
			$this->createContentMock()
		);

		$this->fixture->setConfigurationValue(
			'defaultContactEmail', 'default-contact@example.com'
		);
		$this->fixture->setConfigurationValue('blindCarbonCopyAddress', '');
		$this->fixture->setConfigurationValue(
			'visibleContactFormFields',
			'name,street,zip_and_city,telephone,request,viewing,information,callback'
		);
		$this->fixture->setConfigurationValue(
			'requiredContactFormFields', 'request'
		);

		$finalMailMessageClassName = t3lib_utility_VersionNumber::convertVersionNumberToInteger(TYPO3_version) >= 6000000
			? 'TYPO3\\CMS\\Core\\Mail\\MailMessage' : 't3lib_mail_Message';
		$this->message = $this->getMock('t3lib_mail_Message', array('send', '__destruct'));
		t3lib_div::addInstance($finalMailMessageClassName, $this->message);
	}

	protected function tearDown() {
		// Get any surplus instances added via t3lib_div::addInstance.
		t3lib_div::makeInstance('t3lib_mail_Message');

		$this->testingFramework->cleanUp();
	}

	/*
	 * Utility functions.
	 */

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
	 * @param int $pageId the page ID to link to, must be >= 0
	 *
	 * @return string faked URL, will not be empty
	 */
	public function getTypoLinkUrl($pageId) {
		return 'index.php?id=' . $pageId;
	}


	/*
	 * Tests for the utility functions.
	 */

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


	/*
	 * Tests concerning view-dependently displayed strings.
	 */

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
		$user->setData(array('email' => 'frontend-user@example.com'));
		tx_oelib_FrontEndLoginManager::getInstance()->logInUser($user);

		$this->assertContains(
			'value="frontend-user@example.com" disabled="disabled"',
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
		$user->setData(array('email' => 'frontend-user@example.com'));
		tx_oelib_FrontEndLoginManager::getInstance()->logInUser($user);

		$this->assertContains(
			'value="frontend-user@example.com" disabled="disabled"',
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
					'tx_realty_objects', array('deleted' => 1)
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
					'tx_realty_objects', array('deleted' => 1)
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
					'tx_realty_objects', array('deleted' => 1)
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
					'tx_realty_objects', array('deleted' => 1)
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
					'tx_realty_objects', array('deleted' => 1)
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


	/*
	 * Test concerning the link to the terms
	 */

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


	/*
	 * Tests concerning (error) messages.
	 */

	/**
	 * @test
	 */
	public function specializedContactFormDisplaysAnErrorIfRealtyObjectDoesNotExist() {
		$this->assertContains(
			$this->fixture->translate('message_noResultsFound_contact_form'),
			$this->fixture->render(
				array('showUid' => $this->testingFramework->createRecord(
					'tx_realty_objects', array('deleted' => 1)
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
					'requesterEmail' => 'requester@example.com' . LF . 'anything',
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
					'requesterEmail' => 'requester@example.com',
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
					'requesterEmail' => 'requester@example.com',
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
					'requesterEmail' => 'requester@example.com',
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
					'requesterEmail' => 'requester@example.com',
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
					'requesterEmail' => 'requester@example.com',
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
					'requesterEmail' => 'requester@example.com',
					'request' => '',
				)
			)
		);
	}

	/**
	 * @test
	 */
	public function generalContactFormForRequiredMessageDisplaysErrorAfterSubmittingIfTheRequestWasEmpty() {
		$this->fixture->setConfigurationValue('requiredContactFormFields', 'request');

		$this->assertContains(
			$this->fixture->translate('message_required_field_request'),
			$this->fixture->render(
				array(
					'isSubmitted' => TRUE,
					'requesterName' => 'any name',
					'requesterEmail' => 'requester@example.com',
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
					'requesterEmail' => 'requester@example.com',
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
					'requesterEmail' => 'requester@example.com',
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
					'requesterEmail' => 'requester@example.com',
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
						'requesterEmail' => 'requester@example.com',
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
				'requesterEmail' => 'requester@example.com',
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
				'requesterEmail' => 'requester@example.com',
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
					'requesterEmail' => 'requester@example.com',
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
					'requesterEmail' => 'requester@example.com',
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
					'requesterEmail' => 'requester@example.com',
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
					'requesterEmail' => 'requester@example.com',
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
					'requesterEmail' => 'requester@example.com',
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
					'requesterEmail' => 'requester@example.com',
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
					'requesterEmail' => 'requester@example.com',
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
					'requesterEmail' => 'requester@example.com',
				)
			)
		);
	}


	/*
	 * Tests for generally displayed strings.
	 */

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


	/*
	 * Tests concerning the form fields' values.
	 */

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
			'value="requester@example.com"',
			$this->fixture->render(
				array(
					'isSubmitted' => TRUE,
					'requesterEmail' => 'requester@example.com',
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


	/*
	 * Tests concerning the content of e-mails.
	 */

	/**
	 * @test
	 */
	public function specializedContactFormUsesDefaultEmailAddressIfTheObjectHasNoContactData() {
		$this->fixture->render(
			array(
				'showUid' => $this->realtyUid,
				'isSubmitted' => TRUE,
				'requesterName' => 'any name',
				'requesterEmail' => 'requester@example.com',
				'request' => 'the request',
			)
		);

		$this->assertArrayHasKey(
			'default-contact@example.com',
			$this->message->getTo()
		);
	}

	/**
	 * @test
	 */
	public function specializedContactFormSendsEmail() {
		$this->message->expects($this->once())->method('send');

		$this->fixture->render(
			array(
				'showUid' => $this->realtyUid,
				'isSubmitted' => TRUE,
				'requesterName' => 'any name',
				'requesterEmail' => 'requester@example.com',
				'request' => 'the request',
			)
		);
	}

	/**
	 * @test
	 */
	public function specializedContactFormUsesTheCorrectContactDataWhenDataSourceIsSetToRealtyObject() {
		$this->testingFramework->changeRecord(
			'tx_realty_objects',
			$this->realtyUid,
			array(
				'contact_data_source' => tx_realty_Model_RealtyObject::CONTACT_DATA_FROM_REALTY_OBJECT,
				'contact_person' => 'any contact person',
				'contact_email' => 'any-valid@email-address.org',
			)
		);
		$this->fixture->render(
			array(
				'showUid' => $this->realtyUid,
				'isSubmitted' => TRUE,
				'requesterName' => 'any name',
				'requesterEmail' => 'requester@example.com',
				'request' => 'the request',
			)
		);

		$this->assertArrayHasKey(
			'any-valid@email-address.org',
			$this->message->getTo()
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
			'tx_realty_objects',
			$this->realtyUid,
			array(
				'contact_data_source' => tx_realty_Model_RealtyObject::CONTACT_DATA_FROM_OWNER_ACCOUNT,
				'owner' => $ownerUid,
			)
		);
		$this->fixture->render(
			array(
				'showUid' => $this->realtyUid,
				'isSubmitted' => TRUE,
				'requesterName' => 'any name',
				'requesterEmail' => 'requester@example.com',
				'request' => 'the request',
			)
		);

		$this->assertArrayHasKey(
			'default-contact@example.com',
			$this->message->getTo()
		);
	}

	/**
	 * @test
	 */
	public function specializedContactFormUsesTheDefaultEmailAddressIfTheContactPersonsAddressIsInvalid() {
		$this->testingFramework->changeRecord(
			'tx_realty_objects',
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
				'requesterEmail' => 'requester@example.com',
				'request' => 'the request',
			)
		);

		$this->assertArrayHasKey(
			'default-contact@example.com',
			$this->message->getTo()
		);
	}

	/**
	 * @test
	 */
	public function specializedContactFormUsesTheCorrectContactDataWhenDataSourceIsSetToOwner() {
		$this->testingFramework->changeRecord(
			'tx_realty_objects',
			$this->realtyUid,
			array(
				'contact_data_source' => tx_realty_Model_RealtyObject::CONTACT_DATA_FROM_OWNER_ACCOUNT,
				'owner' => $this->testingFramework->createFrontEndUser(
					'', array('email' => 'frontend-user@example.com')
				),
			)
		);

		$this->fixture->render(
			array(
				'showUid' => $this->realtyUid,
				'isSubmitted' => TRUE,
				'requesterName' => 'any name',
				'requesterEmail' => 'requester@example.com',
				'request' => 'the request',
			)
		);

		$this->assertArrayHasKey(
			'frontend-user@example.com',
			$this->message->getTo()
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
				'email' => 'deleted-user@example.com',
				'telephone' => '7654321',
				'deleted' => 1,
			)
		);
		$this->testingFramework->changeRecord(
			'tx_realty_objects',
			$this->realtyUid,
			array(
				'contact_data_source' => tx_realty_Model_RealtyObject::CONTACT_DATA_FROM_OWNER_ACCOUNT,
				'owner' => $deletedUserUid,
			)
		);
		$this->fixture->render(
			array(
				'showUid' => $this->realtyUid,
				'isSubmitted' => TRUE,
				'requesterName' => 'any name',
				'requesterEmail' => 'requester@example.com',
				'request' => 'the request',
			)
		);

		$this->assertArrayHasKey(
			'default-contact@example.com',
			$this->message->getTo()
		);
	}

	/**
	 * @test
	 */
	public function specializedContactFormUsesDefaultEmailAddressForInvalidAddressFromOwnerAccount() {
		$this->testingFramework->changeRecord(
			'tx_realty_objects',
			$this->realtyUid,
			array(
				'contact_data_source' => tx_realty_Model_RealtyObject::CONTACT_DATA_FROM_OWNER_ACCOUNT,
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
				'requesterEmail' => 'requester@example.com',
				'request' => 'the request',
			)
		);

		$this->assertArrayHasKey(
			'default-contact@example.com',
			$this->message->getTo()
		);
	}

	/**
	 * @test
	 */
	public function generalContactFormSendsEmail() {
		$this->message->expects($this->once())->method('send');

		$this->fixture->render(
			array(
				'isSubmitted' => TRUE,
				'requesterName' => 'any name',
				'requesterEmail' => 'requester@example.com',
				'request' => 'the request',
			)
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
				'requesterEmail' => 'requester@example.com',
				'request' => 'the request',
			)
		);

		$this->assertArrayHasKey(
			'default-contact@example.com',
			$this->message->getTo()
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
				'requesterEmail' => 'requester@example.com',
				'request' => 'the request',
			)
		);

		$this->assertSame(
			array('requester@example.com' => 'any name'),
			$this->message->getFrom()
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
				'email' => 'frontend-user@example.com',
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

		$this->assertSame(
			array('frontend-user@example.com' => 'test user'),
			$this->message->getFrom()
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
				'email' => 'frontend-user@example.com',
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

		$this->assertArrayHasKey(
			'frontend-user@example.com',
			$this->message->getFrom()
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
				'email' => 'frontend-user@example.com',
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

		$this->assertArrayNotHasKey(
			'test user',
			$this->message->getFrom()
		);
	}

	/**
	 * @test
	 */
	public function headerContainsABccAddressIfThisWasConfigured() {
		$this->fixture->setConfigurationValue(
			'blindCarbonCopyAddress', 'bcc-address@example.com'
		);
		$this->fixture->render(
			array(
				'isSubmitted' => TRUE,
				'requesterName' => 'any name',
				'requesterEmail' => 'requester@example.com',
				'request' => 'the request',
			)
		);

		$this->assertArrayHasKey(
			'bcc-address@example.com',
			$this->message->getBcc()
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
				'requesterEmail' => 'requester@example.com',
				'request' => 'the request',
			)
		);

		$this->assertNull(
			$this->message->getBcc()
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

		$this->message->expects($this->never())->method('send');
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
				'requesterEmail' => 'requester@example.com',
				'request' => 'the request',
			)
		);

		$this->assertNotContains(
			'###',
			$this->message->getBody()
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
				'requesterEmail' => 'requester@example.com',
				'request' => 'Bonjour!',
			)
		);

		$this->assertContains(
			$this->fixture->translate('label_has_request'),
			$this->message->getBody()
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
				'requesterEmail' => 'requester@example.com',
				'request' => '',
			)
		);

		$this->assertNotContains(
			$this->fixture->translate('label_has_request'),
			$this->message->getBody()
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
				'requesterEmail' => 'requester@example.com',
			)
		);

		$this->assertNotContains(
			$this->fixture->translate('label_has_request'),
			$this->message->getBody()
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
				'requesterEmail' => 'requester@example.com',
				'request' => 'the request',
			)
		);

		$this->assertContains(
			self::REALTY_TITLE,
			$this->message->getBody()
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
				'requesterEmail' => 'requester@example.com',
				'request' => 'the request',
			)
		);

		$this->assertContains(
			self::REALTY_OBJECT_NUMBER,
			$this->message->getBody()
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
				'requesterEmail' => 'requester@example.com',
				'request' => 'the request',
				'summaryStringOfFavorites' => 'summary of favorites',
			)
		);

		$this->assertContains(
			'summary of favorites',
			$this->message->getBody()
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
				'requesterEmail' => 'requester@example.com',
				'request' => 'the request',
			)
		);

		$this->assertContains(
			'a name of a requester',
			$this->message->getBody()
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
				'requesterEmail' => 'requester@example.com',
				'request' => 'the request',
			)
		);

		$this->assertContains(
			'requester@example.com',
			$this->message->getBody()
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
				'requesterEmail' => 'requester@example.com',
				'requesterPhone' => '1234567',
				'request' => 'the request',
			)
		);

		$this->assertContains(
			'1234567',
			$this->message->getBody()
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
				'requesterEmail' => 'requester@example.com',
				'request' => 'the request',
			)
		);

		$this->assertNotContains(
			$this->fixture->translate('label_requester_phone'),
			$this->message->getBody()
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
				'requesterEmail' => 'requester@example.com',
				'requesterStreet' => 'main street',
				'request' => 'the request',
			)
		);

		$this->assertContains(
			'main street',
			$this->message->getBody()
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
				'requesterEmail' => 'requester@example.com',
				'requesterZip' => '12345',
				'request' => 'the request',
			)
		);

		$this->assertContains(
			'12345',
			$this->message->getBody()
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
				'requesterEmail' => 'requester@example.com',
				'requesterCity' => 'a city',
				'request' => 'the request',
			)
		);

		$this->assertContains(
			'a city',
			$this->message->getBody()
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
				'requesterEmail' => 'requester@example.com',
				'request' => 'the request',
			)
		);

		$this->assertContains(
			$this->fixture->translate('label_email_subject_general'),
			$this->message->getSubject()
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
				'requesterEmail' => 'requester@example.com',
				'request' => 'the request',
			)
		);

		$this->assertContains(
			self::REALTY_OBJECT_NUMBER,
			$this->message->getSubject()
		);
	}


	/*
	 * Tests concerning the monkey functionality of the checkboxes
	 */

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
					'requesterEmail' => 'requester@example.com',
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
					'requesterEmail' => 'requester@example.com',
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
					'requesterEmail' => 'requester@example.com',
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
					'requesterEmail' => 'requester@example.com',
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
					'requesterEmail' => 'requester@example.com',
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
					'requesterEmail' => 'requester@example.com',
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
					'requesterEmail' => 'requester@example.com',
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
					'requesterEmail' => 'requester@example.com',
					'terms' => '1',
				)
			)
		);
	}


	/*
	 * Tests concerning the checkbox texts in the e-mail
	 */

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
				'requesterEmail' => 'requester@example.com',
				'viewing' => '1',
			)
		);

		$this->assertContains(
			$this->fixture->translate('label_viewing'),
			$this->message->getBody()
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
				'requesterEmail' => 'requester@example.com',
				'viewing' => '',
			)
		);

		$this->assertNotContains(
			$this->fixture->translate('label_viewing'),
			$this->message->getBody()
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
				'requesterEmail' => 'requester@example.com',
				'information' => '1',
			)
		);

		$this->assertContains(
			$this->fixture->translate('label_information'),
			$this->message->getBody()
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
				'requesterEmail' => 'requester@example.com',
				'information' => '',
			)
		);

		$this->assertNotContains(
			$this->fixture->translate('label_information'),
			$this->message->getBody()
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
				'requesterEmail' => 'requester@example.com',
				'callback' => '1',
			)
		);

		$this->assertContains(
			$this->fixture->translate('label_callback'),
			$this->message->getBody()
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
				'requesterEmail' => 'requester@example.com',
				'callback' => '',
			)
		);

		$this->assertNotContains(
			$this->fixture->translate('label_callback'),
			$this->message->getBody()
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
				'requesterEmail' => 'requester@example.com',
				'terms' => '1',
			)
		);

		$label = strip_tags(
			str_replace(' %s', '', $this->fixture->translate('label_terms'))
		);
		$this->assertContains(
			$label,
			$this->message->getBody()
		);
	}

	/**
	 * @test
	 */
	public function emailForVisibleAndNotCheckedTermsCheckboxNotSendsEmail() {
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
				'requesterEmail' => 'requester@example.com',
				'terms' => '',
			)
		);

		$this->message->expects($this->never())->method('send');
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
				'requesterEmail' => 'requester@example.com',
				'information' => '1',
				'callback' => '1',
			)
		);

		$emailBody = $this->message->getBody();
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