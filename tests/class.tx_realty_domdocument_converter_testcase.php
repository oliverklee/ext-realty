<?php
/***************************************************************
* Copyright notice
*
* (c) 2007-2008 Saskia Metzler <saskia@merlin.owl.de>
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

require_once(t3lib_extMgm::extPath('realty').'tests/fixtures/class.tx_realty_domdocument_converter_child.php');
require_once(t3lib_extMgm::extPath('oelib').'class.tx_oelib_configurationProxy.php');

class tx_realty_domdocument_converter_testcase extends tx_phpunit_testcase {
	public function setUp() {
		$this->fixture = new tx_realty_domdocument_converter_child();
	}

	public function tearDown() {
		unset($this->fixture);
	}

	public function testFindFirstGrandchildReturnsGrandchildIfExists() {
		$node = new DOMDocument();
		$parent = $node->appendChild(
			$node->createElement('immobilie')
		);
		$child = $parent->appendChild(
			$node->createElement('child')
		);
		$grandchild = $child->appendChild(
			$node->createElement('grandchild', 'foo')
		);
		$this->fixture->setRawRealtyData($node);

		$result = $this->fixture->findFirstGrandchild(
			'child',
			'grandchild'
		);

		$this->assertEquals(
			$result->nodeValue,
			'foo'
		);
	}

	public function testFindFirstGrandchildReturnsNullIfGrandchildNotExists() {
		$node = new DOMDocument();
		$parent = $node->appendChild(
			$node->createElement('immobilie')
		);
		$child = $parent->appendChild(
			$node->createElement('child')
		);
		$this->fixture->setRawRealtyData($node);

		$this->assertNull(
			$this->fixture->findFirstGrandchild('child', 'grandchild')
		);
	}

	public function testFindFirstGrandchildReturnsNullIfGivenDomnodeIsEmpty() {
		$node = new DOMDocument();
		$parent = $node->appendChild(
			$node->createElement('immobilie')
		);
		$this->fixture->setRawRealtyData($node);

		$this->assertNull(
			$this->fixture->findFirstGrandchild('child', 'grandchild')
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
		$this->fixture->addElementToArray($data, 'foo', 'bar');

		$this->assertEquals(
			$data,
			array('foo' => 'bar')
		);
	}

	public function testAddElementToArrayTwice() {
		$data = array();
		$this->fixture->addElementToArray($data, 'foo', 'foo');
		$this->fixture->addElementToArray($data, 'bar', 'bar');

		$this->assertEquals(
			$data,
			array('foo' => 'foo',
				'bar' => 'bar')
		);
	}

	public function testGetConvertedDataWhenSeveralRecordsGiven() {
		$node = DOMDocument::loadXML(
			'<openimmo>'
				.'<anbieter>'
					.'<immobilie/>'
					.'<immobilie/>'
					.'<immobilie/>'
					.'<immobilie/>'
				.'</anbieter>'
			.'</openimmo>'
		);
		$this->fixture->setRawRealtyData($node);

		$this->assertEquals(
			array(
				array(),
				array(),
				array(),
				array()
			),
			$this->fixture->getConvertedData($node)
		);
	}

	public function testGetConvertedDataWhenSeveralRecordsWithContainContent() {
		$node = DOMDocument::loadXML(
			'<openimmo>'
				.'<anbieter>'
					.'<immobilie>'
						.'<geo>'
							.'<strasse>bar</strasse>'
							.'<plz>bar</plz>'
						.'</geo>'
					.'</immobilie>'
					.'<immobilie>'
						.'<geo>'
							.'<strasse>foo</strasse>'
						.'</geo>'
					.'</immobilie>'
				.'</anbieter>'
			.'</openimmo>'
		);
		$this->fixture->setRawRealtyData($node);

		$this->assertEquals(
			array(
				array('street' => 'bar', 'zip' => 'bar'),
				array('street' => 'foo'),
			),
			$this->fixture->getConvertedData($node)
		);
	}

	public function testGetConvertedDataReturnsUniversalDataInEachRecord() {
		$node = DOMDocument::loadXML(
			'<openimmo>'
				.'<anbieter>'
					.'<firma>foo</firma>'
					.'<openimmo_anid>bar</openimmo_anid>'
					.'<immobilie/>'
					.'<immobilie/>'
				.'</anbieter>'
			.'</openimmo>'
		);
		$this->fixture->setRawRealtyData($node);

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
		$node = DOMDocument::loadXML(
			'<openimmo>'
				.'<anbieter>'
					.'<immobilie>'
						.'<geo>'
							.'<strasse>foobar</strasse>'
							.'<plz>bar</plz>'
						.'</geo>'
						.'<freitexte>'
							.'<lage>foo</lage>'
						.'</freitexte>'
					.'</immobilie>'
				.'</anbieter>'
			.'</openimmo>'
		);
		$this->fixture->setRawRealtyData($node);

		$this->assertEquals(
			$this->fixture->getConvertedData($node),
			array(
				array(
					'street' => 'foobar',
					'zip' => 'bar',
					'location' => 'foo'
				)
			)
		);
	}

	public function testGetConvertedDataSetsPetsTitleIfValueIsStringTrue() {
		global $LANG;

		$node = DOMDocument::loadXML(
			'<openimmo>'
				.'<anbieter>'
					.'<immobilie>'
						.'<verwaltung_objekt>'
							.'<haustiere>true</haustiere>'
						.'</verwaltung_objekt>'
					.'</immobilie>'
				.'</anbieter>'
			.'</openimmo>'
		);
		$this->fixture->setRawRealtyData($node);

		$result = $this->fixture->getConvertedData($node);
		$this->assertEquals(
			$result[0]['pets'],
			$LANG->getLL('label_allowed')
		);
	}

	public function testGetConvertedDataSetsPetsTitleIfValueIsOne() {
		global $LANG;

		$node = DOMDocument::loadXML(
			'<openimmo>'
				.'<anbieter>'
					.'<immobilie>'
						.'<verwaltung_objekt>'
							.'<haustiere>1</haustiere>'
						.'</verwaltung_objekt>'
					.'</immobilie>'
				.'</anbieter>'
			.'</openimmo>'
		);
		$this->fixture->setRawRealtyData($node);

		$result = $this->fixture->getConvertedData($node);
		$this->assertEquals(
			$result[0]['pets'],
			$LANG->getLL('label_allowed')
		);
	}

	public function testGetConvertedDataSetsPetsTitleIfValueIsBooleanTrue() {
		global $LANG;

		$node = DOMDocument::loadXML(
			'<openimmo>'
				.'<anbieter>'
					.'<immobilie>'
						.'<verwaltung_objekt>'
							.'<haustiere>'.true.'</haustiere>'
						.'</verwaltung_objekt>'
					.'</immobilie>'
				.'</anbieter>'
			.'</openimmo>'
		);
		$this->fixture->setRawRealtyData($node);

		$result = $this->fixture->getConvertedData($node);
		$this->assertEquals(
			$result[0]['pets'],
			$LANG->getLL('label_allowed')
		);
	}

	public function testGetConvertedDataSetsPetsGermanTitleIfLanguageIsGerman() {
		tx_oelib_configurationProxy::getInstance('realty')->
			setConfigurationValueString('cliLanguage', 'de');

		$node = DOMDocument::loadXML(
			'<openimmo>'
				.'<anbieter>'
					.'<immobilie>'
						.'<verwaltung_objekt>'
							.'<haustiere>true</haustiere>'
						.'</verwaltung_objekt>'
					.'</immobilie>'
				.'</anbieter>'
			.'</openimmo>'
		);
		$this->fixture->setRawRealtyData($node);

		$result = $this->fixture->getConvertedData($node);
		$this->assertEquals(
			$result[0]['pets'],
			'Erlaubt'
		);
	}

	public function testGetConvertedDataSetsPetsEnglishTitleIfLanguageIsEnglish() {
		tx_oelib_configurationProxy::getInstance('realty')->
			setConfigurationValueString('cliLanguage', 'en');

		$node = DOMDocument::loadXML(
			'<openimmo>'
				.'<anbieter>'
					.'<immobilie>'
						.'<verwaltung_objekt>'
							.'<haustiere>true</haustiere>'
						.'</verwaltung_objekt>'
					.'</immobilie>'
				.'</anbieter>'
			.'</openimmo>'
		);
		$this->fixture->setRawRealtyData($node);

		$result = $this->fixture->getConvertedData($node);
		$this->assertEquals(
			$result[0]['pets'],
			'Allowed'
		);
	}

	public function testGetConvertedDataSetsPetsEnglishTitleIfLanguageKeyIsInvalid() {
		tx_oelib_configurationProxy::getInstance('realty')->
			setConfigurationValueString('cliLanguage', 'xy');

		$node = DOMDocument::loadXML(
			'<openimmo>'
				.'<anbieter>'
					.'<immobilie>'
						.'<verwaltung_objekt>'
							.'<haustiere>true</haustiere>'
						.'</verwaltung_objekt>'
					.'</immobilie>'
				.'</anbieter>'
			.'</openimmo>'
		);
		$this->fixture->setRawRealtyData($node);

		$result = $this->fixture->getConvertedData($node);
		$this->assertEquals(
			$result[0]['pets'],
			'Allowed'
		);
	}

	public function testGetConvertedDataSetsPetsEnglishTitleIfLanguageKeyIsEmpty() {
		tx_oelib_configurationProxy::getInstance('realty')->
			setConfigurationValueString('cliLanguage', '');

		$node = DOMDocument::loadXML(
			'<openimmo>'
				.'<anbieter>'
					.'<immobilie>'
						.'<verwaltung_objekt>'
							.'<haustiere>true</haustiere>'
						.'</verwaltung_objekt>'
					.'</immobilie>'
				.'</anbieter>'
			.'</openimmo>'
		);
		$this->fixture->setRawRealtyData($node);

		$result = $this->fixture->getConvertedData($node);
		$this->assertEquals(
			$result[0]['pets'],
			'Allowed'
		);
	}

	public function testGetConvertedDataSubstitudesSurplusDecimals() {
		$node = DOMDocument::loadXML(
			'<openimmo>'
				.'<anbieter>'
					.'<immobilie>'
						.'<geo>'
							.'<strasse>1.00</strasse>'
						.'</geo>'
					.'</immobilie>'
				.'</anbieter>'
			.'</openimmo>'
		);
		$this->fixture->setRawRealtyData($node);

		$this->assertEquals(
			$this->fixture->getConvertedData($node),
			array(array('street' => '1'))
		);
	}

	public function testGetConvertedDataNotSubstitudesNonSurplusDecimals() {
		$node = DOMDocument::loadXML(
			'<openimmo>'
				.'<anbieter>'
					.'<immobilie>'
						.'<geo>'
							.'<strasse>1.11</strasse>'
						.'</geo>'
					.'</immobilie>'
				.'</anbieter>'
			.'</openimmo>'
		);
		$this->fixture->setRawRealtyData($node);

		$this->assertEquals(
			$this->fixture->getConvertedData($node),
			array(array('street' => '1.11'))
		);
	}

	public function testGetConvertedDataFetchesAltenativeContactEmail() {
		$node = DOMDocument::loadXML(
			'<openimmo>'
				.'<anbieter>'
					.'<immobilie>'
						.'<kontaktperson>'
							.'<email_direkt>foo</email_direkt>'
						.'</kontaktperson>'
					.'</immobilie>'
				.'</anbieter>'
			.'</openimmo>'
		);
		$this->fixture->setRawRealtyData($node);


		$this->assertEquals(
			$this->fixture->getConvertedData($node),
			array(array('contact_email' => 'foo'))
		);
	}

	public function testGetConvertedDataReplacesBooleanStringsWithTrueBooleans() {
		$node = DOMDocument::loadXML(
			'<openimmo>'
				.'<anbieter>'
					.'<immobilie>'
						.'<geo>'
							.'<strasse>true</strasse>'
							.'<plz>false</plz>'
						.'</geo>'
						.'<freitexte>'
							.'<lage>TRUE</lage>'
						.'</freitexte>'
					.'</immobilie>'
				.'</anbieter>'
			.'</openimmo>'
		);
		$this->fixture->setRawRealtyData($node);

		$this->assertEquals(
			$this->fixture->getConvertedData($node),
			array(
				array(
					'street' => true,
					'zip' => false,
					'location' => true
				)
			)
		);
	}

	public function testCreateRecordsForImagesIfNodeWithoutImagePathGiven() {
		$node = DOMDocument::loadXML(
			'<immobilie>'
				.'<anhang>'
					.'<anhangtitel>foo</anhangtitel>'
				.'</anhang>'
			.'</immobilie>'
		);
		$this->fixture->setRawRealtyData($node);

		$this->assertEquals(
			array(),
			$this->fixture->createRecordsForImages()
		);
	}

	public function testCreateRecordsForImagesIfNodeOneImagePathGiven() {
		$node = DOMDocument::loadXML(
			'<immobilie>'
				.'<anhang>'
					.'<anhangtitel>bar</anhangtitel>'
					.'<daten>'
						.'<pfad>foo</pfad>'
					.'</daten>'
				.'</anhang>'
			.'</immobilie>'
		);
		$this->fixture->setRawRealtyData($node);

		$this->assertEquals(
			array(
				array(
					'caption' => 'bar',
					'image' => 'foo'
				)
			),
			$this->fixture->createRecordsForImages()
		);
	}

	public function testCreateRecordsForImagesIfNodeTwoImagePathsGiven() {
		$node = DOMDocument::loadXML(
			'<immobilie>'
				.'<anhang>'
					.'<anhangtitel>bar</anhangtitel>'
					.'<daten>'
						.'<pfad>bar</pfad>'
					.'</daten>'
				.'</anhang>'
				.' <anhang>'
					.'<anhangtitel>foo</anhangtitel>'
					.'<daten>'
						.'<pfad>foo</pfad>'
					.'</daten>'
				.'</anhang>'
			.'</immobilie>'
		);
		$this->fixture->setRawRealtyData($node);

		$images = $this->fixture->createRecordsForImages();
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

	public function testCreateRecordsForImagesOfTwoRealtyObjectsInOneFile() {
		$node = DOMDocument::loadXML(
			'<openimmo>'
				.'<immobilie>'
					.'<anhang>'
						.'<anhangtitel>bar</anhangtitel>'
						.'<daten>'
							.'<pfad>bar</pfad>'
						.'</daten>'
					.'</anhang>'
				.'</immobilie>'
				.'<immobilie>'
					.' <anhang>'
						.'<anhangtitel>foo</anhangtitel>'
						.'<daten>'
							.'<pfad>foo</pfad>'
						.'</daten>'
					.'</anhang>'
				.'</immobilie>'
			.'</openimmo>'
		);
		$this->fixture->setRawRealtyData($node);

		$this->assertTrue(
			count($this->fixture->createRecordsForImages()) == 1
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
