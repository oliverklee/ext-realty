<?php
/***************************************************************
* Copyright notice
*
* (c) 2009 Saskia Metzler <saskia@merlin.owl.de>
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
require_once(t3lib_extMgm::extPath('realty') . 'lib/class.tx_realty_object.php');

/**
 * Class 'tx_realty_pi1_Formatter' for the 'realty' extension.
 *
 * This class returns formatted realty object properties.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 */
class tx_realty_pi1_Formatter extends tx_oelib_templatehelper {
	/**
	 * @var string path to this script relative to the extension dir
	 */
	public $scriptRelPath = 'pi1/class.tx_realty_pi1_Formatter.php';

	/**
	 * @var string same as plugin name
	 */
	public $prefixId = 'tx_realty_pi1';

	/**
	 * @var string the extension key
	 */
	public $extKey = 'realty';

	/**
	 * @var integer character length for cropped titles
	 */
	const CROP_SIZE = 74;

	/**
	 * @var tx_realty_object realty object
	 */
	private $realtyObject = null;

	/**
	 * The constructor. Initializes the temlatehelper and loads the realty
	 * object.
	 *
	 * @throws Exception if $realtyObjectUid is not a UID of a realty object
	 *
	 * @param integer UID of the object of which to get formatted properties,
	 *                must be > 0
	 * @param array TypoScript configuration for the plugin
	 * @param tslib_cObj the parent cObj content, needed for the flexforms
	 */
	public function __construct(
		$realtyObjectUid, array $configuration, tslib_cObj $cObj
	) {
		if ($realtyObjectUid == 0) {
			throw new Exception('$realtyObjectUid must be greater than zero.');
		}

		$this->realtyObject = t3lib_div::makeInstance('tx_realty_object');
		$this->loadRealtyObject($realtyObjectUid);

		if ($this->realtyObject->isRealtyObjectDataEmpty()) {
			throw new Exception('There was no realty object to load with the ' .
				'provided UID of ' . $realtyObjectUid . '. The formatter can ' .
				'only work for existing, non-deleted realty objects.'
			);
		}

		$this->cObj = $cObj;
		$this->init($configuration);
	}

	/**
	 * Frees as much memory that has been used by this object as possible.
	 */
	public function __destruct() {
		unset($this->realtyObject);
		parent::__destruct();
	}

	/**
	 * Returns the formatted content of a realty object field.
	 *
	 * @throws Exception if $key was empty
	 *
	 * @param string key of the realty object's field of which to retrieve the
	 *               formatted value, may also be "address" or "cropped_title",
	 *               must not be empty
	 *
	 * @return string formatted value of the field, may be empty
	 */
	public function getProperty($key) {
		if ($key == '') {
			throw new Exception('$key must not be empty.');
		}

		$result = '';

		switch($key) {
			case 'heating_type':
				// The fallthrough is intended.
			case 'state':
				$result = $this->getLabelForValidProperty($key);
				break;
			case 'pets':
				// The fallthrough is intended.
			case 'garage_type':
				// The fallthrough is intended.
			case 'house_type':
				// The fallthrough is intended.
			case 'apartment_type':
				// The fallthrough is intended.
			case 'city':
				// The fallthrough is intended.
			case 'district':
				$result = htmlspecialchars(
					$this->realtyObject->getForeignPropertyField($key)
				);
				break;
			case 'country':
				if ($this->realtyObject->getProperty($key)
					!= $this->getConfValueInteger('defaultCountryUID')
				) {
					$result = $this->realtyObject
						->getForeignPropertyField($key, 'cn_short_local');
				}
				break;
			case 'total_area':
				// The fallthrough is intended.
			case 'living_area':
				// The fallthrough is intended.
			case 'estate_size':
				$result = $this->getFormattedArea($key);
				break;
			case 'rent_excluding_bills':
				// The fallthrough is intended.
			case 'extra_charges':
				// The fallthrough is intended.
			case 'buying_price':
				// The fallthrough is intended.
			case 'year_rent':
				// The fallthrough is intended.
			case 'garage_rent':
				// The fallthrough is intended.
			case 'hoa_fee':
				// The fallthrough is intended.
			case 'garage_price':
				$result = $this->getFormattedPrice($key);
				break;
			case 'usable_from':
				$usableFrom = $this->realtyObject->getProperty($key);
				// If no date is set, assume "now".
				$result = ($usableFrom != '')
					? htmlspecialchars($usableFrom)
					: $this->translate('message_now');
				break;
			case 'number_of_rooms':
				// The fallthrough is intended.
			case 'floor':
				// The fallthrough is intended.
			case 'floors':
				// The fallthrough is intended.
			case 'bedrooms':
				// The fallthrough is intended.
			case 'bathrooms':
				// The fallthrough is intended.
			case 'construction_year':
				$number = $this->realtyObject->getProperty($key);
				$result = ($number != 0) ? ((string) $number) : '';
				break;
			case 'heating_included':
				// The fallthrough is intended.
			case 'has_air_conditioning':
				// The fallthrough is intended.
			case 'has_pool':
				// The fallthrough is intended.
			case 'has_community_pool':
				// The fallthrough is intended.
			case 'rented':
				// The fallthrough is intended.
			case 'balcony':
				// The fallthrough is intended.
			case 'garden':
				// The fallthrough is intended.
			case 'elevator':
				// The fallthrough is intended.
			case 'barrier_free':
				// The fallthrough is intended.
			case 'assisted_living':
				// The fallthrough is intended.
			case 'fitted_kitchen':
				$result = ($this->realtyObject->getProperty($key) == 1)
					? $this->translate('message_yes')
					: '';
				break;
			case 'description':
				// The fallthrough is intended.
			case 'equipment':
				// The fallthrough is intended.
			case 'location':
				// The fallthrough is intended.
			case 'misc':
				$result = $this->pi_RTEcssText(
					$this->realtyObject->getProperty($key)
				);
				break;
			case 'address':
				$result = $this->realtyObject->getAddressAsHtml();
				break;
			case 'uid':
				$result = $this->realtyObject->getUid();
				break;
			default:
				$result = htmlspecialchars(
					$this->realtyObject->getProperty($key)
				);
				break;
		}

		return trim($result);
	}

