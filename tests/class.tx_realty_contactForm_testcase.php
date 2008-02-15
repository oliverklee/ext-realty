<?php
/***************************************************************
* Copyright notice
*
* (c) 2008 Saskia Metzler <saskia@merlin.owl.de> All rights reserved
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
 * Unit tests for the tx_realty_contactForm class in the 'realty' extension.
 *
 * @package		TYPO3
 * @subpackage	tx_realty
 * @author		Saskia Metzler <saskia@merlin.owl.de>
 */

require_once(t3lib_extMgm::extPath('realty').'pi1/class.tx_realty_contactForm.php');

require_once(t3lib_extMgm::extPath('realty').'pi1/class.tx_realty_pi1.php');
require_once(t3lib_extMgm::extPath('oelib').'class.tx_oelib_testingFramework.php');
require_once(t3lib_extMgm::extPath('oelib').'class.tx_oelib_mailerFactory.php');

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
		// Bolster up the fake front end.
		$GLOBALS['TSFE']->tmpl = t3lib_div::makeInstance('t3lib_tsparser_ext');
		$GLOBALS['TSFE']->tmpl->flattenSetup(array(), '', false);
		$GLOBALS['TSFE']->tmpl->init();
		$GLOBALS['TSFE']->tmpl->getCurrentPageData();

		if (!is_object($GLOBALS['TSFE']->fe_user)) {
			$GLOBALS['TSFE']->fe_user = t3lib_div::makeInstance('tslib_feUserAuth');
		}

		$this->pi1 = new tx_realty_pi1();
		$this->pi1->init(array('templateFile' => 'EXT:realty/pi1/tx_realty_pi1.tpl.htm'));

		$this->fixture = new tx_realty_contactForm($this->pi1);
		$this->testingFramework = new tx_oelib_testingFramework('tx_realty');

		$this->createDummyRecords();
		$this->pi1->setConfigurationValue('defaultEmail', 'any-default@email-address.org');
		tx_oelib_mailerFactory::getInstance()->enableTestMode();
	}

	public function tearDown() {
		$this->logoutFeUser();
		$this->testingFramework->cleanUp();
		tx_oelib_mailerFactory::getInstance()->getMailer()->cleanUpCollectedEmailData();
		tx_oelib_mailerFactory::getInstance()->disableTestMode();
		unset($this->fixture, $this->testingFramework);
	}


	///////////////////////
	// Utility functions.
	///////////////////////

	/**
	 * Fakes that a FE user has logged in.
	 *
	 * @param	integer		UID of the FE user, must be > 0
	 */
	private function fakeFeUserLogin($userId) {
		$GLOBALS['TSFE']->fe_user->createUserSession(array());
		$GLOBALS['TSFE']->fe_user->user = array('uid' => $userId);
		$GLOBALS['TSFE']->loginUser = 1;
	}

	/**
	 * Logs out the current FE user.
	 */
	private function logoutFeUser() {
		if (is_object($GLOBALS['TSFE']->fe_user)) {
			$GLOBALS['TSFE']->fe_user->logoff();
		}
		unset($GLOBALS['TSFE']->loginUser);
	}

	/**
	 * Creates dummy records in the DB.
	 */
	private function createDummyRecords() {
		$this->realtyUid = $this->testingFramework->createRecord(
			'tx_realty_objects',
			array(
				'title' => self::$realtyTitle,
				'object_number' => self::$realtyObjectNumber
			)
		);
		$this->feUserId = $this->createFeUser(
			array(
				'name' => self::$feUserTitle,
				'email' => self::$feUserEmail,
				'telephone' => '7654321'
			)
		);
	}

	/**
	 * Creates a FE user and returns the UID.
	 *
	 * Note: This function can be removed when the testing framework allows to
	 * create FE users.
	 *
	 * @see		https://bugs.oliverklee.com/show_bug.cgi?id=1439
	 *
	 * @param	array		data for the FE user, may be empty
	 *
	 * @return	integer		UID of the FE user
	 */
	private function createFeUser(array $data) {
		$data['tx_oelib_is_dummy_record'] = 1;
		$dbResult = $GLOBALS['TYPO3_DB']->exec_INSERTquery(
			'fe_users',
			$data
		);
		if ($dbResult) {
			$result = $GLOBALS['TYPO3_DB']->sql_insert_id();
			$this->testingFramework->markTableAsDirty('fe_users');
		} else {
			throw new Exception('There was an error while creating the FE user');
		}

		return $result;
	}


	/////////////////////////////////////
	// Tests for the utility functions.
	/////////////////////////////////////

	public function testCreateFeUserCreatesADummyFeUser() {
		// Ensures that the database is clean of other dummy records.
		$this->testingFramework->cleanUp();
		$this->createFeUser(array());

		$this->assertEquals(
			1,
			$this->testingFramework->countRecords(
				'fe_users',
				'tx_oelib_is_dummy_record=1'
			)
		);
	}

	public function testCreateDummyRecordsCreatesDummyObjectAndFeUser() {
		// Ensures that the database is clean of other dummy records.
		$this->testingFramework->cleanUp();
		$this->createDummyRecords();

		$this->assertEquals(
			1,
			$this->testingFramework->countRecords(
				'tx_realty_objects',
				'is_dummy_record=1'
			)
		);
		$this->assertEquals(
			1,
			$this->testingFramework->countRecords(
				'fe_users',
				'tx_oelib_is_dummy_record=1'
			)
		);
	}

	public function testFakeUserLogin() {
		$this->fakeFeUserLogin($this->feUserId);

		$this->assertTrue(
			$this->fixture->isLoggedIn()
		);
	}

	public function testLogoutFeUser() {
		$this->fakeFeUserLogin($this->feUserId);
		$this->logoutFeUser();

		$this->assertFalse(
			$this->fixture->isLoggedIn()
		);
	}


	////////////////////////////////////////////////////////
	// Tests conerning view-dependently displayed strings.
	////////////////////////////////////////////////////////

	public function testSpecializedContactFormContainsObjectTitle() {
		$this->assertContains(
			self::$realtyTitle,
			$this->fixture->getHtmlOfContactForm(
				array('showUid' => $this->realtyUid)
			)
		);
	}

	public function testSpecializedContactFormContainsObjectNumber() {
		$this->assertContains(
			self::$realtyObjectNumber,
			$this->fixture->getHtmlOfContactForm(
				array('showUid' => $this->realtyUid)
			)
		);
	}

	public function testGeneralContactFormDoesNotContainTitleLabelWithoutRealtySet() {
		$this->assertNotContains(
			$this->pi1->translate('label_title'),
			$this->fixture->getHtmlOfContactForm(array())
		);
	}

	public function testGeneralContactFormDoesNotContainObjectNumberLabelWithoutRealtySet() {
		$this->assertNotContains(
			$this->pi1->translate('label_object_number'),
			$this->fixture->getHtmlOfContactForm(array())
		);
	}

	public function testSpecializedContactFormHasFieldsForNameAndEmailAddressIfNotLoggedIn() {
		$result = $this->fixture->getHtmlOfContactForm(
			array('showUid' => $this->realtyUid)
		);

		$this->assertContains(
			$this->pi1->translate('label_your_name'),
			$result
		);
		$this->assertContains(
			$this->pi1->translate('label_your_email'),
			$result
		);
	}

	public function testGeneralContactFormHasFieldsForNameAndEmailAddressIfNotLoggedIn() {
		$result = $this->fixture->getHtmlOfContactForm(array());

		$this->assertContains(
			$this->pi1->translate('label_your_name'),
			$result
		);
		$this->assertContains(
			$this->pi1->translate('label_your_email'),
			$result
		);
	}

	public function testSpecializedContactFormDoesNotHaveFieldsForNameAndEmailAddressIfLoggedIn() {
		$this->fakeFeUserLogin($this->feUserId);
		$result = $this->fixture->getHtmlOfContactForm(
			array('showUid' => $this->realtyUid)
		);

		$this->assertNotContains(
			$this->pi1->translate('label_your_name'),
			$result
		);
		$this->assertNotContains(
			$this->pi1->translate('label_your_email'),
			$result
		);
	}

	public function testGeneralContactFormDoesNotHaveFieldsForNameAndEmailAddressIfLoggedIn() {
		$this->fakeFeUserLogin($this->feUserId);
		$result = $this->fixture->getHtmlOfContactForm(array());

		$this->assertNotContains(
			$this->pi1->translate('label_your_name'),
			$result
		);
		$this->assertNotContains(
			$this->pi1->translate('label_your_email'),
			$result
		);
	}


	///////////////////////////////////////
	// Tests concerning (error) messages.
	///////////////////////////////////////

	public function testSpecializedContactFormDisplaysAnErrorIfRealtyObjectDoesNotExist() {
		$this->assertContains(
			$this->pi1->translate('message_noResultsFound_contact_form'),
			$this->fixture->getHtmlOfContactForm(
				array('showUid' => ($this->realtyUid + 1))
			)
		);
	}

	public function testContactFormDisplaysGeneralViewIfTheRealtyObjectUidWasNotNumeric() {
		$this->assertNotContains(
			$this->pi1->translate('label_object_number'),
			$this->fixture->getHtmlOfContactForm(
				array('showUid' => 'foo')
			)
		);
	}

	public function testSpecializedContactFormDisplaysErrorAfterSubmittingIfNoValidEmailAddressWasProvided() {
		$this->assertContains(
			$this->pi1->translate('label_set_valid_email_address'),
			$this->fixture->getHtmlOfContactForm(
				array(
					'showUid' => $this->realtyUid,
					'isSubmitted' => true,
					'requesteeName' => 'any name',
					'requesteeEmail' => 'requestee-invalid-email',
					'request' => 'the request'

				)
			)
		);
	}

	public function testGeneralContactFormDisplaysErrorAfterSubmittingIfNoValidEmailAddressWasProvided() {
		$this->assertContains(
			$this->pi1->translate('label_set_valid_email_address'),
			$this->fixture->getHtmlOfContactForm(
				array(
					'isSubmitted' => true,
					'requesteeName' => 'any name',
					'requesteeEmail' => 'requestee-invalid-email',
					'request' => 'the request'
				)
			)
		);
	}

	public function testSpecializedContactFormDisplaysErrorAfterSubmittingIfHeaderInjectionWasAttemptedInTheEmailField() {
		$this->assertContains(
			$this->pi1->translate('label_set_valid_email_address'),
			$this->fixture->getHtmlOfContactForm(
				array(
					'showUid' => $this->realtyUid,
					'isSubmitted' => true,
					'requesteeName' => 'any name',
					'requesteeEmail' => 'requestee@valid-email.org'.chr(10).'anything',
					'request' => 'the request'

				)
			)
		);
	}

	public function testSpecializedContactFormDisplaysErrorAfterSubmittingIfHeaderInjectionWasAttemptedInTheNameField() {
		$this->assertContains(
			$this->pi1->translate('label_set_name'),
			$this->fixture->getHtmlOfContactForm(
				array(
					'showUid' => $this->realtyUid,
					'isSubmitted' => true,
					'requesteeName' => 'any name'.chr(10).'anything',
					'requesteeEmail' => 'requestee@valid-email.org',
					'request' => 'the request'

				)
			)
		);
	}

	public function testSpecializedContactFormDisplaysErrorAfterSubmittingIfNoNameWasProvided() {
		$this->assertContains(
			$this->pi1->translate('label_set_name'),
			$this->fixture->getHtmlOfContactForm(
				array(
					'showUid' => $this->realtyUid,
					'isSubmitted' => true,
					'requesteeName' => '',
					'requesteeEmail' => 'requestee@valid-email.org',
					'request' => 'the request'
				)
			)
		);
	}

	public function testGeneralContactFormDisplaysErrorAfterSubmittingIfNoNameWasProvided() {
		$this->assertContains(
			$this->pi1->translate('label_set_name'),
			$this->fixture->getHtmlOfContactForm(
				array(
					'isSubmitted' => true,
					'requesteeName' => '',
					'requesteeEmail' => 'requestee@valid-email.org',
					'request' => 'the request'
				)
			)
		);
	}

	public function testSpecializedContactFormDisplaysErrorAfterSubmittingIfTheRequestWasEmpty() {
		$this->assertContains(
			$this->pi1->translate('label_no_empty_textarea'),
			$this->fixture->getHtmlOfContactForm(
				array(
					'showUid' => $this->realtyUid,
					'isSubmitted' => true,
					'requesteeName' => 'any name',
					'requesteeEmail' => 'requestee@valid-email.org',
					'request' => ''
				)
			)
		);
	}

	public function testGeneralContactFormDisplaysErrorAfterSubmittingIfTheRequestWasEmpty() {
		$this->assertContains(
			$this->pi1->translate('label_no_empty_textarea'),
			$this->fixture->getHtmlOfContactForm(
				array(
					'isSubmitted' => true,
					'requesteeName' => 'any name',
					'requesteeEmail' => 'requestee@valid-email.org',
					'request' => ''
				)
			)
		);
	}

	public function testSpecializedContactFormDisplaysErrorAfterSubmittingIfTheObjectHasNoContactDataAndNoDefaultEmailWasSet() {
		$this->pi1->setConfigurationValue('defaultEmail', '');

		$this->assertContains(
			$this->pi1->translate('label_no_contact_person'),
			$this->fixture->getHtmlOfContactForm(
				array(
					'showUid' => $this->realtyUid,
					'isSubmitted' => true,
					'requesteeName' => 'any name',
					'requesteeEmail' => 'requestee@valid-email.org',
					'request' => 'the request'
				)
			)
		);
	}

	public function testGeneralContactFormDisplaysAnErrorAfterSubmittingIfNoDefaultEmailAddressWasSet() {
		$this->pi1->setConfigurationValue('defaultEmail', '');

		$this->assertContains(
			$this->pi1->translate('label_no_contact_person'),
			$this->fixture->getHtmlOfContactForm(
				array(
					'isSubmitted' => true,
					'requesteeName' => 'any name',
					'requesteeEmail' => 'requestee@valid-email.org',
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
			$this->fixture->getHtmlOfContactForm(
				array(
					'isSubmitted' => true,
					'requesteeName' => '',
					'requesteeEmail' => '',
					'request' => ''
				)
			)
		);
	}

	public function testSpecializedContactFormShowsSubmittedMessageIfAllContentIsValid() {
		$this->assertContains(
			$this->pi1->translate('label_message_sent'),
			$this->fixture->getHtmlOfContactForm(
				array(
					'showUid' => $this->realtyUid,
					'isSubmitted' => true,
					'requesteeName' => 'any name',
					'requesteeEmail' => 'requestee@valid-email.org',
					'request' => 'the request'
				)
			)
		);
	}

	public function testGeneralContactFormShowsSubmittedMessageIfAllContentIsValid() {
		$this->assertContains(
			$this->pi1->translate('label_message_sent'),
			$this->fixture->getHtmlOfContactForm(
				array(
					'isSubmitted' => true,
					'requesteeName' => 'any name',
					'requesteeEmail' => 'requestee@valid-email.org',
					'request' => 'the request'
				)
			)
		);
	}


	/////////////////////////////////////////////
	// Tests concerning the content of e-mails.
	/////////////////////////////////////////////

	public function testSpecializedContactFormUsesDefaultEmailAddressIfTheObjectHasNoContactData() {
		$this->fixture->getHtmlOfContactForm(
			array(
				'showUid' => $this->realtyUid,
				'isSubmitted' => true,
				'requesteeName' => 'any name',
				'requesteeEmail' => 'requestee@valid-email.org',
				'request' => 'the request'
			)
		);

		$this->assertEquals(
			'any-default@email-address.org',
			tx_oelib_mailerFactory::getInstance()->getMailer()->getLastRecipient()
		);
	}

	public function testSpecializedContactFormUsesTheContactPersonsEmailIfTheObjectHasNoOwner() {
		$this->testingFramework->changeRecord(
			'tx_realty_objects',
			$this->realtyUid,
			array(
				'contact_person' => 'any contact person',
				'contact_email' => 'any-valid@email-address.org'
			)
		);
		$this->fixture->getHtmlOfContactForm(
			array(
				'showUid' => $this->realtyUid,
				'isSubmitted' => true,
				'requesteeName' => 'any name',
				'requesteeEmail' => 'requestee@valid-email.org',
				'request' => 'the request'
			)
		);

		$this->assertEquals(
			'any-valid@email-address.org',
			tx_oelib_mailerFactory::getInstance()->getMailer()->getLastRecipient()
		);
	}

	public function testSpecializedContactFormUsesTheDefaultEmailAddressEmailIfTheOwnersAddressWasNotValid() {
		$ownerUid = $this->createFeUser(array('email' => 'invalid-address'));
		$this->testingFramework->changeRecord(
			'tx_realty_objects',
			$this->realtyUid,
			array('owner' => $ownerUid)
		);
		$this->fixture->getHtmlOfContactForm(
			array(
				'showUid' => $this->realtyUid,
				'isSubmitted' => true,
				'requesteeName' => 'any name',
				'requesteeEmail' => 'requestee@valid-email.org',
				'request' => 'the request'
			)
		);

		$this->assertEquals(
			'any-default@email-address.org',
			tx_oelib_mailerFactory::getInstance()->getMailer()->getLastRecipient()
		);
	}

	public function testSpecializedContactFormUsesTheDefaultEmailAddressEmailIfNoOwnerIsSetAndTheContactPersonsAddressIsInvalid() {
		$this->testingFramework->changeRecord(
			'tx_realty_objects',
			$this->realtyUid,
			array(
				'contact_person' => 'Mr.Contact',
				'contact_email' => 'invalid-address'
			)
		);
		$this->fixture->getHtmlOfContactForm(
			array(
				'showUid' => $this->realtyUid,
				'isSubmitted' => true,
				'requesteeName' => 'any name',
				'requesteeEmail' => 'requestee@valid-email.org',
				'request' => 'the request'
			)
		);

		$this->assertEquals(
			'any-default@email-address.org',
			tx_oelib_mailerFactory::getInstance()->getMailer()->getLastRecipient()
		);
	}

	public function testSpecializedContactFormUsesTheOwnersEmailAddress() {
		$this->testingFramework->changeRecord(
			'tx_realty_objects',
			$this->realtyUid,
			array('owner' => $this->feUserId)
		);
		$this->fixture->getHtmlOfContactForm(
			array(
				'showUid' => $this->realtyUid,
				'isSubmitted' => true,
				'requesteeName' => 'any name',
				'requesteeEmail' => 'requestee@valid-email.org',
				'request' => 'the request'
			)
		);

		$this->assertEquals(
			'frontend-user@valid-email.org',
			tx_oelib_mailerFactory::getInstance()->getMailer()->getLastRecipient()
		);
	}

	public function testGeneralContactFormUsesTheDefaultEmailAddress() {
		$this->fixture->getHtmlOfContactForm(
			array(
				'isSubmitted' => true,
				'requesteeName' => 'any name',
				'requesteeEmail' => 'requestee@valid-email.org',
				'request' => 'the request'
			)
		);

		$this->assertEquals(
			'any-default@email-address.org',
			tx_oelib_mailerFactory::getInstance()->getMailer()->getLastRecipient()
		);
	}

	public function testHeaderContainsNameAndEmailAddress() {
		$this->fixture->getHtmlOfContactForm(
			array(
				'isSubmitted' => true,
				'requesteeName' => 'any name',
				'requesteeEmail' => 'requestee@valid-email.org',
				'request' => 'the request'
			)
		);

		$this->assertContains(
			'"any name" <requestee@valid-email.org>',
			tx_oelib_mailerFactory::getInstance()->getMailer()->getLastHeaders()
		);
	}

	public function testNameAndEmailAddressAreFetchedAutomaticallyIfAFeUserIsLoggedIn() {
		$this->fakeFeUserLogin($this->feUserId);
		$this->fixture->getHtmlOfContactForm(
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

	public function testNoEmailIsSentIfTheContactFormWasNotFilledCorrectly() {
		$this->fixture->getHtmlOfContactForm(
			array(
				'isSubmitted' => true,
				'requesteeName' => 'any name'
			)
		);

		$this->assertEquals(
			array(),
			tx_oelib_mailerFactory::getInstance()->getMailer()->getLastEmail()
		);
	}

	public function testEmailContainsTheTitleOfTheRequestedObjectIfASpecializedContactFormWasSubmitted() {
		$this->fixture->getHtmlOfContactForm(
			array(
				'showUid' => $this->realtyUid,
				'isSubmitted' => true,
				'requesteeName' => 'any name',
				'requesteeEmail' => 'requestee@valid-email.org',
				'request' => 'the request'
			)
		);

		$this->assertContains(
			self::$realtyTitle,
			tx_oelib_mailerFactory::getInstance()->getMailer()->getLastBody()
		);
	}

	public function testEmailContainsTheObjectNumberOfTheRequestedObjectIfASpecializedContactFormWasSubmitted() {
		$this->fixture->getHtmlOfContactForm(
			array(
				'showUid' => $this->realtyUid,
				'isSubmitted' => true,
				'requesteeName' => 'any name',
				'requesteeEmail' => 'requestee@valid-email.org',
				'request' => 'the request'
			)
		);

		$this->assertContains(
			self::$realtyObjectNumber,
			tx_oelib_mailerFactory::getInstance()->getMailer()->getLastBody()
		);
	}

	public function testEmailFromGeneralContactFormContainsASummaryStringOfTheFavoritesList() {
		$this->fixture->getHtmlOfContactForm(
			array(
				'isSubmitted' => true,
				'requesteeName' => 'a name of a requestee',
				'requesteeEmail' => 'requestee@valid-email.org',
				'request' => 'the request'
			),
			'summary of favorites'
		);

		$this->assertContains(
			'summary of favorites',
			tx_oelib_mailerFactory::getInstance()->getMailer()->getLastBody()
		);
	}

	public function testEmailSubjectIsGeneralForTheGeneralForm() {
		$this->fixture->getHtmlOfContactForm(
			array(
				'isSubmitted' => true,
				'requesteeName' => 'a name of a requestee',
				'requesteeEmail' => 'requestee@valid-email.org',
				'request' => 'the request'
			)
		);

		$this->assertContains(
			$this->pi1->translate('label_email_subject_general'),
			tx_oelib_mailerFactory::getInstance()->getMailer()->getLastSubject()
		);
	}

	public function testEmailSubjectIsSpecializedForTheSpecializedForm() {
		$this->fixture->getHtmlOfContactForm(
			array(
				'showUid' => $this->realtyUid,
				'isSubmitted' => true,
				'requesteeName' => 'any name',
				'requesteeEmail' => 'requestee@valid-email.org',
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
