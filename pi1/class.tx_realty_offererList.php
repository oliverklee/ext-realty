<?php
/***************************************************************
* Copyright notice
*
* (c) 2008-2009 Saskia Metzler <saskia@merlin.owl.de>
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
 * Class 'tx_realty_offererList' for the 'realty' extension.
 * This class provides a list of offerers for the realty plugin.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 */
class tx_realty_offererList extends tx_realty_pi1_FrontEndView {
	/**
	 * @var boolean whether this class is instantiated for testing
	 */
	private $isTestMode = false;

	/**
	 * The constructor.
	 *
	 * @param array TypoScript configuration for the plugin
	 * @param tslib_cObj the parent cObj content, needed for the flexforms
	 * @param boolean true if this class is instantiated for testing, else false
	 */
	public function __construct(
		array $configuration, tslib_cObj $cObj, $isTestMode = false
	) {
		$this->isTestMode = $isTestMode;
		parent::__construct($configuration, $cObj);
	}

	/**
	 * Returns the offerer list in HTML.
	 *
	 * @param array unused
	 *
	 * @return string HTML of the offerer list, will not be empty
	 */
	public function render(array $unused = array()) {
		$listItems = $this->getListItems();

		if ($listItems != '') {
			$this->setSubpart('offerer_list_item', $listItems);
		} else {
			$this->setMarker(
				'message_noResultsFound',
				$this->translate('message_noResultsFound_offererList')
			);
			$this->setSubpart(
				'offerer_list_result', $this->getSubpart('EMPTY_RESULT_VIEW')
			);
		}

		return $this->getSubpart('OFFERER_LIST');
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
		return $this->listItemQuery('uid = ' . $offererUid);
	}

	/**
	 * Returns the HTML for one list item.
	 *
	 * @param array owner data array, the keys 'company', 'name', 'first_name',
	 *              'last_name', 'address', 'zip', 'city', 'email', 'www'
	 *              and 'telephone' will be used for the HTML
	 *
	 * @return string HTML for one contact data item, will be empty if
	 *                $ownerData did not contain data to use
	 */
	public function renderOneItemWithTheDataProvided(array $ownerData) {
		if (isset($ownerData['usergroup'])) {
			throw new Exception(
				'To process user group information you need to use render() or' .
					'renderOneItem().'
			);
		}

		$frontEndUser = t3lib_div::makeInstance('tx_realty_Model_FrontEndUser');

		// setData() will not create the relations, but "usergroup" is expected
		// to hold a list instance.
		$dataToSet = $ownerData;
		$dataToSet['usergroup'] = t3lib_div::makeInstance('tx_oelib_List');
		$frontEndUser->setData($dataToSet);

		return $this->createListRow($frontEndUser);
	}

