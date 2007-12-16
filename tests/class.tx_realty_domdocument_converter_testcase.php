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
 * Unit tests for the tx_realty_object class in the 'realty' extension.
 *
 * @package		TYPO3
 * @subpackage	tx_realty
 * @author		Saskia Metzler <saskia@merlin.owl.de>
 */

require_once(t3lib_extMgm::extPath('realty')
	.'tests/fixtures/class.tx_realty_domdocument_converter_child.php');

class tx_realty_domdocument_converter_testcase extends tx_phpunit_testcase {
	public function setUp() {
		$this->fixture = new tx_realty_domdocument_converter_child();
	}

	public function tearDown() {
		unset($this->fixture);
	}

	public function testFindFirstChildReturnsChildIfExists() {
		$parent = new DOMDocument();
		$child = $parent->appendChild(
			$parent->createElement('child', 'foo')
		);
		$result = $this->fixture->findFirstChild($parent, 'child');

		$this->assertEquals(
			$result->nodeValue,
			'foo'
		);
	}

	public function testFindFirstChildReturnsNullIfChildNotExists() {
		$parent = new DOMDocument();

		$this->assertNull(
			$this->fixture->findFirstChild($parent, 'child')
		);
	}

	public function testFindFirstGrandchildReturnsGrandchildIfExists() {
		$parent = new DOMDocument();
		$child = $parent->appendChild(
			$parent->createElement('child')
		);
		$grandchild = $child->appendChild(
			$parent->createElement('grandchild', 'foo')
		);
		$result = $this->fixture->findFirstGrandchild($parent, 'child', 'grandchild');

		$this->assertEquals(
			$result->nodeValue,
			'foo'
		);
	}

	public function testFindFirstGrandchildReturnsNullIfGrandchildNotExists() {
		$parent = new DOMDocument();
		$child = $parent->appendChild(
			$parent->createElement('child')
		);

		$this->assertNull(
			$this->fixture->findFirstGrandchild($parent, 'child', 'grandchild')
		);
	}

	public function testFindFirstGrandchildReturnsNullIfGivenDomnodeIsEmpty() {
		$parent = new DOMDocument();

		$this->assertNull(
			$this->fixture->findFirstGrandchild($parent, 'child', 'grandchild')
		);
	}

	public function testGetNodeNameDoesNotChangeNodeNameWithoutPrefix() {
		$node = new DOMDocument();
		$child = $node->appendChild(
			$node->createElement('foo')
		);

		$this->assertEquals(
			'foo',
			$this->fixture->getNodeName($child)
		);
	}

	public function testGetNodeNameReturnsNameWithoutPrefixWhenNameWithPrefixGiven() {
		$node = new DOMDocument();
		$child = $node->appendChild(
			$node->createElement('prefix:foo')
		);

		$this->assertEquals(
			'foo',
			$this->fixture->getNodeName($child)
		);
	}

	public function testAddElementToArrayOnce() {
		$data = array();
		$this->fixture->addElementToArray(&$data, 'foo', 'bar');

		$this->assertEquals(
			$data,
			array('foo' => 'bar')
		);
	}

	public function testAddElementToArrayTwice() {
		$data = array();
		$this->fixture->addElementToArray(&$data, 'foo', 'foo');
		$this->fixture->addElementToArray(&$data, 'bar', 'bar');

		$this->assertEquals(
			$data,
			array('foo' => 'foo',
				'bar' => 'bar')
		);
	}

	public function testIsolateRealtyRecordsWhenNoRealtyRecordGiven() {
		$node = new DOMDocument();
		$openimmo = $node->appendChild(
			$node->createElement('openimmo')
		);
		$vendor = $openimmo->appendChild(
			$node->createElement('anbieter')
		);

		$this->assertEquals(
			$this->fixture->isolateRealtyRecords($node),
			array()
		);
	}

	public function testIsolateRealtyRecordsWhenOneRealtyRecordGiven() {
		$node = new DOMDocument();
		$openimmo = $node->appendChild(
			$node->createElement('openimmo')
		);
		$vendor = $openimmo->appendChild(
			$node->createElement('anbieter')
		);
		$realtyOne = $vendor->appendChild(
			$node->createElement('immobilie')
		);

		$result = $this->fixture->isolateRealtyRecords($node);

		$this->assertEquals(
			$result[0]->nodeName,
			'immobilie'
		);
	}

