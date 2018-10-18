<?php

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * This class renders Google Maps.
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 * @author Saskia Metzler <saskia@merlin.owl.de>
 */
class tx_realty_pi1_GoogleMapsView extends tx_realty_pi1_FrontEndView
{
    /**
     * maximum fuzzing distance/radius
     *
     * @var float
     */
    const GEO_FUZZING_DISTANCE = .15;

    /**
     * @var tx_realty_mapMarker[]
     */
    private $mapMarkers = [];

    /**
     * @var bool whether the constructor is called in test mode
     */
    private $isTestMode = false;

    /**
     * @var int the Google Maps zoom factor for a single marker
     */
    const ZOOM_FOR_SINGLE_MARKER = 13;

    /**
     * The constructor.
     *
     * @param array $configuration TypoScript configuration for the plugin
     * @param ContentObjectRenderer $contentObjectRenderer the parent cObj content, needed for the flexforms
     * @param bool $isTestMode whether the class is instantiated in test mode
     */
    public function __construct(
        array $configuration,
        ContentObjectRenderer $contentObjectRenderer,
        $isTestMode = false
    ) {
        $this->cObj = $contentObjectRenderer;
        $this->init($configuration);

        $this->getTemplateCode();
    }

    /**
     * Returns the HTML for Google Maps.
     *
     * If none of the objects on the current page have coordinates, the result
     * will be empty.
     *
     * @param array $unused unused
     *
     * @return string HTML for the Google Map, will be empty no map markers have
     *                been set before
     */
    public function render(array $unused = [])
    {
        if (empty($this->mapMarkers)) {
            return '';
        }

        $this->addGoogleMapToHtmlHead();

        return $this->getSubpart('GOOGLE_MAP');
    }

    /**
     * Sets a map marker for the realty object with $realtyObjectUid.
     *
     * @param int $realtyObjectUid UID of the realty object of which to collect the marker, must be > 0
     * @param bool $createLink whether the detail page should be linked in the object title
     *
     * @return void
     */
    public function setMapMarker($realtyObjectUid, $createLink = false)
    {
        $this->createMarkerFromCoordinates($realtyObjectUid, $createLink);
    }

    /**
     * Creates the necessary Google Map entries in the HTML head for all
     * map markers in $this->mapMarkers.
     *
     * @return void
     */
    private function addGoogleMapToHtmlHead()
    {
        $configuration = Tx_Oelib_ConfigurationRegistry::get('plugin.tx_oelib');
        $apiKey = $configuration->getAsString('googleMapsApiKey');

        $generalGoogleMapsJavaScript = '<script type="text/javascript" ' .
            'src="https://maps.googleapis.com/maps/api/js?key=' . $apiKey . '"></script>' . LF;
        $createMapJavaScript = '<script type="text/javascript">' . LF .
            'var TYPO3 = TYPO3 || {};' . LF .
            'TYPO3.realty = TYPO3.realty || {};' . LF .
            'TYPO3.realty.initializeMapMarkers = function() {' . LF .
            'var mapOptions = {' . LF .
            'zoom: ' . self::ZOOM_FOR_SINGLE_MARKER . ',' . LF .
            'center: ' . $this->mapMarkers[0]->getCoordinates() . ',' . LF .
            'mapTypeControl: true,' . LF .
            'navigationControl: true,' . LF .
            'streetViewControl: false,' . LF .
            'mapTypeId: google.maps.MapTypeId.ROADMAP' . LF .
            '}; ' . LF .
            'var map = new google.maps.Map(document.getElementById("tx_realty_map"), mapOptions);' . LF .
            'var myInfoWindow = new google.maps.InfoWindow({' . LF .
            'content: "Loading …"' . LF .
            '});' . LF .
            'var bounds = new google.maps.LatLngBounds();' . LF .
            'var markersArray = [];';

        foreach ($this->mapMarkers as $mapMarker) {
            $createMapJavaScript .= $mapMarker->render() . LF .
                'bounds.extend(' . $mapMarker->getCoordinates() . ');' . LF;
        }

        if (count($this->mapMarkers) > 1) {
            $createMapJavaScript .= 'map.fitBounds(bounds);' . LF;
        }

        $createMapJavaScript .= '};' . LF . '</script>';
        $frontEndController = $this->getFrontEndController();
        $frontEndController->additionalHeaderData['tx_realty_pi1_maps'] =
            $generalGoogleMapsJavaScript . $createMapJavaScript;
    }

