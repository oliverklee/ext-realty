<?php

namespace OliverKlee\Realty\Tests\Functional\Model;

use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use Prophecy\Prophecy\ObjectProphecy;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Test case.
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class RealtyObjectTest extends FunctionalTestCase
{
    /**
     * @var string[]
     */
    protected $testExtensionsToLoad = [
        'typo3conf/ext/realty',
    ];

    /**
     * @var \tx_realty_Mapper_RealtyObject
     */
    private $realtyObjectMapper = null;

    /**
     * @var \tx_realty_Model_RealtyObject
     */
    private $subject = null;

    protected function setUp()
    {
        parent::setUp();

        /** @var BackendUserAuthentication|ObjectProphecy $backEndUserProphecy */
        $backEndUserProphecy = $this->prophesize(BackendUserAuthentication::class);
        $backEndUserProphecy->isAdmin()->willReturn(true);
        $GLOBALS['BE_USER'] = $backEndUserProphecy->reveal();

        $this->realtyObjectMapper = new \tx_realty_Mapper_RealtyObject();

        $this->subject = new \tx_realty_Model_RealtyObject();
    }

    protected function tearDown()
    {
        if (\file_exists($this->getAbsoluteAttachmentsTargetFolder())) {
            GeneralUtility::rmdir($this->getAbsoluteAttachmentsTargetFolder(), true);
        }

        parent::tearDown();
    }

    /**
     * @return string
     */
    private function getAbsoluteFixturesPath()
    {
        return __DIR__ . '/../Fixtures/';
    }

    /**
     * @return string
     */
    public function getAbsoluteAttachmentsTargetFolder()
    {
        return GeneralUtility::getFileAbsFileName('fileadmin/realty_attachments');
    }

    /**
     * @test
     */
    public function recordExistsInDatabaseIfNoExistingObjectNumberGiven()
    {
        self::assertFalse($this->subject->recordExistsInDatabase(['object_number' => '99999']));
    }

    /**
     * @test
     */
    public function recordExistsInDatabaseIfExistingObjectNumberGiven()
    {
        $this->importDataSet(__DIR__ . '/../Fixtures/RealtyObjects.xml');

        self::assertTrue($this->subject->recordExistsInDatabase(['object_number' => '100000']));
    }

    /**
     * @test
     */
    public function loadDatabaseEntryWithValidUidLoadsData()
    {
        $this->importDataSet(__DIR__ . '/../Fixtures/RealtyObjects.xml');

        $result = $this->subject->loadDatabaseEntry(102);

        self::assertSame(\Tx_Oelib_Db::selectSingle('*', 'tx_realty_objects', 'uid = 102'), $result);
    }

    /**
     * @test
     */
    public function loadDatabaseEntryWithInexistentUidReturnsEmptyArray()
    {
        $result = $this->subject->loadDatabaseEntry('99999');

        self::assertSame([], $result);
    }

    /**
     * @test
     */
    public function loadDatabaseEntryOfAnNonHiddenObjectIfOnlyVisibleAreAllowedReturnsObjectData()
    {
        $this->importDataSet(__DIR__ . '/../Fixtures/RealtyObjects.xml');

        $this->subject->loadRealtyObject(102, false);
        $result = $this->subject->loadDatabaseEntry(102);

        self::assertSame(\Tx_Oelib_Db::selectSingle('*', 'tx_realty_objects', 'uid = 102'), $result);
    }

    /**
     * @test
     */
    public function loadDatabaseEntryDoesNotLoadAHiddenObjectIfOnlyVisibleAreAllowed()
    {
        $this->importDataSet(__DIR__ . '/../Fixtures/RealtyObjects.xml');

        $this->subject->loadRealtyObject(103, false);
        $result = $this->subject->loadDatabaseEntry(103);

        self::assertSame([], $result);
    }

    /**
     * @test
     */
    public function loadDatabaseEntryLoadsAHiddenObjectIfHiddenAreAllowed()
    {
        $this->importDataSet(__DIR__ . '/../Fixtures/RealtyObjects.xml');

        $this->subject->loadRealtyObject(103, true);
        $result = $this->subject->loadDatabaseEntry(103);

        self::assertSame(\Tx_Oelib_Db::selectSingle('*', 'tx_realty_objects', 'uid = 103'), $result);
    }

    /**
     * @test
     */
    public function getAttachmentsForNoAttachmentsReturnsEmptyArray()
    {
        $this->importDataSet(__DIR__ . '/../Fixtures/RealtyObjects.xml');

        /** @var \tx_realty_Model_RealtyObject $subject */
        $subject = $this->realtyObjectMapper->find(101);
        $attachments = $subject->getAttachments();

        self::assertSame([], $attachments);
    }

    /**
     * @test
     */
    public function getAttachmentsReturnsExistingAttachments()
    {
        $this->importDataSet(__DIR__ . '/../Fixtures/Attachments.xml');
        $this->importDataSet(__DIR__ . '/../Fixtures/RealtyObjects.xml');

        /** @var \tx_realty_Model_RealtyObject $subject */
        $subject = $this->realtyObjectMapper->find(102);
        $attachments = $subject->getAttachments();

        self::assertGreaterThanOrEqual(1, $attachments);
        $firstAttachment = $attachments[0];
        self::assertInstanceOf(FileReference::class, $firstAttachment);
        self::assertSame('test.jpg', $firstAttachment->getName());
    }

    /**
     * @test
     */
    public function getPdfAttachmentsReturnsExistingPdfAttachmentsAndIgnoresNonPdfAttachments()
    {
        $this->importDataSet(__DIR__ . '/../Fixtures/Attachments.xml');
        $this->importDataSet(__DIR__ . '/../Fixtures/RealtyObjects.xml');

        /** @var \tx_realty_Model_RealtyObject $subject */
        $subject = $this->realtyObjectMapper->find(102);
        $attachments = $subject->getPdfAttachments();

        self::assertCount(1, $attachments);
        $firstAttachment = $attachments[0];
        self::assertInstanceOf(FileReference::class, $firstAttachment);
        self::assertSame('test.pdf', $firstAttachment->getName());
    }

    /**
     * @test
     */
    public function getPdfJpegAttachmentsReturnsExistingJpegAttachmentsAndIgnoresNonPdfAttachments()
    {
        $this->importDataSet(__DIR__ . '/../Fixtures/Attachments.xml');
        $this->importDataSet(__DIR__ . '/../Fixtures/RealtyObjects.xml');

        /** @var \tx_realty_Model_RealtyObject $subject */
        $subject = $this->realtyObjectMapper->find(102);
        $attachments = $subject->getJpegAttachments();

        self::assertCount(1, $attachments);
        $firstAttachment = $attachments[0];
        self::assertInstanceOf(FileReference::class, $firstAttachment);
        self::assertSame('test.jpg', $firstAttachment->getName());
    }

    /**
     * @test
     *
     * @expectedException \BadMethodCallException
     */
    public function addAndSaveAttachmentForVirginModelThrowsException()
    {
        $subject = new \tx_realty_Model_RealtyObject();

        $subject->addAndSaveAttachment($this->getAbsoluteFixturesPath() . 'test2.jpg', 'test image');
    }

    /**
     * @test
     *
     * @expectedException \BadMethodCallException
     */
    public function addAndSaveAttachmentForEmptyModelWithoutUidThrowsException()
    {
        $subject = new \tx_realty_Model_RealtyObject();
        $subject->setData([]);

        $subject->addAndSaveAttachment($this->getAbsoluteFixturesPath() . 'test2.jpg', 'test image');
    }

    /**
     * @test
     *
     * @expectedException \UnexpectedValueException
     */
    public function addAndSaveAttachmentForInexistentFileThrowsException()
    {
        $this->importDataSet(__DIR__ . '/../Fixtures/Attachments.xml');
        $this->importDataSet(__DIR__ . '/../Fixtures/RealtyObjects.xml');

        /** @var \tx_realty_Model_RealtyObject $subject */
        $subject = $this->realtyObjectMapper->find(102);
        $subject->addAndSaveAttachment($this->getAbsoluteFixturesPath() . 'there-is-no-image.jpg', 'test image');
    }

    /**
     * @test
     */
    public function addAndSaveAttachmentForCompletelyNewAttachmentsIncreasesNumberOfAttachments()
    {
        $this->importDataSet(__DIR__ . '/../Fixtures/Attachments.xml');
        $this->importDataSet(__DIR__ . '/../Fixtures/RealtyObjects.xml');

        /** @var \tx_realty_Model_RealtyObject $subject */
        $subject = $this->realtyObjectMapper->find(102);
        $oldNumberOfAttachments = $subject->getNumberOfAttachments();

        $subject->addAndSaveAttachment($this->getAbsoluteFixturesPath() . 'test2.jpg', 'test image');

        self::assertSame($oldNumberOfAttachments + 1, $subject->getNumberOfAttachments());
    }

    /**
     * @test
     */
    public function addAndSaveAttachmentReturnsForNewAttachmentReturnsPersistedFile()
    {
        $this->importDataSet(__DIR__ . '/../Fixtures/Attachments.xml');
        $this->importDataSet(__DIR__ . '/../Fixtures/RealtyObjects.xml');

        /** @var \tx_realty_Model_RealtyObject $subject */
        $subject = $this->realtyObjectMapper->find(102);

        $result = $subject->addAndSaveAttachment($this->getAbsoluteFixturesPath() . 'test2.jpg', 'test image');

        self::assertInstanceOf(File::class, $result);
        self::assertGreaterThan(0, $result->getUid());
    }

    /**
     * @test
     */
    public function addAndSaveAttachmentReturnsForNewAttachmentCopiesFileToObjectSpecificLocation()
    {
        $this->importDataSet(__DIR__ . '/../Fixtures/Attachments.xml');
        $this->importDataSet(__DIR__ . '/../Fixtures/RealtyObjects.xml');

        /** @var \tx_realty_Model_RealtyObject $subject */
        $subject = $this->realtyObjectMapper->find(102);

        $result = $subject->addAndSaveAttachment($this->getAbsoluteFixturesPath() . 'test2.jpg', 'test image');

        self::assertSame('/realty_attachments/102/test2.jpg', $result->getIdentifier());
    }

    /**
     * @test
     */
    public function addAndSaveAttachmentReturnsForNewAttachmentSetsTitle()
    {
        $this->importDataSet(__DIR__ . '/../Fixtures/Attachments.xml');
        $this->importDataSet(__DIR__ . '/../Fixtures/RealtyObjects.xml');

        /** @var \tx_realty_Model_RealtyObject $subject */
        $subject = $this->realtyObjectMapper->find(102);

        $result = $subject->addAndSaveAttachment($this->getAbsoluteFixturesPath() . 'test2.jpg', 'test image');

        $metaData = \Tx_Oelib_Db::selectSingle('*', 'sys_file_metadata', 'file = ' . $result->getUid());
        self::assertSame('test image', $metaData['title']);
    }

    /**
     * @test
     */
    public function addAndSaveAttachmentCreatesReferenceToNewFile()
    {
        $this->importDataSet(__DIR__ . '/../Fixtures/Attachments.xml');
        $this->importDataSet(__DIR__ . '/../Fixtures/RealtyObjects.xml');

        /** @var \tx_realty_Model_RealtyObject $subject */
        $subject = $this->realtyObjectMapper->find(102);

        $result = $subject->addAndSaveAttachment($this->getAbsoluteFixturesPath() . 'test2.jpg', 'test image');

        $exists = false;
        foreach ($subject->getAttachments() as $reference) {
            $exists = $exists || ($reference->getOriginalFile()->getUid() === $result->getUid());
        }
        self::assertTrue($exists);
    }

    /**
     * @test
     */
    public function addAndSaveAttachmentForDuplicatedFileNameFileReusesExistingFile()
    {
        $this->importDataSet(__DIR__ . '/../Fixtures/Attachments.xml');
        $this->importDataSet(__DIR__ . '/../Fixtures/RealtyObjects.xml');
        $targetFolder = $this->getAbsoluteAttachmentsTargetFolder() . '102/';
        GeneralUtility::mkdir_deep($targetFolder);
        \copy($this->getAbsoluteFixturesPath() . 'test2.txt', $targetFolder . 'test2.txt');

        /** @var \tx_realty_Model_RealtyObject $subject */
        $subject = $this->realtyObjectMapper->find(102);

        $result = $subject->addAndSaveAttachment($this->getAbsoluteFixturesPath() . '/test2.txt', 'test image');

        self::assertSame('test2.txt', $result->getName());
        self::assertSame('/realty_attachments/102/test2.txt', $result->getIdentifier());
        self::assertSame(13, $result->getUid());
    }

    /**
     * @test
     */
    public function addAndSaveAttachmentForDuplicatedFileNameFileUpdatesTitle()
    {
        $newTitle = 'new title';

        $this->importDataSet(__DIR__ . '/../Fixtures/Attachments.xml');
        $this->importDataSet(__DIR__ . '/../Fixtures/RealtyObjects.xml');
        $targetFolder = $this->getAbsoluteAttachmentsTargetFolder() . '102/';
        GeneralUtility::mkdir_deep($targetFolder);
        \copy($this->getAbsoluteFixturesPath() . 'test.jpg', $targetFolder . 'test.jpg');

        /** @var \tx_realty_Model_RealtyObject $subject */
        $subject = $this->realtyObjectMapper->find(102);

        $result = $subject->addAndSaveAttachment($this->getAbsoluteFixturesPath() . '/test.jpg', $newTitle);

        $metaData = \Tx_Oelib_Db::selectSingle('*', 'sys_file_metadata', 'file = ' . $result->getUid());
        self::assertSame($newTitle, $metaData['title']);
    }

    /**
     * @test
     */
    public function addAndSaveAttachmentForDuplicatedFileNameFileReusesExistingFileReference()
    {
        $this->importDataSet(__DIR__ . '/../Fixtures/Attachments.xml');
        $this->importDataSet(__DIR__ . '/../Fixtures/RealtyObjects.xml');

        /** @var \tx_realty_Model_RealtyObject $subject */
        $subject = $this->realtyObjectMapper->find(102);

        $subject->addAndSaveAttachment($this->getAbsoluteFixturesPath() . 'test.jpg', 'test image');

        $numberOfReferences = \Tx_Oelib_Db::count('sys_file_reference', 'uid_local = 10 AND uid_foreign = 102');
        self::assertSame(1, $numberOfReferences);
    }
}
