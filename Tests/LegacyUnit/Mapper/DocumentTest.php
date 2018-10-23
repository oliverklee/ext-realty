<?php

/**
 * Test case.
 *
 * @author Bernd Schönbach <bernd.schoenbach@googlemail.com>
 */
class tx_realty_Mapper_DocumentTest extends \Tx_Phpunit_TestCase
{
    /**
     * @var tx_realty_Mapper_Document
     */
    private $fixture = null;

    /**
     * @var Tx_Oelib_TestingFramework
     */
    private $testingFramework = null;

    protected function setUp()
    {
        $this->testingFramework = new Tx_Oelib_TestingFramework('tx_realty');
        $this->fixture = new tx_realty_Mapper_Document();
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
    public function findWithUidReturnsDocumentInstance()
    {
        self::assertInstanceOf(
            tx_realty_Model_Document::class,
            $this->fixture->find(1)
        );
    }

    /**
     * @test
     */
    public function findWithUidOfExistingRecordReturnsRecordAsModel()
    {
        $uid = $this->testingFramework->createRecord(
            'tx_realty_documents',
            ['title' => 'an important document']
        );

        /** @var tx_realty_Model_Document $model */
        $model = $this->fixture->find($uid);
        self::assertEquals(
            'an important document',
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
        $realtyObject = Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_RealtyObject::class)->getNewGhost();
        /** @var tx_realty_Model_Document $document */
        $document = $this->fixture->getLoadedTestingModel(
            ['object' => $realtyObject->getUid()]
        );

        self::assertSame(
            $realtyObject,
            $document->getObject()
        );
    }

    ////////////////////////////
    // Tests concerning delete
    ////////////////////////////

    /**
     * @test
     */
    public function deleteDeletesDocumentFile()
    {
        $dummyFile = $this->testingFramework->createDummyFile('foo.pdf');
        $uid = $this->testingFramework->createRecord(
            'tx_realty_documents',
            ['filename' => basename($dummyFile)]
        );

        /** @var tx_realty_Model_Document $model */
        $model = $this->fixture->find($uid);
        $this->fixture->delete($model);

        self::assertFileNotExists(
            $dummyFile
        );
    }

    /**
     * @test
     */
    public function deleteForInexistentDocumentFileNotThrowsException()
    {
        $dummyFile = $this->testingFramework->createDummyFile('foo.pdf');
        unlink($dummyFile);
        $uid = $this->testingFramework->createRecord(
            'tx_realty_documents',
            ['filename' => basename($dummyFile)]
        );

        /** @var tx_realty_Model_Document $model */
        $model = $this->fixture->find($uid);
        $this->fixture->delete($model);
    }

    /**
     * @test
     */
    public function deleteForEmptyFileNameNotThrowsException()
    {
        $uid = $this->testingFramework->createRecord(
            'tx_realty_documents',
            ['filename' => '']
        );

        /** @var tx_realty_Model_Document $model */
        $model = $this->fixture->find($uid);
        $this->fixture->delete($model);
    }
}
