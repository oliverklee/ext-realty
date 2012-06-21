<?php
/***************************************************************
* Copyright notice
*
* (c) 2007-2012 Saskia Metzler <saskia@merlin.owl.de>
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
 * Unit tests for the tx_realty_domDocumentConverter class in the "realty"
 * extension.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 * @author Benjamin Schulte <benj@minschulte.de>
 */
class tx_realty_Import_DomDocumentConverterTest extends tx_phpunit_testcase {
	/**
	 * @var tx_realty_domDocumentConverter
	 */
	private $fixture;

	/**
	 * static_info_tables UID of Germany
	 *
	 * @var integer
	 */
	const DE = 54;

	/**
	 * backup of $GLOBALS['TYPO3_CONF_VARS']['GFX']
	 *
	 * @var array
	 */
	private $graphicsConfigurationBackup;

	public function setUp() {
		$this->graphicsConfigurationBackup = $GLOBALS['TYPO3_CONF_VARS']['GFX'];
		$GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext']
			= 'gif,jpg,jpeg,tif,tiff,bmp,pcx,tga,png,pdf,ai';

		$this->fixture = new tx_realty_domDocumentConverterChild(
			new tx_realty_fileNameMapper()
		);
	}

	public function tearDown() {
		$this->fixture->__destruct();

		unset($this->fixture);

		$GLOBALS['TYPO3_CONF_VARS']['GFX'] = $this->graphicsConfigurationBackup;
	}


	/////////////////////
	// Utlity functions
	/////////////////////

	/**
	 * Loads an XML string, sets the raw realty data and returns a DOMDocument
	 * of the provided string.
	 *
	 * @param string $xmlString XML string to set for converting, must contain wellformed XML, must not be empty
	 *
	 * @return DOMDocument DOMDocument of the provided XML string
	 */
	private function setRawDataToConvert($xmlString) {
		$loadedXml = DOMDocument::loadXML($xmlString);
		$this->fixture->setRawRealtyData($loadedXml);

		return $loadedXml;
	}


	/////////////////////////////////////
	// Testing the domDocumentConverter
	/////////////////////////////////////

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


	//////////////////////////////////////
	// Tests concerning getConvertedData
	//////////////////////////////////////

