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
 * Class 'tx_realty_pi1_ContactButtonView' for the 'realty' extension.
 *
 * This class renders the contact button.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 */
class tx_realty_pi1_ContactButtonView extends tx_realty_pi1_FrontEndView {
	/**
	 * Returns the contact button as HTML. For this, requires a "contactPID" to
	 * be configured.
	 *
	 * @param array PiVars array, must contain the key "showUid" with a valid
	 *              realty object UID or zero as value. Note that for zero, the
	 *              linked contact form will not contain any realty object
	 *              information.
	 *
	 * @return string HTML for the contact button or an empty string if the
	 *                configured "contactPID" equals the current page or is not
	 *                set at all
	 */
	public function render(array $piVars = array()) {
		if (!$this->hasConfValueInteger('contactPID')
			|| ($this->getConfValueInteger('contactPID') == $GLOBALS['TSFE']->id)
		) {
			return '';
		}

		$contactUrl = htmlspecialchars($this->cObj->typoLink_URL(array(
			'parameter' => $this->getConfValueInteger('contactPID'),
			'additionalParams' => t3lib_div::implodeArrayForUrl(
				'',
				array($this->prefixId => array('showUid' => $piVars['showUid']))
			),
		)));
		$this->setMarker('contact_url', $contactUrl);

		return $this->getSubpart('FIELD_WRAPPER_CONTACTBUTTON');
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/pi1/class.tx_realty_pi1_ContactButtonView.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/pi1/class.tx_realty_pi1_ContactButtonView.php']);
}
?>