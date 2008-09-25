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

/**
 * Class 'tx_realty_filterForm' for the 'realty' extension. This class
 * provides a form to enter filter criteria for the realty list in the realty
 * plugin.
 *
 * @package		TYPO3
 * @subpackage	tx_realty
 *
 * @author		Saskia Metzler <saskia@merlin.owl.de>
 */

require_once(t3lib_extMgm::extPath('realty') . 'lib/tx_realty_constants.php');

class tx_realty_filterForm {
	/** plugin in which the filter form is used */
	private $plugin = null;

	/**
	 * Filter form data array with the elements "priceRange" and "site".
	 * "priceRange" keeps a string of the format "number-number" and "site" has
	 * any string, directly derived from the form data.
	 */
	private $filterFormData = array('priceRange' => '', 'site' => '');

	/**
	 * The constructor.
	 *
	 * @param	tx_oelib_templatehelper		plugin which uses this class
	 */
	public function __construct(tx_oelib_templatehelper $plugin) {
		$this->plugin = $plugin;
	}

	/**
	 * Returns the filter form in HTML.
	 *
	 * @param	array		current piVars, the elements "priceRange" and "site"
	 * 						will be used if they are available, may be empty
	 *
	 * @return	string		HTML of the filter form, will not be empty
	 */
	public function render(array $filterFormData) {
		$this->extractValidFilterFormData($filterFormData);

		$this->setTargetUrlMarker();
		$this->fillOrHideSiteSearch();
		$this->fillOrHidePriceRangeDropDown();
		$this->fillOrHideIdSearch();

		return $this->plugin->getSubpart('FILTER_FORM');
	}

	/**
	 * Returns a WHERE clause part derived from the provided form data.
	 *
	 * The table on which this WHERE clause part can be applied must be
	 * "tx_realty_objects INNER JOIN tx_realty_cities
	 * ON tx_realty_objects.city = tx_realty_cities.uid";
	 *
	 * @param	array		filter form data, may be empty
	 *
	 * @return	string		WHERE clause part for the current filters beginning
	 * 						with " AND", will be empty if none were provided
	 */
	public function getWhereClausePart(array $filterFormData) {
		$this->extractValidFilterFormData($filterFormData);

		return $this->getPriceRangeWhereClausePart() .
			$this->getSiteWhereClausePart();
	}

	/**
	 * Stores the provided data derived from the form. In case invalid data was
	 * provided, an empty string will be stored.
	 *
	 * @param	array		filter form data, may be empty
	 */
	private function extractValidFilterFormData(array $formData) {
		if (isset($formData['priceRange'])
			&& preg_match('/^(\d+-\d+|-\d+|\d+-)$/', $formData['priceRange'])
		) {
			$this->filterFormData['priceRange'] = $formData['priceRange'];
		} else {
			$this->filterFormData['priceRange'] = '';
		}

		if ($this->isSiteSearchVisible() && isset($formData['site'])) {
			$this->filterFormData['site'] = $formData['site'];
		} else {
			$this->filterFormData['site'] = '';
		}
	}

	/**
	 * Formats one price range.
	 *
	 * @param	string		price range of the format "number-number", may be
	 * 						empty
	 *
	 * @return	array		array with one price range, consists of the two
	 * 						elements "upperLimit" and "lowerLimit", will be
	 * 						empty if no price range was provided in the form
	 * 						data
	 */
	private function getFormattedPriceRange($priceRange) {
		if ($priceRange == '') {
			return array();
		}

		$rangeLimits = explode('-', $priceRange);

		// intval() converts an empty string to 0. So for "-100" zero and 100
		// will be stored as limits.
		return array(
			'lowerLimit' => intval($rangeLimits[0]),
			'upperLimit' => intval($rangeLimits[1])
		);
	}

	/**
	 * Returns whether the site search is configured to be visible in the filter
	 * form.
	 *
	 * @return	boolean		true if the site search should be displayed, false
	 * 						otherwise
	 */
	private function isSiteSearchVisible() {
		return $this->plugin->getConfValueString(
			'showSiteSearchInFilterForm', 's_searchForm')
			== 'show';
	}

	/**
	 * Sets the target URL marker.
	 */
	private function setTargetUrlMarker() {
		$this->plugin->setMarker(
			'target_url',
			t3lib_div::locationHeaderUrl(
				$this->plugin->cObj->typoLink_URL(
					array(
						'parameter' => $this->plugin->getConfValueInteger(
							'filterTargetPID',
							's_searchForm'
						),
					)
				)
			)
		);
	}