	/**
	 * Returns the label for "label_[$key] . [value of $key]" or an empty string
	 * if the value of $key combined with label_[$key] is not a locallang key.
	 *
	 * The value of $key may be a comma-separated list of suffixes. In this case,
	 * a comma-separated list of the localized strings is returned.
	 *
	 * @param string key of the current record's field that contains the
	 *               suffix for the label to get, must not be empty
	 *
	 * @return string localized string for the label
	 *                "label_[$key] . [value of $key]", will be a
	 *                comma-separated list of localized strings if
	 *                the value of $key was a comma-separated list of suffixes,
	 *                will be empty if the value of $key combined with
	 *                label_[$key] is not a locallang key
	 */
	private function getLabelForValidProperty($key) {
		$localizedStrings = array();

		foreach (explode(',', $this->realtyObject->getProperty($key)) as $suffix) {
			if ($suffix >= 1) {
				$locallangKey = 'label_' . $key . '.' . $suffix;
				$translatedLabel = $this->translate($locallangKey);
				$localizedStrings[] = ($translatedLabel != $locallangKey)
					? $translatedLabel
					: '';
			}
		}

		return implode(', ', $localizedStrings);
	}

	/**
	 * Retrieves the value of the record field $key formatted as an area.
	 * If the field's value is empty or its intval is zero, an empty string will
	 * be returned.
	 *
	 * @param string key of the field to retrieve (the name of a database
	 *               column), must not be empty
	 *
	 * @return string HTML for the number in the field formatted using
	 *                decimalSeparator and areaUnit from the TS setup, may
	 *                be an empty string
	 */
	private function getFormattedArea($key) {
		return $this->getFormattedNumber(
			$key, $this->translate('label_squareMeters')
		);
	}

	/**
	 * Returns the number found in the database column $key with a currency
	 * symbol appended. This symbol is the value of "currency" derived from
	 * the same record or, if not available, "currencyUnit" set in the TS
	 * setup.
	 * If the value of $key is zero after applying intval, an empty string
	 * will be returned.
	 *
	 * @param string name of a database column, may not be empty
	 *
	 * @return string HTML for the number in the field with a currency
	 *                symbol appended, may be an empty string
	 */
	private function getFormattedPrice($key) {
		return $this->getFormattedNumber(
			$key, $this->getConfValueString('currencyUnit')
		);
	}

	/**
	 * Retrieves the value of the record field $key and formats,
	 * using the system's locale and appending $unit. If the field's value is
	 * empty or its intval is zero, an empty string will be returned.
	 *
	 * @param string key of the field to retrieve (the name of a database
	 *               column), must not be empty
	 * @param string unit of the formatted number, must not be empty
	 *
	 * @return string HTML for the number in the field formatted using the
	 *                system's locale with $unit appended, may be an empty
	 *                string
	 */
	private function getFormattedNumber($key, $unit) {
		$rawValue = $this->realtyObject->getProperty($key);
		if (($rawValue == '') || (intval($rawValue) == 0)) {
			return '';
		}

		$localeConvention = localeconv();
		$decimals = intval($this->getConfValueString('numberOfDecimals'));

		$formattedNumber = number_format(
			$rawValue, $decimals, $localeConvention['decimal_point'], ' '
		);

		return $formattedNumber . '&nbsp;' . $unit;
	}

	/**
	 * Loads the realty object.
	 *
	 * This function is public for testing purposes only.
	 *
	 * @param integer UID of the realty object to load
	 */
	public function loadRealtyObject($uid) {
		$this->realtyObject->loadRealtyObject($uid, true);
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/pi1/class.tx_realty_pi1_Formatter.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/pi1/class.tx_realty_pi1_Formatter.php']);
}
?>