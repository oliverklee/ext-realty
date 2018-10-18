<?php

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This class converts DOMDocuments of OpenImmo data to arrays which have the
 * columns of the database table "tx_realty_objects" as keys.
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 * @author Benjamin Schulte <benj@minschulte.de>
 */
class tx_realty_domDocumentConverter
{
    /**
     * Associates database column names with their correspondings used in
     * OpenImmo records.
     * In OpenImmo, nested tags are used to describe values. The beginning of
     * these tags is the same for all columns in this array:
     * '<openimmo><anbieter><immobilie>...</immobilie></anbieter></openimmo>'.
     * The last tags, each property's name and the category where to find this
     * name differ.
     * The database column names are the keys of the outer array, their values
     * are arrays which have the category as key and the property as value.
     *
     * @var array[]
     */
    protected $propertyArray = [
        // OpenImmo tag for 'starttime' could possibly be 'beginn_bietzeit'.
        'starttime' => ['bieterverfahren' => 'beginn_angebotsphase'],
        'endtime' => ['bieterverfahren' => 'ende_bietzeit'],
        'object_number' => ['verwaltung_techn' => 'objektnr_extern'],
        'title' => ['freitexte' => 'objekttitel'],
        'street' => ['geo' => 'strasse'],
        'zip' => ['geo' => 'plz'],
        'city' => ['geo' => 'ort'],
        'district' => ['geo' => 'regionaler_zusatz'],
        'show_address' => ['verwaltung_objekt' => 'objektadresse_freigeben'],
        'living_area' => ['flaechen' => 'wohnflaeche'],
        'total_usable_area' => ['flaechen' => 'nutzflaeche'],
        'total_area' => ['flaechen' => 'gesamtflaeche'],
        'shop_area' => ['flaechen' => 'ladenflaeche'],
        'storage_area' => ['flaechen' => 'lagerflaeche'],
        'office_space' => ['flaechen' => 'bueroflaeche'],
        'floor_space_index' => ['flaechen' => 'grz'],
        'site_occupancy_index' => ['flaechen' => 'gfz'],
        'estate_size' => ['flaechen' => 'grundstuecksflaeche'],
        'number_of_rooms' => ['flaechen' => 'anzahl_zimmer'],
        'bedrooms' => ['flaechen' => 'anzahl_schlafzimmer'],
        'bathrooms' => ['flaechen' => 'anzahl_badezimmer'],
        'parking_spaces' => ['flaechen' => 'anzahl_stellplaetze'],
        'buying_price' => ['preise' => 'kaufpreis'],
        'extra_charges' => ['preise' => 'nebenkosten'],
        'heating_included' => ['preise' => 'heizkosten_enthalten'],
        'hoa_fee' => ['preise' => 'hausgeld'],
        'rent_per_square_meter' => ['preise' => 'mietpreis_pro_qm'],
        'provision' => ['preise' => 'aussen_courtage'],
        'year_rent' => ['preise' => 'mieteinnahmen_ist'],

        // OpenImmo tag for 'usable_from' could possibly be 'abdatum'.
        'usable_from' => ['verwaltung_objekt' => 'verfuegbar_ab'],
        'floor' => ['geo' => 'etage'],
        'floors' => ['geo' => 'anzahl_etagen'],
        'pets' => ['verwaltung_objekt' => 'haustiere'],
        'construction_year' => ['zustand_angaben' => 'baujahr'],
        'garden' => ['ausstattung' => 'gartennutzung'],
        'barrier_free' => ['ausstattung' => 'barrierefrei'],
        'wheelchair_accessible' => ['ausstattung' => 'rollstuhlgerecht'],
        'ramp' => ['ausstattung' => 'rampe'],
        'lifting_platform' => ['ausstattung' => 'hebebuehne'],
        'suitable_for_the_elderly' => ['ausstattung' => 'seniorengerecht'],
        'description' => ['freitexte' => 'objektbeschreibung'],
        'equipment' => ['freitexte' => 'ausstatt_beschr'],
        'location' => ['freitexte' => 'lage'],
        'misc' => ['freitexte' => 'sonstige_angaben'],
        'openimmo_obid' => ['verwaltung_techn' => 'openimmo_obid'],
        'contact_person' => ['kontaktperson' => 'name'],
        'contact_person_first_name' => ['kontaktperson' => 'vorname'],
        'contact_person_salutation' => ['kontaktperson' => 'anrede'],
        'contact_email' => ['kontaktperson' => 'email_zentrale'],
        'phone_switchboard' => ['kontaktperson' => 'tel_zentrale'],
        'phone_direct_extension' => ['kontaktperson' => 'tel_durchw'],
        'with_hot_water' => ['energiepass' => 'mitwarmwasser'],
        'energy_certificate_valid_until' => ['energiepass' => 'gueltig_bis'],
        'energy_consumption_characteristic' => ['energiepass' => 'energieverbrauchkennwert'],
        'ultimate_energy_demand' => ['energiepass' => 'endenergiebedarf'],
        'primary_energy_carrier' => ['energiepass' => 'primaerenergietraeger'],
        'electric_power_consumption_characteristic' => ['energiepass' => 'stromwert'],
        'heat_energy_consumption_characteristic' => ['energiepass' => 'waermewert'],
        'value_category' => ['energiepass' => 'wertklasse'],
        'year_of_construction' => ['energiepass' => 'baujahr'],
        'energy_certificate_text' => ['energiepass' => 'epasstext'],
        'heat_energy_requirement_value' => ['energiepass' => 'hwbwert'],
        'heat_energy_requirement_class' => ['energiepass' => 'hwbklasse'],
        'total_energy_efficiency_value' => ['energiepass' => 'fgeewert'],
        'total_energy_efficiency_class' => ['energiepass' => 'fgeeklasse'],
    ];

    /**
     * fields that should be imported as decimals
     *
     * @var string[][]
     */
    private static $decimalFields = [
        'sales_area' => ['flaechen' => 'verkaufsflaeche'],
        'other_area' => ['flaechen' => 'sonstflaeche'],
        'window_bank' => ['flaechen' => 'fensterfront'],
        'rental_income_target' => ['preise' => 'mieteinnahmen_soll'],
        'rent_with_heating_costs' => ['preise' => 'warmmiete'],
    ];

