<?php

use OliverKlee\PhpUnit\TestCase;

/**
 * Test case.
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class tx_realty_Model_DistrictTest extends TestCase
{
    /**
     * @var tx_realty_Model_District
     */
    private $subject = null;

    protected function setUp()
    {
        $this->subject = new tx_realty_Model_District();
    }

    ///////////////////////////////
    // Tests concerning the title
    ///////////////////////////////

    /**
     * @test
     */
    public function getTitleWithNonEmptyTitleReturnsTitle()
    {
        $this->subject->setData(['title' => 'Bad Godesberg']);

        self::assertEquals(
            'Bad Godesberg',
            $this->subject->getTitle()
        );
    }

    /**
     * @test
     */
    public function setTitleSetsTitle()
    {
        $this->subject->setTitle('Bad Godesberg');

        self::assertEquals(
            'Bad Godesberg',
            $this->subject->getTitle()
        );
    }

    /**
     * @test
     */
    public function setTitleWithEmptyStringThrowsException()
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->subject->setTitle('');
    }

    //////////////////////////////
    // Tests concerning the city
    //////////////////////////////

    /**
     * @test
     */
    public function getCityWithCitySetReturnsCity()
    {
        $city = new tx_realty_Model_City();

        $this->subject->setData(['city' => $city]);

        self::assertSame(
            $city,
            $this->subject->getCity()
        );
    }

    /**
     * @test
     */
    public function getCityReturnsCitySetWithSetCity()
    {
        $city = new tx_realty_Model_City();

        $this->subject->setCity($city);

        self::assertSame(
            $city,
            $this->subject->getCity()
        );
    }

    /**
     * @test
     */
    public function getCityAfterSetCityWithNullReturnsNull()
    {
        $this->subject->setCity(null);

        self::assertNull(
            $this->subject->getCity()
        );
    }
}
