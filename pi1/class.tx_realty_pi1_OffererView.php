<?php
/*
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
 * This class renders the offerer view.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 * @author Bernd Schönbach <bernd@oliverklee.de>
 */
class tx_realty_pi1_OffererView extends tx_realty_pi1_FrontEndView {
	/**
	 * Returns the offerer view as HTML.
	 *
	 * @param array $piVars piVars array, must contain the key "showUid" with a valid realty object UID as value
	 *
	 * @return string HTML for the offerer view or an empty string if the
	 *                realty object with the provided UID has no data to show
	 */
	public function render(array $piVars = array()) {
		$contactData = $this->fetchContactDataFromSource($piVars['showUid']);
		$this->setMarker('offerer_information', $contactData);

		return ($contactData != '')
			? $this->getSubpart('FIELD_WRAPPER_OFFERER')
			: '';
	}

	/**
	 * Fetches the contact data from the source defined in the realty record and
	 * returns it in an array.
	 *
	 * @param int $uid UID of the realty object for which to receive the contact data, must be > 0
	 *
	 * @return string the contact data as HTML, will be empty if none was found
	 */
	private function fetchContactDataFromSource($uid) {
		$offererList = t3lib_div::makeInstance(
			'tx_realty_offererList', $this->conf, $this->cObj
		);
		$realtyObject = tx_oelib_MapperRegistry
			::get('tx_realty_Mapper_RealtyObject')->find($uid);

		switch ($realtyObject->getProperty('contact_data_source')) {
			case tx_realty_Model_RealtyObject::CONTACT_DATA_FROM_OWNER_ACCOUNT:
				$result = $offererList->renderOneItem((int)$realtyObject->getProperty('owner'));
				break;
			case tx_realty_Model_RealtyObject::CONTACT_DATA_FROM_REALTY_OBJECT:
				$result = $offererList->renderOneItemWithTheDataProvided(array(
					'email' => $realtyObject->getProperty('contact_email'),
					'company' => $realtyObject->getProperty('employer'),
					'telephone' => $realtyObject->getContactPhoneNumber(),
					'name' => $realtyObject->getProperty('contact_person'),
					'image' => '',
				));
				break;
			default:
				$result = '';
		}

		return $result;
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/realty/pi1/class.tx_realty_pi1_OffererView.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/realty/pi1/class.tx_realty_pi1_OffererView.php']);
}