	/**
	 * @test
	 */
	public function testGetConvertedDataForNoRecordsReturnsEmptyArray() {
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

	/**
	 * @test
	 */
	public function getConvertedDataForOneEmptyRecordReturnsDefaultValues() {
		$node = $this->setRawDataToConvert(
			'<openimmo>'
				.'<anbieter>'
					.'<immobilie/>'
				.'</anbieter>'
			.'</openimmo>'
		);

		$this->assertEquals(
			array(
				array(
					'sales_area' => 0.0,
					'other_area' => 0.0,
					'window_bank' => 0.0,
					'rental_income_target' => 0.0,
				),
			),
			$this->fixture->getConvertedData($node)
		);
	}

	/**
	 * @test
	 */
	public function getConvertedDataForSeveralEmptyRecordsReturnsArrayOfDefaultValues() {
		$node = $this->setRawDataToConvert(
			'<openimmo>' .
				'<anbieter>' .
					'<immobilie/>' .
					'<immobilie/>' .
				'</anbieter>' .
			'</openimmo>'
		);

		$this->assertEquals(
			array(
				array(
					'sales_area' => 0.0,
					'other_area' => 0.0,
					'window_bank' => 0.0,
					'rental_income_target' => 0.0,
				),
				array(
					'sales_area' => 0.0,
					'other_area' => 0.0,
					'window_bank' => 0.0,
					'rental_income_target' => 0.0,
				),
			),
			$this->fixture->getConvertedData($node)
		);
	}

	/**
	 * @test
	 */
	public function getConvertedDataCanImportSeveralObjects() {
		$node = $this->setRawDataToConvert(
			'<openimmo>' .
				'<anbieter>' .
					'<immobilie>' .
						'<freitexte>' .
							'<objekttitel>foo</objekttitel>' .
						'</freitexte>' .
					'</immobilie>' .
					'<immobilie>' .
						'<freitexte>' .
							'<objekttitel>bar</objekttitel>' .
						'</freitexte>' .
					'</immobilie>' .
				'</anbieter>' .
			'</openimmo>'
		);

		$importedData = $this->fixture->getConvertedData($node);
		$this->assertEquals(
			'foo',
			$importedData[0]['title'],
			'The first object is missing.'
		);
		$this->assertEquals(
			'bar',
			$importedData[1]['title'],
			'The second object is missing.'
		);
	}

	/**
	 * @test
	 */
	public function getConvertedDataReadsObjectTitle() {
		$node = $this->setRawDataToConvert(
			'<openimmo>' .
				'<anbieter>' .
					'<immobilie>' .
						'<freitexte>' .
							'<objekttitel>klein und teuer</objekttitel>' .
						'</freitexte>' .
					'</immobilie>' .
				'</anbieter>' .
			'</openimmo>'
		);

		$importedData = $this->fixture->getConvertedData($node);
		$this->assertEquals(
			'klein und teuer',
			$importedData[0]['title']
		);
	}

	/**
	 * @test
	 */
	public function getConvertedDataReplacesSingleLinefeedInObjectTitleWithSpace() {
		$node = $this->setRawDataToConvert(
			'<openimmo>' .
				'<anbieter>' .
					'<immobilie>' .
						'<freitexte>' .
							'<objekttitel>foo' . LF . 'bar</objekttitel>' .
						'</freitexte>' .
					'</immobilie>' .
				'</anbieter>' .
			'</openimmo>'
		);

		$importedData = $this->fixture->getConvertedData($node);
		$this->assertEquals(
			'foo bar',
			$importedData[0]['title']
		);
	}

	/**
	 * @test
	 */
	public function getConvertedDataReplacesDoubleLinefeedInObjectTitleWithSpace() {
		$node = $this->setRawDataToConvert(
			'<openimmo>' .
				'<anbieter>' .
					'<immobilie>' .
						'<freitexte>' .
							'<objekttitel>foo' . LF . LF. 'bar</objekttitel>' .
						'</freitexte>' .
					'</immobilie>' .
				'</anbieter>' .
			'</openimmo>'
		);

		$importedData = $this->fixture->getConvertedData($node);
		$this->assertEquals(
			'foo bar',
			$importedData[0]['title']
		);
	}

	/**
	 * @test
	 */
	public function getConvertedDataReplacesSingleCarriageReturnInObjectTitleWithSpace() {
		$node = $this->setRawDataToConvert(
			'<openimmo>' .
				'<anbieter>' .
					'<immobilie>' .
						'<freitexte>' .
							'<objekttitel>foo' . CR . 'bar</objekttitel>' .
						'</freitexte>' .
					'</immobilie>' .
				'</anbieter>' .
			'</openimmo>'
		);

		$importedData = $this->fixture->getConvertedData($node);
		$this->assertEquals(
			'foo bar',
			$importedData[0]['title']
		);
	}

	/**
	 * @test
	 */
	public function getConvertedDataReplacesDoubleCarriageReturnInObjectTitleWithSpace() {
		$node = $this->setRawDataToConvert(
			'<openimmo>' .
				'<anbieter>' .
					'<immobilie>' .
						'<freitexte>' .
							'<objekttitel>foo' . CR . CR . 'bar</objekttitel>' .
						'</freitexte>' .
					'</immobilie>' .
				'</anbieter>' .
			'</openimmo>'
		);

		$importedData = $this->fixture->getConvertedData($node);
		$this->assertEquals(
			'foo bar',
			$importedData[0]['title']
		);
	}

	/**
	 * @test
	 */
	public function getConvertedDataReplacesCrLfInObjectTitleWithSpace() {
		$node = $this->setRawDataToConvert(
			'<openimmo>' .
				'<anbieter>' .
					'<immobilie>' .
						'<freitexte>' .
							'<objekttitel>foo' . CRLF .'bar</objekttitel>' .
						'</freitexte>' .
					'</immobilie>' .
				'</anbieter>' .
			'</openimmo>'
		);

		$importedData = $this->fixture->getConvertedData($node);
		$this->assertEquals(
			'foo bar',
			$importedData[0]['title']
		);
	}

	/**
	 * data provider for all fields converted to HTML
	 *
	 * @return array
	 */
	public function htmlFieldsDataProvider() {
		return array(
			'location' => array('lage', 'location'),
			'misc' => array('sonstige_angaben', 'misc'),
			'equipment' => array('ausstatt_beschr', 'equipment'),
			'description' => array('objektbeschreibung', 'description'),
		);
	}

	/**
	 * @test
	 *
	 * @param string $xmlKey the name of the XML tag
	 * @param string $arrayKey the name of the array key
	 *
	 * @dataProvider htmlFieldsDataProvider
	 */
	public function getConvertedDataForCrLfReplacesCrLfWithLfInRichTextField($xmlKey, $arrayKey) {
		$node = $this->setRawDataToConvert(
			'<openimmo>' .
				'<anbieter>' .
					'<immobilie>' .
						'<freitexte>' .
							'<' . $xmlKey . '>foo' . CRLF .'bar 123</' . $xmlKey . '>' .
						'</freitexte>' .
					'</immobilie>' .
				'</anbieter>' .
			'</openimmo>'
		);
		$importedData = $this->fixture->getConvertedData($node);

		$this->assertEquals(
			'foo' . LF .'bar 123',
			$importedData[0][$arrayKey]
		);
	}

	/**
	 * @test
	 *
	 * @param string $xmlKey the name of the XML tag
	 * @param string $arrayKey the name of the array key
	 *
	 * @dataProvider htmlFieldsDataProvider
	 */
	public function getConvertedDataForCrReplacesCrWithLfInRichTextField($xmlKey, $arrayKey) {
		$node = $this->setRawDataToConvert(
			'<openimmo>' .
				'<anbieter>' .
					'<immobilie>' .
						'<freitexte>' .
							'<' . $xmlKey . '>foo' . CR .'bar 123</' . $xmlKey . '>' .
						'</freitexte>' .
					'</immobilie>' .
				'</anbieter>' .
			'</openimmo>'
		);
		$importedData = $this->fixture->getConvertedData($node);

		$this->assertEquals(
			'foo' . LF . 'bar 123',
			$importedData[0][$arrayKey]
		);
	}

	/**
	 * @test
	 *
	 * @param string $xmlKey the name of the XML tag
	 * @param string $arrayKey the name of the array key
	 *
	 * @dataProvider htmlFieldsDataProvider
	 */
	public function getConvertedDataForLfKeepsLfInRichTextField($xmlKey, $arrayKey) {
		$node = $this->setRawDataToConvert(
			'<openimmo>' .
				'<anbieter>' .
					'<immobilie>' .
						'<freitexte>' .
							'<' . $xmlKey . '>foo' . LF .'bar 123</' . $xmlKey . '>' .
						'</freitexte>' .
					'</immobilie>' .
				'</anbieter>' .
			'</openimmo>'
		);
		$importedData = $this->fixture->getConvertedData($node);

		$this->assertEquals(
			'foo' . LF . 'bar 123',
			$importedData[0][$arrayKey]
		);
	}

	/**
	 * @test
	 */
	public function getConvertedDataReplacesSingleTabInObjectTitleWithSpace() {
		$node = $this->setRawDataToConvert(
			'<openimmo>' .
				'<anbieter>' .
					'<immobilie>' .
						'<freitexte>' .
							'<objekttitel>foo' . TAB . 'bar</objekttitel>' .
						'</freitexte>' .
					'</immobilie>' .
				'</anbieter>' .
			'</openimmo>'
		);

		$importedData = $this->fixture->getConvertedData($node);
		$this->assertEquals(
			'foo bar',
			$importedData[0]['title']
		);
	}

	/**
	 * @test
	 */
	public function getConvertedDataReplacesMultipleTabsInObjectTitleWithSpace() {
		$node = $this->setRawDataToConvert(
			'<openimmo>' .
				'<anbieter>' .
					'<immobilie>' .
						'<freitexte>' .
							'<objekttitel>foo' . TAB . TAB . 'bar</objekttitel>' .
						'</freitexte>' .
					'</immobilie>' .
				'</anbieter>' .
			'</openimmo>'
		);

		$importedData = $this->fixture->getConvertedData($node);
		$this->assertEquals(
			'foo bar',
			$importedData[0]['title']
		);
	}

	/**
	 * @test
	 */
	public function getConvertedDataForSaleTrueAndRentFalseCreatesSaleObject() {
		$node = $this->setRawDataToConvert(
			'<openimmo>' .
				'<anbieter>' .
					'<immobilie>' .
						'<objektkategorie>' .
							'<vermarktungsart KAUF="true" MIETE_PACHT="false"/>' .
						'</objektkategorie>' .
					'</immobilie>' .
				'</anbieter>' .
			'</openimmo>'
		);

		$importedData = $this->fixture->getConvertedData($node);
		$this->assertEquals(
			REALTY_FOR_SALE,
			$importedData[0]['object_type']
		);
	}

	/**
	 * @test
	 */
	public function getConvertedDataForSaleFalseAndRentTrueCreatesRentObject() {
		$node = $this->setRawDataToConvert(
			'<openimmo>' .
				'<anbieter>' .
					'<immobilie>' .
						'<objektkategorie>' .
							'<vermarktungsart KAUF="false" MIETE_PACHT="true"/>' .
						'</objektkategorie>' .
					'</immobilie>' .
				'</anbieter>' .
			'</openimmo>'
		);

		$importedData = $this->fixture->getConvertedData($node);
		$this->assertEquals(
			REALTY_FOR_RENTING,
			$importedData[0]['object_type']
		);
	}

	/**
	 * @test
	 */
	public function getConvertedDataForSaleOneAndRentZeroCreatesSaleObject() {
		$node = $this->setRawDataToConvert(
			'<openimmo>' .
				'<anbieter>' .
					'<immobilie>' .
						'<objektkategorie>' .
							'<vermarktungsart KAUF="1" MIETE_PACHT="0"/>' .
						'</objektkategorie>' .
					'</immobilie>' .
				'</anbieter>' .
			'</openimmo>'
		);

		$importedData = $this->fixture->getConvertedData($node);
		$this->assertEquals(
			REALTY_FOR_SALE,
			$importedData[0]['object_type']
		);
	}

	/**
	 * @test
	 */
	public function getConvertedDataForSaleZeroAndRentOneCreatesRentObject() {
		$node = $this->setRawDataToConvert(
			'<openimmo>' .
				'<anbieter>' .
					'<immobilie>' .
						'<objektkategorie>' .
							'<vermarktungsart KAUF="0" MIETE_PACHT="1"/>' .
						'</objektkategorie>' .
					'</immobilie>' .
				'</anbieter>' .
			'</openimmo>'
		);

		$importedData = $this->fixture->getConvertedData($node);
		$this->assertEquals(
			REALTY_FOR_RENTING,
			$importedData[0]['object_type']
		);
	}

	/**
	 * @test
	 */
	public function getConvertedDataReturnsUniversalDataAndDefaultValuesInEachRecord() {
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

		$universalDataAndDefaultValues = array(
			'sales_area' => 0.0,
			'other_area' => 0.0,
			'window_bank' => 0.0,
			'rental_income_target' => 0.0,
			'employer' => 'foo',
			'openimmo_anid' => 'bar',
		);
		$result = $this->fixture->getConvertedData($node);

		$this->assertEquals(
			$universalDataAndDefaultValues,
			$result[0],
			'The first record has been imported incorrectly.'
		);
		$this->assertEquals(
			$universalDataAndDefaultValues,
			$result[1],
			'The second record has been imported incorrectly.'
		);
	}

	/**
	 * @test
	 */
	public function getConvertedDataCanImportSeveralProperties() {
		$node = $this->setRawDataToConvert(
			'<openimmo>' .
				'<anbieter>' .
					'<immobilie>' .
						'<geo>' .
							'<strasse>foobar</strasse>' .
							'<plz>bar</plz>' .
						'</geo>' .
						'<freitexte>' .
							'<lage>foo</lage>' .
						'</freitexte>' .
					'</immobilie>' .
				'</anbieter>' .
			'</openimmo>'
		);

		$importedData = $this->fixture->getConvertedData($node);
		$this->assertEquals(
			'foobar',
			$importedData[0]['street'],
			'The street is missing.'
		);
		$this->assertEquals(
			'bar',
			$importedData[0]['zip'],
			'The ZIP is missing.'
		);
		$this->assertEquals(
			'foo',
			$importedData[0]['location'],
			'The location is missing.'
		);
	}

	public function testGetConvertedDataSetsLocalizedPetsTitleIfValueIsStringTrue() {
		$node = $this->setRawDataToConvert(
			'<openimmo>'
				.'<anbieter>'
					.'<immobilie>'
						.'<verwaltung_objekt>'
							.'<haustiere>TRUE</haustiere>'
						.'</verwaltung_objekt>'
					.'</immobilie>'
				.'</anbieter>'
			.'</openimmo>'
		);

		$result = $this->fixture->getConvertedData($node);
		$this->assertEquals(
			tx_oelib_ObjectFactory::make('tx_realty_translator')->translate('label_allowed'),
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
			tx_oelib_ObjectFactory::make('tx_realty_translator')->translate('label_allowed'),
			$result[0]['pets']
		);
	}

	/**
	 * @test
	 */
	public function getConvertedDataSubstitutesSurplusDecimalsWhenAPositiveNumberIsGiven() {
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

		$result = $this->fixture->getConvertedData($node);
		$this->assertEquals(
			'1',
			$result[0]['street']
		);
	}

	/**
	 * @test
	 */
	public function getConvertedDataSubstitutesSurplusDecimalsWhenANegativeNumberIsGiven() {
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

		$result = $this->fixture->getConvertedData($node);
		$this->assertEquals(
			'-1',
			$result[0]['street']
		);
	}

	/**
	 * @test
	 */
	public function getConvertedDataSubstitutesTwoSurplusDecimalsWhenZeroIsGiven() {
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

		$result = $this->fixture->getConvertedData($node);
		$this->assertEquals(
			'0',
			$result[0]['street']
		);
	}

	/**
	 * @test
	 */
	public function getConvertedDataSubstitutesOneSurplusDecimalWhenZeroIsGiven() {
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

		$result = $this->fixture->getConvertedData($node);
		$this->assertEquals(
			'0',
			$result[0]['street']
		);
	}

	/**
	 * @test
	 */
	public function getConvertedDataNotSubstitutesTwoNonSurplusDecimalsFromAPositiveNumber() {
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

		$result = $this->fixture->getConvertedData($node);
		$this->assertEquals(
			'1.11',
			$result[0]['street']
		);
	}

	/**
	 * @test
	 */
	public function getConvertedDataNotSubstitutesTwoNonSurplusDecimalsFromANegativeNumber() {
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

		$result = $this->fixture->getConvertedData($node);
		$this->assertEquals(
			'-1.11',
			$result[0]['street']
		);
	}

	/**
	 * @test
	 */
	public function getConvertedDataNotSubstitutesOneNonSurplusDecimals() {
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

		$result = $this->fixture->getConvertedData($node);
		$this->assertEquals(
			'1.1',
			$result[0]['street']
		);
	}

	public function testGetConvertedDataFetchesAlternativeContactEmail() {
		$node = $this->setRawDataToConvert(
			'<openimmo>'
				.'<anbieter>'
					.'<immobilie>'
						.'<kontaktperson>'
							.'<email_direkt>any-email@example.com</email_direkt>'
						.'</kontaktperson>'
					.'</immobilie>'
				.'</anbieter>'
			.'</openimmo>'
		);

		$result = $this->fixture->getConvertedData($node);
		$this->assertEquals(
			'any-email@example.com',
			$result[0]['contact_email']
		);
	}

	public function testGetConvertedDataGetsStateIfValidStateProvided() {
		$node = DOMDocument::loadXML(
			'<openimmo>' .
				'<anbieter>' .
					'<immobilie>' .
						'<zustand_angaben>' .
							'<zustand ZUSTAND_ART="gepflegt" />' .
						'</zustand_angaben>' .
					'</immobilie>' .
				'</anbieter>' .
			'</openimmo>'
		);
		$this->fixture->setRawRealtyData($node);

		$result = $this->fixture->getConvertedData($node);
		$this->assertEquals(
			8,
			$result[0]['state']
		);
	}

	public function testGetConvertedDataDoesNotGetStateIfInvalidStateProvided() {
		$node = DOMDocument::loadXML(
			'<openimmo>' .
				'<anbieter>' .
					'<immobilie>' .
						'<zustand_angaben>' .
							'<zustand ZUSTAND_ART="geputzt" />' .
						'</zustand_angaben>' .
					'</immobilie>' .
				'</anbieter>' .
			'</openimmo>'
		);
		$this->fixture->setRawRealtyData($node);

		$result = $this->fixture->getConvertedData($node);
		$this->assertFalse(
			isset($result[0]['state'])
		);
	}

	public function testGetConvertedDataCanGetOneValidHeatingType() {
		$node = DOMDocument::loadXML(
			'<openimmo>' .
				'<anbieter>' .
					'<immobilie>' .
						'<ausstattung>' .
							'<heizungsart ZENTRAL="true" />' .
						'</ausstattung>' .
					'</immobilie>' .
				'</anbieter>' .
			'</openimmo>'
		);
		$this->fixture->setRawRealtyData($node);

		$result = $this->fixture->getConvertedData($node);
		$this->assertEquals(
			'2',
			$result[0]['heating_type']
		);
	}

	public function testGetConvertedDataCanGetMultipleValidHeatingTypesFromHeatingTypeNode() {
		$node = DOMDocument::loadXML(
			'<openimmo>' .
				'<anbieter>' .
					'<immobilie>' .
						'<ausstattung>' .
							'<heizungsart ZENTRAL="true" OFEN="true" ETAGE="true" />' .
						'</ausstattung>' .
					'</immobilie>' .
				'</anbieter>' .
			'</openimmo>'
		);
		$this->fixture->setRawRealtyData($node);

		$result = $this->fixture->getConvertedData($node);
		$this->assertEquals(
			'2,9,11',
			$result[0]['heating_type']
		);
	}

	public function testGetConvertedDataCanGetMultipleValidHeatingTypesFromFiringNode() {
		$node = DOMDocument::loadXML(
			'<openimmo>' .
				'<anbieter>' .
					'<immobilie>' .
						'<ausstattung>' .
							'<befeuerung OEL="true" GAS="true" BLOCK="true" />' .
						'</ausstattung>' .
					'</immobilie>' .
				'</anbieter>' .
			'</openimmo>'
		);
		$this->fixture->setRawRealtyData($node);

		$result = $this->fixture->getConvertedData($node);
		$this->assertEquals(
			'5,8,12',
			$result[0]['heating_type']
		);
	}

	public function testGetConvertedDataCanGetHeatingTypesFromFiringNodeAndHeatingTypeNode() {
		$node = DOMDocument::loadXML(
			'<openimmo>' .
				'<anbieter>' .
					'<immobilie>' .
						'<ausstattung>' .
							'<heizungsart OFEN="true" />' .
							'<befeuerung BLOCK="true" />' .
						'</ausstattung>' .
					'</immobilie>' .
				'</anbieter>' .
			'</openimmo>'
		);
		$this->fixture->setRawRealtyData($node);

		$result = $this->fixture->getConvertedData($node);
		$this->assertEquals(
			'11,12',
			$result[0]['heating_type']
		);
	}

	public function testGetConvertedDataDoesNotGetInvalidHeatingTypeFromHeatingTypeNode() {
		$node = DOMDocument::loadXML(
			'<openimmo>' .
				'<anbieter>' .
					'<immobilie>' .
						'<ausstattung>' .
							'<heizungsart BACKOFEN="true" />' .
						'</ausstattung>' .
					'</immobilie>' .
				'</anbieter>' .
			'</openimmo>'
		);
		$this->fixture->setRawRealtyData($node);

		$result = $this->fixture->getConvertedData($node);
		$this->assertFalse(
			isset($result[0]['heating_type'])
		);
	}

	public function testGetConvertedDataDoesNotGetInvalidHeatingTypeFromFiringNode() {
		$node = DOMDocument::loadXML(
			'<openimmo>' .
				'<anbieter>' .
					'<immobilie>' .
						'<ausstattung>' .
							'<befeuerung KERZE="true" />' .
						'</ausstattung>' .
					'</immobilie>' .
				'</anbieter>' .
			'</openimmo>'
		);
		$this->fixture->setRawRealtyData($node);

		$result = $this->fixture->getConvertedData($node);
		$this->assertFalse(
			isset($result[0]['heating_type'])
		);
	}

	public function testGetConvertedDataOnlyGetsValidHeatingTypesIfValidAndInvalidTypesProvided() {
		$node = DOMDocument::loadXML(
			'<openimmo>' .
				'<anbieter>' .
					'<immobilie>' .
						'<ausstattung>' .
							'<heizungsart ZENTRAL="true" FUSSBODEN="true" BACKOFEN="true" />' .
						'</ausstattung>' .
					'</immobilie>' .
				'</anbieter>' .
			'</openimmo>'
		);
		$this->fixture->setRawRealtyData($node);

		$result = $this->fixture->getConvertedData($node);
		$this->assertEquals(
			'2,4',
			$result[0]['heating_type']
		);
	}

	/**
	 * @test
	 */
	public function getConvertedDataFetchesSwitchboardPhoneNumber() {
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


		$result = $this->fixture->getConvertedData($node);
		$this->assertEquals(
			'1234567',
			$result[0]['phone_switchboard']
		);
	}

	/**
	 * @test
	 */
	public function getConvertedDataFetchesDirectExtensionPhoneNumber() {
		$node = DOMDocument::loadXML(
			'<openimmo>'
				.'<anbieter>'
					.'<immobilie>'
						.'<kontaktperson>'
							.'<tel_durchw>1234567</tel_durchw>'
						.'</kontaktperson>'
					.'</immobilie>'
				.'</anbieter>'
			.'</openimmo>'
		);
		$this->fixture->setRawRealtyData($node);

		$result = $this->fixture->getConvertedData($node);
		$this->assertEquals(
			'1234567',
			$result[0]['phone_direct_extension']
		);
	}

	/**
	 * @test
	 *
	 * @see https://bugs.oliverklee.com/show_bug.cgi?id=3991
	 * @see https://bugs.oliverklee.com/show_bug.cgi?id=4057
	 */
	public function getConvertedDataCanImportLivingUsageUsingTrueFalseAttributeValues() {
		$node = DOMDocument::loadXML(
			'<openimmo>' .
				'<anbieter>' .
					'<immobilie>' .
						'<objektkategorie>' .
							'<nutzungsart WOHNEN="true" GEWERBE="false"/>' .
						'</objektkategorie>' .
					'</immobilie>' .
				'</anbieter>' .
			'</openimmo>'
		);
		$this->fixture->setRawRealtyData($node);

		$result = $this->fixture->getConvertedData($node);
		$this->assertEquals(
			'Wohnen',
			$result[0]['utilization']
		);
	}

	/**
	 * @test
	 *
	 * @see https://bugs.oliverklee.com/show_bug.cgi?id=3991
	 * @see https://bugs.oliverklee.com/show_bug.cgi?id=4057
	 */
	public function getConvertedDataCanImportLivingUsageUsingOneZeroAttributeValues() {
		$node = DOMDocument::loadXML(
			'<openimmo>' .
				'<anbieter>' .
					'<immobilie>' .
						'<objektkategorie>' .
							'<nutzungsart WOHNEN="1" GEWERBE="0"/>' .
						'</objektkategorie>' .
					'</immobilie>' .
				'</anbieter>' .
			'</openimmo>'
		);
		$this->fixture->setRawRealtyData($node);

		$result = $this->fixture->getConvertedData($node);
		$this->assertEquals(
			'Wohnen',
			$result[0]['utilization']
		);
	}

	/**
	 * @test
	 *
	 * @see https://bugs.oliverklee.com/show_bug.cgi?id=3991
	 */
	public function getConvertedDataCanImportCommercialUsage() {
		$node = DOMDocument::loadXML(
			'<openimmo>' .
				'<anbieter>' .
					'<immobilie>' .
						'<objektkategorie>' .
							'<nutzungsart WOHNEN="false" GEWERBE="true"/>' .
						'</objektkategorie>' .
					'</immobilie>' .
				'</anbieter>' .
			'</openimmo>'
		);
		$this->fixture->setRawRealtyData($node);

		$result = $this->fixture->getConvertedData($node);
		$this->assertEquals(
			'Gewerbe',
			$result[0]['utilization']
		);
	}

	/**
	 * @test
	 *
	 * @see https://bugs.oliverklee.com/show_bug.cgi?id=3991
	 */
	public function getConvertedDataCanImportLivingAndCommercialUsage() {
		$node = DOMDocument::loadXML(
			'<openimmo>' .
				'<anbieter>' .
					'<immobilie>' .
						'<objektkategorie>' .
							'<nutzungsart WOHNEN="true" GEWERBE="true"/>' .
						'</objektkategorie>' .
					'</immobilie>' .
				'</anbieter>' .
			'</openimmo>'
		);
		$this->fixture->setRawRealtyData($node);

		$result = $this->fixture->getConvertedData($node);
		$this->assertEquals(
			'Wohnen, Gewerbe',
			$result[0]['utilization']
		);
	}

	/**
	 * @test
	 *
	 * @see https://bugs.oliverklee.com/show_bug.cgi?id=3991
	 */
	public function getConvertedDataCanImportEmptyUsage() {
		$node = DOMDocument::loadXML(
			'<openimmo>' .
				'<anbieter>' .
					'<immobilie>' .
						'<objektkategorie>' .
							'<nutzungsart WOHNEN="false" GEWERBE="false"/>' .
						'</objektkategorie>' .
					'</immobilie>' .
				'</anbieter>' .
			'</openimmo>'
		);
		$this->fixture->setRawRealtyData($node);

		$result = $this->fixture->getConvertedData($node);
		$this->assertFalse(
			isset($result[0]['utilization'])
		);
	}

	/**
	 * @test
	 */
	public function getConvertedDataGetsFurnishingCategoryForStandardCategoryProvided() {
		$node = DOMDocument::loadXML(
			'<openimmo>' .
				'<anbieter>' .
					'<immobilie>' .
						'<ausstattung>' .
							'<ausstatt_kategorie>STANDARD</ausstatt_kategorie>' .
						'</ausstattung>' .
					'</immobilie>' .
				'</anbieter>' .
			'</openimmo>'
		);
		$this->fixture->setRawRealtyData($node);

		$result = $this->fixture->getConvertedData($node);
		$this->assertEquals(
			1,
			$result[0]['furnishing_category']
		);
	}

	/**
	 * @test
	 */
	public function getConvertedDataGetsFurnishingCategoryForUpmarketCategoryProvided() {
		$node = DOMDocument::loadXML(
			'<openimmo>' .
				'<anbieter>' .
					'<immobilie>' .
						'<ausstattung>' .
							'<ausstatt_kategorie>GEHOBEN</ausstatt_kategorie>' .
						'</ausstattung>' .
					'</immobilie>' .
				'</anbieter>' .
			'</openimmo>'
		);
		$this->fixture->setRawRealtyData($node);

		$result = $this->fixture->getConvertedData($node);
		$this->assertEquals(
			2,
			$result[0]['furnishing_category']
		);
	}

	/**
	 * @test
	 */
	public function getConvertedDataGetsFurnishingCategoryForLuxuryCategoryProvided() {
		$node = DOMDocument::loadXML(
			'<openimmo>' .
				'<anbieter>' .
					'<immobilie>' .
						'<ausstattung>' .
							'<ausstatt_kategorie>LUXUS</ausstatt_kategorie>' .
						'</ausstattung>' .
					'</immobilie>' .
				'</anbieter>' .
			'</openimmo>'
		);
		$this->fixture->setRawRealtyData($node);

		$result = $this->fixture->getConvertedData($node);
		$this->assertEquals(
			3,
			$result[0]['furnishing_category']
		);
	}

	/**
	 * @test
	 */
	public function getConvertedDataNotGetsFurnishingCategoryIfInvalidCategoryProvided() {
		$node = DOMDocument::loadXML(
			'<openimmo>' .
				'<anbieter>' .
					'<immobilie>' .
						'<ausstattung>' .
							'<ausstatt_kategorie>FOO</ausstatt_kategorie>' .
						'</ausstattung>' .
					'</immobilie>' .
				'</anbieter>' .
			'</openimmo>'
		);
		$this->fixture->setRawRealtyData($node);

		$result = $this->fixture->getConvertedData($node);
		$this->assertFalse(
			isset($result[0]['furnishing_category'])
		);
	}

	/**
	 * @test
	 */
	public function getConvertedDataCanGetOneValidFlooringType() {
		$node = DOMDocument::loadXML(
			'<openimmo>' .
				'<anbieter>' .
					'<immobilie>' .
						'<ausstattung>' .
							'<boden FLIESEN="true" />' .
						'</ausstattung>' .
					'</immobilie>' .
				'</anbieter>' .
			'</openimmo>'
		);
		$this->fixture->setRawRealtyData($node);

		$result = $this->fixture->getConvertedData($node);
		$this->assertEquals(
			'1',
			$result[0]['flooring']
		);
	}

	/**
	 * @test
	 */
	public function getConvertedDataCanGetMultipleValidFlooringTypesFromFlooringNode() {
		$node = DOMDocument::loadXML(
			'<openimmo>' .
				'<anbieter>' .
					'<immobilie>' .
						'<ausstattung>' .
							'<boden STEIN="true" TEPPICH="true" PARKETT="true" />' .
						'</ausstattung>' .
					'</immobilie>' .
				'</anbieter>' .
			'</openimmo>'
		);
		$this->fixture->setRawRealtyData($node);

		$result = $this->fixture->getConvertedData($node);
		$this->assertEquals(
			'2,3,4',
			$result[0]['flooring']
		);
	}

	/**
	 * @test
	 */
	public function getConvertedDataNotGetsInvalidFlooringFromFlooringNode() {
		$node = DOMDocument::loadXML(
			'<openimmo>' .
				'<anbieter>' .
					'<immobilie>' .
						'<ausstattung>' .
							'<boden RAUHFAHSER="true" />' .
						'</ausstattung>' .
					'</immobilie>' .
				'</anbieter>' .
			'</openimmo>'
		);
		$this->fixture->setRawRealtyData($node);

		$result = $this->fixture->getConvertedData($node);
		$this->assertFalse(
			isset($result[0]['flooring'])
		);
	}

	/**
	 * @test
	 */
	public function getConvertedDataOnlyGetsValidFlooringsIfValidAndInvalidFlooringsProvided() {
		$node = DOMDocument::loadXML(
			'<openimmo>' .
				'<anbieter>' .
					'<immobilie>' .
						'<ausstattung>' .
							'<boden FERTIGPARKETT="true" LAMINAT="true" FENSTER="true" />' .
						'</ausstattung>' .
					'</immobilie>' .
				'</anbieter>' .
			'</openimmo>'
		);
		$this->fixture->setRawRealtyData($node);

		$result = $this->fixture->getConvertedData($node);
		$this->assertEquals(
			'5,6',
			$result[0]['flooring']
		);
	}

	/**
	 * @test
	 */
	public function getConvertedDataForValidButFalseFlooringDoesNotImportThisFlooring() {
		$node = DOMDocument::loadXML(
			'<openimmo>' .
				'<anbieter>' .
					'<immobilie>' .
						'<ausstattung>' .
							'<boden FERTIGPARKETT="false" LINOLEUM="true" />' .
						'</ausstattung>' .
					'</immobilie>' .
				'</anbieter>' .
			'</openimmo>'
		);
		$this->fixture->setRawRealtyData($node);

		$result = $this->fixture->getConvertedData($node);
		$this->assertEquals(
			'11',
			$result[0]['flooring']
		);
	}

	/**
	 * @test
	 */
	public function getConvertedDataImportsRentedTrueAsStatusRented() {
		$node = $this->setRawDataToConvert(
			'<openimmo>' .
				'<anbieter>' .
					'<immobilie>' .
						'<verwaltung_objekt>' .
							'<vermietet>TRUE</vermietet>' .
						'</verwaltung_objekt>' .
					'</immobilie>' .
				'</anbieter>' .
			'</openimmo>'
		);

		$result = $this->fixture->getConvertedData($node);
		$this->assertEquals(
			tx_realty_Model_RealtyObject::STATUS_RENTED,
			$result[0]['status']
		);
	}

	/**
	 * @test
	 */
	public function getConvertedDataImportsRentedFalseAsStatusVacant() {
		$node = $this->setRawDataToConvert(
			'<openimmo>' .
				'<anbieter>' .
					'<immobilie>' .
						'<verwaltung_objekt>' .
							'<vermietet>FALSE</vermietet>' .
						'</verwaltung_objekt>' .
					'</immobilie>' .
				'</anbieter>' .
			'</openimmo>'
		);

		$result = $this->fixture->getConvertedData($node);
		$this->assertEquals(
			tx_realty_Model_RealtyObject::STATUS_VACANT,
			$result[0]['status']
		);
	}

	/**
	 * @test
	 */
	public function getConvertedDataForRentedMissingNotSetsStatus() {
		$node = $this->setRawDataToConvert(
			'<openimmo>' .
				'<anbieter>' .
					'<immobilie>' .
						'<verwaltung_objekt>' .
						'</verwaltung_objekt>' .
					'</immobilie>' .
				'</anbieter>' .
			'</openimmo>'
		);

		$result = $this->fixture->getConvertedData($node);
		$this->assertFalse(
			isset($result[0]['status'])
		);
	}


	////////////////////////////////////////////
	// Tests concerning createRecordsForImages
	////////////////////////////////////////////

	public function testCreateRecordsForImagesIfOneImageAppendixWithoutAnImagePathIsGiven() {
		$this->setRawDataToConvert(
			'<immobilie>' .
				'<anhaenge>' .
					'<anhang>' .
						'<anhangtitel>foo</anhangtitel>' .
					'</anhang>' .
				'</anhaenge>' .
			'</immobilie>'
		);

		$this->assertEquals(
			array(),
			$this->fixture->createRecordsForImages()
		);
	}

	/**
	 * @test
	 */
	public function createRecordsForImagesForLowercaseJpgWithCaptionReturnsRecord() {
		$this->setRawDataToConvert(
			'<immobilie>' .
				'<anhaenge>' .
					'<anhang>' .
						'<anhangtitel>bar</anhangtitel>' .
						'<daten>' .
							'<pfad>tx_realty_image_test.jpg</pfad>' .
						'</daten>' .
					'</anhang>' .
				'</anhaenge>' .
			'</immobilie>'
		);

		$this->assertEquals(
			array(
				array(
					'caption' => 'bar',
					'image' => 'tx_realty_image_test.jpg'
				)
			),
			$this->fixture->createRecordsForImages()
		);
	}

	/**
	 * @test
	 */
	public function createRecordsForImagesForUppercaseJpgWithCaptionReturnsRecord() {
		$this->setRawDataToConvert(
			'<immobilie>' .
				'<anhaenge>' .
					'<anhang>' .
						'<anhangtitel>bar</anhangtitel>' .
						'<daten>' .
							'<pfad>tx_realty_image_test.JPG</pfad>' .
						'</daten>' .
					'</anhang>' .
				'</anhaenge>' .
			'</immobilie>'
		);

		$this->assertEquals(
			array(
				array(
					'caption' => 'bar',
					'image' => 'tx_realty_image_test.JPG'
				)
			),
			$this->fixture->createRecordsForImages()
		);
	}

	/**
	 * @test
	 */
	public function createRecordsForImagesForPdfWithCaptionNotReturnsRecord() {
		$this->setRawDataToConvert(
			'<immobilie>' .
				'<anhaenge>' .
					'<anhang>' .
						'<anhangtitel>bar</anhangtitel>' .
						'<daten>' .
							'<pfad>layout.pdf</pfad>' .
						'</daten>' .
					'</anhang>' .
				'</anhaenge>' .
			'</immobilie>'
		);

		$this->assertEquals(
			array(),
			$this->fixture->createRecordsForImages()
		);
	}

	/**
	 * @test
	 */
	public function createRecordsForImagesForPsWithCaptionNotReturnsRecord() {
		$this->setRawDataToConvert(
			'<immobilie>' .
				'<anhaenge>' .
					'<anhang>' .
						'<anhangtitel>bar</anhangtitel>' .
						'<daten>' .
							'<pfad>layout.ps</pfad>' .
						'</daten>' .
					'</anhang>' .
				'</anhaenge>' .
			'</immobilie>'
		);

		$this->assertEquals(
			array(),
			$this->fixture->createRecordsForImages()
		);
	}

	/**
	 * @test
	 */
	public function createRecordsForImagesForExeWithCaptionNotReturnsRecord() {
		$this->setRawDataToConvert(
			'<immobilie>' .
				'<anhaenge>' .
					'<anhang>' .
						'<anhangtitel>bar</anhangtitel>' .
						'<daten>' .
							'<pfad>evil-virus.exe</pfad>' .
						'</daten>' .
					'</anhang>' .
				'</anhaenge>' .
			'</immobilie>'
		);

		$this->assertEquals(
			array(),
			$this->fixture->createRecordsForImages()
		);
	}

	/**
	 * @test
	 */
	public function createRecordsForImagesForJpgWithoutCaptionReturnsRecord() {
		$this->setRawDataToConvert(
			'<immobilie>' .
				'<anhaenge>' .
					'<anhang>' .
						'<anhangtitel/>' .
						'<daten>' .
							'<pfad>tx_realty_image_test.jpg</pfad>' .
						'</daten>' .
					'</anhang>' .
				'</anhaenge>' .
			'</immobilie>'
		);

		$this->assertEquals(
			array(
				array(
					'caption' => '',
					'image' => 'tx_realty_image_test.jpg'
				)
			),
			$this->fixture->createRecordsForImages()
		);
	}

	public function testCreateRecordsForImagesIfTwoValidImageAppendixesAreGiven() {
		$this->setRawDataToConvert(
			'<immobilie>' .
				'<anhaenge>' .
					'<anhang>' .
						'<anhangtitel>bar</anhangtitel>' .
						'<daten>' .
							'<pfad>tx_realty_image_test2.jpg</pfad>' .
						'</daten>' .
					'</anhang>' .
					'<anhang>' .
						'<anhangtitel>foo</anhangtitel>' .
						'<daten>' .
							'<pfad>tx_realty_image_test.jpg</pfad>' .
						'</daten>' .
					'</anhang>' .
				'</anhaenge>' .
			'</immobilie>'
		);
		$images = $this->fixture->createRecordsForImages();

		$this->assertEquals(
			array(
				'caption' => 'bar',
				'image' => 'tx_realty_image_test2.jpg'
			),
			$images[0]
		);
		$this->assertEquals(
			array(
				'caption' => 'foo',
				'image' => 'tx_realty_image_test.jpg'
			),
			$images[1]
		);
	}

	public function testCreateRecordsForImagesIfTwoImageAppendixesWithTheSameTitleAreGiven() {
		$this->setRawDataToConvert(
			'<immobilie>' .
				'<anhaenge>' .
					'<anhang>' .
						'<anhangtitel>bar</anhangtitel>' .
						'<daten>' .
							'<pfad>tx_realty_image_test2.jpg</pfad>' .
						'</daten>' .
					'</anhang>' .
					'<anhang>' .
						'<anhangtitel>bar</anhangtitel>' .
						'<daten>' .
							'<pfad>tx_realty_image_test.jpg</pfad>' .
						'</daten>' .
					'</anhang>' .
				'</anhaenge>' .
			'</immobilie>'
		);
		$images = $this->fixture->createRecordsForImages();

		$this->assertEquals(
			array(
				'caption' => 'bar',
				'image' => 'tx_realty_image_test2.jpg'
			),
			$images[0]
		);
		$this->assertEquals(
			array(
				'caption' => 'bar',
				'image' => 'tx_realty_image_test.jpg'
			),
			$images[1]
		);
	}

	public function testCreateRecordsForImagesOfTwoRealtyObjectsWithOneImageEachCreatesOneImageRecordPerImage() {
		$this->setRawDataToConvert(
			'<openimmo>' .
				'<immobilie>' .
					'<anhaenge>' .
						'<anhang>' .
							'<anhangtitel>bar</anhangtitel>' .
							'<daten>' .
								'<pfad>tx_realty_image_test2.jpg</pfad>' .
							'</daten>' .
						'</anhang>' .
					'</anhaenge>' .
				'</immobilie>' .
				'<immobilie>' .
					'<anhaenge>' .
						'<anhang>' .
							'<anhangtitel>foo</anhangtitel>' .
							'<daten>' .
								'<pfad>tx_realty_image_test.jpg</pfad>' .
							'</daten>' .
						'</anhang>' .
					'</anhaenge>' .
				'</immobilie>' .
			'</openimmo>'
		);

		$this->assertEquals(
			1,
			count($this->fixture->createRecordsForImages())
		);
	}

	public function testCreateRecordsForImagesOfTwoRealtyObjectsInOneFileWithAnIdenticallyNamedImage() {
		$node = $this->setRawDataToConvert(
			'<openimmo>' .
				'<immobilie>' .
					'<anhaenge>' .
						'<anhang>' .
							'<anhangtitel>bar</anhangtitel>' .
							'<daten>' .
								'<pfad>tx_realty_image_test.jpg</pfad>' .
							'</daten>' .
						'</anhang>' .
					'</anhaenge>' .
				'</immobilie>' .
				'<immobilie>' .
					'<anhaenge>' .
						'<anhang>' .
							'<anhangtitel>foo</anhangtitel>' .
							'<daten>' .
								'<pfad>tx_realty_image_test.jpg</pfad>' .
							'</daten>' .
						'</anhang>' .
					'</anhaenge>' .
				'</immobilie>' .
			'</openimmo>'
		);
		$result = $this->fixture->getConvertedData($node);

		$this->assertEquals(
			array(
				'caption' => 'bar',
				'image' => 'tx_realty_image_test.jpg'
			),
			$result[0]['images'][0]
		);
		$this->assertEquals(
			array(
				'caption' => 'foo',
				'image' => 'tx_realty_image_test_00.jpg'
			),
			$result[1]['images'][0]
		);
	}


	/////////////////////////////////////
	// Tests concerning importDocuments
	/////////////////////////////////////

	/**
	 * @test
	 */
	public function importDocumentsIgnoresAppendixWithoutFileName() {
		$this->setRawDataToConvert(
			'<immobilie>' .
				'<anhaenge>' .
					'<anhang>' .
						'<anhangtitel>foo</anhangtitel>' .
					'</anhang>' .
				'</anhaenge>' .
			'</immobilie>'
		);

		$this->assertEquals(
			array(),
			$this->fixture->importDocuments()
		);
	}

	/**
	 * @test
	 */
	public function importDocumentsForLowercasePdfWithTitleReturnsRecord() {
		$this->setRawDataToConvert(
			'<immobilie>' .
				'<anhaenge>' .
					'<anhang>' .
						'<anhangtitel>bar</anhangtitel>' .
						'<daten>' .
							'<pfad>tx_realty_document_test.pdf</pfad>' .
						'</daten>' .
					'</anhang>' .
				'</anhaenge>' .
			'</immobilie>'
		);

		$this->assertEquals(
			array(
				array(
					'title' => 'bar',
					'filename' => 'tx_realty_document_test.pdf'
				)
			),
			$this->fixture->importDocuments()
		);
	}

	/**
	 * @test
	 */
	public function importDocumentsForUppercasePdfWithTitleReturnsRecord() {
		$this->setRawDataToConvert(
			'<immobilie>' .
				'<anhaenge>' .
					'<anhang>' .
						'<anhangtitel>bar</anhangtitel>' .
						'<daten>' .
							'<pfad>tx_realty_document_test.PDF</pfad>' .
						'</daten>' .
					'</anhang>' .
				'</anhaenge>' .
			'</immobilie>'
		);

		$this->assertEquals(
			array(
				array(
					'title' => 'bar',
					'filename' => 'tx_realty_document_test.PDF'
				)
			),
			$this->fixture->importDocuments()
		);
	}

	/**
	 * @test
	 */
	public function importDocumentsForAttachmentWithAttributesAndFormatTagReturnsRecord() {
		$this->setRawDataToConvert(
			'<immobilie>' .
				'<anhaenge>' .
					'<anhang location="EXTERN" gruppe="TITELBILD">' .
						'<anhangtitel>bar</anhangtitel>' .
						'<format>PDF</format>' .
						'<daten>' .
							'<pfad>tx_realty_document_test.pdf</pfad>' .
						'</daten>' .
					'</anhang>' .
				'</anhaenge>' .
			'</immobilie>'
		);

		$this->assertEquals(
			array(
				array(
					'title' => 'bar',
					'filename' => 'tx_realty_document_test.pdf'
				)
			),
			$this->fixture->importDocuments()
		);
	}

	/**
	 * @test
	 */
	public function importDocumentsForJpgWithTitleNotReturnsRecord() {
		$this->setRawDataToConvert(
			'<immobilie>' .
				'<anhaenge>' .
					'<anhang>' .
						'<anhangtitel>bar</anhangtitel>' .
						'<daten>' .
							'<pfad>layout.jpg</pfad>' .
						'</daten>' .
					'</anhang>' .
				'</anhaenge>' .
			'</immobilie>'
		);

		$this->assertEquals(
			array(),
			$this->fixture->importDocuments()
		);
	}

	/**
	 * @test
	 */
	public function importDocumentsForPsWithTitleNotReturnsRecord() {
		$this->setRawDataToConvert(
			'<immobilie>' .
				'<anhaenge>' .
					'<anhang>' .
						'<anhangtitel>bar</anhangtitel>' .
						'<daten>' .
							'<pfad>layout.ps</pfad>' .
						'</daten>' .
					'</anhang>' .
				'</anhaenge>' .
			'</immobilie>'
		);

		$this->assertEquals(
			array(),
			$this->fixture->importDocuments()
		);
	}

	/**
	 * @test
	 */
	public function importDocumentsForExeWithTitleNotReturnsRecord() {
		$this->setRawDataToConvert(
			'<immobilie>' .
				'<anhaenge>' .
					'<anhang>' .
						'<anhangtitel>bar</anhangtitel>' .
						'<daten>' .
							'<pfad>evil-virus.exe</pfad>' .
						'</daten>' .
					'</anhang>' .
				'</anhaenge>' .
			'</immobilie>'
		);

		$this->assertEquals(
			array(),
			$this->fixture->importDocuments()
		);
	}

	/**
	 * @test
	 */
	public function importDocumentsForPdfWithoutTitleNotReturnsRecord() {
		$this->setRawDataToConvert(
			'<immobilie>' .
				'<anhaenge>' .
					'<anhang>' .
						'<anhangtitel/>' .
						'<daten>' .
							'<pfad>tx_realty_document_test.pdf</pfad>' .
						'</daten>' .
					'</anhang>' .
				'</anhaenge>' .
			'</immobilie>'
		);

		$this->assertEquals(
			array(),
			$this->fixture->importDocuments()
		);
	}

	/**
	 * @test
	 */
	public function importDocumentsCanInportTwoDocuments() {
		$this->setRawDataToConvert(
			'<immobilie>' .
				'<anhaenge>' .
					'<anhang>' .
						'<anhangtitel>bar</anhangtitel>' .
						'<daten>' .
							'<pfad>tx_realty_document_test2.pdf</pfad>' .
						'</daten>' .
					'</anhang>' .
					'<anhang>' .
						'<anhangtitel>foo</anhangtitel>' .
						'<daten>' .
							'<pfad>tx_realty_document_test.pdf</pfad>' .
						'</daten>' .
					'</anhang>' .
				'</anhaenge>' .
		'</immobilie>'
		);
		$documents = $this->fixture->importDocuments();

		$this->assertEquals(
			array(
				'title' => 'bar',
				'filename' => 'tx_realty_document_test2.pdf'
			),
			$documents[0]
		);
		$this->assertEquals(
			array(
				'title' => 'foo',
				'filename' => 'tx_realty_document_test.pdf'
			),
			$documents[1]
		);
	}

	/**
	 * @test
	 */
	public function importDocumentsOfTwoObjectsWithOneDocumentEachCreatesOneDocumentPerObject() {
		$this->setRawDataToConvert(
			'<openimmo>' .
				'<immobilie>' .
					'<anhaenge>' .
						'<anhang>' .
							'<anhangtitel>bar</anhangtitel>' .
							'<daten>' .
								'<pfad>tx_realty_document_test2.pdf</pfad>' .
							'</daten>' .
						'</anhang>' .
					'</anhaenge>' .
				'</immobilie>' .
				'<immobilie>' .
					'<anhaenge>' .
						'<anhang>' .
							'<anhangtitel>foo</anhangtitel>' .
							'<daten>' .
								'<pfad>tx_realty_document_test.pdf</pfad>' .
							'</daten>' .
						'</anhang>' .
					'</anhaenge>' .
				'</immobilie>' .
			'</openimmo>'
		);

		$this->assertEquals(
			1,
			count($this->fixture->importDocuments())
		);
	}

	/**
	 * @test
	 */
	public function importDocumentsOfTwoObjectsWithIdenticallyNamedDocumentsCreatesDifferentFileNames() {
		$node = $this->setRawDataToConvert(
			'<openimmo>' .
				'<immobilie>' .
					'<anhaenge>' .
						'<anhang>' .
							'<anhangtitel>bar</anhangtitel>' .
							'<daten>' .
								'<pfad>tx_realty_document_test.pdf</pfad>' .
							'</daten>' .
						'</anhang>' .
					'</anhaenge>' .
				'</immobilie>' .
				'<immobilie>' .
					'<anhaenge>' .
						'<anhang>' .
							'<anhangtitel>foo</anhangtitel>' .
							'<daten>' .
								'<pfad>tx_realty_document_test.pdf</pfad>' .
							'</daten>' .
						'</anhang>' .
					'</anhaenge>' .
				'</immobilie>' .
			'</openimmo>'
		);
		$result = $this->fixture->getConvertedData($node);

		$this->assertEquals(
			array(
				'title' => 'bar',
				'filename' => 'tx_realty_document_test.pdf'
			),
			$result[0]['documents'][0]
		);
		$this->assertEquals(
			array(
				'title' => 'foo',
				'filename' => 'tx_realty_document_test_00.pdf'
			),
			$result[1]['documents'][0]
		);
	}


	//////////////////////////////////////
	// Tests concerning getConvertedData
	//////////////////////////////////////

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

		$result = $this->fixture->getConvertedData($node);
		$this->assertEquals(
			FALSE,
			$result[0]['elevator'],
			'The value for "elevator" is incorrect.'
		);
		$this->assertEquals(
			TRUE,
			$result[0]['fitted_kitchen'],
			'The value for "fitted_kitchen" is incorrect.'
		);
	}

