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

require_once(t3lib_extMgm::extPath('realty') . 'lib/tx_realty_constants.php');

/**
 * Class 'tx_realty_filterForm' for the 'realty' extension. This class
 * provides a form to enter filter criteria for the realty list in the realty
 * plugin.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 */
class tx_realty_filterForm extends tx_realty_pi1_FrontEndView {
	/**
	 * @var array Filter form data array with the elements "priceRange",
	 *            "site", "objectNumber" and "uid".
	 *            "priceRange" keeps a string of the format
	 *            "number-number" and "site" has any string, directly
	 *            derived from the form data.
	 */
	private $filterFormData = array(
		'priceRange' => '', 'site' => '', 'objectNumber' => '', 'uid' => 0,
		'objectType' => '',
	);

	/**
	 * @var array the search fields which should be displayed in the search form
	 */
	private $displayedSearchFields = array();

	/**
	 * Returns the filter form in HTML.
	 *
	 * @param array current piVars, the elements "priceRange" and "site"
	 *              will be used if they are available, may be empty
	 *
	 * @return string HTML of the filter form, will not be empty
	 */
	public function render(array $filterFormData = array()) {
		$this->extractValidFilterFormData($filterFormData);
		$this->displayedSearchFields = t3lib_div::trimExplode(
			',',
			$this->getConfValueString(
				'displayedSearchWidgetFields', 's_searchForm'),
			true
		);

		$this->setTargetUrlMarker();
		$this->fillOrHideSiteSearch();
		$this->fillOrHidePriceRangeDropDown();
		$this->fillOrHideUidSearch();
		$this->fillOrHideObjectNumberSearch();
		$this->fillOrHideCitySearch();
		$this->fillOrHideObjectTypeSelect();

		return $this->getSubpart('FILTER_FORM');
	}

	/**
	 * Returns a WHERE clause part derived from the provided form data.
	 *
	 * The table on which this WHERE clause part can be applied must be
	 * "tx_realty_objects INNER JOIN tx_realty_cities
	 * ON tx_realty_objects.city = tx_realty_cities.uid";
	 *
	 * @param array filter form data, may be empty
	 *
	 * @return string WHERE clause part for the current filters beginning
	 *                with " AND", will be empty if none were provided
	 */
	public function getWhereClausePart(array $filterFormData) {
		$this->extractValidFilterFormData($filterFormData);

		return $this->getPriceRangeWhereClausePart() .
			$this->getSiteWhereClausePart() .
			$this->getObjectNumberWhereClausePart() .
			$this->getUidWhereClausePart() .
			$this->getObjectTypeWhereClausePart();
	}

	/**
	 * Stores the provided data derived from the form. In case invalid data was
	 * provided, an empty string will be stored.
	 *
	 * @param array filter form data, may be empty
	 */
	private function extractValidFilterFormData(array $formData) {
		foreach (array('site', 'objectNumber', 'uid', 'objectType') as $key) {
			if (isset($formData[$key])) {
				$this->filterFormData[$key] = ($key == 'uid')
					? intval($formData[$key])
					: $formData[$key];
			} else {
				$this->filterFormData[$key] = ($key == 'uid') ? 0 : '';
			}
		}

		if (isset($formData['priceRange'])
			&& preg_match('/^(\d+-\d+|-\d+|\d+-)$/', $formData['priceRange'])
		) {
			$this->filterFormData['priceRange'] = $formData['priceRange'];
		} else {
			$this->filterFormData['priceRange'] = '';
		}

		if (isset($formData['objectType'])
			&& in_array($formData['objectType'], array('forSale', 'forRent'))
		) {
			$this->filterFormData['objectType'] = $formData['objectType'];
		} else {
			$this->filterFormData['objectType'] = '';
		}
	}

	/**
	 * Formats one price range.
	 *
	 * @param string price range of the format "number-number", may be empty
	 *
	 * @return array array with one price range, consists of the two elements
	 *               "upperLimit" and "lowerLimit", will be empty if no price
	 *               range was provided in the form data
	 */
	private function getFormattedPriceRange($priceRange) {
		if ($priceRange == '') {
			return array();
		}

		$rangeLimits = t3lib_div::intExplode('-', $priceRange);

		// intval() converts an empty string to 0. So for "-100" zero and 100
		// will be stored as limits.
		return array(
			'lowerLimit' => $rangeLimits[0],
			'upperLimit' => $rangeLimits[1],
		);
	}

