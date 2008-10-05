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
 * Class 'tx_realty_contactForm' for the 'realty' extension.
 * This class provides a contact form for the realty plugin.
 *
 * @package		TYPO3
 * @subpackage	tx_realty
 *
 * @author		Saskia Metzler <saskia@merlin.owl.de>
 */

require_once(t3lib_extMgm::extPath('oelib') . 'tx_oelib_commonConstants.php');
require_once(t3lib_extMgm::extPath('oelib') . 'class.tx_oelib_templatehelper.php');
require_once(t3lib_extMgm::extPath('oelib') . 'class.tx_oelib_mailerFactory.php');
require_once(t3lib_extMgm::extPath('oelib') . 'class.tx_oelib_db.php');

require_once(t3lib_extMgm::extPath('realty') . 'lib/class.tx_realty_object.php');

class tx_realty_contactForm extends tx_oelib_templatehelper {
	/** plugin in which the contact form is used */
	private $plugin = null;

	/** data for the contact form */
	private $contactFormData = array(
		'isSubmitted' => false,
		'showUid' => 0,
		'requesterName' => '',
		'requesterEmail' => '',
		'requesterPhone' => '',
		'request' => '',
		'summaryStringOfFavorites' => ''
	);

	/** instance of the realty object */
	private $realtyObject = null;

	/**
	 * The constructor.
	 *
	 * @param	tx_oelib_templatehelper		plugin which uses this contact form
	 */
	public function __construct(tx_oelib_templatehelper $plugin) {
		$this->plugin = $plugin;
		// For the templatehelper's functions about setting labels and filling
		// markers, the plugin's templatehelper object is used as the inherited
		// templatehelper does not have all configuration which would be
		// necessary for this.
		$this->init($this->plugin->getConfiguration());
		$this->pi_initPIflexForm();

		$this->realtyObject = t3lib_div::makeInstance('tx_realty_object');
	}

	/**
	 * Frees as much memory that has been used by this object as possible.
	 */
	public function __destruct() {
		if ($this->realtyObject) {
			$this->realtyObject->__destruct();
		}

		unset($this->formCreator, $this->plugin, $this->realtyObject);

		parent::__destruct();
	}

	/**
	 * Returns the contact form in HTML.
	 * If $contactFormData contains a value greater zero for the element
	 * 'showUid', the contact form will be specific for the current realty
	 * object and the requests are sent directly to the owner (or the contact
	 * person if there is no owner). Otherwise the content is unspecific and
	 * requests always go to the default e-mail address.
	 * The form's content also depends on whether a FE user is logged in or not.
	 * Registered users do not need to fill in their name, e-mail address and
	 * telephone number as they already exist in the database.
	 * If the request has been successfully sent, the HTML string will contain
	 * a message about this, otherwise a specific error message.
	 *
	 * @param	array		contact form data, may be empty
	 * @param	string		summary string of the current favorites list, may be
	 * 						empty
	 *
	 * @return	string		HTML of the contact form, will not be empty
	 */
	public function render(
		array $contactFormData, $summaryStringOfFavorites = ''
	) {
		$this->storeContactFormData($contactFormData, $summaryStringOfFavorites);

		// setOrHideSpecializedView() will fail if the 'showUid' parameter is
		// set to an invalid value.
		if (!$this->setOrHideSpecializedView()) {
			$this->plugin->setMarker(
				'message_noResultsFound',
				$this->plugin->translate('message_noResultsFound_contact_form')
			);

			return $this->plugin->getSubpart('EMPTY_RESULT_VIEW');
		}

		$subpartName = 'CONTACT_FORM';
		$errorMessages = array();
		$this->fillContactInformationFieldsForLoggedInUser();
		$this->setFormValues();

		if ($this->contactFormData['isSubmitted']) {
			if (!$this->isLoggedIn()) {
				if (!$this->isValidName($this->contactFormData['requesterName'])) {
					$errorMessages[] = 'label_set_name';
				}
				if (!$this->isValidEmail($this->contactFormData['requesterEmail'])) {
					$errorMessages[] = 'label_set_valid_email_address';
				}
			}
			if ($this->contactFormData['request'] == '') {
				$errorMessages[] = 'label_no_empty_textarea';
			}
		}

		if (empty($errorMessages) && $this->contactFormData['isSubmitted']) {
			if ($this->sendRequest()) {
				$subpartName = 'CONTACT_FORM_SUBMITTED';
			} else {
				$errorMessages[] = 'label_no_contact_person';
			}
		}

		if (empty($errorMessages)) {
			$this->plugin->hideSubparts('contact_form_error', 'wrapper');
		} else {
			$this->setErrorMessageContent($errorMessages);
		}

		return $this->plugin->getSubpart($subpartName);
	}

