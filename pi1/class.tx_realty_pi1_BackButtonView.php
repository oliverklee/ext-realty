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
 * This class renders the back button.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Bernd Schönbach <bernd@oliverklee.de>
 */
class tx_realty_pi1_BackButtonView extends tx_realty_pi1_FrontEndView {
	/**
	 * Returns the back button.
	 *
	 * @param array $piVars piVars array, may be empty
	 *
	 * @return string HTML for the back button, will not be empty
	 */
	public function render(array $piVars = array()) {
		if ($this->nextPreviousButtonsAreEnabled()) {
			$backUrl = $this->getBackLinkUrl();
			$javaScriptBack = '';
		} else {
			$backUrl =  '#';
			$javaScriptBack = ' onclick="history.back(); return false;"';
		}

		$this->setMarker('BACK_URL', $backUrl);
		$this->setMarker('JAVASCRIPT_BACK', $javaScriptBack);

		return $this->getSubpart('FIELD_WRAPPER_BACKBUTTON');
	}

	/**
	 * Builds the URL for the back link.
	 *
	 * @return string the URL to the listView, will be empty if listUid is not
	 *                set or zero in piVars
	 */
	private function getBackLinkUrl() {
		if (intval($this->piVars['listUid']) == 0) {
			return '';
		}

		$listUid = intval($this->piVars['listUid']);

		try {
			$listViewPage = tx_oelib_db::selectSingle(
				'pid',
				'tt_content',
				'uid=' . $listUid . tx_oelib_db::enableFields('tt_content')
			);
		} catch (tx_oelib_Exception_EmptyQueryResult $exception) {
			return '';
		}

		$additionalParameters = array();
		if (isset($this->piVars['listViewLimitation'])) {
			$decodedParameters = json_decode($this->piVars['listViewLimitation'], TRUE);
			$additionalParameters = (is_array($decodedParameters)) ? $decodedParameters : array();
		}

		$urlParameter = array(
			'parameter' => $listViewPage['pid'],
			'additionalParams' => t3lib_div::implodeArrayForUrl(
				$this->prefixId, $additionalParameters
			),
			'useCacheHash' => FALSE,
		);

		return htmlspecialchars($this->cObj->typoLink_URL($urlParameter));
	}

	/**
	 * Checks whether the display of the next/previous buttons is enabled.
	 *
	 * @return boolean TRUE if the buttons should be displayed, FALSE otherwise
	 */
	private function nextPreviousButtonsAreEnabled() {
		if (!isset($this->piVars['listUid'])) {
			return FALSE;
		}
		if (!$this->getConfValueBoolean('enableNextPreviousButtons')) {
			return FALSE;
		}

		$displayedSingleViewParts = t3lib_div::trimExplode(
			',', $this->getConfValueString('singleViewPartsToDisplay'), TRUE
		);

		if (!in_array('nextPreviousButtons', $displayedSingleViewParts)) {
			return FALSE;
		}

		return TRUE;
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/realty/pi1/class.tx_realty_pi1_BackButtonView.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/realty/pi1/class.tx_realty_pi1_BackButtonView.php']);
}