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
 * This class renders the buying price or rent (depending on the object type)
 * of a single realty object.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class tx_realty_pi1_PriceView extends tx_realty_pi1_FrontEndView {
	/**
	 * Returns this view as HTML.
	 *
	 * @param array $piVars piVars array, must contain the key "showUid" with a valid realty object UID as value
	 *
	 * @return string HTML for this view, will be empty if the realty object
	 *                with the provided UID has no prices for the defined object
	 *                type
	 */
	public function render(array $piVars = array()) {
		$realtyObject = tx_oelib_MapperRegistry
			::get('tx_realty_Mapper_RealtyObject')->find($piVars['showUid']);
		if ($this->getConfValueBoolean('priceOnlyIfAvailable')
			&& $realtyObject->isRentedOrSold()
		) {
			return '';
		}

		$hasValidContent = TRUE;

		switch ($realtyObject->getProperty('object_type')) {
			case tx_realty_Model_RealtyObject::TYPE_FOR_SALE:
				$keyToShow = 'buying_price';
				$keyToHide = 'rent_excluding_bills';
				break;
			case tx_realty_Model_RealtyObject::TYPE_FOR_RENT:
				$keyToShow = 'rent_excluding_bills';
				$keyToHide = 'buying_price';
				break;
			default:
				$hasValidContent = FALSE;
				break;
		}

		if ($hasValidContent) {
			$formatter = t3lib_div::makeInstance(
				'tx_realty_pi1_Formatter', $piVars['showUid'], $this->conf,
				$this->cObj
			);
			$hasValidContent = $this->setOrDeleteMarkerIfNotEmpty(
				$keyToShow,
				$formatter->getProperty($keyToShow),
				'',
				'field_wrapper'
			);
			$this->hideSubparts($keyToHide, 'field_wrapper');
		}

		return $hasValidContent ? $this->getSubpart('FIELD_WRAPPER_PRICE') : '';
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/realty/pi1/class.tx_realty_pi1_PriceView.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/realty/pi1/class.tx_realty_pi1_PriceView.php']);
}