	/**
	 * Sends the filled-in request of the contact form to the owner of the
	 * object (or to the contact person if there is no owner).
	 * If a recipient for a blind carbon copy is configured, the request is
	 * also sent to this address.
	 *
	 * Note: When this extension requires TYPO3 4.2, the return value of
	 * sendEmail() should be returned instead of just returning true after
	 * sending an e-mail.
	 *
	 * @return	boolean		true if the contact data for sending an e-mail could
	 * 						be fetched and the send e-mail function was called,
	 * 						false otherwise
	 *
	 * @see		https://bugs.oliverklee.com/show_bug.cgi?id=961
	 */
	private function sendRequest() {
		$contactData = $this->getContactData();
		if (($contactData['email'] == '') || !$this->setOrHideSpecializedView()) {
			return false;
		}

		tx_oelib_mailerFactory::getInstance()->getMailer()->sendEmail(
			$contactData['email'],
			$this->getEmailSubject(),
			$this->getFilledEmailBody($contactData['name']),
			$this->getEmailSender().$this->getBccAddress(),
			'',
			'UTF-8'
		);

		return true;
	}

	/**
	 * Returns the e-mail body. It contains the request and the requester's
	 * contact data.
	 *
	 * @param	string		name of the contact person, must not be empty
	 *
	 * @return	string		the body of the e-mail to send, contains the request
	 * 						and the contact data of the requester, will not be
	 * 						empty
	 */
	private function getFilledEmailBody($contactPerson) {
		foreach (array(
			'request' => $this->contactFormData['request'],
			'requester_name' => $this->contactFormData['requesterName'],
			'requester_email' => '('.$this->contactFormData['requesterEmail'].')',
			'requester_phone' => $this->contactFormData['requesterPhone'],
			'summary_string_of_favorites'
				=> $this->contactFormData['summaryStringOfFavorites'],
			'contact_person' => $contactPerson
		) as $marker => $value) {
			$this->plugin->setOrDeleteMarkerIfNotEmpty(
				$marker,
				$value,
				'',
				'wrapper'
			);
		}

		return $this->formatEmailBody($this->plugin->getSubpart('EMAIL_BODY'));
	}

	/**
	 * Returns the subject for the e-mail to send. It depends on the type of
	 * contact form whether the object number will be included.
	 *
	 * @return	string		the e-mail's subject, will not be empy
	 */
	private function getEmailSubject() {
		if ($this->isSpecializedView()) {
			$this->loadCurrentRealtyObject();
			$result = $this->plugin->translate('label_email_subject_specialized')
				.' '.$this->realtyObject->getProperty('object_number');
		} else {
			$result = $this->plugin->translate('label_email_subject_general');
		}

		return $result;
	}

	/**
	 * Returns the formatted "From:" header line for the e-mail to send.
	 * The validity of the requester's name and e-mail address is not checked by
	 * this function.
	 *
	 * @return	string		formatted e-mail header line containing the sender,
	 * 						will not be empty
	 */
	private function getEmailSender() {
		return 'From: "'.$this->contactFormData['requesterName'].'" '
			.'<'.$this->contactFormData['requesterEmail'].'>'.LF;
	}

	/**
	 * Returns a formatted header line for the BCC if a blind carbon copy
	 * address is set in the TS setup.
	 *
	 * @return	string		formatted e-mail header for BCC ending with LF or an
	 * 						empty string if no recipient was configured
	 */
	private function getBccAddress() {
		$result = '';

		if ($this->plugin->hasConfValueString('blindCarbonCopyAddress')) {
			$result = 'Bcc: '
				.$this->plugin->getConfValueString('blindCarbonCopyAddress').LF;
		}

		return $result;
	}

	/**
	 * Formats the e-mail body.
	 * Replaces single linefeeds with carriage return plus linefeed and strips
	 * surplus blank lines.
	 *
	 * @param	string		raw e-mail body, must not be empty
	 *
	 * @return	string		formatted e-mail body, will not be empty
	 */
	private function formatEmailBody($rawEmailBody) {
		$body = trim(preg_replace('/\n|\r/', CRLF, $rawEmailBody));
		return preg_replace(
			'/(\r\n){2,}/', CRLF.CRLF, $body
		);
	}

