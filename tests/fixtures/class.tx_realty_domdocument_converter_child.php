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
 * Class 'tx_realty_domdocument_converter_child' for the 'realty' extension.
 *
 * This is mere a class used for unit tests of the 'realty' extension. Don't
 * use it for any other purpose.
 *
 * @package		TYPO3
 * @subpackage	tx_realty
 * @author		Saskia Metzler <saskia@merlin.owl.de>
 */

require_once(t3lib_extMgm::extPath('realty').'lib/class.tx_realty_domdocument_converter.php');

final class tx_realty_domdocument_converter_child extends tx_realty_domdocument_converter {
	public function addElementToArray($arrayToExpand, $keyToInsert, $valueToInsert) {
		parent::addElementToArray($arrayToExpand, $keyToInsert, $valueToInsert);
	}

	public function createRecordsForImages($domElementAnhang) {
		return parent::createRecordsForImages($domElementAnhang);
	}

	public function findFirstChild($domnode, $nameOfChild) {
		return parent::findFirstChild($domnode, $nameOfChild);
	}

	public function findFirstGrandchild($domnode, $nameOfChild, $nameOfGrandchild) {
		return parent::findFirstGrandchild($domnode, $nameOfChild, $nameOfGrandchild);
	}

	public function fetchDomAttributes($nodeWithAttributes) {
		return parent::fetchDomAttributes($nodeWithAttributes);
	}

	public function getNodeName($domNode) {
		return parent::getNodeName($domNode);
	}

	public function isolateRealtyRecords(DOMNode $rawDomDocument) {
		return parent::isolateRealtyRecords($rawDomDocument);
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/tests/fixtures/class.tx_realty_domdocument_converter_child']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/tests/fixtures/class.tx_realty_domdocument_converter_child.php']);
}

?>
