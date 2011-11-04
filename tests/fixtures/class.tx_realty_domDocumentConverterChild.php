<?php
/***************************************************************
* Copyright notice
*
* (c) 2007-2011 Saskia Metzler <saskia@merlin.owl.de>
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
 * Class 'tx_realty_domDocumentConverterChild' for the 'realty' extension.
 *
 * This is mere a class used for unit tests of the 'realty' extension. Don't
 * use it for any other purpose.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
final class tx_realty_domDocumentConverterChild extends tx_realty_domDocumentConverter {
	public function addElementToArray(&$arrayToExpand, $keyToInsert, $valueToInsert) {
		parent::addElementToArray($arrayToExpand, $keyToInsert, $valueToInsert);
	}

	public function createRecordsForImages() {
		return parent::createRecordsForImages();
	}

	public function importDocuments() {
		return parent::importDocuments();
	}

	public function findFirstGrandchild($nameOfChild, $nameOfGrandchild) {
		return parent::findFirstGrandchild($nameOfChild, $nameOfGrandchild);
	}

	public function fetchDomAttributes($nodeWithAttributes) {
		return parent::fetchDomAttributes($nodeWithAttributes);
	}

	public function getNodeName($domNode) {
		return parent::getNodeName($domNode);
	}

	public function setRawRealtyData($rawRealtyData) {
		return parent::setRawRealtyData($rawRealtyData);
	}

	public function initializeLanguage() {
		global $LANG;

		// the language class does not restore this value
		$LANG->lang = 'default';
		parent::initializeLanguage();
	}
}
?>