<?php
/***************************************************************
* Copyright notice
*
* (c) 2010-2012 Oliver Klee <typo3-coding@oliverklee.de>
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
 * Class tx_realty_Model_Image for the "image" extension.
 *
 * This class represents a titled image.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class tx_realty_Model_Image extends tx_oelib_Model implements tx_oelib_Interface_Sortable {
	/**
	 * the folder where uploaded images get stored.
	 *
	 * @var string
	 */
	const UPLOAD_FOLDER = 'uploads/tx_realty/';

	/**
	 * Gets this image's title (caption).
	 *
	 * @return string the image's title, might be empty
	 */
	public function getTitle() {
		return $this->getAsString('caption');
	}

	/**
	 * Sets this images title (caption).
	 *
	 * @param string $title the title to set, may be empty
	 */
	public function setTitle($title) {
		$this->setAsString('caption', $title);
	}

	/**
	 * Gets the file name of this image (relative to the extension's upload
	 * directory).
	 *
	 * @return string the image file name, will be empty if no file name has
	 *                been set
	 */
	public function getFileName() {
		return $this->getAsString('image');
	}

	/**
	 * Sets the image file name.
	 *
	 * @param string $fileName
	 *        the name of the image file relative to the extension's upload
	 *        directory, must not be empty
	 */
	public function setFileName($fileName) {
		if ($fileName == '') {
			throw new InvalidArgumentException('$fileName must not be empty.', 1333036064);
		}

		$this->setAsString('image', $fileName);
	}

	/**
	 * Gets the thumbnail file name of this image (relative to the extension's
	 * upload directory).
	 *
	 * @return string
	 *         the thumbnail file name, will be empty if no file name has been
	 *         set
	 */
	public function getThumbnailFileName() {
		return $this->getAsString('thumbnail');
	}

	/**
	 * Sets the name of the separate thumbnail file.
	 *
	 * @param string $fileName
	 *        the name of the thumbnail file relative to the extension's upload
	 *        directory, may be empty
	 */
	public function setThumbnailFileName($fileName) {
		$this->setAsString('thumbnail', $fileName);
	}

	/**
	 * Checks whether this image has a non-empty thumbnail file name.
	 *
	 * @return boolean
	 *         TRUE if this image has a non-empty thumbnail file name, FALSE
	 *         otherwise
	 */
	public function hasThumbnailFileName() {
		return $this->hasString('thumbnail');
	}

	/**
	 * Gets the realty object this image is related to.
	 *
	 * @return tx_realty_Model_RealtyObject the related object, will be NULL
	 *                                      if non has been assigned
	 */
	public function getObject() {
		return $this->getAsModel('object');
	}

	/**
	 * Sets the realty object this image is related to.
	 *
	 * @param tx_realty_Model_RealtyObject $realtyObject
	 *        the related object to assign
	 */
	public function setObject(tx_realty_Model_RealtyObject $realtyObject) {
		$this->set('object', $realtyObject);
	}

	/**
	 * Returns the sorting value for this image.
	 *
	 * This is the sorting as used in the back end.
	 *
	 * @return integer the sorting value of this image, will be >= 0
	 */
	public function getSorting() {
		return $this->getAsInteger('sorting');
	}

	/**
	 * Sets the sorting value for this image.
	 *
	 * This is the sorting as used in the back end.
	 *
	 * @param integer $sorting the sorting value of this image, must be >= 0
	 */
	public function setSorting($sorting) {
		$this->setAsInteger('sorting', $sorting);
	}

	/**
	 * Sets the sorting of this image.
	 *
	 * @param integer $position
	 *        the position of this image, must be between 0 and 4
	 */
	public function setPosition($position) {
		$this->setAsInteger('position', $position);
	}

	/*
	 * Gets the position of this image.
	 *
	 * @return integer the position of this image, will be between 0 and 4
	 */
	public function getPosition() {
		return $this->getAsInteger('position');
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/realty/Model/Image.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/realty/Model/Image.php']);
}
?>