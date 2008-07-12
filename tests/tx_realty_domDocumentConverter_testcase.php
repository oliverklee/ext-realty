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
 * Unit tests for the tx_realty_domDocumentConverter class in the 'realty'
 * extension.
 *
 * @package		TYPO3
 * @subpackage	tx_realty
 *
 * @author		Saskia Metzler <saskia@merlin.owl.de>
 */

require_once(t3lib_extMgm::extPath('oelib').'class.tx_oelib_configurationProxy.php');

require_once(t3lib_extMgm::extPath('realty').'lib/class.tx_realty_translator.php');
require_once(t3lib_extMgm::extPath('realty').'tests/fixtures/class.tx_realty_domDocumentConverterChild.php');

class tx_realty_domDocumentConverter_testcase extends tx_phpunit_testcase {
	/** instance to be tested */
	private $fixture;

	public function setUp() {
		$this->fixture = new tx_realty_domDocumentConverterChild();
	}

	public function tearDown() {
		unset($this->fixture);
	}

	public function testFindFirstGrandchildReturnsGrandchildIfItExists() {
		$this->setRawDataToConvert(
			'<immobilie>'
				.'<child>'
					.'<grandchild>foo</grandchild>'
				.'</child>'
			.'</immobilie>'
		);

		$this->assertEquals(
			'foo',
			$this->fixture->findFirstGrandchild('child', 'grandchild')->nodeValue
		);
	}

	public function testFindFirstGrandchildReturnsNullIfTheGrandchildDoesNotExists() {
		$this->setRawDataToConvert(
			'<immobilie>'
				.'<child/>'
			.'</immobilie>'
		);

		$this->assertNull(
			$this->fixture->findFirstGrandchild('child', 'grandchild')
		);
	}

	public function testFindFirstGrandchildReturnsNullIfTheGivenDomnodeIsEmpty() {
		$this->setRawDataToConvert(
			'<immobilie/>'
		);

		$this->assertNull(
			$this->fixture->findFirstGrandchild('child', 'grandchild')
		);
	}

	public function testTheFirstGrandchildIsFoundAlthoughTheSecondChildAndItsChildAlsoMatch() {
		$this->setRawDataToConvert(
			'<immobilie>'
				.'<child>'
					.'<grandchild>foo</grandchild>'
				.'</child>'
				.'<child>'
					.'<grandchild>bar</grandchild>'
				.'</child>'
			.'</immobilie>'
		);

		$this->assertEquals(
			'foo',
			$this->fixture->findFirstGrandchild('child', 'grandchild')->nodeValue
		);
	}

	public function testTheFirstGrandchildIsFoundAlthoughTheFirstChildHasTwoMatchingChildren() {
		$this->setRawDataToConvert(
			'<immobilie>'
				.'<child>'
					.'<grandchild>foo</grandchild>'
					.'<grandchild>bar</grandchild>'
				.'</child>'
			.'</immobilie>'
		);

		$this->assertEquals(
			'foo',
			$this->fixture->findFirstGrandchild('child', 'grandchild')->nodeValue
		);
	}

	public function testGetNodeNameDoesNotChangeNodeNameWithoutXmlNamespace() {
		$node = new DOMDocument();
		$child = $node->appendChild(
			$node->createElement('foo')
		);

		$this->assertEquals(
			'foo',
			$this->fixture->getNodeName($child)
		);
	}

	public function testGetNodeNameReturnsNameWithoutXmlNamespaceWhenNameWithXmlNamespaceGiven() {
		$node = new DOMDocument();
		$child = $node->appendChild(
			$node->createElement('prefix:foo')
		);

		$this->assertEquals(
			'foo',
			$this->fixture->getNodeName($child)
		);
	}

	public function testAddOneElementToTheRealtyDataArray() {
		$data = array();
		$this->fixture->addElementToArray($data, 'foo', 'bar');

		$this->assertEquals(
			array('foo' => 'bar'),
			$data
		);
	}