    /**
     * the keys of the fields that are of boolean type
     *
     * @var string[]
     */
    private static $booleanFields = [
        'show_address',
        'heating_included',
        'garden',
        'barrier_free',
        'wheelchair_accessible',
        'ramp',
        'lifting_platform',
        'suitable_for_the_elderly',
        'elevator',
        'has_air_conditioning',
        'assisted_living',
        'fitted_kitchen',
        'has_pool',
        'has_community_pool',
        'with_hot_water',
    ];

    /**
     * the keys of the fields that are of rich text type
     *
     * @var string[]
     */
    private static $richTextFields = ['description', 'equipment', 'location', 'misc'];

    /**
     * raw data of an OpenImmo record
     *
     * @var DOMXPath
     */
    private $rawRealtyData = null;

    /**
     * data which is the same for all realty records of one DOMDocument
     *
     * @var string[]
     */
    private $universalRealtyData = [];

    /**
     * imported data of a realty record
     *
     * @var array
     */
    private $importedData = [];

    /**
     * Number of the current record. Sometimes there are several realties in one
     * OpenImmo record.
     *
     * @var int
     */
    private $recordNumber = 0;

    /**
     * @var int[]
     */
    private static $cachedCountries = [];

    /**
     * the mapper that creates unique file names for images and documents
     *
     * @var tx_realty_fileNameMapper
     */
    private $fileNameMapper = null;

    /**
     * @var tx_realty_translator
     */
    private static $translator = null;

    /**
     * Constructor.
     *
     * @param tx_realty_fileNameMapper $fileNameMapper
     *        mapper to receive unique file names for the images and documents
     */
    public function __construct(tx_realty_fileNameMapper $fileNameMapper)
    {
        $this->fileNameMapper = $fileNameMapper;
    }

    /**
     * Handles the conversion of a DOMDocument and returns the realty records
     * found in the DOMDocument as values of an array. Each of this values is an
     * array with column names like in the database table 'tx_realty_objects' as
     * keys and their corresponding values fetched from the DOMDocument.
     *
     * As images need to be inserted to a separate database table, all image
     * data is stored in an inner array in the element 'images' of each record.
     *
     * All document data is stored in an inner array in the element 'documents'
     * of each record.
     *
     * The returned array is empty if the given DOMDocument could not be
     * converted.
     *
     * @param DOMDocument $domDocument data to convert, must not be NULL
     *
     * @return array[] data of each realty record, will be empty if the passed DOMDocument could not be converted
     */
    public function getConvertedData(DOMDocument $domDocument)
    {
        $this->setRawRealtyData($domDocument);
        if (!$this->hasValidRootNode()) {
            return [];
        }

        $result = [];

        $this->fetchUniversalData();
        $numberOfRecords = $this->getNumberOfRecords();

        for (
            $this->recordNumber = 0;
            $this->recordNumber < $numberOfRecords;
            $this->recordNumber++
        ) {
            $realtyRecordArray = $this->getRealtyArray();
            $this->addUniversalData($realtyRecordArray);
            $result[] = $realtyRecordArray;

            $this->resetImportedData();
        }

        return $result;
    }

    /**
     * Loads the raw data from a DOMDocument.
     *
     * @param DOMDocument $rawRealtyData raw data to load, must not be NULL
     *
     * @return void
     */
    protected function setRawRealtyData(DOMDocument $rawRealtyData)
    {
        $this->rawRealtyData = new DOMXPath($rawRealtyData);
    }

    /**
     * Resets the imported data.
     *
     * @return void
     */
    private function resetImportedData()
    {
        $this->importedData = [];
    }

    /**
     * Appends realty data found in $this->universalRealtyData to the given
     * array. This data is the same for all realty records in one DOMDocument
     * and is fetched by $this->fetchUniversalData().
     *
     * @param array &$realtyDataArray realty data, may be empty
     *
     * @return void
     */
    private function addUniversalData(array &$realtyDataArray)
    {
        $realtyDataArray = array_replace($realtyDataArray, $this->universalRealtyData);
    }

    /**
     * Fetches data which is the same for the whole set of realty records in
     * the current OpenImmo record and stores it to $this->universalRealtyData.
     *
     * @return void
     */
    private function fetchUniversalData()
    {
        $this->universalRealtyData = $this->fetchEmployerAndAnid();
    }

    /**
     * Fetches 'employer' and 'openimmo_anid' from an OpenImmo record and
     * returns them in an array. These nodes must only occur once in an OpenImmo
     * record.
     *
     * @return string[] contains the elements 'employer' and 'openimmo_anid', will be empty if the nodes were not found
     */
    private function fetchEmployerAndAnid()
    {
        $result = [];

        $columnNames = [
            'firma' => 'employer',
            'openimmo_anid' => 'openimmo_anid',
        ];
        foreach ($columnNames as $grandchild => $columnName) {
            $nodeList = $this->getNodeListFromRawData('anbieter', $grandchild);
            $this->addElementToArray(
                $result,
                $columnName,
                $nodeList->item(0)->nodeValue
            );
        }

        return $result;
    }

    /**
     * Substitutes XML namespaces from a node name and returns the name.
     *
     * @param DOMNode|null $domNode node, may be NULL
     *
     * @return string node name without namespaces, may be empty
     */
    protected function getNodeName(DOMNode $domNode = null)
    {
        if ($domNode === null) {
            return '';
        }

        return preg_replace('/(.*)\\:/', '', $domNode->nodeName);
    }

    /**
     * Gets the number of realties included in the current OpenImmo record.
     *
     * @return int number of realties in the current OpenImmo record, 0
     *                 if no realty data was found
     */
    private function getNumberOfRecords()
    {
        $nodeList = $this->getListedRealtyObjects();

        if ($nodeList !== null) {
            $result = $nodeList->length;
        } else {
            $result = 0;
        }

        return $result;
    }