	/**
	 * Sets the requester's data if the requester is a logged in user. Does
	 * nothing if no user is logged in.
	 */
	private function setDataForLoggedInUser() {
		if (!$this->isLoggedIn()) {
			return;
		}

		$ownerData = $this->getFeUserData($this->getFeUserUid());
		foreach (array(
			'requesterName' => 'name',
			'requesterEmail' => 'email',
			'requesterPhone' => 'telephone'
		) as $contactFormDataKey => $ownerDataKey) {
			$this->contactFormData[$contactFormDataKey]
				= $ownerData[$ownerDataKey];
		}
	}

	/**
	 * Returns the name and e-mail address of the contact person in an
	 * associative array with the keys 'name' and 'email'.
	 * According to 'contact_data_source', either the owner's account data or
	 * the data from the realty object ('contact_email' and 'contact_person')
	 * is used.
	 *
	 * If the fetched e-mail address is invalid, the configured default e-mail
	 * address is returned instead. The result then will not contain a name.
	 *
	 * If no contact person's data could be fetched and no default e-mail
	 * address is configured, an empty array is returned.
	 *
	 * @return	array		owner or contact person and the corresponding
	 * 						e-mail address in an array, contains the default
	 * 						e-mail address if no valid address was found, empty
	 * 						if the expected contact data was not found
	 *
	 */
	private function getContactData() {
		$result = array('name' => '', 'email' => '');

		$contactData = $this->fetchContactDataFromSource();

		if ($this->isValidEmail($contactData['email'])) {
			$result = $contactData;
		} elseif ($this->plugin->hasConfValueString('defaultContactEmail')) {
			$result['email'] = $this->plugin->getConfValueString(
				'defaultContactEmail'
			);
		}

		return $result;
	}

	/**
	 * Fetches the contact data from the source defined in the realty record and
	 * returns it in an array.
	 *
	 * @return	array		contact data array, will always contain the two
	 * 						elements 'email' and 'name'
	 */
	private function fetchContactDataFromSource() {
		$this->loadCurrentRealtyObject();

		// Gets the contact data from the chosen source. No data is fetched if
		// the 'contact_data_source' is set to an invalid value.
		switch ($this->realtyObject->getProperty('contact_data_source')) {
			case REALTY_CONTACT_FROM_OWNER_ACCOUNT:
				$result = $this->getFeUserData(
					$this->realtyObject->getProperty('owner')
				);
				break;
			case REALTY_CONTACT_FROM_REALTY_OBJECT:
				$result['email'] = $this->realtyObject->getProperty('contact_email');
				$result['name'] = $this->realtyObject->getProperty('contact_person');
				break;
			default:
				$result = array('email' => '', 'name' => '');
				break;
		}

		return $result;
	}

	/**
	 * Returns the name, the e-mail address and the phone number of a FE user or
	 * an empty array if there is none.
	 *
	 * @param	integer		UID of the FE user (> 0) or zero which means
	 * 						there is no FE user
	 *
	 * @return	array		associative array with the keys name, email,
	 * 						telephone, will not be empty
	 */
	private function getFeUserData($uid) {
		if ($uid == 0) {
			return array('name' => '', 'email' => '', 'telephone' => '');
		}

		$dbResult = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'name, email, telephone',
			'fe_users',
			'uid=' . $uid . tx_oelib_db::enableFields('fe_users')
		);
		if (!$dbResult) {
			throw new Exception(DATABASE_QUERY_ERROR);
		}

