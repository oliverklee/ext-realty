<?php
/***************************************************************
* Copyright notice
*
* (c) 2007-2010 Saskia Metzler <saskia@merlin.owl.de>
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
 * Class 'tx_realty_Model_RealtyObjectChild' for the 'realty' extension.
 *
 * This is mere a class used for unit tests of the 'realty' extension. Don't
 * use it for any other purpose.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 */
final class tx_realty_Model_RealtyObjectChild extends tx_realty_Model_RealtyObject {
	public function recordExistsInDatabase(
		$dataArray, $table = REALTY_TABLE_OBJECTS
	) {
		return parent::recordExistsInDatabase($dataArray, $table);
	}

	public function createNewDatabaseEntry(
		array $realtyData, $table = REALTY_TABLE_OBJECTS, $overridePid = 0
	) {
		return parent::createNewDatabaseEntry($realtyData, $table, $overridePid);
	}

	public function getDataType($realtyData) {
		return parent::getDataType($realtyData);
	}

	public function loadDatabaseEntry($uid) {
		return parent::loadDatabaseEntry($uid);
	}

	public function checkForRequiredFields() {
		return parent::checkForRequiredFields();
	}

	public function prepareInsertionAndInsertRelations() {
		parent::prepareInsertionAndInsertRelations();
	}

	public function fetchDomAttributes($nodeWithAttributes) {
		return parent::fetchDomAttributes($nodeWithAttributes);
	}

	public function getAllProperties() {
		return parent::getAllProperties();
	}

	public function getAllImageData() {
		return parent::getAllImageData();
	}
}
?>