    /**
     * Converts the realty data to an array. The array values are fetched from
     * the DOMDocument to convert. The keys are the same as the column names of
     * the database table 'tx_realty_objects'. The result is an empty array if
     * the given data is of an invalid format.
     *
     * @return array data of the realty object, empty if the DOMNode is not convertible
     */
    private function getRealtyArray()
    {
        $this->fetchNodeValues();
        $this->fetchImages();
        $this->fetchDocuments();
        $this->fetchEquipmentAttributes();
        $this->fetchCategoryAttributes();
        $this->fetchState();
        $this->fetchStatus();
        $this->fetchFlooring();
        $this->fetchFurnishingCategory();
        $this->fetchValueForOldOrNewBuilding();
        $this->fetchAction();
        $this->fetchHeatingType();
        $this->fetchParkingSpaceType();
        $this->fetchGaragePrice();
        $this->fetchCurrency();
        $this->fetchLanguage();
        $this->fetchGeoCoordinates();
        $this->fetchCountry();
        $this->fetchRent();
        $this->fetchEnergyCertificateIssueDate();
        $this->fetchEnergyCertificateType();
        $this->fetchEnergyCertificateYear();
        $this->fetchBuildingType();
        $this->fetchDeposit();
        $this->fetchBalcony();

        $this->replaceImportedBooleanLikeStrings();
        $this->substituteSurplusDecimals();

        return $this->importedData;
    }

    /**
     * Replaces the strings 'TRUE' and 'FALSE' of the currently imported data
     * with ints. This replacement is not case sensitive.
     *
     * @return void
     */
    private function replaceImportedBooleanLikeStrings()
    {
        foreach (self::$booleanFields as $key) {
            $value = $this->importedData[$key];
            if ($this->isBooleanLikeStringTrue($value)) {
                $this->importedData[$key] = 1;
            } elseif ($this->isBooleanLikeStringFalse($value)) {
                $this->importedData[$key] = 0;
            }
        }
    }

    /**
     * Checks whether a string evaluates to TRUE.
     *
     * @param string $booleanLikeString
     *        case-insensitive string to evaluate, may be surrounded by quotes
     *
     * @return bool
     *         TRUE if $booleanLikeString evaluates to TRUE, FALSE otherwise
     */
    private function isBooleanLikeStringTrue($booleanLikeString)
    {
        $trimmedString = strtolower(trim($booleanLikeString, '"'));

        return ($trimmedString === 'true') || ($trimmedString === '1');
    }

    /**
     * Checks whether a string evaluates to FALSE.
     *
     * @param string $booleanLikeString
     *        case-insensitive string to evaluate, may be surrounded by quotes
     *
     * @return bool
     *         TRUE if $booleanLikeString evaluates to FALSE, FALSE otherwise
     */
    private function isBooleanLikeStringFalse($booleanLikeString)
    {
        $trimmedString = strtolower(trim($booleanLikeString, '"'));

        return ($trimmedString === 'false') || ($trimmedString === '0');
    }

    /**
     * Substitutes decimals from the currently imported data if they are zero.
     *
     * Handles the "zip" column as a special case since here leading zeros are
     * allowed. So the ZIP will not be cast to an int.
     *
     * @return void
     */
    private function substituteSurplusDecimals()
    {
        foreach ($this->importedData as $key => &$value) {
            $intValue = (int)$value;
            if (is_numeric($value) && (string)$intValue === $value && ($key !== 'zip')) {
                $value = $intValue;
            }
        }
    }

    /**
     * Fetches node values and stores them with their corresponding database
     * column names as keys in $this->importedData.
     *
     * @return void
     */
    private function fetchNodeValues()
    {
        foreach ($this->propertyArray as $key => $path) {
            $currentDomNode = $this->findFirstGrandchild(key($path), implode('', $path));
            $this->addImportedData($key, $currentDomNode->nodeValue);
        }
        foreach (self::$decimalFields as $key => $path) {
            $currentDomNode = $this->findFirstGrandchild(key($path), implode('', $path));
            $value = ($currentDomNode !== null) ? (float)$currentDomNode->nodeValue : 0.0;
            $this->addImportedData($key, $value);
        }

        $this->appendStreetNumber();
        $this->setTitleForPets();
        $this->trySecondContactEmailIfEmailNotFound();
    }

    /**
     * Fetches 'hausnummer' and appends it to the string in the array element
     * 'street' of $this->importedData. If 'street' is empty or does not exist,
     * nothing is changed at all.
     *
     * @return void
     */
    private function appendStreetNumber()
    {
        if (!$this->importedData['street'] || ((string)$this->importedData['street'] === '')) {
            return;
        }

        $streetNumberNode = $this->findFirstGrandchild('geo', 'hausnummer');
        if ($streetNumberNode) {
            $this->addImportedData('street', $this->importedData['street'] . ' ' . $streetNumberNode->nodeValue);
        }
    }

    /**
     * Replaces the value for 'pets' with a describtive string.
     * 'pets' is boolean in OpenImmo records. But in the database the value for
     * 'pets' is inserted to a separate table and displayed with this value as
     * title in the FE.
     *
     * @return void
     */
    private function setTitleForPets()
    {
        if (!isset($this->importedData['pets'])) {
            return;
        }

        $petsValue = strtolower((string)$this->importedData['pets']);
        if (($petsValue === 1) || $this->isBooleanLikeStringTrue($petsValue)) {
            $this->importedData['pets'] = $this->getTranslator()->translate('label_allowed');
        } else {
            $this->importedData['pets'] = $this->getTranslator()->translate('label_not_allowed');
        }
    }

    /**
     * Gets a cached translator object (and creates it first, if necessary).
     *
     * @return tx_realty_translator the cached translator object
     */
    private function getTranslator()
    {
        if (!self::$translator) {
            self::$translator = GeneralUtility::makeInstance(\tx_realty_translator::class);
        }

        return self::$translator;
    }

    /**
     * Fetches the contact e-mail from the tag 'email_direct' if the e-mail
     * address has not been imported yet.
     *
     * @return void
     */
    private function trySecondContactEmailIfEmailNotFound()
    {
        if (isset($this->importedData['contact_email'])) {
            return;
        }

        $contactEmailNode = $this->findFirstGrandchild('kontaktperson', 'email_direkt');
        $this->addImportedDataIfValueIsNonEmpty('contact_email', $contactEmailNode->nodeValue);
    }

    /**
     * Fetches information about images from $openImmoNode of an OpenImmo record
     * and stores them as an inner array in $this->importedData.
     *
     * @return void
     */
    private function fetchImages()
    {
        $this->addImportedDataIfValueIsNonEmpty('images', $this->createRecordsForImages());
    }