	public function testAddTwoElementsToTheRealtyDataArray() {
		$data = array();
		$this->fixture->addElementToArray($data, 'foo', 'foo');
		$this->fixture->addElementToArray($data, 'bar', 'bar');

		$this->assertEquals(
			array('foo' => 'foo', 'bar' => 'bar'),
			$data
		);
	}

	public function testAddOneElementTwiceToTheRealtyDataArray() {
		$data = array();
		$this->fixture->addElementToArray($data, 'foo', 'foo');
		$this->fixture->addElementToArray($data, 'foo', 'bar');

		$this->assertEquals(
			array('foo' => 'bar'),
			$data
		);
	}

	public function testGetConvertedDataWhenNoRecordsAreGiven() {
		$node = $this->setRawDataToConvert(
			'<openimmo>'
				.'<anbieter/>'
			.'</openimmo>'
		);

		$this->assertEquals(
			array(),
			$this->fixture->getConvertedData($node)
		);
	}

	public function testGetConvertedDataWhenOneRecordIsGiven() {
		$node = $this->setRawDataToConvert(
			'<openimmo>'
				.'<anbieter>'
					.'<immobilie/>'
				.'</anbieter>'
			.'</openimmo>'
		);

		$this->assertEquals(
			array(array()),
			$this->fixture->getConvertedData($node)
		);
	}

