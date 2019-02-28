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
    private $subject = null;

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

        $this->subject = new tx_realty_Model_RealtyObjectChild(true);

        $this->subject->setRequiredFields([]);
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
        if (in_array('tx_realty_images', $this->testingFramework->getListOfDirtyTables(), true)) {
            \Tx_Oelib_Db::delete(
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

        $this->subject->loadRealtyObject($objectData);
    }

    ///////////////////////////////
    // Testing the realty object.
    ///////////////////////////////

    /**
     * @test
     */
    public function getDataTypeWhenArrayGiven()
    {
        self::assertEquals(
            'array',
            $this->subject->getDataType(['foo'])
        );
    }

    /**
     * @test
     */
    public function loadRealtyObjectWithValidArraySetDataForGetProperty()
    {
        $this->subject->loadRealtyObject(['title' => 'foo']);

        self::assertEquals(
            'foo',
            $this->subject->getProperty('title')
        );
    }

    /**
     * @test
     */
    public function loadRealtyObjectFromAnArrayWithNonZeroUidIsAllowed()
    {
        $this->subject->loadRealtyObject(['uid' => 1234]);
    }

    /**
     * @test
     */
    public function loadRealtyObjectFromArrayWithZeroUidIsAllowed()
    {
        $this->subject->loadRealtyObject(['uid' => 0]);
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
        $this->subject->loadRealtyObject($this->objectUid, false);

        self::assertTrue(
            $this->subject->isEmpty()
        );
    }

    /**
     * @test
     */
    public function loadHiddenRealtyObjectIfHiddenObjectsAreAllowed()
    {
        $this->testingFramework->changeRecord(
            'tx_realty_objects',
            $this->objectUid,
            ['hidden' => 1]
        );
        $this->subject->loadRealtyObject($this->objectUid, true);

        self::assertFalse(
            $this->subject->isEmpty()
        );
    }

    /**
     * @test
     */
    public function createNewDatabaseEntryIfAValidArrayIsGiven()
    {
        $this->subject->createNewDatabaseEntry(
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
        $this->subject->createNewDatabaseEntry(['uid' => 1234]);
    }

    /**
     * @test
     *
     * @expectedException \InvalidArgumentException
     */
    public function createNewDatabaseEntryForArrayWithZeroUidThrowsException()
    {
        $this->subject->createNewDatabaseEntry(['uid' => 0]);
    }

    /**
     * @test
     */
    public function getDataTypeWhenIntegerGiven()
    {
        self::assertEquals(
            'uid',
            $this->subject->getDataType(1)
        );
    }

    /**
     * @test
     */
    public function setDataSetsTheRealtyObjectsTitle()
    {
        $this->subject->setData(['title' => 'foo']);

        self::assertEquals(
            'foo',
            $this->subject->getTitle()
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
        $this->subject->setData([]);
        self::assertSame(
            '',
            $this->subject->getTitle()
        );
    }

    /**
     * @test
     */
    public function getTitleReturnsTitle()
    {
        $title = 'A very nice house indeed.';
        $this->subject->setData(['title' => $title]);

        self::assertSame(
            $title,
            $this->subject->getTitle()
        );
    }

    /**
     * @test
     */
    public function setTitleSetsTitle()
    {
        $this->subject->setData([]);
        $this->subject->setTitle('foo bar');

        self::assertSame(
            'foo bar',
            $this->subject->getTitle()
        );
    }

    /**
     * @test
     */
    public function setTitleCanSetEmptyTitle()
    {
        $this->subject->setData([]);
        $this->subject->setTitle('');

        self::assertSame(
            '',
            $this->subject->getTitle()
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
        $this->subject->loadRealtyObject($this->objectUid);

        /** @var tx_realty_Model_Image $firstImage */
        $firstImage = $this->subject->getImages()->first();
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
        $this->subject->setData(['uid' => $this->objectUid, 'images' => 1]);

        /** @var tx_realty_Model_Image $firstImage */
        $firstImage = $this->subject->getImages()->first();
        self::assertEquals(
            'foo',
            $firstImage->getTitle()
        );
    }

    /**
     * @test
     */
    public function setDataSetsTheImageDataForImageFromArray()
    {
        $this->subject->setData(
            [
                'object_number' => self::$otherObjectNumber,
                'images' => [
                    ['caption' => 'test', 'image' => 'test.jpg'],
                ],
            ]
        );

        /** @var tx_realty_Model_Image $firstImage */
        $firstImage = $this->subject->getImages()->first();
        self::assertEquals(
            'test',
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
        $this->subject->loadRealtyObject($this->objectUid);

        $titles = [];
        foreach ($this->subject->getImages() as $image) {
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
        $this->subject->loadRealtyObject($this->objectUid);

        /** @var tx_realty_Model_Image $firstImage */
        $firstImage = $this->subject->getImages()->first();
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
        $this->subject->loadRealtyObject($this->objectUid);

        /** @var tx_realty_Model_Image $firstImage */
        $firstImage = $this->subject->getImages()->first();
        self::assertSame(
            'jpg',
            $firstImage->getTitle()
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
        $this->subject->loadRealtyObject($this->objectUid);
        $this->subject->setProperty('title', 'new title');
        $message = $this->subject->writeToDatabase();

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
        $this->subject->loadRealtyObject(
            [
                'title' => 'new title',
                'object_number' => self::$objectNumber,
                'language' => 'foo',
                'openimmo_obid' => 'test-obid',
            ]
        );
        $this->subject->writeToDatabase();

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
        $this->subject->loadRealtyObject(
            [
                'title' => 'new title',
                'object_number' => self::$objectNumber,
                'language' => 'bar',
                'openimmo_obid' => 'test-obid',
            ]
        );
        $this->subject->writeToDatabase();

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
        $this->subject->loadRealtyObject(
            [
                'title' => 'new title',
                'object_number' => self::$objectNumber,
                'language' => 'foo',
                'openimmo_obid' => 'another-test-obid',
            ]
        );
        $this->subject->writeToDatabase();

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
        $this->subject->loadRealtyObject(
            [
                'title' => 'new title',
                'object_number' => self::$objectNumber,
                'language' => '',
                'openimmo_obid' => 'test-obid',
            ]
        );
        $this->subject->writeToDatabase();

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
        $this->subject->loadRealtyObject(
            [
                'title' => 'new title',
                'object_number' => self::$objectNumber,
            ]
        );
        $message = $this->subject->writeToDatabase();

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
        $this->subject->loadRealtyObject(
            [
                'title' => 'new title',
                'object_number' => self::$objectNumber,
                'openimmo_obid' => 'test-obid',
            ]
        );
        $message = $this->subject->writeToDatabase();

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
        $this->subject->loadRealtyObject(
            [
                'title' => 'new title',
                'object_number' => self::$objectNumber,
                'language' => 'foo',
            ]
        );
        $message = $this->subject->writeToDatabase();

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
        $this->subject->loadRealtyObject(
            [
                'title' => 'this is a title',
                'object_number' => self::$objectNumber,
                'language' => 'bar',
            ]
        );
        $message = $this->subject->writeToDatabase();

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
        $this->subject->loadRealtyObject(
            [
                'title' => 'this is a title',
                'object_number' => self::$objectNumber,
                'openimmo_obid' => 'another-test-obid',
            ]
        );
        $message = $this->subject->writeToDatabase();

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
        $this->subject->loadRealtyObject(
            [
                'title' => 'this is a title',
                'object_number' => self::$objectNumber,
                'openimmo_obid' => 'another-test-obid',
            ]
        );
        $message = $this->subject->writeToDatabase();

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
        $this->subject->loadRealtyObject(
            [
                'title' => 'new title',
                'object_number' => self::$otherObjectNumber,
                'openimmo_obid' => 'test-obid',
                'language' => 'foo',
            ]
        );
        $this->subject->writeToDatabase();

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
        $this->subject->setRequiredFields(['city']);
        $this->subject->loadRealtyObject(
            [
                'object_number' => self::$otherObjectNumber,
                'title' => 'new entry',
            ]
        );

        self::assertEquals(
            'message_fields_required',
            $this->subject->writeToDatabase()
        );
    }

    /**
     * @test
     */
    public function writeToDatabaseReturnsObjectNotLoadedMessageIfTheCurrentObjectIsEmpty()
    {
        $this->subject->loadRealtyObject([]);

        self::assertEquals(
            'message_object_not_loaded',
            $this->subject->writeToDatabase()
        );
    }

    /**
     * @test
     */
    public function writeToDatabaseCreatesNewDatabaseEntry()
    {
        $this->subject->loadRealtyObject(
            ['object_number' => self::$otherObjectNumber]
        );
        $this->subject->writeToDatabase();

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
        $this->subject->loadRealtyObject(
            [
                'object_number' => '"' . self::$otherObjectNumber . '"',
                'openimmo_obid' => '"foo"',
                'title' => '"bar"',
            ]
        );
        $this->subject->writeToDatabase();

        self::assertEquals(
            1,
            $this->testingFramework->countRecords(
                'tx_realty_objects',
                'uid=' . $this->subject->getUid()
            )
        );
    }

    /**
     * @test
     */
    public function writeToDatabaseCreatesNewRealtyRecordWithRealtyRecordPid()
    {
        $this->subject->loadRealtyObject(
            ['object_number' => self::$otherObjectNumber]
        );
        $this->subject->writeToDatabase();

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

        $this->subject->loadRealtyObject(
            ['object_number' => self::$otherObjectNumber]
        );
        $this->subject->writeToDatabase($systemFolderPid);

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
        $this->subject->loadRealtyObject(
            ['object_number' => self::$objectNumber]
        );
        $this->subject->writeToDatabase($systemFolderPid);

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

        $this->subject->loadRealtyObject($this->objectUid);
        $this->subject->setProperty('city', 'foo');
        $this->subject->writeToDatabase();

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

        $this->subject->loadRealtyObject($this->objectUid);
        $this->subject->setProperty('city', 'foo');
        $this->subject->writeToDatabase();

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
     *
     * @doesNotPerformAssertions
     */
    public function writeToDataBaseAfterSetDataWithAttachedFilesThrowsNoException()
    {
        $this->subject->setData(
            [
                'title' => 'new title',
                'object_number' => self::$otherObjectNumber,
                'openimmo_obid' => 'test-obid',
                'attached_files' => [['title' => 'nice image', 'path' => '/tmp/image.jpg']],
            ]
        );
        $this->subject->writeToDatabase();
    }

    /**
     * @test
     */
    public function getPropertyWithNonExistingKeyWhenObjectLoaded()
    {
        $this->subject->loadRealtyObject($this->objectUid);

        self::assertEquals(
            '',
            $this->subject->getProperty('foo')
        );
    }

    /**
     * @test
     */
    public function getPropertyWithExistingKeyWhenObjectLoaded()
    {
        $this->subject->loadRealtyObject($this->objectUid);
        $this->subject->set('city', 'foo');

        self::assertEquals(
            'foo',
            $this->subject->getProperty('city')
        );
    }

    /**
     * @test
     */
    public function setPropertyWhenKeyExists()
    {
        $this->subject->loadRealtyObject($this->objectUid);
        $this->subject->setProperty('city', 'foo');

        self::assertEquals(
            'foo',
            $this->subject->getProperty('city')
        );
    }

    /**
     * @test
     */
    public function setPropertyWhenValueOfBoolean()
    {
        $this->subject->loadRealtyObject($this->objectUid);
        $this->subject->setProperty('pets', true);

        self::assertTrue(
            $this->subject->getProperty('pets')
        );
    }

    /**
     * @test
     */
    public function setPropertyWhenValueIsNumber()
    {
        $this->subject->loadRealtyObject($this->objectUid);
        $this->subject->setProperty('zip', 100);

        self::assertEquals(
            100,
            $this->subject->getProperty('zip')
        );
    }

    /**
     * @test
     */
    public function setPropertyWhenKeyNotExists()
    {
        $this->subject->loadRealtyObject($this->objectUid);
        $this->subject->setProperty('foo', 'bar');

        self::assertEquals(
            '',
            $this->subject->getProperty('foo')
        );
    }

    /**
     * @test
     */
    public function setPropertyDoesNotSetTheValueWhenTheValuesTypeIsInvalid()
    {
        $this->subject->loadRealtyObject($this->objectUid);
        $this->subject->setProperty('pets', ['bar']);

        self::assertEquals(
            $this->objectUid,
            $this->subject->getUid()
        );
    }

    /**
     * @test
     *
     * @expectedException \InvalidArgumentException
     */
    public function setPropertyKeySetToUidThrowsException()
    {
        $this->subject->loadRealtyObject($this->objectUid);

        $this->subject->setProperty('uid', 12345);
    }

    /**
     * @test
     */
    public function isEmptyWithObjectLoadedReturnsFalse()
    {
        $this->subject->loadRealtyObject($this->objectUid);
        self::assertFalse(
            $this->subject->isEmpty()
        );
    }

    /**
     * @test
     */
    public function isEmptyWithNoObjectLoadedReturnsTrue()
    {
        self::assertTrue(
            $this->subject->isEmpty()
        );
    }

    /**
     * @test
     */
    public function checkForRequiredFieldsIfNoFieldsAreRequired()
    {
        $this->subject->loadRealtyObject($this->objectUid);

        self::assertEquals(
            [],
            $this->subject->checkForRequiredFields()
        );
    }

    /**
     * @test
     */
    public function checkForRequiredFieldsIfAllFieldsAreSet()
    {
        $this->subject->loadRealtyObject($this->objectUid);
        $this->subject->setRequiredFields(
            [
                'title',
                'object_number',
            ]
        );

        self::assertEquals(
            [],
            $this->subject->checkForRequiredFields()
        );
    }

    /**
     * @test
     */
    public function checkForRequiredFieldsIfOneRequriredFieldIsMissing()
    {
        $this->subject->loadRealtyObject(['title' => 'foo']);
        $this->subject->setRequiredFields(['object_number']);

        self::assertContains(
            'object_number',
            $this->subject->checkForRequiredFields()
        );
    }

    /**
     * @test
     */
    public function prepareInsertionAndInsertRelationsWritesUidOfInsertedPropertyToRealtyObjectData()
    {
        $this->testingFramework->markTableAsDirty('tx_realty_cities');

        $this->subject->loadRealtyObject($this->objectUid);
        $this->subject->setProperty('city', 'foo');
        $this->subject->prepareInsertionAndInsertRelations();

        self::assertTrue(
            $this->subject->getProperty('city') > 0
        );
    }

    /**
     * @test
     */
    public function prepareInsertionAndInsertRelationsInsertsPropertyIntoItsTable()
    {
        $this->testingFramework->markTableAsDirty('tx_realty_cities');

        $this->subject->loadRealtyObject($this->objectUid);
        $this->subject->setProperty('city', 'foo');
        $this->subject->prepareInsertionAndInsertRelations();

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

        $this->subject->loadRealtyObject($this->objectUid);
        $this->subject->setProperty('city', 'foo "bar"');
        $this->subject->prepareInsertionAndInsertRelations();

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

        $this->subject->loadRealtyObject($this->objectUid);
        $this->subject->setProperty('city', 'test city');
        $this->subject->prepareInsertionAndInsertRelations();

        self::assertEquals(
            $cityUid,
            $this->subject->getProperty('city')
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

        $this->subject->loadRealtyObject($this->objectUid);
        $this->subject->setProperty('city', 'test city');
        $this->subject->prepareInsertionAndInsertRelations();

        self::assertEquals(
            $cityUid,
            $this->subject->getProperty('city')
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

        $this->subject->loadRealtyObject($this->objectUid);
        $this->subject->setProperty('city', 'test city');
        $this->subject->prepareInsertionAndInsertRelations();

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
        $this->subject->loadRealtyObject($this->objectUid);
        $this->subject->setProperty('city', '12345');
        $this->subject->prepareInsertionAndInsertRelations();

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
        $this->subject->loadRealtyObject($this->objectUid);
        $this->subject->prepareInsertionAndInsertRelations();

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
        $this->subject->loadRealtyObject(
            ['object_number' => self::$objectNumber, 'city' => 0]
        );
        $this->subject->prepareInsertionAndInsertRelations();

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
        $this->subject->loadRealtyObject(
            ['object_number' => self::$objectNumber, 'city' => '']
        );
        $this->subject->prepareInsertionAndInsertRelations();

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
        $this->subject->loadRealtyObject(
            ['object_number' => self::$objectNumber]
        );
        $this->subject->prepareInsertionAndInsertRelations();

        self::assertEquals(
            0,
            $this->testingFramework->countRecords('tx_realty_cities')
        );
    }

    /**
     * @test
     */
    public function writeToDatabaseInsertsCorrectPageUidForNewRecord()
    {
        $this->subject->loadRealtyObject(
            ['object_number' => self::$otherObjectNumber]
        );
        $this->subject->writeToDatabase();

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
        $this->subject->loadRealtyObject(
            ['object_number' => self::$otherObjectNumber]
        );
        $this->subject->writeToDatabase($this->otherPageUid);

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
    public function updatingAnExistingRecordDoesNotChangeThePageUid()
    {
        $this->subject->loadRealtyObject($this->objectUid);
        $this->subject->setProperty('title', 'new title');

        Tx_Oelib_ConfigurationProxy::getInstance('realty')
            ->setAsInteger('pidForRealtyObjectsAndImages', $this->otherPageUid);
        $message = $this->subject->writeToDatabase();

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

        $this->subject->loadRealtyObject(
            ['object_number' => self::$otherObjectNumber],
            true
        );
        $this->subject->writeToDatabase();

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
        $this->subject->loadRealtyObject($this->objectUid);
        $this->subject->setToDeleted();
        $this->subject->writeToDatabase();

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
        $this->subject->loadRealtyObject($this->objectUid, true);
        $this->subject->setProperty('hidden', 1);
        $this->subject->writeToDatabase();

        $this->subject->setToDeleted();
        $this->subject->writeToDatabase();

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
        $this->subject->loadRealtyObject($this->objectUid);
        $this->subject->setToDeleted();
        $this->subject->writeToDatabase();

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
        $this->subject->loadRealtyObject($this->objectUid);
        $this->subject->setToDeleted();
        $this->subject->writeToDatabase();

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

        $this->subject->loadRealtyObject($this->objectUid);
        $this->subject->setProperty('city', 'foo');
        $this->subject->writeToDatabase();

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
        $this->subject->loadRealtyObject($this->objectUid);
        $this->subject->addImageRecord('foo', 'foo.jpg');

        /** @var tx_realty_Model_Image $firstImage */
        $firstImage = $this->subject->getImages()->first();
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
        $this->subject->loadRealtyObject($this->objectUid);

        self::assertEquals(
            0,
            $this->subject->addImageRecord('foo', 'foo.jpg')
        );
    }

    /**
     * @test
     *
     * @expectedException \BadMethodCallException
     */
    public function addImageRecordForNoObjectLoadedThrowsException()
    {
        $this->subject->addImageRecord('foo', 'foo.jpg');
    }

    /**
     * @test
     */
    public function addImagesRecordsUpdatesTheNumberOfCurrentlyAppendedImagesForTheRealtyObject()
    {
        $this->subject->loadRealtyObject($this->objectUid);
        $this->subject->addImageRecord('foo1', 'foo1.jpg');
        $this->subject->addImageRecord('foo2', 'foo2.jpg');
        $this->subject->addImageRecord('foo3', 'foo3.jpg');

        self::assertEquals(
            3,
            $this->subject->getProperty('images')
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
        $this->subject->setData(['openimmo_anid' => $anid]);

        self::assertSame($anid, $this->subject->getAnid());
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
        $this->subject->loadRealtyObject($this->objectUid);
        $this->subject->setProperty('openimmo_anid', 'test anid');
        $this->subject->writeToDatabase(0, true);

        self::assertEquals(
            $feUserUid,
            $this->subject->getProperty('owner')
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
        $this->subject->loadRealtyObject(['openimmo_anid' => 'test anid']);
        $this->subject->writeToDatabase(0, true);

        self::assertEquals(
            $feUserUid,
            $this->subject->getProperty('owner')
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
        $this->subject->loadRealtyObject($this->objectUid);
        $this->subject->setProperty('openimmo_anid', 'OABC10011128124930123asd43fer34');
        $this->subject->writeToDatabase(0, true);

        self::assertSame(
            $feUserUid,
            (int)$this->subject->getProperty('owner')
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
        $this->subject->loadRealtyObject($this->objectUid);
        $this->subject->setProperty('openimmo_anid', 'OABD20011128124930123asd43fer34');
        $this->subject->writeToDatabase(0, true);

        self::assertNotSame(
            $feUserUid,
            (int)$this->subject->getProperty('owner')
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
        $this->subject->loadRealtyObject($this->objectUid);
        $this->subject->setProperty('openimmo_anid', 'test anid');
        $this->subject->writeToDatabase(0, false);

        self::assertEquals(
            0,
            $this->subject->getProperty('owner')
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
        $this->subject->loadRealtyObject($this->objectUid);
        $this->subject->writeToDatabase(0, true);

        self::assertEquals(
            0,
            $this->subject->getProperty('owner')
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

        $this->subject->loadRealtyObject($this->objectUid);
        $this->subject->setProperty('openimmo_anid', 'test anid 1');
        $this->subject->writeToDatabase(0, true);
        $this->subject->setProperty('openimmo_anid', 'test anid 2');
        $this->subject->writeToDatabase(0, true);

        self::assertEquals(
            $feUserUid,
            $this->subject->getProperty('owner')
        );
        self::assertEquals(
            'test anid 2',
            $this->subject->getProperty('openimmo_anid')
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
        $this->subject->loadRealtyObject($this->objectUid);
        $this->subject->setProperty('openimmo_anid', 'test anid 1');
        $this->subject->writeToDatabase(0, true);
        $this->subject->setProperty('openimmo_anid', 'test anid 2');
        $this->subject->writeToDatabase(0, true);

        self::assertEquals(
            $uidOfFeUserOne,
            $this->subject->getProperty('owner')
        );
        self::assertEquals(
            'test anid 2',
            $this->subject->getProperty('openimmo_anid')
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
        $this->subject->loadRealtyObject($this->objectUid);
        $this->subject->setProperty('openimmo_anid', 'test anid');
        $this->subject->writeToDatabase(0, true);

        self::assertEquals(
            1,
            $this->subject->getProperty('contact_data_source')
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
        $this->subject->loadRealtyObject($this->objectUid);
        $this->subject->setProperty('openimmo_anid', 'test anid');
        $this->subject->writeToDatabase(0, true);

        self::assertEquals(
            0,
            $this->subject->getProperty('contact_data_source')
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
        $this->subject->loadRealtyObject($this->objectUid);
        $this->subject->writeToDatabase(0, true);

        self::assertEquals(
            0,
            $this->subject->getProperty('contact_data_source')
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
        $this->subject->setData([]);

        self::assertFalse(
            $this->subject->getShowAddress()
        );
    }

    /**
     * @test
     */
    public function getShowAddressReturnsShowAddress()
    {
        $this->subject->setData(['show_address' => true]);

        self::assertTrue(
            $this->subject->getShowAddress()
        );
    }

    /**
     * @test
     */
    public function setShowAddressSetsShowAddress()
    {
        $this->subject->setData([]);
        $this->subject->setShowAddress(true);
        self::assertTrue(
            $this->subject->getShowAddress()
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
        $this->subject->loadRealtyObject([
            'street' => '',
            'zip' => '',
            'city' => 0,
            'country' => 0,
        ]);

        self::assertSame(
            '',
            $this->subject->getGeoAddress()
        );
    }

    /**
     * @test
     */
    public function getGeoAddressForCityOnlyReturnsCityName()
    {
        $this->subject->loadRealtyObject([
            'street' => '',
            'zip' => '',
            'city' => $this->testingFramework->createRecord('tx_realty_cities', ['title' => 'Bonn']),
            'country' => 0,
        ]);

        self::assertSame(
            'Bonn',
            $this->subject->getGeoAddress()
        );
    }

    /**
     * @test
     */
    public function getGeoAddressForZipCodeOnlyReturnsEmptyString()
    {
        $this->subject->loadRealtyObject([
            'street' => '',
            'zip' => '53111',
            'city' => 0,
            'country' => 0,
        ]);

        self::assertSame(
            '',
            $this->subject->getGeoAddress()
        );
    }

    /**
     * @test
     */
    public function getGeoAddressForZipCodeAndCityReturnsZipCodeAndCity()
    {
        $this->subject->loadRealtyObject([
            'street' => '',
            'zip' => '53111',
            'city' => $this->testingFramework->createRecord('tx_realty_cities', ['title' => 'Bonn']),
            'country' => 0,
        ]);

        self::assertSame(
            '53111 Bonn',
            $this->subject->getGeoAddress()
        );
    }

    /**
     * @test
     */
    public function getGeoAddressForStreetAndZipCodeAndCityReturnsStreetAndZipCodeAndCity()
    {
        $this->subject->loadRealtyObject([
            'street' => 'Am Hof 1',
            'zip' => '53111',
            'city' => $this->testingFramework->createRecord('tx_realty_cities', ['title' => 'Bonn']),
            'country' => 0,
            'show_address' => 1,
        ]);

        self::assertSame(
            'Am Hof 1, 53111 Bonn',
            $this->subject->getGeoAddress()
        );
    }

    /**
     * @test
     */
    public function getGeoAddressForOnlyStreetReturnsEmptyString()
    {
        $this->subject->loadRealtyObject([
            'street' => 'Am Hof 1',
            'zip' => '',
            'city' => 0,
            'country' => 0,
            'show_address' => 1,
        ]);

        self::assertSame(
            '',
            $this->subject->getGeoAddress()
        );
    }

    /**
     * @test
     */
    public function getGeoAddressForStreetAndZipCodeReturnsEmptyString()
    {
        $this->subject->loadRealtyObject([
            'street' => 'Am Hof 1',
            'zip' => '53111',
            'city' => 0,
            'country' => 0,
            'show_address' => 1,
        ]);

        self::assertSame(
            '',
            $this->subject->getGeoAddress()
        );
    }

    /**
     * @test
     */
    public function getGeoAddressForStreetAndCityReturnsStreetAndCity()
    {
        $this->subject->loadRealtyObject([
            'street' => 'Am Hof 1',
            'zip' => '',
            'city' => $this->testingFramework->createRecord('tx_realty_cities', ['title' => 'Bonn']),
            'country' => 0,
            'show_address' => 1,
        ]);

        self::assertSame(
            'Am Hof 1, Bonn',
            $this->subject->getGeoAddress()
        );
    }

    /**
     * @test
     */
    public function getGeoAddressForStreetAndZipCodeAndCityAndCountryReturnsStreetAndZipCodeAndCityAndCountry()
    {
        $this->subject->loadRealtyObject([
            'street' => 'Am Hof 1',
            'zip' => '53111',
            'city' => $this->testingFramework->createRecord('tx_realty_cities', ['title' => 'Bonn']),
            'country' => self::DE,
            'show_address' => 1,
        ]);

        self::assertSame(
            'Am Hof 1, 53111 Bonn, DE',
            $this->subject->getGeoAddress()
        );
    }

    /**
     * @test
     */
    public function getGeoAddressForCityAndCountryReturnsCityAndCountry()
    {
        $this->subject->loadRealtyObject([
            'street' => '',
            'zip' => '',
            'city' => $this->testingFramework->createRecord('tx_realty_cities', ['title' => 'Bonn']),
            'country' => self::DE,
        ]);

        self::assertSame(
            'Bonn, DE',
            $this->subject->getGeoAddress()
        );
    }

    /**
     * @test
     */
    public function getGeoAddressForOnlyCountryReturnsEmptyString()
    {
        $this->subject->loadRealtyObject([
            'street' => '',
            'zip' => '',
            'city' => 0,
            'country' => self::DE,
        ]);

        self::assertSame(
            '',
            $this->subject->getGeoAddress()
        );
    }

    /**
     * @test
     */
    public function hasGeoAddressForNoAddressDataReturnsFalse()
    {
        $this->subject->loadRealtyObject([
            'street' => '',
            'zip' => '',
            'city' => 0,
            'country' => 0,
        ]);

        self::assertFalse(
            $this->subject->hasGeoAddress()
        );
    }

    /**
     * @test
     */
    public function hasGeoAddressForFullAddressReturnsTrue()
    {
        $this->subject->loadRealtyObject([
            'street' => 'Am Hof 1',
            'zip' => '53111',
            'city' => $this->testingFramework->createRecord('tx_realty_cities', ['title' => 'Bonn']),
            'country' => self::DE,
            'show_address' => 1,
        ]);

        self::assertTrue(
            $this->subject->hasGeoAddress()
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
        $this->subject->loadRealtyObject($this->objectUid);

        self::assertEquals(
            $this->objectUid,
            $this->subject->getUid()
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
        $this->subject->loadRealtyObject(
            ['title' => 'foo title filltext-filltext-filltext-filltext']
        );

        self::assertEquals(
            'foo title filltext-filltext-filltext-filltext',
            $this->subject->getTitle()
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
        $this->subject->loadRealtyObject(
            ['title' => '12345678901234567890123456789012']
        );

        self::assertEquals(
            '12345678901234567890123456789012',
            $this->subject->getCroppedTitle()
        );
    }

    /**
     * @test
     */
    public function getCroppedTitleReturnsLongTitleCroppedAtDefaultCropSize()
    {
        $this->subject->loadRealtyObject(
            ['title' => '123456789012345678901234567890123']
        );

        self::assertEquals(
            '12345678901234567890123456789012…',
            $this->subject->getCroppedTitle()
        );
    }

    /**
     * @test
     */
    public function getCroppedTitleReturnsLongTitleCroppedAtGivenCropSize()
    {
        $this->subject->loadRealtyObject(
            ['title' => '123456789012345678901234567890123']
        );

        self::assertEquals(
            '1234567890…',
            $this->subject->getCroppedTitle(10)
        );
    }

    /**
     * @test
     */
    public function getCroppedTitleWithZeroGivenReturnsLongTitleCroppedAtDefaultLength()
    {
        $this->subject->loadRealtyObject(
            ['title' => '123456789012345678901234567890123']
        );

        self::assertEquals(
            '12345678901234567890123456789012…',
            $this->subject->getCroppedTitle(0)
        );
    }

    /**
     * @test
     */
    public function getCroppedTitleWithStringGivenReturnsLongTitleCroppedAtDefaultLength()
    {
        $this->subject->loadRealtyObject(
            ['title' => '123456789012345678901234567890123']
        );

        self::assertEquals(
            '12345678901234567890123456789012…',
            $this->subject->getCroppedTitle('foo')
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
        $this->subject->loadRealtyObject($this->objectUid);
        $this->subject->getForeignPropertyField('floor');
    }

    /**
     * @test
     */
    public function getForeignPropertyFieldReturnsNonNumericFieldContentForAllowedField()
    {
        $this->subject->loadRealtyObject($this->objectUid);
        $this->subject->setProperty('city', 'test city');

        self::assertEquals(
            'test city',
            $this->subject->getForeignPropertyField('city')
        );
    }

    /**
     * @test
     */
    public function getForeignPropertyFieldReturnsEmptyStringIfThereIsNoPropertySetForAllowedField()
    {
        $this->subject->loadRealtyObject($this->objectUid);

        self::assertEquals(
            '',
            $this->subject->getForeignPropertyField('city')
        );
    }

    /**
     * @test
     */
    public function getForeignPropertyFieldReturnsACitysTitle()
    {
        $this->subject->loadRealtyObject($this->objectUid);
        $cityUid = $this->testingFramework->createRecord(
            'tx_realty_cities',
            ['title' => 'foo']
        );
        $this->subject->setProperty('city', $cityUid);

        self::assertEquals(
            'foo',
            $this->subject->getForeignPropertyField('city')
        );
    }

    /**
     * @test
     */
    public function getForeignPropertyFieldReturnsADistrictsTitle()
    {
        $this->subject->loadRealtyObject($this->objectUid);
        $districtUid = $this->testingFramework->createRecord(
            'tx_realty_districts',
            ['title' => 'foo']
        );
        $this->subject->setProperty('district', $districtUid);

        self::assertEquals(
            'foo',
            $this->subject->getForeignPropertyField('district')
        );
    }

    /**
     * @test
     */
    public function getForeignPropertyFieldReturnsACountrysShortLocalName()
    {
        $this->subject->loadRealtyObject($this->objectUid);
        $this->subject->setProperty('country', self::DE);

        self::assertEquals(
            'Deutschland',
            $this->subject->getForeignPropertyField('country', 'cn_short_local')
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
        $this->subject->loadRealtyObject([
            'street' => 'Main Street',
            'zip' => '12345',
            'city' => 'Test Town',
            'district' => 'District',
            'country' => self::DE,
        ]);

        self::assertEquals(
            '12345 Test Town District<br />Deutschland',
            $this->subject->getAddressAsHtml()
        );
    }

    /**
     * @test
     */
    public function getAddressAsHtmlReturnsFormattedCompleteAddressIfAllDataProvidedAndShowAddressTrue()
    {
        $this->subject->loadRealtyObject([
            'show_address' => 1,
            'street' => 'Main Street',
            'zip' => '12345',
            'city' => 'Test Town',
            'district' => 'District',
            'country' => self::DE,
        ]);

        self::assertEquals(
            'Main Street<br />12345 Test Town District<br />Deutschland',
            $this->subject->getAddressAsHtml()
        );
    }

    /**
     * @test
     */
    public function getAddressAsHtmlReturnsFormattedAddressForAllDataButCountryProvidedAndShowAddressTrue()
    {
        $this->subject->loadRealtyObject([
            'show_address' => 1,
            'street' => 'Main Street',
            'zip' => '12345',
            'city' => 'Test Town',
            'district' => 'District',
        ]);

        self::assertEquals(
            'Main Street<br />12345 Test Town District',
            $this->subject->getAddressAsHtml()
        );
    }

    /**
     * @test
     */
    public function getAddressAsHtmlReturnsFormattedAddressForAllDataButStreetProvidedAndShowAddressTrue()
    {
        $this->subject->loadRealtyObject([
            'show_address' => 1,
            'zip' => '12345',
            'city' => 'Test Town',
            'district' => 'District',
            'country' => self::DE,
        ]);

        self::assertEquals(
            '12345 Test Town District<br />Deutschland',
            $this->subject->getAddressAsHtml()
        );
    }

    /**
     * @test
     */
    public function getAddressAsHtmlReturnsFormattedAddressForOnlyStreetProvidedAndShowAddressTrue()
    {
        $this->subject->loadRealtyObject([
            'show_address' => 1,
            'street' => 'Main Street',
        ]);

        self::assertEquals(
            'Main Street<br />',
            $this->subject->getAddressAsHtml()
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
        $this->subject->loadRealtyObject([
            'street' => 'Main Street',
            'zip' => '12345',
            'city' => 'Test Town',
            'district' => 'District',
            'country' => self::DE,
        ]);

        self::assertEquals(
            '12345 Test Town District, Deutschland',
            $this->subject->getAddressAsSingleLine()
        );
    }

    /**
     * @test
     */
    public function getAddressAsSingleLineForShowAddressTrueReturnsCompleteAddress()
    {
        $this->subject->loadRealtyObject([
            'show_address' => 1,
            'street' => 'Main Street',
            'zip' => '12345',
            'city' => 'Test Town',
            'district' => 'District',
            'country' => self::DE,
        ]);

        self::assertEquals(
            'Main Street, 12345 Test Town District, Deutschland',
            $this->subject->getAddressAsSingleLine()
        );
    }

    /**
     * @test
     */
    public function getAddressAsSingleLineForNoCountrySetAndShowAddressTrueReturnsAddressWithoutCountry()
    {
        $this->subject->loadRealtyObject([
            'show_address' => 1,
            'street' => 'Main Street',
            'zip' => '12345',
            'city' => 'Test Town',
            'district' => 'District',
        ]);

        self::assertEquals(
            'Main Street, 12345 Test Town District',
            $this->subject->getAddressAsSingleLine()
        );
    }

    /**
     * @test
     */
    public function getAddressAsSingleLineForNoStreetSetAndShowAddressTrueReturnsAddressWithoutStreet()
    {
        $this->subject->loadRealtyObject([
            'show_address' => 1,
            'zip' => '12345',
            'city' => 'Test Town',
            'district' => 'District',
            'country' => self::DE,
        ]);

        self::assertEquals(
            '12345 Test Town District, Deutschland',
            $this->subject->getAddressAsSingleLine()
        );
    }

    /**
     * @test
     */
    public function getAddressAsSingleLineForShowAddressTrueReturnsCompleteAddressWithoutHtmlTags()
    {
        $this->subject->loadRealtyObject([
            'show_address' => 1,
            'street' => 'Main Street',
            'zip' => '12345',
            'city' => 'Test Town',
            'district' => 'District',
            'country' => self::DE,
        ]);

        self::assertNotContains(
            '<',
            $this->subject->getAddressAsSingleLine()
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
            $this->subject->isAllowedKey('title')
        );
    }

    /**
     * @test
     */
    public function isAllowedKeyReturnsFalseForNonRealtyObjectField()
    {
        self::assertFalse(
            $this->subject->isAllowedKey('foo')
        );
    }

    /**
     * @test
     */
    public function isAllowedKeyReturnsFalseForEmptyKey()
    {
        self::assertFalse(
            $this->subject->isAllowedKey('')
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
        $this->subject->loadRealtyObject(
            [
                'owner' => $this->testingFramework->createFrontEndUser(),
            ]
        );

        self::assertInstanceOf(
            tx_realty_Model_FrontEndUser::class,
            $this->subject->getOwner()
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
            $this->subject->getContactName()
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
            $this->subject->getContactName()
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
            $this->subject->getContactEMailAddress()
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
            $this->subject->getContactEMailAddress()
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
            $this->subject->getContactEMailAddress()
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
            $this->subject->getContactEMailAddress()
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
            $this->subject->getContactCity()
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
            $this->subject->getContactCity()
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
            $this->subject->getContactCity()
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
            $this->subject->getContactStreet()
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
            $this->subject->getContactStreet()
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
            $this->subject->getContactStreet()
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
            $this->subject->getContactZip()
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
            $this->subject->getContactZip()
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
            $this->subject->getContactZip()
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
            $this->subject->getContactHomepage()
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
            $this->subject->getContactHomepage()
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
            $this->subject->getContactHomepage()
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
            $this->subject->getContactPhoneNumber()
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
            $this->subject->getContactPhoneNumber()
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
            $this->subject->getContactPhoneNumber()
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
            $this->subject->getContactPhoneNumber()
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
            $this->subject->getContactPhoneNumber()
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
            $this->subject->getContactPhoneNumber()
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
            $this->subject->getContactSwitchboard()
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
            $this->subject->getContactSwitchboard()
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
            $this->subject->getContactDirectExtension()
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
            $this->subject->getContactDirectExtension()
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
            $this->subject->getStatus()
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
            $this->subject->getStatus()
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
            $this->subject->isRentedOrSold()
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
            $this->subject->isRentedOrSold()
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
            $this->subject->isRentedOrSold()
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
            $this->subject->isRentedOrSold()
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
        $this->subject->setData([]);

        self::assertEquals(
            '',
            $this->subject->getStreet()
        );
    }

    /**
     * @test
     */
    public function getStreetForNonEmptyStreetReturnsStreet()
    {
        $this->subject->setData(['street' => 'foo']);

        self::assertSame(
            'foo',
            $this->subject->getStreet()
        );
    }

    /**
     * @test
     */
    public function hasStreetForEmptyStreetReturnsFalse()
    {
        $this->subject->setData(['street' => '']);

        self::assertFalse(
            $this->subject->hasStreet()
        );
    }

    /**
     * @test
     */
    public function hasStreetForNonEmptyStreetReturnsTrue()
    {
        $this->subject->setData(['street' => 'foo']);

        self::assertTrue(
            $this->subject->hasStreet()
        );
    }

    /**
     * @test
     */
    public function getZipForEmptyZipReturnsEmptyString()
    {
        $this->subject->setData([]);

        self::assertSame(
            '',
            $this->subject->getZip()
        );
    }

    /**
     * @test
     */
    public function setStreetSetsStreet()
    {
        $this->subject->setData([]);
        $this->subject->setStreet('bar');

        self::assertSame(
            'bar',
            $this->subject->getStreet()
        );
    }

    /**
     * @test
     */
    public function getZipForNonEmptyZipReturnsZip()
    {
        $this->subject->setData(['zip' => '12345']);

        self::assertSame(
            '12345',
            $this->subject->getZip()
        );
    }

    /**
     * @test
     */
    public function setZipSetsZip()
    {
        $this->subject->setData([]);
        $zip = '16432';
        $this->subject->setZip($zip);

        self::assertSame(
            $zip,
            $this->subject->getZip()
        );
    }

    /**
     * @test
     */
    public function hasZipForEmptyZipReturnsFalse()
    {
        $this->subject->setData(['zip' => '']);

        self::assertFalse(
            $this->subject->hasZip()
        );
    }

    /**
     * @test
     */
    public function hasZipForNonEmptyZipReturnsTrue()
    {
        $this->subject->setData(['zip' => '12345']);

        self::assertTrue(
            $this->subject->hasZip()
        );
    }

    /**
     * @test
     */
    public function getCityForNoCityReturnsNull()
    {
        $this->subject->setData([]);

        self::assertNull(
            $this->subject->getCity()
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
        $this->subject->setData(['city' => $cityUid]);
        /** @var tx_realty_Mapper_City $mapper */
        $mapper = Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_City::class);
        /** @var tx_realty_Model_City $city */
        $city = $mapper->find($cityUid);

        self::assertSame(
            $city,
            $this->subject->getCity()
        );
    }

    /**
     * @test
     */
    public function hasCityForNoCityReturnsFalse()
    {
        $this->subject->setData([]);

        self::assertFalse(
            $this->subject->hasCity()
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
        $this->subject->setData(['city' => $cityUid]);

        self::assertTrue(
            $this->subject->hasCity()
        );
    }

    /**
     * @test
     */
    public function getCountryForNoCountryReturnsNull()
    {
        $this->subject->setData([]);

        self::assertNull(
            $this->subject->getCountry()
        );
    }

    /**
     * @test
     */
    public function getCountryForExistingCountryReturnsCountry()
    {
        $this->subject->setData(['country' => self::DE]);
        /** @var Tx_Oelib_Mapper_Country $mapper */
        $mapper = Tx_Oelib_MapperRegistry::get(Tx_Oelib_Mapper_Country::class);
        /** @var Tx_Oelib_Model_Country $country */
        $country = $mapper->find(self::DE);

        self::assertSame(
            $country,
            $this->subject->getCountry()
        );
    }

    /**
     * @test
     */
    public function hasCountryForNoCountryReturnsFalse()
    {
        $this->subject->setData([]);

        self::assertFalse(
            $this->subject->hasCountry()
        );
    }

    /**
     * @test
     */
    public function hasCountryForExistingCountryReturnsTrue()
    {
        $this->subject->setData(['country' => self::DE]);

        self::assertTrue(
            $this->subject->hasCountry()
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
        $this->subject->setData(
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
            $this->subject->getGeoCoordinates()
        );
    }

    /**
     * @test
     */
    public function getGeoCoordinatesForNotHasCoordinatesReturnsEmptyArray()
    {
        $this->subject->setData(
            [
                'latitude' => -42.7,
                'longitude' => 42.0,
                'has_coordinates' => false,
            ]
        );

        self::assertSame(
            [],
            $this->subject->getGeoCoordinates()
        );
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function setGeoCoordinatesWithoutLatitudeThrowsException()
    {
        $this->subject->setGeoCoordinates(['longitude' => 42.0]);
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function setGeoCoordinatesWithoutLongitudeThrowsException()
    {
        $this->subject->setGeoCoordinates(['latitude' => -42.7]);
    }

    /**
     * @test
     */
    public function setGeoCoordinatesSetsCoordinates()
    {
        $this->subject->setData([]);

        $this->subject->setGeoCoordinates(
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
            $this->subject->getGeoCoordinates()
        );
    }

    /**
     * @test
     */
    public function setGeoCoordinatesSetsHasCoordinatesToTrue()
    {
        $this->subject->setData(['has_coordinates' => false]);

        $this->subject->setGeoCoordinates(
            [
                'latitude' => -42.7,
                'longitude' => 42.0,
            ]
        );

        self::assertTrue(
            $this->subject->hasGeoCoordinates()
        );
    }

    /**
     * @test
     */
    public function setGeoCoordinatesSetsHasGeoErrorToFalse()
    {
        $this->subject->setData(['coordinates_problem' => true]);

        $this->subject->setGeoCoordinates(
            [
                'latitude' => -42.7,
                'longitude' => 42.0,
            ]
        );

        self::assertFalse(
            $this->subject->hasGeoError()
        );
    }

    /**
     * @test
     */
    public function hasGeoCoordinatesForHasCoordinatesTrueReturnsTrue()
    {
        $this->subject->setData(['has_coordinates' => true]);

        self::assertTrue(
            $this->subject->hasGeoCoordinates()
        );
    }

    /**
     * @test
     */
    public function hasGeoCoordinatesForHasCoordinatesFalseReturnsFalse()
    {
        $this->subject->setData(['has_coordinates' => false]);

        self::assertFalse(
            $this->subject->hasGeoCoordinates()
        );
    }

    /**
     * @test
     */
    public function clearGeoCoordinatesSetsHasCoordinatesToFalse()
    {
        $this->subject->setData(['has_coordinates' => true]);

        $this->subject->clearGeoCoordinates();

        self::assertFalse(
            $this->subject->hasGeoCoordinates()
        );
    }

    /**
     * @test
     */
    public function hasGeoErrorForProblemTrueReturnsTrue()
    {
        $this->subject->setData(['coordinates_problem' => true]);

        self::assertTrue(
            $this->subject->hasGeoError()
        );
    }

    /**
     * @test
     */
    public function hasGeoErrorForProblemFalseReturnsFalse()
    {
        $this->subject->setData(['coordinates_problem' => false]);

        self::assertFalse(
            $this->subject->hasGeoError()
        );
    }

    /**
     * @test
     */
    public function setGeoErrorSetsGeoErrorToTrue()
    {
        $this->subject->setData(['coordinates_problem' => false]);

        $this->subject->setGeoError();

        self::assertTrue(
            $this->subject->hasGeoError()
        );
    }

    /**
     * @test
     */
    public function clearGeoErrorSetsGeoErrorToFalse()
    {
        $this->subject->setData(['coordinates_problem' => true]);

        $this->subject->clearGeoError();

        self::assertFalse(
            $this->subject->hasGeoError()
        );
    }

    /**
     * @test
     */
    public function getDistanceToTheSeaInitiallyReturnsZero()
    {
        $this->subject->setData([]);

        self::assertSame(
            0,
            $this->subject->getDistanceToTheSea()
        );
    }

    /**
     * @test
     */
    public function getDistanceToTheSeaReturnsDistanceToTheSea()
    {
        $distance = 42;

        $this->subject->setData(['distance_to_the_sea' => $distance]);

        self::assertSame(
            $distance,
            $this->subject->getDistanceToTheSea()
        );
    }

    /**
     * @test
     */
    public function hasDistanceToTheSeaForZeroReturnsFalse()
    {
        $this->subject->setData(['distance_to_the_sea' => 0]);

        self::assertFalse(
            $this->subject->hasDistanceToTheSea()
        );
    }

    /**
     * @test
     */
    public function hasDistanceToTheSeaForPositiveNumberReturnsTrue()
    {
        $this->subject->setData(['distance_to_the_sea' => 9]);

        self::assertTrue(
            $this->subject->hasDistanceToTheSea()
        );
    }

    /**
     * @test
     */
    public function setDistanceToTheSeaSetsDistanceToTheSea()
    {
        $distance = 9;

        $this->subject->setData([]);
        $this->subject->setDistanceToTheSea($distance);

        self::assertSame(
            $distance,
            $this->subject->getDistanceToTheSea()
        );
    }

    /**
     * @test
     *
     * @expectedException \InvalidArgumentException
     */
    public function setDistanceToTheSeaWithNegativeNumberThrowsException()
    {
        $this->subject->setData([]);
        $this->subject->setDistanceToTheSea(-1);
    }
}