	/**
	 * Returns the HTML for the list items.
	 *
	 * @return string HTML for the list items, will be empty if there are
	 *                no offerers
	 */
	private function getListItems() {
		if ($this->hasConfValueString(
			'userGroupsForOffererList', 's_offererInformation'
		)) {
			$userGroups = str_replace(
				',',
				'|',
				$this->getConfValueString(
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
		$listItems = '';

		$offererRecords = tx_oelib_db::selectMultiple(
			'*',
			'fe_users',
			$whereClause . tx_oelib_db::enableFields('fe_users') .
				$this->getWhereClauseForTesting(),
			'',
			'usergroup,city,company,last_name,name,username,image'
		);
		$offererList = tx_oelib_MapperRegistry
			::get('tx_realty_Mapper_FrontEndUser')
			->getListOfModels($offererRecords);

		foreach ($offererList as $offerer) {
			$listItems .= $this->createListRow($offerer);
		}

		return $listItems;
	}

	/**
	 * Returns a single table row for the offerer list.
	 *
	 * @param tx_realty_Model_FrontEndUser FE user for which to create the row
	 *
	 * @return string HTML for one list row, will be empty if there is no
	 *                no content (or only the user group) for the row
	 */
	private function createListRow(tx_realty_Model_FrontEndUser $offerer) {
		$rowHasContent = false;
		$this->resetSubpartsHiding();

		foreach ($this->getListRowContent($offerer) as $key => $value) {
			$this->setMarker(
				'emphasized_' . $key,
				(!$rowHasContent && ($value != '')) ? 'emphasized' : ''
			);

			if (!in_array(
				$key, array('www', 'objects_by_owner_link', 'image'))
			) {
				$value = htmlspecialchars($value);
			}

			if ($this->setOrDeleteMarkerIfNotEmpty($key, $value, '', 'wrapper')) {
				$rowHasContent = ($key != 'usergroup');
			} else {
				$this->hideSubparts($key, 'wrapper');
			}
		}

		// Apart from in the single view, the user group is appended to the
		// company (if displayed) or to else the offerer name.
		if ($this->getConfValueString('what_to_display') != 'single_view') {
			$this->hideSubparts('usergroup', 'wrapper');
		}

		return ($rowHasContent ? $this->getSubpart('OFFERER_LIST_ITEM') : '');
	}

	/**
	 * Returns an array of data for a list row.
	 *
	 * @param tx_realty_Model_FrontEndUser offerer for which to create the row
	 *
	 * @return array associative array with the marker names as keys and the
	 *               content to replace them with as values, will not be empty
	 */
	private function getListRowContent(tx_realty_Model_FrontEndUser $offerer) {
		$result = array();

		$maximumRowContent = array(
			'usergroup' => $this->getFirstUserGroup($offerer->getUserGroups()),
			'company' => $this->getCompany($offerer),
			'offerer_label' => $this->getOffererLabel($offerer),
			'street' => $offerer->getStreet(),
			'city' => $offerer->getZipAndCity(),
			'telephone' => $offerer->getPhoneNumber(),
			'email' => $offerer->getEMailAddress(),
			'objects_by_owner_link' => $this->getObjectsByOwnerUrl(
				$offerer->getUid()
			),
			'www' => $this->cObj->typoLink(
				htmlspecialchars($offerer->getHomepage()),
				array('parameter' => $offerer->getHomepage())
			),
			'image' => $this->getImageMarkerContent($offerer),
		);

		foreach ($maximumRowContent as $key => $value) {
			$result[$key] = $this->mayDisplayInformation($offerer, $key)
				? trim($value) : '';
		}

		return $result;
	}

	/**
	 * Checks wether an item of offerer information may be displayed.
	 *
	 * @param tx_realty_Model_FrontEndUser offerer
	 * @param string key of the information for which to check visibility, must
	 *               not be emtpy
	 *
	 * @return boolean true if it is configured to display the information of
	 *                 the provided offerer, false otherwise
	 */
	private function mayDisplayInformation(
		tx_realty_Model_FrontEndUser $offerer, $keyOfInformation
	) {
		$configurationKey = 'displayedContactInformation';

		$specialGroups = $this->getConfValueString(
			'groupsWithSpeciallyDisplayedContactInformation',
			's_offererInformation'
		);

		if (($specialGroups != '')
			&& $offerer->hasGroupMembership($specialGroups)
		) {
			$configurationKey .= 'Special';
		}

		return in_array($keyOfInformation, t3lib_div::trimExplode(
			',',
			$this->getConfValueString($configurationKey, 's_offererInformation'),
			true
		));
	}

	/**
	 * Returns a FE user's first name and last name if provided, else the name.
	 * If none of these is provided, the user name will be returned.
	 * FE user records are expected to have at least a user name.
	 *
	 * @param tx_realty_Model_FrontEndUser offerer of which to get the name
	 *
	 * @return string label for the owner with the first user group appended if
	 *                no company will be displayed (which usually has the user
	 *                group appended) and if the offerer list is not used in the
	 *                single view, will be empty if no owner record was cached
	 *                or if the cached record is an invalid FE user record
	 *                without a user name
	 */
	private function getOffererLabel(tx_realty_Model_FrontEndUser $offerer) {
		$result = $offerer->getName();

		if (!$offerer->hasCompany()
			|| !$this->mayDisplayInformation($offerer, 'company')
		) {
			$this->appendUserGroup($result, $offerer);
		}

		return trim($result);
	}

	/**
	 * Returns the company with the user group appended.
	 *
	 * @param tx_realty_Model_FrontEndUser the offerer of which to get the
	 *                                     company, must not be empty
	 *
	 * @return string the company with the user group appended if the offerer
	 *                list is not used in the single view, will be empty if
	 *                there is no company
	 */
	private function getCompany(tx_realty_Model_FrontEndUser $offerer) {
		$result = $offerer->getCompany();
		$this->appendUserGroup($result, $offerer);

		return trim($result);
	}

	/**
	 * Appends the user group if $information is non-empty and if the current
	 * view is not single view and if the user group may be displayed and is
	 * non-empty.
	 *
	 * @param string information to which the user group should be appended, may
	 *               be empty, will be modified
	 * @param tx_realty_Model_FrontEndUser the offerer of which to append the
	 *                                     user group
	 */
	private function appendUserGroup(
		&$information, tx_realty_Model_FrontEndUser $offerer
	) {
		if (($this->getConfValueString('what_to_display') != 'single_view')
			&& $this->mayDisplayInformation($offerer, 'usergroup')
			&& ($information != '')
		) {
			$information
				.= ' ' . $this->getFirstUserGroup($offerer->getUserGroups());
		}
	}

	/**
	 * Returns the title of the first user group a user belongs to and which is
	 * within the list of allowed user groups.
	 *
	 * @param tx_oelib_List the offerer's user groups of which to get the first
	 *                      which is within the list of allowed user groups
	 *
	 * @return string title of the first allowed user group of the given
	 *                FE user, will be empty if the user has no group
	 */
	private function getFirstUserGroup(tx_oelib_List $userGroups) {
		$result = '';

		$allowedGroups = t3lib_div::trimExplode(
			',',
			$this->getConfValueString(
				'userGroupsForOffererList', 's_offererInformation'
			),
			true
		);

		foreach ($userGroups as $group) {
			if (in_array($group->getUid(), $allowedGroups)) {
				$result = $group->getTitle();
				break;
			}
		}

		return ($result != '') ? ' (' . $result . ')' : '';
	}

	/**
	 * Returns the URL to the list of objects by the provided owner.
	 *
	 * @param integer UID of the owner for which to create the URL, must be >= 0
	 *
	 * @return string URL to the objects-by-owner list, will be empty if the
	 *                owner UID is zero
	 */
	private function getObjectsByOwnerUrl($ownerUid) {
		// There might be no UID if the data to render as offerer information
		// was initially provided in an array.
		if ($ownerUid == 0) {
			return '';
		}

		return t3lib_div::locationHeaderUrl($this->cObj->typoLink_URL(
			array(
				'parameter' => $this->getConfValueInteger(
					'objectsByOwnerPID', 's_offererInformation'
				),
				'additionalParams' => t3lib_div::implodeArrayForUrl(
					$this->prefixId, array('owner' => $ownerUid)
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

	/**
	 * Returns the image tag for the offerer image with the image resized to the
	 * maximum width and height as configured in TS Setup.
	 *
	 * @param tx_realty_Model_FrontEndUser the offerer to show the image for
	 *
	 * @return string the image tag with the image, will be empty if user has no
	 *                image
	 */
	private function getImageMarkerContent(tx_realty_Model_FrontEndUser $offerer) {
		if (!$offerer->hasImage()) {
			return '';
		}

		$configuredUploadFolder = tx_oelib_configurationProxy::getInstance(
				'sr_feuser_register'
			)->getConfigurationValueString('uploadFolder');

		$uploadFolder = ($configuredUploadFolder == '')
			? 'uploads/tx_srfeuserregister'
			: $configuredUploadFolder;

		if (substr($uploadFolder, -1) != '/') {
			$uploadFolder .= '/';
		}

		return $this->createRestrictedImage(
			$uploadFolder . $offerer->getImage(),
			'',
			$this->getConfValueInteger('offererImageMaxWidth'),
			$this->getConfValueInteger('offererImageMaxHeight'),
			0,
			$offerer->getName()
		);
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/pi1/class.tx_realty_offererList.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/pi1/class.tx_realty_offererList.php']);
}
?>