	public function testGetConvertedDataWhenSeveralRecordsAreGiven() {
		$node = $this->setRawDataToConvert(
			'<openimmo>'
				.'<anbieter>'
					.'<immobilie/>'
					.'<immobilie/>'
					.'<immobilie/>'
					.'<immobilie/>'
				.'</anbieter>'
			.'</openimmo>'
		);

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

	public function testGetConvertedDataWhenSeveralRecordsAreGivenAndContainContent() {
		$node = $this->setRawDataToConvert(
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

		$this->assertEquals(
			array(
				array('street' => 'bar', 'zip' => 'bar'),
				array('street' => 'foo'),
			),
			$this->fixture->getConvertedData($node)
		);
	}

	public function testGetConvertedDataReturnsUniversalDataInEachRecord() {
		$node = $this->setRawDataToConvert(
			'<openimmo>'
				.'<anbieter>'
					.'<firma>foo</firma>'
					.'<openimmo_anid>bar</openimmo_anid>'
					.'<immobilie/>'
					.'<immobilie/>'
				.'</anbieter>'
			.'</openimmo>'
		);

		$universalData = array(
			'employer' => 'foo',
			'openimmo_anid' => 'bar'
		);
		$result = $this->fixture->getConvertedData($node);

		$this->assertEquals(
			$universalData,
			$result[0]
		);
		$this->assertEquals(
			$universalData,
			$result[1]
		);
	}

	public function testGetConvertedDataWhenSeveralPropertiesAreGiven() {
		$node = $this->setRawDataToConvert(
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

		$this->assertEquals(
			array(
				array(
					'street' => 'foobar',
					'zip' => 'bar',
					'location' => 'foo'
				)
			),
			$this->fixture->getConvertedData($node)
		);
	}

	public function testGetConvertedDataSetsLocalizedPetsTitleIfValueIsStringTrue() {
		$node = $this->setRawDataToConvert(
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

		$result = $this->fixture->getConvertedData($node);
		$this->assertEquals(
			t3lib_div::makeInstance('tx_realty_translator')->translate('label_allowed'),
			$result[0]['pets']
		);
	}

	public function testGetConvertedDataSetsLocalizedPetsTitleIfValueIsOne() {
		$node = $this->setRawDataToConvert(
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

		$result = $this->fixture->getConvertedData($node);
		$this->assertEquals(
			t3lib_div::makeInstance('tx_realty_translator')->translate('label_allowed'),
			$result[0]['pets']
		);
	}

	public function testGetConvertedDataSubstitudesSurplusDecimalsWhenAPositiveNumberIsGiven() {
		$node = $this->setRawDataToConvert(
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

		$this->assertEquals(
			array(array('street' => '1')),
			$this->fixture->getConvertedData($node)
		);
	}

	public function testGetConvertedDataSubstitudesSurplusDecimalsWhenANegativeNumberIsGiven() {
		$node = $this->setRawDataToConvert(
			'<openimmo>'
				.'<anbieter>'
					.'<immobilie>'
						.'<geo>'
							.'<strasse>-1.00</strasse>'
						.'</geo>'
					.'</immobilie>'
				.'</anbieter>'
			.'</openimmo>'
		);

		$this->assertEquals(
			array(array('street' => '-1')),
			$this->fixture->getConvertedData($node)
		);
	}

	public function testGetConvertedDataSubstitudesTwoSurplusDecimalsWhenZeroIsGiven() {
		$node = $this->setRawDataToConvert(
			'<openimmo>'
				.'<anbieter>'
					.'<immobilie>'
						.'<geo>'
							.'<strasse>0.00</strasse>'
						.'</geo>'
					.'</immobilie>'
				.'</anbieter>'
			.'</openimmo>'
		);

		$this->assertEquals(
			array(array('street' => '0')),
			$this->fixture->getConvertedData($node)
		);
	}

	public function testGetConvertedDataSubstitudesOneSurplusDecimalWhenZeroIsGiven() {
		$node = $this->setRawDataToConvert(
			'<openimmo>'
				.'<anbieter>'
					.'<immobilie>'
						.'<geo>'
							.'<strasse>0.0</strasse>'
						.'</geo>'
					.'</immobilie>'
				.'</anbieter>'
			.'</openimmo>'
		);

		$this->assertEquals(
			array(array('street' => '0')),
			$this->fixture->getConvertedData($node)
		);
	}

	public function testGetConvertedDataNotSubstitudesTwoNonSurplusDecimalsFromAPositiveNumber() {
		$node = $this->setRawDataToConvert(
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

		$this->assertEquals(
			array(array('street' => '1.11')),
			$this->fixture->getConvertedData($node)
		);
	}

	public function testGetConvertedDataNotSubstitudesTwoNonSurplusDecimalsFromANegativeNumber() {
		$node = $this->setRawDataToConvert(
			'<openimmo>'
				.'<anbieter>'
					.'<immobilie>'
						.'<geo>'
							.'<strasse>-1.11</strasse>'
						.'</geo>'
					.'</immobilie>'
				.'</anbieter>'
			.'</openimmo>'
		);

		$this->assertEquals(
			array(array('street' => '-1.11')),
			$this->fixture->getConvertedData($node)
		);
	}

	public function testGetConvertedDataNotSubstitudesOneNonSurplusDecimals() {
		$node = $this->setRawDataToConvert(
			'<openimmo>'
				.'<anbieter>'
					.'<immobilie>'
						.'<geo>'
							.'<strasse>1.1</strasse>'
						.'</geo>'
					.'</immobilie>'
				.'</anbieter>'
			.'</openimmo>'
		);

		$this->assertEquals(
			array(array('street' => '1.1')),
			$this->fixture->getConvertedData($node)
		);
	}

	public function testGetConvertedDataFetchesAlternativeContactEmail() {
		$node = $this->setRawDataToConvert(
			'<openimmo>'
				.'<anbieter>'
					.'<immobilie>'
						.'<kontaktperson>'
							.'<email_direkt>any-email@address.org</email_direkt>'
						.'</kontaktperson>'
					.'</immobilie>'
				.'</anbieter>'
			.'</openimmo>'
		);

		$this->assertEquals(
			array(array('contact_email' => 'any-email@address.org')),
			$this->fixture->getConvertedData($node)
		);
	}

	public function testGetConvertedDataFetchesPhoneNumber() {
		$node = DOMDocument::loadXML(
			'<openimmo>'
				.'<anbieter>'
					.'<immobilie>'
						.'<kontaktperson>'
							.'<tel_zentrale>1234567</tel_zentrale>'
						.'</kontaktperson>'
					.'</immobilie>'
				.'</anbieter>'
			.'</openimmo>'
		);
		$this->fixture->setRawRealtyData($node);


		$this->assertEquals(
			$this->fixture->getConvertedData($node),
			array(array('contact_phone' => '1234567'))
		);
	}

	public function testGetConvertedDataFetchesAlternativePhoneNumber() {
		$node = DOMDocument::loadXML(
			'<openimmo>'
				.'<anbieter>'
					.'<immobilie>'
						.'<kontaktperson>'
							.'<tel_privat>1234567</tel_privat>'
						.'</kontaktperson>'
					.'</immobilie>'
				.'</anbieter>'
			.'</openimmo>'
		);
		$this->fixture->setRawRealtyData($node);


		$this->assertEquals(
			$this->fixture->getConvertedData($node),
			array(array('contact_phone' => '1234567'))
		);
	}

	public function testGetConvertedDataReplacesLowercasedBooleanStringsWithTrueBooleans() {
		$node = $this->setRawDataToConvert(
			'<openimmo>'
				.'<anbieter>'
					.'<immobilie>'
						.'<geo>'
							.'<strasse>true</strasse>'
							.'<plz>false</plz>'
						.'</geo>'
					.'</immobilie>'
				.'</anbieter>'
			.'</openimmo>'
		);

		$this->assertEquals(
			array(
				array(
					'street' => true,
					'zip' => false
				)
			),
			$this->fixture->getConvertedData($node)
		);
	}

	public function testGetConvertedDataReplacesUppercasedBooleanStringsWithTrueBooleans() {
		$node = $this->setRawDataToConvert(
			'<openimmo>'
				.'<anbieter>'
					.'<immobilie>'
						.'<geo>'
							.'<strasse>TRUE</strasse>'
							.'<plz>FALSE</plz>'
						.'</geo>'
					.'</immobilie>'
				.'</anbieter>'
			.'</openimmo>'
		);

		$this->assertEquals(
			array(
				array(
					'street' => true,
					'zip' => false
				)
			),
			$this->fixture->getConvertedData($node)
		);
	}

	public function testGetConvertedDataReplacesQuotedBooleanStringsWithTrueBooleans() {
		$node = $this->setRawDataToConvert(
			'<openimmo>'
				.'<anbieter>'
					.'<immobilie>'
						.'<geo>'
							.'<strasse>"true"</strasse>'
							.'<plz>"false"</plz>'
						.'</geo>'
					.'</immobilie>'
				.'</anbieter>'
			.'</openimmo>'
		);

		$this->assertEquals(
			array(
				array(
					'street' => true,
					'zip' => false
				)
			),
			$this->fixture->getConvertedData($node)
		);
	}

	public function testGetConvertedDataGetsEstateSize() {
		$node = DOMDocument::loadXML(
			'<openimmo>'
				.'<anbieter>'
					.'<immobilie>'
						.'<flaechen>'
							.'<grundstuecksflaeche>12345</grundstuecksflaeche>'
						.'</flaechen>'
					.'</immobilie>'
				.'</anbieter>'
			.'</openimmo>'
		);
		$this->fixture->setRawRealtyData($node);

		$this->assertEquals(
			array(array('estate_size' => '12345')),
			$this->fixture->getConvertedData($node)
		);
	}

	public function testCreateRecordsForImagesIfOneImageAppendixWithoutAnImagePathIsGiven() {
		$node = $this->setRawDataToConvert(
			'<immobilie>'
				.'<anhang>'
					.'<anhangtitel>foo</anhangtitel>'
				.'</anhang>'
			.'</immobilie>'
		);

		$this->assertEquals(
			array(),
			$this->fixture->createRecordsForImages()
		);
	}

	public function testCreateRecordsForImagesIfOneImageValidImageAppendixIsGiven() {
		$this->setRawDataToConvert(
			'<immobilie>'
				.'<anhang>'
					.'<anhangtitel>bar</anhangtitel>'
					.'<daten>'
						.'<pfad>foo.jpg</pfad>'
					.'</daten>'
				.'</anhang>'
			.'</immobilie>'
		);

		$this->assertEquals(
			array(
				array(
					'caption' => 'bar',
					'image' => 'foo.jpg'
				)
			),
			$this->fixture->createRecordsForImages()
		);
	}

	public function testCreateRecordsForImagesIfOneImageImageAppendixWithoutTitleIsGiven() {
		$this->setRawDataToConvert(
			'<immobilie>'
				.'<anhang>'
					.'<anhangtitel/>'
					.'<daten>'
						.'<pfad>foo.jpg</pfad>'
					.'</daten>'
				.'</anhang>'
			.'</immobilie>'
		);

		$this->assertEquals(
			array(
				array(
					'caption' => '',
					'image' => 'foo.jpg'
				)
			),
			$this->fixture->createRecordsForImages()
		);
	}

	public function testCreateRecordsForImagesIfTwoValidImageAppendixesAreGiven() {
		$this->setRawDataToConvert(
			'<immobilie>'
				.'<anhang>'
					.'<anhangtitel>bar</anhangtitel>'
					.'<daten>'
						.'<pfad>bar.jpg</pfad>'
					.'</daten>'
				.'</anhang>'
				.' <anhang>'
					.'<anhangtitel>foo</anhangtitel>'
					.'<daten>'
						.'<pfad>foo.jpg</pfad>'
					.'</daten>'
				.'</anhang>'
			.'</immobilie>'
		);
		$images = $this->fixture->createRecordsForImages();

		$this->assertEquals(
			array(
				'caption' => 'bar',
				'image' => 'bar.jpg'
			),
			$images[0]
		);
		$this->assertEquals(
			array(
				'caption' => 'foo',
				'image' => 'foo.jpg'
			),
			$images[1]
		);
	}

	public function testCreateRecordsForImagesIfTwoImageAppendixesWithTheSameTitleAreGiven() {
		$this->setRawDataToConvert(
			'<immobilie>'
				.'<anhang>'
					.'<anhangtitel>bar</anhangtitel>'
					.'<daten>'
						.'<pfad>bar.jpg</pfad>'
					.'</daten>'
				.'</anhang>'
				.' <anhang>'
					.'<anhangtitel>bar</anhangtitel>'
					.'<daten>'
						.'<pfad>foo.jpg</pfad>'
					.'</daten>'
				.'</anhang>'
			.'</immobilie>'
		);
		$images = $this->fixture->createRecordsForImages();

		$this->assertEquals(
			array(
				'caption' => 'bar',
				'image' => 'bar.jpg'
			),
			$images[0]
		);
		$this->assertEquals(
			array(
				'caption' => 'bar',
				'image' => 'foo.jpg'
			),
			$images[1]
		);
	}

	public function testCreateRecordsForImagesOfTwoRealtyObjectsInOneFile() {
		$this->setRawDataToConvert(
			'<openimmo>'
				.'<immobilie>'
					.'<anhang>'
						.'<anhangtitel>bar</anhangtitel>'
						.'<daten>'
							.'<pfad>bar.jpg</pfad>'
						.'</daten>'
					.'</anhang>'
				.'</immobilie>'
				.'<immobilie>'
					.' <anhang>'
						.'<anhangtitel>foo</anhangtitel>'
						.'<daten>'
							.'<pfad>foo.jpg</pfad>'
						.'</daten>'
					.'</anhang>'
				.'</immobilie>'
			.'</openimmo>'
		);

		$this->assertTrue(
			count($this->fixture->createRecordsForImages()) == 1
		);
	}

	public function testGetConvertedDataImportsAttributeValuesCorrectly() {
		$node = $this->setRawDataToConvert(
			'<openimmo>'
				.'<anbieter>'
					.'<immobilie>'
						.'<ausstattung>'
							.'<fahrstuhl PERSONEN="false"/>'
							.'<kueche EBK="true"/>'
						.'</ausstattung>'
					.'</immobilie>'
				.'</anbieter>'
			.'</openimmo>'
		);

		$this->assertEquals(
			array(
				array(
					'elevator' => false,
					'fitted_kitchen' => true
				)
			),
			$this->fixture->getConvertedData($node)
		);
	}

	public function testGetConvertedDataImportsTheLanguage() {
		$node = $this->setRawDataToConvert(
			'<openimmo>'
				.'<anbieter>'
					.'<immobilie>'
						.'<verwaltung_objekt>'
							.'<user_defined_anyfield>'
								.'<sprache>foo</sprache>'
							.'</user_defined_anyfield>'
						.'</verwaltung_objekt>'
					.'</immobilie>'
				.'</anbieter>'
			.'</openimmo>'
		);

		$this->assertEquals(
			array(array('language' => 'foo')),
			$this->fixture->getConvertedData($node)
		);
	}

	public function testGetConvertedDataImportsTheValueForNewBuilding() {
		$node = $this->setRawDataToConvert(
			'<openimmo>'
				.'<anbieter>'
					.'<immobilie>'
						.'<zustand_angaben>'
							.'<alter ALTER_ATTR="neubau" />'
						.'</zustand_angaben>'
					.'</immobilie>'
				.'</anbieter>'
			.'</openimmo>'
		);

		$this->assertEquals(
			array(array('old_or_new_building' => 1)),
			$this->fixture->getConvertedData($node)
		);
	}

	public function testGetConvertedDataImportsTheValueForOldBuilding() {
		$node = $this->setRawDataToConvert(
			'<openimmo>'
				.'<anbieter>'
					.'<immobilie>'
						.'<zustand_angaben>'
							.'<alter ALTER_ATTR="altbau" />'
						.'</zustand_angaben>'
					.'</immobilie>'
				.'</anbieter>'
			.'</openimmo>'
		);

		$this->assertEquals(
			array(array('old_or_new_building' => 2)),
			$this->fixture->getConvertedData($node)
		);
	}

	public function testConvertedDataDoesNotContainTheKeyOldOrNewBuildingIfNoValueWasSet() {
		$node = $this->setRawDataToConvert(
			'<openimmo>'
				.'<anbieter>'
					.'<immobilie>'
						.'<zustand_angaben>'
							.'<alter ALTER_ATTR="" />'
						.'</zustand_angaben>'
					.'</immobilie>'
				.'</anbieter>'
			.'</openimmo>'
		);

		$this->assertEquals(
			array(array()),
			$this->fixture->getConvertedData($node)
		);
	}

	public function testFetchDomAttributesIfValidNodeGiven() {
		$node = new DOMDocument();
		$element = $node->appendChild(
			$node->createElement('foo')
		);
		$attribute = $element->setAttributeNode(new DOMAttr('foo', 'bar'));

		$this->assertEquals(
			array('foo' => 'bar'),
			$this->fixture->fetchDomAttributes($element)
		);
	}

	public function testFetchDomAttributesIfNodeWithoutAttributesGiven() {
		$node = new DOMDocument();
		$element = $node->appendChild(
			$node->createElement('foo')
		);

		$this->assertEquals(
			array(),
			$this->fixture->fetchDomAttributes($element)
		);
	}


	/////////////////////
	// Utlity functions
	/////////////////////

	/**
	 * Loads an XML string, sets the raw realty data and returns a DOMDocument
	 * of the provided string.
	 *
	 * @param	string		XML string to set for converting, must contain
	 * 						wellformed XML, must not be empty
	 *
	 * @return	DOMDocument		DOMDocument of the provided XML string
	 */
	private function setRawDataToConvert($xmlString) {
		$loadedXml = DOMDocument::loadXML($xmlString);
		$this->fixture->setRawRealtyData($loadedXml);

		return $loadedXml;
	}
}
?>
