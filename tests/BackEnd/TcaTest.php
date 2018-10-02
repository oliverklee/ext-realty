<?php

use OliverKlee\Realty\BackEnd\Tca;

/**
 * Test case.
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class tx_realty_BackEnd_TcaTest extends \Tx_Phpunit_TestCase
{
    /**
     * @var Tca
     */
    private $fixture = null;

    /**
     * @var Tx_Oelib_TestingFramework
     */
    private $testingFramework = null;

    protected function setUp()
    {
        $this->testingFramework = new Tx_Oelib_TestingFramework('tx_realty');
        $this->fixture = new Tca();
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

        self::assertTrue(isset($result['items']));
    }

    /**
     * @test
     */
    public function getDistrictsForCityContainsEmptyOption()
    {
        $result = $this->fixture->getDistrictsForCity(
            ['row' => ['city' => 0]]
        );

        self::assertContains(['', 0], $result['items']);
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
            \tx_realty_Mapper_District::class,
            ['findAllByCityUidOrUnassigned']
        );
        $mapper->expects(self::once())
            ->method('findAllByCityUidOrUnassigned')->with(42)
            ->will(self::returnValue($cities));
        Tx_Oelib_MapperRegistry::set('tx_realty_Mapper_District', $mapper);

        $result = $this->fixture->getDistrictsForCity(
            ['row' => ['city' => 42]]
        );

        self::assertContains(['Kreuzberg', 2], $result['items']);
    }
}