	/**
	 * Sets the target URL marker.
	 */
	private function setTargetUrlMarker() {
		$this->setMarker(
			'target_url',
			t3lib_div::locationHeaderUrl($this->cObj->typoLink_URL(array(
				'parameter' => $this->getConfValueInteger(
					'filterTargetPID', 's_searchForm'
				),
			)))
		);
	}


	////////////////////////////////////////////////////////////////////
	// Functions concerning the hiding or filling of the search fields
	////////////////////////////////////////////////////////////////////

	/**
	 * Fills the input box for zip code or city if there is data for it. Hides
	 * the input if it is disabled by configuration.
	 */
	private function fillOrHideSiteSearch() {
		if ($this->hasSearchField('site')) {
			$this->setMarker(
				'site', htmlspecialchars($this->filterFormData['site'])
			);
		} else {
			$this->hideSubparts('wrapper_site_search');
		}
	}

	/**
	 * Fills the price range drop-down with the configured ranges if it is
	 * enabled in the configuration, hides it otherwise.
	 */
	private function fillOrHidePriceRangeDropDown() {
		if (!$this->hasSearchField('priceRanges')) {
			$this->hideSubparts('wrapper_price_range_options');
			return;
		}

		$priceRanges = $this->getPriceRangesFromConfiguration();
		$optionTags = '';

		foreach ($priceRanges as $range) {
			$priceRangeString = implode('-', $range);
			$label = $this->getPriceRangeLabel($range);
			$selectedAttribute
				= ($this->filterFormData['priceRange'] == $priceRangeString)
					? ' selected="selected"'
					: '';

			$optionTags .= '<option value="' . $priceRangeString .
				'" label="' . $label . '" ' . $selectedAttribute . '>' .
				$label . '</option>';
		}
		$this->setMarker('price_range_options', $optionTags);

		$this->setMarker(
			'price_range_on_change', $this->getOnChangeForSingleField()
		);
	}

	/**
	 * Fills the input box for the UID search if it is configured to be
	 * displayed. Hides the form element if it is disabled by
	 * configuration.
	 */
	private function fillOrHideUidSearch() {
		if (!$this->hasSearchField('uid')) {
			$this->hideSubparts('wrapper_uid_search');
			return;
		}

		$this->setMarker(
			'searched_uid',
			((intval($this->filterFormData['uid']) == 0)
				? ''
				: intval($this->filterFormData['uid'])
			)
		);
	}

	/**
	 * Fills the input box for the object number search if it is configured to
	 * be displayed. Hides the form element if it is disabled by configuration.
	 */
	private function fillOrHideObjectNumberSearch() {
		if (!$this->hasSearchField('objectNumber')) {
			$this->hideSubparts('wrapper_object_number_search');
			return;
		}

		$this->setMarker(
			'searched_object_number',
			htmlspecialchars($this->filterFormData['objectNumber'])
		);
	}

 	/**
	 * Shows the city selector if enabled via configuration, otherwise hides it.
	 */
	private function fillOrHideCitySearch() {
		if (!$this->hasSearchField('city')) {
			$this->hideSubparts('wrapper_city_search');
			return;
		}

		$cities = tx_oelib_db::selectMultiple(
			REALTY_TABLE_CITIES . '.uid, ' . REALTY_TABLE_CITIES . '.title',
			REALTY_TABLE_OBJECTS . ',' . REALTY_TABLE_CITIES,
			REALTY_TABLE_OBJECTS . '.city = ' . REALTY_TABLE_CITIES . '.uid' .
				tx_oelib_db::enableFields(REALTY_TABLE_OBJECTS) .
				tx_oelib_db::enableFields(REALTY_TABLE_CITIES),
			'uid',
			REALTY_TABLE_CITIES . '.title'
		);

		$options = '';
		foreach ($cities as $city) {
			$options .= '<option value="' . $city['uid'] . '">' .
				htmlspecialchars($city['title']) . '</option>' . LF;
		}
		$this->setOrDeleteMarkerIfNotEmpty('options_city_search', $options);

		$this->setMarker(
			'city_select_on_change', $this->getOnChangeForSingleField()
		);
	}

