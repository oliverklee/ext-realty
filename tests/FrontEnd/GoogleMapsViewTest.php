<?php
/***************************************************************
* Copyright notice
*
* (c) 2008-2013 Saskia Metzler <saskia@merlin.owl.de>
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
 * Test case.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 */
class tx_realty_FrontEnd_GoogleMapsViewTest extends tx_phpunit_testcase {
	/**
	 * @var tx_realty_pi1_GoogleMapsView
	 */
	private $fixture;
	/**
	 * @var tx_oelib_testingFramework
	 */
	private $testingFramework;

	/**
	 * @var integer dummy realty object
	 */
	private $realtyUid = 0;

	/**
	 * @var integer dummy city UID
	 */
	private $cityUid = 0;

	/**
	 * @var string title for the dummy city
	 */
	private static $cityTitle = 'Bonn';

	/**
	 * @var string a valid Google Maps API key for localhost
	 */
	const GOOGLE_MAPS_API_KEY = 'ABQIAAAAbDm1mvIP78sIsBcIbMgOPRT2yXp_ZAY8_ufC3CFXhHIE1NvwkxTwV0FqSWhHhsXRyGQ_btfZ1hNR7g';

	/**
	 * @var float latitude
	 */
	const LATITUDE = 50.7;

	/**
	 * @var float longitude
	 */
	const LONGITUDE = 7.1;

	public function setUp() {
		$this->testingFramework = new tx_oelib_testingFramework('tx_realty');
		$this->testingFramework->createFakeFrontEnd();

		tx_oelib_MapperRegistry::getInstance()
			->activateTestingMode($this->testingFramework);

		$this->cityUid = $this->testingFramework->createRecord(
			REALTY_TABLE_CITIES, array('title' => self::$cityTitle)
		);
		$this->realtyUid = $this->testingFramework->createRecord(
			REALTY_TABLE_OBJECTS,
			array(
				'title' => 'test realty object',
				'object_number' => '12345',
				'pid' => $this->testingFramework->createFrontEndPage(),
				'city' => $this->cityUid,
			)
		);

		$geoFinder = new tx_realty_tests_fixtures_FakeGoogleMapsLookup();
		$geoFinder->setCoordinates(self::LATITUDE, self::LONGITUDE);
		tx_realty_googleMapsLookup::setInstance($geoFinder);

		$this->fixture = new tx_realty_pi1_GoogleMapsView(
			array(
				'templateFile' => 'EXT:realty/pi1/tx_realty_pi1.tpl.htm',
				'googleMapsApiKey' => self::GOOGLE_MAPS_API_KEY,
				'defaultCountryUID' => 54,
			),
			$GLOBALS['TSFE']->cObj,
			TRUE
		);
	}

	public function tearDown() {
		$this->fixture->__destruct();

		$this->testingFramework->cleanUp();

		tx_realty_googleMapsLookup::purgeInstance();

		unset($this->fixture, $this->testingFramework);
	}


	///////////////////////////////////
	// Tests for the Google Maps view
	///////////////////////////////////