    /**
     * Creates an array of image records for one realty record.
     *
     * @return array[] image records, will be empty if there are none
     */
    protected function createRecordsForImages()
    {
        $imageExtensions = GeneralUtility::trimExplode(',', $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'], true);
        if (in_array('pdf', $imageExtensions, true)) {
            unset($imageExtensions[(int)array_search('pdf', $imageExtensions, true)]);
        }
        if (in_array('ps', $imageExtensions, true)) {
            unset($imageExtensions[(int)array_search('ps', $imageExtensions, true)]);
        }
        $extensionValidator = '/^.+\\.(' . implode('|', $imageExtensions) . ')$/i';

        $listedRealtyObjects = $this->getListedRealtyObjects();
        if ($listedRealtyObjects === null) {
            return [];
        }

        $attachments = $this->getNodeListFromRawData('anhang', '', $listedRealtyObjects->item($this->recordNumber));

        $images = [];
        /** @var DOMNode $contextNode */
        foreach ($attachments as $contextNode) {
            $titleNodeList = $this->getNodeListFromRawData('anhangtitel', '', $contextNode);

            $title = '';
            if ($titleNodeList->item(0)) {
                $title = $titleNodeList->item(0)->nodeValue;
            }

            $fileNameNodeList = $this->getNodeListFromRawData('daten', 'pfad', $contextNode);

            if ($fileNameNodeList->item(0)) {
                $rawFileName = $fileNameNodeList->item(0)->nodeValue;

                if (preg_match($extensionValidator, $rawFileName)) {
                    $fileName = $this->fileNameMapper->getUniqueFileNameAndMapIt(basename($rawFileName));

                    $images[] = ['caption' => $title, 'image' => $fileName];
                }
            }
        }

        return $images;
    }

    /**
     * Fetches information about documents from $openImmoNode of an OpenImmo
     * record and stores them as an inner array in $this->importedData.
     *
     * @return void
     */
    private function fetchDocuments()
    {
        $this->addImportedDataIfValueIsNonEmpty('documents', $this->importDocuments());
    }

    /**
     * Creates an array of document records for one realty record.
     *
     * @return array[] document records, will be empty if there are none
     */
    protected function importDocuments()
    {
        $listedRealtyObjects = $this->getListedRealtyObjects();
        if ($listedRealtyObjects === null) {
            return [];
        }

        $attachments = $this->getNodeListFromRawData('anhang', '', $listedRealtyObjects->item($this->recordNumber));

        $documents = [];
        /** @var DOMNode $contextNode */
        foreach ($attachments as $contextNode) {
            $titleNodeList = $this->getNodeListFromRawData('anhangtitel', '', $contextNode);
            $fileNameNodeList = $this->getNodeListFromRawData('daten', 'pfad', $contextNode);

            if (!$titleNodeList->item(0) || !$fileNameNodeList->item(0)) {
                continue;
            }

            $title = $titleNodeList->item(0)->nodeValue;
            $rawFileName = $fileNameNodeList->item(0)->nodeValue;

            if (($title !== '') && preg_match('/\\.pdf$/i', $rawFileName)) {
                $fileName = $this->fileNameMapper->getUniqueFileNameAndMapIt(basename($rawFileName));

                $documents[] = ['title' => $title, 'filename' => $fileName];
            }
        }

        return $documents;
    }

    /**
     * Fetches attributes about equipment and stores them with their
     * corresponding database column names as keys in $this->importedData.
     *
     * @return void
     */
    private function fetchEquipmentAttributes()
    {
        $rawAttributes = [];

        foreach (['serviceleistungen', 'fahrstuhl', 'kueche'] as $grandchildName) {
            $nodeWithAttributes = $this->findFirstGrandchild('ausstattung', $grandchildName);
            $rawAttributes[$grandchildName] = $this->fetchLowercasedTruthyDomAttributes($nodeWithAttributes);
        }

        foreach (
            [
                'assisted_living' => $rawAttributes['serviceleistungen']['betreutes_wohnen'],
                'fitted_kitchen' => $rawAttributes['kueche']['ebk'],
                // For realty records, the type of elevator is not relevant.
                'elevator' => $rawAttributes['fahrstuhl']['lasten'],
            ] as $key => $value
        ) {
            if ($value !== null) {
                $this->addImportedDataIfValueIsNonEmpty($key, $value);
            }
        }
        if (isset($rawAttributes['fahrstuhl']['personen'])) {
            $this->addImportedDataIfValueIsNonEmpty('elevator', $rawAttributes['fahrstuhl']['personen']);
        }
    }

    /**
     * Fetches attributes about 'objektkategorie' and stores them with their
     * corresponding database column names as keys in $this->importedData.
     *
     * @return void
     */
    private function fetchCategoryAttributes()
    {
        $this->fetchHouseType();

        $nodeWithAttributes = $this->findFirstGrandchild('objektkategorie', 'vermarktungsart');

        $objectTypeAttributes = $this->fetchLowercasedDomAttributes($nodeWithAttributes);

        // It must be ensured that the key 'object_type' is set as soon as there
        // are attributes provided, because 'object_type' is a required field.
        if (!empty($objectTypeAttributes)) {
            if (isset($objectTypeAttributes['kauf']) && $this->isBooleanLikeStringTrue($objectTypeAttributes['kauf'])) {
                $this->addImportedData('object_type', tx_realty_Model_RealtyObject::TYPE_FOR_SALE);
            } else {
                $this->addImportedData('object_type', tx_realty_Model_RealtyObject::TYPE_FOR_RENT);
            }
        }

        $utilizationAttributes = [];
        $nodes = $this->findFirstGrandchild('objektkategorie', 'nutzungsart');
        $domAttributes = $this->fetchDomAttributes($nodes);
        foreach ($domAttributes as $key => $value) {
            if ($this->isBooleanLikeStringTrue($value)) {
                $utilizationAttributes[] = $key;
            }
        }
        if (!empty($utilizationAttributes)) {
            $this->addImportedData('utilization', $this->getFormattedString($utilizationAttributes));
        }
    }

    /**
     * Fetches the 'objektart' and stores it with the corresponding database
     * column name 'house_type' as key in $this->importedData.
     *
     * @return void
     */
    private function fetchHouseType()
    {
        $nodeContainingAttributeNode = $this->findFirstGrandchild('objektkategorie', 'objektart');
        if (!$nodeContainingAttributeNode) {
            return;
        }

        $nodeWithAttributes = $this->rawRealtyData->query(
            './/*[not(starts-with(local-name(), "#"))]',
            $nodeContainingAttributeNode
        );

        $value = $this->getNodeName($nodeWithAttributes->item(0));

        if ($value !== '') {
            $attributes = $this->fetchDomAttributes($nodeWithAttributes);

            if (!empty($attributes)) {
                $value .= ': ' . $this->getFormattedString(array_values($attributes));
            }

            $this->addImportedData('house_type', $this->getFormattedString([$value]));
        }
    }

    /**
     * Fetches the 'heizungsart' and stores it with the corresponding database
     * column name 'heating_type' as key in $this->importedData.
     *
     * @return void
     */
    private function fetchHeatingType()
    {
        $heatingTypeNode = $this->findFirstGrandchild('ausstattung', 'heizungsart');
        $firingTypeNode = $this->findFirstGrandchild('ausstattung', 'befeuerung');
        $attributes = array_merge(
            array_keys($this->fetchLowercasedTruthyDomAttributes($heatingTypeNode)),
            array_keys($this->fetchLowercasedTruthyDomAttributes($firingTypeNode))
        );

        // The fetched heating types are always German. In the database they
        // are stored as a sorted list of keys which refer to localized strings.
        $keys = array_keys(array_intersect(
            [
                1 => 'fern',
                2 => 'zentral',
                3 => 'elektro',
                4 => 'fussboden',
                5 => 'gas',
                6 => 'alternativ',
                7 => 'erdwaerme',
                8 => 'oel',
                9 => 'etage',
                10 => 'solar',
                11 => 'ofen',
                12 => 'block',
                13 => 'luftwp',
                14 => 'wasser-elektro',
                15 => 'pellet',
                16 => 'kohle',
                17 => 'holz',
                18 => 'fluessiggas',
            ],
            $attributes
        ));
        $this->addImportedDataIfValueIsNonEmpty('heating_type', implode(',', $keys));
    }

    /**
     * Fetches the 'stellplatzart' and stores it with the corresponding database
     * column name 'garage_type' as key in $this->importedData.
     *
     * @return void
     */
    private function fetchParkingSpaceType()
    {
        $nodeWithAttributes = $this->findFirstGrandchild('ausstattung', 'stellplatzart');
        $attributes = $this->fetchLowercasedTruthyDomAttributes($nodeWithAttributes);

        $this->addImportedDataIfValueIsNonEmpty('garage_type', $this->getFormattedString(array_keys($attributes)));
    }

    /**
     * Fetches the attribute for 'stellplatzmiete' and 'stellplatzkaufpreis' and
     * stores them with the corresponding database column name as key in
     * $this->importedData.
     *
     * @return void
     */
    private function fetchGaragePrice()
    {
        $nodeWithAttributes = $this->findFirstGrandchild(
            'preise',
            // 'stp_*' exists for each defined type of 'stellplatz'
            'stp_garage'
        );
        $attributes = $this->fetchLowercasedDomAttributes($nodeWithAttributes);

        if (isset($attributes['stellplatzmiete'])) {
            $this->addImportedDataIfValueIsNonEmpty('garage_rent', $attributes['stellplatzmiete']);
        }
        if (isset($attributes['stellplatzkaufpreis'])) {
            $this->addImportedDataIfValueIsNonEmpty('garage_price', $attributes['stellplatzkaufpreis']);
        }
    }

    /**
     * Fetches the attribute 'currency' and stores it in $this->importedData.
     *
     * @return void
     */
    private function fetchCurrency()
    {
        $nodeWithAttributes = $this->findFirstGrandchild('preise', 'waehrung');
        $attributes = $this->fetchLowercasedDomAttributes($nodeWithAttributes);

        if (isset($attributes['iso_waehrung'])) {
            $this->addImportedDataIfValueIsNonEmpty('currency', strtoupper($attributes['iso_waehrung']));
        }
    }

    /**
     * Fetches the attributes for 'zustand' and stores them with the
     * corresponding database column name as key in $this->importedData.
     *
     * @return void
     */
    private function fetchState()
    {
        $nodeWithAttributes = $this->findFirstGrandchild('zustand_angaben', 'zustand');
        $attributes = $this->fetchLowercasedDomAttributes($nodeWithAttributes);
        $possibleStates = [
            'rohbau' => 1,
            'nach_vereinbarung' => 2,
            'baufaellig' => 3,
            'erstbezug' => 4,
            'abrissobjekt' => 5,
            'entkernt' => 6,
            'modernisiert' => 7,
            'gepflegt' => 8,
            'teil_vollrenovierungsbed' => 9,
            'neuwertig' => 10,
            'teil_vollrenoviert' => 11,
            'teil_vollsaniert' => 12,
            'projektiert' => 13,
        ];

        if (isset($attributes['zustand_art'], $possibleStates[$attributes['zustand_art']])) {
            $this->addImportedData('state', $possibleStates[$attributes['zustand_art']]);
        }
    }

    /**
     * Fetches the status of this object (vacant or rented).
     *
     * @return void
     */
    private function fetchStatus()
    {
        $node = $this->findFirstGrandchild('verwaltung_objekt', 'vermietet');
        if ($node === null) {
            return;
        }

        $status = $this->isBooleanLikeStringTrue($node->nodeValue)
            ? tx_realty_Model_RealtyObject::STATUS_RENTED : tx_realty_Model_RealtyObject::STATUS_VACANT;

        $this->addImportedData('status', $status);
    }

    /**
     * Fetches the 'boden' and stores it with the corresponding database
     * column name 'flooring' as key in $this->importedData.
     *
     * @return void
     */
    private function fetchFlooring()
    {
        $flooringNode = $this->findFirstGrandchild('ausstattung', 'boden');
        $attributes = $this->fetchLowercasedTruthyDomAttributes($flooringNode);

        // The fetched flooring types are always German. In the database they
        // are stored as a sorted list of keys which refer to localized strings.
        $validKeys = [
            1 => 'fliesen',
            2 => 'stein',
            3 => 'teppich',
            4 => 'parkett',
            5 => 'fertigparkett',
            6 => 'laminat',
            7 => 'dielen',
            8 => 'kunststoff',
            9 => 'estrich',
            10 => 'doppelboden',
            11 => 'linoleum',
        ];

        $keys = [];
        foreach ($validKeys as $key => $value) {
            if (isset($attributes[$value]) && $this->isBooleanLikeStringTrue($attributes[$value])) {
                $keys[] = $key;
            }
        }

        $this->addImportedDataIfValueIsNonEmpty('flooring', implode(',', $keys));
    }

    /**
     * Fetches the value for 'ausstatt_kategorie' and stores it with the
     * corresponding database column name as key in $this->importedData.
     *
     * @return void
     */
    private function fetchFurnishingCategory()
    {
        $nodeWithAttributes = $this->findFirstGrandchild('ausstattung', 'ausstatt_kategorie');
        $furnishingCategory = strtolower($nodeWithAttributes->nodeValue);

        $possibleStates = [
            'standard' => 1,
            'gehoben' => 2,
            'luxus' => 3,
        ];

        if (($furnishingCategory !== '') && isset($possibleStates[$furnishingCategory])) {
            $this->addImportedData('furnishing_category', $possibleStates[$furnishingCategory]);
        }
    }

    /**
     * Fetches the value for 'old_or_new_building' and stores it in
     * $this->importedData.
     *
     * @return void
     */
    private function fetchValueForOldOrNewBuilding()
    {
        $attributesArray = $this->fetchLowercasedDomAttributes($this->findFirstGrandchild('zustand_angaben', 'alter'));

        if ($attributesArray['alter_attr'] === 'neubau') {
            $this->addImportedData('old_or_new_building', 1);
        } elseif ($attributesArray['alter_attr'] === 'altbau') {
            $this->addImportedData('old_or_new_building', 2);
        }
    }

    /**
     * Fetches the attribute 'aktion' and stores it with the corresponding
     * database column name as key in $this->importedData.
     *
     * @return void
     */
    private function fetchAction()
    {
        $nodeWithAttributes = $this->findFirstGrandchild('verwaltung_techn', 'aktion');
        // The node is valid when there is a node name, it does not need to
        // have attributes.
        if ($this->getNodeName($nodeWithAttributes)) {
            $this->addImportedData(
                'deleted',
                (int)in_array('delete', $this->fetchLowercasedDomAttributes($nodeWithAttributes), true)
            );
        }
    }

    /**
     * Fetches the value for 'language' and stores it in $this->importedData.
     *
     * @return void
     */
    private function fetchLanguage()
    {
        $userDefinedAnyFieldNode = $this->findFirstGrandchild('verwaltung_objekt', 'user_defined_anyfield');

        if ($userDefinedAnyFieldNode) {
            $languageNode = $this->getNodeListFromRawData('sprache', '', $userDefinedAnyFieldNode);
            $this->addImportedData('language', $languageNode->item(0)->nodeValue);
        }
    }

    /**
     * Fetches the values for the geo coordinates and stores it in $this->importedData.
     *
     * @return void
     */
    private function fetchGeoCoordinates()
    {
        $geoCoordinatesNode = $this->findFirstGrandchild('geo', 'geokoordinaten');
        $attributes = $this->fetchLowercasedDomAttributes($geoCoordinatesNode);

        if (
            $this->isElementSetAndNonEmpty('laengengrad', $attributes)
            && $this->isElementSetAndNonEmpty('breitengrad', $attributes)
        ) {
            $this->addImportedData('has_coordinates', true);
            $this->addImportedData('coordinates_problem', false);
            $this->addImportedData('longitude', (float)$attributes['laengengrad']);
            $this->addImportedData('latitude', (float)$attributes['breitengrad']);
        }
    }

    /**
     * Fetches the value for country, finds the corresponding UID in the static
     * countries table and stores it in $this->importedData.
     *
     * @throws Tx_Oelib_Exception_Database if the database query fails
     *
     * @return void
     */
    private function fetchCountry()
    {
        $nodeWithAttributes = $this->findFirstGrandchild('geo', 'land');
        $attributes = $this->fetchLowercasedDomAttributes($nodeWithAttributes);

        if (!isset($attributes['iso_land']) || $attributes['iso_land'] === '') {
            return;
        }

        $country = strtoupper($attributes['iso_land']);

        if (isset(self::$cachedCountries[$country])) {
            $uid = self::$cachedCountries[$country];
        } else {
            try {
                $row = Tx_Oelib_Db::selectSingle(
                    'uid',
                    'static_countries',
                    'cn_iso_3 = "' . Tx_Oelib_Db::getDatabaseConnection()->quoteStr($country, 'static_countries') . '"'
                );
                $uid = $row['uid'];
            } catch (Tx_Oelib_Exception_EmptyQueryResult $exception) {
                $uid = 0;
            }
            $this->cacheCountry($country, $uid);
        }

        $this->addImportedDataIfValueIsNonEmpty('country', $uid);
    }

    /**
     * Fetches the attribute 'ausstelldatum' and stores it in $this->importedData.
     *
     * @return void
     */
    private function fetchEnergyCertificateIssueDate()
    {
        $node = $this->findFirstGrandchild('energiepass', 'ausstelldatum');
        $nodeValue = $node->nodeValue;
        if ($nodeValue !== null) {
            $result = strtotime($nodeValue);
        } else {
            $result = 0;
        }

        $this->addImportedData('energy_certificate_issue_date', $result);
    }

    /**
     * Fetches the attribute 'epart' and stores it in $this->importedData.
     *
     * @return void
     */
    private function fetchEnergyCertificateType()
    {
        $node = $this->findFirstGrandchild('energiepass', 'epart');
        if ($node === null || $node->nodeValue === null) {
            return;
        }

        $validValues = [
            'BEDARF' => tx_realty_Model_RealtyObject::ENERGY_CERTIFICATE_TYPE_REQUIREMENT,
            'VERBRAUCH' => tx_realty_Model_RealtyObject::ENERGY_CERTIFICATE_TYPE_CONSUMPTION,
        ];

        $nodeValue = $node->nodeValue;
        if (isset($validValues[$nodeValue])) {
            $this->addImportedData('energy_certificate_type', $validValues[$nodeValue]);
        }
    }

    /**
     * Fetches the attribute 'jahrgang' and stores it in $this->importedData.
     *
     * @return void
     */
    private function fetchEnergyCertificateYear()
    {
        $node = $this->findFirstGrandchild('energiepass', 'jahrgang');
        if ($node === null || $node->nodeValue === null) {
            return;
        }

        $validValues = [
            '2008' => tx_realty_Model_RealtyObject::ENERGY_CERTIFICATE_YEAR_2008,
            '2014' => tx_realty_Model_RealtyObject::ENERGY_CERTIFICATE_YEAR_2014,
            'ohne' => tx_realty_Model_RealtyObject::ENERGY_CERTIFICATE_YEAR_NOT_AVAILABLE,
            'nicht_noetig' => tx_realty_Model_RealtyObject::ENERGY_CERTIFICATE_YEAR_NOT_REQUIRED,
        ];

        $nodeValue = $node->nodeValue;
        if (isset($validValues[$nodeValue])) {
            $this->addImportedData('energy_certificate_year', $validValues[$nodeValue]);
        }
    }

    /**
     * Fetches the attribute 'gebaeudeart' and stores it in $this->importedData.
     *
     * @return void
     */
    private function fetchBuildingType()
    {
        $node = $this->findFirstGrandchild('energiepass', 'gebaeudeart');
        if ($node === null || $node->nodeValue === null) {
            return;
        }

        $validValues = [
            'wohn' => tx_realty_Model_RealtyObject::BUILDING_TYPE_RESIDENTIAL,
            'nichtwohn' => tx_realty_Model_RealtyObject::BUILDING_TYPE_BUSINESS,
        ];

        $nodeValue = $node->nodeValue;
        if (isset($validValues[$nodeValue])) {
            $this->addImportedData('building_type', $validValues[$nodeValue]);
        }
    }

    /**
     * Returns a comma-separated list of an array. The first letter of each word
     * is uppercased.
     *
     * @param string[] $dataToFormat data to format, must not be empty
     *
     * @return string formatted string
     */
    private function getFormattedString(array $dataToFormat)
    {
        return ucwords(strtolower(implode(', ', $dataToFormat)));
    }

    /**
     * Returns DOMNodeList from the raw data. This list consists of all
     * elements from raw data which apply to $nodeName and to $childNodeName. If
     * $childNodeName is empty, $nodeName is the only criteria. $nodeName and
     * $childNodeName must not contain namespaces.
     * If $contextNode is set, the elements are fetched relatively from this
     * node.
     *
     * @param string $nodeName
     *        node name, must not be empty
     * @param string $childNodeName
     *        child node name, may be empty, the elements are taken from the node named $nodeName then
     * @param DOMNode $contextNode
     *        subnode to fetch a relative result, may be NULL, the query is made on the root node then
     *
     * @return DOMNodeList all nodes which are named $childNodeName, $nodeName
     *                     if $childNodeName is not set, can be empty if these
     *                     names do not exist
     */
    private function getNodeListFromRawData($nodeName, $childNodeName = '', $contextNode = null)
    {
        $queryString = '';
        $isContextNodeValid = false;
        if ($contextNode && (get_parent_class($contextNode) === 'DOMNode')) {
            $isContextNodeValid = true;
            $queryString = '.';
        }

        $queryString .= '//*[local-name()="' . $nodeName . '"]';
        if ($childNodeName !== '') {
            $queryString .= '/*[local-name()="' . $childNodeName . '"]';
        }

        if ($isContextNodeValid) {
            $result = $this->rawRealtyData->query($queryString, $contextNode);
        } else {
            $result = $this->rawRealtyData->query($queryString);
        }

        return $result;
    }

    /**
     * Returns the first grandchild of an element inside the realty record
     * with the current record number specified by the child's and the
     * grandchild's name. If one of these names can not be found or there are no
     * realty records, NULL is returned.
     *
     * @param string $nameOfChild name of child, must not be empty
     * @param string $nameOfGrandchild name of grandchild, must not be empty
     *
     * @return DOMNode first grandchild with this name, null if it does not exist
     */
    protected function findFirstGrandchild($nameOfChild, $nameOfGrandchild)
    {
        $listedRealtyObjects = $this->getListedRealtyObjects();

        if ($listedRealtyObjects === null) {
            return null;
        }

        $contextNode = $listedRealtyObjects->item($this->recordNumber);

        $queryResult = $this->getNodeListFromRawData($nameOfChild, $nameOfGrandchild, $contextNode);

        if ($queryResult) {
            $result = $queryResult->item(0);
        } else {
            $result = null;
        }

        return $result;
    }

    /**
     * Checks whether the OpenImmo record has a valid root node. The node must
     * be named 'openimmo' or 'immoxml'.
     *
     * @return bool TRUE if the root node is named 'openimmo' or 'immoxml', FALSE otherwise
     */
    private function hasValidRootNode()
    {
        $rootNode = $this->rawRealtyData->query('//*[local-name()="openimmo"] | //*[local-name()="immoxml"]');

        return (bool)$rootNode->item(0);
    }

    /**
     * Returns a DOMNodeList of the realty records found in $realtyData or NULL
     * if there are none.
     *
     * @return DOMNodeList list of nodes named 'immobilie', NULL if none were
     *                     found
     */
    private function getListedRealtyObjects()
    {
        return $this->getNodeListFromRawData('immobilie');
    }

    /**
     * Adds a new element $value to the array $arrayExpand, using the key $key.
     * The element will not be added if is NULL.
     *
     * @param string[] $arrayToExpand
     *        array into which the new element should be inserted, may be empty
     * @param string $key the key to insert, must not be empty
     * @param mixed $value the value to insert, may be empty or even NULL
     *
     * @return void
     */
    protected function addElementToArray(array &$arrayToExpand, $key, $value)
    {
        if ($value === null) {
            return;
        }

        if ($this->isRichtextField($key)) {
            $cleanedValue = (string)$value;
        } elseif (is_string($value)) {
            $cleanedValue = preg_replace('/[\\r\\n\\t]+/', ' ', trim($value));
        } else {
            $cleanedValue = $value;
        }

        $arrayToExpand[$key] = $cleanedValue;
    }

    /**
     * Checks whether the key should be parsed as richtext field.
     *
     * @param string $key the key to check for, must not be empty
     *
     * @return bool TRUE if the field is a richtext field. FALSE otherwise.
     */
    protected function isRichtextField($key)
    {
        return in_array($key, self::$richTextFields, true);
    }

    /**
     * Adds an element to $this->importedData.
     *
     * @param string $key the key to insert, must not be empty
     * @param mixed $value the value to insert, may be empty or even NULL
     *
     * @return void
     */
    private function addImportedData($key, $value)
    {
        $this->addElementToArray($this->importedData, $key, $value);
    }

    /**
     * Adds an element to $this->importedData if $value is non-empty.
     *
     * @param string $key the key for the element to add, must not be empty
     * @param mixed $value
     *        the value for the element to add, will not be added if it is empty
     *
     * @return void
     */
    private function addImportedDataIfValueIsNonEmpty($key, $value)
    {
        if (empty($value)) {
            return;
        }

        $this->addImportedData($key, $value);
    }

    /**
     * Checks whether an element exists in an array and is non-empty.
     *
     * @param string $key key of the element that should be checked to exist and being non-empty, must not be empty
     * @param array $array array in which the existence of an element should be checked, may be empty
     *
     * @return bool TRUE if the the element exists and is non-empty,
     *                 FALSE otherwise
     */
    private function isElementSetAndNonEmpty($key, array $array)
    {
        return isset($array[$key]) && !empty($array[$key]);
    }

    /**
     * Fetches an attribute from a given node and returns name/value pairs as an
     * array. If there are no attributes, the returned array will be empty.
     *
     * @param DOMNode|DOMNodeList|null $nodeWithAttributes node from where to fetch the attribute, may be NULL
     *
     * @return string[] attributes and attribute values, empty if there are no attributes
     */
    protected function fetchDomAttributes($nodeWithAttributes = null)
    {
        if ($nodeWithAttributes === null) {
            return [];
        }

        $fetchedValues = [];
        $attributeToFetch = $nodeWithAttributes->attributes;
        if ($attributeToFetch) {
            /** @var DOMAttr $domObject */
            foreach ($attributeToFetch as $domObject) {
                $fetchedValues[$domObject->name] = $domObject->value;
            }
        }

        return $fetchedValues;
    }

    /**
     * Fetches an attribute from a given node and returns lowercased name/value
     * pairs as an array. If there are no attributes, the returned array will be
     * empty.
     *
     * @param DOMNode $nodeWithAttributes node from where to fetch the attribute, may be NULL
     *
     * @return string[] lowercased attributes and attribute values, empty if
     *               there are no attributes
     */
    private function fetchLowercasedDomAttributes($nodeWithAttributes)
    {
        $result = [];

        foreach ($this->fetchDomAttributes($nodeWithAttributes) as $key => $value) {
            $result[strtolower($key)] = strtolower($value);
        }

        return $result;
    }

    /**
     * Fetches an attribute from a given node and returns lowercased name/value
     * pairs as an array for those attributes that have a truthy value.
     * If there are no attributes, the returned array will be empty.
     *
     * @param DOMNode $nodeWithAttributes node from where to fetch the attribute, may be NULL
     *
     * @return string[] lowercased attributes and attribute values, empty if
     *               there are no attributes
     */
    private function fetchLowercasedTruthyDomAttributes($nodeWithAttributes)
    {
        $result = [];
        foreach ($this->fetchLowercasedDomAttributes($nodeWithAttributes) as $key => $value) {
            if ($value === 'true' || $value === '1') {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * Caches the fetched countries in order to reduce the the number of
     * database queries.
     *
     * @param string $key ISO3166 code for the country, must not be empty
     * @param int $value UID of the country, must match the UID in the static
     *                countries table, must be >= 0
     *
     * @return void
     */
    private function cacheCountry($key, $value)
    {
        self::$cachedCountries[$key] = $value;
    }

    /**
     * Fetches the rent excluding bills from the XML.
     *
     * Rent excluding bills will be fetched from 'nettokaltmiete' if it is
     * present and not empty, otherwise it will be fetched from 'kaltmiete'.
     *
     * @return void
     */
    private function fetchRent()
    {
        $nodeWithAttributes = $this->findFirstGrandchild('preise', 'nettokaltmiete');

        if (!$nodeWithAttributes || ($nodeWithAttributes->nodeValue === '')) {
            $nodeWithAttributes = $this->findFirstGrandchild('preise', 'kaltmiete');
        }

        if ($nodeWithAttributes) {
            $this->addImportedDataIfValueIsNonEmpty('rent_excluding_bills', $nodeWithAttributes->nodeValue);
        }
    }

    /**
     * Fetches the deposit from the XML.
     *
     * The deposit will be fetched from preise > kaution_text, and if that is empty, from preise > kaution.
     *
     * @return void
     */
    private function fetchDeposit()
    {
        $depositTextNode = $this->findFirstGrandchild('preise', 'kaution_text');
        if ($depositTextNode !== null && $depositTextNode->nodeValue !== '') {
            $this->addImportedDataIfValueIsNonEmpty('deposit', $depositTextNode->nodeValue);
        } else {
            $depositNumberNode = $this->findFirstGrandchild('preise', 'kaution');
            if ($depositNumberNode !== null && $depositNumberNode->nodeValue !== '') {
                $deposit = (float)$depositNumberNode->nodeValue;
                $this->addImportedDataIfValueIsNonEmpty('deposit', (string)$deposit);
            }
        }
    }

    /**
     * Fetches the balcony presence from the XML.
     *
     * @return void
     */
    private function fetchBalcony()
    {
        $hasBalconies = false;

        $balconiesNode = $this->findFirstGrandchild('flaechen', 'anzahl_balkone');
        if ($balconiesNode !== null) {
            $hasBalconies = $hasBalconies || (bool)$balconiesNode->nodeValue;
        }
        $balconyAndPatioNode = $this->findFirstGrandchild('flaechen', 'anzahl_balkon_terrassen');
        if ($balconyAndPatioNode !== null) {
            $hasBalconies = $hasBalconies || (bool)$balconyAndPatioNode->nodeValue;
        }
        $this->addImportedDataIfValueIsNonEmpty('balcony', (int)$hasBalconies);
    }
}