		$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbResult);

		return ($row)
			? $row
			: array('name' => '', 'email' => '', 'telephone' => '');
	}

	/**
	 * Sets or hides the specialized contact form.
	 *
	 * @return	boolean		false if the specialized contact form is supposed to
	 * 						be set but no object data could be fetched, true
	 * 						otherwise
	 */
	private function setOrHideSpecializedView() {
		$wasSuccessful = true;
		$subpartsToHide = '';

		if ($this->isSpecializedView()) {
			$subpartsToHide = 'email_from_general_contact_form';

			$this->loadCurrentRealtyObject();
			if ($this->realtyObject->isRealtyObjectDataEmpty()) {
				$wasSuccessful = false;
			}

			foreach (array('object_number', 'title', 'uid') as $key) {
				$this->plugin->setMarker(
					$key, $this->realtyObject->getProperty($key), '', 'wrapper'
				);
			}
		} else {
			$subpartsToHide = 'specialized_contact_form,'
				.'email_from_specialized_contact_form';
		}

		$this->plugin->hideSubparts($subpartsToHide, 'wrapper');

		return $wasSuccessful;
	}

	/**
	 * Declares the fields for the requester's contact data as not editable and
	 * fills them with the current FE user's data if a user is logged in.
	 */
	private function fillContactInformationFieldsForLoggedInUser() {
		$readonlyMarkerContent = '';
		if ($this->isLoggedIn()) {
			$readonlyMarkerContent = 'disabled="disabled"';
			$this->setDataForLoggedInUser();
		} else {
			$this->plugin->hideSubparts('requester_data_is_uneditable', 'wrapper');
		}
		$this->plugin->setMarker('declare_uneditable', $readonlyMarkerContent);
	}

	/**
	 * Sets an error message to the marker 'ERROR_MESSAGE'.
	 *
	 * @param	array		keys of the error messages to set, may be empy
	 */
	private function setErrorMessageContent(array $keys) {
		$errorMessage = '';
		foreach ($keys as $key) {
			$errorMessage .= $this->plugin->translate($key).'<br />';
		}
		$this->plugin->setMarker('ERROR_MESSAGE', $errorMessage);
	}

	/**
	 * Checks whether the specialized view should be set.
	 *
	 * @return	boolean		true if the view should be specialized, false
	 * 						otherwise
	 */
	private function isSpecializedView() {
		return ($this->contactFormData['showUid'] > 0);
	}

	/**
	 * Checks whether an e-mail address is valid.
	 *
	 * @param	string		e-mail address to check, may be empty
	 *
	 * @return	boolean		true if the e-mail address is valid, false otherwise
	 */
	private function isValidEmail($emailAddress) {
		return (($emailAddress != '') && t3lib_div::validEmail($emailAddress));
	}

	/**
	 * Checks whether a name is non-empty and valid.
	 *
	 * @param	string		the name to check, may be empty
	 *
	 * @return	boolean		true if the name is non-empty and valid, false
	 * 						otherwise
	 */
	private function isValidName($name) {
		return (boolean) preg_match('/^[\S ]+$/s', $name);
	}

	/**
	 * Sets the form fields' values to the currently stored form data.
	 * Therefore converts special characters to HTML entities.
	 */
	private function setFormValues() {
		foreach (array(
			'request' => $this->contactFormData['request'],
			'requester_name' => $this->contactFormData['requesterName'],
			'requester_email' => $this->contactFormData['requesterEmail'],
			'requester_phone' => $this->contactFormData['requesterPhone'],
		) as $marker => $value) {
			$this->plugin->setMarker($marker, htmlspecialchars($value));
		}
	}

	/**
	 * Stores the submitted contact form data locally.
	 *
	 * @param	array		contact form data, may be empty
	 * @param	string		summary string of the current favorites list, may be
	 * 						empty
	 */
	private function storeContactFormData(
		array $contactFormData, $summaryStringOfFavorites
	) {
		foreach (
			array(
				'requesterName', 'requesterEmail', 'requesterPhone', 'request'
			) as $key
		) {
			$this->contactFormData[$key] = isset($contactFormData[$key])
				? trim($contactFormData[$key]) : '';
		}

		$this->contactFormData['isSubmitted']
			= isset($contactFormData['isSubmitted'])
			? (boolean) $contactFormData['isSubmitted'] : false;
		$this->contactFormData['showUid']
			= isset($contactFormData['showUid'])
			? intval($contactFormData['showUid']) : 0;
		$this->contactFormData['summaryStringOfFavorites']
			= $summaryStringOfFavorites;
	}

	/**
	 * Loads the current realty object if it is not already loaded.
	 */
	private function loadCurrentRealtyObject() {
		if ($this->realtyObject->isRealtyObjectDataEmpty()) {
			$this->realtyObject->loadRealtyObject(
				intval($this->contactFormData['showUid'])
			);
		}
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/pi1/class.tx_realty_contactForm.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/pi1/class.tx_realty_contactForm.php']);
}
?>