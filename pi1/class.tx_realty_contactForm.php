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

/**
 * Class 'tx_realty_contactForm' for the 'realty' extension.
 * This class provides a contact form for the realty plugin.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 */
class tx_realty_contactForm extends tx_realty_pi1_FrontEndView {
	/**
	 * @var array data for the contact form
	 */
	private $contactFormData = array(
		'isSubmitted' => false,
		'showUid' => 0,
		'requesterName' => '',
		'requesterStreet' => '',
		'requesterZip' => '',
		'requesterCity' => '',
		'requesterEmail' => '',
		'requesterPhone' => '',
		'request' => '',
		'summaryStringOfFavorites' => '',
	);

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
	 * @param array contact form data, may be empty
	 *
	 * @return string HTML of the contact form, will not be empty
	 */
	public function render(array $contactFormData = array()) {
		$this->storeContactFormData($contactFormData);

		// setOrHideSpecializedView() will fail if the 'showUid' parameter is
		// set to an invalid value.
		if (!$this->setOrHideSpecializedView()) {
			$this->setMarker(
				'message_noResultsFound',
				$this->translate('message_noResultsFound_contact_form')
			);

			return $this->getSubpart('EMPTY_RESULT_VIEW');
		}

		$this->hideNonVisibleFormFields();

		$subpartName = 'CONTACT_FORM';
		$errorMessages = array(
			'requesterName' => '', 'requesterStreet' => '', 'requesterZip' => '',
			'requesterCity' => '', 'requesterEmail' => '', 'requesterPhone' => '',
			'request' => '', 'contact_form' => '',
		);

		$this->fillContactInformationFieldsForLoggedInUser();
		$this->setFormValues();

		if ($this->contactFormData['isSubmitted']
			&& $this->checkFormData($errorMessages)
		) {
			if ($this->sendRequest()) {
				$subpartName = 'CONTACT_FORM_SUBMITTED';
			} else {
				$errorMessages['contact_form'] = 'label_no_contact_person';
			}
		}

		$this->setErrorMessageContent($errorMessages);

		return $this->getSubpart($subpartName);
	}

	/**
	 * Hides form fields that are not configured to be visible.
	 */
	private function hideNonVisibleFormFields() {
		$this->hideSubpartsArray(
			array_diff(
				array('name', 'street', 'zip_and_city', 'telephone'),
				$this->getConfigurationArray('visibleContactFormFields')
			),
			'contact_form_wrapper'
		);
	}

	/**
	 * Checks whether the form data is correctly filled.
	 *
	 * @param array associative array with field names as keys, locallang keys
	 *              of error messages will be added as values by this function
	 *
	 * @return boolean true if the form data was correctly filled, false
	 *                 otherwise
	 */
	private function checkFormData(&$errorMessages) {
		$noErrorsSet = true;

		if (!$this->isLoggedIn()) {
			if (!$this->isValidName($this->contactFormData['requesterName'])) {
				$errorMessages['requesterName'] = 'label_set_name';
				$noErrorsSet = false;
			}
			if (!$this->isValidEmail($this->contactFormData['requesterEmail'])) {
				$errorMessages['requesterEmail'] = 'label_set_valid_email_address';
				$noErrorsSet = false;
			}
			$nonFilledRequiredFields = $this->getEmptyRequiredFields();
			if (!empty($nonFilledRequiredFields)) {
				foreach ($nonFilledRequiredFields as $key) {
					$suffix = '';
					if (($key == 'requesterZip') || ($key == 'requesterCity')) {
						$suffix = '_' . $key;
					}
					$errorMessages[$key] = 'message_required_field' . $suffix;
				}
				$noErrorsSet = false;
			}
		}
		if ($this->contactFormData['request'] == '') {
			$errorMessages['request'] = 'label_no_empty_textarea';
			$noErrorsSet = false;
		}

		return $noErrorsSet;
	}

