<?php

use OliverKlee\PhpUnit\TestCase;

/**
 * Test case.
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class tx_realty_FrontEnd_MapMarkerTest extends TestCase
{
    /**
     * @var tx_realty_mapMarker
     */
    private $subject = null;

    protected function setUp()
    {
        $this->subject = new tx_realty_mapMarker();
    }

    /**
     * @test
     */
    public function renderReturnsEmptyStringIfSetCoordinatesHasNotBeenCalled()
    {
        self::assertSame(
            '',
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function renderContainsCoordinatesFromSetCoordinates()
    {
        $latitude = 50.734343;
        $longitude = 7.10211;
        $this->subject->setCoordinates($latitude, $longitude);

        self::assertContains(
            (string)$latitude,
            $this->subject->render()
        );
        self::assertContains(
            (string)$longitude,
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function renderAfterSetCoordinatesAddsMarkerToMap()
    {
        $this->subject->setCoordinates(50.734343, 7.10211);

        self::assertContains(
            'google.maps.LatLng(',
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function renderAfterSetCoordinatesAndSetTitleIncludesTitle()
    {
        $this->subject->setCoordinates(50.734343, 7.10211);
        $this->subject->setTitle('Foo title');

        self::assertContains(
            'title: "Foo title"',
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function renderWithTitleEscapesQuotes()
    {
        $this->subject->setCoordinates(50.734343, 7.10211);
        $this->subject->setTitle('Foo " title');

        self::assertContains(
            'title: "Foo \\" title"',
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function renderWithTitleStripsTags()
    {
        $this->subject->setCoordinates(50.734343, 7.10211);
        $this->subject->setTitle('Foo <script>title</script>');

        self::assertContains(
            'title: "Foo title"',
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function getCoordinatesReturnsEmptyStringForNoCoordinates()
    {
        self::assertSame(
            '',
            $this->subject->getCoordinates()
        );
    }

    /**
     * @test
     */
    public function getCoordinatesContainsCoordinatesFromSetCoordinates()
    {
        $latitude = 50.734343;
        $longitude = 7.10211;
        $this->subject->setCoordinates($latitude, $longitude);

        self::assertContains(
            (string)$latitude,
            $this->subject->getCoordinates()
        );
        self::assertContains(
            (string)$longitude,
            $this->subject->getCoordinates()
        );
    }

    /**
     * @test
     */
    public function getInfoWindowContainsTextFromSetInfoWindowHtml()
    {
        $this->subject->setCoordinates(50.734343, 7.10211);
        $this->subject->setInfoWindowHtml('foo');

        self::assertContains(
            'myInfoWindow.setContent(\'foo\')',
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function getInfoWindowEscapesSingleQuotesFromSetInfoWindowHtml()
    {
        $this->subject->setCoordinates(50.734343, 7.10211);
        $this->subject->setInfoWindowHtml('foo\'bar');

        self::assertContains(
            'myInfoWindow.setContent(\'foo\\\'bar\')',
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function getInfoWindowEscapesBackslashesFromSetInfoWindowHtml()
    {
        $this->subject->setCoordinates(50.734343, 7.10211);
        $this->subject->setInfoWindowHtml('foo\\bar');

        self::assertContains(
            'myInfoWindow.setContent(\'foo\\\\bar\')',
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function getInfoWindowEscapesHtmlClosingTagsFromSetInfoWindowHtml()
    {
        $this->subject->setCoordinates(50.734343, 7.10211);
        $this->subject->setInfoWindowHtml('foo</bar');

        self::assertContains(
            'myInfoWindow.setContent(\'foo<\\/bar\')',
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function getInfoWindowDoesNotEscapeDoubleQuotesFromSetInfoWindowHtml()
    {
        $this->subject->setCoordinates(50.734343, 7.10211);
        $this->subject->setInfoWindowHtml('foo"bar');

        self::assertContains(
            'myInfoWindow.setContent(\'foo"bar\')',
            $this->subject->render()
        );
    }

    /**
     * @test
     */
    public function getInfoWindowContainsTextWithHtmlFromSetInfoWindowHtml()
    {
        $this->subject->setCoordinates(50.734343, 7.10211);
        $this->subject->setInfoWindowHtml('<strong>foo</strong><br />bar');

        self::assertContains(
            'myInfoWindow.setContent(\'<strong>foo<\\/strong><br />bar\')',
            $this->subject->render()
        );
    }
}