	/**
	 * Fills the input box for zip code or city if there is data for it. Hides
	 * the input if it is disabled by configuration.
	 */
	private function fillOrHideSiteSearch() {
		if ($this->isSiteSearchVisible()) {
			$this->plugin->setMarker(
				'site', htmlspecialchars($this->filterFormData['site'])
			);
		} else {
			$this->plugin->hideSubparts('wrapper_site_search');
		}
	}

	/**
	 * Fills the price range drop-down with the configured ranges or hides it if
	 * none are configured.
	 */
	private function fillOrHidePriceRangeDropDown() {
		$priceRanges = $this->getPriceRangesFromConfiguration();

		if (!empty($priceRanges)) {
			$optionTags = '';

			foreach ($priceRanges as $key => $range) {
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
			$this->plugin->setMarker('price_range_options', $optionTags);
		} else {
			$this->plugin->hideSubparts('wrapper_price_range_options');
		}
	}

	/**
	 * Returns an array of configured price ranges.
	 *
	 * @return	array			Two-dimensional array of the possible price
	 * 							ranges. Each inner array consists of two
	 * 							elements with the keys "lowerLimit" and
	 * 							"upperLimit". Note that the zero element will
	 * 							always be empty because the first option in the
	 * 							selectbox remains empty. If no price ranges are
	 * 							configured, this array will be empty.
	 */
	private function getPriceRangesFromConfiguration() {
		if (!$this->plugin->hasConfValueString(
			'priceRangesForFilterForm', 's_searchForm')
		) {
			return array();
		}

		// The first element is empty because the first selectbox element should
		// remain empty.
		$priceRanges = array(array());

		$priceRangeConfiguration = t3lib_div::trimExplode(
			',', $this->plugin->getConfValueString(
				'priceRangesForFilterForm',
				's_searchForm')
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
	 * @param	array		range for which to receive the label, must have the
	 * 						elements "upperLimit" and "lowerLimit", both must
	 * 						have integers as values, only one of the elements'
	 * 						values may be 0, for an empty array the result will
	 * 						always be "&nbsp;"
	 *
	 * @return	string		formatted label for the price range, will be "&nbsp;"
	 * 						if an empty array was provided (an empty string
	 * 						would break the XHTML output's validity)
	 */
	private function getPriceRangeLabel(array $range) {
		if (empty($range)) {
			return '&nbsp;';
		}

		$currencySymbol = $this->plugin->getConfValueString('currencyUnit');

		if ($range['lowerLimit'] == 0) {
			$result = $this->plugin->translate('label_less_than') . ' ' .
				$range['upperLimit'] . $currencySymbol;
		} elseif ($range['upperLimit'] == 0) {
			$result = $this->plugin->translate('label_greater_than') . ' ' .
				$range['lowerLimit'] . $currencySymbol;
		} else {
			$result = $range['lowerLimit'] . $currencySymbol . ' ' .
				$this->plugin->translate('label_to') . ' ' .
				$range['upperLimit'] . $currencySymbol;
		}

		return $result;
	}

	/**
	 * Returns a WHERE clause part for one price range.
	 *
	 * @return	string		WHERE clause part for the provided price range
	 * 						starting with " AND", will be empty if the filter
	 * 						form data was zero
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
			'>'.$equalSign.' ' . $range['lowerLimit'];
		$lowerLimitBuy = REALTY_TABLE_OBJECTS . '.buying_price ' .
			'>'.$equalSign.' '.$range['lowerLimit'];

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
	 * @return	string		WHERE clause part beginning with " AND", will be
	 * 						empty if no filter form data was provided for
	 * 						the site
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
	 * Fills the input box for the UID or the object number search if it is
	 * configured to be displayed. Hides the form element if it is disabled by
	 * configuration.
	 */
	private function fillOrHideIdSearch() {
		$searchType = $this->plugin->getConfValueString(
			'showIdSearchInFilterForm',
			's_searchForm'
		);
		if ($searchType == '') {
			$this->plugin->hideSubparts('wrapper_id_search');
			return;
		}

		$this->plugin->setMarker(
			'id_search_label',
			$this->plugin->translate(
				'label_enter_' . $searchType
			)
		);
		$this->plugin->setMarker('id_search_type', $searchType);
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/pi1/class.tx_realty_filterForm.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/pi1/class.tx_realty_filterForm.php']);
}
?>