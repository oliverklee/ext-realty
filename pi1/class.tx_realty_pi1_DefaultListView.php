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
	 * @var bool whether Google Maps should be shown in this view
	 */
	protected $isGoogleMapsAllowed = TRUE;

	/**
	 * @var string[] the names of the database tables for foreign keys
	 */
	private $tableNames = array(
		'objects' => 'tx_realty_objects',
		'city' => 'tx_realty_cities',
		'district' => 'tx_realty_districts',
		'country' => 'static_countries',
		'apartment_type' => 'tx_realty_apartment_types',
		'house_type' => 'tx_realty_house_types',
		'garage_type' => 'tx_realty_car_places',
		'pets' => 'tx_realty_pets',
		'images' => 'tx_realty_images',
	);

	/**
	 * Initializes some view-specific data.
	 *
	 * @return void
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
			$this->setMarker('self_url_without_pivars', $this->getSelfUrl(TRUE, array('search', 'pointer')));

			$result = $this->getSubpart('LIST_FILTER');
		} else {
			$result = '';
		}

		return $result;
	}

	/**
	 * Checks whether the checkboxes filter may be created.
	 *
	 * @return bool TRUE if there is a sort criterion configured and if the
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
	 * @return string[] HTML for each checkbox item in an array, will be
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
			'FROM ' . 'tx_realty_objects' . ' ' .
			'WHERE ' . 'tx_realty_objects' . '.' . $filterCriterion .
				' = ' . $currentTable . '.uid ' .
				parent::getWhereClausePartForPidList() .
				tx_oelib_db::enableFields('tx_realty_objects') .
			')' . tx_oelib_db::enableFields($currentTable);

		$checkboxItems = tx_oelib_db::selectMultiple('uid, title', $currentTable, $whereClause, '', 'title ASC');

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
	 * @return bool whether the city selector is currently used
	 */
	private function isCitySelectorInUse() {
		return $this->piVars['city'] > 0;
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/realty/pi1/class.tx_realty_pi1_DefaultListView.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/realty/pi1/class.tx_realty_pi1_DefaultListView.php']);
}