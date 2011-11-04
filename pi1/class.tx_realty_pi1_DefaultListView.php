<?php
/***************************************************************
* Copyright notice
*
* (c) 2009-2011 Bernd Schönbach <bernd@oliverklee.de>
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
 * Class tx_realty_RealtyListView for the "realty" extension.
 *
 * This class represents the "realty list" view.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 * @author Saskia Metzler <saskia@merlin.owl.de>
 * @author Bernd Schönbach <bernd@oliverklee.de>
 */
class tx_realty_pi1_DefaultListView extends tx_realty_pi1_AbstractListView {
	/**
	 * @var string the list view type to display
	 */
	protected $currentView = 'realty_list';

	/**
	 * @var string the locallang key to the label of a list view
	 */
	protected $listViewLabel = 'label_weofferyou';

	/**
	 * @var boolean whether Google Maps should be shown in this view
	 */
	protected $isGoogleMapsAllowed = TRUE;

	/**
	 * @var array the names of the database tables for foreign keys
	 */
	private $tableNames = array(
		'objects' => REALTY_TABLE_OBJECTS,
		'city' => REALTY_TABLE_CITIES,
		'district' => REALTY_TABLE_DISTRICTS,
		'country' => STATIC_COUNTRIES,
		'apartment_type' => REALTY_TABLE_APARTMENT_TYPES,
		'house_type' => REALTY_TABLE_HOUSE_TYPES,
		'garage_type' => REALTY_TABLE_CAR_PLACES,
		'pets' => REALTY_TABLE_PETS,
		'images' => REALTY_TABLE_IMAGES,
	);

	/**
	 * Initializes some view-specific data.
	 */
	protected function initializeView() {
		$this->unhideSubparts(
			'favorites_url,list_filter,add_to_favorites_button,' .
			'wrapper_checkbox'
		);
		$this->setSubpart('list_filter', $this->createCheckboxesFilter());
	}

	/**
	 * Creates the search checkboxes for the DB field selected in the BE.
	 * If no field is selected in the BE or there are not DB records with
	 * non-empty data for that field, this function returns an empty string.
	 *
	 * This function will also return an empty string if "city" is selected in
	 * the BE and $this->piVars['city'] is set (by the city selector).
	 *
	 * @return string HTML for the search bar, may be empty
	 */
	private function createCheckboxesFilter() {
		if (!$this->mayCheckboxesFilterBeCreated()) {
			return '';
		}

		$items = $this->getCheckboxItems();
		if (!empty($items)) {
			$this->setSubpart('search_item', implode(LF, $items));
			$this->setMarker(
				'self_url_without_pivars',
				$this->getSelfUrl(TRUE, array('search'))
			);

			$result = $this->getSubpart('LIST_FILTER');
		} else {
			$result = '';
		}

		return $result;
	}

	/**
	 * Checks whether the checkboxes filter may be created.
	 *
	 * @return boolean TRUE if there is a sort criterion configured and if the
	 *                 criterion is not "city" while the city selector is
	 *                 active, FALSE otherwise
	 */
	private function mayCheckboxesFilterBeCreated() {
		if (!$this->hasConfValueString('checkboxesFilter')) {
			return FALSE;
		}

		return (($this->getConfValueString('checkboxesFilter') != 'city')
			|| !$this->isCitySelectorInUse()
		);
	}

	/**
	 * Returns an array of checkbox items for the list filter.
	 *
	 * @return array HTML for each checkbox item in an array, will be
	 *               empty if there are no entries found for the
	 *               configured filter
	 */
	private function getCheckboxItems() {
		$result = array();

		$filterCriterion = $this->getConfValueString('checkboxesFilter');
		$currentTable = $this->tableNames[$filterCriterion];
		$currentSearch = parent::searchSelectionExists()
			? $this->piVars['search']
			: array();

		$whereClause = 'EXISTS ' . '(' .
			'SELECT * ' .
			'FROM ' . REALTY_TABLE_OBJECTS . ' ' .
			'WHERE ' . REALTY_TABLE_OBJECTS . '.' . $filterCriterion .
				' = ' . $currentTable . '.uid ' .
				parent::getWhereClausePartForPidList() .
				tx_oelib_db::enableFields(REALTY_TABLE_OBJECTS) .
			')' . tx_oelib_db::enableFields($currentTable);

		$checkboxItems = tx_oelib_db::selectMultiple(
			'uid, title', $currentTable, $whereClause
		);

		foreach ($checkboxItems as $checkboxItem) {
			if (in_array($checkboxItem['uid'], $currentSearch)) {
				$checked = ' checked="checked"';
			} else {
				$checked = '';
			}
			$this->setMarker('search_checked', $checked);
			$this->setMarker('search_value', $checkboxItem['uid']);
			$this->setMarker(
				'search_label', htmlspecialchars($checkboxItem['title'])
			);
			$result[] = $this->getSubpart('SEARCH_ITEM');
		}

		return $result;
	}

	/**
	 * Checks whether the current piVars contain a value for the city selector.
	 *
	 * @return boolean whether the city selector is currently used
	 */
	private function isCitySelectorInUse() {
		return $this->piVars['city'] > 0;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/pi1/class.tx_realty_pi1_DefaultListView.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/pi1/class.tx_realty_pi1_DefaultListView.php']);
}
?>