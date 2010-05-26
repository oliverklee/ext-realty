<?php
/***************************************************************
* Copyright notice
*
* (c) 2009-2010 Saskia Metzler <saskia@merlin.owl.de>
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
 * Class 'tx_realty_pi1_FurtherDescriptionView' for the 'realty' extension.
 *
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
	 * @param array piVars array, must contain the key "showUid" with a valid
	 *              realty object UID as value
	 *
	 * @return string HTML for the further-description view will be epmty if
	 *                the realty object with the provided UID has neither data
	 *                in "equipment" nor "loaction" nor "misc"
	 */
	public function render(array $piVars = array()) {
		$hasContent = FALSE;
		$model = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->find($piVars['showUid']);

		foreach (array('equipment', 'location', 'misc') as $key) {
			$value = $this->pi_RTEcssText($model->getProperty($key));

			$hasContent = $this->setOrDeleteMarkerIfNotEmpty(
				$key, $value, '', 'field_wrapper'
			) || $hasContent;
		}

		return ($hasContent
			? $this->getSubpart('FIELD_WRAPPER_FURTHERDESCRIPTION')
			: ''
		);
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/pi1/class.tx_realty_pi1_FurtherDescriptionView.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/pi1/class.tx_realty_pi1_FurtherDescriptionView.php']);
}
?>