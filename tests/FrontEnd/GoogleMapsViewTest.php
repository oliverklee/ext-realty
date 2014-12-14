<?php
/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * Test case.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 * @author Oliver Klee <typo3-coding@oliverklee.de>
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
	 * @var int dummy realty object
	 */
	private $realtyUid = 42;

	/**
	 * @var tx_realty_Mapper_RealtyObject|PHPUnit_Framework_MockObject_MockObject
	 */
	protected $realtyMapper = NULL;

	/**
	 * @var tx_realty_Model_RealtyObject|PHPUnit_Framework_MockObject_MockObject
	 */
	protected $realtyObject = NULL;

	/**
	 * @var tx_oelib_Geocoding_Google|PHPUnit_Framework_MockObject_MockObject
	 */
	protected $geoCoder = NULL;

	/**
	 * @var float latitude
	 */
	const LATITUDE = 50.7;

	/**
	 * @var float longitude
	 */
	const LONGITUDE = 7.1;

	protected function setUp() {
		$this->testingFramework = new tx_oelib_testingFramework('tx_realty');
		$this->testingFramework->createFakeFrontEnd();

		tx_oelib_MapperRegistry::getInstance()->activateTestingMode($this->testingFramework);

		$realtyData = array(
			'title' => 'test realty object',
			'object_number' => '12345',
			'pid' => $this->testingFramework->createFrontEndPage(),
			'street' => 'Main Street',
			'zip' => '12345',
			'latitude' => self::LATITUDE,
			'longitude' => self::LONGITUDE,
			'has_coordinates' => TRUE,
			'coordinates_problem' => FALSE,
		);

		$this->geoCoder = $this->getMock('tx_oelib_Geocoding_Dummy');
		tx_oelib_Geocoding_Google::setInstance($this->geoCoder);

		$this->realtyMapper = $this->getMock('tx_realty_Mapper_RealtyObject');
		tx_oelib_MapperRegistry::set('tx_realty_Mapper_RealtyObject', $this->realtyMapper);

		$this->realtyObject = $this->getMock('tx_realty_Model_RealtyObject', array('writeToDatabase'));
		$this->realtyObject->setData($realtyData);
		$this->realtyMapper->expects($this->any())->method('find')->with($this->realtyUid)
			->will($this->returnValue($this->realtyObject));

		$this->fixture = new tx_realty_pi1_GoogleMapsView(
			array(
				'templateFile' => 'EXT:realty/pi1/tx_realty_pi1.tpl.htm',
				'defaultCountryUID' => 54,
			),
			$GLOBALS['TSFE']->cObj,
			TRUE
		);
	}

	protected function tearDown() {
		$this->testingFramework->cleanUp();
	}


	///////////////////////////////////
	// Tests for the Google Maps view
	///////////////////////////////////

	/**
	 * @test
	 */
	public function renderGoogleMapsViewWithNoCollectedMarkersReturnsEmptyResult() {
		$this->assertSame(
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
	public function setMapMarkerForObjectWithCoordinatesWithoutGeoErrorNotFetchesCoordinates() {
		$this->realtyObject->setGeoCoordinates(array('latitude' => self::LATITUDE, 'longitude' => self::LONGITUDE));
		$this->realtyObject->clearGeoError();

		tx_oelib_Geocoding_Google::setInstance($this->geoCoder);
		$this->geoCoder->expects($this->never())->method('lookUp');

		$this->fixture->setMapMarker($this->realtyUid);
	}

	/**
	 * @test
	 */
	public function setMapMarkerForObjectWithCoordinatesWithoutGeoErrorNotSavesObject() {
		$this->realtyObject->setGeoCoordinates(array('latitude' => self::LATITUDE, 'longitude' => self::LONGITUDE));
		$this->realtyObject->clearGeoError();

		$this->realtyObject->expects($this->never())->method('writeToDatabase');

		$this->fixture->setMapMarker($this->realtyUid);
	}

	/**
	 * @test
	 */
	public function setMapMarkerForObjectWithCoordinatesWithGeoErrorNotFetchesCoordinates() {
		$this->realtyObject->setGeoCoordinates(array('latitude' => self::LATITUDE, 'longitude' => self::LONGITUDE));
		$this->realtyObject->setGeoError();

		tx_oelib_Geocoding_Google::setInstance($this->geoCoder);
		$this->geoCoder->expects($this->never())->method('lookUp');

		$this->fixture->setMapMarker($this->realtyUid);
	}

	/**
	 * @test
	 */
	public function setMapMarkerForObjectWithCoordinatesWithGeoErrorNotSavesObject() {
		$this->realtyObject->setGeoCoordinates(array('latitude' => self::LATITUDE, 'longitude' => self::LONGITUDE));
		$this->realtyObject->setGeoError();

		$this->realtyObject->expects($this->never())->method('writeToDatabase');

		$this->fixture->setMapMarker($this->realtyUid);
	}

	/**
	 * @test
	 */
	public function setMapMarkerForObjectWithoutCoordinatesWithGeoErrorNotFetchesCoordinates() {
		$this->realtyObject->clearGeoCoordinates();
		$this->realtyObject->setGeoError();

		tx_oelib_Geocoding_Google::setInstance($this->geoCoder);
		$this->geoCoder->expects($this->never())->method('lookUp');

		$this->fixture->setMapMarker($this->realtyUid);
	}

	/**
	 * @test
	 */
	public function setMapMarkerForObjectWithoutCoordinatesWithGeoErrorNotSavesObject() {
		$this->realtyObject->clearGeoCoordinates();
		$this->realtyObject->setGeoError();

		$this->realtyObject->expects($this->never())->method('writeToDatabase');

		$this->fixture->setMapMarker($this->realtyUid);
	}

	/**
	 * @test
	 */
	public function setMapMarkerForObjectWithoutCoordinatesWithoutGeoErrorFetchesCoordinates() {
		$this->realtyObject->clearGeoCoordinates();
		$this->realtyObject->clearGeoError();

		tx_oelib_Geocoding_Google::setInstance($this->geoCoder);
		$this->geoCoder->expects($this->once())->method('lookUp')->with($this->realtyObject);

		$this->fixture->setMapMarker($this->realtyUid);
	}

	/**
	 * @test
	 */
	public function setMapMarkerForObjectWithoutCoordinatesWithoutGeoErrorSavesObject() {
		$this->realtyObject->clearGeoCoordinates();
		$this->realtyObject->clearGeoError();

		$this->realtyObject->expects($this->once())->method('writeToDatabase');

		$this->fixture->setMapMarker($this->realtyUid);
	}

	/**
	 * @test
	 */
	public function renderGoogleMapsViewReturnsMapForObjectWithCoordinatesAndGoogleMapsEnabledCreatesMapsDiv() {
		$this->realtyObject->setGeoCoordinates(array('latitude' => self::LATITUDE, 'longitude' => self::LONGITUDE));
		$this->fixture->setMapMarker($this->realtyUid);

		$this->assertContains(
			'<div id="tx_realty_map"',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function renderGoogleMapsViewForObjectWithGeoErrorAndGoogleMapsEnabledNotCreatesMapsDiv() {
		$this->realtyObject->setGeoError();
		$this->fixture->setMapMarker($this->realtyUid);

		$this->assertNotContains(
			'<div id="tx_realty_map"',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function renderGoogleMapsViewForObjectWithCoordinatesAddsGoogleMapsJavaScript() {
		$this->realtyObject->setGeoCoordinates(array('latitude' => self::LATITUDE, 'longitude' => self::LONGITUDE));
		$this->fixture->setMapMarker($this->realtyUid);
		$this->fixture->render();

		$this->assertTrue(
			isset($GLOBALS['TSFE']->additionalHeaderData['tx_realty_pi1_maps'])
		);
	}

	/**
	 * @test
	 */
	public function renderGoogleMapsViewForObjectWithGeoErrorAndWithCoordinatesNotAddsGoogleMapsJavaScript() {
		$this->realtyObject->setGeoCoordinates(array('latitude' => self::LATITUDE, 'longitude' => self::LONGITUDE));
		$this->realtyObject->setGeoError();
		$this->fixture->setMapMarker($this->realtyUid);
		$this->fixture->render();

		$this->assertFalse(
			isset($GLOBALS['TSFE']->additionalHeaderData['tx_realty_pi1_maps'])
		);
	}

	/**
	 * @test
	 */
	public function renderGoogleMapsForObjectWithCoordinatesViewAddsOnLoad() {
		$this->realtyObject->setGeoCoordinates(array('latitude' => self::LATITUDE, 'longitude' => self::LONGITUDE));
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
	public function renderGoogleMapsViewForObjectWithCoordinatesAddsOnUnload() {
		$this->realtyObject->setGeoCoordinates(array('latitude' => self::LATITUDE, 'longitude' => self::LONGITUDE));
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
	public function renderGoogleMapsViewReturnsCoordinatesInJavaScript() {
		$this->realtyObject->setGeoCoordinates(array('latitude' => self::LATITUDE, 'longitude' => self::LONGITUDE));
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
		$this->realtyObject->setShowAddress(TRUE);
		$this->fixture->setMapMarker($this->realtyUid);
		$this->fixture->render();

		$this->assertContains(
			'title: "Main Street',
			$GLOBALS['TSFE']->additionalHeaderData['tx_realty_pi1_maps']
		);
	}

	/**
	 * @test
	 */
	public function renderGoogleMapsViewForShowAddressFalseReturnsTheObjectsAddressWithoutStreetAsTitleForGoogleMaps() {
		$this->realtyObject->setShowAddress(FALSE);

		$this->fixture->setMapMarker($this->realtyUid);
		$this->fixture->render();

		$this->assertContains(
			'title: "12345"',
			$GLOBALS['TSFE']->additionalHeaderData['tx_realty_pi1_maps']
		);
	}

	/**
	 * @test
	 */
	public function renderGoogleMapsViewReturnsCroppedObjectTitleAsInfoWindowForGoogleMaps() {
		$this->realtyObject->setTitle('A really long title that is not too short.');
		$this->fixture->setMapMarker($this->realtyUid);
		$this->fixture->render();

		$this->assertRegExp(
			'/myInfoWindow\.setContent\(\'[^\']*A really long title that is not …/',
			$GLOBALS['TSFE']->additionalHeaderData['tx_realty_pi1_maps']
		);
	}

	/**
	 * @test
	 */
	public function renderGoogleMapsViewHasStreetAsInfoWindowForGoogleMapsForDetailedAddress() {
		$this->realtyObject->setShowAddress(TRUE);
		$this->fixture->setMapMarker($this->realtyUid);
		$this->fixture->render();

		$this->assertRegExp(
			'/myInfoWindow\.setContent\(\'[^\']*Main Street/',
			$GLOBALS['TSFE']->additionalHeaderData['tx_realty_pi1_maps']
		);
	}

	/**
	 * @test
	 */
	public function renderGoogleMapsViewWithMapMarkerWithoutCreateLinkOptiontDoesNotLinkObjectTitleInMap() {
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
	public function renderGoogleMapsViewOmitsStreetAsInfoWindowForGoogleMapsForShowAddressFalse() {
		$this->realtyObject->setShowAddress(FALSE);
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
	public function renderSetsAutoZoomForTwoObjectsWithCoordinates() {
		$this->fixture->setMapMarker($this->realtyUid);
		$this->fixture->setMapMarker($this->realtyUid);

		$this->fixture->render();

		$this->assertContains(
			'zoom:',
			$GLOBALS['TSFE']->additionalHeaderData['tx_realty_pi1_maps']
		);
	}
}