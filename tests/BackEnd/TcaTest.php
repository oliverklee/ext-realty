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
class tx_realty_BackEnd_TcaTest extends Tx_Phpunit_TestCase
{
    /**
     * @var tx_realty_Tca
     */
    private $fixture = null;

    /**
     * @var Tx_Oelib_TestingFramework
     */
    private $testingFramework = null;

    protected function setUp()
    {
        $this->testingFramework = new Tx_Oelib_TestingFramework('tx_realty');
        $this->fixture = new tx_realty_Tca();
    }

    protected function tearDown()
    {
        $this->testingFramework->cleanUp();
    }

    /////////////////////////////////////////
    // Tests concerning getDistrictsForCity
    /////////////////////////////////////////

    /**
     * @test
     */
    public function getDistrictsForCitySetsItems()
    {
        $result = $this->fixture->getDistrictsForCity(
            ['row' => ['city' => 0]]
        );

        self::assertTrue(
            isset($result['items'])
        );
    }

    /**
     * @test
     */
    public function getDistrictsForCityContainsEmptyOption()
    {
        $result = $this->fixture->getDistrictsForCity(
            ['row' => ['city' => 0]]
        );

        self::assertTrue(
            in_array(['', 0], $result['items'])
        );
    }

    /**
     * @test
     */
    public function getDistrictsForCityReturnsDistrictsForCityOrUnassigned()
    {
        $city = new tx_realty_Model_District();
        $city->setData(['uid' => 2, 'title' => 'Kreuzberg']);
        $cities = new Tx_Oelib_List();
        $cities->add($city);

        /** @var tx_realty_Mapper_District|PHPUnit_Framework_MockObject_MockObject $mapper */
        $mapper = $this->getMock(
            'tx_realty_Mapper_District',
            ['findAllByCityUidOrUnassigned']
        );
        $mapper->expects(self::once())
            ->method('findAllByCityUidOrUnassigned')->with(42)
            ->will(self::returnValue($cities));
        Tx_Oelib_MapperRegistry::set('tx_realty_Mapper_District', $mapper);

        $result = $this->fixture->getDistrictsForCity(
            ['row' => ['city' => 42]]
        );

        self::assertTrue(
            in_array(['Kreuzberg', 2], $result['items'])
        );
    }
}
