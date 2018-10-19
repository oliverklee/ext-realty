<?php

/**
 * Test case.
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 * @author Bernd Schönbach <bernd@oliverklee.de>
 */
class tx_realty_Model_RealtyObjectTest extends \Tx_Phpunit_TestCase
{
    /**
     * @var tx_realty_Model_RealtyObjectChild
     */
    private $fixture = null;

    /**
     * @var Tx_Oelib_TestingFramework
     */
    private $testingFramework = null;

    /**
     * @var Tx_Oelib_TemplateHelper
     */
    private $templateHelper;

    /**
     * @var int UID of a dummy realty object
     */
    private $objectUid = 0;

    /**
     * @var int page UID of a dummy FE page
     */
    private $pageUid = 0;

    /**
     * @var int page UID of another dummy FE page
     */
    private $otherPageUid = 0;

    /**
     * @var string object number of a dummy realty object
     */
    private static $objectNumber = '100000';

    /**
     * @var string object number of a dummy realty object
     */
    private static $otherObjectNumber = '100001';

    /**
     * @var array
     */
    private $configurationVariablesBackup = [];

    /**
     * @var int static_info_tables UID of Germany
     */
    const DE = 54;

    protected function setUp()
    {
        $this->testingFramework = new Tx_Oelib_TestingFramework('tx_realty');
        $this->createDummyRecords();

        Tx_Oelib_MapperRegistry::getInstance()
            ->activateTestingMode($this->testingFramework);

        $this->templateHelper = $this->getMock(
            \Tx_Oelib_TemplateHelper::class,
            ['hasConfValueString', 'getConfValueString']
        );

        $this->fixture = new tx_realty_Model_RealtyObjectChild(true);

        $this->fixture->setRequiredFields([]);
        Tx_Oelib_ConfigurationProxy::getInstance('realty')
            ->setAsInteger('pidForRealtyObjectsAndImages', $this->pageUid);

        $this->configurationVariablesBackup = $GLOBALS['TYPO3_CONF_VARS'];
        $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'] = 'jpg,tif,tiff,pdf,png,ps,gif';
    }

    protected function tearDown()
    {
        $GLOBALS['TYPO3_CONF_VARS'] = $this->configurationVariablesBackup;

        $this->cleanUpDatabase();
    }

    ///////////////////////
    // Utility functions.
    ///////////////////////

    /**
     * Creates dummy system folders and realty objects in the DB.
     *
     * @return void
     */
    private function createDummyRecords()
    {
        $this->pageUid = $this->testingFramework->createSystemFolder();
        $this->otherPageUid = $this->testingFramework->createSystemFolder();
        $this->objectUid = $this->testingFramework->createRecord(
            'tx_realty_objects',
            [
                'title' => 'foo',
                'object_number' => self::$objectNumber,
                'pid' => $this->pageUid,
                'language' => 'foo',
                'openimmo_obid' => 'test-obid',
            ]
        );
    }

    /**
     * Cleans up the tables in which dummy records are created during the tests.
     *
     * @return void
     */
    private function cleanUpDatabase()
    {
        // Inserting images causes an entry to 'sys_refindex' which is currently
        // not cleaned up automatically by the testing framework.
        if (in_array(
            'tx_realty_images',
            $this->testingFramework->getListOfDirtyTables()
        )) {
            Tx_Oelib_Db::delete(
                'sys_refindex',
                'ref_string = "' . tx_realty_Model_Image::UPLOAD_FOLDER . 'bar"'
            );
        }

        $this->testingFramework->cleanUp();
    }

    /**
     * Loads a realty object into the fixture and sets the owner of this object.
     *
     * @param int $ownerSource
     *        the source of the owner data for the object,
     *        must be tx_realty_Model_RealtyObject::CONTACT_DATA_FROM_OWNER_ACCOUNT or
     *     tx_realty_Model_RealtyObject::CONTACT_DATA_FROM_REALTY_OBJECT
     * @param array $userData
     *        additional data which should be stored into the owners data, may be empty
     * @param array $additionalObjectData
     *        additional data which should be stored into the object, may be empty
     *
     * @return void
     */
    private function loadRealtyObjectAndSetOwner(
        $ownerSource,
        array $userData = [],
        array $additionalObjectData = []
    ) {
        $objectData = array_merge(
            $additionalObjectData,
            [
                'contact_data_source' => $ownerSource,
                'owner' =>
                    Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_FrontEndUser::class)
                        ->getLoadedTestingModel($userData)->getUid(),
            ]
        );

