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
	 * @param string[] $errorMessage
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
				'parameter' => $this->getFrontEndController()->id,
				'additionalParams' => t3lib_div::implodeArrayForUrl(
					$this->prefixId, $piVars, '', TRUE, TRUE
				),
				'useCacheHash' => TRUE,
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