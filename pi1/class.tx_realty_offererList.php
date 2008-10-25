<?php
/***************************************************************
* Copyright notice
*
* (c) 2008 Saskia Metzler <saskia@merlin.owl.de>
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

require_once(t3lib_extMgm::extPath('oelib') . 'tx_oelib_commonConstants.php');
require_once(t3lib_extMgm::extPath('oelib') . 'class.tx_oelib_db.php');

/**
 * Class 'tx_realty_offererList' for the 'realty' extension.
 * This class provides a list of offerers for the realty plugin.
 *
 * @package		TYPO3
 * @subpackage	tx_realty
 *
 * @author		Saskia Metzler <saskia@merlin.owl.de>
 */
class tx_realty_offererList {
	/**
	 * @var	tx_realty_pi1		plugin that contains the offerer list
	 */
	private $plugin = null;

	/** @var boolean whether this class is instantiated for testing */
	private $isTestMode = false;

	/**
	 * The constructor.
	 *
	 * @param tx_realty_pi1 plugin that contains the offerer list
	 * @param boolean true if this class is instantiated for testing, else false
	 */
	public function __construct(tx_realty_pi1 $plugin, $isTestMode = false) {
		$this->plugin = $plugin;
		$this->isTestMode = $isTestMode;
	}

	/**
	 * Frees as much memory that has been used by this object as possible.
	 */
	public function __destruct() {
		unset($this->plugin);
	}

	/**
	 * Returns the offerer list in HTML.
	 *
	 * @return	string		HTML of the offerer list, will not be empty
	 */
	public function render() {
		$listItems = $this->getListItems();

		if ($listItems != '') {
			$this->plugin->setSubpart('offerer_list_item', $listItems);
		} else {
			$this->plugin->setMarker(
				'message_noResultsFound',
				$this->plugin->translate('message_noResultsFound_offererList')
			);
			$this->plugin->setSubpart(
				'offerer_list_result',
				$this->plugin->getSubpart('EMPTY_RESULT_VIEW')
			);
		}

		return $this->plugin->getSubpart('OFFERER_LIST');
	}

	/**
	 * Returns the HTML for one list item.
	 *
	 * @param integer UID of the FE user record for which to get the contact
	 *                information, must be > 0
	 *
	 * @return string HTML for one contact data item, will be empty if
	 *                $offererUid is not a UID of an enabled user
	 */
	public function renderOneItem($offererUid) {
		return $this->listItemQuery('uid=' . $offererUid);
	}

	/**
	 * Returns the HTML for one list item.
	 *
	 * @param array owner data array, the keys 'company', 'usergroup', 'name',
	 *              'first_name', 'last_name', 'address', 'zip', 'city', 'email',
	 *              'www' and 'telephone' will be used for the HTML
	 *
	 * @return string HTML for one contact data item, will be empty if
	 *                $ownerData did not contain data to use
	 */
	public function renderOneItemWithTheDataProvided(array $ownerData) {
		return $this->createListRow($ownerData);
	}

	/**
	 * Returns the HTML for the list items.
	 *
	 * @return	string		HTML for the list items, will be empty if there are
	 * 						no offerers
	 */
	private function getListItems() {
		if ($this->plugin->hasConfValueString(
			'userGroupsForOffererList', 's_offererInformation'
		)) {
			$userGroups = str_replace(
				',',
				'|',
				$this->plugin->getConfValueString(
					'userGroupsForOffererList', 's_offererInformation'
				)
			);
			$userGroupRestriction = 'usergroup ' .
				'REGEXP "(^|,)(' . $userGroups . ')(,|$)"';
		} else {
			$userGroupRestriction = '1=1';
		}

		return $this->listItemQuery($userGroupRestriction);
	}

	/**
	 * Gets the offerer records in an array.
	 *
	 * @param string WHERE clause for the query, must not be empty
	 *
	 * @return string HTML for each fetched offerer record, will be empty if
	 *                none were found
	 */
	private function listItemQuery($whereClause) {
		$dbResult = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'*',
			'fe_users',
			$whereClause .
				tx_oelib_db::enableFields('fe_users') .
				$this->getWhereClauseForTesting(),
			'',
			'usergroup,company,last_name,name,username'
		);
		if (!$dbResult) {
			throw new Exception(DATABASE_QUERY_ERROR);
		}

