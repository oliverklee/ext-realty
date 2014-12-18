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
 * This class renders the description of "equipment" and "location" and the
 * "misc" text field of a single realty object.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 */
class tx_realty_pi1_FurtherDescriptionView extends tx_realty_pi1_FrontEndView {
	/**
	 * Returns the further-description view as HTML.
	 *
	 * @param array $piVars piVars array, must contain the key "showUid" with a valid realty object UID as value
	 *
	 * @return string HTML for the further-description view will be epmty if
	 *                the realty object with the provided UID has neither data
	 *                in "equipment" nor "loaction" nor "misc"
	 */
	public function render(array $piVars = array()) {
		$hasContent = FALSE;
		/** @var tx_realty_Mapper_RealtyObject $mapper */
		$mapper = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject');
		/** @var tx_realty_Model_RealtyObject $model */
		$model = $mapper->find($piVars['showUid']);

		foreach (array('equipment', 'location', 'misc') as $key) {
			$value = $this->pi_RTEcssText($model->getProperty($key));

			$hasContent = $this->setOrDeleteMarkerIfNotEmpty($key, $value, '', 'field_wrapper') || $hasContent;
		}

		return $hasContent ? $this->getSubpart('FIELD_WRAPPER_FURTHERDESCRIPTION') : '';
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/realty/pi1/class.tx_realty_pi1_FurtherDescriptionView.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/realty/pi1/class.tx_realty_pi1_FurtherDescriptionView.php']);
}