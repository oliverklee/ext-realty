<?php
/***************************************************************
* Copyright notice
*
* (c) 2008-2009 Saskia Metzler <saskia@merlin.owl.de>
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

require_once(t3lib_extMgm::extPath('oelib') . 'class.tx_oelib_Autoloader.php');

require_once(t3lib_extMgm::extPath('realty') . 'lib/tx_realty_constants.php');

/**
 * Unit tests for the tx_realty_pi1_GoogleMapsView class in the 'realty'
 * extension.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 */
class tx_realty_GoogleMapsView_testcase extends tx_phpunit_testcase {
	/** @var tx_realty_pi1_GoogleMapsView */
	private $fixture;
	/** @var tx_oelib_testingFramework */
	private $testingFramework;

	/** dummy realty object */
	private $realtyUid = 0;

	/** dummy city UID */
	private $cityUid = 0;
	/** title for the dummy city */
	private static $cityTitle = 'Bonn';

	/** @var string a valid Google Maps API key for localhost */
	const GOOGLE_MAPS_API_KEY = 'ABQIAAAAbDm1mvIP78sIsBcIbMgOPRT2yXp_ZAY8_ufC3CFXhHIE1NvwkxTwV0FqSWhHhsXRyGQ_btfZ1hNR7g';

	public function setUp() {
		$this->testingFramework = new tx_oelib_testingFramework('tx_realty');
		$this->testingFramework->createFakeFrontEnd();

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

		$this->fixture = new tx_realty_pi1_GoogleMapsView(
			array(
				'templateFile' => 'EXT:realty/pi1/tx_realty_pi1.tpl.htm',
				'googleMapsApiKey' => self::GOOGLE_MAPS_API_KEY,
				'showGoogleMaps' => 1,
				'defaultCountryUID' => 54,
			),
			$GLOBALS['TSFE']->cObj,
			true
		);
	}

	public function tearDown() {
		$this->testingFramework->cleanUp();

		$this->fixture->__destruct();
		unset($this->fixture, $this->testingFramework);
	}


	///////////////////////////////////
	// Tests for the Google Maps view
	///////////////////////////////////

	public function testRenderGoogleMapsViewWithNoCollectedMarkersReturnsEmptyResult() {
		$this->assertEquals(
			'',
			$this->fixture->render()
		);
	}

	public function testRenderGoogleMapsViewWithCollectedMarkerReturnsNonEmptyResult() {
		$this->fixture->setMapMarker($this->realtyUid);

		$this->assertContains(
			'<div id="tx_realty_map"',
			$this->fixture->render()
		);
	}

	public function testSetMapMarkerForZeroCausesException() {
		$this->setExpectedException(
			'Exception',
			'$realtyObjectUid must not be an integer greater than zero.'
		);

		$this->fixture->setMapMarker(0);
	}

	public function testRenderGoogleMapsViewWhenDisabledByConfigurationDoesNotMarkAnyCoordinatesAsCached() {
		$this->fixture->setConfigurationValue('showGoogleMaps', 0);
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->realtyUid,
			array(
				'street' => 'Am Hof 1',
				'city' => $this->cityUid,
				'show_address' => 1,
			)
		);

		$this->fixture->setMapMarker($this->realtyUid);
		$this->fixture->render();