	public function testGetConvertedDataImportsTheHoaFee() {
		$node = $this->setRawDataToConvert(
			'<openimmo>' .
				'<anbieter>' .
					'<immobilie>' .
						'<preise>' .
							'<hausgeld>12345</hausgeld>' .
						'</preise>' .
					'</immobilie>' .
				'</anbieter>' .
			'</openimmo>'
		);

		$result = $this->fixture->getConvertedData($node);
		$this->assertEquals(
			12345.00,
			$result[0]['hoa_fee']
		);
	}

	public function testGetConvertedDataImportsRentExcludingBillsFromNettokaltmiete() {
		$node = $this->setRawDataToConvert(
			'<openimmo>' .
				'<anbieter>' .
					'<immobilie>' .
						'<preise>' .
							'<nettokaltmiete>12345</nettokaltmiete>' .
						'</preise>' .
					'</immobilie>' .
				'</anbieter>' .
			'</openimmo>'
		);

		$result = $this->fixture->getConvertedData($node);
		$this->assertEquals(
			12345.00,
			$result[0]['rent_excluding_bills']
		);
	}

	public function testGetConvertedDataImportsRentExcludingBillsFromNettokaltmieteWhenKaltmieteIsPresent() {
		$node = $this->setRawDataToConvert(
			'<openimmo>' .
				'<anbieter>' .
					'<immobilie>' .
						'<preise>' .
							'<nettokaltmiete>12345</nettokaltmiete>' .
							'<kaltmiete>54321</kaltmiete>' .
						'</preise>' .
					'</immobilie>' .
				'</anbieter>' .
			'</openimmo>'
		);

		$result = $this->fixture->getConvertedData($node);
		$this->assertEquals(
			12345.00,
			$result[0]['rent_excluding_bills']
		);
	}

