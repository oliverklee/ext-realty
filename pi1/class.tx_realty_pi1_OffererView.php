<?php
/***************************************************************
* Copyright notice
*
* (c) 2009-2011 Saskia Metzler <saskia@merlin.owl.de>
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
 * Class 'tx_realty_pi1_OffererView' for the 'realty' extension.
 *
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
	 * @param array piVars array, must contain the key "showUid" with a valid
	 *              realty object UID as value
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
	 * @param integer UID of the realty object for which to receive the contact
	 *                data, must be > 0
	 *
	 * @return string the contact data as HTML, will be empty if none was found
	 */
	private function fetchContactDataFromSource($uid) {
		$offererList = tx_oelib_ObjectFactory::make(
			'tx_realty_offererList', $this->conf, $this->cObj
		);
		$realtyObject = tx_oelib_MapperRegistry
			::get('tx_realty_Mapper_RealtyObject')->find($uid);

		switch ($realtyObject->getProperty('contact_data_source')) {
			case REALTY_CONTACT_FROM_OWNER_ACCOUNT:
				$result = $offererList->renderOneItem(
					intval($realtyObject->getProperty('owner'))
				);
				break;
			case REALTY_CONTACT_FROM_REALTY_OBJECT:
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
				break;
		}

		$offererList->__destruct();

		return $result;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/pi1/class.tx_realty_pi1_OffererView.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/pi1/class.tx_realty_pi1_OffererView.php']);
}
?>