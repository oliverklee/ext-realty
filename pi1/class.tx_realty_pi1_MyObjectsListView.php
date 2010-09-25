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
 * Class tx_realty_pi1_MyObjectsListView for the "realty" extension.
 *
 * This class represents the "my objects" list view.
 *
 * This view may only be rendered if a user is logged-in at the front end.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 * @author Saskia Metzler <saskia@merlin.owl.de>
 * @author Bernd Schönbach <bernd@oliverklee.de>
 */
class tx_realty_pi1_MyObjectsListView extends tx_realty_pi1_AbstractListView {
	/**
	 * @var string the list view type to display
	 */
	protected $currentView = 'my_objects';

	/**
	 * @var string the locallang key for the label belonging to this view
	 */
	protected $listViewLabel = 'label_your_objects';

	/**
	 * @var boolean whether Google Maps should be shown in this view
	 */
	protected $isGoogleMapsAllowed = FALSE;

	/**
	 * Initializes some view-specific data.
	 */
	protected function initializeView() {
		$this->unhideSubparts(
			'wrapper_editor_specific_content,new_record_link'
		);

		$this->setLimitHeading();
		$this->setEditorLinkMarker();
		$this->setMarker(
			'empty_editor_link',
			$this->createLinkToFeEditorPage('editorPID', 0)
		);
		$this->processDeletion();
	}

	/**
	 * Sets the message how many objects the currently logged-in front-end user
	 * still can enter.
	 *
	 * This function should only be called when a user is logged-in at the front
	 * end.
	 */
	private function setLimitHeading() {
		$user = tx_oelib_FrontEndLoginManager::getInstance()
			->getLoggedInUser('tx_realty_Mapper_FrontEndUser');

		if ($user->getTotalNumberOfAllowedObjects() == 0) {
			$this->hideSubparts('limit_heading');
			return;
		}

		$objectsLeftToEnter = $user->getObjectsLeftToEnter();
		$this->unhideSubparts('limit_heading');
		$this->setMarker(
			'objects_limit_heading',
			sprintf(
				$this->translate('label_objects_already_entered'),
				$user->getNumberOfObjects(),
				$user->getTotalNumberOfAllowedObjects()
			)
		);
		switch ($objectsLeftToEnter) {
			case 0:
				$labelLeftToEnter = $this->translate('label_no_objects_left');
				break;
			case 1:
				$labelLeftToEnter = $this->translate('label_one_object_left');
				break;
			default:
				$labelLeftToEnter = sprintf(
					$this->translate('label_multiple_objects_left'),
					$objectsLeftToEnter
				);
				break;
		}

		$this->setMarker(
			'objects_left_to_enter',
			$labelLeftToEnter
		);
	}

	/**
	 * Sets the link to the new record button of the my objects view and hides
	 * it if the user cannot enter any more objects.
	 *
	 * This function should only be called when a user is logged in at the front
	 * end.
	 */
	private function setEditorLinkMarker() {
		if (tx_oelib_FrontEndLoginManager::getInstance()
			->getLoggedInUser('tx_realty_Mapper_FrontEndUser')
			->canAddNewObjects()
		) {
			$this->setMarker(
				'empty_editor_link',
				$this->createLinkToFeEditorPage('editorPID', 0)
			);
		} else {
			$this->hideSubparts('new_record_link');
		}
	}

	/**
	 * Processes the deletion of a realty record.
	 */
	private function processDeletion() {
		// no need for a front-end editor if there is nothing to delete
		if ($this->piVars['delete'] == 0) {
			return;
		}

		// For testing, the FE editor's FORMidable object must not be created.
		$frontEndEditor = tx_oelib_ObjectFactory::make(
			'tx_realty_frontEndEditor', $this->conf, $this->cObj,
			$this->piVars['delete'], 'pi1/tx_realty_frontEndEditor.xml',
			$this->isTestMode
		);
		$frontEndEditor->deleteRecord();
		$frontEndEditor->__destruct();
	}

	/**
	 * Gets the WHERE clause part specific to this view.
	 *
	 * @return string the WHERE clause parts to add, will be empty if no view
	 *                specific WHERE clause parts are needed
	 */
	protected function getViewSpecificWhereClauseParts() {
		return ' AND ' . REALTY_TABLE_OBJECTS . '.owner = ' .
			$this->getFeUserUid();
	}

