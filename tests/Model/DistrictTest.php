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
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class tx_realty_Model_DistrictTest extends Tx_Phpunit_TestCase
{
    /**
     * @var tx_realty_Model_District
     */
    private $fixture = null;

    protected function setUp()
    {
        $this->fixture = new tx_realty_Model_District();
    }

    ///////////////////////////////
    // Tests concerning the title
    ///////////////////////////////

    /**
     * @test
     */
    public function getTitleWithNonEmptyTitleReturnsTitle()
    {
        $this->fixture->setData(array('title' => 'Bad Godesberg'));

        self::assertEquals(
            'Bad Godesberg',
            $this->fixture->getTitle()
        );
    }

    /**
     * @test
     */
    public function setTitleSetsTitle()
    {
        $this->fixture->setTitle('Bad Godesberg');

        self::assertEquals(
            'Bad Godesberg',
            $this->fixture->getTitle()
        );
    }

    /**
     * @test
     * @expectedException InvalidArgumentException
     */
    public function setTitleWithEmptyStringThrowsException()
    {
        $this->fixture->setTitle('');
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

        $this->fixture->setData(array('city' => $city));

        self::assertSame(
            $city,
            $this->fixture->getCity()
        );
    }

    /**
     * @test
     */
    public function getCityReturnsCitySetWithSetCity()
    {
        $city = new tx_realty_Model_City();

        $this->fixture->setCity($city);

        self::assertSame(
            $city,
            $this->fixture->getCity()
        );
    }

    /**
     * @test
     */
    public function getCityAfterSetCityWithNullReturnsNull()
    {
        $this->fixture->setCity(null);

        self::assertNull(
            $this->fixture->getCity()
        );
    }
}
