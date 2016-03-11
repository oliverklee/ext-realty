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
class tx_realty_Mapper_ImageTest extends Tx_Phpunit_TestCase
{
    /**
     * @var tx_realty_Mapper_Image
     */
    private $fixture = null;

    /**
     * @var Tx_Oelib_TestingFramework
     */
    private $testingFramework = null;

    protected function setUp()
    {
        $this->testingFramework = new Tx_Oelib_TestingFramework('tx_realty');
        $this->fixture = new tx_realty_Mapper_Image();
    }

    protected function tearDown()
    {
        $this->testingFramework->cleanUp();
    }

    /////////////////////////////////////////
    // Tests concerning the basic functions
    /////////////////////////////////////////

    /**
     * @test
     */
    public function findWithUidReturnsImageInstance()
    {
        self::assertTrue(
            $this->fixture->find(1) instanceof tx_realty_Model_Image
        );
    }

    /**
     * @test
     */
    public function findWithUidOfExistingRecordReturnsRecordAsModel()
    {
        $uid = $this->testingFramework->createRecord(
            'tx_realty_images', array('caption' => 'a nice green lawn')
        );

        /** @var tx_realty_Model_Image $model */
        $model = $this->fixture->find($uid);
        self::assertEquals(
            'a nice green lawn',
            $model->getTitle()
        );
    }

    ///////////////////////////////////////////
    // Tests concerning the "object" relation
    ///////////////////////////////////////////

    /**
     * @test
     */
    public function getObjectReturnsRelatedRealtyObject()
    {
        /** @var tx_realty_Model_RealtyObject $realtyObject */
        $realtyObject = Tx_Oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')->getNewGhost();
        /** @var tx_realty_Model_Image $image */
        $image = $this->fixture->getLoadedTestingModel(
            array('object' => $realtyObject->getUid())
        );

        self::assertSame(
            $realtyObject,
            $image->getObject()
        );
    }

    ////////////////////////////
    // Tests concerning delete
    ////////////////////////////

    /**
     * @test
     */
    public function deleteDeletesImageFile()
    {
        $dummyFile = $this->testingFramework->createDummyFile('foo.jpg');
        $uid = $this->testingFramework->createRecord(
            'tx_realty_images', array('image' => basename($dummyFile))
        );

        /** @var tx_realty_Model_Image $model */
        $model = $this->fixture->find($uid);
        $this->fixture->delete($model);

        self::assertFalse(
            file_exists($dummyFile)
        );
    }

    /**
     * @test
     */
    public function deleteForInexistentImageFileNotThrowsException()
    {
        $dummyFile = $this->testingFramework->createDummyFile('foo.jpg');
        unlink($dummyFile);
        $uid = $this->testingFramework->createRecord(
            'tx_realty_images', array('image' => basename($dummyFile))
        );

        /** @var tx_realty_Model_Image $model */
        $model = $this->fixture->find($uid);
        $this->fixture->delete($model);
    }

    /**
     * @test
     */
    public function deleteForEmptyImageFileNameNotThrowsException()
    {
        $uid = $this->testingFramework->createRecord(
            'tx_realty_images', array('image' => '')
        );

        /** @var tx_realty_Model_Image $model */
        $model = $this->fixture->find($uid);
        $this->fixture->delete($model);
    }
}
