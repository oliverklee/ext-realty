<?php

namespace OliverKlee\Realty\Tests\Functional\Model;

use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use Prophecy\Prophecy\ObjectProphecy;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Resource\FileReference;

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

        /** @var \tx_realty_Model_RealtyObject $model */
        $model = $this->realtyObjectMapper->find(101);
        $attachments = $model->getAttachments();

        self::assertSame([], $attachments);
    }

    /**
     * @test
     */
    public function getAttachmentsReturnsExistingAttachments()
    {
        $this->importDataSet(__DIR__ . '/../Fixtures/Attachments.xml');
        $this->importDataSet(__DIR__ . '/../Fixtures/RealtyObjects.xml');

        /** @var \tx_realty_Model_RealtyObject $model */
        $model = $this->realtyObjectMapper->find(102);
        $attachments = $model->getAttachments();

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

        /** @var \tx_realty_Model_RealtyObject $model */
        $model = $this->realtyObjectMapper->find(102);
        $attachments = $model->getPdfAttachments();

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

        /** @var \tx_realty_Model_RealtyObject $model */
        $model = $this->realtyObjectMapper->find(102);
        $attachments = $model->getJpegAttachments();

        self::assertCount(1, $attachments);
        $firstAttachment = $attachments[0];
        self::assertInstanceOf(FileReference::class, $firstAttachment);
        self::assertSame('test.jpg', $firstAttachment->getName());
    }
}
