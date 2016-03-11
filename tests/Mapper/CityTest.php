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
class tx_realty_Mapper_CityTest extends Tx_Phpunit_TestCase
{
    /**
     * @var Tx_Oelib_TestingFramework
     */
    private $testingFramework = null;

    /**
     * @var tx_realty_Mapper_City
     */
    private $fixture = null;

    protected function setUp()
    {
        $this->testingFramework = new Tx_Oelib_TestingFramework('tx_realty');

        $this->fixture = new tx_realty_Mapper_City();
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
        self::assertTrue(
            $this->fixture->find(1) instanceof tx_realty_Model_City
        );
    }

    /**
     * @test
     */
    public function findWithUidOfExistingRecordReturnsRecordAsModel()
    {
        $uid = $this->testingFramework->createRecord(
            'tx_realty_cities', array('title' => 'London')
        );

        /** @var tx_realty_Model_City $model */
        $model = $this->fixture->find($uid);
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
        $this->setExpectedException(
            'InvalidArgumentException',
            '$value must not be empty.'
        );

        $this->fixture->findByName('');
    }

    /**
     * @test
     */
    public function findByNameCanFindModelFromCache()
    {
        $model = $this->fixture->getLoadedTestingModel(
            array('title' => 'Kleinwurzeling')
        );

        self::assertSame(
            $model,
            $this->fixture->findByName('Kleinwurzeling')
        );
    }

    /**
     * @test
     */
    public function findByNameCanLoadModelFromDatabase()
    {
        $uid = $this->testingFramework->createRecord(
            'tx_realty_cities', array('title' => 'Kleinwurzeling')
        );

        /** @var tx_realty_Model_City $model */
        $model = $this->fixture->findByName('Kleinwurzeling');
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
        $this->setExpectedException('Tx_Oelib_Exception_NotFound');

        $this->fixture->findByName('Hupflingen');
    }
}