        $this->fixture->loadRealtyObject($objectData);
    }

    ///////////////////////////////
    // Testing the realty object.
    ///////////////////////////////

    /**
     * @test
     */
    public function getObidReturnsObid()
    {
        $obid = 'bklhjewkbjvewq';
        $this->fixture->setData(['openimmo_obid' => $obid]);

        self::assertSame($obid, $this->fixture->getObid());
    }

    /**
     * @test
     */
    public function recordExistsInDatabaseIfNoExistingObjectNumberGiven()
    {
        self::assertFalse(
            $this->fixture->recordExistsInDatabase(
                ['object_number' => '99999']
            )
        );
    }

    /**
     * @test
     */
    public function recordExistsInDatabaseIfExistingObjectNumberGiven()
    {
        self::assertTrue(
            $this->fixture->recordExistsInDatabase(
                ['object_number' => self::$objectNumber]
            )
        );
    }

    /**
     * @test
     */
    public function loadDatabaseEntryWithValidUid()
    {
        self::assertEquals(
            Tx_Oelib_Db::selectSingle(
                '*',
                'tx_realty_objects',
                'uid = ' . $this->objectUid
            ),
            $this->fixture->loadDatabaseEntry($this->objectUid)
        );
    }

    /**
     * @test
     */
    public function loadDatabaseEntryWithInvalidUid()
    {
        self::assertEquals(
            [],
            $this->fixture->loadDatabaseEntry('99999')
        );
    }

    /**
     * @test
     */
    public function loadDatabaseEntryOfAnNonHiddenObjectIfOnlyVisibleAreAllowed()
    {
        $this->fixture->loadRealtyObject($this->objectUid, false);
        self::assertEquals(
            Tx_Oelib_Db::selectSingle(
                '*',
                'tx_realty_objects',
                'uid = ' . $this->objectUid
            ),
            $this->fixture->loadDatabaseEntry($this->objectUid)
        );
    }

    /**
     * @test
     */
    public function loadDatabaseEntryDoesNotLoadAHiddenObjectIfOnlyVisibleAreAllowed()
    {
        $this->fixture->loadRealtyObject($this->objectUid, false);
        $uid = $this->testingFramework->createRecord(
            'tx_realty_objects',
            ['hidden' => 1]
        );
        self::assertEquals(
            [],
            $this->fixture->loadDatabaseEntry($uid)
        );
    }

    /**
     * @test
     */
    public function loadDatabaseEntryLoadsAHiddenObjectIfHiddenAreAllowed()
    {
        $this->fixture->loadRealtyObject($this->objectUid, true);
        $uid = $this->testingFramework->createRecord(
            'tx_realty_objects',
            ['hidden' => 1]
        );
        self::assertEquals(
            Tx_Oelib_Db::selectSingle(
                '*',
                'tx_realty_objects',
                'uid = ' . $uid
            ),
            $this->fixture->loadDatabaseEntry($uid)
        );
    }

    /**
     * @test
     */
    public function getDataTypeWhenArrayGiven()
    {
        self::assertEquals(
            'array',
            $this->fixture->getDataType(['foo'])
        );
    }

    /**
     * @test
     */
    public function loadRealtyObjectWithValidArraySetDataForGetProperty()
    {
        $this->fixture->loadRealtyObject(['title' => 'foo']);

        self::assertEquals(
            'foo',
            $this->fixture->getProperty('title')
        );
    }

    /**
     * @test
     */
    public function loadRealtyObjectFromAnArrayWithNonZeroUidIsAllowed()
    {
        $this->fixture->loadRealtyObject(['uid' => 1234]);
    }

    /**
     * @test
     */
    public function loadRealtyObjectFromArrayWithZeroUidIsAllowed()
    {
        $this->fixture->loadRealtyObject(['uid' => 0]);
    }

    /**
     * @test
     */
    public function loadHiddenRealtyObjectIfHiddenObjectsAreNotAllowed()
    {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->objectUid,
            ['hidden' => 1]
        );
        $this->fixture->loadRealtyObject($this->objectUid, false);

        self::assertTrue(
            $this->fixture->isEmpty()
        );
    }

    /**
     * @test
     */
    public function loadHiddenRealtyObjectIfHidddenObjectsAreAllowed()
    {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->objectUid,
            ['hidden' => 1]
        );
        $this->fixture->loadRealtyObject($this->objectUid, true);

        self::assertFalse(
            $this->fixture->isEmpty()
        );
    }

    /**
     * @test
     */
    public function createNewDatabaseEntryIfAValidArrayIsGiven()
    {
        $this->fixture->createNewDatabaseEntry(
            ['object_number' => self::$otherObjectNumber]
        );

        self::assertEquals(
            1,
            $this->testingFramework->countRecords(
                'tx_realty_objects',
                'object_number="' . self::$otherObjectNumber . '"' .
                Tx_Oelib_Db::enableFields('tx_realty_objects')
            )
        );
    }

    /**
     * @test
     *
     * @expectedException \InvalidArgumentException
     */
    public function createNewDatabaseEntryForArrayWithNonZeroUidThrowsException()
    {
        $this->fixture->createNewDatabaseEntry(['uid' => 1234]);
    }

    /**
     * @test
     *
     * @expectedException \InvalidArgumentException
     */
    public function createNewDatabaseEntryForArrayWithZeroUidThrowsException()
    {
        $this->fixture->createNewDatabaseEntry(['uid' => 0]);
    }

    /**
     * @test
     */
    public function getDataTypeWhenIntegerGiven()
    {
        self::assertEquals(
            'uid',
            $this->fixture->getDataType(1)
        );
    }

    /**
     * @test
     */
    public function setDataSetsTheRealtyObjectsTitle()
    {
        $this->fixture->setData(['title' => 'foo']);

        self::assertEquals(
            'foo',
            $this->fixture->getTitle()
        );
    }

    /**
     * Test concerning the title
     */

    /**
     * @test
     */
    public function getTitleInitiallyReturnsEmptyString()
    {
        $this->fixture->setData([]);
        self::assertSame(
            '',
            $this->fixture->getTitle()
        );
    }

    /**
     * @test
     */
    public function getTitleReturnsTitle()
    {
        $title = 'A very nice house indeed.';
        $this->fixture->setData(['title' => $title]);

        self::assertSame(
            $title,
            $this->fixture->getTitle()
        );
    }

    /**
     * @test
     */
    public function setTitleSetsTitle()
    {
        $this->fixture->setData([]);
        $this->fixture->setTitle('foo bar');

        self::assertSame(
            'foo bar',
            $this->fixture->getTitle()
        );
    }

    /**
     * @test
     */
    public function setTitleCanSetEmptyTitle()
    {
        $this->fixture->setData([]);
        $this->fixture->setTitle('');

        self::assertSame(
            '',
            $this->fixture->getTitle()
        );
    }

    ////////////////////////////////
    // Tests concerning the images
    ////////////////////////////////

    /**
     * @test
     */
    public function loadRealtyObjectByUidAlsoLoadsImages()
    {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->objectUid,
            ['images' => 1]
        );
        $this->testingFramework->createRecord(
            'tx_realty_images',
            [
                'caption' => 'foo',
                'image' => 'foo.jpg',
                'object' => $this->objectUid,
            ]
        );
        $this->fixture->loadRealtyObject($this->objectUid);

        /** @var tx_realty_Model_Image $firstImage */
        $firstImage = $this->fixture->getImages()->first();
        self::assertEquals(
            'foo',
            $firstImage->getTitle()
        );
    }

    /**
     * @test
     */
    public function setDataSetsTheImageDataForImageFromDatabase()
    {
        $this->testingFramework->createRecord(
            'tx_realty_images',
            [
                'caption' => 'foo',
                'image' => 'foo.jpg',
                'object' => $this->objectUid,
            ]
        );
        $this->fixture->setData(['uid' => $this->objectUid, 'images' => 1]);

        /** @var tx_realty_Model_Image $firstImage */
        $firstImage = $this->fixture->getImages()->first();
        self::assertEquals(
            'foo',
            $firstImage->getTitle()
        );
    }

    /**
     * @test
     */
    public function setDataSetsImagePositionForImageFromDatabase()
    {
        $this->testingFramework->createRecord(
            'tx_realty_images',
            [
                'caption' => 'foo',
                'image' => 'foo.jpg',
                'object' => $this->objectUid,
                'position' => 4,
            ]
        );
        $this->fixture->setData(['uid' => $this->objectUid, 'images' => 1]);

        /** @var tx_realty_Model_Image $firstImage */
        $firstImage = $this->fixture->getImages()->first();
        self::assertEquals(
            4,
            $firstImage->getPosition()
        );
    }

    /**
     * @test
     */
    public function setDataSetsTheImageDataForImageFromArray()
    {
        $this->fixture->setData(
            [
                'object_number' => self::$otherObjectNumber,
                'images' => [
                    ['caption' => 'test', 'image' => 'test.jpg'],
                ],
            ]
        );

        /** @var tx_realty_Model_Image $firstImage */
        $firstImage = $this->fixture->getImages()->first();
        self::assertEquals(
            'test',
            $firstImage->getTitle()
        );
    }

    /**
     * @test
     */
    public function setDataWithDocumentAndImageSetsTheDataForImagesFromArray()
    {
        $this->fixture->setData(
            [
                'object_number' => self::$otherObjectNumber,
                'images' => [
                    ['caption' => 'test image', 'image' => 'test.jpg'],
                ],
                'documents' => [
                    ['title' => 'test document', 'filename' => 'test.pdf'],
                ],
            ]
        );

        /** @var tx_realty_Model_Image $firstImage */
        $firstImage = $this->fixture->getImages()->first();
        self::assertEquals(
            'test image',
            $firstImage->getTitle()
        );
    }

    /**
     * @test
     */
    public function getImagesReturnsTheCurrentObjectsImagesOrderedBySorting()
    {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->objectUid,
            ['images' => 2]
        );
        $this->testingFramework->createRecord(
            'tx_realty_images',
            [
                'caption' => 'second',
                'image' => 'second.jpg',
                'object' => $this->objectUid,
                'sorting' => 2,
            ]
        );
        $this->testingFramework->createRecord(
            'tx_realty_images',
            [
                'caption' => 'first',
                'image' => 'first.jpg',
                'object' => $this->objectUid,
                'sorting' => 1,
            ]
        );
        $this->fixture->loadRealtyObject($this->objectUid);

        $titles = [];
        foreach ($this->fixture->getImages() as $image) {
            $titles[] = $image->getTitle();
        }
        self::assertEquals(
            ['first', 'second'],
            $titles
        );
    }

    /**
     * @test
     */
    public function getImagesReturnsTheCurrentObjectsImagesWithoutPdf()
    {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->objectUid,
            ['images' => 2]
        );
        $this->testingFramework->createRecord(
            'tx_realty_images',
            [
                'caption' => 'pdf',
                'image' => 'foo.pdf',
                'object' => $this->objectUid,
                'sorting' => 1,
            ]
        );
        $this->testingFramework->createRecord(
            'tx_realty_images',
            [
                'caption' => 'jpg',
                'image' => 'foo.jpg',
                'object' => $this->objectUid,
                'sorting' => 2,
            ]
        );
        $this->fixture->loadRealtyObject($this->objectUid);

        /** @var tx_realty_Model_Image $firstImage */
        $firstImage = $this->fixture->getImages()->first();
        self::assertSame(
            'jpg',
            $firstImage->getTitle()
        );
    }

    /**
     * @test
     */
    public function getImagesReturnsTheCurrentObjectsImagesWithoutPs()
    {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->objectUid,
            ['images' => 2]
        );
        $this->testingFramework->createRecord(
            'tx_realty_images',
            [
                'caption' => 'ps',
                'image' => 'foo.ps',
                'object' => $this->objectUid,
                'sorting' => 1,
            ]
        );
        $this->testingFramework->createRecord(
            'tx_realty_images',
            [
                'caption' => 'jpg',
                'image' => 'foo.jpg',
                'object' => $this->objectUid,
                'sorting' => 2,
            ]
        );
        $this->fixture->loadRealtyObject($this->objectUid);

        /** @var tx_realty_Model_Image $firstImage */
        $firstImage = $this->fixture->getImages()->first();
        self::assertSame(
            'jpg',
            $firstImage->getTitle()
        );
    }

    ///////////////////////////////////
    // Tests concerning the documents
    ///////////////////////////////////

    /**
     * @test
     */
    public function loadRealtyObjectByUidLoadsDocuments()
    {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->objectUid,
            ['documents' => 1]
        );
        $this->testingFramework->createRecord(
            'tx_realty_documents',
            [
                'title' => 'foo',
                'filename' => 'foo.pdf',
                'object' => $this->objectUid,
            ]
        );
        $this->fixture->loadRealtyObject($this->objectUid);

        /** @var tx_realty_Model_Document $firstDocument */
        $firstDocument = $this->fixture->getDocuments()->first();
        self::assertEquals(
            'foo',
            $firstDocument->getTitle()
        );
    }

    /**
     * @test
     */
    public function setDataSetsTheDataForDocumentFromDatabase()
    {
        $this->testingFramework->createRecord(
            'tx_realty_documents',
            [
                'title' => 'foo',
                'filename' => 'foo.pdf',
                'object' => $this->objectUid,
            ]
        );
        $this->fixture->setData(
            ['uid' => $this->objectUid, 'documents' => 1]
        );

        /** @var tx_realty_Model_Document $firstDocument */
        $firstDocument = $this->fixture->getDocuments()->first();
        self::assertEquals(
            'foo',
            $firstDocument->getTitle()
        );
    }

    /**
     * @test
     */
    public function setDataSetsTheDataForDocumentFromArray()
    {
        $this->fixture->setData(
            [
                'object_number' => self::$otherObjectNumber,
                'documents' => [
                    ['title' => 'test', 'filename' => 'test.pdf'],
                ],
            ]
        );

        /** @var tx_realty_Model_Document $firstDocument */
        $firstDocument = $this->fixture->getDocuments()->first();
        self::assertEquals(
            'test',
            $firstDocument->getTitle()
        );
    }

    /**
     * @test
     */
    public function setDataWithDocumentAndImageSetsTheDataForDocumentFromArray()
    {
        $this->fixture->setData(
            [
                'object_number' => self::$otherObjectNumber,
                'images' => [
                    ['caption' => 'test image', 'image' => 'test.jpg'],
                ],
                'documents' => [
                    ['title' => 'test document', 'filename' => 'test.pdf'],
                ],
            ]
        );

        /** @var tx_realty_Model_Document $firstDocument */
        $firstDocument = $this->fixture->getDocuments()->first();
        self::assertEquals(
            'test document',
            $firstDocument->getTitle()
        );
    }

    /**
     * @test
     */
    public function getDocumentsReturnsTheCurrentObjectsDocumentsOrderedBySorting()
    {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->objectUid,
            ['documents' => 2]
        );
        $this->testingFramework->createRecord(
            'tx_realty_documents',
            [
                'title' => 'second',
                'filename' => 'second.pdf',
                'object' => $this->objectUid,
                'sorting' => 2,
            ]
        );
        $this->testingFramework->createRecord(
            'tx_realty_documents',
            [
                'title' => 'first',
                'filename' => 'first.pdf',
                'object' => $this->objectUid,
                'sorting' => 1,
            ]
        );
        $this->fixture->loadRealtyObject($this->objectUid);

        $titles = [];
        foreach ($this->fixture->getDocuments() as $document) {
            $titles[] = $document->getTitle();
        }
        self::assertEquals(
            ['first', 'second'],
            $titles
        );
    }

    /////////////////////////////////////
    // Tests concerning writeToDatabase
    /////////////////////////////////////

    /**
     * @test
     */
    public function writeToDatabaseUpdatesEntryIfUidExistsInDb()
    {
        $this->fixture->loadRealtyObject($this->objectUid);
        $this->fixture->setProperty('title', 'new title');
        $message = $this->fixture->writeToDatabase();

        self::assertEquals(
            1,
            $this->testingFramework->countRecords(
                'tx_realty_objects',
                'object_number="' . self::$objectNumber . '" AND title="new title"'
            )
        );
        self::assertEquals(
            '',
            $message
        );
    }

    /**
     * @test
     */
    public function writeToDatabaseUpdatesEntryIfObjectMatchesObjectNumberLanguageAndObidOfADbEntry()
    {
        $this->fixture->loadRealtyObject(
            [
                'title' => 'new title',
                'object_number' => self::$objectNumber,
                'language' => 'foo',
                'openimmo_obid' => 'test-obid',
            ]
        );
        $this->fixture->writeToDatabase();

        self::assertEquals(
            1,
            $this->testingFramework->countRecords(
                'tx_realty_objects',
                'object_number="' . self::$objectNumber . '" AND title="new title"'
            )
        );
    }

    /**
     * @test
     */
    public function writeToDatabaseCreatesNewEntryIfObjectMatchesObjectNumberAndObidExistOfADbEntryButNotLanguage()
    {
        $this->fixture->loadRealtyObject(
            [
                'title' => 'new title',
                'object_number' => self::$objectNumber,
                'language' => 'bar',
                'openimmo_obid' => 'test-obid',
            ]
        );
        $this->fixture->writeToDatabase();

        self::assertEquals(
            2,
            $this->testingFramework->countRecords(
                'tx_realty_objects',
                'object_number=' . self::$objectNumber
            )
        );
    }

    /**
     * @test
     */
    public function writeToDatabaseCreatesNewEntryIfObjectMatchesObjectNumberAndLanguageExistOfADbEntryButNotObid()
    {
        $this->fixture->loadRealtyObject(
            [
                'title' => 'new title',
                'object_number' => self::$objectNumber,
                'language' => 'foo',
                'openimmo_obid' => 'another-test-obid',
            ]
        );
        $this->fixture->writeToDatabase();

        self::assertEquals(
            2,
            $this->testingFramework->countRecords(
                'tx_realty_objects',
                'object_number=' . self::$objectNumber
            )
        );
    }

    /**
     * @test
     */
    public function writeToDatabaseCreatesNewEntryIfObjectMatchesObjectNumberAndObidOfADbEntryAndLanguageIsEmpty()
    {
        $this->fixture->loadRealtyObject(
            [
                'title' => 'new title',
                'object_number' => self::$objectNumber,
                'language' => '',
                'openimmo_obid' => 'test-obid',
            ]
        );
        $this->fixture->writeToDatabase();

        self::assertEquals(
            2,
            $this->testingFramework->countRecords(
                'tx_realty_objects',
                'object_number=' . self::$objectNumber
            )
        );
    }

    /**
     * @test
     */
    public function writeToDatabaseUpdatesEntryIfObjectMatchesObjectNumberOfADbEntryAndNoLanguageAndNoObidAreSet()
    {
        $this->fixture->loadRealtyObject(
            [
                'title' => 'new title',
                'object_number' => self::$objectNumber,
            ]
        );
        $message = $this->fixture->writeToDatabase();

        self::assertEquals(
            1,
            $this->testingFramework->countRecords(
                'tx_realty_objects',
                'uid=' . $this->objectUid . ' AND title="new title"'
            )
        );
        self::assertEquals(
            '',
            $message
        );
    }

    /**
     * @test
     */
    public function writeToDatabaseUpdatesEntryIfObjectMatchesObjectNumberAndObidOfADbEntryAndNoLanguageIsSet()
    {
        $this->fixture->loadRealtyObject(
            [
                'title' => 'new title',
                'object_number' => self::$objectNumber,
                'openimmo_obid' => 'test-obid',
            ]
        );
        $message = $this->fixture->writeToDatabase();

        self::assertEquals(
            1,
            $this->testingFramework->countRecords(
                'tx_realty_objects',
                'uid=' . $this->objectUid . ' AND title="new title"'
            )
        );
        self::assertEquals(
            '',
            $message
        );
    }

    /**
     * @test
     */
    public function writeToDatabaseUpdatesEntryIfObjectMatchesObjectNumberAndLanguageOfADbEntryAndNoObidIsSet()
    {
        $this->fixture->loadRealtyObject(
            [
                'title' => 'new title',
                'object_number' => self::$objectNumber,
                'language' => 'foo',
            ]
        );
        $message = $this->fixture->writeToDatabase();

        self::assertEquals(
            1,
            $this->testingFramework->countRecords(
                'tx_realty_objects',
                'uid=' . $this->objectUid . ' AND title="new title"'
            )
        );
        self::assertEquals(
            '',
            $message
        );
    }

    /**
     * @test
     */
    public function writeToDatabaseCreatesNewEntryIfObjectNumberButNoLanguageExistsInTheDbAndLanguageIsSet()
    {
        $this->testingFramework->createRecord(
            'tx_realty_objects',
            [
                'title' => 'this is a title',
                'object_number' => self::$objectNumber,
            ]
        );
        $this->fixture->loadRealtyObject(
            [
                'title' => 'this is a title',
                'object_number' => self::$objectNumber,
                'language' => 'bar',
            ]
        );
        $message = $this->fixture->writeToDatabase();

        self::assertEquals(
            2,
            $this->testingFramework->countRecords(
                'tx_realty_objects',
                'object_number="' . self::$objectNumber . '" AND title="this is a title"'
            )
        );
        self::assertEquals(
            '',
            $message
        );
    }

    /**
     * @test
     */
    public function writeToDatabaseCreatesNewEntryIfObjectNumberButNoObidExistsInTheDbAndObidIsSet()
    {
        $this->testingFramework->createRecord(
            'tx_realty_objects',
            [
                'title' => 'this is a title',
                'object_number' => self::$objectNumber,
            ]
        );
        $this->fixture->loadRealtyObject(
            [
                'title' => 'this is a title',
                'object_number' => self::$objectNumber,
                'openimmo_obid' => 'another-test-obid',
            ]
        );
        $message = $this->fixture->writeToDatabase();

        self::assertEquals(
            2,
            $this->testingFramework->countRecords(
                'tx_realty_objects',
                'object_number="' . self::$objectNumber . '" AND title="this is a title"'
            )
        );
        self::assertEquals(
            '',
            $message
        );
    }

    /**
     * @test
     */
    public function writeToDatabaseCreatesNewEntryIfObjectNumberButObidExistsInTheDbAndObidIsSet()
    {
        $this->testingFramework->createRecord(
            'tx_realty_objects',
            [
                'title' => 'this is a title',
                'object_number' => self::$objectNumber,
            ]
        );
        $this->fixture->loadRealtyObject(
            [
                'title' => 'this is a title',
                'object_number' => self::$objectNumber,
                'openimmo_obid' => 'another-test-obid',
            ]
        );
        $message = $this->fixture->writeToDatabase();

        self::assertEquals(
            2,
            $this->testingFramework->countRecords(
                'tx_realty_objects',
                'object_number="' . self::$objectNumber . '" AND title="this is a title"'
            )
        );
        self::assertEquals(
            '',
            $message
        );
    }

    /**
     * @test
     */
    public function writeToDatabaseCreatesNewEntryIfObjectMatchesLanguageAndObidOfADbEntryButNotObjectNumber()
    {
        $this->fixture->loadRealtyObject(
            [
                'title' => 'new title',
                'object_number' => self::$otherObjectNumber,
                'openimmo_obid' => 'test-obid',
                'language' => 'foo',
            ]
        );
        $this->fixture->writeToDatabase();

        self::assertEquals(
            2,
            $this->testingFramework->countRecords(
                'tx_realty_objects',
                'language="foo" AND openimmo_obid="test-obid"'
            )
        );
    }

    /**
     * @test
     */
    public function writeToDatabaseReturnsRequiredFieldsMessageIfTheRequiredFieldsAreNotSet()
    {
        $this->fixture->setRequiredFields(['city']);
        $this->fixture->loadRealtyObject(
            [
                'object_number' => self::$otherObjectNumber,
                'title' => 'new entry',
            ]
        );

        self::assertEquals(
            'message_fields_required',
            $this->fixture->writeToDatabase()
        );
    }

    /**
     * @test
     */
    public function writeToDatabaseReturnsObjectNotLoadedMessageIfTheCurrentObjectIsEmpty()
    {
        $this->fixture->loadRealtyObject([]);

        self::assertEquals(
            'message_object_not_loaded',
            $this->fixture->writeToDatabase()
        );
    }

    /**
     * @test
     */
    public function writeToDatabaseCreatesNewDatabaseEntry()
    {
        $this->fixture->loadRealtyObject(
            ['object_number' => self::$otherObjectNumber]
        );
        $this->fixture->writeToDatabase();

        self::assertEquals(
            1,
            $this->testingFramework->countRecords(
                'tx_realty_objects',
                'object_number="' . self::$otherObjectNumber . '"' .
                Tx_Oelib_Db::enableFields('tx_realty_objects')
            )
        );
    }

    /**
     * @test
     */
    public function writeToDatabaseCreatesNewDatabaseEntryForObjectWithQuotedData()
    {
        $this->fixture->loadRealtyObject(
            [
                'object_number' => '"' . self::$otherObjectNumber . '"',
                'openimmo_obid' => '"foo"',
                'title' => '"bar"',
            ]
        );
        $this->fixture->writeToDatabase();

        self::assertEquals(
            1,
            $this->testingFramework->countRecords(
                'tx_realty_objects',
                'uid=' . $this->fixture->getUid()
            )
        );
    }

    /**
     * @test
     */
    public function writeToDatabaseCreatesNewRealtyRecordWithRealtyRecordPid()
    {
        $this->fixture->loadRealtyObject(
            ['object_number' => self::$otherObjectNumber]
        );
        $this->fixture->writeToDatabase();

        self::assertEquals(
            ['pid' => $this->pageUid],
            Tx_Oelib_Db::selectSingle(
                'pid',
                'tx_realty_objects',
                'object_number = ' . self::$otherObjectNumber .
                Tx_Oelib_Db::enableFields('tx_realty_objects')
            )
        );
    }

    /**
     * @test
     */
    public function writeToDatabaseCanOverrideDefaultPidForNewRecords()
    {
        $systemFolderPid = $this->testingFramework->createSystemFolder();

        $this->fixture->loadRealtyObject(
            ['object_number' => self::$otherObjectNumber]
        );
        $this->fixture->writeToDatabase($systemFolderPid);

        self::assertEquals(
            1,
            $this->testingFramework->countRecords(
                'tx_realty_objects',
                'object_number=' . self::$otherObjectNumber .
                ' AND pid=' . $systemFolderPid .
                Tx_Oelib_Db::enableFields('tx_realty_objects')
            )
        );
    }

    /**
     * @test
     */
    public function writeToDatabaseUpdatesAndCannotOverrideDefaultPid()
    {
        $systemFolderPid = $this->testingFramework->createSystemFolder();
        $this->fixture->loadRealtyObject(
            ['object_number' => self::$objectNumber]
        );
        $this->fixture->writeToDatabase($systemFolderPid);

        self::assertEquals(
            1,
            $this->testingFramework->countRecords(
                'tx_realty_objects',
                'uid=' . $this->objectUid
                . ' AND pid=' . $this->pageUid
            )
        );
    }

    /**
     * @test
     */
    public function writeToDatabaseCreatesNewCityRecordWithAuxiliaryRecordPid()
    {
        $this->testingFramework->markTableAsDirty('tx_realty_cities');
        Tx_Oelib_ConfigurationProxy::getInstance('realty')->setAsInteger('pidForAuxiliaryRecords', $this->otherPageUid);

        $this->fixture->loadRealtyObject($this->objectUid);
        $this->fixture->setProperty('city', 'foo');
        $this->fixture->writeToDatabase();

        self::assertEquals(
            ['pid' => $this->otherPageUid],
            Tx_Oelib_Db::selectSingle(
                'pid',
                'tx_realty_cities',
                'title = "foo"' .
                Tx_Oelib_Db::enableFields('tx_realty_cities')
            )
        );
    }

    /**
     * @test
     */
    public function writeToDatabaseCreatesNewCityRecordWithRealtyRecordPidIfAuxiliaryRecordPidNotSet()
    {
        $this->testingFramework->markTableAsDirty('tx_realty_cities');
        Tx_Oelib_ConfigurationProxy::getInstance('realty')->setAsInteger('pidForAuxiliaryRecords', 0);

        $this->fixture->loadRealtyObject($this->objectUid);
        $this->fixture->setProperty('city', 'foo');
        $this->fixture->writeToDatabase();

        self::assertEquals(
            ['pid' => $this->pageUid],
            Tx_Oelib_Db::selectSingle(
                'pid',
                'tx_realty_cities',
                'title = "foo"' .
                Tx_Oelib_Db::enableFields('tx_realty_cities')
            )
        );
    }

    /**
     * @test
     */
    public function getPropertyWithNonExistingKeyWhenObjectLoaded()
    {
        $this->fixture->loadRealtyObject($this->objectUid);

        self::assertEquals(
            '',
            $this->fixture->getProperty('foo')
        );
    }

    /**
     * @test
     */
    public function getPropertyWithExistingKeyWhenObjectLoaded()
    {
        $this->fixture->loadRealtyObject($this->objectUid);
        $this->fixture->set('city', 'foo');

        self::assertEquals(
            'foo',
            $this->fixture->getProperty('city')
        );
    }

    /**
     * @test
     */
    public function setPropertyWhenKeyExists()
    {
        $this->fixture->loadRealtyObject($this->objectUid);
        $this->fixture->setProperty('city', 'foo');

        self::assertEquals(
            'foo',
            $this->fixture->getProperty('city')
        );
    }

    /**
     * @test
     */
    public function setPropertyWhenValueOfBoolean()
    {
        $this->fixture->loadRealtyObject($this->objectUid);
        $this->fixture->setProperty('pets', true);

        self::assertTrue(
            $this->fixture->getProperty('pets')
        );
    }

    /**
     * @test
     */
    public function setPropertyWhenValueIsNumber()
    {
        $this->fixture->loadRealtyObject($this->objectUid);
        $this->fixture->setProperty('zip', 100);

        self::assertEquals(
            100,
            $this->fixture->getProperty('zip')
        );
    }

    /**
     * @test
     */
    public function setPropertyWhenKeyNotExists()
    {
        $this->fixture->loadRealtyObject($this->objectUid);
        $this->fixture->setProperty('foo', 'bar');

        self::assertEquals(
            '',
            $this->fixture->getProperty('foo')
        );
    }

    /**
     * @test
     */
    public function setPropertyDoesNotSetTheValueWhenTheValuesTypeIsInvalid()
    {
        $this->fixture->loadRealtyObject($this->objectUid);
        $this->fixture->setProperty('pets', ['bar']);

        self::assertEquals(
            $this->objectUid,
            $this->fixture->getUid()
        );
    }

    /**
     * @test
     *
     * @expectedException \InvalidArgumentException
     */
    public function setPropertyKeySetToUidThrowsException()
    {
        $this->fixture->loadRealtyObject($this->objectUid);

        $this->fixture->setProperty('uid', 12345);
    }

    /**
     * @test
     */
    public function isEmptyWithObjectLoadedReturnsFalse()
    {
        $this->fixture->loadRealtyObject($this->objectUid);
        self::assertFalse(
            $this->fixture->isEmpty()
        );
    }

    /**
     * @test
     */
    public function isEmptyWithNoObjectLoadedReturnsTrue()
    {
        self::assertTrue(
            $this->fixture->isEmpty()
        );
    }

    /**
     * @test
     */
    public function checkForRequiredFieldsIfNoFieldsAreRequired()
    {
        $this->fixture->loadRealtyObject($this->objectUid);

        self::assertEquals(
            [],
            $this->fixture->checkForRequiredFields()
        );
    }

    /**
     * @test
     */
    public function checkForRequiredFieldsIfAllFieldsAreSet()
    {
        $this->fixture->loadRealtyObject($this->objectUid);
        $this->fixture->setRequiredFields(
            [
                'title',
                'object_number',
            ]
        );

        self::assertEquals(
            [],
            $this->fixture->checkForRequiredFields()
        );
    }

    /**
     * @test
     */
    public function checkForRequiredFieldsIfOneRequriredFieldIsMissing()
    {
        $this->fixture->loadRealtyObject(['title' => 'foo']);
        $this->fixture->setRequiredFields(['object_number']);

        self::assertContains(
            'object_number',
            $this->fixture->checkForRequiredFields()
        );
    }

    /**
     * @test
     */
    public function prepareInsertionAndInsertRelationsWritesUidOfInsertedPropertyToRealtyObjectData()
    {
        $this->testingFramework->markTableAsDirty('tx_realty_cities');

        $this->fixture->loadRealtyObject($this->objectUid);
        $this->fixture->setProperty('city', 'foo');
        $this->fixture->prepareInsertionAndInsertRelations();

        self::assertTrue(
            $this->fixture->getProperty('city') > 0
        );
    }

    /**
     * @test
     */
    public function prepareInsertionAndInsertRelationsInsertsPropertyIntoItsTable()
    {
        $this->testingFramework->markTableAsDirty('tx_realty_cities');

        $this->fixture->loadRealtyObject($this->objectUid);
        $this->fixture->setProperty('city', 'foo');
        $this->fixture->prepareInsertionAndInsertRelations();

        self::assertEquals(
            1,
            $this->testingFramework->countRecords('tx_realty_cities')
        );
    }

    /**
     * @test
     */
    public function prepareInsertionAndInsertRelationsInsertsPropertyWithQuotesInTitleIntoItsTable()
    {
        $this->testingFramework->markTableAsDirty('tx_realty_cities');

        $this->fixture->loadRealtyObject($this->objectUid);
        $this->fixture->setProperty('city', 'foo "bar"');
        $this->fixture->prepareInsertionAndInsertRelations();

        self::assertEquals(
            1,
            $this->testingFramework->countRecords('tx_realty_cities')
        );
    }

    /**
     * @test
     */
    public function prepareInsertionAndInsertRelationsCreatesRelationToAlreadyExistingPropertyWithMatchingPid()
    {
        Tx_Oelib_ConfigurationProxy::getInstance('realty')->setAsInteger('pidForAuxiliaryRecords', $this->otherPageUid);
        $cityUid = $this->testingFramework->createRecord(
            'tx_realty_cities',
            ['title' => 'test city', 'pid' => $this->otherPageUid]
        );

        $this->fixture->loadRealtyObject($this->objectUid);
        $this->fixture->setProperty('city', 'test city');
        $this->fixture->prepareInsertionAndInsertRelations();

        self::assertEquals(
            $cityUid,
            $this->fixture->getProperty('city')
        );
    }

    /**
     * @test
     */
    public function prepareInsertionAndInsertRelationsCreatesRelationToAlreadyExistingPropertyWithMismatchingPid()
    {
        Tx_Oelib_ConfigurationProxy::getInstance('realty')
            ->setAsInteger('pidForAuxiliaryRecords', $this->otherPageUid + 1);
        $cityUid = $this->testingFramework->createRecord(
            'tx_realty_cities',
            ['title' => 'test city', 'pid' => $this->otherPageUid]
        );

        $this->fixture->loadRealtyObject($this->objectUid);
        $this->fixture->setProperty('city', 'test city');
        $this->fixture->prepareInsertionAndInsertRelations();

        self::assertEquals(
            $cityUid,
            $this->fixture->getProperty('city')
        );
    }

    /**
     * @test
     */
    public function prepareInsertionAndInsertDoesNotUpdateThePidOfAnAlreadyExistingPropertyForMismatchingPids()
    {
        Tx_Oelib_ConfigurationProxy::getInstance('realty')
            ->setAsInteger('pidForAuxiliaryRecords', $this->otherPageUid + 1);
        $cityUid = $this->testingFramework->createRecord(
            'tx_realty_cities',
            ['title' => 'test city', 'pid' => $this->otherPageUid]
        );

        $this->fixture->loadRealtyObject($this->objectUid);
        $this->fixture->setProperty('city', 'test city');
        $this->fixture->prepareInsertionAndInsertRelations();

        self::assertEquals(
            1,
            $this->testingFramework->countRecords(
                'tx_realty_cities',
                'uid=' . $cityUid . ' AND pid=' . $this->otherPageUid
            )
        );
    }

    /**
     * @test
     */
    public function prepareInsertionAndInsertRelationsDoesNotCreateARecordForAnInteger()
    {
        $this->testingFramework->markTableAsDirty('tx_realty_cities');
        $this->fixture->loadRealtyObject($this->objectUid);
        $this->fixture->setProperty('city', '12345');
        $this->fixture->prepareInsertionAndInsertRelations();

        self::assertEquals(
            0,
            $this->testingFramework->countRecords('tx_realty_cities')
        );
    }

    /**
     * @test
     */
    public function prepareInsertionAndInsertRelationsDoesNotCreateARecordForZeroPropertyFromTheDatabase()
    {
        $this->testingFramework->markTableAsDirty('tx_realty_cities');
        $this->fixture->loadRealtyObject($this->objectUid);
        $this->fixture->prepareInsertionAndInsertRelations();

        self::assertEquals(
            0,
            $this->testingFramework->countRecords('tx_realty_cities')
        );
    }

    /**
     * @test
     */
    public function prepareInsertionAndInsertRelationsDoesNotCreateARecordForZeroPropertyFromLoadedArray()
    {
        $this->testingFramework->markTableAsDirty('tx_realty_cities');
        $this->fixture->loadRealtyObject(
            ['object_number' => self::$objectNumber, 'city' => 0]
        );
        $this->fixture->prepareInsertionAndInsertRelations();

        self::assertEquals(
            0,
            $this->testingFramework->countRecords('tx_realty_cities')
        );
    }

    /**
     * @test
     */
    public function prepareInsertionAndInsertRelationsReturnsZeroForEmptyPropertyFetchedFromLoadedArray()
    {
        $this->testingFramework->markTableAsDirty('tx_realty_cities');
        $this->fixture->loadRealtyObject(
            ['object_number' => self::$objectNumber, 'city' => '']
        );
        $this->fixture->prepareInsertionAndInsertRelations();

        self::assertEquals(
            0,
            $this->testingFramework->countRecords('tx_realty_cities')
        );
    }

    /**
     * @test
     */
    public function prepareInsertionAndInsertRelationsReturnsZeroIfThePropertyNotExists()
    {
        $this->testingFramework->markTableAsDirty('tx_realty_cities');
        $this->fixture->loadRealtyObject(
            ['object_number' => self::$objectNumber]
        );
        $this->fixture->prepareInsertionAndInsertRelations();

        self::assertEquals(
            0,
            $this->testingFramework->countRecords('tx_realty_cities')
        );
    }

    /**
     * @test
     */
    public function addImageRecordInsertsNewEntryWithParentUid()
    {
        $this->testingFramework->markTableAsDirty('tx_realty_images');

        $this->fixture->loadRealtyObject($this->objectUid);
        $this->fixture->addImageRecord('foo', 'foo.jpg');
        $this->fixture->writeToDatabase();

        self::assertEquals(
            ['image' => 'foo.jpg'],
            Tx_Oelib_Db::selectSingle(
                'image',
                'tx_realty_images',
                'object = ' . $this->objectUid
            )
        );
    }

    /**
     * @test
     */
    public function insertImageEntriesInsertsNewImageWithCaptionWithQuotationMarks()
    {
        $this->testingFramework->markTableAsDirty('tx_realty_images');

        $this->fixture->loadRealtyObject($this->objectUid);
        $this->fixture->addImageRecord('foo "bar"', 'foo.jpg');
        $this->fixture->writeToDatabase();

        self::assertEquals(
            ['image' => 'foo.jpg'],
            Tx_Oelib_Db::selectSingle(
                'image',
                'tx_realty_images',
                'object = ' . $this->objectUid
            )
        );
    }

    /**
     * @test
     */
    public function insertImageEntriesInsertsImageWithEmptyTitleIfNoTitleIsSet()
    {
        $this->testingFramework->markTableAsDirty('tx_realty_images');

        $this->fixture->loadRealtyObject($this->objectUid);
        $this->fixture->addImageRecord('', 'foo.jpg');
        $this->fixture->writeToDatabase();

        self::assertEquals(
            ['caption' => '', 'image' => 'foo.jpg'],
            Tx_Oelib_Db::selectSingle(
                'caption, image',
                'tx_realty_images',
                'object = ' . $this->objectUid
            )
        );
    }

    /**
     * @test
     */
    public function deleteFromDatabaseRemovesRelatedImage()
    {
        $this->testingFramework->markTableAsDirty('tx_realty_images');

        $this->fixture->loadRealtyObject($this->objectUid);
        $this->fixture->addImageRecord('foo', 'foo.jpg');
        $this->fixture->writeToDatabase();
        $this->fixture->setToDeleted();
        $message = $this->fixture->writeToDatabase();

        self::assertEquals(
            1,
            $this->testingFramework->countRecords(
                'tx_realty_images',
                'deleted = 1'
            )
        );
        self::assertEquals(
            'message_deleted_flag_causes_deletion',
            $message
        );
    }

    /**
     * @test
     */
    public function deleteFromDatabaseRemovesSeveralRelatedImages()
    {
        $this->testingFramework->markTableAsDirty('tx_realty_images');

        $this->fixture->loadRealtyObject($this->objectUid);
        $this->fixture->addImageRecord('foo1', 'foo1.jpg');
        $this->fixture->addImageRecord('foo2', 'foo2.jpg');
        $this->fixture->addImageRecord('foo3', 'foo3.jpg');
        $this->fixture->writeToDatabase();
        $this->fixture->setToDeleted();
        $message = $this->fixture->writeToDatabase();

        self::assertEquals(
            3,
            $this->testingFramework->countRecords(
                'tx_realty_images',
                'deleted = 1'
            )
        );
        self::assertEquals(
            'message_deleted_flag_causes_deletion',
            $message
        );
    }

    /**
     * @test
     */
    public function writeToDatabaseInsertsCorrectPageUidForNewRecord()
    {
        $this->fixture->loadRealtyObject(
            ['object_number' => self::$otherObjectNumber]
        );
        $this->fixture->writeToDatabase();

        self::assertEquals(
            ['pid' => $this->pageUid],
            Tx_Oelib_Db::selectSingle(
                'pid',
                'tx_realty_objects',
                'object_number = "' . self::$otherObjectNumber . '"' .
                Tx_Oelib_Db::enableFields('tx_realty_objects')
            )
        );
    }

    /**
     * @test
     */
    public function writeToDatabaseInsertsCorrectPageUidForNewRecordIfOverridePidIsSet()
    {
        $this->fixture->loadRealtyObject(
            ['object_number' => self::$otherObjectNumber]
        );
        $this->fixture->writeToDatabase($this->otherPageUid);

        self::assertEquals(
            ['pid' => $this->otherPageUid],
            Tx_Oelib_Db::selectSingle(
                'pid',
                'tx_realty_objects',
                'object_number = "' . self::$otherObjectNumber . '"' .
                Tx_Oelib_Db::enableFields('tx_realty_objects')
            )
        );
    }

    /**
     * @test
     */
    public function imagesReceiveTheCorrectPageUidIfOverridePidIsSet()
    {
        $this->testingFramework->markTableAsDirty('tx_realty_images');

        $this->fixture->loadRealtyObject(
            [
                'object_number' => self::$otherObjectNumber,
                'images' => [['caption' => 'foo', 'image' => 'bar.jpg']],
            ]
        );
        $this->fixture->writeToDatabase($this->otherPageUid);

        self::assertEquals(
            ['pid' => $this->otherPageUid],
            Tx_Oelib_Db::selectSingle(
                'pid',
                'tx_realty_images',
                'is_dummy_record = 1'
            )
        );
    }

    /**
     * @test
     */
    public function updatingAnExistingRecordDoesNotChangeThePageUid()
    {
        $this->fixture->loadRealtyObject($this->objectUid);
        $this->fixture->setProperty('title', 'new title');

        Tx_Oelib_ConfigurationProxy::getInstance('realty')
            ->setAsInteger('pidForRealtyObjectsAndImages', $this->otherPageUid);
        $message = $this->fixture->writeToDatabase();

        $result = Tx_Oelib_Db::selectSingle(
            'pid',
            'tx_realty_objects',
            'object_number = "' . self::$objectNumber . '"' .
            Tx_Oelib_Db::enableFields('tx_realty_objects')
        );

        self::assertEquals(
            ['pid' => $this->pageUid],
            $result
        );
        self::assertEquals(
            '',
            $message
        );
    }

    /**
     * @test
     */
    public function createANewRealtyRecordAlthoughTheSameRecordWasSetToDeletedInTheDatabase()
    {
        $uid = $this->testingFramework->createRecord(
            'tx_realty_objects',
            [
                'object_number' => self::$otherObjectNumber,
                'deleted' => 1,
            ]
        );

        $this->fixture->loadRealtyObject(
            ['object_number' => self::$otherObjectNumber],
            true
        );
        $this->fixture->writeToDatabase();

        self::assertEquals(
            1,
            $this->testingFramework->countRecords(
                'tx_realty_objects',
                'object_number=' . self::$otherObjectNumber .
                ' AND uid <> ' . $uid .
                Tx_Oelib_Db::enableFields('tx_realty_objects')
            )
        );
    }

    /**
     * @test
     */
    public function writeToDatabaseDeletesAnExistingNonHiddenRealtyRecordIfTheDeletedFlagIsSet()
    {
        $this->fixture->loadRealtyObject($this->objectUid);
        $this->fixture->setToDeleted();
        $this->fixture->writeToDatabase();

        self::assertEquals(
            0,
            $this->testingFramework->countRecords(
                'tx_realty_objects',
                'uid=' . $this->objectUid .
                Tx_Oelib_Db::enableFields('tx_realty_objects')
            )
        );
    }

    /**
     * @test
     */
    public function writeToDatabaseDeletesAnExistingHiddenRealtyRecordIfTheDeletedFlagIsSet()
    {
        $this->fixture->loadRealtyObject($this->objectUid, true);
        $this->fixture->setProperty('hidden', 1);
        $this->fixture->writeToDatabase();

        $this->fixture->setToDeleted();
        $this->fixture->writeToDatabase();

        self::assertEquals(
            0,
            $this->testingFramework->countRecords(
                'tx_realty_objects',
                'uid=' . $this->objectUid .
                Tx_Oelib_Db::enableFields('tx_realty_objects', 1)
            )
        );
    }

    /**
     * @test
     */
    public function deleteAnExistingRealtyRecordAndImportItAgainIfTheDeletedFlagIsSetExplicitly()
    {
        $this->fixture->loadRealtyObject($this->objectUid);
        $this->fixture->setToDeleted();
        $this->fixture->writeToDatabase();

        $realtyObject = new tx_realty_Model_RealtyObjectChild(true);
        $realtyObject->setRequiredFields([]);
        $realtyObject->loadRealtyObject(
            ['object_number' => self::$objectNumber, 'deleted' => 0],
            true
        );
        $realtyObject->writeToDatabase();

        self::assertEquals(
            1,
            $this->testingFramework->countRecords(
                'tx_realty_objects',
                'object_number=' . self::$objectNumber .
                ' AND uid <> ' . $this->objectUid .
                Tx_Oelib_Db::enableFields('tx_realty_objects')
            )
        );
    }

    /**
     * @test
     */
    public function deleteAnExistingRealtyRecordAndImportItAgainIfTheDeletedFlagIsNotSetExplicitly()
    {
        $this->fixture->loadRealtyObject($this->objectUid);
        $this->fixture->setToDeleted();
        $this->fixture->writeToDatabase();

        $realtyObject = new tx_realty_Model_RealtyObjectChild(true);
        $realtyObject->setRequiredFields([]);
        $realtyObject->loadRealtyObject(
            ['object_number' => self::$objectNumber],
            true
        );
        $realtyObject->writeToDatabase();

        self::assertEquals(
            1,
            $this->testingFramework->countRecords(
                'tx_realty_objects',
                'object_number=' . self::$objectNumber .
                ' AND uid <> ' . $this->objectUid .
                Tx_Oelib_Db::enableFields('tx_realty_objects')
            )
        );
    }

    /**
     * @test
     */
    public function loadingAnExistingRecordWithAnImageAndWritingItToTheDatabaseDoesNotDuplicateTheImage()
    {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->objectUid,
            ['images' => 1]
        );
        $this->testingFramework->createRecord(
            'tx_realty_images',
            ['object' => $this->objectUid, 'image' => 'test.jpg']
        );
        $this->fixture->loadRealtyObject($this->objectUid);
        $this->fixture->writeToDatabase();

        self::assertTrue(
            $this->testingFramework->existsExactlyOneRecord(
                'tx_realty_images',
                'deleted = 0 AND image="test.jpg"'
            )
        );
    }

    /**
     * @test
     */
    public function loadingAnExistingRecordWithAnImageByArrayAndWritingItWithAnotherImageToTheDatabaseDeletesTheExistingImage(
    ) {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->objectUid,
            ['images' => 1]
        );
        $this->testingFramework->createRecord(
            'tx_realty_images',
            ['object' => $this->objectUid, 'image' => 'test.jpg']
        );
        $this->fixture->loadRealtyObject(
            [
                'object_number' => self::$objectNumber,
                'images' => [
                    ['caption' => 'test', 'image' => 'test2.jpg'],
                ],
            ]
        );
        $this->fixture->writeToDatabase();

        self::assertTrue(
            $this->testingFramework->existsExactlyOneRecord(
                'tx_realty_images',
                'deleted = 1 AND image="test.jpg"'
            )
        );
    }

    /**
     * @test
     */
    public function importRecordWithImageThatAlreadyExistsForAnotherRecordDoesNotChangeTheOriginalObjectUid()
    {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->objectUid,
            ['images' => 1]
        );
        $this->testingFramework->createRecord(
            'tx_realty_images',
            [
                'object' => $this->objectUid,
                'image' => 'test.jpg',
                'caption' => 'test',
            ]
        );
        $this->fixture->loadRealtyObject(
            [
                'object_number' => self::$otherObjectNumber,
                'images' => [
                    ['caption' => 'test', 'image' => 'test.jpg'],
                ],
            ]
        );
        $this->fixture->writeToDatabase();

        self::assertTrue(
            $this->testingFramework->existsExactlyOneRecord(
                'tx_realty_images',
                'object=' . $this->objectUid . ' AND image="test.jpg"'
            )
        );
    }

    /**
     * @test
     */
    public function recreateAnAuxiliaryRecord()
    {
        $this->testingFramework->markTableAsDirty('tx_realty_cities');
        $cityUid = $this->testingFramework->createRecord(
            'tx_realty_cities',
            [
                'title' => 'foo',
                'deleted' => 1,
            ]
        );

        $this->fixture->loadRealtyObject($this->objectUid);
        $this->fixture->setProperty('city', 'foo');
        $this->fixture->writeToDatabase();

        self::assertEquals(
            1,
            $this->testingFramework->countRecords(
                'tx_realty_cities',
                'title="foo" AND uid <> ' . $cityUid .
                Tx_Oelib_Db::enableFields('tx_realty_cities')
            )
        );
    }

    ////////////////////////////////////
    // Tests concerning addImageRecord
    ////////////////////////////////////

    /**
     * @test
     */
    public function addImageRecordForLoadedObject()
    {
        $this->testingFramework->markTableAsDirty('tx_realty_images');
        $this->fixture->loadRealtyObject($this->objectUid);
        $this->fixture->addImageRecord('foo', 'foo.jpg');

        /** @var tx_realty_Model_Image $firstImage */
        $firstImage = $this->fixture->getImages()->first();
        self::assertEquals(
            'foo',
            $firstImage->getTitle()
        );
    }

    /**
     * @test
     */
    public function addImageRecordForLoadedObjectReturnsKeyWhereTheRecordIsStored()
    {
        $this->testingFramework->markTableAsDirty('tx_realty_images');
        $this->fixture->loadRealtyObject($this->objectUid);

        self::assertEquals(
            0,
            $this->fixture->addImageRecord('foo', 'foo.jpg')
        );
    }

    /**
     * @test
     *
     * @expectedException \BadMethodCallException
     */
    public function addImageRecordForNoObjectLoadedThrowsException()
    {
        $this->testingFramework->markTableAsDirty('tx_realty_images');

        $this->fixture->addImageRecord('foo', 'foo.jpg');
    }

    /**
     * @test
     */
    public function addImagesRecordsUpdatesTheNumberOfCurrentlyAppendedImagesForTheRealtyObject()
    {
        $this->testingFramework->markTableAsDirty('tx_realty_images');

        $this->fixture->loadRealtyObject($this->objectUid);
        $this->fixture->addImageRecord('foo1', 'foo1.jpg');
        $this->fixture->addImageRecord('foo2', 'foo2.jpg');
        $this->fixture->addImageRecord('foo3', 'foo3.jpg');

        self::assertEquals(
            3,
            $this->fixture->getProperty('images')
        );
    }

    /**
     * @test
     */
    public function addImageRecordByDefaultSetsPositionToZero()
    {
        $this->testingFramework->markTableAsDirty('tx_realty_images');
        $this->fixture->loadRealtyObject($this->objectUid);
        $this->fixture->addImageRecord('foo', 'foo.jpg');

        /** @var tx_realty_Model_Image $firstImage */
        $firstImage = $this->fixture->getImages()->first();
        self::assertEquals(
            0,
            $firstImage->getPosition()
        );
    }

    /**
     * @test
     */
    public function addImageRecordCanSetPositionZero()
    {
        $this->testingFramework->markTableAsDirty('tx_realty_images');
        $this->fixture->loadRealtyObject($this->objectUid);
        $this->fixture->addImageRecord('foo', 'foo.jpg', 0);

        /** @var tx_realty_Model_Image $firstImage */
        $firstImage = $this->fixture->getImages()->first();
        self::assertEquals(
            0,
            $firstImage->getPosition()
        );
    }

    /**
     * @test
     */
    public function addImageRecordCanSetPositionOne()
    {
        $this->testingFramework->markTableAsDirty('tx_realty_images');
        $this->fixture->loadRealtyObject($this->objectUid);
        $this->fixture->addImageRecord('foo', 'foo.jpg', 1);

        /** @var tx_realty_Model_Image $firstImage */
        $firstImage = $this->fixture->getImages()->first();
        self::assertEquals(
            1,
            $firstImage->getPosition()
        );
    }

    /**
     * @test
     */
    public function addImageRecordCanSetPositionFour()
    {
        $this->testingFramework->markTableAsDirty('tx_realty_images');
        $this->fixture->loadRealtyObject($this->objectUid);
        $this->fixture->addImageRecord('foo', 'foo.jpg', 4);

        /** @var tx_realty_Model_Image $firstImage */
        $firstImage = $this->fixture->getImages()->first();
        self::assertEquals(
            4,
            $firstImage->getPosition()
        );
    }

    /**
     * @test
     */
    public function addImageRecordByDefaultSetsEmptyThumbnailFileName()
    {
        $this->testingFramework->markTableAsDirty('tx_realty_images');
        $this->fixture->loadRealtyObject($this->objectUid);
        $this->fixture->addImageRecord('foo', 'foo.jpg');

        /** @var tx_realty_Model_Image $firstImage */
        $firstImage = $this->fixture->getImages()->first();
        self::assertEquals(
            '',
            $firstImage->getThumbnailFileName()
        );
    }

    /**
     * @test
     */
    public function addImageRecordCanSetNonEmptyThumbnailFileName()
    {
        $this->testingFramework->markTableAsDirty('tx_realty_images');
        $this->fixture->loadRealtyObject($this->objectUid);
        $this->fixture->addImageRecord('foo', 'foo.jpg', 0, 'bar.jpg');

        /** @var tx_realty_Model_Image $firstImage */
        $firstImage = $this->fixture->getImages()->first();
        self::assertEquals(
            'bar.jpg',
            $firstImage->getThumbnailFileName()
        );
    }

    //////////////////////////////////////////////
    // Tests concerning markImageRecordAsDeleted
    //////////////////////////////////////////////

    /**
     * @test
     */
    public function markImageRecordAsDeletedUpdatesTheNumberOfCurrentlyAppendedImagesForTheRealtyObject()
    {
        $this->testingFramework->markTableAsDirty('tx_realty_images');

        $this->fixture->loadRealtyObject($this->objectUid);
        $this->fixture->addImageRecord('foo1', 'foo1.jpg');
        $this->fixture->addImageRecord('foo2', 'foo2.jpg');
        $this->fixture->markImageRecordAsDeleted(
            $this->fixture->addImageRecord('foo', 'foo.jpg')
        );

        self::assertEquals(
            2,
            $this->fixture->getProperty('images')
        );
    }

    /**
     * @test
     *
     * @expectedException \BadMethodCallException
     */
    public function markImageRecordAsDeletedForNoObjectLoadedThrowsException()
    {
        $this->testingFramework->markTableAsDirty('tx_realty_images');

        $this->fixture->markImageRecordAsDeleted(
            $this->fixture->addImageRecord('foo', 'foo.jpg')
        );
    }

    /**
     * @test
     *
     * @expectedException \Tx_Oelib_Exception_NotFound
     */
    public function markImageRecordAsDeletedForNonExistingRecordThrowsException()
    {
        $this->testingFramework->markTableAsDirty('tx_realty_images');

        $this->fixture->loadRealtyObject($this->objectUid);
        $this->fixture->markImageRecordAsDeleted(
            $this->fixture->addImageRecord('foo', 'foo.jpg') + 1
        );
    }

    /////////////////////////////////////////////////
    // Tests concerning writeToDatabase with images
    /////////////////////////////////////////////////

    /**
     * @test
     */
    public function writeToDatabaseMarksImageRecordToDeleteAsDeleted()
    {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->objectUid,
            ['images' => 1]
        );
        $imageUid = $this->testingFramework->createRecord(
            'tx_realty_images',
            [
                'caption' => 'foo',
                'image' => 'foo.jpg',
                'object' => $this->objectUid,
            ]
        );

        $this->fixture->loadRealtyObject($this->objectUid);
        $this->fixture->markImageRecordAsDeleted(0);
        $this->fixture->writeToDatabase();

        self::assertEquals(
            1,
            $this->testingFramework->countRecords(
                'tx_realty_images',
                'uid=' . $imageUid . ' AND deleted=1'
            )
        );
    }

    /**
     * @test
     */
    public function writeToDatabaseCreatesNewImageRecordIfTheSameRecordExistsButIsDeleted()
    {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->objectUid,
            ['images' => 1]
        );
        $this->testingFramework->createRecord(
            'tx_realty_images',
            [
                'caption' => 'foo',
                'image' => 'foo.jpg',
                'object' => $this->objectUid,
                'deleted' => 1,
            ]
        );
        $this->fixture->loadRealtyObject($this->objectUid);
        $this->fixture->addImageRecord('foo', 'foo.jpg');
        $this->fixture->writeToDatabase();

        self::assertEquals(
            2,
            $this->testingFramework->countRecords(
                'tx_realty_images',
                'image = "foo.jpg"'
            )
        );
    }

    /**
     * @test
     */
    public function writeToDatabaseDeletesExistingImageFromTheFileSystem()
    {
        $fileName = $this->testingFramework->createDummyFile('foo.jpg');
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->objectUid,
            ['images' => 1]
        );
        $this->testingFramework->createRecord(
            'tx_realty_images',
            [
                'caption' => 'foo',
                'image' => basename($fileName),
                'object' => $this->objectUid,
            ]
        );

        $this->fixture->loadRealtyObject($this->objectUid);
        $this->fixture->markImageRecordAsDeleted(0);
        $this->fixture->writeToDatabase();

        self::assertFileNotExists(
            $fileName
        );
    }

    /**
     * @test
     */
    public function writeToDatabaseNotAddsImageRecordWithDeletedFlagSet()
    {
        $this->testingFramework->markTableAsDirty('tx_realty_images');

        $this->fixture->loadRealtyObject($this->objectUid);
        $this->fixture->markImageRecordAsDeleted(
            $this->fixture->addImageRecord('foo', 'foo.jpg')
        );
        $this->fixture->writeToDatabase();

        self::assertEquals(
            0,
            $this->testingFramework->countRecords(
                'tx_realty_images',
                'deleted = 1'
            )
        );
    }

    /**
     * @test
     */
    public function importANewRecordWithImagesAndTheDeletedFlagBeingSetReturnsMarkedAsDeletedMessageKey()
    {
        $this->testingFramework->markTableAsDirty('tx_realty_images');

        $this->fixture->loadRealtyObject(
            ['object_number' => 'foo-bar', 'deleted' => 1]
        );
        $this->fixture->addImageRecord('foo', 'foo.jpg');

        self::assertEquals(
            'message_deleted_flag_set',
            $this->fixture->writeToDatabase()
        );
    }

    /////////////////////////////////
    // Tests concerning addDocument
    /////////////////////////////////

    /**
     * @test
     */
    public function numberOfAppendedDocumentsInitiallyIsZero()
    {
        $this->testingFramework->markTableAsDirty('tx_realty_documents');

        $this->fixture->loadRealtyObject($this->objectUid);

        self::assertEquals(
            0,
            $this->fixture->getProperty('documents')
        );
    }

    /**
     * @test
     */
    public function addDocumentMakesDocumentAvailableViaGetDocuments()
    {
        $this->testingFramework->markTableAsDirty('tx_realty_documents');
        $this->fixture->loadRealtyObject($this->objectUid);
        $this->fixture->addDocument('foo', 'foo.pdf');

        /** @var tx_realty_Model_Document $firstDocument */
        $firstDocument = $this->fixture->getDocuments()->first();
        self::assertEquals(
            'foo',
            $firstDocument->getTitle()
        );
    }

    /**
     * @test
     */
    public function addDocumentForFirstDocumentsReturnsZeroIndex()
    {
        $this->testingFramework->markTableAsDirty('tx_realty_documents');
        $this->fixture->loadRealtyObject($this->objectUid);

        self::assertEquals(
            0,
            $this->fixture->addDocument('foo', 'foo.pdf')
        );
    }

    /**
     * @test
     *
     * @expectedException \BadMethodCallException
     */
    public function addDocumentForNoObjectLoadedThrowsException()
    {
        $this->testingFramework->markTableAsDirty('tx_realty_documents');

        $this->fixture->addDocument('foo', 'foo.pdf');
    }

    /**
     * @test
     */
    public function addDocumentUpdatesTheNumberOfAppendedDocuments()
    {
        $this->testingFramework->markTableAsDirty('tx_realty_documents');

        $this->fixture->loadRealtyObject($this->objectUid);
        $this->fixture->addDocument('foo1', 'foo1.pdf');
        $this->fixture->addDocument('foo2', 'foo2.pdf');
        $this->fixture->addDocument('foo3', 'foo3.pdf');

        self::assertEquals(
            3,
            $this->fixture->getProperty('documents')
        );
    }

    ////////////////////////////////////
    // Tests concerning deleteDocument
    ////////////////////////////////////

    /**
     * @test
     */
    public function deleteDocumentUpdatesTheNumberOfAppendedDocuments()
    {
        $this->testingFramework->markTableAsDirty('tx_realty_documents');

        $this->fixture->loadRealtyObject($this->objectUid);
        $this->fixture->addDocument('foo1', 'foo1.pdf');
        $this->fixture->addDocument('foo2', 'foo2.pdf');
        $this->fixture->deleteDocument(
            $this->fixture->addDocument('foo', 'foo.pdf')
        );

        self::assertEquals(
            2,
            $this->fixture->getProperty('documents')
        );
    }

    /**
     * @test
     *
     * @expectedException \BadMethodCallException
     */
    public function deleteDocumentForNoObjectLoadedThrowsException()
    {
        $this->testingFramework->markTableAsDirty('tx_realty_documents');

        $this->fixture->deleteDocument(
            $this->fixture->addDocument('foo', 'foo.pdf')
        );
    }

    /**
     * @test
     *
     * @expectedException \Tx_Oelib_Exception_NotFound
     */
    public function deleteDocumentForNonExistingRecordThrowsException()
    {
        $this->testingFramework->markTableAsDirty('tx_realty_documents');

        $this->fixture->loadRealtyObject($this->objectUid);
        $documentKey = $this->fixture->addDocument('foo', 'foo.pdf') + 1;

        $this->fixture->deleteDocument($documentKey);
    }

    ////////////////////////////////////////////////////
    // Tests concerning writeToDatabase with documents
    ////////////////////////////////////////////////////

    /**
     * @test
     */
    public function writeToDatabaseMarksDocumentRecordToDeleteAsDeleted()
    {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->objectUid,
            ['documents' => 1]
        );
        $documentUid = $this->testingFramework->createRecord(
            'tx_realty_documents',
            [
                'title' => 'foo',
                'filename' => 'foo.pdf',
                'object' => $this->objectUid,
            ]
        );

        $this->fixture->loadRealtyObject($this->objectUid);
        $this->fixture->deleteDocument(0);
        $this->fixture->writeToDatabase();

        self::assertTrue(
            $this->testingFramework->existsRecord(
                'tx_realty_documents',
                'uid = ' . $documentUid . ' AND deleted = 1'
            )
        );
    }

    /**
     * @test
     */
    public function writeToDatabaseCreatesNewDocumentRecordIfTheSameRecordExistsButIsDeleted()
    {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->objectUid,
            ['documents' => 1]
        );
        $this->testingFramework->createRecord(
            'tx_realty_documents',
            [
                'title' => 'foo',
                'filename' => 'foo.pdf',
                'object' => $this->objectUid,
                'deleted' => 1,
            ]
        );
        $this->fixture->loadRealtyObject($this->objectUid);
        $this->fixture->addDocument('foo', 'foo.pdf');
        $this->fixture->writeToDatabase();

        self::assertEquals(
            1,
            $this->testingFramework->countRecords(
                'tx_realty_documents',
                'filename = "foo.pdf" AND deleted = 0'
            )
        );
    }

    /**
     * @test
     */
    public function writeToDatabaseDeletesExistingDocumentFromFileSystem()
    {
        $fileName = $this->testingFramework->createDummyFile('foo.pdf');
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->objectUid,
            ['documents' => 1]
        );
        $this->testingFramework->createRecord(
            'tx_realty_documents',
            [
                'title' => 'foo',
                'filename' => basename($fileName),
                'object' => $this->objectUid,
            ]
        );

        $this->fixture->loadRealtyObject($this->objectUid);
        $this->fixture->deleteDocument(0);
        $this->fixture->writeToDatabase();

        self::assertFileNotExists(
            $fileName
        );
    }

    /**
     * @test
     */
    public function writeToDatabaseNotAddsDeletedDocumentRecord()
    {
        $this->testingFramework->markTableAsDirty('tx_realty_documents');

        $this->fixture->loadRealtyObject($this->objectUid);
        $this->fixture->deleteDocument(
            $this->fixture->addDocument('foo', 'foo.pdf')
        );
        $this->fixture->writeToDatabase();

        self::assertEquals(
            0,
            $this->testingFramework->countRecords(
                'tx_realty_documents',
                'deleted = 1'
            )
        );
    }

    /**
     * @test
     */
    public function importANewRecordWithDocumentsAndTheDeletedFlagBeingSetReturnsMarkedAsDeletedMessageKey()
    {
        $this->testingFramework->markTableAsDirty('tx_realty_documents');

        $this->fixture->loadRealtyObject(
            ['object_number' => 'foo-bar', 'deleted' => 1]
        );
        $this->fixture->addDocument('foo', 'foo.pdf');

        self::assertEquals(
            'message_deleted_flag_set',
            $this->fixture->writeToDatabase()
        );
    }

    /////////////////////////////////////
    // Tests for processing owner data.
    /////////////////////////////////////

    /**
     * @test
     */
    public function getAnidReturnsOffererId()
    {
        $anid = 'bklhjewkbjvewq';
        $this->fixture->setData(['openimmo_anid' => $anid]);

        self::assertSame($anid, $this->fixture->getAnid());
    }

    /**
     * @test
     */
    public function uidOfFeUserWithMatchingAnidIsAddedAsOwnerForExistingObjectIfAddingTheOwnerIsAllowed()
    {
        $feUserUid = $this->testingFramework->createFrontEndUser(
            '',
            ['tx_realty_openimmo_anid' => 'test anid']
        );
        $this->fixture->loadRealtyObject($this->objectUid);
        $this->fixture->setProperty('openimmo_anid', 'test anid');
        $this->fixture->writeToDatabase(0, true);

        self::assertEquals(
            $feUserUid,
            $this->fixture->getProperty('owner')
        );
    }

    /**
     * @test
     */
    public function uidOfFeUserWithMatchingAnidIsAddedAsOwnerForNewObjectIfAddingTheOwnerIsAllowed()
    {
        $feUserUid = $this->testingFramework->createFrontEndUser(
            '',
            ['tx_realty_openimmo_anid' => 'test anid']
        );
        $this->fixture->loadRealtyObject(['openimmo_anid' => 'test anid']);
        $this->fixture->writeToDatabase(0, true);

        self::assertEquals(
            $feUserUid,
            $this->fixture->getProperty('owner')
        );
    }

    /**
     * @test
     */
    public function userIsMatchedByAnidWithTheFirstFourCharactersTheSame()
    {
        $feUserUid = $this->testingFramework->createFrontEndUser(
            '',
            ['tx_realty_openimmo_anid' => 'OABC20017128124930123asd43fer35']
        );
        $this->fixture->loadRealtyObject($this->objectUid);
        $this->fixture->setProperty('openimmo_anid', 'OABC10011128124930123asd43fer34');
        $this->fixture->writeToDatabase(0, true);

        self::assertSame(
            $feUserUid,
            (int)$this->fixture->getProperty('owner')
        );
    }

    /**
     * @test
     */
    public function userIsNotMatchedByAnidWithOnlyTheFirstThreeCharactersTheSame()
    {
        $feUserUid = $this->testingFramework->createFrontEndUser(
            '',
            ['tx_realty_openimmo_anid' => 'OABC20017128124930123asd43fer35']
        );
        $this->fixture->loadRealtyObject($this->objectUid);
        $this->fixture->setProperty('openimmo_anid', 'OABD20011128124930123asd43fer34');
        $this->fixture->writeToDatabase(0, true);

        self::assertNotSame(
            $feUserUid,
            (int)$this->fixture->getProperty('owner')
        );
    }

    /**
     * @test
     */
    public function uidOfFeUserWithMatchingAnidIsNotAddedAsOwnerIfThisIsForbidden()
    {
        $this->testingFramework->createFrontEndUser(
            '',
            ['tx_realty_openimmo_anid' => 'test anid']
        );
        $this->fixture->loadRealtyObject($this->objectUid);
        $this->fixture->setProperty('openimmo_anid', 'test anid');
        $this->fixture->writeToDatabase(0, false);

        self::assertEquals(
            0,
            $this->fixture->getProperty('owner')
        );
    }

    /**
     * @test
     */
    public function noOwnerIsAddedForRealtyRecordWithoutOpenImmoAnid()
    {
        $this->testingFramework->createFrontEndUser(
            '',
            ['tx_realty_openimmo_anid' => 'test anid']
        );
        $this->fixture->loadRealtyObject($this->objectUid);
        $this->fixture->writeToDatabase(0, true);

        self::assertEquals(
            0,
            $this->fixture->getProperty('owner')
        );
    }

    /**
     * @test
     */
    public function ownerIsNotChangedAlthoughTheAnidOfARecordIsUpdatedAndDoesNotMatchAnymore()
    {
        $feUserUid = $this->testingFramework->createFrontEndUser(
            '',
            ['tx_realty_openimmo_anid' => 'test anid 1']
        );

        $this->fixture->loadRealtyObject($this->objectUid);
        $this->fixture->setProperty('openimmo_anid', 'test anid 1');
        $this->fixture->writeToDatabase(0, true);
        $this->fixture->setProperty('openimmo_anid', 'test anid 2');
        $this->fixture->writeToDatabase(0, true);

        self::assertEquals(
            $feUserUid,
            $this->fixture->getProperty('owner')
        );
        self::assertEquals(
            'test anid 2',
            $this->fixture->getProperty('openimmo_anid')
        );
    }

    /**
     * @test
     */
    public function ownerIsNotChangedAlthoughTheAnidOfARecordIsUpdatedAndMatchesAnotherFeUser()
    {
        $feUserGroup = $this->testingFramework->createFrontEndUserGroup();
        $uidOfFeUserOne = $this->testingFramework->createFrontEndUser(
            $feUserGroup,
            ['tx_realty_openimmo_anid' => 'test anid 1']
        );
        $this->testingFramework->createFrontEndUser(
            $feUserGroup,
            ['tx_realty_openimmo_anid' => 'test anid 2']
        );
        $this->fixture->loadRealtyObject($this->objectUid);
        $this->fixture->setProperty('openimmo_anid', 'test anid 1');
        $this->fixture->writeToDatabase(0, true);
        $this->fixture->setProperty('openimmo_anid', 'test anid 2');
        $this->fixture->writeToDatabase(0, true);

        self::assertEquals(
            $uidOfFeUserOne,
            $this->fixture->getProperty('owner')
        );
        self::assertEquals(
            'test anid 2',
            $this->fixture->getProperty('openimmo_anid')
        );
    }

    /**
     * @test
     */
    public function useFeUserDataFlagIsSetIfThisOptionIsEnabledByConfiguration()
    {
        $this->testingFramework->createFrontEndUser(
            '',
            ['tx_realty_openimmo_anid' => 'test anid']
        );
        Tx_Oelib_ConfigurationProxy::getInstance('realty')->setAsBoolean(
            'useFrontEndUserDataAsContactDataForImportedRecords',
            true
        );
        $this->fixture->loadRealtyObject($this->objectUid);
        $this->fixture->setProperty('openimmo_anid', 'test anid');
        $this->fixture->writeToDatabase(0, true);

        self::assertEquals(
            1,
            $this->fixture->getProperty('contact_data_source')
        );
    }

    /**
     * @test
     */
    public function useFeUserDataFlagIsNotSetIfThisOptionIsDisabledByConfiguration()
    {
        $this->testingFramework->createFrontEndUser(
            '',
            ['tx_realty_openimmo_anid' => 'test anid']
        );
        Tx_Oelib_ConfigurationProxy::getInstance('realty')->setAsBoolean(
            'useFrontEndUserDataAsContactDataForImportedRecords',
            false
        );
        $this->fixture->loadRealtyObject($this->objectUid);
        $this->fixture->setProperty('openimmo_anid', 'test anid');
        $this->fixture->writeToDatabase(0, true);

        self::assertEquals(
            0,
            $this->fixture->getProperty('contact_data_source')
        );
    }

    /**
     * @test
     */
    public function useFeUserDataFlagIsNotSetIfNoOwnerWasSetAlthoughOptionIsEnabledByConfiguration()
    {
        $this->testingFramework->createFrontEndUser(
            '',
            ['tx_realty_openimmo_anid' => 'test anid']
        );
        Tx_Oelib_ConfigurationProxy::getInstance('realty')->setAsBoolean(
            'useFrontEndUserDataAsContactDataForImportedRecords',
            true
        );
        $this->fixture->loadRealtyObject($this->objectUid);
        $this->fixture->writeToDatabase(0, true);

        self::assertEquals(
            0,
            $this->fixture->getProperty('contact_data_source')
        );
    }

    /*
     * Test concerning the show_address field
     */

    /**
     * @test
     */
    public function getShowAddressInitiallyReturnsFalse()
    {
        $this->fixture->setData([]);

        self::assertFalse(
            $this->fixture->getShowAddress()
        );
    }

    /**
     * @test
     */
    public function getShowAddressReturnsShowAddress()
    {
        $this->fixture->setData(['show_address' => true]);

        self::assertTrue(
            $this->fixture->getShowAddress()
        );
    }

    /**
     * @test
     */
    public function setShowAddressSetsShowAddress()
    {
        $this->fixture->setData([]);
        $this->fixture->setShowAddress(true);
        self::assertTrue(
            $this->fixture->getShowAddress()
        );
    }

    /*
     * Tests concerning getGeoAddress and hasGeoAddress
     */

    /**
     * @test
     */
    public function getGeoAddressForNoAddressDataReturnsEmptyString()
    {
        $this->fixture->loadRealtyObject([
            'street' => '',
            'zip' => '',
            'city' => 0,
            'country' => 0,
        ]);

        self::assertSame(
            '',
            $this->fixture->getGeoAddress()
        );
    }

    /**
     * @test
     */
    public function getGeoAddressForCityOnlyReturnsCityName()
    {
        $this->fixture->loadRealtyObject([
            'street' => '',
            'zip' => '',
            'city' => $this->testingFramework->createRecord('tx_realty_cities', ['title' => 'Bonn']),
            'country' => 0,
        ]);

        self::assertSame(
            'Bonn',
            $this->fixture->getGeoAddress()
        );
    }

    /**
     * @test
     */
    public function getGeoAddressForZipCodeOnlyReturnsEmptyString()
    {
        $this->fixture->loadRealtyObject([
            'street' => '',
            'zip' => '53111',
            'city' => 0,
            'country' => 0,
        ]);

        self::assertSame(
            '',
            $this->fixture->getGeoAddress()
        );
    }

    /**
     * @test
     */
    public function getGeoAddressForZipCodeAndCityReturnsZipCodeAndCity()
    {
        $this->fixture->loadRealtyObject([
            'street' => '',
            'zip' => '53111',
            'city' => $this->testingFramework->createRecord('tx_realty_cities', ['title' => 'Bonn']),
            'country' => 0,
        ]);

        self::assertSame(
            '53111 Bonn',
            $this->fixture->getGeoAddress()
        );
    }

    /**
     * @test
     */
    public function getGeoAddressForStreetAndZipCodeAndCityReturnsStreetAndZipCodeAndCity()
    {
        $this->fixture->loadRealtyObject([
            'street' => 'Am Hof 1',
            'zip' => '53111',
            'city' => $this->testingFramework->createRecord('tx_realty_cities', ['title' => 'Bonn']),
            'country' => 0,
            'show_address' => 1,
        ]);

        self::assertSame(
            'Am Hof 1, 53111 Bonn',
            $this->fixture->getGeoAddress()
        );
    }

    /**
     * @test
     */
    public function getGeoAddressForOnlyStreetReturnsEmptyString()
    {
        $this->fixture->loadRealtyObject([
            'street' => 'Am Hof 1',
            'zip' => '',
            'city' => 0,
            'country' => 0,
            'show_address' => 1,
        ]);

        self::assertSame(
            '',
            $this->fixture->getGeoAddress()
        );
    }

    /**
     * @test
     */
    public function getGeoAddressForStreetAndZipCodeReturnsEmptyString()
    {
        $this->fixture->loadRealtyObject([
            'street' => 'Am Hof 1',
            'zip' => '53111',
            'city' => 0,
            'country' => 0,
            'show_address' => 1,
        ]);

        self::assertSame(
            '',
            $this->fixture->getGeoAddress()
        );
    }

    /**
     * @test
     */
    public function getGeoAddressForStreetAndCityReturnsStreetAndCity()
    {
        $this->fixture->loadRealtyObject([
            'street' => 'Am Hof 1',
            'zip' => '',
            'city' => $this->testingFramework->createRecord('tx_realty_cities', ['title' => 'Bonn']),
            'country' => 0,
            'show_address' => 1,
        ]);

        self::assertSame(
            'Am Hof 1, Bonn',
            $this->fixture->getGeoAddress()
        );
    }

    /**
     * @test
     */
    public function getGeoAddressForStreetAndZipCodeAndCityAndCountryReturnsStreetAndZipCodeAndCityAndCountry()
    {
        $this->fixture->loadRealtyObject([
            'street' => 'Am Hof 1',
            'zip' => '53111',
            'city' => $this->testingFramework->createRecord('tx_realty_cities', ['title' => 'Bonn']),
            'country' => self::DE,
            'show_address' => 1,
        ]);

        self::assertSame(
            'Am Hof 1, 53111 Bonn, DE',
            $this->fixture->getGeoAddress()
        );
    }

    /**
     * @test
     */
    public function getGeoAddressForCityAndCountryReturnsCityAndCountry()
    {
        $this->fixture->loadRealtyObject([
            'street' => '',
            'zip' => '',
            'city' => $this->testingFramework->createRecord('tx_realty_cities', ['title' => 'Bonn']),
            'country' => self::DE,
        ]);

        self::assertSame(
            'Bonn, DE',
            $this->fixture->getGeoAddress()
        );
    }

    /**
     * @test
     */
    public function getGeoAddressForOnlyCountryReturnsEmptyString()
    {
        $this->fixture->loadRealtyObject([
            'street' => '',
            'zip' => '',
            'city' => 0,
            'country' => self::DE,
        ]);

        self::assertSame(
            '',
            $this->fixture->getGeoAddress()
        );
    }

    /**
     * @test
     */
    public function hasGeoAddressForNoAddressDataReturnsFalse()
    {
        $this->fixture->loadRealtyObject([
            'street' => '',
            'zip' => '',
            'city' => 0,
            'country' => 0,
        ]);

        self::assertFalse(
            $this->fixture->hasGeoAddress()
        );
    }

    /**
     * @test
     */
    public function hasGeoAddressForFullAddressReturnsTrue()
    {
        $this->fixture->loadRealtyObject([
            'street' => 'Am Hof 1',
            'zip' => '53111',
            'city' => $this->testingFramework->createRecord('tx_realty_cities', ['title' => 'Bonn']),
            'country' => self::DE,
            'show_address' => 1,
        ]);

        self::assertTrue(
            $this->fixture->hasGeoAddress()
        );
    }

    ////////////////////////////
    // Tests concerning getUid
    ////////////////////////////

    /**
     * @test
     */
    public function getUidReturnsZeroForObjectWithoutUid()
    {
        $realtyObject = new tx_realty_Model_RealtyObjectChild(true);

        self::assertEquals(
            0,
            $realtyObject->getUid()
        );
    }

    /**
     * @test
     */
    public function getUidReturnsCurrentUidForObjectWithUid()
    {
        $this->fixture->loadRealtyObject($this->objectUid);

        self::assertEquals(
            $this->objectUid,
            $this->fixture->getUid()
        );
    }

    //////////////////////////////
    // Tests concerning getTitle
    //////////////////////////////

    /**
     * @test
     */
    public function getTitleReturnsEmptyStringForObjectWithoutTitle()
    {
        $realtyObject = new tx_realty_Model_RealtyObjectChild(true);
        $realtyObject->loadRealtyObject(0);

        self::assertEquals(
            '',
            $realtyObject->getTitle()
        );
    }

    /**
     * @test
     */
    public function getTitleReturnsFullTitleForObjectWithTitle()
    {
        $this->fixture->loadRealtyObject(
            ['title' => 'foo title filltext-filltext-filltext-filltext']
        );

        self::assertEquals(
            'foo title filltext-filltext-filltext-filltext',
            $this->fixture->getTitle()
        );
    }

    /////////////////////////////////////
    // Tests concerning getCroppedTitle
    /////////////////////////////////////

    /**
     * @test
     */
    public function getCroppedTitleReturnsEmptyStringForObjectWithoutTitle()
    {
        $realtyObject = new tx_realty_Model_RealtyObjectChild(true);
        $realtyObject->loadRealtyObject(0);

        self::assertEquals(
            '',
            $realtyObject->getCroppedTitle()
        );
    }

    /**
     * @test
     */
    public function getCroppedTitleReturnsFullShortTitleForObjectWithTitle()
    {
        $this->fixture->loadRealtyObject(
            ['title' => '12345678901234567890123456789012']
        );

        self::assertEquals(
            '12345678901234567890123456789012',
            $this->fixture->getCroppedTitle()
        );
    }

    /**
     * @test
     */
    public function getCroppedTitleReturnsLongTitleCroppedAtDefaultCropSize()
    {
        $this->fixture->loadRealtyObject(
            ['title' => '123456789012345678901234567890123']
        );

        self::assertEquals(
            '12345678901234567890123456789012…',
            $this->fixture->getCroppedTitle()
        );
    }

    /**
     * @test
     */
    public function getCroppedTitleReturnsLongTitleCroppedAtGivenCropSize()
    {
        $this->fixture->loadRealtyObject(
            ['title' => '123456789012345678901234567890123']
        );

        self::assertEquals(
            '1234567890…',
            $this->fixture->getCroppedTitle(10)
        );
    }

    /**
     * @test
     */
    public function getCroppedTitleWithZeroGivenReturnsLongTitleCroppedAtDefaultLength()
    {
        $this->fixture->loadRealtyObject(
            ['title' => '123456789012345678901234567890123']
        );

        self::assertEquals(
            '12345678901234567890123456789012…',
            $this->fixture->getCroppedTitle(0)
        );
    }

    /**
     * @test
     */
    public function getCroppedTitleWithStringGivenReturnsLongTitleCroppedAtDefaultLength()
    {
        $this->fixture->loadRealtyObject(
            ['title' => '123456789012345678901234567890123']
        );

        self::assertEquals(
            '12345678901234567890123456789012…',
            $this->fixture->getCroppedTitle('foo')
        );
    }

    /////////////////////////////////////////////
    // Tests concerning getForeignPropertyField
    /////////////////////////////////////////////

    /**
     * @test
     *
     * @expectedException \InvalidArgumentException
     */
    public function getForeignPropertyFieldForNonAllowedFieldThrowsException()
    {
        $this->fixture->loadRealtyObject($this->objectUid);
        $this->fixture->getForeignPropertyField('floor');
    }

    /**
     * @test
     */
    public function getForeignPropertyFieldReturnsNonNumericFieldContentForAllowedField()
    {
        $this->fixture->loadRealtyObject($this->objectUid);
        $this->fixture->setProperty('city', 'test city');

        self::assertEquals(
            'test city',
            $this->fixture->getForeignPropertyField('city')
        );
    }

    /**
     * @test
     */
    public function getForeignPropertyFieldReturnsEmptyStringIfThereIsNoPropertySetForAllowedField()
    {
        $this->fixture->loadRealtyObject($this->objectUid);

        self::assertEquals(
            '',
            $this->fixture->getForeignPropertyField('city')
        );
    }

    /**
     * @test
     */
    public function getForeignPropertyFieldReturnsACitysTitle()
    {
        $this->fixture->loadRealtyObject($this->objectUid);
        $cityUid = $this->testingFramework->createRecord(
            'tx_realty_cities',
            ['title' => 'foo']
        );
        $this->fixture->setProperty('city', $cityUid);

        self::assertEquals(
            'foo',
            $this->fixture->getForeignPropertyField('city')
        );
    }

    /**
     * @test
     */
    public function getForeignPropertyFieldReturnsADistrictsTitle()
    {
        $this->fixture->loadRealtyObject($this->objectUid);
        $districtUid = $this->testingFramework->createRecord(
            'tx_realty_districts',
            ['title' => 'foo']
        );
        $this->fixture->setProperty('district', $districtUid);

        self::assertEquals(
            'foo',
            $this->fixture->getForeignPropertyField('district')
        );
    }

    /**
     * @test
     */
    public function getForeignPropertyFieldReturnsACountrysShortLocalName()
    {
        $this->fixture->loadRealtyObject($this->objectUid);
        $this->fixture->setProperty('country', self::DE);

        self::assertEquals(
            'Deutschland',
            $this->fixture->getForeignPropertyField('country', 'cn_short_local')
        );
    }

    //////////////////////////////////////
    // Tests concerning getAddressAsHtml
    //////////////////////////////////////

    /**
     * @test
     */
    public function getAddressAsHtmlReturnsFormattedPartlyAddressIfAllDataProvidedAndShowAddressFalse()
    {
        $this->fixture->loadRealtyObject([
            'street' => 'Main Street',
            'zip' => '12345',
            'city' => 'Test Town',
            'district' => 'District',
            'country' => self::DE,
        ]);

        self::assertEquals(
            '12345 Test Town District<br />Deutschland',
            $this->fixture->getAddressAsHtml()
        );
    }

    /**
     * @test
     */
    public function getAddressAsHtmlReturnsFormattedCompleteAddressIfAllDataProvidedAndShowAddressTrue()
    {
        $this->fixture->loadRealtyObject([
            'show_address' => 1,
            'street' => 'Main Street',
            'zip' => '12345',
            'city' => 'Test Town',
            'district' => 'District',
            'country' => self::DE,
        ]);

        self::assertEquals(
            'Main Street<br />12345 Test Town District<br />Deutschland',
            $this->fixture->getAddressAsHtml()
        );
    }

    /**
     * @test
     */
    public function getAddressAsHtmlReturnsFormattedAddressForAllDataButCountryProvidedAndShowAddressTrue()
    {
        $this->fixture->loadRealtyObject([
            'show_address' => 1,
            'street' => 'Main Street',
            'zip' => '12345',
            'city' => 'Test Town',
            'district' => 'District',
        ]);

        self::assertEquals(
            'Main Street<br />12345 Test Town District',
            $this->fixture->getAddressAsHtml()
        );
    }

    /**
     * @test
     */
    public function getAddressAsHtmlReturnsFormattedAddressForAllDataButStreetProvidedAndShowAddressTrue()
    {
        $this->fixture->loadRealtyObject([
            'show_address' => 1,
            'zip' => '12345',
            'city' => 'Test Town',
            'district' => 'District',
            'country' => self::DE,
        ]);

        self::assertEquals(
            '12345 Test Town District<br />Deutschland',
            $this->fixture->getAddressAsHtml()
        );
    }

    /**
     * @test
     */
    public function getAddressAsHtmlReturnsFormattedAddressForOnlyStreetProvidedAndShowAddressTrue()
    {
        $this->fixture->loadRealtyObject([
            'show_address' => 1,
            'street' => 'Main Street',
        ]);

        self::assertEquals(
            'Main Street<br />',
            $this->fixture->getAddressAsHtml()
        );
    }

    ////////////////////////////////////////////
    // Tests concerning getAddressAsSingleLine
    ////////////////////////////////////////////

    /**
     * @test
     */
    public function getAddressAsSingleLineForShowAddressFalseReturnsAddressWithoutStreet()
    {
        $this->fixture->loadRealtyObject([
            'street' => 'Main Street',
            'zip' => '12345',
            'city' => 'Test Town',
            'district' => 'District',
            'country' => self::DE,
        ]);

        self::assertEquals(
            '12345 Test Town District, Deutschland',
            $this->fixture->getAddressAsSingleLine()
        );
    }

    /**
     * @test
     */
    public function getAddressAsSingleLineForShowAddressTrueReturnsCompleteAddress()
    {
        $this->fixture->loadRealtyObject([
            'show_address' => 1,
            'street' => 'Main Street',
            'zip' => '12345',
            'city' => 'Test Town',
            'district' => 'District',
            'country' => self::DE,
        ]);

        self::assertEquals(
            'Main Street, 12345 Test Town District, Deutschland',
            $this->fixture->getAddressAsSingleLine()
        );
    }

    /**
     * @test
     */
    public function getAddressAsSingleLineForNoCountrySetAndShowAddressTrueReturnsAddressWithoutCountry()
    {
        $this->fixture->loadRealtyObject([
            'show_address' => 1,
            'street' => 'Main Street',
            'zip' => '12345',
            'city' => 'Test Town',
            'district' => 'District',
        ]);

        self::assertEquals(
            'Main Street, 12345 Test Town District',
            $this->fixture->getAddressAsSingleLine()
        );
    }

    /**
     * @test
     */
    public function getAddressAsSingleLineForNoStreetSetAndShowAddressTrueReturnsAddressWithoutStreet()
    {
        $this->fixture->loadRealtyObject([
            'show_address' => 1,
            'zip' => '12345',
            'city' => 'Test Town',
            'district' => 'District',
            'country' => self::DE,
        ]);

        self::assertEquals(
            '12345 Test Town District, Deutschland',
            $this->fixture->getAddressAsSingleLine()
        );
    }

    /**
     * @test
     */
    public function getAddressAsSingleLineForShowAddressTrueReturnsCompleteAddressWithoutHtmlTags()
    {
        $this->fixture->loadRealtyObject([
            'show_address' => 1,
            'street' => 'Main Street',
            'zip' => '12345',
            'city' => 'Test Town',
            'district' => 'District',
            'country' => self::DE,
        ]);

        self::assertNotContains(
            '<',
            $this->fixture->getAddressAsSingleLine()
        );
    }

    /////////////////////////////
    // Tests for isAllowedKey()
    /////////////////////////////

    /**
     * @test
     */
    public function isAllowedKeyReturnsTrueForRealtyObjectField()
    {
        self::assertTrue(
            $this->fixture->isAllowedKey('title')
        );
    }

    /**
     * @test
     */
    public function isAllowedKeyReturnsFalseForNonRealtyObjectField()
    {
        self::assertFalse(
            $this->fixture->isAllowedKey('foo')
        );
    }

    /**
     * @test
     */
    public function isAllowedKeyReturnsFalseForEmptyKey()
    {
        self::assertFalse(
            $this->fixture->isAllowedKey('')
        );
    }

    //////////////////////////////
    // Tests concerning getOwner
    //////////////////////////////

    /**
     * @test
     */
    public function getOwnerForObjectWithOwnerReturnsFrontEndUserModel()
    {
        $this->fixture->loadRealtyObject(
            [
                'owner' => $this->testingFramework->createFrontEndUser(),
            ]
        );

        self::assertInstanceOf(
            tx_realty_Model_FrontEndUser::class,
            $this->fixture->getOwner()
        );
    }

    ////////////////////////////////////////////
    // Tests concerning the owner data getters
    ////////////////////////////////////////////

    /*
     * Tests concerning getContactName
     */

    /**
     * @test
     */
    public function getContactNameForOwnerFromFeUserWithNameReturnsOwnerName()
    {
        $this->loadRealtyObjectAndSetOwner(
            tx_realty_Model_RealtyObject::CONTACT_DATA_FROM_OWNER_ACCOUNT,
            ['name' => 'foo']
        );

        self::assertEquals(
            'foo',
            $this->fixture->getContactName()
        );
    }

    /**
     * @return array[]
     */
    public function contactPersonDataProvider()
    {
        return [
            'everything empty' => ['', '', '', ''],
            'only name' => ['Marissa Mayer', '', '', 'Marissa Mayer'],
            'first and last name' => ['Malcovich', 'John', '', 'John Malcovich'],
            'salutation and last name' => ['Malcovich', '', 'Mr.', 'Mr. Malcovich'],
            'salutation, first name and last name' => ['Malcovich', 'John', 'Mr.', 'Mr. John Malcovich'],
        ];
    }

    /**
     * @test
     *
     * @dataProvider contactPersonDataProvider
     *
     * @param string $name
     * @param string $firstName
     * @param string $salutation
     * @param string $fullName
     */
    public function getContactNameForOwnerFromObjectWithNameReturnsOwnerName($name, $firstName, $salutation, $fullName)
    {
        $this->loadRealtyObjectAndSetOwner(
            tx_realty_Model_RealtyObject::CONTACT_DATA_FROM_REALTY_OBJECT,
            [],
            [
                'contact_person' => $name,
                'contact_person_first_name' => $firstName,
                'contact_person_salutation' => $salutation,
            ]
        );

        self::assertSame(
            $fullName,
            $this->fixture->getContactName()
        );
    }

    ////////////////////////////////////////////
    // Tests concerning getContactEMailAddress
    ////////////////////////////////////////////

    /**
     * @test
     */
    public function getContactEMailAddressForOwnerFromFeUserAndWithoutEMailAddressReturnsEmptyString()
    {
        $this->loadRealtyObjectAndSetOwner(tx_realty_Model_RealtyObject::CONTACT_DATA_FROM_OWNER_ACCOUNT);

        self::assertEquals(
            '',
            $this->fixture->getContactEMailAddress()
        );
    }

    /**
     * @test
     */
    public function getContactEMailAddressForOwnerFromObjectAndWithoutEMailAddressReturnsEmptyString()
    {
        $this->loadRealtyObjectAndSetOwner(tx_realty_Model_RealtyObject::CONTACT_DATA_FROM_REALTY_OBJECT);

        self::assertEquals(
            '',
            $this->fixture->getContactEMailAddress()
        );
    }

    /**
     * @test
     */
    public function getContactEMailAddressForOwnerFromFeUserWithEMailAddressReturnsEMailAddress()
    {
        $this->loadRealtyObjectAndSetOwner(
            tx_realty_Model_RealtyObject::CONTACT_DATA_FROM_OWNER_ACCOUNT,
            ['email' => 'foo@example.com']
        );

        self::assertEquals(
            'foo@example.com',
            $this->fixture->getContactEMailAddress()
        );
    }

    /**
     * @test
     */
    public function getContactEMailAddressForOwnerFromObjectWithContactEMailAddressReturnsContactEMailAddress()
    {
        $this->loadRealtyObjectAndSetOwner(
            tx_realty_Model_RealtyObject::CONTACT_DATA_FROM_REALTY_OBJECT,
            [],
            ['contact_email' => 'bar@example.com']
        );

        self::assertEquals(
            'bar@example.com',
            $this->fixture->getContactEMailAddress()
        );
    }

    ////////////////////////////////////
    // Tests concerning getContactCity
    ////////////////////////////////////

    /**
     * @test
     */
    public function getContactCityForOwnerFromFeUserAndWithoutCityReturnsEmptyString()
    {
        $this->loadRealtyObjectAndSetOwner(tx_realty_Model_RealtyObject::CONTACT_DATA_FROM_OWNER_ACCOUNT);

        self::assertEquals(
            '',
            $this->fixture->getContactCity()
        );
    }

    /**
     * @test
     */
    public function getContactCityForOwnerFromObjectReturnsEmptyString()
    {
        $this->loadRealtyObjectAndSetOwner(tx_realty_Model_RealtyObject::CONTACT_DATA_FROM_REALTY_OBJECT);

        self::assertEquals(
            '',
            $this->fixture->getContactCity()
        );
    }

    /**
     * @test
     */
    public function getContactCityForOwnerFromFeUserWithCityReturnsCity()
    {
        $this->loadRealtyObjectAndSetOwner(
            tx_realty_Model_RealtyObject::CONTACT_DATA_FROM_OWNER_ACCOUNT,
            ['city' => 'footown']
        );

        self::assertEquals(
            'footown',
            $this->fixture->getContactCity()
        );
    }

    //////////////////////////////////////
    // Tests concerning getContactStreet
    //////////////////////////////////////

    /**
     * @test
     */
    public function getContactStreetForOwnerFromFeUserAndWithoutStreetReturnsEmptyString()
    {
        $this->loadRealtyObjectAndSetOwner(tx_realty_Model_RealtyObject::CONTACT_DATA_FROM_OWNER_ACCOUNT);

        self::assertEquals(
            '',
            $this->fixture->getContactStreet()
        );
    }

    /**
     * @test
     */
    public function getContactStreetForOwnerFromObjectReturnsEmptyString()
    {
        $this->loadRealtyObjectAndSetOwner(tx_realty_Model_RealtyObject::CONTACT_DATA_FROM_REALTY_OBJECT);

        self::assertEquals(
            '',
            $this->fixture->getContactStreet()
        );
    }

    /**
     * @test
     */
    public function getContactStreetForOwnerFromFeUserWithStreetReturnsStreet()
    {
        $this->loadRealtyObjectAndSetOwner(
            tx_realty_Model_RealtyObject::CONTACT_DATA_FROM_OWNER_ACCOUNT,
            ['address' => 'foo']
        );

        self::assertEquals(
            'foo',
            $this->fixture->getContactStreet()
        );
    }

    ///////////////////////////////////
    // Tests concerning getContactZip
    ///////////////////////////////////

    /**
     * @test
     */
    public function getContactZipForOwnerFromFeUserAndWithoutZipReturnsEmptyString()
    {
        $this->loadRealtyObjectAndSetOwner(tx_realty_Model_RealtyObject::CONTACT_DATA_FROM_OWNER_ACCOUNT);

        self::assertEquals(
            '',
            $this->fixture->getContactZip()
        );
    }

    /**
     * @test
     */
    public function getContactZipForOwnerFromObjectReturnsEmptyString()
    {
        $this->loadRealtyObjectAndSetOwner(tx_realty_Model_RealtyObject::CONTACT_DATA_FROM_REALTY_OBJECT);

        self::assertEquals(
            '',
            $this->fixture->getContactZip()
        );
    }

    /**
     * @test
     */
    public function getContactZipForOwnerFromFeUserWithZipReturnsZip()
    {
        $this->loadRealtyObjectAndSetOwner(
            tx_realty_Model_RealtyObject::CONTACT_DATA_FROM_OWNER_ACCOUNT,
            ['zip' => '12345']
        );

        self::assertEquals(
            '12345',
            $this->fixture->getContactZip()
        );
    }

    ////////////////////////////////////////
    // Tests concerning getContactHomepage
    ////////////////////////////////////////

    /**
     * @test
     */
    public function getContactHomepageForOwnerFromFeUserAndWithoutHomepageReturnsEmptyString()
    {
        $this->loadRealtyObjectAndSetOwner(tx_realty_Model_RealtyObject::CONTACT_DATA_FROM_OWNER_ACCOUNT);

        self::assertEquals(
            '',
            $this->fixture->getContactHomepage()
        );
    }

    /**
     * @test
     */
    public function getContactHomepageForOwnerFromObjectReturnsEmptyString()
    {
        $this->loadRealtyObjectAndSetOwner(tx_realty_Model_RealtyObject::CONTACT_DATA_FROM_REALTY_OBJECT);

        self::assertEquals(
            '',
            $this->fixture->getContactHomepage()
        );
    }

    /**
     * @test
     */
    public function getContactHomepageForOwnerFromFeUserWithHomepageReturnsHomepage()
    {
        $this->loadRealtyObjectAndSetOwner(
            tx_realty_Model_RealtyObject::CONTACT_DATA_FROM_OWNER_ACCOUNT,
            ['www' => 'www.foo.de']
        );

        self::assertEquals(
            'www.foo.de',
            $this->fixture->getContactHomepage()
        );
    }

    ///////////////////////////////////////////
    // Tests concerning getContactPhoneNumber
    ///////////////////////////////////////////

    /**
     * @test
     */
    public function getContactPhoneNumberForOwnerFromFeUserAndWithoutPhoneNumberReturnsEmptyString()
    {
        $this->loadRealtyObjectAndSetOwner(tx_realty_Model_RealtyObject::CONTACT_DATA_FROM_OWNER_ACCOUNT);

        self::assertEquals(
            '',
            $this->fixture->getContactPhoneNumber()
        );
    }

    /**
     * @test
     */
    public function getContactPhoneNumberForOwnerFromObjectAndWithoutPhoneNumberReturnsEmptyString()
    {
        $this->loadRealtyObjectAndSetOwner(tx_realty_Model_RealtyObject::CONTACT_DATA_FROM_REALTY_OBJECT);

        self::assertEquals(
            '',
            $this->fixture->getContactPhoneNumber()
        );
    }

    /**
     * @test
     */
    public function getContactPhoneNumberForOwnerFromFeUserWithPhoneNumberReturnsPhoneNumber()
    {
        $this->loadRealtyObjectAndSetOwner(
            tx_realty_Model_RealtyObject::CONTACT_DATA_FROM_OWNER_ACCOUNT,
            ['telephone' => '555-123456']
        );

        self::assertEquals(
            '555-123456',
            $this->fixture->getContactPhoneNumber()
        );
    }

    /**
     * @test
     */
    public function getContactPhoneNumberForOwnerFromObjectWithDirectExtensionPhoneNumberReturnsThisNumber()
    {
        $this->loadRealtyObjectAndSetOwner(
            tx_realty_Model_RealtyObject::CONTACT_DATA_FROM_REALTY_OBJECT,
            [],
            ['phone_direct_extension' => '555-123456']
        );

        self::assertEquals(
            '555-123456',
            $this->fixture->getContactPhoneNumber()
        );
    }

    /**
     * @test
     */
    public function getContactPhoneNumberForOwnerFromObjectWithSwitchboardAndWithoutDirectExtensionPhoneNumberReturnsSwitchboardNumber(
    ) {
        $this->loadRealtyObjectAndSetOwner(
            tx_realty_Model_RealtyObject::CONTACT_DATA_FROM_REALTY_OBJECT,
            [],
            ['phone_switchboard' => '555-123456']
        );

        self::assertEquals(
            '555-123456',
            $this->fixture->getContactPhoneNumber()
        );
    }

    /**
     * @test
     */
    public function getContactPhoneNumberForOwnerFromObjectWithSwitchboardAndDirectExtensionPhoneNumberReturnsDirectExtensionNumber(
    ) {
        $this->loadRealtyObjectAndSetOwner(
            tx_realty_Model_RealtyObject::CONTACT_DATA_FROM_REALTY_OBJECT,
            [],
            [
                'phone_switchboard' => '123456',
                'phone_direct_extension' => '654321',
            ]
        );

        self::assertEquals(
            '654321',
            $this->fixture->getContactPhoneNumber()
        );
    }

    ///////////////////////////////////////////
    // Tests concerning getContactSwitchboard
    ///////////////////////////////////////////

    /**
     * @test
     */
    public function getContactSwitchboardForNoSwitchboardSetReturnsEmptyString()
    {
        $this->loadRealtyObjectAndSetOwner(
            tx_realty_Model_RealtyObject::CONTACT_DATA_FROM_REALTY_OBJECT,
            [],
            []
        );

        self::assertEquals(
            '',
            $this->fixture->getContactSwitchboard()
        );
    }

    /**
     * @test
     */
    public function getContactSwitchboardForSwitchboardSetReturnsSwitchboardNumber()
    {
        $this->loadRealtyObjectAndSetOwner(
            tx_realty_Model_RealtyObject::CONTACT_DATA_FROM_OWNER_ACCOUNT,
            [],
            ['phone_switchboard' => '555-123456']
        );

        self::assertEquals(
            '555-123456',
            $this->fixture->getContactSwitchboard()
        );
    }

    ///////////////////////////////////////////////
    // Tests concerning getContactDirectExtension
    ///////////////////////////////////////////////

    /**
     * @test
     */
    public function getContactDirectExtensionForNoDirectExtensionSetReturnsEmptyString()
    {
        $this->loadRealtyObjectAndSetOwner(
            tx_realty_Model_RealtyObject::CONTACT_DATA_FROM_REALTY_OBJECT,
            [],
            []
        );

        self::assertEquals(
            '',
            $this->fixture->getContactDirectExtension()
        );
    }

    /**
     * @test
     */
    public function getContactDirectExtensionForDirectExtensionSetReturnsDirectExtensionNumber()
    {
        $this->loadRealtyObjectAndSetOwner(
            tx_realty_Model_RealtyObject::CONTACT_DATA_FROM_OWNER_ACCOUNT,
            [],
            ['phone_direct_extension' => '555-123456']
        );

        self::assertEquals(
            '555-123456',
            $this->fixture->getContactDirectExtension()
        );
    }

    ////////////////////////////////
    // Tests concerning the status
    ////////////////////////////////

    /**
     * @test
     */
    public function getStatusForNoStatusSetReturnsVacant()
    {
        $this->loadRealtyObjectAndSetOwner(
            tx_realty_Model_RealtyObject::CONTACT_DATA_FROM_OWNER_ACCOUNT,
            [],
            []
        );

        self::assertEquals(
            tx_realty_Model_RealtyObject::STATUS_VACANT,
            $this->fixture->getStatus()
        );
    }

    /**
     * @test
     */
    public function getStatusForStatusSetReturnsStatus()
    {
        $this->loadRealtyObjectAndSetOwner(
            tx_realty_Model_RealtyObject::CONTACT_DATA_FROM_OWNER_ACCOUNT,
            [],
            ['status' => tx_realty_Model_RealtyObject::STATUS_RENTED]
        );

        self::assertEquals(
            tx_realty_Model_RealtyObject::STATUS_RENTED,
            $this->fixture->getStatus()
        );
    }

    /**
     * @test
     */
    public function isRentedOrSoldForStatusVacantReturnsFalse()
    {
        $this->loadRealtyObjectAndSetOwner(
            tx_realty_Model_RealtyObject::CONTACT_DATA_FROM_OWNER_ACCOUNT,
            [],
            ['status' => tx_realty_Model_RealtyObject::STATUS_VACANT]
        );

        self::assertFalse(
            $this->fixture->isRentedOrSold()
        );
    }

    /**
     * @test
     */
    public function isRentedOrSoldForStatusReservedReturnsFalse()
    {
        $this->loadRealtyObjectAndSetOwner(
            tx_realty_Model_RealtyObject::CONTACT_DATA_FROM_OWNER_ACCOUNT,
            [],
            ['status' => tx_realty_Model_RealtyObject::STATUS_RESERVED]
        );

        self::assertFalse(
            $this->fixture->isRentedOrSold()
        );
    }

    /**
     * @test
     */
    public function isRentedOrSoldForStatusSoldReturnsTrue()
    {
        $this->loadRealtyObjectAndSetOwner(
            tx_realty_Model_RealtyObject::CONTACT_DATA_FROM_OWNER_ACCOUNT,
            [],
            ['status' => tx_realty_Model_RealtyObject::STATUS_SOLD]
        );

        self::assertTrue(
            $this->fixture->isRentedOrSold()
        );
    }

    /**
     * @test
     */
    public function isRentedOrSoldForStatusRentedReturnsTrue()
    {
        $this->loadRealtyObjectAndSetOwner(
            tx_realty_Model_RealtyObject::CONTACT_DATA_FROM_OWNER_ACCOUNT,
            [],
            ['status' => tx_realty_Model_RealtyObject::STATUS_RENTED]
        );

        self::assertTrue(
            $this->fixture->isRentedOrSold()
        );
    }

    /*
     * Tests concerning the address
     */

    /**
     * @test
     */
    public function getStreetForEmptyStreetReturnsEmptyString()
    {
        $this->fixture->setData([]);

        self::assertEquals(
            '',
            $this->fixture->getStreet()
        );
    }

    /**
     * @test
     */
    public function getStreetForNonEmptyStreetReturnsStreet()
    {
        $this->fixture->setData(['street' => 'foo']);

        self::assertSame(
            'foo',
            $this->fixture->getStreet()
        );
    }

    /**
     * @test
     */
    public function hasStreetForEmptyStreetReturnsFalse()
    {
        $this->fixture->setData(['street' => '']);

        self::assertFalse(
            $this->fixture->hasStreet()
        );
    }

    /**
     * @test
     */
    public function hasStreetForNonEmptyStreetReturnsTrue()
    {
        $this->fixture->setData(['street' => 'foo']);

        self::assertTrue(
            $this->fixture->hasStreet()
        );
    }

    /**
     * @test
     */
    public function getZipForEmptyZipReturnsEmptyString()
    {
        $this->fixture->setData([]);

        self::assertSame(
            '',
            $this->fixture->getZip()
        );
    }

    /**
     * @test
     */
    public function setStreetSetsStreet()
    {
        $this->fixture->setData([]);
        $this->fixture->setStreet('bar');

        self::assertSame(
            'bar',
            $this->fixture->getStreet()
        );
    }

    /**
     * @test
     */
    public function getZipForNonEmptyZipReturnsZip()
    {
        $this->fixture->setData(['zip' => '12345']);

        self::assertSame(
            '12345',
            $this->fixture->getZip()
        );
    }

    /**
     * @test
     */
    public function setZipSetsZip()
    {
        $this->fixture->setData([]);
        $zip = '16432';
        $this->fixture->setZip($zip);

        self::assertSame(
            $zip,
            $this->fixture->getZip()
        );
    }

    /**
     * @test
     */
    public function hasZipForEmptyZipReturnsFalse()
    {
        $this->fixture->setData(['zip' => '']);

        self::assertFalse(
            $this->fixture->hasZip()
        );
    }

    /**
     * @test
     */
    public function hasZipForNonEmptyZipReturnsTrue()
    {
        $this->fixture->setData(['zip' => '12345']);

        self::assertTrue(
            $this->fixture->hasZip()
        );
    }

    /**
     * @test
     */
    public function getCityForNoCityReturnsNull()
    {
        $this->fixture->setData([]);

        self::assertNull(
            $this->fixture->getCity()
        );
    }

    /**
     * @test
     */
    public function getCityForExistingCityReturnsCity()
    {
        $cityUid = $this->testingFramework->createRecord(
            'tx_realty_cities',
            ['title' => 'Berlin']
        );
        $this->fixture->setData(['city' => $cityUid]);
        /** @var tx_realty_Mapper_City $mapper */
        $mapper = Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_City::class);
        /** @var tx_realty_Model_City $city */
        $city = $mapper->find($cityUid);

        self::assertSame(
            $city,
            $this->fixture->getCity()
        );
    }

    /**
     * @test
     */
    public function hasCityForNoCityReturnsFalse()
    {
        $this->fixture->setData([]);

        self::assertFalse(
            $this->fixture->hasCity()
        );
    }

    /**
     * @test
     */
    public function hasCityForExistingCityReturnsTrue()
    {
        $cityUid = $this->testingFramework->createRecord(
            'tx_realty_cities',
            ['title' => 'Berlin']
        );
        $this->fixture->setData(['city' => $cityUid]);

        self::assertTrue(
            $this->fixture->hasCity()
        );
    }

    /**
     * @test
     */
    public function getCountryForNoCountryReturnsNull()
    {
        $this->fixture->setData([]);

        self::assertNull(
            $this->fixture->getCountry()
        );
    }

    /**
     * @test
     */
    public function getCountryForExistingCountryReturnsCountry()
    {
        $this->fixture->setData(['country' => self::DE]);
        /** @var Tx_Oelib_Mapper_Country $mapper */
        $mapper = Tx_Oelib_MapperRegistry::get(Tx_Oelib_Mapper_Country::class);
        /** @var Tx_Oelib_Model_Country $country */
        $country = $mapper->find(self::DE);

        self::assertSame(
            $country,
            $this->fixture->getCountry()
        );
    }

    /**
     * @test
     */
    public function hasCountryForNoCountryReturnsFalse()
    {
        $this->fixture->setData([]);

        self::assertFalse(
            $this->fixture->hasCountry()
        );
    }

    /**
     * @test
     */
    public function hasCountryForExistingCountryReturnsTrue()
    {
        $this->fixture->setData(['country' => self::DE]);

        self::assertTrue(
            $this->fixture->hasCountry()
        );
    }

    /*
     * Tests concerning the geo coordinates
    /*

    /**
     * @test
     */
    public function getGeoCoordinatesForHasCoordinatesReturnsLatitudeAndLongitude()
    {
        $this->fixture->setData(
            [
                'latitude' => -42.7,
                'longitude' => 42.0,
                'has_coordinates' => true,
            ]
        );

        self::assertSame(
            [
                'latitude' => -42.7,
                'longitude' => 42.0,
            ],
            $this->fixture->getGeoCoordinates()
        );
    }

    /**
     * @test
     */
    public function getGeoCoordinatesForNotHasCoordinatesReturnsEmptyArray()
    {
        $this->fixture->setData(
            [
                'latitude' => -42.7,
                'longitude' => 42.0,
                'has_coordinates' => false,
            ]
        );

        self::assertSame(
            [],
            $this->fixture->getGeoCoordinates()
        );
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function setGeoCoordinatesWithoutLatitudeThrowsException()
    {
        $this->fixture->setGeoCoordinates(['longitude' => 42.0]);
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function setGeoCoordinatesWithoutLongitudeThrowsException()
    {
        $this->fixture->setGeoCoordinates(['latitude' => -42.7]);
    }

    /**
     * @test
     */
    public function setGeoCoordinatesSetsCoordinates()
    {
        $this->fixture->setData([]);

        $this->fixture->setGeoCoordinates(
            [
                'latitude' => -42.7,
                'longitude' => 42.0,
            ]
        );

        self::assertSame(
            [
                'latitude' => -42.7,
                'longitude' => 42.0,
            ],
            $this->fixture->getGeoCoordinates()
        );
    }

    /**
     * @test
     */
    public function setGeoCoordinatesSetsHasCoordinatesToTrue()
    {
        $this->fixture->setData(['has_coordinates' => false]);

        $this->fixture->setGeoCoordinates(
            [
                'latitude' => -42.7,
                'longitude' => 42.0,
            ]
        );

        self::assertTrue(
            $this->fixture->hasGeoCoordinates()
        );
    }

    /**
     * @test
     */
    public function setGeoCoordinatesSetsHasGeoErrorToFalse()
    {
        $this->fixture->setData(['coordinates_problem' => true]);

        $this->fixture->setGeoCoordinates(
            [
                'latitude' => -42.7,
                'longitude' => 42.0,
            ]
        );

        self::assertFalse(
            $this->fixture->hasGeoError()
        );
    }

    /**
     * @test
     */
    public function hasGeoCoordinatesForHasCoordinatesTrueReturnsTrue()
    {
        $this->fixture->setData(['has_coordinates' => true]);

        self::assertTrue(
            $this->fixture->hasGeoCoordinates()
        );
    }

    /**
     * @test
     */
    public function hasGeoCoordinatesForHasCoordinatesFalseReturnsFalse()
    {
        $this->fixture->setData(['has_coordinates' => false]);

        self::assertFalse(
            $this->fixture->hasGeoCoordinates()
        );
    }

    /**
     * @test
     */
    public function clearGeoCoordinatesSetsHasCoordinatesToFalse()
    {
        $this->fixture->setData(['has_coordinates' => true]);

        $this->fixture->clearGeoCoordinates();

        self::assertFalse(
            $this->fixture->hasGeoCoordinates()
        );
    }

    /**
     * @test
     */
    public function hasGeoErrorForProblemTrueReturnsTrue()
    {
        $this->fixture->setData(['coordinates_problem' => true]);

        self::assertTrue(
            $this->fixture->hasGeoError()
        );
    }

    /**
     * @test
     */
    public function hasGeoErrorForProblemFalseReturnsFalse()
    {
        $this->fixture->setData(['coordinates_problem' => false]);

        self::assertFalse(
            $this->fixture->hasGeoError()
        );
    }

    /**
     * @test
     */
    public function setGeoErrorSetsGeoErrorToTrue()
    {
        $this->fixture->setData(['coordinates_problem' => false]);

        $this->fixture->setGeoError();

        self::assertTrue(
            $this->fixture->hasGeoError()
        );
    }

    /**
     * @test
     */
    public function clearGeoErrorSetsGeoErrorToFalse()
    {
        $this->fixture->setData(['coordinates_problem' => true]);

        $this->fixture->clearGeoError();

        self::assertFalse(
            $this->fixture->hasGeoError()
        );
    }

    /**
     * @test
     */
    public function getDistanceToTheSeaInitiallyReturnsZero()
    {
        $this->fixture->setData([]);

        self::assertSame(
            0,
            $this->fixture->getDistanceToTheSea()
        );
    }

    /**
     * @test
     */
    public function getDistanceToTheSeaReturnsDistanceToTheSea()
    {
        $distance = 42;

        $this->fixture->setData(['distance_to_the_sea' => $distance]);

        self::assertSame(
            $distance,
            $this->fixture->getDistanceToTheSea()
        );
    }

    /**
     * @test
     */
    public function hasDistanceToTheSeaForZeroReturnsFalse()
    {
        $this->fixture->setData(['distance_to_the_sea' => 0]);

        self::assertFalse(
            $this->fixture->hasDistanceToTheSea()
        );
    }

    /**
     * @test
     */
    public function hasDistanceToTheSeaForPositiveNumberReturnsTrue()
    {
        $this->fixture->setData(['distance_to_the_sea' => 9]);

        self::assertTrue(
            $this->fixture->hasDistanceToTheSea()
        );
    }

    /**
     * @test
     */
    public function setDistanceToTheSeaSetsDistanceToTheSea()
    {
        $distance = 9;

        $this->fixture->setData([]);
        $this->fixture->setDistanceToTheSea($distance);

        self::assertSame(
            $distance,
            $this->fixture->getDistanceToTheSea()
        );
    }

    /**
     * @test
     *
     * @expectedException \InvalidArgumentException
     */
    public function setDistanceToTheSeaWithNegativeNumberThrowsException()
    {
        $this->fixture->setData([]);
        $this->fixture->setDistanceToTheSea(-1);
    }
}