 	/**
	 * Shows the rent/sale radiobuttons if enabled via configuration, otherwise
	 * hides them.
	 */
	private function fillOrHideObjectTypeSelect() {
		if (!$this->hasSearchField('objectType')) {
			$this->hideSubparts('wrapper_object_type_selector');
			return;
		}

		foreach(array('forRent' => 'rent', 'forSale' => 'sale')
			as $key => $markerPrefix
		) {
			$this->setMarker($markerPrefix . '_attributes',
				(($this->filterFormData['objectType'] == $key)
					? ' checked="checked"'
					: ''
				) . $this->getOnChangeForSingleField()
			);
		}
	}

	/**
	 * Returns an array of configured price ranges.
	 *
	 * @return array Two-dimensional array of the possible price ranges. Each
	 *               inner array consists of two elements with the keys
	 *               "lowerLimit" and "upperLimit". Note that the zero element
	 *               will always be empty because the first option in the
	 *               selectbox remains empty. If no price ranges are configured,
	 *               this array will be empty.
	 */
	private function getPriceRangesFromConfiguration() {
		if (!$this->hasConfValueString(
			'priceRangesForFilterForm', 's_searchForm')
		) {
			return array();
		}

		// The first element is empty because the first selectbox element should
		// remain empty.
		$priceRanges = array(array());

		$priceRangeConfiguration = t3lib_div::trimExplode(
			',',
			$this->getConfValueString('priceRangesForFilterForm','s_searchForm')
		);

		foreach ($priceRangeConfiguration as $range) {
			$priceRanges[] = $this->getFormattedPriceRange($range);
		}

		return $priceRanges;
	}

	/**
	 * Returns a formatted label for one price range according to the configured
	 * currency unit.
	 *
	 * @param array range for which to receive the label, must have the elements
	 *              "upperLimit" and "lowerLimit", both must have integers as
	 *               values, only one of the elements' values may be 0, for an
	 *               empty array the result will always be "&nbsp;"
	 *
	 * @return string formatted label for the price range, will be "&nbsp;"
	 *                if an empty array was provided (an empty string
	 *                would break the XHTML output's validity)
	 */
	private function getPriceRangeLabel(array $range) {
		if (empty($range)) {
			return '&nbsp;';
		}

		$currencySymbol = $this->getConfValueString('currencyUnit');

		if ($range['lowerLimit'] == 0) {
			$result = $this->translate('label_less_than') . ' ' .
				$range['upperLimit'] . $currencySymbol;
		} elseif ($range['upperLimit'] == 0) {
			$result = $this->translate('label_greater_than') . ' ' .
				$range['lowerLimit'] . $currencySymbol;
		} else {
			$result = $range['lowerLimit'] . $currencySymbol . ' ' .
				$this->translate('label_to') . ' ' .
				$range['upperLimit'] . $currencySymbol;
		}

		return $result;
	}

	/**
	 * Returns a WHERE clause part for one price range.
	 *
	 * @return string WHERE clause part for the provided price range
	 *                starting with " AND", will be empty if the filter
	 *                form data was zero
	 */
	private function getPriceRangeWhereClausePart() {
		if ($this->filterFormData['priceRange'] == '') {
			return '';
		}

		$range = $this->getFormattedPriceRange(
			$this->filterFormData['priceRange']
		);

		if ($range['lowerLimit'] == 0) {
			// Zero as lower limit must be excluded of the range because each
			// non-set price will be identified as zero. Many objects either
			// have a buying price or a rent which would make searching for
			// zero-prices futile.
			$equalSign = '';
			// Additionally to the objects that have at least one non-zero price
			// inferior to the lower lower limit, objects which have no price at
			// all need to be found.
			$whereClauseForObjectsForFree = ' OR (' . REALTY_TABLE_OBJECTS .
				'.rent_excluding_bills = 0 AND ' . REALTY_TABLE_OBJECTS .
				'.buying_price = 0)';
		} else {
			$equalSign = '=';
			$whereClauseForObjectsForFree = '';
		}
		// The WHERE clause part for the lower limit is always set, even if no
		// lower limit was provided. The lower limit will just be zero then.
		$lowerLimitRent = REALTY_TABLE_OBJECTS . '.rent_excluding_bills ' .
			'>' . $equalSign . ' ' . $range['lowerLimit'];
		$lowerLimitBuy = REALTY_TABLE_OBJECTS . '.buying_price ' .
			'>' . $equalSign . ' ' . $range['lowerLimit'];

		// The upper limit will be zero if no upper limit was provided. So zero
		// means infinite here.
		if ($range['upperLimit'] != 0) {
			$upperLimitRent = ' AND ' . REALTY_TABLE_OBJECTS .
				'.rent_excluding_bills <= ' . $range['upperLimit'];
			$upperLimitBuy = ' AND ' . REALTY_TABLE_OBJECTS .
				'.buying_price <= ' . $range['upperLimit'];
		} else {
			$upperLimitRent = '';
			$upperLimitBuy = '';
		}

		return ' AND ((' . $lowerLimitRent . $upperLimitRent . ') OR (' .
			$lowerLimitBuy . $upperLimitBuy . ')' .
			$whereClauseForObjectsForFree . ')';
	}

