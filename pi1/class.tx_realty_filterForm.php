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

	/** filter form data array */
	private $filterFormData = array('priceRange' => 0);

	/**
	 * Two-dimensional array for the possible price ranges. Each inner array
	 * consists of two elements with the keys "lowerLimit" and "upperLimit".
	 * Note that the zero element will always be empty because the first option
	 * in the selectbox remains empty.
	 * If no price ranges are configured, this array will be empty.
	 */
	private $priceRanges = array();

	/**
	 * The constructor.
	 *
	 * @param	tx_oelib_templatehelper		plugin which uses this class
	 */
	public function __construct(tx_oelib_templatehelper $plugin) {
		$this->plugin = $plugin;
		$this->setupPriceRanges();
	}

	/**
	 * Returns the filter form in HTML.
	 *
	 * @param	array		current piVars, the element "priceRange" will be
	 * 						used if it is available, may be empty
	 *
	 * @return	string		HTML of the filter form, will not be empty
	 */
	public function render(array $filterFormData) {
		$this->extractValidFilterFormData($filterFormData);

		$this->setTargetUrlMarker();
		$this->fillOrHidePriceRangeDropDown();

		return $this->plugin->getSubpart('FILTER_FORM');
	}

	/**
	 * Returns a WHERE clause part derived from the provided form data.
	 *
	 * @param	array		filter form data, may be empty
	 *
	 * @return	string		WHERE clause part for the current filters beginning
	 * 						with " AND", will be empty if none were provided
	 */
	public function getWhereClausePart(array $filterFormData) {
		$this->extractValidFilterFormData($filterFormData);

		return $this->getPriceRangeWhereClausePart();
	}

	/**
	 * Stores the provided data derived from the form. In case invalid data was
	 * provided, zero will be stored.
	 *
	 * @param	array		filter form data, may be empty
	 */
	private function extractValidFilterFormData(array $formData) {
		if (isset($formData['priceRange'])
			&& isset($this->priceRanges[$formData['priceRange']])
		) {
			$this->filterFormData['priceRange'] = intval($formData['priceRange']);
		} else {
			$this->filterFormData['priceRange'] = 0;
		}
	}

	/**
	 * Stores the configured price ranges for further use in $this->priceRanges.
	 *
	 * This function is declared public for testing purposes.
	 */
	public function setupPriceRanges() {
		if (!$this->plugin->hasConfValueString('priceRangesForFilterForm')) {
			return;
		}

		// The first element is empty because the first selectbox element should
		// remain empty.
		$this->priceRanges[] = '';

		$priceRanges = t3lib_div::trimExplode(
			',', $this->plugin->getConfValueString('priceRangesForFilterForm')
		);

		foreach ($priceRanges as $range) {
			$rangeLimits = explode('-', $range);
			// intval() converts an empty string to 0 if a range like "-100"
			// was given.
			$this->priceRanges[] = array(
				'lowerLimit' => intval($rangeLimits[0]),
				'upperLimit' => intval($rangeLimits[1])
			);
		}
	}

	/**
	 * Sets the target URL marker.
	 */
	private function setTargetUrlMarker() {
		$this->plugin->setMarker(
			'target_url',
			t3lib_div::locationHeaderUrl(
				$this->plugin->cObj->getTypoLink_URL(
					$this->plugin->getConfValueInteger('filterTargetPID')
				)
			)
		);
	}

	/**
	 * Fills the price range drop-down with the configured ranges or hides it if
	 * none are configured.
	 */
	private function fillOrHidePriceRangeDropDown() {
		if (!empty($this->priceRanges)) {
			$optionTags = '';
			foreach ($this->priceRanges as $key => $ranges) {
				$label = $this->getPriceRangeLabel($key);
				$selectedAttribute = ($this->filterFormData['priceRange'] == $key)
					? ' selected="selected"'
					: '';

				$optionTags .= '<option value="' . $key . '" label="' . $label .
					'" ' . $selectedAttribute . '>' . $label . '</option>';
			}
			$this->plugin->setMarker('price_range_options', $optionTags);
		} else {
			$this->plugin->hideSubparts('wrapper_price_range_options');
		}
	}

	/**
	 * Returns a formatted label for one price range according to the configured
	 * currency unit.
	 *
	 * @param	integer		numeric key of the range for which to receive the
	 * 						label, must be >= 0, for 0 the result will always
	 * 						be "&nbsp;"
	 *
	 * @return	string		formatted label for the price range defined by $key,
	 * 						will be "&nbsp;" if $key is zero (an empty string
	 * 						would break the XHTML output's validity)
	 */
	private function getPriceRangeLabel($key) {
		if ($key == 0) {
			return '&nbsp;';
		}

		if (!isset($this->priceRanges[$key])) {
			throw new Exception(
				'There is no price range for the key ' . $key . '. '
			);
		}

		$currencySymbol = $this->plugin->getConfValueString('currencyUnit');
		$range = $this->priceRanges[$key];

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
	 * setupPriceRanges() must have been called before using this function.
	 *
	 * @return	string		WHERE clause part for the provided price range
	 * 						starting with " AND", will be empty if the filter
	 * 						form data was zero
	 */
	private function getPriceRangeWhereClausePart() {
		if ($this->filterFormData['priceRange'] == 0) {
			return '';
		}

		$range = $this->priceRanges[$this->filterFormData['priceRange']];

		// The WHERE clause part for the lower limit is always set, even if no
		// lower limit was provided. The lower limit will just be zero then.
		// For this case it is important to exclude the lower limit of the range
		// because each non-set price will be identified as zero which makes
		// searching for zero-prices futile.
		$equalSign = ($range['lowerLimit'] != 0) ? '=' : '';
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

		return ' AND ((' . $lowerLimitRent . $upperLimitRent .
			') OR (' . $lowerLimitBuy . $upperLimitBuy . '))';
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/pi1/class.tx_realty_filterForm.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/pi1/class.tx_realty_filterForm.php']);
}
?>
