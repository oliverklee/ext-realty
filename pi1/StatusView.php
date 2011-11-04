<?php
/***************************************************************
* Copyright notice
*
* (c) 2010-2011 Oliver Klee <typo3-coding@oliverklee.de>
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
 * Class tx_realty_pi1_StatusView for the "realty" extension.
 *
 * This class represents a view that contains the status of an object.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class tx_realty_pi1_StatusView extends tx_realty_pi1_FrontEndView {
	/**
	 * Returns the rendered view.
	 *
	 * @param array $piVars
	 *        piVars, must contain the key "showUid" with a valid realty object
	 *        UID as value
	 *
	 * @return string HTML for this view, will not be empty
	 */
	public function render(array $piVars = array()) {
		$realtyObject = tx_oelib_MapperRegistry
			::get('tx_realty_Mapper_RealtyObject')->find($piVars['showUid']);

		$statusClasses = array(
			tx_realty_Model_RealtyObject::STATUS_VACANT => 'vacant',
			tx_realty_Model_RealtyObject::STATUS_RESERVED => 'reserved',
			tx_realty_Model_RealtyObject::STATUS_SOLD => 'sold',
			tx_realty_Model_RealtyObject::STATUS_RENTED => 'rented',
		);

		$this->setMarker(
			'statusclass', $statusClasses[$realtyObject->getStatus()]
		);

		$formatter = tx_oelib_ObjectFactory::make(
			'tx_realty_pi1_Formatter',
			$piVars['showUid'], $this->conf, $this->cObj
		);
		$this->setMarker('status', $formatter->getProperty('status'));

		return $this->getSubpart('FIELD_WRAPPER_STATUS');
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/pi1/StatusView.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/pi1/StatusView.php']);
}
?>