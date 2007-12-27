<?php
/***************************************************************
* Copyright notice
*
* (c) 2007 Saskia Metzler <saskia@merlin.owl.de>
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
 * Class 'tx_realty_openimmo_import_child' for the 'realty' extension.
 *
 * This is mere a class used for unit tests of the 'realty' extension. Don't
 * use it for any other purpose.
 *
 * @package		TYPO3
 * @subpackage	tx_realty
 * @author		Saskia Metzler <saskia@merlin.owl.de>
 */

require_once(t3lib_extMgm::extPath('realty').'lib/class.tx_realty_openimmo_import.php');

final class tx_realty_openimmo_import_child extends tx_realty_openimmo_import {
	public function unifyPath($importDirectory) {
		return parent::unifyPath($importDirectory);
	}

	public function getPathsOfZipsToExtract($importDirectory) {
		return parent::getPathsOfZipsToExtract($importDirectory);
	}

	public function getNameForExtractionFolder($pathOfZip) {
		return parent::getNameForExtractionFolder($pathOfZip);
	}

	public function clearFeCache() {
		parent::clearFeCache();
	}

	public function getPathForXml($pathOfZip) {
		return parent::getPathForXml($pathOfZip);
	}

	public function loadXmlFile($pathOfZip) {
		return parent::loadXmlFile($pathOfZip);
	}

	public function writeToDatabase($domDocument) {
		return parent::writeToDatabase($domDocument);
	}

	public function setSchemaFile($pathToSchemaFile) {
		return parent::setSchemaFile($pathToSchemaFile);
	}

	public function getImportedXml() {
		return parent::getImportedXml();
	}

	public function getRequiredFields() {
		return parent::getRequiredFields();
	}

	public function ensureContactEmail() {
		return parent::ensureContactEmail();
	}

	public function prepareEmails($emailData) {
		return parent::prepareEmails($emailData);
	}

	public function findContactEmails($pathOfZip) {
		return parent::findContactEmails($pathOfZip);
	}

	public function getContactEmailFromRealtyObject() {
		return parent::getContactEmailFromRealtyObject();
	}

	public function loadRealtyObject($data) {
		return parent::loadRealtyObject($data);
	}

	public function getDefaultEmailAddress() {
		return parent::getDefaultEmailAddress();
	}
	
	public function setDefaultEmailAddress($emailAddress = 'EM') {
		parent::setDefaultEmailAddress($emailAddress);
	}

	public function convertDomDocumentToArray($domDocument) {
		return parent::convertDomDocumentToArray($domDocument);
	}

	public function setUploadDirectory($path) {
		return parent::setUploadDirectory($path);
	}

	public function initializeLanguage() {
		parent::initializeLanguage();
	}

	/**
	 * To ensure static test conditions, the redefinition of this function
	 * always returns false. In tx_realty_openimmo_import, it returns a boolean
	 * value from the global configuration.
	 */
	public function isIgnoreValidationEnabled() {
		return false;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/tests/fixtures/class.tx_realty_openimmo_import_child']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/tests/fixtures/class.tx_realty_openimmo_import_child.php']);
}

?>