	public function testIsolateRealtyRecordsWhenTwoRealtyRecordsGiven() {
		$node = new DOMDocument();
		$openimmo = $node->appendChild(
			$node->createElement('openimmo')
		);
		$vendor = $openimmo->appendChild(
			$node->createElement('anbieter')
		);
		$realtyOne = $vendor->appendChild(
			$node->createElement('immobilie')
		);
		$realtyTwo = $vendor->appendChild(
			$node->createElement('immobilie')
		);

		$result = $this->fixture->isolateRealtyRecords($node);

		$this->assertEquals(
			$result[0]->nodeName,
			'immobilie'
		);

		$this->assertEquals(
			$result[1]->nodeName,
			'immobilie'
		);
	}

	public function testGetConvertedDataWhenSeveralRecordsAreGiven() {
		$node = new DOMDocument();
		$openimmo = $node->appendChild(
			$node->createElement('openimmo')
		);
		$vendor = $openimmo->appendChild(
			$node->createElement('anbieter')
		);
		$realtyOne = $vendor->appendChild(
			$node->createElement('immobilie')
		);
		$realtyTwo = $vendor->appendChild(
			$node->createElement('immobilie')
		);
		$realtyThree = $vendor->appendChild(
			$node->createElement('immobilie')
		);
		$realtyFour = $vendor->appendChild(
			$node->createElement('immobilie')
		);

		$this->assertEquals(
			array(
				array(),
				array(),
				array(),
				array(),
			),
			$this->fixture->getConvertedData($node)
		);
	}

	public function testGetConvertedDataReturnsUniversalDataInEachRecord() {
		$node = new DOMDocument();
		$openimmo = $node->appendChild(
			$node->createElement('openimmo')
		);
		$vendor = $openimmo->appendChild(
			$node->createElement('anbieter')
		);
		$employer = $vendor->appendChild(
			$node->createElement('firma', 'foo')
		);
		$anid = $vendor->appendChild(
			$node->createElement('openimmo_anid', 'bar')
		);
		$realtyOne = $vendor->appendChild(
			$node->createElement('immobilie')
		);
		$immobilieTwo = $vendor->appendChild(
			$node->createElement('immobilie')
		);

		$supposedResult = array(
			'employer' => 'foo',
			'openimmo_anid' => 'bar'
		);
		$result = $this->fixture->getConvertedData($node);

		$this->assertEquals(
			$result[0],
			$supposedResult
		);

		$this->assertEquals(
			$result[1],
			$supposedResult
		);
	}

	public function testGetConvertedDataWhenSeveralPropertiesAreGiven() {
		$node = new DOMDocument();
		$openimmo = $node->appendChild(
			$node->createElement('openimmo')
		);
		$vendor = $openimmo->appendChild(
			$node->createElement('anbieter')
		);
		$realty = $vendor->appendChild(
			$node->createElement('immobilie')
		);
		$geography = $realty->appendChild(
			$node->createElement('geo')
		);
		$street = $geography->appendChild(
			$node->createElement('strasse', 'foobar')
		);
		$zip = $geography->appendChild(
			$node->createElement('plz', 'bar')
		);
		$texts = $realty->appendChild(
			$node->createElement('freitexte')
		);
		$location =  $texts->appendChild(
			$node->createElement('lage', 'foo')
		);
		$realResult = $this->fixture->getConvertedData($node);
		$supposedResult = array(
			array(
			'street' => 'foobar',
			'zip' => 'bar',
			'location' => 'foo'
			)
		);

		ksort($realResult[0]);
		ksort($supposedResult[0]);
		$this->assertEquals(
			$realResult,
			$supposedResult
		);
	}

