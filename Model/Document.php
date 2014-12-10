<?php
/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * This class represents an attached document.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Bernd Schönbach <bernd.schoenbach@googlemail.com>
 */
class tx_realty_Model_Document extends tx_oelib_Model implements tx_oelib_Interface_Sortable {
	/**
	 * the folder where uploaded documents get stored.
	 *
	 * @var string
	 */
	const UPLOAD_FOLDER = 'uploads/tx_realty/';

	/**
	 * Gets this document's title.
	 *
	 * @return string this document's title, will be empty if no title has been set
	 */
	public function getTitle() {
		return $this->getAsString('title');
	}

	/**
	 * Sets this document's title.
	 *
	 * @param string $title the title to set, must not be empty
	 *
	 * @return void
	 */
	public function setTitle($title) {
		if ($title == '') {
			throw new InvalidArgumentException('$title must not be empty.', 1333036044);
		}

		$this->setAsString('title', $title);
	}

	/**
	 * Gets the file name of this document (relative to the extension's upload
	 * directory).
	 *
	 * @return string this document's file name, will be empty if no file name has
	 *                been set
	 */
	public function getFileName() {
		return $this->getAsString('filename');
	}

	/**
	 * Sets this document's file name.
	 *
	 * @param string $fileName
	 *        the name of the file relative to the extension's upload
	 *        directory, must not be empty
	 *
	 * @return void
	 */
	public function setFileName($fileName) {
		if ($fileName == '') {
			throw new InvalidArgumentException('$fileName must not be empty.', 1333036052);
		}

		$this->setAsString('filename', $fileName);
	}

	/**
	 * Gets the realty object this document is related to.
	 *
	 * @return tx_realty_Model_RealtyObject the related object, will be NULL
	 *                                      if non has been assigned
	 */
	public function getObject() {
		return $this->getAsModel('object');
	}

	/**
	 * Sets the realty object this document is related to.
	 *
	 * @param tx_realty_Model_RealtyObject $realtyObject
	 *        the related object to assign
	 *
	 * @return void
	 */
	public function setObject(tx_realty_Model_RealtyObject $realtyObject) {
		$this->set('object', $realtyObject);
	}

	/**
	 * Returns the sorting value for this document.
	 *
	 * This is the sorting as used in the back end.
	 *
	 * @return integer the sorting value of this document, will be >= 0
	 */
	public function getSorting() {
		return $this->getAsInteger('sorting');
	}

	/**
	 * Sets the sorting value for this document.
	 *
	 * This is the sorting as used in the back end.
	 *
	 * @param integer $sorting the sorting value of this document, must be >= 0
	 *
	 * @return void
	 */
	public function setSorting($sorting) {
		$this->setAsInteger('sorting', $sorting);
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/realty/Model/Document.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/realty/Model/Document.php']);
}