	/**
	 * @test
	 */
	public function renderGoogleMapsViewWithNoCollectedMarkersReturnsEmptyResult() {
		$this->assertEquals(
			'',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function renderGoogleMapsViewWithCollectedMarkerReturnsNonEmptyResult() {
		$this->fixture->setMapMarker($this->realtyUid);

		$this->assertContains(
			'<div id="tx_realty_map"',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function renderGoogleMapsViewWithCollectedMarkerReturnsNoUnreplacedMarkers() {
		$this->fixture->setMapMarker($this->realtyUid);

		$this->assertNotContains(
			'###',
			$this->fixture->render(array('message_access_denied'))
		);
	}

	/**
	 * @test
	 */
	public function setMapMarkerForZeroCausesException() {
		$this->setExpectedException(
			'InvalidArgumentException',
			'$realtyObjectUid must not be an integer greater than zero.'
		);

		$this->fixture->setMapMarker(0);
	}

	/**
	 * @test
	 */
	public function renderGoogleMapsViewWhenEnabledAndExactAddressMarksCoordinatesAsCached() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->realtyUid,
			array(
				'street' => 'Am Hof 1',
				'city' => $this->cityUid,
				'show_address' => TRUE,
			)
		);

		$this->fixture->setMapMarker($this->realtyUid);
		$this->fixture->render();

		$this->assertTrue(
			$this->testingFramework->existsExactlyOneRecord(
				REALTY_TABLE_OBJECTS,
				'uid = ' . $this->realtyUid . ' AND has_coordinates = 1'
			)
		);
	}

	/**
	 * @test
	 */
	public function renderGoogleMapsViewReturnsMapForObjectWithExactAddressAndGoogleMapsEnabled() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->realtyUid,
			array(
				'has_coordinates' => TRUE,
				'latitude' => self::LATITUDE,
				'longitude' => self::LONGITUDE,
				'show_address' => TRUE,
			)
		);

		$this->fixture->setMapMarker($this->realtyUid);

		$this->assertContains(
			'<div id="tx_realty_map"',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function renderGoogleMapsViewNotReturnsMapForObjectWithGeoErrorAndGoogleMapsEnabled() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->realtyUid,
			array(
				'has_coordinates' => TRUE,
				'latitude' => self::LATITUDE,
				'longitude' => self::LONGITUDE,
				'coordinates_problem' => TRUE,
			)
		);

		$this->fixture->setMapMarker($this->realtyUid);

		$this->assertNotContains(
			'<div id="tx_realty_map"',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function renderGoogleMapsViewAddsGoogleMapsJavaScriptForObjectWithCachedAddressAndGoogleMapsEnabled() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->realtyUid,
			array(
				'has_coordinates' => TRUE,
				'latitude' => self::LATITUDE,
				'longitude' => self::LONGITUDE,
				'show_address' => TRUE,
			)
		);

		$this->fixture->setMapMarker($this->realtyUid);
		$this->fixture->render();

		$this->assertTrue(
			isset($GLOBALS['TSFE']->additionalHeaderData['tx_realty_pi1_maps'])
		);
	}

