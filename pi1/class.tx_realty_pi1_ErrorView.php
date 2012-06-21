<?php
/***************************************************************
* Copyright notice
*
* (c) 2008-2012 Saskia Metzler <saskia@merlin.owl.de>
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
 * Class 'tx_realty_pi1_ErrorView' for the 'realty' extension.
 *
 * This class renders error messages.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 */
class tx_realty_pi1_ErrorView extends tx_realty_pi1_FrontEndView {
	/**
	 * Returns the HTML for an error view.
	 *
	 * @param array $errorMessage
	 *        key of the error message to render (must be in the first element a numeric array, the rest is ignored)
	 *
	 * @return string HTML of the error message, will not be empty
	 */
	public function render(array $errorMessage = array()) {
		if ($errorMessage[0] == 'message_please_login') {
			$message = $this->getLinkedPleaseLogInMessage();
		} else {
			$message = $this->translate($errorMessage[0]);
		}

		$this->setMarker('error_message', $message);

		return $this->getSubpart('ERROR_VIEW');
	}

	/**
	 * Returns the linked please-login error message. The message is linked to
	 * the page configured in "loginPID".
	 *
	 * @return string linked please-login error message with a redirect URL to
	 *                the current page, will not be empty
	 */
	private function getLinkedPleaseLogInMessage() {
		$piVars = $this->piVars;
		unset($piVars['DATA']);

		$redirectUrl = t3lib_div::locationHeaderUrl(
			$this->cObj->typoLink_URL(array(
				'parameter' => $GLOBALS['TSFE']->id,
				'additionalParams' => t3lib_div::implodeArrayForUrl(
					$this->prefixId, $piVars, '', TRUE, TRUE
				),
			))
		);

		return $this->cObj->typoLink(
			$this->translate('message_please_login'),
			array(
				'parameter' => $this->getConfValueInteger('loginPID'),
				'additionalParams' => t3lib_div::implodeArrayForUrl(
					'', array('redirect_url' => $redirectUrl)
				),
			)
		);
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/realty/pi1/class.tx_realty_pi1_ErrorView.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/realty/pi1/class.tx_realty_pi1_ErrorView.php']);
}
?>