		$this->assertTrue(
			$this->testingFramework->existsExactlyOneRecord(
				REALTY_TABLE_OBJECTS,
				'uid = ' . $this->realtyUid .
					' AND exact_coordinates_are_cached = 0' .
					' AND rough_coordinates_are_cached = 0'
				)
		);
	}

	public function testRenderGoogleMapsViewWhenEnabledAndExactAddressMarksExactCoordinatesAsCached() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->realtyUid,
			array(
				'street' => 'Am Hof 1',
				'city' => $this->cityUid,
				'show_address' => 1,
			)
		);

		$this->fixture->setMapMarker($this->realtyUid);
		$this->fixture->render();

		$this->assertTrue(
			$this->testingFramework->existsExactlyOneRecord(
				REALTY_TABLE_OBJECTS,
				'uid = ' . $this->realtyUid .
					' AND exact_coordinates_are_cached = 1' .
					' AND rough_coordinates_are_cached = 0'
				)
		);
	}

	public function testRenderGoogleMapsViewWhenEnabledAndRoughAddressMarksRoughCoordinatesAsCached() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->realtyUid,
			array(
				'street' => 'Am Hof 1',
				'city' => $this->cityUid,
				'show_address' => 0,
			)
		);

		$this->fixture->setMapMarker($this->realtyUid);
		$this->fixture->render();

		$this->assertTrue(
			$this->testingFramework->existsExactlyOneRecord(
				REALTY_TABLE_OBJECTS,
				'uid = ' . $this->realtyUid .
					' AND exact_coordinates_are_cached = 0' .
					' AND rough_coordinates_are_cached = 1'
				)
		);
	}

	public function testRenderGoogleMapsViewReturnsMapForObjectWithCachedAddressAndGoogleMapsEnabled() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->realtyUid,
			array(
				'exact_coordinates_are_cached' => 1,
				'exact_latitude' => 50.734343,
				'exact_longitude' => 7.10211,
				'show_address' => 1,
			)
		);

		$this->fixture->setMapMarker($this->realtyUid);

		$this->assertContains(
			'<div id="tx_realty_map"',
			$this->fixture->render()
		);
	}

	public function testRenderGoogleMapsViewNotReturnsMapForObjectWithEmptyCachedAddressAndGoogleMapsEnabled() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->realtyUid,
			array(
				'exact_coordinates_are_cached' => 1,
				'exact_latitude' => '',
				'exact_longitude' => '',
				'show_address' => 1,
			)
		);

		$this->fixture->setMapMarker($this->realtyUid);

		$this->assertNotContains(
			'<div id="tx_realty_map"',
			$this->fixture->render()
		);
	}

	public function testRenderGoogleMapsViewNotReturnsMapForObjectWithCachedAddressAndGoogleMapsDisabled() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->realtyUid,
			array(
				'exact_coordinates_are_cached' => 1,
				'exact_latitude' => 50.734343,
				'exact_longitude' => 7.10211,
				'show_address' => 1,
			)
		);

		$this->fixture->setConfigurationValue('showGoogleMaps', 0);
		$this->fixture->setMapMarker($this->realtyUid);

		$this->assertNotContains(
			'<div id="tx_realty_map"',
			$this->fixture->render()
		);
	}

	public function testRenderGoogleMapsViewAddsGoogleMapsJavaScriptForObjectWithCachedAddressAndGoogleMapsEnabled() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->realtyUid,
			array(
				'exact_coordinates_are_cached' => 1,
				'exact_latitude' => 50.734343,
				'exact_longitude' => 7.10211,
				'show_address' => 1,
			)
		);

		$this->fixture->setMapMarker($this->realtyUid);
		$this->fixture->render();

		$this->assertTrue(
			isset($GLOBALS['TSFE']->additionalHeaderData['tx_realty_pi1_maps'])
		);
	}

	public function testRenderGoogleMapsViewNotAddsGoogleMapsJavaScriptForObjectWithEmptyCachedAddressAndGoogleMapsEnabled() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->realtyUid,
			array(
				'exact_coordinates_are_cached' => 1,
				'exact_latitude' => '',
				'exact_longitude' => '',
				'show_address' => 1,
			)
		);

		$this->fixture->setMapMarker($this->realtyUid);
		$this->fixture->render();

		$this->assertFalse(
			isset($GLOBALS['TSFE']->additionalHeaderData['tx_realty_pi1_maps'])
		);
	}

	public function testRenderGoogleMapsViewNotAddsGoogleMapsJavaScriptForObjectWithCachedAddressAndGoogleMapsDisabled() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->realtyUid,
			array(
				'exact_coordinates_are_cached' => 1,
				'exact_latitude' => 50.734343,
				'exact_longitude' => 7.10211,
				'show_address' => 1,
			)
		);

		$this->fixture->setConfigurationValue('showGoogleMaps', 0);
		$this->fixture->setMapMarker($this->realtyUid);
		$this->fixture->render();

		$this->assertFalse(
			isset($GLOBALS['TSFE']->additionalHeaderData['tx_realty_pi1_maps'])
		);
	}

	public function testRenderGoogleMapsViewAddsOnLoadForObjectWithCachedAddressAndGoogleMapsEnabled() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->realtyUid,
			array(
				'exact_coordinates_are_cached' => 1,
				'exact_latitude' => 50.734343,
				'exact_longitude' => 7.10211,
				'show_address' => 1,
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

	public function testRenderGoogleMapsViewNotAddsOnLoadForObjectWithCachedAddressAndGoogleMapsDisabled() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->realtyUid,
			array(
				'exact_coordinates_are_cached' => 1,
				'exact_latitude' => 50.734343,
				'exact_longitude' => 7.10211,
				'show_address' => 1,
			)
		);

		$this->fixture->setConfigurationValue('showGoogleMaps', 0);
		$this->fixture->setMapMarker($this->realtyUid);
		$this->fixture->render();

		$this->assertFalse(
			isset($GLOBALS['TSFE']
				->JSeventFuncCalls['onload']['tx_realty_pi1_maps']
			)
		);
	}

	public function testRenderGoogleMapsViewAddsOnUnloadForObjectWithCachedAddressAndGoogleMapsEnabled() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->realtyUid,
			array(
				'exact_coordinates_are_cached' => 1,
				'exact_latitude' => 50.734343,
				'exact_longitude' => 7.10211,
				'show_address' => 1,
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

	public function testRenderGoogleMapsViewNotAddsOnUnloadForObjectWithCachedAddressAndGoogleMapsDisabled() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->realtyUid,
			array(
				'exact_coordinates_are_cached' => 1,
				'exact_latitude' => 50.734343,
				'exact_longitude' => 7.10211,
				'show_address' => 1,
			)
		);

		$this->fixture->setConfigurationValue('showGoogleMaps', 0);
		$this->fixture->setMapMarker($this->realtyUid);
		$this->fixture->render();

		$this->assertFalse(
			isset($GLOBALS['TSFE']
				->JSeventFuncCalls['onunload']['tx_realty_pi1_maps']
			)
		);
	}

	public function testRenderGoogleMapsViewReturnsCoordinatesInJavaScriptForGoogleMapsEnabled() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->realtyUid,
			array(
				'exact_coordinates_are_cached' => 1,
				'exact_latitude' => 50.734343,
				'exact_longitude' => 7.10211,
				'show_address' => 1,
			)
		);

		$this->fixture->setMapMarker($this->realtyUid);
		$this->fixture->render();

		$this->assertContains(
			'50.7343',
			$GLOBALS['TSFE']->additionalHeaderData['tx_realty_pi1_maps']
		);
		$this->assertContains(
			'7.1021',
			$GLOBALS['TSFE']->additionalHeaderData['tx_realty_pi1_maps']
		);
	}

	public function testRenderGoogleMapsViewReturnsTheObjectsFullTitleAsTitleForGoogleMaps() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->realtyUid,
			array(
				'title' => 'A really long title that is not too short.',
				'exact_coordinates_are_cached' => 1,
				'exact_latitude' => 50.734343,
				'exact_longitude' => 7.10211,
				'show_address' => 1,
			)
		);

		$this->fixture->setMapMarker($this->realtyUid);
		$this->fixture->render();

		$this->assertContains(
			'title: "A really long title that is not too short."',
			$GLOBALS['TSFE']->additionalHeaderData['tx_realty_pi1_maps']
		);
	}

	public function testRenderGoogleMapsViewReturnsCroppedObjectTitleAsInfoWindowForGoogleMaps() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->realtyUid,
			array(
				'title' => 'A really long title that is not too short.',
				'exact_coordinates_are_cached' => 1,
				'exact_latitude' => 50.734343,
				'exact_longitude' => 7.10211,
				'show_address' => 1,
			)
		);

		$this->fixture->setMapMarker($this->realtyUid);
		$this->fixture->render();

		$this->assertRegExp(
			'/bindInfoWindowHtml\(\'[^\']*A really long title that is not …/',
			$GLOBALS['TSFE']->additionalHeaderData['tx_realty_pi1_maps']
		);
	}

	public function testRenderGoogleMapsViewHasTheObjectsCityAndDistrictAsInfoWindowForGoogleMaps() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->realtyUid,
			array(
				'exact_coordinates_are_cached' => 1,
				'exact_latitude' => 50.734343,
				'exact_longitude' => 7.10211,
				'district' => $this->testingFramework->createRecord(
					REALTY_TABLE_DISTRICTS,
					array('title' => 'Beuel')
				),
				'show_address' => 1,
			)
		);

		$this->fixture->setMapMarker($this->realtyUid);
		$this->fixture->render();

		$this->assertRegExp(
			'/bindInfoWindowHtml\(\'[^\']*' . self::$cityTitle . ' Beuel/',
			$GLOBALS['TSFE']->additionalHeaderData['tx_realty_pi1_maps']
		);
	}

	public function testRenderGoogleMapsViewHasStreetAsInfoWindowForGoogleMapsForDetailedAddress() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->realtyUid,
			array(
				'exact_coordinates_are_cached' => 1,
				'exact_latitude' => 50.734343,
				'exact_longitude' => 7.10211,
				'street' => 'Foo road',
				'show_address' => 1,
			)
		);

		$this->fixture->setMapMarker($this->realtyUid);
		$this->fixture->render();

		$this->assertRegExp(
			'/bindInfoWindowHtml\(\'[^\']*Foo road/',
			$GLOBALS['TSFE']->additionalHeaderData['tx_realty_pi1_maps']
		);
	}

	public function testRenderGoogleMapsViewWithMapMarkerWithoutCreateLinkOptiontDoesNotLinkObjectTitleInMap() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->realtyUid,
			array(
				'exact_coordinates_are_cached' => 1,
				'exact_latitude' => 50.734343,
				'exact_longitude' => 7.10211,
				'street' => 'Foo road',
				'show_address' => 1,
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

	public function testRenderGoogleMapsViewWithMapMarkerWithCreateLinkOptionLinksObjectTitleInMap() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->realtyUid,
			array(
				'exact_coordinates_are_cached' => 1,
				'exact_latitude' => 50.734343,
				'exact_longitude' => 7.10211,
				'street' => 'Foo road',
				'show_address' => 1,
			)
		);

		$this->fixture->setConfigurationValue(
			'singlePID', $this->testingFramework->createFrontEndPage()
		);
		$this->fixture->setMapMarker($this->realtyUid, true);
		$this->fixture->render();

		$this->assertContains(
			'href=',
			$GLOBALS['TSFE']->additionalHeaderData['tx_realty_pi1_maps']
		);
	}

	public function testRenderGoogleMapsViewOmitsStreetAsInfoWindowForGoogleMapsForRoughAddress() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->realtyUid,
			array(
				'exact_coordinates_are_cached' => 1,
				'exact_latitude' => 50.734343,
				'exact_longitude' => 7.10211,
				'street' => 'Foo road',
				'show_address' => 0,
			)
		);

		$this->fixture->setMapMarker($this->realtyUid);
		$this->fixture->render();

		$this->assertNotRegExp(
			'/bindInfoWindowHtml\(\'[^\']*Foo road/',
			$GLOBALS['TSFE']->additionalHeaderData['tx_realty_pi1_maps']
		);
	}

	public function testRetrievingGeoCoordinatesDoesNotDeleteAppendedImage() {
		$this->testingFramework->createRecord(
			REALTY_TABLE_IMAGES,
			array(
				'caption'=>'foo.jpg',
				'image' => 'foo.jpg',
				'realty_object_uid' => $this->realtyUid,
			)
		);
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS,
			$this->realtyUid,
			array(
				'street' => 'Am Hof 1',
				'city' => $this->cityUid,
				'show_address' => 1,
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

	public function testRenderSetsExactCachedCoordinatesOfTwoObjectsInHeader() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS, $this->realtyUid,
			array(
				'exact_coordinates_are_cached' => 1,
				'exact_latitude' => 52.123,
				'exact_longitude' => 7.456,
				'show_address' => 1,
			)
		);
		$this->fixture->setMapMarker($this->realtyUid);
		$this->fixture->setMapMarker(
			$this->testingFramework->createRecord(
				REALTY_TABLE_OBJECTS, array(
					'title' => 'second test object',
					'object_number' => '6789',
					'exact_coordinates_are_cached' => 1,
					'exact_latitude' => 50.734343,
					'exact_longitude' => 7.10211,
					'show_address' => 1,
				)
			)
		);

		$this->fixture->render();

		$this->assertContains(
			'52.123,7.456',
			$GLOBALS['TSFE']->additionalHeaderData['tx_realty_pi1_maps']
		);
		$this->assertContains(
			'50.734343,7.10211',
			$GLOBALS['TSFE']->additionalHeaderData['tx_realty_pi1_maps']
		);
	}

	public function testRenderSetsRoughCachedCoordinatesOfTwoObjectsInHeader() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS, $this->realtyUid,
			array(
				'rough_coordinates_are_cached' => 1,
				'rough_latitude' => 52.123,
				'rough_longitude' => 7.456,
				'show_address' => 0,
			)
		);
		$this->fixture->setMapMarker($this->realtyUid);
		$this->fixture->setMapMarker(
			$this->testingFramework->createRecord(
				REALTY_TABLE_OBJECTS, array(
					'title' => 'second test object',
					'object_number' => '6789',
					'rough_coordinates_are_cached' => 1,
					'rough_latitude' => 50.734343,
					'rough_longitude' => 7.10211,
					'show_address' => 0,
				)
			)
		);

		$this->fixture->render();

		$this->assertContains(
			'52.123,7.456',
			$GLOBALS['TSFE']->additionalHeaderData['tx_realty_pi1_maps']
		);
		$this->assertContains(
			'50.734343,7.10211',
			$GLOBALS['TSFE']->additionalHeaderData['tx_realty_pi1_maps']
		);
	}

	public function testRenderSetsFullTitlesOfTwoObjectsInHeader() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS, $this->realtyUid,
			array(
				'exact_coordinates_are_cached' => 1,
				'exact_latitude' => 52.123,
				'exact_longitude' => 7.456,
				'show_address' => 1,
			)
		);
		$this->fixture->setMapMarker($this->realtyUid);
		$this->fixture->setMapMarker(
			$this->testingFramework->createRecord(
				REALTY_TABLE_OBJECTS, array(
					'title' => 'second test object',
					'object_number' => '6789',
					'exact_coordinates_are_cached' => 1,
					'exact_latitude' => 50.734343,
					'exact_longitude' => 7.10211,
					'show_address' => 1,
				)
			)
		);

		$this->fixture->render();

		$this->assertTrue(
			isset($GLOBALS['TSFE']->additionalHeaderData['tx_realty_pi1_maps'])
		);

		$this->assertContains(
			'title: "test realty object"',
			$GLOBALS['TSFE']->additionalHeaderData['tx_realty_pi1_maps']
		);
		$this->assertContains(
			'title: "second test object"',
			$GLOBALS['TSFE']->additionalHeaderData['tx_realty_pi1_maps']
		);
	}

	public function testRenderSetsAutoZoomForTwoObjectsWithCoordinates() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS, $this->realtyUid,
			array(
				'exact_coordinates_are_cached' => 1,
				'exact_latitude' => 52.123,
				'exact_longitude' => 7.456,
				'show_address' => 1,
			)
		);
		$this->fixture->setMapMarker($this->realtyUid);
		$this->fixture->setMapMarker(
			$this->testingFramework->createRecord(
				REALTY_TABLE_OBJECTS, array(
					'title' => 'second test object',
					'object_number' => '6789',
					'exact_coordinates_are_cached' => 1,
					'exact_latitude' => 50.734343,
					'exact_longitude' => 7.10211,
					'show_address' => 1,
				)
			)
		);

		$this->fixture->render();

		$this->assertContains(
			'setZoom',
			$GLOBALS['TSFE']->additionalHeaderData['tx_realty_pi1_maps']
		);
	}

	public function testRenderSetsDoesNotSetAutoZoomForOnlyOneObjectWithCoordinates() {
		$this->testingFramework->changeRecord(
			REALTY_TABLE_OBJECTS, $this->realtyUid,
			array(
				'exact_coordinates_are_cached' => 1,
				'show_address' => 1,
			)
		);
		$this->fixture->setMapMarker($this->realtyUid);
		$this->fixture->setMapMarker(
			$this->testingFramework->createRecord(
				REALTY_TABLE_OBJECTS, array(
					'title' => 'second test object',
					'object_number' => '6789',
					'exact_coordinates_are_cached' => 1,
					'exact_latitude' => 50.734343,
					'exact_longitude' => 7.10211,
					'show_address' => 1,
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