	/**
	 * @test
	 */
	public function renderGoogleMapsViewNotAddsGoogleMapsJavaScriptForObjectWithGeoErrorAndAddressAndGoogleMapsEnabled() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->realtyUid,
			array(
				'has_coordinates' => TRUE,
				'latitude' => self::LATITUDE,
				'longitude' => self::LONGITUDE,
				'coordinates_problem' => TRUE,
				'show_address' => TRUE,
			)
		);

		$this->fixture->setMapMarker($this->realtyUid);
		$this->fixture->render();

		$this->assertFalse(
			isset($GLOBALS['TSFE']->additionalHeaderData['tx_realty_pi1_maps'])
		);
	}

	/**
	 * @test
	 */
	public function renderGoogleMapsViewAddsOnLoadForObjectWithCoordinatesAndGoogleMapsEnabled() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->realtyUid,
			array(
				'has_coordinates' => TRUE,
				'latitude' => self::LATITUDE,
				'longitude' => self::LONGITUDE,
				'show_address' => TRUE,
			)
		);

		$this->fixture->setMapMarker($this->realtyUid);
		$this->fixture->render();

		$this->assertTrue(
			isset($GLOBALS['TSFE']
				->JSeventFuncCalls['onload']['tx_realty_pi1_maps']
			)
		);
	}

	/**
	 * @test
	 */
	public function renderGoogleMapsViewAddsOnUnloadForObjectWithCoordinatesAndGoogleMapsEnabled() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->realtyUid,
			array(
				'has_coordinates' => TRUE,
				'latitude' => self::LATITUDE,
				'longitude' => self::LONGITUDE,
				'show_address' => TRUE,
			)
		);

		$this->fixture->setMapMarker($this->realtyUid);
		$this->fixture->render();

		$this->assertTrue(
			isset($GLOBALS['TSFE']
				->JSeventFuncCalls['onunload']['tx_realty_pi1_maps']
			)
		);
	}

	/**
	 * @test
	 */
	public function renderGoogleMapsViewReturnsCoordinatesInJavaScriptForGoogleMapsEnabled() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->realtyUid,
			array(
				'has_coordinates' => TRUE,
				'latitude' => self::LATITUDE,
				'longitude' => self::LONGITUDE,
				'show_address' => TRUE,
			)
		);

		$this->fixture->setMapMarker($this->realtyUid);
		$this->fixture->render();

		$this->assertContains(
			(string) self::LATITUDE,
			$GLOBALS['TSFE']->additionalHeaderData['tx_realty_pi1_maps']
		);
		$this->assertContains(
			(string) self::LONGITUDE,
			$GLOBALS['TSFE']->additionalHeaderData['tx_realty_pi1_maps']
		);
	}

	/**
	 * @test
	 */
	public function renderGoogleMapsViewForShowAddressTrueReturnsTheObjectsFullAddressAsTitleForGoogleMaps() {
		$cityUid = $this->testingFramework->createRecord(
			REALTY_TABLE_CITIES, array('title' => 'Test Town')
		);
		$districtUid = $this->testingFramework->createRecord(
			REALTY_TABLE_DISTRICTS, array('title' => 'District')
		);
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->realtyUid,
			array(
				'title' => 'foo',
				'has_coordinates' => TRUE,
				'latitude' => self::LATITUDE,
				'longitude' => self::LONGITUDE,
				'show_address' => TRUE,
				'street' => 'Main Street',
				'zip' => '12345',
				'city' => $cityUid,
				'district' => $districtUid,
				'country' => 54,
			)
		);

		$this->fixture->setMapMarker($this->realtyUid);
		$this->fixture->render();

		$this->assertContains(
			'title: "Main Street, 12345 Test Town District, Deutschland"',
			$GLOBALS['TSFE']->additionalHeaderData['tx_realty_pi1_maps']
		);
	}

	/**
	 * @test
	 */
	public function renderGoogleMapsViewForShowAddressFalseReturnsTheObjectsAddressWithoutStreetAsTitleForGoogleMaps() {
		$cityUid = $this->testingFramework->createRecord(
			REALTY_TABLE_CITIES, array('title' => 'Test Town')
		);
		$districtUid = $this->testingFramework->createRecord(
			REALTY_TABLE_DISTRICTS, array('title' => 'District')
		);
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->realtyUid,
			array(
				'title' => 'foo',
				'has_coordinates' => TRUE,
				'latitude' => self::LATITUDE,
				'longitude' => self::LONGITUDE,
				'show_address' => FALSE,
				'street' => 'Main Street',
				'zip' => '12345',
				'city' => $cityUid,
				'district' => $districtUid,
				'country' => 54,
			)
		);

		$this->fixture->setMapMarker($this->realtyUid);
		$this->fixture->render();

		$this->assertContains(
			'title: "12345 Test Town District, Deutschland"',
			$GLOBALS['TSFE']->additionalHeaderData['tx_realty_pi1_maps']
		);
	}

	/**
	 * @test
	 */
	public function renderGoogleMapsViewReturnsCroppedObjectTitleAsInfoWindowForGoogleMaps() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->realtyUid,
			array(
				'title' => 'A really long title that is not too short.',
				'has_coordinates' => TRUE,
				'latitude' => self::LATITUDE,
				'longitude' => self::LONGITUDE,
				'show_address' => TRUE,
			)
		);

		$this->fixture->setMapMarker($this->realtyUid);
		$this->fixture->render();

		$this->assertRegExp(
			'/bindInfoWindowHtml\(\'[^\']*A really long title that is not …/',
			$GLOBALS['TSFE']->additionalHeaderData['tx_realty_pi1_maps']
		);
	}

	/**
	 * @test
	 */
	public function renderGoogleMapsViewHasTheObjectsCityAndDistrictAsInfoWindowForGoogleMaps() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->realtyUid,
			array(
				'has_coordinates' => TRUE,
				'latitude' => self::LATITUDE,
				'longitude' => self::LONGITUDE,
				'district' => $this->testingFramework->createRecord(
					REALTY_TABLE_DISTRICTS,
					array('title' => 'Beuel')
				),
				'show_address' => TRUE,
			)
		);

		$this->fixture->setMapMarker($this->realtyUid);
		$this->fixture->render();

		$this->assertRegExp(
			'/bindInfoWindowHtml\(\'[^\']*' . self::$cityTitle . ' Beuel/',
			$GLOBALS['TSFE']->additionalHeaderData['tx_realty_pi1_maps']
		);
	}

	/**
	 * @test
	 */
	public function renderGoogleMapsViewHasStreetAsInfoWindowForGoogleMapsForDetailedAddress() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->realtyUid,
			array(
				'has_coordinates' => TRUE,
				'latitude' => self::LATITUDE,
				'longitude' => self::LONGITUDE,
				'street' => 'Foo road',
				'show_address' => TRUE,
			)
		);

		$this->fixture->setMapMarker($this->realtyUid);
		$this->fixture->render();

		$this->assertRegExp(
			'/bindInfoWindowHtml\(\'[^\']*Foo road/',
			$GLOBALS['TSFE']->additionalHeaderData['tx_realty_pi1_maps']
		);
	}

	/**
	 * @test
	 */
	public function renderGoogleMapsViewWithMapMarkerWithoutCreateLinkOptiontDoesNotLinkObjectTitleInMap() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->realtyUid,
			array(
				'has_coordinates' => TRUE,
				'latitude' => self::LATITUDE,
				'longitude' => self::LONGITUDE,
				'street' => 'Foo road',
				'show_address' => TRUE,
			)
		);

		$this->fixture->setConfigurationValue(
			'singlePID', $this->testingFramework->createFrontEndPage()
		);
		$this->fixture->setMapMarker($this->realtyUid);
		$this->fixture->render();

		$this->assertNotContains(
			'href=',
			$GLOBALS['TSFE']->additionalHeaderData['tx_realty_pi1_maps']
		);
	}

	/**
	 * @test
	 */
	public function renderGoogleMapsViewWithMapMarkerWithCreateLinkOptionLinksObjectTitleInMap() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->realtyUid,
			array(
				'has_coordinates' => TRUE,
				'latitude' => self::LATITUDE,
				'longitude' => self::LONGITUDE,
				'street' => 'Foo road',
				'show_address' => TRUE,
			)
		);

		$this->fixture->setConfigurationValue(
			'singlePID', $this->testingFramework->createFrontEndPage()
		);
		$this->fixture->setMapMarker($this->realtyUid, TRUE);
		$this->fixture->render();

		$this->assertContains(
			'href=',
			$GLOBALS['TSFE']->additionalHeaderData['tx_realty_pi1_maps']
		);
	}

	/**
	 * @test
	 */
	public function renderGoogleMapsViewOmitsStreetAsInfoWindowForGoogleMapsForRoughAddress() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->realtyUid,
			array(
				'has_coordinates' => TRUE,
				'latitude' => self::LATITUDE,
				'longitude' => self::LONGITUDE,
				'street' => 'Foo road',
				'show_address' => FALSE,
			)
		);

		$this->fixture->setMapMarker($this->realtyUid);
		$this->fixture->render();

		$this->assertNotRegExp(
			'/bindInfoWindowHtml\(\'[^\']*Foo road/',
			$GLOBALS['TSFE']->additionalHeaderData['tx_realty_pi1_maps']
		);
	}

	/**
	 * @test
	 */
	public function retrievingGeoCoordinatesDoesNotDeleteAppendedImage() {
		$this->testingFramework->createRecord(
			REALTY_TABLE_IMAGES,
			array(
				'caption'=>'foo.jpg',
				'image' => 'foo.jpg',
				'object' => $this->realtyUid,
			)
		);
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->realtyUid,
			array(
				'street' => 'Am Hof 1',
				'city' => $this->cityUid,
				'show_address' => TRUE,
				'images' => 1,
			)
		);

		$this->fixture->setMapMarker($this->realtyUid);
		$this->fixture->render();

		$this->assertTrue(
			$this->testingFramework->existsExactlyOneRecord(
				REALTY_TABLE_IMAGES,
				'caption="foo.jpg" AND image="foo.jpg" AND deleted=0'
			)
		);
	}

	/**
	 * @test
	 */
	public function renderSetsCachedCoordinatesOfTwoObjectsInHeader() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS, $this->realtyUid,
			array(
				'has_coordinates' => TRUE,
				'latitude' => self::LATITUDE,
				'longitude' => self::LONGITUDE,
				'show_address' => TRUE,
			)
		);
		$this->fixture->setMapMarker($this->realtyUid);
		$this->fixture->setMapMarker(
			$this->testingFramework->createRecord(
				REALTY_TABLE_OBJECTS, array(
					'title' => 'second test object',
					'object_number' => '6789',
					'has_coordinates' => TRUE,
					'latitude' => self::LATITUDE + 1,
					'longitude' => self::LONGITUDE + 1,
					'show_address' => TRUE,
				)
			)
		);

		$this->fixture->render();

		// We need to allow for additional digits due to rounding errors.
		$this->assertRegExp(
			'/' . (self::LATITUDE + 1) . '\d*,' . (self::LONGITUDE + 1) . '/',
			$GLOBALS['TSFE']->additionalHeaderData['tx_realty_pi1_maps']
		);
		$this->assertRegExp(
			'/' . self::LATITUDE . '\d*,' . self::LONGITUDE . '/',
			$GLOBALS['TSFE']->additionalHeaderData['tx_realty_pi1_maps']
		);
	}

	/**
	 * @test
	 */
	public function renderSetsFullTitlesOfTwoObjectsInHeader() {
		$cityUid = $this->testingFramework->createRecord(
			REALTY_TABLE_CITIES, array('title' => 'Test Town')
		);
		$districtUid = $this->testingFramework->createRecord(
			REALTY_TABLE_DISTRICTS, array('title' => 'District')
		);
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS, $this->realtyUid,
			array(
				'has_coordinates' => TRUE,
				'latitude' => self::LATITUDE + 1,
				'longitude' => self::LONGITUDE + 1,
				'show_address' => TRUE,
				'street' => 'foo street',
				'zip' => '12345',
				'city' => $cityUid,
				'district' => $districtUid,
				'country' => 54,
			)
		);
		$this->fixture->setMapMarker($this->realtyUid);
		$this->fixture->setMapMarker(
			$this->testingFramework->createRecord(
				REALTY_TABLE_OBJECTS, array(
					'title' => 'second test object',
					'object_number' => '6789',
					'has_coordinates' => TRUE,
					'latitude' => self::LATITUDE,
					'longitude' => self::LONGITUDE,
					'show_address' => TRUE,
					'street' => 'bar street',
					'zip' => '12345',
					'city' => $cityUid,
					'district' => $districtUid,
					'country' => 54,
				)
			)
		);

		$this->fixture->render();

		$this->assertTrue(
			isset($GLOBALS['TSFE']->additionalHeaderData['tx_realty_pi1_maps'])
		);

		$this->assertContains(
			'title: "foo street, 12345 Test Town District, Deutschland"',
			$GLOBALS['TSFE']->additionalHeaderData['tx_realty_pi1_maps']
		);
		$this->assertContains(
			'title: "bar street, 12345 Test Town District, Deutschland"',
			$GLOBALS['TSFE']->additionalHeaderData['tx_realty_pi1_maps']
		);
	}

	/**
	 * @test
	 */
	public function renderSetsAutoZoomForTwoObjectsWithCoordinates() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS, $this->realtyUid,
			array(
				'has_coordinates' => TRUE,
				'latitude' => self::LATITUDE + 1,
				'longitude' => self::LONGITUDE + 1,
				'show_address' => TRUE,
			)
		);
		$this->fixture->setMapMarker($this->realtyUid);
		$this->fixture->setMapMarker(
			$this->testingFramework->createRecord(
				REALTY_TABLE_OBJECTS, array(
					'title' => 'second test object',
					'object_number' => '6789',
					'has_coordinates' => TRUE,
					'latitude' => self::LATITUDE,
					'longitude' => self::LONGITUDE,
					'show_address' => TRUE,
				)
			)
		);

		$this->fixture->render();

		$this->assertContains(
			'setZoom',
			$GLOBALS['TSFE']->additionalHeaderData['tx_realty_pi1_maps']
		);
	}

	/**
	 * @test
	 */
	public function renderSetsDoesNotSetAutoZoomForOnlyOneObjectWithCoordinates() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS, $this->realtyUid,
			array(
				'has_coordinates' => TRUE,
				'coordinates_problem' => TRUE,
				'show_address' => TRUE,
			)
		);
		$this->fixture->setMapMarker($this->realtyUid);
		$this->fixture->setMapMarker(
			$this->testingFramework->createRecord(
				REALTY_TABLE_OBJECTS, array(
					'title' => 'second test object',
					'object_number' => '6789',
					'has_coordinates' => TRUE,
					'latitude' => self::LATITUDE,
					'longitude' => self::LONGITUDE,
					'show_address' => TRUE,
				)
			)
		);

		$this->fixture->render();

		$this->assertNotContains(
			'setZoom',
			$GLOBALS['TSFE']->additionalHeaderData['tx_realty_pi1_maps']
		);
	}
}
?>