		$listItems = '';
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbResult)) {
			$listItems .= $this->createListRow($row);
		}

		return $listItems;
	}

	/**
	 * Returns a single table row for the offerer list.
	 *
	 * @param	array		the FE user record for which to create the row, must
	 * 						not be empty
	 *
	 * @return	string		HTML for one list row, will not be empty
	 */
	private function createListRow(array $userRecord) {
		$subpartHasContent = false;
		// resetSubpartsHiding cannot be used as this also affects the subparts
		// hidden in the pi1 class.
		$this->plugin->unhideSubparts(
			'offerer_label,usergroup,street,zip,city,telephone,email,www,' .
				'objects_by_owner_link,address',
			'',
			'wrapper'
		);

		foreach (array(
			'offerer_label' => $this->getOffererLabel($userRecord),
			'usergroup' => $this->getFirstUserGroup($userRecord),
			'street' => $userRecord['address'],
			'zip' => $userRecord['zip'],
			'city' => $userRecord['city'],
			'telephone' => $userRecord['telephone'],
			'email' => $userRecord['email'],
			'www' => $userRecord['www'],
		) as $key => $value) {
			if ($this->mayDisplayInformation($userRecord, $key)
				&& $this->plugin->setOrDeleteMarkerIfNotEmpty(
					$key, htmlspecialchars($value), '', 'wrapper'
				)
			) {
				$subpartHasContent = true;
			} else {
				$this->plugin->hideSubparts($key, 'wrapper');
			}
		}

		if (empty($userRecord['zip']) && empty($userRecord['city'])) {
			$this->plugin->hideSubparts('address', 'wrappper');
		}
		$this->plugin->setOrDeleteMarkerIfNotEmpty(
			'objects_by_owner_link',
			$this->getObjectsByOwnerUrl($userRecord),
			'',
			'wrapper'
		);

		return ($subpartHasContent
			? $this->plugin->getSubpart('OFFERER_LIST_ITEM')
			: ''
		);
	}

	/**
	 * Checks wether an item of offerer information may be displayed.
	 *
	 * @param array offerer record, must not be empty
	 *
	 * @return boolean true if it is configured to display the information for
	 *                 the provided user, false otherwise
	 */
	private function mayDisplayInformation($userRecord, $keyOfInformation) {
		$configurationKey = 'displayedContactInformation' . (
			$this->containsSpecialGroup($userRecord['usergroup']) ? 'Special' : ''
		);

		return in_array(
			$keyOfInformation,
			explode(',', $this->plugin->getConfValueString(
				$configurationKey, 's_offererInformation'
			))
		);
	}

	/**
	 * Checks whether a list of user groups contains at least one of the
	 * configured special groups.
	 *
	 * @param string comma-separated list of FE user group UIDs to check, must
	 *               not be empty
	 *
	 * @return boolean true if the provided string contains at least one of the
	 *                 configured special user groups
	 */
	private function containsSpecialGroup($groupList) {
		if (!$this->plugin->hasConfValueString(
				'groupsWithSpeciallyDisplayedContactInformation',
				's_offererInformation'
			)
		) {
			return false;
		}

		$specialGroups = array_values(array_intersect(
			explode(',', $groupList),
			explode(',', $this->plugin->getConfValueString(
				'groupsWithSpeciallyDisplayedContactInformation',
				's_offererInformation'
			))
		));

		return !empty($specialGroups);
	}

	/**
	 * Returns a FE user's company if set in $userRecord and the first
	 * name and last name if provided, else the name. If none of these is
	 * provided, the user name will be returned.
	 * FE user records are expected to have at least a user name.
	 *
	 * @param	array		the user record of which to get the label, must not
	 * 						be empty
	 *
	 * @return	string		label for the owner, will be empty if no owner
	 * 						record was cached or if the cached record is an
	 * 						invalid FE user record without a user name
	 */
	private function getOffererLabel(array $userRecord) {
		$company = $userRecord['company'];
		$name = ($userRecord['last_name'] != '')
			? trim($userRecord['first_name'] . ' ' . $userRecord['last_name'])
			: $userRecord['name'];

		$result = trim($company . ', ' . $name, ', ');

		return ($result != '') ? $result : $userRecord['username'];
	}

	/**
	 * Returns the first user group a user belongs to which is within the list
	 * of allowed user groups.
	 *
	 * @param	array		the user record of which to get the first user group
	 * 						which is within the list of allowed user groups,
	 * 						must not be empty
	 *
	 * @return	string		title of the first allowed user group of the given
	 * 						FE user, will be empty if the user has no group
	 */
	private function getFirstUserGroup(array $userRecord) {
		$result = '';
		$matchingGroups = explode(',', $userRecord['usergroup']);

		if ($this->plugin->hasConfValueString(
			'userGroupsForOffererList', 's_offererInformation'
		)) {
			$matchingGroups = array_values(array_intersect(
				$matchingGroups,
				explode(
					',',
					$this->plugin->getConfValueString(
						'userGroupsForOffererList', 's_offererInformation'
					)
				)
			));
		}

		if (intval($matchingGroups[0]) != 0) {
			// No enableFields is used here as the FE user records fetched in
			// getListItems are not checked to be in enabled groups, either.
			$dbResult = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'title',
				'fe_groups',
				'uid=' . $matchingGroups[0] . $this->getWhereClauseForTesting()
			);
			if (!$dbResult) {
				throw new Exception(DATABASE_QUERY_ERROR);
			}

			$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbResult);
			if (!$row) {
				throw new Exception(DATABASE_RESULT_ERROR);
			}

			$result = $row['title'];
		}

		return $result;
	}

	/**
	 * Returns the URL to the list of objects by the owner provided in
	 * $userRecord.
	 *
	 * @param	array		user record for which to create the URL, must not be
	 * 						empty
	 *
	 * @return	string		URL to the objects-by-owner list, will be empty if
	 * 						the configuration for 'objectsByOwnerPID' is zero
	 */
	private function getObjectsByOwnerUrl(array $userRecord) {
		// There might be no UID if the data to render as offerer information
		// was initially provided in an array.
		if (!$this->plugin->hasConfValueInteger(
			'objectsByOwnerPID', 's_offererInformation'
		) || !isset($userRecord['uid'])) {
			return '';
		}

		return t3lib_div::locationHeaderUrl($this->plugin->cObj->typoLink_URL(
			array(
				'parameter' => $this->plugin->getConfValueInteger(
					'objectsByOwnerPID', 's_offererInformation'
				),
				'additionalParams' => t3lib_div::implodeArrayForUrl(
					$this->plugin->prefixId, array('owner' => $userRecord['uid'])
				),
				'useCacheHash' => true,
			)
		));
	}

	/**
	 * Returns a WHERE clause part for the test mode. So only dummy records will
	 * be retrieved for testing.
	 *
	 * @return string WHERE clause part for testing starting with ' AND'
	 *                if the test mode is enabled, an empty string otherwise
	 */
	private function getWhereClauseForTesting() {
		return $this->isTestMode ? ' AND tx_oelib_is_dummy_record=1' : '';
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/pi1/class.tx_realty_offererList.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/pi1/class.tx_realty_offererList.php']);
}
?>