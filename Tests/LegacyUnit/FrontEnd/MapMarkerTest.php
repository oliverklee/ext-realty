<?php

/**
 * Test case.
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class tx_realty_FrontEnd_MapMarkerTest extends \Tx_Phpunit_TestCase
{
    /**
     * @var tx_realty_mapMarker
     */
    private $fixture = null;

    protected function setUp()
    {
        $this->fixture = new tx_realty_mapMarker();
    }

    /**
     * @test
     */
    public function renderReturnsEmptyStringIfSetCoordinatesHasNotBeenCalled()
    {
        self::assertSame(
            '',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function renderContainsCoordinatesFromSetCoordinates()
    {
        $latitude = 50.734343;
        $longitude = 7.10211;
        $this->fixture->setCoordinates($latitude, $longitude);

        self::assertContains(
            (string)$latitude,
            $this->fixture->render()
        );
        self::assertContains(
            (string)$longitude,
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function renderAfterSetCoordinatesAddsMarkerToMap()
    {
        $this->fixture->setCoordinates(50.734343, 7.10211);

        self::assertContains(
            'google.maps.LatLng(',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function renderAfterSetCoordinatesAndSetTitleIncludesTitle()
    {
        $this->fixture->setCoordinates(50.734343, 7.10211);
        $this->fixture->setTitle('Foo title');

        self::assertContains(
            'title: "Foo title"',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function renderWithTitleEscapesQuotes()
    {
        $this->fixture->setCoordinates(50.734343, 7.10211);
        $this->fixture->setTitle('Foo " title');

        self::assertContains(
            'title: "Foo \\" title"',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function renderWithTitleStripsTags()
    {
        $this->fixture->setCoordinates(50.734343, 7.10211);
        $this->fixture->setTitle('Foo <script>title</script>');

        self::assertContains(
            'title: "Foo title"',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function getCoordinatesReturnsEmptyStringForNoCoordinates()
    {
        self::assertSame(
            '',
            $this->fixture->getCoordinates()
        );
    }

    /**
     * @test
     */
    public function getCoordinatesContainsCoordinatesFromSetCoordinates()
    {
        $latitude = 50.734343;
        $longitude = 7.10211;
        $this->fixture->setCoordinates($latitude, $longitude);

        self::assertContains(
            (string)$latitude,
            $this->fixture->getCoordinates()
        );
        self::assertContains(
            (string)$longitude,
            $this->fixture->getCoordinates()
        );
    }

    /**
     * @test
     */
    public function getInfoWindowContainsTextFromSetInfoWindowHtml()
    {
        $this->fixture->setCoordinates(50.734343, 7.10211);
        $this->fixture->setInfoWindowHtml('foo');

        self::assertContains(
            'myInfoWindow.setContent(\'foo\')',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function getInfoWindowEscapesSingleQuotesFromSetInfoWindowHtml()
    {
        $this->fixture->setCoordinates(50.734343, 7.10211);
        $this->fixture->setInfoWindowHtml('foo\'bar');

        self::assertContains(
            'myInfoWindow.setContent(\'foo\\\'bar\')',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function getInfoWindowEscapesBackslashesFromSetInfoWindowHtml()
    {
        $this->fixture->setCoordinates(50.734343, 7.10211);
        $this->fixture->setInfoWindowHtml('foo\\bar');

        self::assertContains(
            'myInfoWindow.setContent(\'foo\\\\bar\')',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function getInfoWindowEscapesHtmlClosingTagsFromSetInfoWindowHtml()
    {
        $this->fixture->setCoordinates(50.734343, 7.10211);
        $this->fixture->setInfoWindowHtml('foo</bar');

        self::assertContains(
            'myInfoWindow.setContent(\'foo<\\/bar\')',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function getInfoWindowDoesNotEscapeDoubleQuotesFromSetInfoWindowHtml()
    {
        $this->fixture->setCoordinates(50.734343, 7.10211);
        $this->fixture->setInfoWindowHtml('foo"bar');

        self::assertContains(
            'myInfoWindow.setContent(\'foo"bar\')',
            $this->fixture->render()
        );
    }

    /**
     * @test
     */
    public function getInfoWindowContainsTextWithHtmlFromSetInfoWindowHtml()
    {
        $this->fixture->setCoordinates(50.734343, 7.10211);
        $this->fixture->setInfoWindowHtml('<strong>foo</strong><br />bar');

        self::assertContains(
            'myInfoWindow.setContent(\'<strong>foo<\\/strong><br />bar\')',
            $this->fixture->render()
        );
    }
}
