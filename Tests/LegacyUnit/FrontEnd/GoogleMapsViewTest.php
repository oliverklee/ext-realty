<?php

use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Test case.
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class tx_realty_FrontEnd_GoogleMapsViewTest extends \Tx_Phpunit_TestCase
{
    /**
     * @var tx_realty_pi1_GoogleMapsView
     */
    private $fixture = null;

    /**
     * @var Tx_Oelib_TestingFramework
     */
    private $testingFramework = null;

    /**
     * @var int dummy realty object
     */
    private $realtyUid = 42;

    /**
     * @var tx_realty_Mapper_RealtyObject|PHPUnit_Framework_MockObject_MockObject
     */
    protected $realtyMapper = null;

    /**
     * @var tx_realty_Model_RealtyObject|PHPUnit_Framework_MockObject_MockObject
     */
    protected $realtyObject = null;

    /**
     * @var Tx_Oelib_Geocoding_Google|PHPUnit_Framework_MockObject_MockObject
     */
    protected $geoCoder = null;

    /**
     * @var \Tx_Oelib_Configuration
     */
    private $configuration = null;

    /**
     * @var float latitude
     */
    const LATITUDE = 50.7;

    /**
     * @var float longitude
     */
    const LONGITUDE = 7.1;

    protected function setUp()
    {
        $this->testingFramework = new Tx_Oelib_TestingFramework('tx_realty');
        $this->testingFramework->createFakeFrontEnd();

        Tx_Oelib_MapperRegistry::getInstance()->activateTestingMode($this->testingFramework);

        $configurationRegistry = \Tx_Oelib_ConfigurationRegistry::getInstance();
        $configurationRegistry->set('plugin', new \Tx_Oelib_Configuration());
        $this->configuration = new \Tx_Oelib_Configuration();
        $configurationRegistry->set('plugin.tx_oelib', $this->configuration);

        $realtyData = [
            'title' => 'test realty object',
            'object_number' => '12345',
            'pid' => $this->testingFramework->createFrontEndPage(),
            'street' => 'Main Street',
            'zip' => '12345',
            'latitude' => self::LATITUDE,
            'longitude' => self::LONGITUDE,
            'has_coordinates' => true,
            'coordinates_problem' => false,
        ];

        $this->geoCoder = $this->getMock(Tx_Oelib_Geocoding_Dummy::class);
        Tx_Oelib_Geocoding_Google::setInstance($this->geoCoder);

        $this->realtyMapper = $this->getMock(\tx_realty_Mapper_RealtyObject::class);
        Tx_Oelib_MapperRegistry::set('tx_realty_Mapper_RealtyObject', $this->realtyMapper);

        $this->realtyObject = $this->getMock(\tx_realty_Model_RealtyObject::class, ['writeToDatabase']);
        $this->realtyObject->setData($realtyData);
        $this->realtyMapper->method('find')->with($this->realtyUid)
            ->will(self::returnValue($this->realtyObject));

        $this->fixture = new tx_realty_pi1_GoogleMapsView(
            [
                'templateFile' => 'EXT:realty/Resources/Private/Templates/FrontEnd/Plugin.html',
                'defaultCountryUID' => 54,
            ],
            $this->getFrontEndController()->cObj,
            true
        );
    }

    protected function tearDown()
    {
        $this->testingFramework->cleanUp();
    }

    /**
     * Returns the current front-end instance.
     *
     * @return TypoScriptFrontendController
     */
    private function getFrontEndController()
    {
        return $GLOBALS['TSFE'];
    }

    ///////////////////////////////////
    // Tests for the Google Maps view
    ///////////////////////////////////

    /**
     * @test
     */
    public function renderGoogleMapsViewWithNoCollectedMarkersReturnsEmptyResult()
    {
        self::assertSame(
            '',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function renderGoogleMapsViewWithCollectedMarkerReturnsNonEmptyResult()
    {
        $this->fixture->setMapMarker($this->realtyUid);

        self::assertContains(
            '<div id="tx_realty_map"',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function renderGoogleMapsViewWithCollectedMarkerReturnsNoUnreplacedMarkers()
    {
        $this->fixture->setMapMarker($this->realtyUid);

        self::assertNotContains(
            '###',
            $this->fixture->render(['message_access_denied'])
        );
    }

    /**
     * @test
     */
    public function setMapMarkerForObjectWithCoordinatesWithoutGeoErrorNotFetchesCoordinates()
    {
        $this->realtyObject->setGeoCoordinates(['latitude' => self::LATITUDE, 'longitude' => self::LONGITUDE]);
        $this->realtyObject->clearGeoError();

        Tx_Oelib_Geocoding_Google::setInstance($this->geoCoder);
        $this->geoCoder->expects(self::never())->method('lookUp');

        $this->fixture->setMapMarker($this->realtyUid);
    }

    /**
     * @test
     */
    public function setMapMarkerForObjectWithCoordinatesWithoutGeoErrorNotSavesObject()
    {
        $this->realtyObject->setGeoCoordinates(['latitude' => self::LATITUDE, 'longitude' => self::LONGITUDE]);
        $this->realtyObject->clearGeoError();

        $this->realtyObject->expects(self::never())->method('writeToDatabase');

        $this->fixture->setMapMarker($this->realtyUid);
    }

    /**
     * @test
     */
    public function setMapMarkerForObjectWithCoordinatesWithGeoErrorNotFetchesCoordinates()
    {
        $this->realtyObject->setGeoCoordinates(['latitude' => self::LATITUDE, 'longitude' => self::LONGITUDE]);
        $this->realtyObject->setGeoError();

        Tx_Oelib_Geocoding_Google::setInstance($this->geoCoder);
        $this->geoCoder->expects(self::never())->method('lookUp');

        $this->fixture->setMapMarker($this->realtyUid);
    }

    /**
     * @test
     */
    public function setMapMarkerForObjectWithCoordinatesWithGeoErrorNotSavesObject()
    {
        $this->realtyObject->setGeoCoordinates(['latitude' => self::LATITUDE, 'longitude' => self::LONGITUDE]);
        $this->realtyObject->setGeoError();

        $this->realtyObject->expects(self::never())->method('writeToDatabase');

        $this->fixture->setMapMarker($this->realtyUid);
    }

    /**
     * @test
     */
    public function setMapMarkerForObjectWithoutCoordinatesWithGeoErrorNotFetchesCoordinates()
    {
        $this->realtyObject->clearGeoCoordinates();
        $this->realtyObject->setGeoError();

        Tx_Oelib_Geocoding_Google::setInstance($this->geoCoder);
        $this->geoCoder->expects(self::never())->method('lookUp');

        $this->fixture->setMapMarker($this->realtyUid);
    }

    /**
     * @test
     */
    public function setMapMarkerForObjectWithoutCoordinatesWithGeoErrorNotSavesObject()
    {
        $this->realtyObject->clearGeoCoordinates();
        $this->realtyObject->setGeoError();

        $this->realtyObject->expects(self::never())->method('writeToDatabase');

        $this->fixture->setMapMarker($this->realtyUid);
    }

    /**
     * @test
     */
    public function setMapMarkerForObjectWithoutCoordinatesWithoutGeoErrorFetchesCoordinates()
    {
        $this->realtyObject->clearGeoCoordinates();
        $this->realtyObject->clearGeoError();

        Tx_Oelib_Geocoding_Google::setInstance($this->geoCoder);
        $this->geoCoder->expects(self::once())->method('lookUp')->with($this->realtyObject);

        $this->fixture->setMapMarker($this->realtyUid);
    }

    /**
     * @test
     */
    public function setMapMarkerForObjectWithoutCoordinatesWithoutGeoErrorSavesObject()
    {
        $this->realtyObject->clearGeoCoordinates();
        $this->realtyObject->clearGeoError();

        $this->realtyObject->expects(self::once())->method('writeToDatabase');

        $this->fixture->setMapMarker($this->realtyUid);
    }

    /**
     * @test
     */
    public function renderGoogleMapsViewReturnsMapForObjectWithCoordinatesAndGoogleMapsEnabledCreatesMapsDiv()
    {
        $this->realtyObject->setGeoCoordinates(['latitude' => self::LATITUDE, 'longitude' => self::LONGITUDE]);
        $this->fixture->setMapMarker($this->realtyUid);

        self::assertContains(
            '<div id="tx_realty_map"',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function renderGoogleMapsViewForObjectWithGeoErrorAndGoogleMapsEnabledNotCreatesMapsDiv()
    {
        $this->realtyObject->setGeoError();
        $this->fixture->setMapMarker($this->realtyUid);

        self::assertNotContains(
            '<div id="tx_realty_map"',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function renderGoogleMapsViewForObjectWithCoordinatesAddsGoogleMapsJavaScript()
    {
        $this->realtyObject->setGeoCoordinates(['latitude' => self::LATITUDE, 'longitude' => self::LONGITUDE]);
        $this->fixture->setMapMarker($this->realtyUid);
        $this->fixture->render();

        self::assertTrue(
            isset($this->getFrontEndController()->additionalHeaderData['tx_realty_pi1_maps'])
        );
    }

    /**
     * @test
     */
    public function renderGoogleMapsViewForObjectWithGeoErrorAndWithCoordinatesNotAddsGoogleMapsJavaScript()
    {
        $this->realtyObject->setGeoCoordinates(['latitude' => self::LATITUDE, 'longitude' => self::LONGITUDE]);
        $this->realtyObject->setGeoError();
        $this->fixture->setMapMarker($this->realtyUid);
        $this->fixture->render();

        self::assertFalse(
            isset($this->getFrontEndController()->additionalHeaderData['tx_realty_pi1_maps'])
        );
    }

    /**
     * @test
     */
    public function renderGoogleMapsViewReturnsCoordinatesInJavaScript()
    {
        $this->realtyObject->setGeoCoordinates(['latitude' => self::LATITUDE, 'longitude' => self::LONGITUDE]);
        $this->fixture->setMapMarker($this->realtyUid);
        $this->fixture->render();

        self::assertContains(
            (string)self::LATITUDE,
            $this->getFrontEndController()->additionalHeaderData['tx_realty_pi1_maps']
        );
        self::assertContains(
            (string)self::LONGITUDE,
            $this->getFrontEndController()->additionalHeaderData['tx_realty_pi1_maps']
        );
    }

    /**
     * @test
     */
    public function renderGoogleMapsViewForShowAddressTrueReturnsTheObjectsFullAddressAsTitleForGoogleMaps()
    {
        $this->realtyObject->setShowAddress(true);
        $this->fixture->setMapMarker($this->realtyUid);
        $this->fixture->render();

        self::assertContains(
            'title: "Main Street',
            $this->getFrontEndController()->additionalHeaderData['tx_realty_pi1_maps']
        );
    }

    /**
     * @test
     */
    public function renderGoogleMapsViewForShowAddressFalseReturnsTheObjectsAddressWithoutStreetAsTitleForGoogleMaps()
    {
        $this->realtyObject->setShowAddress(false);

        $this->fixture->setMapMarker($this->realtyUid);
        $this->fixture->render();

        self::assertContains(
            'title: "12345"',
            $this->getFrontEndController()->additionalHeaderData['tx_realty_pi1_maps']
        );
    }

    /**
     * @test
     */
    public function renderGoogleMapsViewReturnsCroppedObjectTitleAsInfoWindowForGoogleMaps()
    {
        $this->realtyObject->setTitle('A really long title that is not too short.');
        $this->fixture->setMapMarker($this->realtyUid);
        $this->fixture->render();

        self::assertRegExp(
            '/myInfoWindow\\.setContent\\(\'[^\']*A really long title that is not …/',
            $this->getFrontEndController()->additionalHeaderData['tx_realty_pi1_maps']
        );
    }

    /**
     * @test
     */
    public function renderGoogleMapsViewHasStreetAsInfoWindowForGoogleMapsForDetailedAddress()
    {
        $this->realtyObject->setShowAddress(true);
        $this->fixture->setMapMarker($this->realtyUid);
        $this->fixture->render();

        self::assertRegExp(
            '/myInfoWindow\\.setContent\\(\'[^\']*Main Street/',
            $this->getFrontEndController()->additionalHeaderData['tx_realty_pi1_maps']
        );
    }

    /**
     * @test
     */
    public function renderGoogleMapsViewWithMapMarkerWithoutCreateLinkOptiontDoesNotLinkObjectTitleInMap()
    {
        $this->fixture->setConfigurationValue(
            'singlePID',
            $this->testingFramework->createFrontEndPage()
        );
        $this->fixture->setMapMarker($this->realtyUid);
        $this->fixture->render();

        self::assertNotContains(
            'href=',
            $this->getFrontEndController()->additionalHeaderData['tx_realty_pi1_maps']
        );
    }

    /**
     * @test
     */
    public function renderGoogleMapsViewWithMapMarkerWithCreateLinkOptionLinksObjectTitleInMap()
    {
        $this->fixture->setConfigurationValue(
            'singlePID',
            $this->testingFramework->createFrontEndPage()
        );
        $this->fixture->setMapMarker($this->realtyUid, true);
        $this->fixture->render();

        self::assertContains(
            'href=',
            $this->getFrontEndController()->additionalHeaderData['tx_realty_pi1_maps']
        );
    }

    /**
     * @test
     */
    public function renderGoogleMapsViewOmitsStreetAsInfoWindowForGoogleMapsForShowAddressFalse()
    {
        $this->realtyObject->setShowAddress(false);
        $this->fixture->setMapMarker($this->realtyUid);
        $this->fixture->render();

        self::assertNotRegExp(
            '/bindInfoWindowHtml\\(\'[^\']*Foo road/',
            $this->getFrontEndController()->additionalHeaderData['tx_realty_pi1_maps']
        );
    }

    /**
     * @test
     */
    public function renderSetsAutoZoomForTwoObjectsWithCoordinates()
    {
        $this->fixture->setMapMarker($this->realtyUid);
        $this->fixture->setMapMarker($this->realtyUid);

        $this->fixture->render();

        self::assertContains(
            'zoom:',
            $this->getFrontEndController()->additionalHeaderData['tx_realty_pi1_maps']
        );
    }
}