	/**
	 * Returns the WHERE clause part for one site.
	 *
	 * @return string WHERE clause part beginning with " AND", will be empty if
	 *                no filter form data was provided for the site
	 */
	private function getSiteWhereClausePart() {
		if ($this->filterFormData['site'] == '') {
			return '';
		}

		// only the first two characters are used for a zip code search
		$zipSearchString = $GLOBALS['TYPO3_DB']->quoteStr(
			$GLOBALS['TYPO3_DB']->escapeStrForLike(
				substr($this->filterFormData['site'], 0, 2),
				REALTY_TABLE_OBJECTS
			),
			REALTY_TABLE_OBJECTS
		);
		$citySearchString = $GLOBALS['TYPO3_DB']->quoteStr(
			$GLOBALS['TYPO3_DB']->escapeStrForLike(
				$this->filterFormData['site'],
				REALTY_TABLE_CITIES
			),
			REALTY_TABLE_CITIES
		);

		return ' AND (' . REALTY_TABLE_OBJECTS . '.zip LIKE "' .
			$zipSearchString . '%" OR ' . REALTY_TABLE_CITIES .
			'.title LIKE "%' . $citySearchString . '%")';
	}

	/**
	 * Returns the WHERE clause part for the object number.
	 *
	 * @return string WHERE clause part beginning with " AND", will be empty if
	 *                no filter form data was provided for the object number
	 */
	private function getObjectNumberWhereClausePart() {
		if ($this->filterFormData['objectNumber'] == '') {
			return '';
		}

		return ' AND ' . REALTY_TABLE_OBJECTS . '.object_number="' .
			$GLOBALS['TYPO3_DB']->quoteStr(
				$this->filterFormData['objectNumber'], REALTY_TABLE_OBJECTS
			) . '"';
	}

	/**
	 * Returns the WHERE clause part for the UID.
	 *
	 * @return string WHERE clause part beginning with " AND", will be empty if
	 *                no filter form data was provided for the UID
	 */
	private function getUidWhereClausePart() {
		if ($this->filterFormData['uid'] == 0) {
			return '';
		}

		return ' AND ' . REALTY_TABLE_OBJECTS . '.uid=' .
			$this->filterFormData['uid'];
	}

	/**
	 * Returns the WHERE clause part for the objectType selector.
	 *
	 * @return string WHERE clause part beginning with " AND", will be empty if
	 *                no filter form data was provided for the objectType
	 *                selector
	 */
	private function getObjectTypeWhereClausePart() {
		if ($this->filterFormData['objectType'] == '') {
			return '';
		}

		$objectType = ($this->filterFormData['objectType'] == 'forRent')
			? REALTY_FOR_RENTING
			: REALTY_FOR_SALE;

		return ' AND ' . REALTY_TABLE_OBJECTS . '.object_type = ' . $objectType;
	}

	/**
	 * Checks whether a given search field ID is set in displayedSearchFields
	 *
	 * @param string the search field name to check, must not be empty
	 *
	 * @return boolean true if the given field should be displayed as set per
	 *                 configuration, false otherwise
	 */
	private function hasSearchField($fieldToCheck) {
		return in_array($fieldToCheck, $this->displayedSearchFields);
	}

	/**
	 * Returns an onChange attribute for the search wigdet fields.
	 *
	 * @return string attribute which sends the search widget on change event
	 *                handler, will be empty if more than one field is shown
	 */
	private function getOnChangeForSingleField() {
		if (count($this->displayedSearchFields) == 1) {
			$result = ' onchange="document.' .
				'forms[\'tx_realty_pi1_searchWidget\'].submit();"';
		} else {
			$result = '';
		}

		return $result;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/pi1/class.tx_realty_filterForm.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/pi1/class.tx_realty_filterForm.php']);
}
?>