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
		/** @var tx_realty_Mapper_RealtyObject $mapper */
		$mapper = Tx_Oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject');
		/** @var tx_realty_Model_RealtyObject $realtyObject */
		$realtyObject = $mapper->find($piVars['showUid']);

		$statusClasses = array(
			tx_realty_Model_RealtyObject::STATUS_VACANT => 'vacant',
			tx_realty_Model_RealtyObject::STATUS_RESERVED => 'reserved',
			tx_realty_Model_RealtyObject::STATUS_SOLD => 'sold',
			tx_realty_Model_RealtyObject::STATUS_RENTED => 'rented',
		);

		$this->setMarker(
			'statusclass', $statusClasses[$realtyObject->getStatus()]
		);

		/** @var tx_realty_pi1_Formatter $formatter */
		$formatter = t3lib_div::makeInstance(
			'tx_realty_pi1_Formatter',
			$piVars['showUid'], $this->conf, $this->cObj
		);
		$this->setMarker('status', $formatter->getProperty('status'));

		return $this->getSubpart('FIELD_WRAPPER_STATUS');
	}
}