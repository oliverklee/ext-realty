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
 * @author		Saskia Metzler <saskia@merlin.owl.de>
 */

require_once(t3lib_extMgm::extPath('oelib').'class.tx_oelib_templatehelper.php');
require_once(t3lib_extMgm::extPath('oelib').'class.tx_oelib_mailerFactory.php');
require_once(t3lib_extMgm::extPath('realty').'lib/class.tx_realty_object.php');

class tx_realty_contactForm extends tx_oelib_templatehelper {
	/** plugin in which the contact form is used */
	private $plugin = null;

	/** data for the contact form */
	private $contactFormData = array(
		'isSubmitted' => false,
		'showUid' => 0,
		'requesteeName' => '',
		'requesteeEmail' => '',
		'requesteePhone' => '',
		'request' => '',
		'summaryStringOfFavorites' => ''
	);

	/** instance of the realty object */
	private $realtyObject = null;

	/**
	 * The constructor.
	 *
	 * @param	tx_oelib_templatehelper		plugin in which uses this contact
	 * 										form
	 */
	public function __construct(tx_oelib_templatehelper $plugin) {
		$this->plugin = $plugin;
		// For the templatehelper's functions about setting labels and filling
		// markers, the plugin's templatehelper object is used as the inherited
		// templatehelper does not have all configuration which would be
		// necessary for this.
		$this->plugin->getTemplateCode();
		$this->plugin->setLabels();
		// For configuration stuff the own inherited templatehelper can be used.
		$this->init($this->plugin->getConfiguration);
		$this->pi_initPIflexForm();

		$this->realtyObject = t3lib_div::makeInstance('tx_realty_object');
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
	public function getHtmlOfContactForm(
		array $contactFormData,
		$summaryStringOfFavorites = ''
	) {
		$this->storeContactFormData($contactFormData, $summaryStringOfFavorites);
		$subpartName = 'CONTACT_FORM_ERROR';
		$errorMessages = array();

		if (!$this->contactFormData['isSubmitted']) {
			$this->checkToHideRequesteeData();
			if ($this->setOrHideSpecializedView()) {
				$subpartName = 'CONTACT_FORM';
			} else {
				$errorMessages[] = 'message_noResultsFound_contact_form';
			}
		} else {
			if (!$this->isLoggedIn()) {
				if (!$this->isValidName($this->contactFormData['requesteeName'])) {
					$errorMessages[] = 'label_set_name';
				}
				if (!$this->isValidEmail($this->contactFormData['requesteeEmail'])) {
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

		$this->setErrorMessageContent($errorMessages);

		return $this->plugin->getSubpart($subpartName);
	}

	/**
	 * Sends the filled-in request of the contact form to the owner of the
	 * object (or to the contact person if there is no owner).
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
		if (empty($contactData) || !$this->setOrHideSpecializedView()) {
			return false;
		}

		tx_oelib_mailerFactory::getInstance()->getMailer()->sendEmail(
			$contactData['email'],
			$this->getEmailSubject(),
			$this->getFilledEmailBody($contactData['name']),
			$this->getEmailSender(),
			'',
			'UTF-8'
		);

		return true;
	}

	/**
	 * Returns the e-mail body. It contains the request and the requestee's
	 * contact data.
	 *
	 * @param	string		name of the contact person, must not be empty
	 *
	 * @return	string		the body of the e-mail to send, contains the request
	 * 						and the contact data of the requestee, will not be
	 * 						empty
	 */
	private function getFilledEmailBody($contactPerson) {
		$this->setDataForLoggedInUser();

		foreach (array(
			'request' => $this->contactFormData['request'],
			'requestee_name' => $this->contactFormData['requesteeName'],
			'requestee_phone' => $this->contactFormData['requesteePhone'],
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
	 * The valitidy of the requestee's name and e-mail address is not checked by
	 * this function.
	 *
	 * @return	string		formatted string of header information, will not be
	 * 						empty
	 */
	private function getEmailSender() {
		$this->setDataForLoggedInUser();

		return 'From: "'.$this->contactFormData['requesteeName'].'" '
			.'<'.$this->contactFormData['requesteeEmail'].'>'.chr(10);
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
		$body = trim(preg_replace('/\n|\r/', chr(13).chr(10), $rawEmailBody));
		return preg_replace(
			'/(\r\n){2,}/', chr(13).chr(10).chr(13).chr(10), $body
		);
	}

	/**
	 * Sets the requestee's data if the requestee is a logged in user. Does
	 * nothing if no user is logged in.
	 */
	private function setDataForLoggedInUser() {
		if (!$this->isLoggedIn()) {
			return;
		}

		$ownerData = $this->getFeUserData($this->getFeUserUid());
		foreach (array(
			'requesteeName' => 'name',
			'requesteeEmail' => 'email',
			'requesteePhone' => 'telephone'
		) as $contactFormDataKey => $ownerDataKey) {
			$this->contactFormData[$contactFormDataKey]
				= $ownerData[$ownerDataKey];
		}
	}

	/**
	 * Returns the name and e-mail address of the owner of a realty object in an
	 * associative array with the keys 'name' and 'email'. If the object has no
	 * owner, the contact person's name and e-mail address are returned instead.
	 *
	 * If there is neither an owner nor a contact person or if the fetched
	 * e-mail address is invalid, the configured default e-mail address is
	 * returned instead. The result then does not contain a name.
	 *
	 * If neither an owner nor a contact person nor a default e-mail address can
	 * be found, an empty array is returned.
	 *
	 * @return	array		owner or contact person and the corresponding
	 * 						e-mail address in an array, contains the default
	 * 						e-mail address if no valid address was found, empty
	 * 						no contact data was found
	 *
	 */
	private function getContactData() {
		$result = array();

		$ownerData = array();
		$this->loadCurrentRealtyObject();
		$ownerUid = $this->realtyObject->getProperty('owner');
		if ($ownerUid > 0) {
			$ownerData = $this->getFeUserData($ownerUid);
		} else {
			$ownerData['email'] = $this->realtyObject->getProperty('contact_email');
			$ownerData['name'] = $this->realtyObject->getProperty('contact_person');
		}

		if ($this->isValidEmail($ownerData['email'])) {
			$result['email'] = $ownerData['email'];
		}

		if ($result['email']) {
			$result['name'] = $ownerData['name'];
		} elseif ($this->plugin->hasConfValueString('defaultEmail')) {
			$result['email'] = $this->plugin->getConfValueString('defaultEmail');
		}

		return $result;
	}

	/**
	 * Returns the name, the e-mail address and the phone number of a FE user.
	 *
	 * @param	integer		UID of the FE user, must be > 0
	 *
	 * @return	array		associative array with the keys name, email,
	 * 						telephone, will be empty if the database result
	 * 						could not be fetched
	 */
	private function getFeUserData($uid) {
		$result = array();

		$dbResult = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'name, email, telephone',
			'fe_users',
			'uid='.$uid
		);
		if ($dbResult && $row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbResult)) {
			$result = $row;
		}

		return $result;
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

		if ($this->isSpecializedView()) {
			$this->plugin->hideSubparts(
				'email_from_general_contact_form',
				'wrapper'
			);
			$this->loadCurrentRealtyObject();

			if (!$this->realtyObject->isRealtyObjectDataEmpty()) {
				foreach (array('object_number', 'title', 'uid') as $key) {
					$this->plugin->setMarker(
						$key, $this->realtyObject->getProperty($key)
					);
				}
			} else {
				$wasSuccessful = false;
			}
		} else {
			$this->plugin->hideSubparts(
				'specialized_contact_form, email_from_specialized_contact_form',
				'wrapper'
			);
		}

		return $wasSuccessful;
	}

	/**
	 * Hides the wrapper 'REQUESTEE_DATA' if a FE user is logged in.
	 */
	private function checkToHideRequesteeData() {
		if ($this->isLoggedIn()) {
			$this->plugin->hideSubparts('requestee_data', 'wrapper');
		}
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
				'requesteeName', 'requesteeEmail', 'requesteePhone', 'request'
			) as $key
		) {
			$this->contactFormData[$key] = isset($contactFormData[$key])
				? trim($contactFormData[$key]) : '';
		}

		$this->contactFormData['isSubmitted']
			= isset($this->contactFormData['isSubmitted'])
			? (boolean) $contactFormData['isSubmitted'] : false;
		$this->contactFormData['showUid']
			= isset($this->contactFormData['showUid'])
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

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/pi1/class.tx_realty_contactForm.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/pi1/class.tx_realty_contactForm.php']);
}
?>
