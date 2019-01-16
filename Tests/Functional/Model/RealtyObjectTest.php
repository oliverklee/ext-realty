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

    protected function setUp()
    {
        parent::setUp();

        /** @var BackendUserAuthentication|ObjectProphecy $backEndUserProphecy */
        $backEndUserProphecy = $this->prophesize(BackendUserAuthentication::class);
        $backEndUserProphecy->isAdmin()->willReturn(true);
        $GLOBALS['BE_USER'] = $backEndUserProphecy->reveal();

        $this->realtyObjectMapper = new \tx_realty_Mapper_RealtyObject();
    }

    /**
     * @test
     */
    public function getAttachmentsForNoAttachmentsReturnsEmptyArray()
    {
        $this->importDataSet(__DIR__ . '/../Fixtures/RealtyObjects.xml');

        /** @var \tx_realty_Model_RealtyObject $model */
        $model = $this->realtyObjectMapper->find(1);
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
        $model = $this->realtyObjectMapper->find(2);
        $attachments = $model->getAttachments();

        self::assertCount(3, $attachments);
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
        $model = $this->realtyObjectMapper->find(2);
        $attachments = $model->getPdfAttachments();

        self::assertCount(1, $attachments);
        $firstAttachment = $attachments[0];
        self::assertInstanceOf(FileReference::class, $firstAttachment);
        self::assertSame('test.pdf', $firstAttachment->getName());
    }
}
