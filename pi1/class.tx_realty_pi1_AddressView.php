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
 * This class renders the address of a single realty object.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 */
class tx_realty_pi1_AddressView extends tx_realty_pi1_FrontEndView {
	/**
	 * Returns the address view as HTML.
	 *
	 * @param array $piVars piVars array, must contain the key "showUid" with a valid realty object UID as value
	 *
	 * @return string HTML for the address view or an empty string if the
	 *                realty object with the provided UID has no address at all
	 */
	public function render(array $piVars = array()) {
		/** @var tx_realty_Mapper_RealtyObject $mapper */
		$mapper = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject');
		/** @var tx_realty_Model_RealtyObject $object */
		$object = $mapper->find($piVars['showUid']);
		$address = $object->getAddressAsHtml();

		$this->setOrDeleteMarkerIfNotEmpty(
			'address', $address, '', 'field_wrapper'
		);

		return $this->getSubpart('FIELD_WRAPPER_ADDRESS');
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/realty/pi1/class.tx_realty_pi1_AddressView.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/realty/pi1/class.tx_realty_pi1_AddressView.php']);
}