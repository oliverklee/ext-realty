<?php
/***************************************************************
* Copyright notice
*
* (c) 2009-2010 Bernd Schönbach <bernd@oliverklee.de>
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
 * Class tx_realty_pi1_NextPreviousButtonsView for the "realty" extension.
 *
 * This class renders the "next" and "previous" buttons.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Bernd Schönbach <bernd@oliverklee.de>
 */
class tx_realty_pi1_NextPreviousButtonsView extends tx_realty_pi1_FrontEndView {
	/**
	 * Renders the "previous" and "next" buttons.
	 *
	 * @param array piVars array, may be empty
	 *
	 * @return string the HTML output for the "previous" and "next" buttons,
	 *                will be empty if both buttons are hidden
	 */
	public function render(array $piVars = array()) {
		$this->piVars = $this->sanitizePiVars();
		if (!$this->canButtonsBeRendered()) {
			return '';
		}

		$visibilityTree = tx_oelib_ObjectFactory::make(
			'tx_oelib_Visibility_Tree',
			array('nextPreviousButtons' => array(
				'previousButton' => false, 'nextButton' => false
			))
		);

		$recordPosition = $this->piVars['recordPosition'];
		if ($recordPosition > 0) {
			$previousRecordUid = $this->getPreviousRecordUid();
			$this->setMarker(
				'previous_url',
				$this->getButtonUrl($recordPosition - 1, $previousRecordUid)
			);
			$visibilityTree->makeNodesVisible(array('previousButton'));
		}

		$nextRecordUid = $this->getNextRecordUid();
		if ($nextRecordUid > 0) {
			$visibilityTree->makeNodesVisible(array('nextButton'));
			$this->setMarker(
				'next_url',
				$this->getButtonUrl($recordPosition + 1, $nextRecordUid)
			);
		}

		$this->hideSubpartsArray(
			$visibilityTree->getKeysOfHiddenSubparts(), 'FIELD_WRAPPER'
		);

		$visibilityTree->__destruct();

		return $this->getSubpart('FIELD_WRAPPER_NEXTPREVIOUSBUTTONS');
	}

	/**
	 * Checks whether all preconditions are fulfilled for the rendering of the
	 * buttons.
	 *
	 * @return boolean TRUE if the buttons can be rendered, FALSE otherwise
	 */
	private function canButtonsBeRendered() {
		if (!$this->getConfValueBoolean('enableNextPreviousButtons')) {
			return FALSE;
		}
		if (intval($this->piVars['recordPosition']) < 0) {
			return FALSE;
		}
		if (!in_array(
				$this->piVars['listViewType'],
				array('my_objects', 'favorites', 'objects_by_offerer', 'realty_list')
			)
		) {
			return FALSE;
		}
		if (($this->piVars['listUid'] <= 0)) {
			return FALSE;
		}

		return tx_oelib_db::existsRecordWithUid(
			'tt_content',
			intval($this->piVars['listUid']),
			tx_oelib_db::enableFields('tt_content')
		);
	}


	/////////////////////////
	// Sanitizing functions
	/////////////////////////

	/**
	 * Sanitizes the piVars needed for this view.
	 *
	 * This function will store the sanitized piVars into $this->piVars.
	 *
	 * @return array the sanitized piVars, will be empty if an empty array was
	 *               given.
	 */
	private function sanitizePiVars() {
		$sanitizedPiVars = array();

		$sanitizedPiVars['recordPosition'] = (isset($this->piVars['recordPosition']))
			? intval($this->piVars['recordPosition'])
			: -1;
		$sanitizedPiVars['listUid'] = (isset($this->piVars['listUid']))
			? max(intval($this->piVars['listUid']), 0)
			: 0;

		$sanitizedPiVars['listViewType'] = (isset($this->piVars['listViewType']))
			? $this->piVars['listViewType']
			: '';

		// listViewLimitation will be sanitized, only if it actually is used.
		if (isset($this->piVars['listViewLimitation'])) {
		  	$sanitizedPiVars['listViewLimitation']
		  		= $this->piVars['listViewLimitation'];
		}

		return $sanitizedPiVars;
	}

	/**
	 * Sanitizes the listViewLimitation piVar, unserializes, and decodes it.
	 *
	 * @param string $listViewLimitation
	 *        the content of the piVar listViewLimitation, may be empty
	 *
	 * @return array the data stored in the listViewLimitation string as array.
	 */
	private function sanitizeAndSplitListViewLimitation() {
		$rawData = unserialize(
			base64_decode($this->piVars['listViewLimitation'])
		);
		if (!is_array($rawData) || empty($rawData)) {
			return array();
		}

		$allowedKeys = array_merge(
			array('search', 'orderBy', 'descFlag', 'uid'),
			tx_realty_filterForm::getPiVarKeys()
		);
		$result = array();

		foreach ($allowedKeys as $allowedKey) {
			if (isset($rawData[$allowedKey])) {
				$result[$allowedKey] = $rawData[$allowedKey];
			}
		}

		return $result;
	}


	/////////////////////////////////////////////
	// Functions for retrieving the record UIDs
	/////////////////////////////////////////////

	/**
	 * Retrieves the UID of the record previous to the currently shown one.
	 *
	 * Before calling this function, ensure that $this->piVars['recordPosition']
	 * is >= 1.
	 *
	 * @return integer the UID of the previous record, will be > 0
	 */
	private function getPreviousRecordUid() {
		return $this->getRecordAtPosition($this->piVars['recordPosition'] - 1);
	}

	/**
	 * Retrieves the UID of the record next to to the currently shown one.
	 *
	 * A return value of 0 means that no record could be found at the given
	 * position.
	 *
	 * @return integer the UID of the next record, will be >= 0
	 */
	private function getNextRecordUid() {
		return $this->getRecordAtPosition($this->piVars['recordPosition'] + 1);
	}

	/**
	 * Retrieves the UID for the record at the given record position.
	 *
	 * @param integer $recordPosition
	 *        the position of the record to find, must be >= 0
	 *
	 * @return integer the UID of the record at the given position, will be >= 0
	 */
	private function getRecordAtPosition($recordPosition) {
		$listView = tx_realty_pi1_ListViewFactory::make(
			$this->piVars['listViewType'], $this->conf, $this->cObj
		);

		$listView->setPiVars($this->sanitizeAndSplitListViewLimitation());

		$result = $listView->getUidForRecordNumber($recordPosition);
		$listView->__destruct();

		return $result;
	}


	//////////////////////////////////////////
	// Functions for building the button URL
	//////////////////////////////////////////

	/**
	 * Returns the URL for the buttons.
	 *
	 * @param integer $recordPosition
	 *        the position of the record the URL points to
	 * @param integer $recordUid
	 *        the UID of the record the URL points to
	 *
	 * @return string the htmlspecialchared URL for the button, will not be empty
	 */
	private function getButtonUrl($recordPosition, $recordUid) {
		$additionalParameters = $this->piVars;
		$additionalParameters['recordPosition'] = $recordPosition;
		$additionalParameters['showUid'] = $recordUid;
		$urlParameters = array(
			'parameter' => $this->cObj->data['pid'],
			'additionalParams' => t3lib_div::implodeArrayForUrl(
				$this->prefixId, $additionalParameters
			),
			'useCacheHash' => TRUE,
		);

		return htmlspecialchars($this->cObj->typoLink_URL($urlParameters));
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/pi1/class.tx_realty_pi1_NextPreviousButtonsView.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/pi1/class.tx_realty_pi1_NextPreviousButtonsView.php']);
}
?>