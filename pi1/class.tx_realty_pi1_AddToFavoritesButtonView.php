<?php
/***************************************************************
* Copyright notice
*
* (c) 2009-2013 Bernd Schönbach <bernd@oliverklee.de>
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
 * Class tx_realty_pi1_AddToFavoritesButtonView for the "realty" extension.
 *
 * This class renders the add-to-favorites button.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Bernd Schönbach <bernd@oliverklee.de>
 */
class tx_realty_pi1_AddToFavoritesButtonView extends tx_realty_pi1_FrontEndView {
	/**
	 * Returns the add-to-favorites button.
	 *
	 * "favoritesPID" is required to be configured.
	 *
	 * @param array $piVars piVars array, must contain the key "showUid" with a valid realty object UID as value
	 *
	 * @return string HTML for the buttons, will not be empty
	 */
	public function render(array $piVars = array()) {
		$favoritesUrl = htmlspecialchars(
			$this->cObj->typoLink_URL(
				array('parameter' => $this->getConfValueInteger('favoritesPID'))
			)
		);

		$this->setMarker('favorites_url', $favoritesUrl);
		$this->setMarker('uid', $piVars['showUid']);

		return $this->getSubpart('FIELD_WRAPPER_ADDTOFAVORITESBUTTON');
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/realty/pi1/class.tx_realty_pi1_AddToFavoritesButtonView.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/realty/pi1/class.tx_realty_pi1_AddToFavoritesButtonView.php']);
}
?>