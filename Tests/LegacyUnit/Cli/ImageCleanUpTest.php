<?php

/**
 * Test case.
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class tx_realty_Cli_ImageCleanUpTest extends \Tx_Phpunit_TestCase
{
    /**
     * @var tx_realty_cli_ImageCleanUp
     */
    private $subject = null;

    /**
     * @var Tx_Oelib_TestingFramework
     */
    private $testingFramework = null;

    /**
     * @var string upload folder name
     */
    private $uploadFolder = 'uploads/tx_realty_test';

    protected function setUp()
    {
        $this->testingFramework = new Tx_Oelib_TestingFramework('tx_realty');
        $this->testingFramework->setUploadFolderPath(PATH_site);
        Tx_Oelib_MapperRegistry::getInstance()->activateTestingMode($this->testingFramework);

        $this->subject = new tx_realty_cli_ImageCleanUp();
        $this->uploadFolder = str_replace(
            PATH_site,
            '',
            $this->testingFramework->createDummyFolder($this->uploadFolder)
        );
        $this->subject->setTestMode($this->uploadFolder);
    }

    protected function tearDown()
    {
        $this->testingFramework->cleanUp();
    }

    ////////////////////////////////
    // Testing checkUploadFolder()
    ////////////////////////////////

    /**
     * @test
     */
    public function checkUploadFolderForExistingWritableFolderNotThrowsException()
    {
        $this->subject->checkUploadFolder();
    }

    /**
     * @test
     */
    public function checkUploadFolderForNonExistingWritableFolderThrowsException()
    {
        $this->expectException(\RuntimeException::class);

        $this->subject->setTestMode('uploads_tx_realty_test/no_folder');
        $this->subject->checkUploadFolder();
    }

    /////////////////////////////////////////
    // Testing hideUnusedImagesInDatabase()
    /////////////////////////////////////////

    /**
     * @test
     */
    public function hideUnusedImagesInDatabaseNotHidesImageReferencingEnabledRealtyRecord()
    {
        $this->testingFramework->createRecord(
            'tx_realty_images',
            [
                'object' => $this->testingFramework->createRecord(
                    'tx_realty_objects',
                    ['images' => 1]
                ),
            ]
        );

        $this->subject->hideUnusedImagesInDatabase();

        self::assertTrue(
            $this->testingFramework->existsExactlyOneRecord(
                'tx_realty_images',
                'hidden = 0'
            )
        );
    }

    /**
     * @test
     */
    public function hideUnusedImagesInDatabaseNotHidesImageReferencingHiddenRealtyRecord()
    {
        $this->testingFramework->createRecord(
            'tx_realty_images',
            [
                'object' => $this->testingFramework->createRecord(
                    'tx_realty_objects',
                    ['images' => 1, 'hidden' => 1]
                ),
            ]
        );

        $this->subject->hideUnusedImagesInDatabase();

        self::assertTrue(
            $this->testingFramework->existsExactlyOneRecord(
                'tx_realty_images',
                'hidden = 0'
            )
        );
    }

    /**
     * @test
     */
    public function hideUnusedImagesInDatabaseHidesImageReferencingDeletedRealtyRecord()
    {
        $this->testingFramework->createRecord(
            'tx_realty_images',
            [
                'object' => $this->testingFramework->createRecord(
                    'tx_realty_objects',
                    ['images' => 1, 'deleted' => 1]
                ),
            ]
        );

        $this->subject->hideUnusedImagesInDatabase();

        self::assertTrue(
            $this->testingFramework->existsExactlyOneRecord(
                'tx_realty_images',
                'hidden = 1'
            )
        );
    }

    /**
     * @test
     */
    public function hideUnusedImagesInDatabaseHidesImageReferencingNoRealtyRecord()
    {
        $this->testingFramework->createRecord('tx_realty_images');

        $this->subject->hideUnusedImagesInDatabase();

        self::assertTrue(
            $this->testingFramework->existsExactlyOneRecord(
                'tx_realty_images',
                'hidden = 1'
            )
        );
    }

    /**
     * @test
     */
    public function hideUnusedImagesInDatabaseHidesTwoImagesReferencingNoRealtyRecords()
    {
        $this->testingFramework->createRecord('tx_realty_images');
        $this->testingFramework->createRecord('tx_realty_images');

        $this->subject->hideUnusedImagesInDatabase();

        self::assertEquals(
            2,
            $this->testingFramework->countRecords(
                'tx_realty_images',
                'hidden = 1'
            )
        );
    }

    ////////////////////////////////////////
    // Testing deleteUnusedDocumentRecords
    ////////////////////////////////////////

    /**
     * @test
     */
    public function deleteUnusedDocumentRecordsKeepsDocumentReferencingNonDeletedRealtyRecord()
    {
        $this->testingFramework->createRecord(
            'tx_realty_documents',
            [
                'object' => $this->testingFramework->createRecord(
                    'tx_realty_objects',
                    ['documents' => 1]
                ),
            ]
        );

        $this->subject->deleteUnusedDocumentRecords();

        self::assertTrue(
            $this->testingFramework->existsExactlyOneRecord(
                'tx_realty_documents',
                'deleted = 0'
            )
        );
    }

    /**
     * @test
     */
    public function deleteUnusedDocumentRecordsKeepsDocumentReferencingHiddenRealtyRecord()
    {
        $this->testingFramework->createRecord(
            'tx_realty_documents',
            [
                'object' => $this->testingFramework->createRecord(
                    'tx_realty_objects',
                    ['documents' => 1, 'hidden' => 1]
                ),
            ]
        );

        $this->subject->deleteUnusedDocumentRecords();

        self::assertTrue(
            $this->testingFramework->existsExactlyOneRecord(
                'tx_realty_documents',
                'deleted = 0'
            )
        );
    }

    /**
     * @test
     */
    public function deleteUnusedDocumentRecordsDeletesDocumentReferencingDeletedRealtyRecord()
    {
        $this->testingFramework->createRecord(
            'tx_realty_documents',
            [
                'object' => $this->testingFramework->createRecord(
                    'tx_realty_objects',
                    ['documents' => 1, 'deleted' => 1]
                ),
            ]
        );

        $this->subject->deleteUnusedDocumentRecords();

        self::assertTrue(
            $this->testingFramework->existsExactlyOneRecord(
                'tx_realty_documents',
                'deleted = 1'
            )
        );
    }

    /**
     * @test
     */
    public function deleteUnusedDocumentRecordsDeletesDocumentReferencingNoRealtyRecord()
    {
        $this->testingFramework->createRecord('tx_realty_documents');

        $this->subject->deleteUnusedDocumentRecords();

        self::assertTrue(
            $this->testingFramework->existsExactlyOneRecord(
                'tx_realty_documents',
                'deleted = 1'
            )
        );
    }

    /**
     * @test
     */
    public function deleteUnusedDocumentRecordsDeletesTwoDocumentsReferencingNoRealtyRecords()
    {
        $this->testingFramework->createRecord('tx_realty_documents');
        $this->testingFramework->createRecord('tx_realty_documents');

        $this->subject->deleteUnusedDocumentRecords();

        self::assertEquals(
            2,
            $this->testingFramework->countRecords(
                'tx_realty_documents',
                'deleted = 1'
            )
        );
    }

    //////////////////////////////
    // Testing deleteUnusedFiles
    //////////////////////////////

    /**
     * @test
     */
    public function deleteUnusedFilesDeletesImageFileReferencedByDeletedImageRecord()
    {
        $fileName = $this->testingFramework->createDummyFile(
            $this->uploadFolder . '/image.jpg'
        );
        $this->testingFramework->createRecord(
            'tx_realty_images',
            ['deleted' => 1, 'image' => basename($fileName)]
        );

        $this->subject->deleteUnusedFiles();

        self::assertFileNotExists(
            $fileName
        );
    }

    /**
     * @test
     */
    public function deleteUnusedFilesDeletesImageFileReferencedByNoImageRecord()
    {
        $fileName = $this->testingFramework->createDummyFile(
            $this->uploadFolder . '/image.jpg'
        );

        $this->subject->deleteUnusedFiles();

        self::assertFileNotExists(
            $fileName
        );
    }

    /**
     * @test
     */
    public function deleteUnusedFilesKeepsImageFileReferencedByHiddenImageRecord()
    {
        $fileName = $this->testingFramework->createDummyFile(
            $this->uploadFolder . '/image.jpg'
        );
        $this->testingFramework->createRecord(
            'tx_realty_images',
            ['hidden' => 1, 'image' => basename($fileName)]
        );

        $this->subject->deleteUnusedFiles();

        self::assertFileExists(
            $fileName
        );
    }

    /**
     * @test
     */
    public function deleteUnusedFilesDeletesTextFile()
    {
        $fileName = $this->testingFramework->createDummyFile(
            $this->uploadFolder . '/test.txt'
        );

        $this->subject->deleteUnusedFiles();

        self::assertFileNotExists(
            $fileName
        );
    }

    /**
     * @test
     */
    public function deleteUnusedFilesKeepsImageFileReferencedByEnabledImageRecord()
    {
        $fileName = $this->testingFramework->createDummyFile(
            $this->uploadFolder . '/image.jpg'
        );
        $this->testingFramework->createRecord(
            'tx_realty_images',
            ['image' => basename($fileName)]
        );

        $this->subject->deleteUnusedFiles();

        self::assertFileExists(
            $fileName
        );
    }

    /**
     * @test
     */
    public function deleteUnusedFilesDeletesTwoImageFilesReferencedByNoImageRecords()
    {
        $fileName1 = $this->testingFramework->createDummyFile(
            $this->uploadFolder . '/image.jpg'
        );
        $fileName2 = $this->testingFramework->createDummyFile(
            $this->uploadFolder . '/image.jpg'
        );

        $this->subject->deleteUnusedFiles();

        self::assertFileNotExists(
            $fileName1
        );
        self::assertFileNotExists(
            $fileName2
        );
    }

    /**
     * @test
     */
    public function deleteUnusedFilesDeletesDocumentFileReferencedByDeletedDocumentRecord()
    {
        $fileName = $this->testingFramework->createDummyFile(
            $this->uploadFolder . '/document.pdf'
        );
        $this->testingFramework->createRecord(
            'tx_realty_documents',
            ['deleted' => 1, 'filename' => basename($fileName)]
        );

        $this->subject->deleteUnusedFiles();

        self::assertFileNotExists(
            $fileName
        );
    }

    /**
     * @test
     */
    public function deleteUnusedFilesDeletesDocumentFileReferencedByNoDocumentRecord()
    {
        $fileName = $this->testingFramework->createDummyFile(
            $this->uploadFolder . '/document.pdf'
        );

        $this->subject->deleteUnusedFiles();

        self::assertFileNotExists(
            $fileName
        );
    }

    /**
     * @test
     */
    public function deleteUnusedFilesKeepsDocumentFileReferencedByNonDeletedDocumentRecord()
    {
        $fileName = $this->testingFramework->createDummyFile(
            $this->uploadFolder . '/document.pdf'
        );
        $this->testingFramework->createRecord(
            'tx_realty_documents',
            ['filename' => basename($fileName)]
        );

        $this->subject->deleteUnusedFiles();

        self::assertFileExists(
            $fileName
        );
    }

    ////////////////////////////
    // Testing getStatistics()
    ////////////////////////////

    /**
     * @test
     */
    public function getStatisticsAfterDeletingFileReturnsFileDeletedInformation()
    {
        $this->testingFramework->createDummyFile(
            $this->uploadFolder . '/image.jpg'
        );

        $this->subject->deleteUnusedFiles();

        self::assertContains(
            'Files deleted: 1',
            $this->subject->getStatistics()
        );
    }

    /**
     * @test
     */
    public function getStatisticsAfterHidingAnImageRecordReturnsImageRecordDeletedInformation()
    {
        $this->testingFramework->createRecord('tx_realty_images');

        $this->subject->hideUnusedImagesInDatabase();

        self::assertContains(
            ', 1 of those were hidden',
            $this->subject->getStatistics()
        );
    }
}
