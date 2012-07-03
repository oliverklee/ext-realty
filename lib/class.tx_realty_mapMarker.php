<?php
/***************************************************************
* Copyright notice
*
* (c) 2008-2012 Oliver Klee <typo3-coding@oliverklee.de>
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
 * Class 'tx_realty_mapMarker' for the 'realty' extension.
 *
 * This class represents a marker on a Google Map.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class tx_realty_mapMarker {
	/**
	 * @var string this marker's latitude
	 */
	private $latitude = '';

	/**
	 * @var string this marker's longitude
	 */
	private $longitude = '';

	/**
	 * @var boolean
	 */
	protected $coordinatesHaveBeenSet = FALSE;

	/**
	 * @var string this marker's title, quote- and tag-safe
	 */
	private $title = '';

	/**
	 * @var string this marker's info window HTML
	 */
	private $infoWindowHtml = '';

	/**
	 * Renders the JavaScript for creating this marker and adding it to an
	 * object 'map'.
	 *
	 * @return string JavaScript snippet for the this marker, will be
	 *                empty if this marker has no coordinates
	 */
	public function render() {
		if (!$this->hasCoordinates()) {
			return '';
		}

		$result = 'marker = new GMarker(' . $this->getCoordinates() .
			$this->getTitle() . ');' . LF .
			$this->getInfoWindow() .
			'map.addOverlay(marker);' . LF;

		return $result;
	}

	/**
	 * Sets this marker's coordinates.
	 *
	 * @param float $latitude latitude
	 * @param float $longitude longitude
	 */
	public function setCoordinates($latitude, $longitude) {
		$this->latitude = $latitude;
		$this->longitude = $longitude;

		$this->coordinatesHaveBeenSet = TRUE;
	}

	/**
	 * Gets this marker's coordinates as a JavaScript GLatLng instantiation.
	 *
	 * @return string this marker's coordinates as a GLatLng instantiation
	 *                JavaScript code snippet, an empty string if this
	 *                marker has no coordinates.
	 */
	public function getCoordinates() {
		if (!$this->hasCoordinates()) {
			return '';
		}

		return 'new GLatLng(' . $this->latitude . ',' . $this->longitude . ')';
	}

	/**
	 * Sets this marker's title.
	 *
	 * @param string $title title, may be empty, must not be HTML-safe
	 */
	public function setTitle($title) {
		$this->title = trim(addslashes(strip_tags($title)));
	}

	/**
	 * Sets this marker's info window HTML
	 *
	 * @param string $html info window HTML, may be empty
	 */
	public function setInfoWindowHtml($html) {
		// 1. escapes \ to \\
		// 2. escapes ' to \'
		// 3. escapes </ to <\/ (because this is embedded JavaScript)
		// Note: We cannot use addslashes because " must not be escaped.
		$this->infoWindowHtml = str_replace(
			array('\\', '\'', '</'),
			array('\\\\', '\\\'', '<\/'),
			$html
		);
	}

	/**
	 * Checks whether this marker has both latitude and longitude.
	 *
	 * @return boolean TRUE if this marker has coordinates, FALSE otherwise
	 */
	private function hasCoordinates() {
		return $this->coordinatesHaveBeenSet;
	}

	/**
	 * Returns this object's title as a JavaScript object declaration, starting
	 * with a comma.
	 *
	 * @return string a JavaScript object declaration for this marker's
	 *                title starting with a comma, an empty string if this
	 *                marker has no title
	 */
	private function getTitle() {
		if ($this->title == '') {
			return '';
		}

		return ', {title: "' . $this->title . '"}';
	}

	/**
	 * Creates a JavaScript snippet for adding the info window HTML.
	 *
	 * @return string info window creation JavaScript with trailing LF,
	 *                will be empty if this marker has no info window HTML
	 */
	private function getInfoWindow() {
		if ($this->infoWindowHtml == '') {
			return '';
		}

		return
			'marker.bindInfoWindowHtml(\'' . $this->infoWindowHtml . '\');' . LF;
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/realty/lib/class.tx_realty_mapMarker.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/realty/lib/class.tx_realty_mapMarker.php']);
}
?>