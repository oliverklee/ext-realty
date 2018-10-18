<?php

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This class provides a form to enter filter criteria for the realty list in the realty plugin.
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class tx_realty_filterForm extends tx_realty_pi1_FrontEndView
{
    /**
     * @var array Filter form data array with the the fields for which a filter
     *            is applicable. "priceRange" keeps a string of the format
     *            "number-number" and "site" has any string, directly
     *            derived from the form data. Fields initialized with 0 refer to
     *            integer values and fields initialized with '' to strings.
     */
    private $filterFormData = [
        'uid' => 0,
        'objectNumber' => '',
        'site' => '',
        'city' => 0,
        'district' => 0,
        'houseType' => 0,
        'priceRange' => '',
        'rentFrom' => 0,
        'rentTo' => 0,
        'livingAreaFrom' => 0,
        'livingAreaTo' => 0,
        'objectType' => '',
        'numberOfRoomsFrom' => 0,
        'numberOfRoomsTo' => 0,
    ];

    /**
     * @var string[] the search fields which should be displayed in the search form
     */
    private $displayedSearchFields = [];

    /**
     * Returns the filter form in HTML.
     *
     * @param array $filterFormData
     *        current piVars, the elements "priceRange" and "site" will be used if they are available, may be empty
     *
     * @return string HTML of the filter form, will not be empty
     */
    public function render(array $filterFormData = [])
    {
        $this->extractValidFilterFormData($filterFormData);
        $this->displayedSearchFields = GeneralUtility::trimExplode(
            ',',
            $this->getConfValueString('displayedSearchWidgetFields', 's_searchForm'),
            true
        );

        $this->setTargetUrlMarker();
        $this->fillOrHideUidSearch();
        $this->fillOrHideObjectNumberSearch();
        $this->fillOrHideSiteSearch();
        $this->fillOrHideCitySearch();
        $this->fillOrHideDistrictSearch();
        $this->fillOrHideHouseTypeSearch();
        $this->fillOrHidePriceRangeDropDown();
        $this->fillOrHideFromToSearchField('rent', 'rent');
        $this->fillOrHideFromToSearchField('livingArea', 'living_area');
        $this->fillOrHideFromToSearchField('numberOfRooms', 'number_of_rooms');
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
     * @param array $filterFormData filter form data, may be empty
     *
     * @return string WHERE clause part for the current filters beginning
     *                with " AND", will be empty if none were provided
     */
    public function getWhereClausePart(array $filterFormData)
    {
        $this->extractValidFilterFormData($filterFormData);

        return $this->getUidWhereClausePart() .
            $this->getObjectNumberWhereClausePart() .
            $this->getSiteWhereClausePart() .
            $this->getCityWhereClausePart() .
            $this->getDistrictWhereClausePart() .
            $this->getHouseTypeWhereClausePart() .
            $this->getRentOrPriceRangeWhereClausePart() .
            $this->getLivingAreaWhereClausePart() .
            $this->getObjectTypeWhereClausePart() .
            $this->getNumberOfRoomsWhereClausePart();
    }

    /**
     * Stores the provided data derived from the form. In case invalid data was
     * provided, an empty string will be stored.
     *
     * @param array $formData filter form data, may be empty
     *
     * @return void
     */
    private function extractValidFilterFormData(array $formData)
    {
        foreach ($formData as $key => $rawValue) {
            switch ($key) {
                case 'uid':
                    // The fallthrough is intended.
                case 'city':
                    // The fallthrough is intended.
                case 'district':
                    // The fallthrough is intended.
                case 'houseType':
                    // The fallthrough is intended.
                case 'rentFrom':
                    // The fallthrough is intended.
                case 'rentTo':
                    // The fallthrough is intended.
                case 'livingAreaFrom':
                    // The fallthrough is intended.
                case 'livingAreaTo':
                    $this->filterFormData[$key] = (int)$rawValue;
                    break;
                case 'objectNumber':
                    // The fallthrough is intended.
                case 'site':
                    $this->filterFormData[$key] = $rawValue;
                    break;
                case 'objectType':
                    $this->filterFormData['objectType'] = in_array(
                        $rawValue,
                        ['forSale', 'forRent'],
                        true
                    ) ? $rawValue : '';
                    break;
                case 'priceRange':
                    $this->filterFormData['priceRange'] = preg_match('/^(\\d+-\\d+|-\\d+|\\d+-)$/', $rawValue)
                        ? $rawValue : '';
                    break;
                case 'numberOfRoomsFrom':
                    // The fallthrough is intended.
                case 'numberOfRoomsTo':
                    $commaFreeValue = (float)$this->replaceCommasWithDots($rawValue);
                    if ($commaFreeValue !== round($commaFreeValue)) {
                        $decimals = 1;
                    } else {
                        $decimals = 0;
                    }
                    $decimalMark = $this->translate('decimal_mark');
                    $this->filterFormData[$key] = number_format($commaFreeValue, $decimals, $decimalMark, '');
                    break;
                default:
            }
        }
    }

    /**
     * Formats one price range.
     *
     * @param string $priceRange price range of the format "number-number", may be empty
     *
     * @return int[] array with one price range, consists of the two elements
     *               "upperLimit" and "lowerLimit", will be empty if no price
     *               range was provided in the form data
     */
    private function getFormattedPriceRange($priceRange)
    {
        if ($priceRange === '') {
            return [];
        }

        $rangeLimits = GeneralUtility::intExplode('-', $priceRange);

        // (int) converts an empty string to 0. So for "-100" zero and 100
        // will be stored as limits.
        return [
            'lowerLimit' => $rangeLimits[0],
            'upperLimit' => $rangeLimits[1],
        ];
    }

    /**
     * Returns the priceRange data stored in priceRange.
     *
     * @return int[] array with one price range, consists of the two elements
     *               "upperLimit" and "lowerLimit", will be empty if no price
     *               range or rent data was set
     */
    private function getPriceRange()
    {
        $rentData = $this->processRentFilterFormData();
        $priceRange = $rentData !== '' ? $rentData : $this->filterFormData['priceRange'];

        return $this->getFormattedPriceRange($priceRange);
    }

    /**
     * Formats the values of rentFrom and rentTo, to fit into the
     * price ranges schema and then stores it in the member variable priceRange.
     *
     * @return string the rent values formatted as priceRange, will be empty if
     *                rentTo and rentFrom are empty
     */
    private function processRentFilterFormData()
    {
        $rentFrom = ((int)$this->filterFormData['rentFrom'] === 0) ? '' : (int)$this->filterFormData['rentFrom'];
        $rentTo = ((int)$this->filterFormData['rentTo'] === 0) ? '' : (int)$this->filterFormData['rentTo'];

        return (($rentFrom !== '') || ($rentTo !== '')) ? $rentFrom . '-' . $rentTo : '';
    }

    /**
     * Sets the target URL marker.
     *
     * @return void
     */
    private function setTargetUrlMarker()
    {
        $this->setMarker(
            'target_url',
            htmlspecialchars(GeneralUtility::locationHeaderUrl($this->cObj->typoLink_URL([
                'parameter' => $this->getConfValueInteger(
                    'filterTargetPID',
                    's_searchForm'
                ),
                'useCacheHash' => true,
            ])))
        );
    }

    ////////////////////////////////////////////////////////////////////
    // Functions concerning the hiding or filling of the search fields
    ////////////////////////////////////////////////////////////////////

    /**
     * Fills the input box for zip code or city if there is data for it. Hides
     * the input if it is disabled by configuration.
     *
     * @return void
     */
    private function fillOrHideSiteSearch()
    {
        if ($this->hasSearchField('site')) {
            $this->setMarker(
                'site',
                htmlspecialchars($this->filterFormData['site'])
            );
        } else {
            $this->hideSubparts('wrapper_site_search');
        }
    }

    /**
     * Fills the price range drop-down with the configured ranges if it is
     * enabled in the configuration, hides it otherwise.
     *
     * @return void
     */
    private function fillOrHidePriceRangeDropDown()
    {
        if (!$this->hasSearchField('priceRanges')) {
            $this->hideSubparts('wrapper_price_range_options');
            return;
        }

        $priceRanges = $this->getPriceRangesFromConfiguration();
        $optionTags = '';

        foreach ($priceRanges as $range) {
            $priceRangeString = implode('-', $range);
            $label = $this->getPriceRangeLabel($range);
            $selectedAttribute = (string)$this->filterFormData['priceRange'] === $priceRangeString
                ? ' selected="selected"' : '';

            $optionTags .= '<option value="' . $priceRangeString .
                '" label="' . $label . '" ' . $selectedAttribute . '>' .
                $label . '</option>';
        }
        $this->setMarker('price_range_options', $optionTags);
    }

    /**
     * Fills the input box for the UID search if it is configured to be
     * displayed. Hides the form element if it is disabled by
     * configuration.
     *
     * @return void
     */
    private function fillOrHideUidSearch()
    {
        if (!$this->hasSearchField('uid')) {
            $this->hideSubparts('wrapper_uid_search');
            return;
        }

        $this->setMarker(
            'searched_uid',
            (
            ((int)$this->filterFormData['uid'] === 0)
                ? '' : (int)$this->filterFormData['uid']
            )
        );
    }

    /**
     * Fills the input box for the object number search if it is configured to
     * be displayed. Hides the form element if it is disabled by configuration.
     *
     * @return void
     */
    private function fillOrHideObjectNumberSearch()
    {
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
     *
     * @return void
     */
    private function fillOrHideCitySearch()
    {
        $this->createAndSetDropDown('city');
    }

    /**
     * Shows the district selector if enabled via configuration, otherwise
     * hides it.
     *
     * @return void
     */
    private function fillOrHideDistrictSearch()
    {
        $this->createAndSetDropDown('district');

        $this->setMarker(
            'hide_district_selector',
            $this->hasSearchField('city') ? ' style="display: none;"' : ' style="display: block;"'
        );
    }

    /**
     * Fills a search drop-down from a list of models in the current template.
     *
     * If the drop-down is configured to be hidden, this function hides it in
     * the template.
     *
     * Note that the object mapper must have a matching count function for
     * $type, e.g. for $type = "city", it must have a "countByCity" function.
     *
     * @param string $type
     *        the type of the selector, for example "city", must not be empty
     *
     * @return void
     */
    private function createAndSetDropDown($type)
    {
        if (!$this->hasSearchField($type)) {
            $this->hideSubparts('wrapper_' . $type . '_search');
            return;
        }

        $this->setMarker(
            'options_' . $type . '_search',
            $this->createDropDownItems($type, $this->filterFormData[$type])
        );
    }

    /**
     * Creates the items HTML for a drop down.
     *
     * @param string $type
     *        the type of the selector, for example "city", must not be empty
     * @param int $selectedUid
     *        the UID of the item that should be selected, must be >= 0,
     *        set to 0 to select no item
     *
     * @return string the created HTML, will contain at least an empty option
     */
    public function createDropDownItems($type, $selectedUid = 0)
    {
        if (!in_array($type, ['city', 'district'])) {
            throw new InvalidArgumentException('"' . $type . '" is not a valid type.', 1333036086);
        }

        /** @var tx_realty_Mapper_RealtyObject $objectMapper */
        $objectMapper = Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_RealtyObject::class);
        $countFunction = 'countBy' . ucfirst($type);
        $models = Tx_Oelib_MapperRegistry::get('tx_realty_Mapper_' . ucfirst($type))->findAll('title ASC');

        if ($this->hasConfValueString('staticSqlFilter')) {
            $additionalWhereClause = ' AND ' . $this->getConfValueString('staticSqlFilter');
        } else {
            $additionalWhereClause = '';
        }

        $options = '';
        /** @var tx_realty_Model_AbstractTitledModel $model */
        foreach ($models as $model) {
            $numberOfMatches = $objectMapper->$countFunction($model, $additionalWhereClause);
            if ($numberOfMatches === 0) {
                continue;
            }

            $selected = $selectedUid === $model->getUid() ? ' selected="selected"' : '';

            $options .= '<option value="' . $model->getUid() . '"' .
                $selected . '>' . htmlspecialchars($model->getTitle()) . ' (' .
                $numberOfMatches . ')</option>' . LF;
        }

        return $options;
    }

    /**
     * Shows a drop down menu for selecting house types if enabled via
     * configuration, otherwise hides it.
     *
     * @return void
     */
    private function fillOrHideHouseTypeSearch()
    {
        $this->fillOrHideAuxiliaryRecordSearch('houseType', 'tx_realty_house_types', 'house_type');
    }

    /**
     * Shows or hides a drop-down box of auxiliary records to filter the list
     * for. Whether the box is hidden or shown depends on the configuration.
     *
     * @param string $searchKey
     *        key used in the search from for the auxiliary records to get, must
     *        be an exiting search key corresponding to the provided table name,
     *        must not be empty
     * @param string $tableName
     *        name of the database table of which to use the records for the
     *        drop-down, must not be empty
     * @param string $columnName
     *        column name in the realty records table which corresponds to the
     *        provided table name, must not be empty
     *
     * @return void
     */
    private function fillOrHideAuxiliaryRecordSearch(
        $searchKey,
        $tableName,
        $columnName
    ) {
        if (!$this->hasSearchField($searchKey)) {
            $this->hideSubparts('wrapper_' . $columnName . '_search');
            return;
        }

        $records = Tx_Oelib_Db::selectMultiple(
            $tableName . '.uid, ' . $tableName . '.title',
            'tx_realty_objects' . ',' . $tableName,
            'tx_realty_objects' . '.' . $columnName .
            ' = ' . $tableName . '.uid' .
            Tx_Oelib_Db::enableFields('tx_realty_objects') .
            Tx_Oelib_Db::enableFields($tableName),
            'uid',
            $tableName . '.title'
        );

        $options = '';
        foreach ($records as $record) {
            $options .= '<option value="' . (int)$record['uid'] . '" ' . (
                ((int)$this->filterFormData[$searchKey] === (int)$record['uid']) ? 'selected="selected"' : ''
                ) . '>' . htmlspecialchars($record['title']) . '</option>' . LF;
        }
        $this->setMarker('options_' . $columnName . '_search', $options);
    }

    /**
     * Shows the rent/sale radiobuttons if enabled via configuration, otherwise
     * hides them.
     *
     * @return void
     */
    private function fillOrHideObjectTypeSelect()
    {
        if (!$this->hasSearchField('objectType')) {
            $this->hideSubparts('wrapper_object_type_selector');
            return;
        }

        foreach (['forRent' => 'rent', 'forSale' => 'sale'] as $key => $markerPrefix) {
            $this->setMarker(
                $markerPrefix . '_attributes',
                $this->filterFormData['objectType'] === $key ? ' checked="checked"' : ''
            );
        }
    }

    /**
     * Fills the input box for the given search field if it is configured to be
     * displayed. Hides the form element if it is disabled by configuration.
     *
     * @param string $searchField the name of the search field, to hide or show, must be 'livingArea' or 'rent'
     * @param string $fieldMarkerPart the name of the field name part of the searched marker, must not be empty
     *
     * @return void
     */
    private function fillOrHideFromToSearchField($searchField, $fieldMarkerPart)
    {
        if (!$this->hasSearchField($searchField)) {
            $this->hideSubparts('wrapper_' . $fieldMarkerPart . '_search');
            return;
        }

        foreach (['From', 'To'] as $suffix) {
            $this->setMarker(
                'searched_' . $fieldMarkerPart . '_' . $suffix,
                $this->filterFormData[$searchField . $suffix] ?: ''
            );
        }
    }

    /**
     * Returns an array of configured price ranges.
     *
     * @return int[][] Two-dimensional array of the possible price ranges. Each
     *               inner array consists of two elements with the keys
     *               "lowerLimit" and "upperLimit". Note that the zero element
     *               will always be empty because the first option in the
     *               selectbox remains empty. If no price ranges are configured,
     *               this array will be empty.
     */
    private function getPriceRangesFromConfiguration()
    {
        if (!$this->hasConfValueString('priceRangesForFilterForm', 's_searchForm')) {
            return [];
        }

        // The first element is empty because the first selectbox element should remain empty.
        $priceRanges = [[]];

        $priceRangeConfiguration = GeneralUtility::trimExplode(
            ',',
            $this->getConfValueString('priceRangesForFilterForm', 's_searchForm')
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
     * @param int[] $range
     *        range for which to receive the label, must have the elements "upperLimit" and "lowerLimit",
     *        both must have integers as values, only one of the elements' values may be 0,
     *        for an empty array the result will always be "&nbsp;"
     *
     * @return string formatted label for the price range, will be "&nbsp;"
     *                if an empty array was provided (an empty string
     *                would break the XHTML output's validity)
     */
    private function getPriceRangeLabel(array $range)
    {
        if (empty($range)) {
            return '&nbsp;';
        }

        $currency = $this->getConfValueString('currencyUnit');

        /** @var Tx_Oelib_ViewHelper_Price $priceViewHelper */
        $priceViewHelper = GeneralUtility::makeInstance(Tx_Oelib_ViewHelper_Price::class);
        $priceViewHelper->setCurrencyFromIsoAlpha3Code($currency);

        if ((int)$range['lowerLimit'] === 0) {
            $priceViewHelper->setValue($range['upperLimit']);
            $result = $this->translate('label_less_than') . ' ' . $priceViewHelper->render();
        } elseif ((int)$range['upperLimit'] === 0) {
            $priceViewHelper->setValue($range['lowerLimit']);
            $result = $this->translate('label_greater_than') . ' ' . $priceViewHelper->render();
        } else {
            $priceViewHelper->setValue($range['lowerLimit']);
            $result = $priceViewHelper->render() . ' ' . $this->translate('label_to') . ' ';
            $priceViewHelper->setValue($range['upperLimit']);
            $result .= $priceViewHelper->render();
        }

        return htmlentities($result, ENT_QUOTES, 'utf-8');
    }

    //////////////////////////////////////////////////////////////////////////////
    // Functions concerning the building of the WHERE clauses for the list view.
    //////////////////////////////////////////////////////////////////////////////

    /**
     * Returns a WHERE clause part for one price range.
     *
     * @return string WHERE clause part for the price range, will be build from
     *                      "rentTo" and "rentFrom" fields if they are empty it
     *                      will be build from "priceRange" field, if all three
     *                      fields are empty an empty string will be returned
     */
    private function getRentOrPriceRangeWhereClausePart()
    {
        $priceRange = $this->getPriceRange();
        if (empty($priceRange)) {
            return '';
        }

        if ($priceRange['lowerLimit'] === 0) {
            // Zero as lower limit must be excluded of the range because each
            // non-set price will be identified as zero. Many objects either
            // have a buying price or a rent which would make searching for
            // zero-prices futile.
            $equalSign = '';
            // Additionally to the objects that have at least one non-zero price
            // inferior to the lower lower limit, objects which have no price at
            // all need to be found.
            $whereClauseForObjectsForFree = ' OR (' . 'tx_realty_objects' .
                '.rent_excluding_bills = 0 AND ' . 'tx_realty_objects' .
                '.buying_price = 0)';
        } else {
            $equalSign = '=';
            $whereClauseForObjectsForFree = '';
        }
        // The WHERE clause part for the lower limit is always set, even if no
        // lower limit was provided. The lower limit will just be zero then.
        $lowerLimitRent = 'tx_realty_objects' . '.rent_excluding_bills ' .
            '>' . $equalSign . ' ' . $priceRange['lowerLimit'];
        $lowerLimitBuy = 'tx_realty_objects' . '.buying_price ' .
            '>' . $equalSign . ' ' . $priceRange['lowerLimit'];

        // The upper limit will be zero if no upper limit was provided. So zero
        // means infinite here.
        if ($priceRange['upperLimit'] !== 0) {
            $upperLimitRent = ' AND ' . 'tx_realty_objects' . '.rent_excluding_bills <= ' . $priceRange['upperLimit'];
            $upperLimitBuy = ' AND ' . 'tx_realty_objects' . '.buying_price <= ' . $priceRange['upperLimit'];
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
    private function getSiteWhereClausePart()
    {
        if ($this->filterFormData['site'] === '') {
            return '';
        }

        $databaseConnection = Tx_Oelib_Db::getDatabaseConnection();

        // only the first two characters are used for a zip code search
        $zipSearchString = $databaseConnection->quoteStr(
            $databaseConnection->escapeStrForLike(
                substr($this->filterFormData['site'], 0, 2),
                'tx_realty_objects'
            ),
            'tx_realty_objects'
        );
        $citySearchString = $databaseConnection->quoteStr(
            $databaseConnection->escapeStrForLike(
                $this->filterFormData['site'],
                'tx_realty_cities'
            ),
            'tx_realty_cities'
        );

        return ' AND (' . 'tx_realty_objects' . '.zip LIKE "' .
            $zipSearchString . '%" OR ' . 'tx_realty_cities' .
            '.title LIKE "%' . $citySearchString . '%")';
    }

    /**
     * Returns the WHERE clause part for the object number.
     *
     * @return string WHERE clause part beginning with " AND", will be empty if
     *                no filter form data was provided for the object number
     */
    private function getObjectNumberWhereClausePart()
    {
        if ($this->filterFormData['objectNumber'] === '') {
            return '';
        }

        return ' AND tx_realty_objects.object_number="' .
            Tx_Oelib_Db::getDatabaseConnection()->quoteStr($this->filterFormData['objectNumber'], 'tx_realty_objects') .
            '"';
    }

    /**
     * Returns the WHERE clause part for the UID.
     *
     * @return string WHERE clause part beginning with " AND", will be empty if
     *                no filter form data was provided for the UID
     */
    private function getUidWhereClausePart()
    {
        if ($this->filterFormData['uid'] === 0) {
            return '';
        }

        return ' AND ' . 'tx_realty_objects' . '.uid=' . $this->filterFormData['uid'];
    }

    /**
     * Returns the WHERE clause part for the objectType selector.
     *
     * @return string WHERE clause part beginning with " AND", will be empty if
     *                no filter form data was provided for the objectType
     *                selector
     */
    private function getObjectTypeWhereClausePart()
    {
        if ($this->filterFormData['objectType'] === '') {
            return '';
        }

        $objectType = $this->filterFormData['objectType'] === 'forRent'
            ? \tx_realty_Model_RealtyObject::TYPE_FOR_RENT
            : \tx_realty_Model_RealtyObject::TYPE_FOR_SALE;

        return ' AND ' . 'tx_realty_objects' . '.object_type = ' . $objectType;
    }

    /**
     * Returns the WHERE clause part for the city selection.
     *
     * @return string WHERE clause part beginning with " AND", will be empty if
     *                no filter form data was provided for the city
     *                selector
     */
    private function getCityWhereClausePart()
    {
        if ($this->filterFormData['city'] === 0) {
            return '';
        }

        return ' AND ' . 'tx_realty_objects' . '.city = ' . $this->filterFormData['city'];
    }

    /**
     * Returns the WHERE clause part for the district selection.
     *
     * @return string WHERE clause part beginning with " AND", will be empty if
     *                no filter form data was provided for the city
     *                selector
     */
    private function getDistrictWhereClausePart()
    {
        if ($this->filterFormData['district'] === 0) {
            return '';
        }

        return ' AND ' . 'tx_realty_objects' . '.district = ' . $this->filterFormData['district'];
    }

    /**
     * Returns the WHERE clause part for the house type selection.
     *
     * @return string WHERE clause part beginning with " AND", will be empty if
     *                no filter form data was provided for the house type
     *                selector
     */
    private function getHouseTypeWhereClausePart()
    {
        if ($this->filterFormData['houseType'] === 0) {
            return '';
        }

        return ' AND ' . 'tx_realty_objects' . '.house_type = ' . $this->filterFormData['houseType'];
    }

    /**
     * Returns the WHERE clause part for the living area search fields.
     *
     * @return string WHERE clause part beginning with " AND", will be empty if
     *                no filter form data was provided for the living area
     *                search fields
     */
    private function getLivingAreaWhereClausePart()
    {
        return (
            $this->filterFormData['livingAreaFrom'] !== 0
                ? ' AND (' . 'tx_realty_objects' . '.living_area >= ' . $this->filterFormData['livingAreaFrom'] . ')'
                : ''
            ) . (
            $this->filterFormData['livingAreaTo'] !== 0
                ? ' AND (' . 'tx_realty_objects' . '.living_area <= ' . $this->filterFormData['livingAreaTo'] . ')'
                : ''
            );
    }

    /**
     * Checks whether a given search field ID is set in displayedSearchFields
     *
     * @param string $fieldToCheck the search field name to check, must not be empty
     *
     * @return bool true if the given field should be displayed as set per configuration, false otherwise
     */
    private function hasSearchField($fieldToCheck)
    {
        return in_array($fieldToCheck, $this->displayedSearchFields, true);
    }

    /**
     * Returns the WHERE clause part for the number of rooms search fields.
     *
     * @return string WHERE clause part beginning with " AND", will be empty if
     *                no filter form data was provided for the number of rooms
     *                search fields
     */
    private function getNumberOfRoomsWhereClausePart()
    {
        $result = '';

        $roomsFromWithDots = (float)$this->replaceCommasWithDots($this->filterFormData['numberOfRoomsFrom']);
        if ($roomsFromWithDots > 0.0) {
            $result .= ' AND (' . 'tx_realty_objects' . '.number_of_rooms >= ' . $roomsFromWithDots . ')';
        }

        $roomsToWithDots = (float)$this->replaceCommasWithDots($this->filterFormData['numberOfRoomsTo']);
        if ($roomsToWithDots > 0.0) {
            $result .= ' AND (' . 'tx_realty_objects' . '.number_of_rooms <= ' . $roomsToWithDots . ')';
        }

        return $result;
    }

    /**
     * Replaces every comma in a given string with a dot.
     *
     * @param string $rawValue the string with commas, may be empty
     *
     * @return string the string, with every comma replaced by a dot, will be
     *                empty if the input string was empty.
     */
    private function replaceCommasWithDots($rawValue)
    {
        return str_replace(',', '.', $rawValue);
    }

    /**
     * Returns the allowed filter form piVar keys.
     *
     * @return string[] the allowed filter form piVar keys, will not be empty
     */
    public static function getPiVarKeys()
    {
        return [
            'uid',
            'objectNumber',
            'site',
            'city',
            'district',
            'houseType',
            'priceRange',
            'rentFrom',
            'rentTo',
            'livingAreaFrom',
            'livingAreaTo',
            'objectType',
            'numberOfRoomsFrom',
            'numberOfRoomsTo',
        ];
    }
}
