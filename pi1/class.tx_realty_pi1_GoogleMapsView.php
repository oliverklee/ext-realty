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
require_once(t3lib_extMgm::extPath('realty') . 'lib/class.tx_realty_mapMarker.php');
require_once(t3lib_extMgm::extPath('realty') . 'lib/class.tx_realty_object.php');

/**
 * Class 'tx_realty_pi1_GoogleMapsView' for the 'realty' extension.
 *
 * This class renders Google Maps.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 * @author Saskia Metzler <saskia@merlin.owl.de>
 */
class tx_realty_pi1_GoogleMapsView extends tx_realty_pi1_FrontEndView {
	/** @var array collected map markers for the current view */
	private $mapMarkers = array();

	/** @var tx_realty_object realty object */
	private $realtyObject = null;

	/** @var boolean whether the constructor is called in test mode */
	private $isTestMode = false;

	/** @var integer the Google Maps zoom factor for a single marker */
	const ZOOM_FOR_SINGLE_MARKER = 13;

	/**
	 * The constructor.
	 *
	 * @param array TypoScript configuration for the plugin
	 * @param tslib_cObj the parent cObj content, needed for the flexforms
	 * @param boolean whether the class is instantiated in test mode
	 */
	public function __construct(
		array $configuration, tslib_cObj $cObj, $isTestMode = false
	) {
		$this->isTestMode = $isTestMode;
		$this->cObj = $cObj;
		$this->init($configuration);

		if ($this->isGoogleMapsEnabled()) {
			$this->getTemplateCode();
		}
	}

	/**
	 * Frees as much memory that has been used by this object as possible.
	 */
	public function __destruct() {
		$this->mapMarkers = array();
		unset($this->realtyObject);
		parent::__destruct();
	}

	/**
	 * Returns the HTML for Google Maps.
	 *
	 * If Google Maps is disabled or if none of the objects on the current
	 * page have coordinates, the result will be empty.
	 *
	 * @param array unused
	 *
	 * @return string HTML for the Google Map, will be empty if Google Maps is
	 *                disabled by configuration and if no map markers have been
	 *                set before
	 */
	public function render(array $unused = array()) {
		if (!$this->isGoogleMapsEnabled() || empty($this->mapMarkers)) {
			return '';
		}

		$this->addGoogleMapToHtmlHead();
		return $this->getSubpart('GOOGLE_MAP');
	}

	/**
	 * Sets a map marker for the realty object with $realtyObjectUid.
	 *
	 * @param integer UID of the realty object of which to collect the marker,
	 *                must be > 0
	 * @param boolean whether the detail page should be linked in the
	 *                object title
	 */
	public function setMapMarker($realtyObjectUid, $createLink = false) {
		if (!$this->isGoogleMapsEnabled()) {
			return;
		}

		$this->createMarkerFromCoordinates($realtyObjectUid, $createLink);
	}

	/**
	 * Checks whether Google Maps is enabled by configuration.
	 *
	 * @return boolean true if Google Maps is enabled, false otherwise
	 */
	private function isGoogleMapsEnabled() {
		return ($this->getConfValueBoolean('showGoogleMaps', 's_googlemaps'));
	}

	/**
	 * Creates the necessary Google Map entries in the HTML head for all
	 * map markers in $this->mapMarkers.
	 */
	private function addGoogleMapToHtmlHead() {
		$generalGoogleMapsJavaScript = '<script type="text/javascript" ' .
			'src="http://maps.google.com/maps?file=api&amp;v=2&amp;key=' .
			$this->getConfValueString(
				'googleMapsApiKey', 's_googlemaps'
			) . '"></script>' . LF;
		$createMapJavaScript = '<script type="text/javascript">' . LF .
			'/*<![CDATA[*/' . LF .
			'function initializeMap() {' . LF .
			'if (GBrowserIsCompatible()) {'. LF .
			'var map = new GMap2(document.getElementById("tx_realty_map"));' . LF .
			'map.setCenter(' . $this->mapMarkers[0]->getCoordinates() .
				', ' . self::ZOOM_FOR_SINGLE_MARKER . ');' . LF .
			'map.enableContinuousZoom();' . LF .
			'map.enableScrollWheelZoom();' . LF .
			'map.addControl(new GLargeMapControl());' . LF .
			'map.addControl(new GMapTypeControl());' . LF .
			'var bounds = new GLatLngBounds();' . LF .
			'var marker;' . LF;

		foreach ($this->mapMarkers as $mapMarker) {
			$createMapJavaScript .= $mapMarker->render() . LF .
			'bounds.extend(' . $mapMarker->getCoordinates() . ');' . LF;
		}

		if (count($this->mapMarkers) > 1) {
			$createMapJavaScript .=
				'map.setZoom(map.getBoundsZoomLevel(bounds));' . LF .
				'map.setCenter(bounds.getCenter());' . LF;
		}
		$createMapJavaScript .=  '}'. LF .
			'}' . LF .
			'/*]]>*/' . LF .
			'</script>';

		$GLOBALS['TSFE']->additionalHeaderData['tx_realty_pi1_maps']
			=  $generalGoogleMapsJavaScript . $createMapJavaScript;

		$GLOBALS['TSFE']->JSeventFuncCalls['onload']['tx_realty_pi1_maps']
			= 'initializeMap();';
		$GLOBALS['TSFE']->JSeventFuncCalls['onunload']['tx_realty_pi1_maps']
			= 'GUnload();';
	}