	/**
	 * Returns an array of the form data keys that have empty values but are
	 * configured to be required fields.
	 *
	 * @return array keys of form fields that are empty but must not be empty,
	 *               may be empty
	 */
	private function getEmptyRequiredFields() {
		if (!$this->hasConfValueString(
			'requiredContactFormFields', 's_contactForm')
		) {
			return array();
		}

		$result = array();
		$requiredFields
			= $this->getConfigurationArray('requiredContactFormFields');

		foreach (array(
			'requesterName' => 'name',
			'requesterStreet' => 'street',
			'requesterZip' => 'zip',
			'requesterCity' => 'city',
			'requesterPhone' => 'telephone',
		) as $formDataKey => $fieldName) {
			if (in_array($fieldName, $requiredFields)
				&& (trim($this->contactFormData[$formDataKey]) == '')
			) {
				$result[] = $formDataKey;
			}
		}

		return $result;
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
	 * @return boolean true if the contact data for sending an e-mail could be
	 *                 fetched and the send e-mail function was called,
	 *                 false otherwise
	 *
	 * @see https://bugs.oliverklee.com/show_bug.cgi?id=961
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
			$this->getEmailSender() . $this->getBccAddress(),
			'',
			'UTF-8'
		);

		return true;
	}

	/**
	 * Returns the e-mail body. It contains the request and the requester's
	 * contact data.
	 *
	 * @param string name of the contact person, must not be empty
	 *
	 * @return string the body of the e-mail to send, contains the request and
	 *                the contact data of the requester, will not be empty
	 */
	private function getFilledEmailBody($contactPerson) {
		foreach (array(
			'request' => $this->contactFormData['request'],
			'requester_name' => $this->contactFormData['requesterName'],
			'requester_email' => $this->contactFormData['requesterEmail'],
			'requester_phone' => $this->contactFormData['requesterPhone'],
			'requester_street' => $this->contactFormData['requesterStreet'],
			'requester_zip_and_city' => trim(
				$this->contactFormData['requesterZip'] . ' ' .
					$this->contactFormData['requesterCity']
				),
			'summary_string_of_favorites'
				=> $this->contactFormData['summaryStringOfFavorites'],
			'contact_person' => $contactPerson
		) as $marker => $value) {
			$this->setOrDeleteMarkerIfNotEmpty($marker, $value, '', 'wrapper');
		}

		return $this->getSubpart('EMAIL_BODY');
	}

	/**
	 * Returns the subject for the e-mail to send. It depends on the type of
	 * contact form whether the object number will be included.
	 *
	 * @return string the e-mail's subject, will not be empy
	 */
	private function getEmailSubject() {
		if ($this->isSpecializedView()) {
			$result = $this->translate('label_email_subject_specialized') .
				' ' . $this->getRealtyObject()->getProperty('object_number');
		} else {
			$result = $this->translate('label_email_subject_general');
		}

		return $result;
	}

	/**
	 * Returns the formatted "From:" header line for the e-mail to send.
	 * The validity of the requester's name and e-mail address is not checked by
	 * this function.
	 *
	 * @return string formatted e-mail header line containing the sender,
	 *                will not be empty
	 */
	private function getEmailSender() {
		if ($this->contactFormData['requesterName'] == '') {
			$result = $this->contactFormData['requesterEmail'] . LF;
		} else {
			$result = 'From: "' .
				$this->contactFormData['requesterName'] . '" ' . '<' .
				$this->contactFormData['requesterEmail'] . '>' . LF;
		}
		return $result;
	}

	/**
	 * Returns a formatted header line for the BCC if a blind carbon copy
	 * address is set in the TS setup.
	 *
	 * @return string formatted e-mail header for BCC ending with LF or an
	 *                empty string if no recipient was configured
	 */
	private function getBccAddress() {
		$result = '';

		if ($this->hasConfValueString(
			'blindCarbonCopyAddress', 's_contactForm')
		) {
			$result = 'Bcc: ' . $this->getConfValueString(
				'blindCarbonCopyAddress', 's_contactForm'
			) . LF;
		}

		return $result;
	}

	/**
	 * Sets the requester's data if the requester is a logged in user. Does
	 * nothing if no user is logged in.
	 */
	private function setDataForLoggedInUser() {
		$loggedInUser = tx_oelib_MapperRegistry
				::get('tx_realty_Mapper_FrontEndUser')->getLoggedInUser();

		if (!$loggedInUser) {
			return;
		}

		$visibilityKeysForFormData = array(
			'requesterName' => 'name',
			'requesterStreet' => 'street',
			'requesterPhone' => 'telephone',
			'requesterZip' => 'zip_and_city',
			'requesterCity' => 'zip_and_city',
		);
		$visibleFields = array_intersect(
			$visibilityKeysForFormData,
			$this->getConfigurationArray('visibleContactFormFields')
		);

		foreach (array(
			'requesterName' => getName,
			'requesterStreet' => getStreet,
			'requesterZip' => getZip,
			'requesterCity' => getCity,
			'requesterEmail' => getEMailAddress,
			'requesterPhone' => getPhoneNumber,
		) as $contactFormDataKey => $functionName) {
			if (isset($visibleFields[$contactFormDataKey])
				|| ($contactFormDataKey == 'requesterEmail')
			) {
				$this->contactFormData[$contactFormDataKey]
					= $loggedInUser->$functionName();
			}
		}
	}

	/**
	 * Returns an array of configuration value that is a comma-separated list in
	 * the s_contactForm sheet.
	 *
	 * @param string key of the configuration value to get as an array, must not
	 *               be empty
	 *
	 * @return array configuration of $key, empty if no configuration was found
	 */
	private function getConfigurationArray($key) {
		return t3lib_div::trimExplode(
			',', $this->getConfValueString($key, 's_contactForm'), true
		);
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
	 * @return array owner or contact person and the corresponding
	 *               e-mail address in an array, contains the default
	 *               e-mail address if no valid address was found, empty
	 *               if the expected contact data was not found
	 */
	private function getContactData() {
		$result = array('name' => '', 'email' => '');
		$contactData = $this->fetchContactDataFromSource();

		if ($this->isValidEmail($contactData['email'])) {
			$result = $contactData;
		} elseif ($this->hasConfValueString(
			'defaultContactEmail', 's_contactForm')
		) {
			$result['email'] = $this->getConfValueString(
				'defaultContactEmail', 's_contactForm'
			);
		}

		return $result;
	}

	/**
	 * Fetches the contact data from the source defined in the realty record and
	 * returns it in an array.
	 *
	 * @return array contact data array, will always contain the two
	 *               elements 'email' and 'name'
	 */
	private function fetchContactDataFromSource() {
		if (!$this->isSpecializedView()) {
			return array('email' => '', 'name' => '');
		}

		$result = array('email' => '', 'name' => '');

		// Gets the contact data from the chosen source. No data is fetched if
		// the 'contact_data_source' is set to an invalid value.
		switch ($this->getRealtyObject()->getProperty('contact_data_source')
		) {
			case REALTY_CONTACT_FROM_OWNER_ACCOUNT:
				$ownerUid = $this->getRealtyObject()->getProperty('owner');
				if ($ownerUid > 0) {
					try {
						$owner = tx_oelib_MapperRegistry
							::get('tx_realty_Mapper_FrontEndUser')
							->find($ownerUid);
						$result['email'] = $owner->getEMailAddress();
						$result['name'] = $owner->getName();
					} catch (tx_oelib_Exception_NotFound $exception) {
					}
				}
				break;
			case REALTY_CONTACT_FROM_REALTY_OBJECT:
				$result['email'] = $this->getRealtyObject()
					->getProperty('contact_email');
				$result['name'] = $this->getRealtyObject()
					->getProperty('contact_person');
				break;
			default:
				break;
		}

		return $result;
	}

	/**
	 * Sets or hides the specialized contact form.
	 *
	 * @return boolean false if the specialized contact form is supposed to
	 *                 be set but no object data could be fetched, true
	 *                 otherwise
	 */
	private function setOrHideSpecializedView() {
		$wasSuccessful = true;
		$subpartsToHide = '';

		if ($this->isSpecializedView()) {
			$subpartsToHide = 'email_from_general_contact_form';

			if (!tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
				->existsModel($this->getShowUid())
			) {
				$wasSuccessful = false;
			} else {
				foreach (array('object_number', 'title', 'uid') as $key) {
					$value = ($key == 'uid')
						? $this->getRealtyObject()->getUid()
						: $this->getRealtyObject()->getProperty($key);
					$this->setMarker($key, $value, '', 'wrapper');
				}
			}
		} else {
			$subpartsToHide = 'specialized_contact_form,' .
				'email_from_specialized_contact_form';
		}

		$this->hideSubparts($subpartsToHide, 'wrapper');

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
			$this->hideSubparts('requester_data_is_uneditable', 'wrapper');
		}
		$this->setMarker('declare_uneditable', $readonlyMarkerContent);
	}

	/**
	 * Sets an error message to the marker 'ERROR_MESSAGE'.
	 *
	 * @param array associative array with the fields where the error occured
	 *              as keys and the locallang key of an error message as value
	 *              if there was one, must not be empty
	 */
	private function setErrorMessageContent(array $errors) {
		foreach ($errors as $formFieldName => $locallangKey) {
			if ($locallangKey != '') {
				$this->setMarker(
					'ERROR_MESSAGE', $this->translate($locallangKey) . '<br/>'
				);
				$formattedError = $this->getSubpart('CONTACT_FORM_ERROR');
			} else {
				$formattedError = '';
			}

			$this->setMarker(
				strtoupper($formFieldName) . '_ERROR', $formattedError
			);
		}
	}

	/**
	 * Checks whether the specialized view should be set.
	 *
	 * @return boolean true if the view should be specialized, false otherwise
	 */
	private function isSpecializedView() {
		return ($this->getShowUid() > 0);
	}

	/**
	 * Checks whether an e-mail address is valid.
	 *
	 * @param string e-mail address to check, may be empty
	 *
	 * @return boolean true if the e-mail address is valid, false otherwise
	 */
	private function isValidEmail($emailAddress) {
		return (($emailAddress != '') && t3lib_div::validEmail($emailAddress));
	}

	/**
	 * Checks whether a name is non-empty and valid.
	 *
	 * @param string the name to check, may be empty
	 *
	 * @return boolean true if the name is valid which means it does not contain
	 *                 any characters that are indicative of header injection,
	 *                 false otherwise
	 */
	private function isValidName($name) {
		if ($name == '') {
			return true;
		}

		return (boolean) preg_match('/^[\S ]+$/s', $name)
			&& !preg_match('/[<>"]/', $name);
	}

	/**
	 * Sets the form fields' values to the currently stored form data.
	 * Therefore converts special characters to HTML entities.
	 */
	private function setFormValues() {
		foreach (array(
			'request' => $this->contactFormData['request'],
			'requester_name' => $this->contactFormData['requesterName'],
			'requester_street' => $this->contactFormData['requesterStreet'],
			'requester_zip' => $this->contactFormData['requesterZip'],
			'requester_city' => $this->contactFormData['requesterCity'],
			'requester_email' => $this->contactFormData['requesterEmail'],
			'requester_phone' => $this->contactFormData['requesterPhone'],
		) as $marker => $value) {
			$this->setMarker($marker, htmlspecialchars($value));
		}
	}

	/**
	 * Stores the submitted contact form data locally.
	 *
	 * @param array contact form data, may be empty
	 */
	private function storeContactFormData(array $contactFormData) {
		foreach (
			array(
				'requesterName', 'requesterStreet', 'requesterZip',
				'requesterCity', 'requesterEmail', 'requesterPhone', 'request',
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
			= isset($contactFormData['summaryStringOfFavorites'])
			? $contactFormData['summaryStringOfFavorites'] : '';
	}

	/**
	 * Returns the current "showUid".
	 *
	 * @return integer UID of the current realty object, will be >= 0
	 */
	private function getShowUid() {
		return $this->contactFormData['showUid'];
	}

	/**
	 * Gets the realty object for the "showUid" defined in the contact data
	 * array.
	 *
	 * @return tx_realty_Model_RealtyObject realty object for current UID
	 */
	private function getRealtyObject() {
		return  tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->find($this->getShowUid());
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/pi1/class.tx_realty_contactForm.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/pi1/class.tx_realty_contactForm.php']);
}
?>