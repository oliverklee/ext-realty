<?php

namespace OliverKlee\Realty\Tests\Unit\Import;

use Nimut\TestingFramework\TestCase\UnitTestCase;
use OliverKlee\Realty\Import\AttachmentImporter;
use org\bovigo\vfs\vfsStream;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\SingletonInterface;

/**
 * Test case.
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class AttachmentImporterTest extends UnitTestCase
{
    /**
     * @var AttachmentImporter
     */
    private $subject = null;

    /**
     * @var \tx_realty_Model_RealtyObject|\PHPUnit_Framework_MockObject_MockObject
     */
    private $realtyObjectMock = null;

    /**
     * @var string
     */
    private $importDirectory = '';

    protected function setUp()
    {
        $this->realtyObjectMock = $this->getMockBuilder(\tx_realty_Model_RealtyObject::class)->getMock();

        vfsStream::setup('import');
        $this->importDirectory = vfsStream::url('import');

        $this->subject = new AttachmentImporter($this->realtyObjectMock);
    }

    /**
     * @test
     */
    public function classIsNoSingleton()
    {
        self::assertNotInstanceOf(SingletonInterface::class, $this->subject);
    }

    /**
     * @test
     */
    public function startTransactionForObjectWithoutUidSavesObject()
    {
        $this->realtyObjectMock->method('hasUid')->willReturn(false);
        $this->realtyObjectMock->expects(self::once())->method('writeToDatabase')->willReturn('');
        $this->realtyObjectMock->method('getAttachments')->willReturn([]);

        $this->subject->startTransaction();
    }

    /**
     * @test
     */
    public function startTransactionForInvalidObjectWithoutUidThrowsExceptionOnSaving()
    {
        $this->realtyObjectMock->method('hasUid')->willReturn(false);
        $this->realtyObjectMock->expects(self::once())
            ->method('writeToDatabase')
            ->willReturn('message_fields_required');
        $this->realtyObjectMock->method('getAttachments')->willReturn([]);

        $this->expectException(\RuntimeException::class);

        $this->subject->startTransaction();
    }

    /**
     * @test
     */
    public function startTransactionForObjectWithUidNotSavesObject()
    {
        $this->realtyObjectMock->method('hasUid')->willReturn(true);
        $this->realtyObjectMock->expects(self::never())->method('writeToDatabase')->willReturn('');
        $this->realtyObjectMock->method('getAttachments')->willReturn([]);

        $this->subject->startTransaction();
    }

    /**
     * @test
     */
    public function startTransactionCalledTwoTimesThrowsException()
    {
        $this->realtyObjectMock->method('writeToDatabase')->willReturn('');
        $this->realtyObjectMock->method('getAttachments')->willReturn([]);

        $this->subject->startTransaction();

        $this->expectException(\BadMethodCallException::class);

        $this->subject->startTransaction();
    }

    /**
     * @test
     */
    public function addAttachmentWithoutStartedTransactionThrowsException()
    {
        $this->expectException(\BadMethodCallException::class);

        $this->subject->addAttachment($this->importDirectory . '/image.jpg', 'some image');
    }

    /**
     * @test
     */
    public function addAttachmentWithAlreadyFinishedTransactionThrowsException()
    {
        $this->realtyObjectMock->method('writeToDatabase')->willReturn('');
        $this->realtyObjectMock->method('getAttachments')->willReturn([]);

        $this->subject->startTransaction();
        $this->subject->finishTransaction();

        $this->expectException(\BadMethodCallException::class);

        $this->subject->addAttachment($this->importDirectory . '/image.jpg', 'some image');
    }

    /**
     * @test
     */
    public function finishTransactionWithoutStartedTransactionThrowsException()
    {
        $this->expectException(\BadMethodCallException::class);

        $this->subject->finishTransaction();
    }

    /**
     * @test
     */
    public function finishTransactionAfterAlreadyFinishedTransactionThrowsException()
    {
        $this->realtyObjectMock->method('writeToDatabase')->willReturn('');
        $this->realtyObjectMock->method('getAttachments')->willReturn([]);

        $this->subject->startTransaction();
        $this->subject->finishTransaction();

        $this->expectException(\BadMethodCallException::class);

        $this->subject->finishTransaction();
    }

    /**
     * @test
     */
    public function finishTransactionSavesObject()
    {
        $this->realtyObjectMock->method('hasUid')->willReturn(true);
        $this->realtyObjectMock->method('getAttachments')->willReturn([]);
        $this->subject->startTransaction();

        $this->realtyObjectMock->expects(self::once())->method('writeToDatabase')->willReturn('');

        $this->subject->finishTransaction();
    }

    /**
     * @test
     */
    public function finishTransactionSavesNewlyAddedAttachments()
    {
        $fileName = $this->importDirectory . '/test.jpg';
        \file_put_contents($fileName, '');
        $title = 'Some image';

        $this->realtyObjectMock->method('writeToDatabase')->willReturn('');
        $this->realtyObjectMock->method('getAttachments')->willReturn([]);

        $this->subject->startTransaction();
        $this->subject->addAttachment($fileName, $title);

        $this->realtyObjectMock->expects(self::once())->method('getAttachmentByBaseName')->with('test.jpg')
            ->willReturn(null);
        $this->realtyObjectMock->expects(self::once())->method('addAndSaveAttachment')->with($fileName, $title);

        $this->subject->finishTransaction();
    }

    /**
     * @test
     */
    public function finishTransactionKeepsUpdatedAttachments()
    {
        $fileName = $this->importDirectory . '/test.jpg';
        $title = 'Some image';

        $fileUid = 42;
        /** @var File|\PHPUnit_Framework_MockObject_MockObject $fileMock */
        $fileMock = $this->getMockBuilder(File::class)->disableOriginalConstructor()->getMock();
        $fileMock->method('getUid')->willReturn($fileUid);
        /** @var FileReference|\PHPUnit_Framework_MockObject_MockObject $fileReferenceMock */
        $fileReferenceMock = $this->getMockBuilder(FileReference::class)->disableOriginalConstructor()->getMock();
        $fileReferenceMock->method('getOriginalFile')->willReturn($fileMock);
        $this->realtyObjectMock->expects(self::at(2))->method('getAttachments')->willReturn([$fileReferenceMock]);

        $this->realtyObjectMock->method('writeToDatabase')->willReturn('');
        $this->realtyObjectMock->expects(self::once())->method('getAttachmentByBaseName')->with('test.jpg')
            ->willReturn($fileReferenceMock);
        $this->realtyObjectMock->expects(self::never())->method('addAndSaveAttachment');
        $this->realtyObjectMock->expects(self::never())->method('removeAttachmentByFileUid');

        $this->subject->startTransaction();
        $this->subject->addAttachment($fileName, $title);

        $this->subject->finishTransaction();
    }

    /**
     * @test
     */
    public function finishTransactionAlsoSavesAttachmentsAddedInSecondTransaction()
    {
        $fileName = $this->importDirectory . '/test.jpg';
        \file_put_contents($fileName, '');
        $title = 'Some image';

        $this->realtyObjectMock->method('writeToDatabase')->willReturn('');
        $this->realtyObjectMock->method('getAttachments')->willReturn([]);

        $this->subject->startTransaction();
        $this->subject->finishTransaction();

        $this->subject->startTransaction();
        $this->subject->addAttachment($fileName, $title);

        $this->realtyObjectMock->expects(self::once())->method('getAttachmentByBaseName')->with('test.jpg')
            ->willReturn(null);
        $this->realtyObjectMock->expects(self::once())->method('addAndSaveAttachment')->with($fileName, $title);

        $this->subject->finishTransaction();
    }

    /**
     * @test
     */
    public function finishTransactionDeletesExistingAttachmentsThatHaveNotBeenUpdated()
    {
        $this->realtyObjectMock->method('writeToDatabase')->willReturn('');

        $fileUid = 42;
        /** @var File|\PHPUnit_Framework_MockObject_MockObject $fileMock */
        $fileMock = $this->getMockBuilder(File::class)->disableOriginalConstructor()->getMock();
        $fileMock->method('getUid')->willReturn($fileUid);
        /** @var FileReference|\PHPUnit_Framework_MockObject_MockObject $fileReferenceMock */
        $fileReferenceMock = $this->getMockBuilder(FileReference::class)->disableOriginalConstructor()->getMock();
        $fileReferenceMock->method('getOriginalFile')->willReturn($fileMock);
        $this->realtyObjectMock->method('getAttachments')->willReturn([$fileReferenceMock]);

        $this->subject->startTransaction();

        $this->realtyObjectMock->expects(self::once())->method('removeAttachmentByFileUid')->with($fileUid);

        $this->subject->finishTransaction();
    }

    /**
     * @test
     */
    public function attachmentAddedOnlyInFirstTransactionWillBeDeletedInSecondTransaction()
    {
        $fileName = $this->importDirectory . '/image.jpg';
        \file_put_contents($fileName, '');

        $this->realtyObjectMock->method('writeToDatabase')->willReturn('');
        $this->realtyObjectMock->expects(self::at(2))->method('getAttachments')->willReturn([]);

        $fileUid = 42;
        /** @var File|\PHPUnit_Framework_MockObject_MockObject $fileMock */
        $fileMock = $this->getMockBuilder(File::class)->disableOriginalConstructor()->getMock();
        $fileMock->method('getUid')->willReturn($fileUid);
        /** @var FileReference|\PHPUnit_Framework_MockObject_MockObject $fileReferenceMock */
        $fileReferenceMock = $this->getMockBuilder(FileReference::class)->disableOriginalConstructor()->getMock();
        $fileReferenceMock->method('getOriginalFile')->willReturn($fileMock);
        $this->realtyObjectMock->expects(self::at(8))->method('getAttachments')->willReturn([$fileReferenceMock]);

        $this->subject->startTransaction();
        $this->subject->addAttachment($fileName, 'some image');
        $this->subject->finishTransaction();

        $this->subject->startTransaction();

        $this->realtyObjectMock->expects(self::once())->method('removeAttachmentByFileUid')->with($fileUid);

        $this->subject->finishTransaction();
    }
}
