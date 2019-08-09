<?php

use OliverKlee\PhpUnit\TestCase;

/**
 * Test case.
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class tx_realty_Mapper_CityTest extends TestCase
{
    /**
     * @var Tx_Oelib_TestingFramework
     */
    private $testingFramework = null;

    /**
     * @var tx_realty_Mapper_City
     */
    private $subject = null;

    protected function setUp()
    {
        $this->testingFramework = new Tx_Oelib_TestingFramework('tx_realty');

        $this->subject = new tx_realty_Mapper_City();
    }

    protected function tearDown()
    {
        $this->testingFramework->cleanUp();
    }

    //////////////////////////
    // Tests concerning find
    //////////////////////////

    /**
     * @test
     */
    public function findWithUidReturnsCityInstance()
    {
        self::assertInstanceOf(
            tx_realty_Model_City::class,
            $this->subject->find(1)
        );
    }

    /**
     * @test
     */
    public function findWithUidOfExistingRecordReturnsRecordAsModel()
    {
        $uid = $this->testingFramework->createRecord(
            'tx_realty_cities',
            ['title' => 'London']
        );

        /** @var tx_realty_Model_City $model */
        $model = $this->subject->find($uid);
        self::assertEquals(
            'London',
            $model->getTitle()
        );
    }

    ////////////////////////////////
    // Tests concerning findByName
    ////////////////////////////////

    /**
     * @test
     */
    public function findByNameForEmptyValueThrowsException()
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->subject->findByName('');
    }

    /**
     * @test
     */
    public function findByNameCanFindModelFromCache()
    {
        $model = $this->subject->getLoadedTestingModel(
            ['title' => 'Kleinwurzeling']
        );

        self::assertSame(
            $model,
            $this->subject->findByName('Kleinwurzeling')
        );
    }

    /**
     * @test
     */
    public function findByNameCanLoadModelFromDatabase()
    {
        $uid = $this->testingFramework->createRecord(
            'tx_realty_cities',
            ['title' => 'Kleinwurzeling']
        );

        /** @var tx_realty_Model_City $model */
        $model = $this->subject->findByName('Kleinwurzeling');
        self::assertEquals(
            $uid,
            $model->getUid()
        );
    }

    /**
     * @test
     */
    public function findByNameForInexistentNameThrowsException()
    {
        $this->expectException(\Tx_Oelib_Exception_NotFound::class);

        $this->subject->findByName('Hupflingen');
    }
}
