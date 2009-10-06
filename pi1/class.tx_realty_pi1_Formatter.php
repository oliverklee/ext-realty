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
	 * @var integer UID of the realty object to show
	 */
	private $showUid = 0;

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
		if ($realtyObjectUid <= 0) {
			throw new Exception('$realtyObjectUid must be greater than zero.');
		}

		if (!tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->existsModel($realtyObjectUid, true)
		) {
			throw new Exception('There was no realty object to load with the ' .
				'provided UID of ' . $realtyObjectUid . '. The formatter can ' .
				'only work for existing, non-deleted realty objects.'
			);
		}

		$this->showUid = $realtyObjectUid;
		$this->cObj = $cObj;
		$this->init($configuration);
	}

	/**
	 * Returns the formatted content of a realty object field.
	 *
	 * @throws Exception if $key was empty
	 *
	 * @param string $key
	 *        key of the realty object's field of which to retrieve the
	 *        formatted value, may also be "address", must not be empty
	 *
	 * @return string formatted value of the field, may be empty
	 */
	public function getProperty($key) {
		if ($key == '') {
			throw new Exception('$key must not be empty.');
		}

		$result = '';
		$realtyObject = tx_oelib_MapperRegistry
			::get('tx_realty_Mapper_RealtyObject')->find($this->getUid());

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
					$realtyObject->getForeignPropertyField($key)
				);
				break;
			case 'country':
				if ($realtyObject->getProperty($key)
					!= $this->getConfValueInteger('defaultCountryUID')
				) {
					$result = $realtyObject->getForeignPropertyField(
						$key, 'cn_short_local'
					);
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
				$result = htmlspecialchars($realtyObject->getProperty($key));
				break;
			case 'bedrooms':
				// The fallthrough is intended.
			case 'bathrooms':
				// The fallthrough is intended.
			case 'number_of_rooms':
				$result = $this->getFormattedDecimal($key);
				break;
			case 'floor':
				// The fallthrough is intended.
			case 'floors':
				// The fallthrough is intended.
			case 'construction_year':
				$number = $realtyObject->getProperty($key);
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
				$result = ($realtyObject->getProperty($key) == 1)
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
				$result = $this->pi_RTEcssText($realtyObject->getProperty($key));
				break;
			case 'address':
				$result = $realtyObject->getAddressAsHtml();
				break;
			case 'uid':
				$result = $this->getUid();
				break;
			default:
				$result = htmlspecialchars($realtyObject->getProperty($key));
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

		foreach (
			t3lib_div::trimExplode(
				',',
				tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
					->find($this->getUid())->getProperty($key),
				true
			) as $suffix
		) {
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
		$currency = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->find($this->getUid())->getProperty('currency');

		if ($currency == '') {
			$currency = $this->getConfValueString('currencyUnit');
		}

		return $this->getFormattedNumber($key, $currency);
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
		$rawValue = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->find($this->getUid())->getProperty($key);
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
	 * Returns the current "showUid".
	 *
	 * @return UID of the realty record to show, will be > 0
	 */
	private function getUid() {
		return $this->showUid;
	}

	/**
	 * Retrieves the value of the record field $key, formats it using the
	 * system's locale and strips zeros on the end of the value.
	 *
	 * @param string $key name of a database column, must not be empty
	 *
	 * @return string the number in the field formatted using the system's
	 *                locale and stripped of trailing zeros, will be empty if
	 *                the value is zero.
	 */
	private function getFormattedDecimal($key) {
		$value = str_replace(
			',',
			'.',
			tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
				->find($this->getUid())->getProperty($key)
		);

		return self::formatDecimal(floatval($value));
	}

	/**
	 * Formats the given decimal removing trailing zeros and the decimal point
	 * if neccessary.
	 *
	 * @param float $number the number to format
	 *
	 * @return $string the formatted float, will be empty if zero was given
	 */
	public static function formatDecimal($number) {
		if ($number == 0) {
			return '';
		}

		$localeConvention = localeconv();
		$decimalPoint = $localeConvention['decimal_point'];
		$formattedNumber = number_format($number, 2, $decimalPoint, '');

		return preg_replace('/\\' . $decimalPoint . '?0+$/',
			'',
			$formattedNumber
		);
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/pi1/class.tx_realty_pi1_Formatter.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/pi1/class.tx_realty_pi1_Formatter.php']);
}
?>