    /**
     * Tries to retrieve the geo coordinates for the realty object with
     * $realtyObjectUid and adds a map marker object to $this->mapMarkers.
     *
     * If the geo coordinates could not be retrieved, $this->mapMarkers will not
     * be changed.
     *
     * @param int $realtyObjectUid UID of the realty object for which to create the marker, must be > 0
     * @param bool $createLink whether the detail page should be linked in the object title
     *
     * @return void
     */
    protected function createMarkerFromCoordinates($realtyObjectUid, $createLink = false)
    {
        /** @vartx_realty_Mapper_RealtyObject  $mapper */
        $mapper = Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_RealtyObject::class);
        /** @var tx_realty_Model_RealtyObject $realtyObject */
        $realtyObject = $mapper->find($realtyObjectUid);
        if ($realtyObject->hasGeoError()) {
            return;
        }

        if (!$realtyObject->hasGeoCoordinates()) {
            Tx_Oelib_Geocoding_Google::getInstance()->lookUp($realtyObject);
            if (!$realtyObject->getShowAddress()) {
                /** @var Tx_Oelib_Geocoding_Calculator $geoCalculator */
                $geoCalculator = GeneralUtility::makeInstance(Tx_Oelib_Geocoding_Calculator::class);
                $geoCalculator->moveInRandomDirection($realtyObject, self::GEO_FUZZING_DISTANCE);
            }
            $realtyObject->writeToDatabase();
        }
        if ($realtyObject->hasGeoError() || !$realtyObject->hasGeoCoordinates()) {
            return;
        }

        /** @var \tx_realty_mapMarker $mapMarker */
        $mapMarker = GeneralUtility::makeInstance(\tx_realty_mapMarker::class);
        $coordinates = $realtyObject->getGeoCoordinates();
        $mapMarker->setCoordinates($coordinates['latitude'], $coordinates['longitude']);
        $mapMarker->setTitle($realtyObject->getAddressAsSingleLine());

        $mapMarkerTitle = $realtyObject->getCroppedTitle();
        if ($createLink) {
            $mapMarkerTitle = $this->createLinkToSingleViewPage($mapMarkerTitle, $realtyObjectUid);
        }

        $mapMarker->setInfoWindowHtml(
            '<strong>' . $mapMarkerTitle . '</strong><br />' . $realtyObject->getAddressAsHtml()
        );
        $this->mapMarkers[] = $mapMarker;
    }

    /**
     * Creates a link to the single view page. Therefore it uses the
     * configuration value "singlePID".
     *
     * @param string $linkText link text, may be "|" but not empty
     * @param int $realtyObjectUid UID of the realty object to link to, must be > 0
     *
     * @return string link tag, will be empty if no link text was provided
     */
    private function createLinkToSingleViewPage($linkText, $realtyObjectUid)
    {
        if ($linkText === '') {
            return '';
        }

        /** @var tx_realty_Mapper_RealtyObject $mapper */
        $mapper = Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_RealtyObject::class);
        /** @var tx_realty_Model_RealtyObject $realtyObject */
        $realtyObject = $mapper->find($realtyObjectUid);
        $separateSingleViewPage = (string)$realtyObject->getProperty('details_page');

        if ($separateSingleViewPage !== '') {
            $result = $this->cObj->typoLink(
                $linkText,
                ['parameter' => $separateSingleViewPage]
            );
        } else {
            $result = $this->cObj->typoLink($linkText, [
                'parameter' => $this->getConfValueInteger('singlePID'),
                'additionalParams' => GeneralUtility::implodeArrayForUrl(
                    $this->prefixId,
                    ['showUid' => $realtyObjectUid]
                ),
                'useCacheHash' => $this->getConfValueString('what_to_display') !== 'favorites',
            ]);
        }

        return $result;
    }
}