	/**
	 * Tries to retrieve the geo coordinates for the realty object with
	 * $realtyObjectUid and adds a map marker object to $this->mapMarkers.
	 *
	 * If the geo coordinates could not be retrieved, $this->mapMarkers will not
	 * be changed.
	 *
	 * @param integer UID of the realty object for which to create the marker,
	 *                must be > 0
	 * @param boolean whether the detail page should be linked in the
	 *                object title
	 */
	private function createMarkerFromCoordinates(
		$realtyObjectUid, $createLink = false
	) {
		$coordinates = $this->retrieveGeoCoordinates($realtyObjectUid);
		if (empty($coordinates)) {
			return;
		}

		$mapMarker = t3lib_div::makeInstance('tx_realty_mapMarker');
		$mapMarker->setCoordinates(
			$coordinates['latitude'], $coordinates['longitude']
		);
		$mapMarker->setTitle(
			$this->getRealtyObject($realtyObjectUid)->getTitle()
		);

		$title = $this->getRealtyObject($realtyObjectUid)->getCroppedTitle();

		if ($createLink) {
			$title = $this->createLinkToSingleViewPage($title, $realtyObjectUid);
		}

		$mapMarker->setInfoWindowHtml(
			'<strong>' . $title .
			'</strong><br />' .
			$this->getRealtyObject($realtyObjectUid)->getAddressAsHtml()
		);
		$this->mapMarkers[] = $mapMarker;
	}

	/**
	 * Retrieves the geo coordinates for the realty object with $realtyObjectUid.
	 *
	 * @throws Exception if the UID is not provided
	 *
	 * @param integer UID of the realty object for which to get the coordinates,
	 *                must be > 0
	 *
	 * @return array the coordinates using the keys "latitude" and
	 *               "longitude" or an empty array if the coordinates
	 *               could not be retrieved
	 */
	private function retrieveGeoCoordinates($realtyObjectUid) {
		if ($realtyObjectUid == 0) {
			throw new Exception(
				'$realtyObjectUid must not be an integer greater than zero.'
			);
		}

		try {
			$coordinates = $this->getRealtyObject($realtyObjectUid)
				->retrieveCoordinates($this);
		} catch (Exception $exception) {
			// RetrieveCoordinates will throw an exception if the Google Maps
			// API key is missing. As this is checked by the configuration
			// check, we don't need to act on this exception here.
			$coordinates = array();
		}

		return $coordinates;
	}

	/**
	 * Creates a link to the single view page. Therefore it uses the
	 * configuration value "singlePID".
	 *
	 * @param string link text, may be "|" but not empty
	 * @param integer UID of the realty object to link to, must be > 0
	 *
	 * @return string link tag, will be empty if no link text was provided
	 */
	private function createLinkToSingleViewPage($linkText, $realtyObjectUid) {
		if ($linkText == '') {
			return '';
		}

		$separateSingleViewPage = $this->getRealtyObject($realtyObjectUid)
			->getProperty('details_page');

		if ($separateSingleViewPage != '') {
			$result = $this->cObj->typoLink(
				$linkText, array('parameter' => $separateSingleViewPage)
			);
		} else {
			$result = $this->cObj->typoLink($linkText, array(
				'parameter' => $this->getConfValueInteger('singlePID'),
				'additionalParams' => t3lib_div::implodeArrayForUrl(
					$this->prefixId, array('showUid' => $realtyObjectUid)
				),
				'useCacheHash' => ($this->getConfValueString('what_to_display')
					!= 'favorites'),
			));
		}

		return $result;
	}

	/**
	 * Gets the realty object with the provided UID. The object is loaded if
	 * necessary. Hidden objects will also be loaded.
	 *
	 * @param integer realty object UID, must be >= 0
	 *
	 * @return tx_realty_object realty object with the provided UID
	 */
	private function getRealtyObject($realtyObjectUid) {
		if (!$this->realtyObject) {
			$realtyObjectClassName
				= t3lib_div::makeInstanceClassName('tx_realty_object');
			$this->realtyObject = new $realtyObjectClassName($this->isTestMode);
		}
		if ($this->realtyObject->getUid() != $realtyObjectUid) {
			$this->realtyObject->loadRealtyObject($realtyObjectUid, true);
		}

		return $this->realtyObject;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/pi1/class.tx_realty_pi1_GoogleMapsView.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/pi1/class.tx_realty_pi1_GoogleMapsView.php']);
}
?>