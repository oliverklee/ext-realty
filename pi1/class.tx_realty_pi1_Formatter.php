<?php

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * This class returns formatted realty object properties.
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class tx_realty_pi1_Formatter extends Tx_Oelib_TemplateHelper
{
    /**
     * faking $this->scriptRelPath so the locallang.xlf file is found
     *
     * @var string
     */
    public $scriptRelPath = 'Resources/Private/Language/locallang.xlf';

    /**
     * @var string same as plugin name
     */
    public $prefixId = 'tx_realty_pi1';

    /**
     * @var string the extension key
     */
    public $extKey = 'realty';

    /**
     * @var int UID of the realty object to show
     */
    private $showUid = 0;

    /**
     * The constructor. Initializes the temlatehelper and loads the realty
     * object.
     *
     * @throws InvalidArgumentException if $realtyObjectUid is not a UID of a realty object
     *
     * @param int $realtyObjectUid UID of the object of which to get formatted properties, must be > 0
     * @param array $configuration TypoScript configuration for the plugin
     * @param ContentObjectRenderer $contentObjectRenderer the parent cObj content, needed for the flexforms
     */
    public function __construct($realtyObjectUid, array $configuration, ContentObjectRenderer $contentObjectRenderer)
    {
        if ($realtyObjectUid <= 0) {
            throw new InvalidArgumentException('$realtyObjectUid must be greater than zero.', 1333036496);
        }

        /** @var tx_realty_Mapper_RealtyObject $mapper */
        $mapper = Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_RealtyObject::class);
        if (!$mapper->existsModel($realtyObjectUid, true)) {
            throw new InvalidArgumentException(
                'There was no realty object to load with the provided UID of ' . $realtyObjectUid .
                '. The formatter can only work for existing, non-deleted realty objects.',
                1333036514
            );
        }

        $this->showUid = $realtyObjectUid;
        $this->cObj = $contentObjectRenderer;
        $this->init($configuration);
    }

    /**
     * Returns the formatted content of a realty object field.
     *
     * @throws InvalidArgumentException if $key was empty
     *
     * @param string $key
     *        key of the realty object's field of which to retrieve the
     *        formatted value, may also be "address", must not be empty
     *
     * @return string formatted value of the field, may be empty
     */
    public function getProperty($key)
    {
        if ($key === '') {
            throw new InvalidArgumentException('$key must not be empty.', 1333036539);
        }

        $result = '';
        $realtyObject = $this->getRealtyObject();
        $uid = $this->getUid();
        $rawProperty = ($key !== 'uid') ? (string)$realtyObject->getProperty($key) : (string)$uid;

        switch ($key) {
            case 'status':
                $result = $this->getLabelForValidProperty('status', $realtyObject->getStatus());
                break;
            case 'flooring':
                // The fallthrough is intended.
            case 'heating_type':
                // The fallthrough is intended.
            case 'furnishing_category':
                // The fallthrough is intended.
            case 'energy_certificate_type':
                // The fallthrough is intended.
            case 'energy_certificate_year':
                // The fallthrough is intended.
            case 'building_type':
                // The fallthrough is intended.
            case 'state':
                $result = $this->getLabelForValidNonZeroProperty($key);
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
                $result = htmlspecialchars($realtyObject->getForeignPropertyField($key));
                break;
            case 'country':
                if ((int)$rawProperty !== $this->getConfValueInteger('defaultCountryUID')) {
                    $result = $realtyObject->getForeignPropertyField($key, 'cn_short_local');
                }
                break;
            case 'total_area':
                // The fallthrough is intended.
            case 'total_usable_area':
                // The fallthrough is intended.
            case 'office_space':
                // The fallthrough is intended.
            case 'shop_area':
                // The fallthrough is intended.
            case 'sales_area':
                // The fallthrough is intended.
            case 'storage_area':
                // The fallthrough is intended.
            case 'living_area':
                // The fallthrough is intended.
            case 'other_area':
                // The fallthrough is intended.
            case 'estate_size':
                $result = $this->getFormattedArea($key);
                break;
            case 'window_bank':
                $result = $this->getFormattedNumber($key, $this->translate('label_meter'));
                break;
            case 'distance_to_the_sea':
                $result = $this->getFormattedNumber($key, $this->translate('label_meter'));
                break;
            case 'rent_excluding_bills':
                // The fallthrough is intended.
            case 'extra_charges':
                // The fallthrough is intended.
            case 'buying_price':
                // The fallthrough is intended.
            case 'year_rent':
                // The fallthrough is intended.
            case 'rental_income_target':
                // The fallthrough is intended.
            case 'garage_rent':
                // The fallthrough is intended.
            case 'hoa_fee':
                // The fallthrough is intended.
            case 'rent_per_square_meter':
                // The fallthrough is intended.
            case 'garage_price':
                // The fallthrough is intended.
            case 'rent_with_heating_costs':
                // The fallthrough is intended.
            case 'deposit':
                $result = htmlentities($this->getFormattedPrice($key), ENT_QUOTES, 'utf-8');
                break;
            case 'bedrooms':
                // The fallthrough is intended.
            case 'bathrooms':
                // The fallthrough is intended.
            case 'number_of_rooms':
                $result = $this->getFormattedDecimal($key, 1);
                break;
            case 'provision':
                // The fallthrough is intended.
            case 'usable_from':
                $result = htmlspecialchars($rawProperty);
                break;
            case 'site_occupancy_index':
                // The fallthrough is intended.
            case 'floor_space_index':
                $result = $this->getFormattedDecimal($key);
                break;
            case 'floor':
                // The fallthrough is intended.
            case 'floors':
                // The fallthrough is intended.
            case 'parking_spaces':
                // The fallthrough is intended.
            case 'construction_year':
                $number = (int)$rawProperty;
                $result = $number !== 0 ? (string)$number : '';
                break;
            case 'heating_included':
                // The fallthrough is intended.
            case 'has_air_conditioning':
                // The fallthrough is intended.
            case 'has_pool':
                // The fallthrough is intended.
            case 'has_community_pool':
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
                // The fallthrough is intended.
            case 'with_hot_water':
                // The fallthrough is intended.
            case 'sea_view':
                // The fallthrough is intended.
            case 'wheelchair_accessible':
                // The fallthrough is intended.
            case 'ramp':
                // The fallthrough is intended.
            case 'lifting_platform':
                // The fallthrough is intended.
            case 'suitable_for_the_elderly':
                $result = (bool)$rawProperty ? $this->translate('message_yes') : '';
                break;
            case 'teaser':
                // The fallthrough is intended.
            case 'description':
                // The fallthrough is intended.
            case 'equipment':
                // The fallthrough is intended.
            case 'layout':
                // The fallthrough is intended.
            case 'location':
                // The fallthrough is intended.
            case 'misc':
                $result = $this->pi_RTEcssText($rawProperty);
                break;
            case 'address':
                $result = $realtyObject->getAddressAsHtml();
                break;
            case 'uid':
                $result = $uid;
                break;
            case 'energy_certificate_issue_date':
                $timestamp = (int)$rawProperty;
                $result = $this->formatDate($timestamp);
                break;
            default:
                $result = htmlspecialchars($realtyObject->getProperty($key));
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
     * @param string $key key of the current record's field that contains the suffix for the label to get, must not be
     *     empty
     *
     * @return string localized string for the label
     *                "label_[$key][value of $key]", will be a
     *                comma-separated list of localized strings if
     *                the value of $key was a comma-separated list of suffixes,
     *                will be empty if the value of $key combined with
     *                label_[$key] is not a locallang key
     */
    private function getLabelForValidNonZeroProperty($key)
    {
        $localizedStrings = [];

        foreach (GeneralUtility::trimExplode(',', $this->getRealtyObject()->getProperty($key), true) as $value) {
            if ($value >= 1) {
                $localizedStrings[] = $this->getLabelForValidProperty($key, $value);
            }
        }

        return implode(', ', $localizedStrings);
    }

    /**
     * Returns the label for "label_[$key]_[$value]" or an empty string
     * if $value combined with label_[$key] is not a locallang key.
     *
     * @param string $key key of the current record's field that contains the suffix for the label to get, must not be
     *     empty
     * @param string $value the value to fetch the label for, must not be empty
     *
     * @return string
     *        localized string for the label "label_[$key]_[$value]",
     *        will be empty if $value combined with label_[$key] is not a
     *        locallang key
     */
    private function getLabelForValidProperty($key, $value)
    {
        $locallangKey = 'label_' . $key . '_' . $value;
        $translatedLabel = $this->translate($locallangKey);

        return $translatedLabel !== $locallangKey ? $translatedLabel : '';
    }

    /**
     * Retrieves the value of the record field $key formatted as an area.
     * If the field's value is empty or its int value is zero, an empty string will
     * be returned.
     *
     * @param string $key
     *        key of the field to retrieve (the name of a database column),
     *        must not be empty
     *
     * @return string HTML for the number in the field formatted using
     *                decimalSeparator and areaUnit from the TS setup, may
     *                be an empty string
     */
    private function getFormattedArea($key)
    {
        return $this->getFormattedNumber($key, $this->translate('label_squareMeters'));
    }

    /**
     * Returns the number found in the database column $key with a currency
     * symbol appended. This symbol is the value of "currency" derived from
     * the same record or, if not available, "currencyUnit" set in the TS
     * setup.
     * Formats the $key using the oelib priceViewHelper for the given ISO alpha code.
     * If the value of $key is zero after casting to int, an empty string will be returned.
     *
     * @param string $key name of a database column, may not be empty
     *
     * @return string HTML for the number in the field with a currency
     *                symbol appended, may be an empty string
     */
    private function getFormattedPrice($key)
    {
        $currency = $this->getRealtyObject()->getProperty('currency');

        if ($currency === '') {
            $currency = $this->getConfValueString('currencyUnit');
        }

        $rawValue = $this->getRealtyObject()->getProperty($key);
        if ($rawValue === '' || (float)$rawValue === 0.0) {
            return '';
        }

        /** @var Tx_Oelib_ViewHelper_Price $priceViewHelper */
        $priceViewHelper = GeneralUtility::makeInstance(Tx_Oelib_ViewHelper_Price::class);
        $priceViewHelper->setCurrencyFromIsoAlpha3Code($currency);
        $priceViewHelper->setValue((float)$rawValue);

        return $priceViewHelper->render();
    }

    /**
     * Retrieves the value of the record field $key and formats it. If the field's value is
     * empty or its int value is zero, an empty string will be returned.
     *
     * @param string $key key of the field to retrieve (the name of a database column), must not be empty
     * @param string $unit unit of the formatted number, must not be empty
     *
     * @return string HTML for the formatted number in the field, may be an empty string
     */
    private function getFormattedNumber($key, $unit)
    {
        $rawValue = $this->getRealtyObject()->getProperty($key);
        if ((string)$rawValue === '' || (float)$rawValue === 0.0) {
            return '';
        }

        $formattedNumber = $this->formatDecimal((float)$rawValue);

        return $formattedNumber . '&nbsp;' . $unit;
    }

    /**
     * Returns the current "showUid".
     *
     * @return int UID of the realty record to show, will be > 0
     */
    private function getUid()
    {
        return $this->showUid;
    }

    /**
     * Retrieves the value of the record field $key, formats it and strips zeros on the end of the value.
     *
     * @param string $key name of a database column, must not be empty
     * @param int $decimals
     *        the number of decimals after the decimal point, must be >= 0
     *
     * @return string the number in the field formatted and stripped of trailing zeros, will be empty if the value is
     *     zero
     */
    private function getFormattedDecimal($key, $decimals = 2)
    {
        $value = str_replace(',', '.', $this->getRealtyObject()->getProperty($key));

        return $this->formatDecimal((float)$value, $decimals);
    }

    /**
     * Formats the given decimal removing trailing zeros and the decimal point if necessary.
     *
     * @param float $number the number to format
     * @param int $decimals the number of decimals after the decimal point, must be >= 0
     *
     * @return string the formatted float, will be empty if zero was given
     */
    public function formatDecimal($number, $decimals = 2)
    {
        if ((float)$number === 0.0) {
            return '';
        }
        if ((float)$number !== round($number)) {
            $realDecimals = $decimals;
        } else {
            $realDecimals = 0;
        }

        $decimalMark = $this->translate('decimal_mark');
        $thousandsSeparator = $this->translate('thousands_separator');
        if ($thousandsSeparator === '') {
            $thousandsSeparator = '&#x202f;';
        }

        return number_format($number, $realDecimals, $decimalMark, $thousandsSeparator);
    }

    /**
     * Retrieves the realty object with the given UID.
     *
     * @return tx_realty_Model_RealtyObject
     */
    protected function getRealtyObject()
    {
        /** @var tx_realty_Mapper_RealtyObject $mapper */
        $mapper = Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_RealtyObject::class);
        return $mapper->find($this->getUid());
    }

    /**
     * Formats a timestamp as a date using the localized date format
     *
     * @param int $timestamp
     *
     * @return string the formatted date, will be empty if $timestamp is 0
     */
    protected function formatDate($timestamp)
    {
        if ($timestamp === 0) {
            return '';
        }

        return date($this->translate('date_format'), $timestamp);
    }
}
