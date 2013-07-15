<?php
/***************************************************************
* Copyright notice
*
* (c) 2008-2013 Saskia Metzler <saskia@merlin.owl.de>
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
 * This class renders Google Maps.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 * @author Saskia Metzler <saskia@merlin.owl.de>
 */
class tx_realty_pi1_GoogleMapsView extends tx_realty_pi1_FrontEndView {
	/**
	 * @var array collected map markers for the current view
	 */
	private $mapMarkers = array();

	/**
	 * @var boolean whether the constructor is called in test mode
	 */
	private $isTestMode = FALSE;

	/**
	 * @var integer the Google Maps zoom factor for a single marker
	 */
	const ZOOM_FOR_SINGLE_MARKER = 13;

	/**
	 * The constructor.
	 *
	 * @param array $configuration TypoScript configuration for the plugin
	 * @param tslib_cObj $cObj the parent cObj content, needed for the flexforms
	 * @param boolean $isTestMode whether the class is instantiated in test mode
	 */
	public function __construct(
		array $configuration, tslib_cObj $cObj, $isTestMode = FALSE
	) {
		$this->isTestMode = $isTestMode;
		$this->cObj = $cObj;
		$this->init($configuration);

		$this->getTemplateCode();
	}

	/**
	 * Frees as much memory that has been used by this object as possible.
	 */
	public function __destruct() {
		if (is_object($this->realtyObject)) {
			$this->realtyObject->__destruct();
		}
		$this->mapMarkers = array();
		unset($this->realtyObject);
		parent::__destruct();
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
	public function render(array $unused = array()) {
		if (empty($this->mapMarkers)) {
			return '';
		}

		$this->addGoogleMapToHtmlHead();

		return $this->getSubpart('GOOGLE_MAP');
	}

	/**
	 * Sets a map marker for the realty object with $realtyObjectUid.
	 *
	 * @param integer $realtyObjectUid UID of the realty object of which to collect the marker, must be > 0
	 * @param boolean $createLink whether the detail page should be linked in the object title
	 *
	 * @return void
	 */
	public function setMapMarker($realtyObjectUid, $createLink = FALSE) {
		$this->createMarkerFromCoordinates($realtyObjectUid, $createLink);
	}

	/**
	 * Creates the necessary Google Map entries in the HTML head for all
	 * map markers in $this->mapMarkers.
	 *
	 * @return void
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
			'/*]]>*/</script>';

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
	 * @param integer $realtyObjectUid UID of the realty object for which to create the marker, must be > 0
	 * @param boolean $createLink whether the detail page should be linked in the object title
	 *
	 * @return void
	 */
	protected function createMarkerFromCoordinates($realtyObjectUid, $createLink = FALSE) {
		/** @var $realtyObject tx_realty_Model_RealtyObject */
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')->find($realtyObjectUid);
		if ($realtyObject->hasGeoError()) {
			return;
		}

		if (!$realtyObject->hasGeoCoordinates()) {
			tx_oelib_Geocoding_Google::getInstance()->lookUp($realtyObject);
			$realtyObject->writeToDatabase();
		}
		if ($realtyObject->hasGeoError() || !$realtyObject->hasGeoCoordinates()) {
			return;
		}

		$mapMarker = tx_oelib_ObjectFactory::make('tx_realty_mapMarker');
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
	 * @param integer $realtyObjectUid UID of the realty object to link to, must be > 0
	 *
	 * @return string link tag, will be empty if no link text was provided
	 */
	private function createLinkToSingleViewPage($linkText, $realtyObjectUid) {
		if ($linkText == '') {
			return '';
		}

		$separateSingleViewPage = tx_oelib_MapperRegistry
			::get('tx_realty_Mapper_RealtyObject')
			->find($realtyObjectUid)->getProperty('details_page');

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
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/realty/pi1/class.tx_realty_pi1_GoogleMapsView.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/realty/pi1/class.tx_realty_pi1_GoogleMapsView.php']);
}
?>