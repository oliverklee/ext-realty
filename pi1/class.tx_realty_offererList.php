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

	/**
	 * The constructor.
	 *
	 * @param	tx_realty_pi1		plugin that contains the offerer
	 * 								list
	 */
	public function __construct(tx_realty_pi1 $plugin) {
		$this->plugin = $plugin;
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
	 * Returns the HTML for the list items.
	 *
	 * @return	string		HTML for the list items, will be empty if there are
	 * 						no offerers
	 */
	private function getListItems() {
		if (!$this->plugin->hasConfValueString('userGroupsForOffererList')) {
			return '';
		}

		$userGroups = str_replace(
			',',
			'|',
			$this->plugin->getConfValueString('userGroupsForOffererList')
		);
		$dbResult = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'*',
			'fe_users',
			'usergroup REGEXP "(^|,)(' . $userGroups . ')(,|$)"' .
				tx_oelib_db::enableFields('fe_users'),
			'',
			'usergroup,company,last_name,name,username'
		);
		if (!$dbResult) {
			throw new Exception(DATABASE_QUERY_ERROR);
		}

		$listItems = '';
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbResult)) {
			$this->plugin->resetSubpartsHiding();
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
		foreach (array(
			'offerer_label' => htmlspecialchars($this->getOffererLabel($userRecord)),
			'usergroup' => htmlspecialchars($this->getFirstUserGroup($userRecord)),
			'telephone' => htmlspecialchars($userRecord['telephone']),
			'objects_by_owner_link' => $this->getObjectsByOwnerUrl($userRecord),
		) as $key => $value) {
			$this->plugin->setOrDeleteMarkerIfNotEmpty($key, $value, '', 'wrapper');
		}

		return $this->plugin->getSubpart('OFFERER_LIST_ITEM');
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
	 * 						FE user, will be non-empty
	 */
	private function getFirstUserGroup(array $userRecord) {
		$matchingGroups = array_values(array_intersect(
			explode(',', $userRecord['usergroup']),
			explode(
				',',
				$this->plugin->getConfValueString('userGroupsForOffererList')
			)
		));

		// No enableFields is used here, as the FE user records fetched in
		// getListItems are not checked to be in enabled groups, either.
		$dbResult = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'title', 'fe_groups', 'uid=' . $matchingGroups[0]
		);
		if (!$dbResult) {
			throw new Exception(DATABASE_QUERY_ERROR);
		}

		$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbResult);
		if (!$row) {
			throw new Exception(DATABASE_RESULT_ERROR);
		}

		return $row['title'];
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
		if (!$this->plugin->hasConfValueInteger('objectsByOwnerPID')) {
			return '';
		}

		return t3lib_div::locationHeaderUrl($this->plugin->cObj->typoLink_URL(
			array(
				'parameter' => $this->plugin->getConfValueInteger('objectsByOwnerPID'),
				'additionalParams' => t3lib_div::implodeArrayForUrl(
					$this->plugin->prefixId, array('owner' => $userRecord['uid'])
				),
				'useCacheHash' => true,
			)
		));
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/pi1/class.tx_realty_offererList.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/pi1/class.tx_realty_offererList.php']);
}
?>