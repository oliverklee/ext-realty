<?php
/***************************************************************
* Copyright notice
*
* (c) 2007-2013 Saskia Metzler <saskia@merlin.owl.de>
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
 * This is merely a class used for unit tests. Don't use it for any other purpose.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
final class tx_realty_domDocumentConverterChild extends tx_realty_domDocumentConverter {
	/**
	 * Adds a new element $value to the array $arrayExpand, using the key $key.
	 * The element will not be added if is NULL.
	 *
	 * @param array $arrayToExpand
	 *        array into which the new element should be inserted, may be empty
	 * @param string $key the key to insert, must not be empty
	 * @param mixed $value the value to insert, may be empty or even NULL
	 *
	 * @return void
	 */
	public function addElementToArray(&$arrayToExpand, $keyToInsert, $valueToInsert) {
		parent::addElementToArray($arrayToExpand, $keyToInsert, $valueToInsert);
	}

	/**
	 * Creates an array of image records for one realty record.
	 *
	 * @return array image records, will be empty if there are none
	 */
	public function createRecordsForImages() {
		return parent::createRecordsForImages();
	}

	/**
	 * Creates an array of document records for one realty record.
	 *
	 * @return array
	 *         document records, will be empty if there are none
	 */
	public function importDocuments() {
		return parent::importDocuments();
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
	 * @return DOMNode first grandchild with this name, NULL if it does not
	 *                 exist
	 */
	public function findFirstGrandchild($nameOfChild, $nameOfGrandchild) {
		return parent::findFirstGrandchild($nameOfChild, $nameOfGrandchild);
	}

	/**
	 * Fetches an attribute from a given node and returns name/value pairs as an
	 * array. If there are no attributes, the returned array will be empty.
	 *
	 * @param DOMNode $nodeWithAttributes node from where to fetch the attribute, may be NULL
	 *
	 * @return array attributes and attribute values, empty if there are
	 *               no attributes
	 */
	public function fetchDomAttributes($nodeWithAttributes) {
		return parent::fetchDomAttributes($nodeWithAttributes);
	}

	/**
	 * Substitutes XML namespaces from a node name and returns the name.
	 *
	 * @param DOMNode $domNode node, may be NULL
	 *
	 * @return string node name without namespaces, may be empty
	 */
	public function getNodeName($domNode) {
		return parent::getNodeName($domNode);
	}

	/**
	 * Loads the raw data from a DOMDocument.
	 *
	 * @param DOMDocument $rawRealtyData raw data to load, must not be NULL
	 *
	 * @return void
	 */
	public function setRawRealtyData($rawRealtyData) {
		return parent::setRawRealtyData($rawRealtyData);
	}
}
?>