	public function testGetConvertedDataForNettokaltmieteMissingAndExistingKaltmieteImportsRentExcludingBillsFromKaltmiete() {
		$node = $this->setRawDataToConvert(
			'<openimmo>' .
				'<anbieter>' .
					'<immobilie>' .
						'<preise>' .
							'<kaltmiete>54321</kaltmiete>' .
						'</preise>' .
					'</immobilie>' .
				'</anbieter>' .
			'</openimmo>'
		);

		$result = $this->fixture->getConvertedData($node);
		$this->assertEquals(
			54321.00,
			$result[0]['rent_excluding_bills']
		);
	}

	public function testGetConvertedDataForNettokaltmieteEmptyAndNonEmptyKaltmieteImportsRentExcludingBillsFromKaltmiete() {
		$node = $this->setRawDataToConvert(
			'<openimmo>' .
				'<anbieter>' .
					'<immobilie>' .
						'<preise>' .
							'<nettokaltmiete></nettokaltmiete>' .
							'<kaltmiete>54321</kaltmiete>' .
						'</preise>' .
					'</immobilie>' .
				'</anbieter>' .
			'</openimmo>'
		);

		$result = $this->fixture->getConvertedData($node);
		$this->assertEquals(
			54321.00,
			$result[0]['rent_excluding_bills']
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

		$result = $this->fixture->getConvertedData($node);
		$this->assertEquals(
			'foo',
			$result[0]['language']
		);
	}

	public function testGetConvertedDataImportsLongitudeAndLatitudeAndSetsFlagIfBothAreProvided() {
		$node = $this->setRawDataToConvert(
			'<openimmo>' .
				'<anbieter>' .
					'<immobilie>' .
						'<geo>' .
							'<geokoordinaten laengengrad="1.23" breitengrad="4.56"/>' .
						'</geo>' .
					'</immobilie>' .
				'</anbieter>' .
			'</openimmo>'
		);

		$result = $this->fixture->getConvertedData($node);
		$this->assertTrue(
			$result[0]['exact_coordinates_are_cached']
		);
		$this->assertEquals(
			1.23,
			$result[0]['exact_longitude']
		);
		$this->assertEquals(
			4.56,
			$result[0]['exact_latitude']
		);
	}

	public function testGetConvertedDataNotImportsTheCoordinatesIfOnlyOneIsProvided() {
		$node = $this->setRawDataToConvert(
			'<openimmo>' .
				'<anbieter>' .
					'<immobilie>' .
						'<geo>' .
							'<geokoordinaten laengengrad="1.23"/>' .
						'</geo>' .
					'</immobilie>' .
				'</anbieter>' .
			'</openimmo>'
		);

		$result = $this->fixture->getConvertedData($node);
		$this->assertFalse(
			isset($result[0]['exact_coordinates_are_cached'])
		);
	}

	public function testGetConvertedDataNotImportsTheCoordinatesIfOneIsNonEmptyAndOneIsEmpty() {
		$node = $this->setRawDataToConvert(
			'<openimmo>' .
				'<anbieter>' .
					'<immobilie>' .
						'<geo>' .
							'<geokoordinaten laengengrad="1.23" breitengrad=""/>' .
						'</geo>' .
					'</immobilie>' .
				'</anbieter>' .
			'</openimmo>'
		);

		$result = $this->fixture->getConvertedData($node);
		$this->assertFalse(
			isset($result[0]['exact_coordinates_are_cached'])
		);
	}

	public function testGetConvertedDataImportsTheCountryAsUidOfTheStaticCountryTableForValidCode() {
		$node = $this->setRawDataToConvert(
			'<openimmo>' .
				'<anbieter>' .
					'<immobilie>' .
						'<geo>' .
							'<land iso_land="DEU"/>' .
						'</geo>' .
					'</immobilie>' .
				'</anbieter>' .
			'</openimmo>'
		);

		$result = $this->fixture->getConvertedData($node);
		$this->assertEquals(
			self::DE,
			$result[0]['country']
		);
	}

	public function testGetConvertedDataImportsTheCountryAsUidOfTheStaticCountryTableForValidCodeTwice() {
		$node = $this->setRawDataToConvert(
			'<openimmo>' .
				'<anbieter>' .
					'<immobilie>' .
						'<geo>' .
							'<land iso_land="DEU"/>' .
						'</geo>' .
					'</immobilie>' .
					'<immobilie>' .
						'<geo>' .
							'<land iso_land="DEU"/>' .
						'</geo>' .
					'</immobilie>' .
				'</anbieter>' .
			'</openimmo>'
		);

		$result = $this->fixture->getConvertedData($node);
		$this->assertEquals(
			self::DE,
			$result[0]['country'],
			'The first country is incorrect.'
		);
		$this->assertEquals(
			self::DE,
			$result[1]['country'],
			'The second country is incorrect.'
		);
	}

	public function testGetConvertedDataNotImportsTheCountryForInvalidCode() {
		$node = $this->setRawDataToConvert(
			'<openimmo>' .
				'<anbieter>' .
					'<immobilie>' .
						'<geo>' .
							'<land iso_land="foo"/>' .
						'</geo>' .
					'</immobilie>' .
				'</anbieter>' .
			'</openimmo>'
		);

		$result = $this->fixture->getConvertedData($node);
		$this->assertFalse(
			isset($result[0]['country'])
		);
	}

	public function testGetConvertedDataNotImportsTheCountryForEmptyCode() {
		$node = $this->setRawDataToConvert(
			'<openimmo>' .
				'<anbieter>' .
					'<immobilie>' .
						'<geo>' .
							'<land iso_land=""/>' .
						'</geo>' .
					'</immobilie>' .
				'</anbieter>' .
			'</openimmo>'
		);

		$result = $this->fixture->getConvertedData($node);
		$this->assertFalse(
			isset($result[0]['country'])
		);
	}

	public function testGetConvertedDataImportsTheCurrency() {
		$node = $this->setRawDataToConvert(
			'<openimmo>' .
				'<anbieter>' .
					'<immobilie>' .
						'<preise>' .
							'<waehrung iso_waehrung="EUR"/>' .
						'</preise>' .
					'</immobilie>' .
				'</anbieter>' .
			'</openimmo>'
		);

		$result = $this->fixture->getConvertedData($node);
		$this->assertEquals(
			'EUR',
			$result[0]['currency']
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

		$result = $this->fixture->getConvertedData($node);
		$this->assertEquals(
			1,
			$result[0]['old_or_new_building']
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

		$result = $this->fixture->getConvertedData($node);
		$this->assertEquals(
			2,
			$result[0]['old_or_new_building']
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

		$result = $this->fixture->getConvertedData($node);
		$this->assertFalse(
			isset($result[0]['old_or_new_building'])
		);
	}

	public function testGetConvertedDataImportsTheValueForShowAddressIfThisIsEnabled() {
		$node = $this->setRawDataToConvert(
			'<openimmo>' .
				'<anbieter>' .
					'<immobilie>' .
						'<verwaltung_objekt>' .
							'<objektadresse_freigeben>1</objektadresse_freigeben>' .
						'</verwaltung_objekt>' .
					'</immobilie>' .
				'</anbieter>' .
			'</openimmo>'
		);

		$result = $this->fixture->getConvertedData($node);
		$this->assertTrue(
			$result[0]['show_address']
		);
	}

	public function testGetConvertedDataImportsTheValueForShowAddressIfThisIsDisabled() {
		$node = $this->setRawDataToConvert(
			'<openimmo>' .
				'<anbieter>' .
					'<immobilie>' .
						'<verwaltung_objekt>' .
							'<objektadresse_freigeben>0</objektadresse_freigeben>' .
						'</verwaltung_objekt>' .
					'</immobilie>' .
				'</anbieter>' .
			'</openimmo>'
		);

		$result = $this->fixture->getConvertedData($node);
		$this->assertFalse(
			$result[0]['show_address']
		);
	}

	/**
	 * @test
	 */
	public function getConvertedDataImportsRentPerSquareMeter() {
		$node = $this->setRawDataToConvert(
			'<openimmo>' .
				'<anbieter>' .
					'<immobilie>' .
						'<preise>' .
							'<mietpreis_pro_qm>12.34</mietpreis_pro_qm>' .
						'</preise>' .
					'</immobilie>' .
				'</anbieter>' .
			'</openimmo>'
		);

		$result = $this->fixture->getConvertedData($node);
		$this->assertEquals(
			12.34,
			$result[0]['rent_per_square_meter']
		);
	}

	/**
	 * @test
	 */
	public function getConvertedDataImportsLivingArea() {
		$node = $this->setRawDataToConvert(
			'<openimmo>' .
				'<anbieter>' .
					'<immobilie>' .
						'<flaechen>' .
							'<wohnflaeche>123.45</wohnflaeche>' .
						'</flaechen>' .
					'</immobilie>' .
				'</anbieter>' .
			'</openimmo>'
		);

		$result = $this->fixture->getConvertedData($node);
		$this->assertEquals(
			123.45,
			$result[0]['living_area']
		);
	}

	/**
	 * @test
	 */
	public function getConvertedDataImportsTotalUsableArea() {
		$node = $this->setRawDataToConvert(
			'<openimmo>' .
				'<anbieter>' .
					'<immobilie>' .
						'<flaechen>' .
							'<nutzflaeche>123.45</nutzflaeche>' .
						'</flaechen>' .
					'</immobilie>' .
				'</anbieter>' .
			'</openimmo>'
		);

		$result = $this->fixture->getConvertedData($node);
		$this->assertEquals(
			123.45,
			$result[0]['total_usable_area']
		);
	}

	/**
	 * @test
	 */
	public function getConvertedDataImportsTotalArea() {
		$node = $this->setRawDataToConvert(
			'<openimmo>' .
				'<anbieter>' .
					'<immobilie>' .
						'<flaechen>' .
							'<gesamtflaeche>123.45</gesamtflaeche>' .
						'</flaechen>' .
					'</immobilie>' .
				'</anbieter>' .
			'</openimmo>'
		);

		$result = $this->fixture->getConvertedData($node);
		$this->assertEquals(
			123.45,
			$result[0]['total_area']
		);
	}

	/**
	 * @test
	 */
	public function getConvertedDataImportsShopArea() {
		$node = $this->setRawDataToConvert(
			'<openimmo>' .
				'<anbieter>' .
					'<immobilie>' .
						'<flaechen>' .
							'<ladenflaeche>123.45</ladenflaeche>' .
						'</flaechen>' .
					'</immobilie>' .
				'</anbieter>' .
			'</openimmo>'
		);

		$result = $this->fixture->getConvertedData($node);
		$this->assertEquals(
			123.45,
			$result[0]['shop_area']
		);
	}

	/**
	 * @test
	 */
	public function getConvertedDataImportsSalesArea() {
		$node = $this->setRawDataToConvert(
			'<openimmo>' .
				'<anbieter>' .
					'<immobilie>' .
						'<flaechen>' .
							'<verkaufsflaeche>123.45</verkaufsflaeche>' .
						'</flaechen>' .
					'</immobilie>' .
				'</anbieter>' .
			'</openimmo>'
		);

		$result = $this->fixture->getConvertedData($node);
		$this->assertEquals(
			123.45,
			$result[0]['sales_area']
		);
	}

	/**
	 * @test
	 */
	public function getConvertedDataImportsStorageArea() {
		$node = $this->setRawDataToConvert(
			'<openimmo>' .
				'<anbieter>' .
					'<immobilie>' .
						'<flaechen>' .
							'<lagerflaeche>123.45</lagerflaeche>' .
						'</flaechen>' .
					'</immobilie>' .
				'</anbieter>' .
			'</openimmo>'
		);

		$result = $this->fixture->getConvertedData($node);
		$this->assertEquals(
			123.45,
			$result[0]['storage_area']
		);
	}

	/**
	 * @test
	 */
	public function getConvertedDataImportsOfficeSpace() {
		$node = $this->setRawDataToConvert(
			'<openimmo>' .
				'<anbieter>' .
					'<immobilie>' .
						'<flaechen>' .
							'<bueroflaeche>123.45</bueroflaeche>' .
						'</flaechen>' .
					'</immobilie>' .
				'</anbieter>' .
			'</openimmo>'
		);

		$result = $this->fixture->getConvertedData($node);
		$this->assertEquals(
			123.45,
			$result[0]['office_space']
		);
	}

	/**
	 * @test
	 */
	public function getConvertedDataImportsOtherArea() {
		$node = $this->setRawDataToConvert(
			'<openimmo>' .
				'<anbieter>' .
					'<immobilie>' .
						'<flaechen>' .
							'<sonstflaeche>123.45</sonstflaeche>' .
						'</flaechen>' .
					'</immobilie>' .
				'</anbieter>' .
			'</openimmo>'
		);

		$result = $this->fixture->getConvertedData($node);
		$this->assertEquals(
			123.45,
			$result[0]['other_area']
		);
	}

	/**
	 * @test
	 */
	public function getConvertedDataImportsWindowBank() {
		$node = $this->setRawDataToConvert(
			'<openimmo>' .
				'<anbieter>' .
					'<immobilie>' .
						'<flaechen>' .
							'<fensterfront>12.34</fensterfront>' .
						'</flaechen>' .
					'</immobilie>' .
				'</anbieter>' .
			'</openimmo>'
		);

		$result = $this->fixture->getConvertedData($node);
		$this->assertEquals(
			12.34,
			$result[0]['window_bank']
		);
	}

	/**
	 * @test
	 */
	public function getConvertedDataImportsFloorSpaceIndex() {
		$node = $this->setRawDataToConvert(
			'<openimmo>' .
				'<anbieter>' .
					'<immobilie>' .
						'<flaechen>' .
							'<grz>0.12</grz>' .
						'</flaechen>' .
					'</immobilie>' .
				'</anbieter>' .
			'</openimmo>'
		);

		$result = $this->fixture->getConvertedData($node);
		$this->assertEquals(
			0.12,
			$result[0]['floor_space_index']
		);
	}

	/**
	 * @test
	 */
	public function getConvertedDataImportsSiteOccupancyIndex() {
		$node = $this->setRawDataToConvert(
			'<openimmo>' .
				'<anbieter>' .
					'<immobilie>' .
						'<flaechen>' .
							'<gfz>0.12</gfz>' .
						'</flaechen>' .
					'</immobilie>' .
				'</anbieter>' .
			'</openimmo>'
		);

		$result = $this->fixture->getConvertedData($node);
		$this->assertEquals(
			0.12,
			$result[0]['site_occupancy_index']
		);
	}

	/**
	 * @test
	 */
	public function getConvertedDataImportsEstateSize() {
		$node = $this->setRawDataToConvert(
			'<openimmo>' .
				'<anbieter>' .
					'<immobilie>' .
						'<flaechen>' .
							'<grundstuecksflaeche>123.45</grundstuecksflaeche>' .
						'</flaechen>' .
					'</immobilie>' .
				'</anbieter>' .
			'</openimmo>'
		);

		$result = $this->fixture->getConvertedData($node);
		$this->assertEquals(
			123.45,
			$result[0]['estate_size']
		);
	}

	/**
	 * @test
	 */
	public function getConvertedDataImportsNumberOfRooms() {
		$node = $this->setRawDataToConvert(
			'<openimmo>' .
				'<anbieter>' .
					'<immobilie>' .
						'<flaechen>' .
							'<anzahl_zimmer>3.5</anzahl_zimmer>' .
						'</flaechen>' .
					'</immobilie>' .
				'</anbieter>' .
			'</openimmo>'
		);

		$importedData = $this->fixture->getConvertedData($node);
		$this->assertEquals(
			3.5,
			$importedData[0]['number_of_rooms']
		);
	}

	/**
	 * @test
	 */
	public function getConvertedDataImportsNumberOfBedrooms() {
		$node = $this->setRawDataToConvert(
			'<openimmo>' .
				'<anbieter>' .
					'<immobilie>' .
						'<flaechen>' .
							'<anzahl_schlafzimmer>2</anzahl_schlafzimmer>' .
						'</flaechen>' .
					'</immobilie>' .
				'</anbieter>' .
			'</openimmo>'
		);

		$importedData = $this->fixture->getConvertedData($node);
		$this->assertEquals(
			2,
			$importedData[0]['bedrooms']
		);
	}

	/**
	 * @test
	 */
	public function getConvertedDataImportsNumberOfBathrooms() {
		$node = $this->setRawDataToConvert(
			'<openimmo>' .
				'<anbieter>' .
					'<immobilie>' .
						'<flaechen>' .
							'<anzahl_badezimmer>2</anzahl_badezimmer>' .
						'</flaechen>' .
					'</immobilie>' .
				'</anbieter>' .
			'</openimmo>'
		);

		$importedData = $this->fixture->getConvertedData($node);
		$this->assertEquals(
			2,
			$importedData[0]['bathrooms']
		);
	}

	/**
	 * @test
	 */
	public function getConvertedDataImportsNumberOfBalconies() {
		$node = $this->setRawDataToConvert(
			'<openimmo>' .
				'<anbieter>' .
					'<immobilie>' .
						'<flaechen>' .
							'<anzahl_balkon_terrassen>1</anzahl_balkon_terrassen>' .
						'</flaechen>' .
					'</immobilie>' .
				'</anbieter>' .
			'</openimmo>'
		);

		$importedData = $this->fixture->getConvertedData($node);
		$this->assertEquals(
			1,
			$importedData[0]['balcony']
		);
	}

	/**
	 * @test
	 */
	public function getConvertedDataImportsNumberOfParkingSpaces() {
		$node = $this->setRawDataToConvert(
			'<openimmo>' .
				'<anbieter>' .
					'<immobilie>' .
						'<flaechen>' .
							'<anzahl_stellplaetze>2</anzahl_stellplaetze>' .
						'</flaechen>' .
					'</immobilie>' .
				'</anbieter>' .
			'</openimmo>'
		);

		$importedData = $this->fixture->getConvertedData($node);
		$this->assertEquals(
			2,
			$importedData[0]['parking_spaces']
		);
	}

	/**
	 * @test
	 */
	public function getConvertedDataImportsRentalIncomeTarget() {
		$node = $this->setRawDataToConvert(
			'<openimmo>' .
				'<anbieter>' .
					'<immobilie>' .
						'<preise>' .
							'<mieteinnahmen_soll>12345.67</mieteinnahmen_soll>' .
						'</preise>' .
					'</immobilie>' .
				'</anbieter>' .
			'</openimmo>'
		);

		$result = $this->fixture->getConvertedData($node);
		$this->assertEquals(
			12345.67,
			$result[0]['rental_income_target']
		);
	}


	////////////////////////////////////////
	// Tests concerning fetchDomAttributes
	////////////////////////////////////////

	public function testFetchDomAttributesIfValidNodeGiven() {
		$node = new DOMDocument();
		$element = $node->appendChild(
			$node->createElement('foo')
		);
		$element->setAttributeNode(new DOMAttr('foo', 'bar'));

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
}
?>