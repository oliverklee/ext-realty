<?php
/***************************************************************
* Copyright notice
*
* (c) 2009-2011 Saskia Metzler (saskia@merlin.owl.de)
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
 * Class tx_realty_pi1_ObjectsByOwnerListView for the "realty" extension.
 *
 * This class creates a list of objects by a given owner (FE user).
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class tx_realty_pi1_ObjectsByOwnerListView extends tx_realty_pi1_AbstractListView {
	/**
	 * @var string the list view type to display
	 */
	protected $currentView = 'objects_by_owner';

	/**
	 * @var string the locallang key to the label of a list view
	 */
	protected $listViewLabel = 'label_offerings_by';

	/**
	 * @var boolean whether Google Maps should be shown in this view
	 */
	protected $isGoogleMapsAllowed = TRUE;

	/**
	 * Initializes some view-specific data.
	 */
	protected function initializeView() {
		$this->listViewLabel = $this->getTitleForTheObjectsByOwnerList();
		$this->unhideSubparts(
			'favorites_url,add_to_favorites_button,wrapper_checkbox,' .
			'back_link'
		);
	}

	/**
	 * Gets the WHERE clause part specific to this view.
	 *
	 * @return string the WHERE clause parts to add, will be empty if no view
	 *                specific WHERE clause parts are needed
	 */
	protected function getViewSpecificWhereClauseParts() {
		if (!$this->existsOwner()) {
			return ' AND 0 = 1';
		}

		return ' AND tx_realty_objects.owner = ' . $this->getOwner()->getUid();
	}

	/**
	 * Returns the title for the list of objects by one owner.
	 * The title will contain a localized string for 'label_offerings_by' plus
	 * the owner's label.
	 *
	 * If there is no cached owner, the return value will be 'label_sorry'
	 * as a localized string. (setEmptyResultView will add the corresponding
	 * error message then.) An owner is cached through cacheSelectedOwner in
	 * the main function if a valid UID was provided by $this->piVars.
	 *
	 * @return string localized string for 'label_offerings_by' plus the
	 *                owner's label or the string for 'label_sorry' if
	 *                there is no owner at all
	 */
	private function getTitleForTheObjectsByOwnerList() {
		$result = $this->translate('label_sorry');

		if ($this->existsOwner()) {
			$result = $this->translate('label_offerings_by') . ' ' .
				htmlspecialchars($this->getOwnerLabel());
		}

		return $result;
	}

	/**
	 * Returns a FE user's company if non-empty, else the first
	 * name and last name if provided, else the name. If none of these is
	 * provided, the user name will be returned.
	 *
	 * Note: This function may only be called if there is a valid owner.
	 *
	 * @return string label for the owner, will not be empty
	 *
	 * @see existsOwner
	 */
	private function getOwnerLabel() {
		$owner = $this->getOwner();

		return ($owner->hasCompany() ? $owner->getCompany() : $owner->getName());
	}

	/**
	 * Creates the message for "no results found".
	 *
	 * @return string the localized message, will not be empty
	 */
	protected function createNoResultsMessage() {
		if (!$this->existsOwner()) {
			return $this->translate('message_no_such_owner');
		}

		return parent::createNoResultsMessage();
	}

	/**
	 * Returns the selected owner.
	 *
	 * @throws tx_oelib_Exception_NotFound if no owner is selected or the owner
	 *                                     does not exist
	 *
	 * @return tx_realty_Model_FrontEndUser the selected owner
	 */
	private function getOwner() {
		$ownerUid = intval($this->piVars['owner']);
		if ($ownerUid <= 0) {
			throw new tx_oelib_Exception_NotFound('No owner is selected.', 1333036590);
		}

		$mapper = tx_oelib_MapperRegistry::get('tx_realty_Mapper_FrontEndUser');
		if (!$mapper->existsModel($ownerUid)) {
			throw new tx_oelib_Exception_NotFound('The owner does not exist.', 1333036603);
		}

		return $mapper->find($ownerUid);
	}

	/**
	 * Checks whether a non-deleted, non-hidden user has been selected for
	 * display.
	 *
	 * @return boolean TRUE if an existing user has been selected, FALSE otherwise
	 */
	protected function existsOwner() {
		try {
			$this->getOwner();
		} catch (tx_oelib_Exception_NotFound $exception) {
			return FALSE;
		}

		return TRUE;
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/realty/pi1/class.tx_realty_pi1_ObjectsByOwnerListView.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/realty/pi1/class.tx_realty_pi1_ObjectsByOwnerListView.php']);
}
?>