	/**
	 * Sets the row contents specific to this view.
	 */
	protected function setViewSpecificListRowContents() {
		$this->setMarker(
			'editor_link',
			$this->createLinkToFeEditorPage(
				'editorPID', $this->internal['currentRow']['uid']
			)
		);
		$this->setMarker(
			'image_upload_link',
			$this->createLinkToFeEditorPage(
				'imageUploadPID', $this->internal['currentRow']['uid']
			)
		);
		$this->setMarker(
			'really_delete',
			$this->translate('label_really_delete') . '\n' .
				$this->translate('label_object_number') . ' ' .
				$this->internal['currentRow']['object_number'] . ': ' .
				$this->internal['currentRow']['title']
		);
		$this->setMarker(
			'delete_link',
			$this->cObj->typoLink_URL(
				array(
					'parameter' => $GLOBALS['TSFE']->id,
					'additionalParams' => t3lib_div::implodeArrayForUrl(
						$this->prefixId,
						array('delete' => $this->internal['currentRow']['uid'])
					),
				)
			)
		);
		$this->setMarker(
			'record_state',
			$this->translate($this->internal['currentRow']['hidden']
				? 'label_pending' : 'label_published'
			)
		);

		$this->setAdvertisementMarkers();
	}

	/**
	 * Determines whether hidden results should be shown.
	 *
	 * This will be used for tx_oelib_db::enableFields.
	 *
	 * @return integer 1 if hidden records should be shown, -1 otherwise
	 */
	protected function shouldShowHiddenObjects() {
		return 1;
	}

	/**
	 * Creates a link to the FE editor page.
	 *
	 * @param string $pidKey
	 *        key of the configuration value with the PID, must not be empty
	 * @param integer $uid
	 *        UID of the object to be loaded for editing, must be >= 0
	 *        (Zero will open the FE editor for a new record to insert.)
	 *
	 * @return string the link to the FE editor page, will not be empty
	 */
	private function createLinkToFeEditorPage($pidKey, $uid) {
		return t3lib_div::locationHeaderUrl(
			$this->cObj->typoLink_URL(
				array(
					'parameter' => $this->getConfValueInteger($pidKey),
					'additionalParams' => t3lib_div::implodeArrayForUrl(
						$this->prefixId, array('showUid' => $uid)
					),
				)
			)
		);
	}

	/**
	 * Sets the markers for the "advertise" link for one row.
	 */
	private function setAdvertisementMarkers() {
		if (!$this->hasConfValueInteger(
			'advertisementPID', 's_advertisements'
		)) {
			$this->hideSubparts('wrapper_advertising');
			return;
		}

		if ($this->isCurrentObjectAdvertised()) {
			$this->hideSubparts('wrapper_advertise_button');
			$this->unhideSubparts('wrapper_advertised_status');
			return;
		}

		$this->unhideSubparts('wrapper_advertise_button');
		$this->hideSubparts('wrapper_advertised_status');

		if ($this->hasConfValueString(
			'advertisementParameterForObjectUid', 's_advertisements'
		)) {
			$linkParameters = t3lib_div::implodeArrayForUrl(
				'',
				array(
					$this->getConfValueString(
						'advertisementParameterForObjectUid',
						's_advertisements'
					) => $this->internal['currentRow']['uid']
				)
			);
		} else {
			$linkParameters = '';
		}

		$this->setMarker(
			'advertise_link',
			$this->cObj->typoLink_URL(
				array(
					'parameter' => $this->getConfValueInteger(
						'advertisementPID', 's_advertisements'
					),
					'additionalParams' => $linkParameters,
				)
			)
		);
	}

	/**
	 * Checks whether the current object is advertised and the advertisement
	 * has not expired yet.
	 *
	 * @return boolean TRUE if the current object is advertised and the
	 *                 advertisement has not expired yet, FALSE otherwise
	 */
	private function isCurrentObjectAdvertised() {
		$advertisementDate = $this->internal['currentRow']['advertised_date'];
		if ($advertisementDate == 0) {
			return FALSE;
		}

		$expiryInDays = $this->getConfValueInteger(
			'advertisementExpirationInDays', 's_advertisements'
		);
		if ($expiryInDays == 0) {
			return TRUE;
		}

		return (
			($advertisementDate + $expiryInDays * tx_oelib_Time::SECONDS_PER_DAY)
				< $GLOBALS['SIM_ACCESS_TIME']
		);
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/pi1/class.tx_realty_pi1_MyObjectsListView.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/pi1/class.tx_realty_pi1_MyObjectsListView.php']);
}
?>