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
 * This class renders the print button.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Bernd Schönbach <bernd@oliverklee.de>
 */
class tx_realty_pi1_PrintPageButtonView extends tx_realty_pi1_FrontEndView {
	/**
	 * Returns the print button as HTML.
	 *
	 * @param array $piVars piVars array, may be empty
	 *
	 * @return string HTML for the print button, will not be empty
	 */
	public function render(array $piVars = array()) {
		return $this->getSubpart('FIELD_WRAPPER_PRINTPAGEBUTTON');
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/realty/pi1/class.tx_realty_pi1_PrintPageButtonView.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/realty/pi1/class.tx_realty_pi1_PrintPageButtonView.php']);
}