	public function testGetConvertedDataFetchesInnenCourtage() {
		$node = new DOMDocument();
		$openimmo = $node->appendChild(
			$node->createElement('openimmo')
		);
		$vendor = $openimmo->appendChild(
			$node->createElement('anbieter')
		);
		$realty = $vendor->appendChild(
			$node->createElement('immobilie')
		);
		$costs = $realty->appendChild(
			$node->createElement('preise')
		);
		$innenCourtage = $costs->appendChild(
			$node->createElement('innen_courtage', '1')
		);

		$this->assertEquals(
			array(array('provision' => '1')),
			$this->fixture->getConvertedData($node)
		);
	}

	public function testGetConvertedDataFetchesTotalCourtage() {
		$node = new DOMDocument();
		$openimmo = $node->appendChild(
			$node->createElement('openimmo')
		);
		$vendor = $openimmo->appendChild(
			$node->createElement('anbieter')
		);
		$realty = $vendor->appendChild(
			$node->createElement('immobilie')
		);
		$costs = $realty->appendChild(
			$node->createElement('preise')
		);
		$innenCourtage = $costs->appendChild(
			$node->createElement('innen_courtage', '1')
		);
		$secondCourtage = $costs->appendChild(
			$node->createElement('aussen_courtage', '1')
		);

		$this->assertEquals(
			array(array('provision' => '2')),
			$this->fixture->getConvertedData($node)
		);
	}

	public function testCreateRecordsForImagesIfNodeWithoutImagePathGiven() {
		$node = new DOMDocument();
		$appendix = $node->appendChild(
			$node->createElement('anhang')
		);
		$appendixTitle = $appendix->appendChild(
			$node->createElement('anhangtitel', 'foo')
		);

		$this->assertEquals(
			array(),
			$this->fixture->createRecordsForImages($node)
		);
	}

	public function testCreateRecordsForImagesIfNodeOneImagePathGiven() {
		$node = new DOMDocument();
		$appendix = $node->appendChild(
			$node->createElement('anhang')
		);
		$appendixTitle = $appendix->appendChild(
			$node->createElement('anhangtitel', 'bar')
		);
		$data = $appendix->appendChild(
			$node->createElement('daten')
		);
		$path = $data->appendChild(
			$node->createElement('pfad', 'foo')
		);

		$this->assertEquals(
			array(
				array(
					'caption' => 'bar',
					'image' => 'foo'
				)
			),
			$this->fixture->createRecordsForImages($node)
		);
	}

	public function testCreateRecordsForImagesIfNodeTwoImagePathsGiven() {
		$node = new DOMDocument();
		$appendix = $node->appendChild(
			$node->createElement('anhang')
		);
		$appendixTitle = $appendix->appendChild(
			$node->createElement('anhangtitel', 'bar')
		);
		$data = $appendix->appendChild(
			$node->createElement('daten')
		);
		$path = $data->appendChild(
			$node->createElement('pfad', 'bar')
		);
		$appendix = $node->appendChild(
			$node->createElement('anhang')
		);
		$appendixTitle = $appendix->appendChild(
			$node->createElement('anhangtitel', 'foo')
		);
		$data = $appendix->appendChild(
			$node->createElement('daten')
		);
		$path = $data->appendChild(
			$node->createElement('pfad', 'foo')
		);

		$images = $this->fixture->createRecordsForImages($node);

		$this->assertEquals(
			array(
				'caption' => 'bar',
				'image' => 'bar'
			),
			$images[0]
		);
		$this->assertEquals(
			array(
				'caption' => 'foo',
				'image' => 'foo'
			),
			$images[1]
		);
	}

	public function testFetchDomAttributesIfValidNodeGiven() {
		$node = new DOMDocument();
		$element = $node->appendChild(
			$node->createElement('foo')
		);
		$attribute = $element->setAttributeNode(new DOMAttr('foo', 'bar'));

		$this->assertEquals(
			$this->fixture->fetchDomAttributes($element),
			array('foo' => 'bar')
		);
	}

	public function testFetchDomAttributesIfNodeWithoutAttributesGiven() {
		$node = new DOMDocument();
		$element = $node->appendChild(
			$node->createElement('foo')
		);

		$this->assertEquals(
			$this->fixture->fetchDomAttributes($element),
			array()
		);
	}
}

?>
