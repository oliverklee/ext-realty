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
 * @author Saskia Metzler <saskia@merlin.owl.de>
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class tx_realty_Cli_ImageCleanUpTest extends Tx_Phpunit_TestCase
{
    /**
     * @var tx_realty_cli_ImageCleanUp
     */
    private $fixture = null;

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

        $this->fixture = new tx_realty_cli_ImageCleanUp();
        $this->uploadFolder = str_replace(
            PATH_site, '',
            $this->testingFramework->createDummyFolder($this->uploadFolder)
        );
        $this->fixture->setTestMode($this->uploadFolder);
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
        $this->fixture->checkUploadFolder();
    }

    /**
     * @test
     */
    public function checkUploadFolderForNonExistingWritableFolderThrowsException()
    {
        $this->setExpectedException(
            'RuntimeException',
            'The folder ' .  PATH_site . 'uploads_tx_realty_test/no_folder/ ' .
                'with the uploaded realty files does not exist. ' .
                'Please check your configuration and restart the clean-up.'
        );
        $this->fixture->setTestMode('uploads_tx_realty_test/no_folder');
        $this->fixture->checkUploadFolder();
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
            array('object' => $this->testingFramework->createRecord(
                'tx_realty_objects', array('images' => 1)
            ))
        );

        $this->fixture->hideUnusedImagesInDatabase();

        self::assertTrue(
            $this->testingFramework->existsExactlyOneRecord(
                'tx_realty_images', 'hidden = 0'
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
            array('object' => $this->testingFramework->createRecord(
                'tx_realty_objects', array('images' => 1, 'hidden' => 1)
            ))
        );

        $this->fixture->hideUnusedImagesInDatabase();

        self::assertTrue(
            $this->testingFramework->existsExactlyOneRecord(
                'tx_realty_images', 'hidden = 0'
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
            array('object' => $this->testingFramework->createRecord(
                'tx_realty_objects', array('images' => 1, 'deleted' => 1)
            ))
        );

        $this->fixture->hideUnusedImagesInDatabase();

        self::assertTrue(
            $this->testingFramework->existsExactlyOneRecord(
                'tx_realty_images', 'hidden = 1'
            )
        );
    }

    /**
     * @test
     */
    public function hideUnusedImagesInDatabaseHidesImageReferencingNoRealtyRecord()
    {
        $this->testingFramework->createRecord('tx_realty_images');

        $this->fixture->hideUnusedImagesInDatabase();

        self::assertTrue(
            $this->testingFramework->existsExactlyOneRecord(
                'tx_realty_images', 'hidden = 1'
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

        $this->fixture->hideUnusedImagesInDatabase();

        self::assertEquals(
            2,
            $this->testingFramework->countRecords(
                'tx_realty_images', 'hidden = 1'
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
            array('object' => $this->testingFramework->createRecord(
                'tx_realty_objects', array('documents' => 1)
            ))
        );

        $this->fixture->deleteUnusedDocumentRecords();

        self::assertTrue(
            $this->testingFramework->existsExactlyOneRecord(
                'tx_realty_documents', 'deleted = 0'
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
            array('object' => $this->testingFramework->createRecord(
                'tx_realty_objects', array('documents' => 1, 'hidden' => 1)
            ))
        );

        $this->fixture->deleteUnusedDocumentRecords();

        self::assertTrue(
            $this->testingFramework->existsExactlyOneRecord(
                'tx_realty_documents', 'deleted = 0'
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
            array('object' => $this->testingFramework->createRecord(
                'tx_realty_objects', array('documents' => 1, 'deleted' => 1)
            ))
        );

        $this->fixture->deleteUnusedDocumentRecords();

        self::assertTrue(
            $this->testingFramework->existsExactlyOneRecord(
                'tx_realty_documents', 'deleted = 1'
            )
        );
    }

    /**
     * @test
     */
    public function deleteUnusedDocumentRecordsDeletesDocumentReferencingNoRealtyRecord()
    {
        $this->testingFramework->createRecord('tx_realty_documents');

        $this->fixture->deleteUnusedDocumentRecords();

        self::assertTrue(
            $this->testingFramework->existsExactlyOneRecord(
                'tx_realty_documents', 'deleted = 1'
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

        $this->fixture->deleteUnusedDocumentRecords();

        self::assertEquals(
            2,
            $this->testingFramework->countRecords(
                'tx_realty_documents', 'deleted = 1'
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
            array('deleted' => 1, 'image' => basename($fileName))
        );

        $this->fixture->deleteUnusedFiles();

        self::assertFalse(
            file_exists($fileName)
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

        $this->fixture->deleteUnusedFiles();

        self::assertFalse(
            file_exists($fileName)
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
            array('hidden' =>  1, 'image' => basename($fileName))
        );

        $this->fixture->deleteUnusedFiles();

        self::assertTrue(
            file_exists($fileName)
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

        $this->fixture->deleteUnusedFiles();

        self::assertFalse(
            file_exists($fileName)
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
            'tx_realty_images', array('image' => basename($fileName))
        );

        $this->fixture->deleteUnusedFiles();

        self::assertTrue(
            file_exists($fileName)
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

        $this->fixture->deleteUnusedFiles();

        self::assertFalse(
            file_exists($fileName1)
        );
        self::assertFalse(
            file_exists($fileName2)
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
            array('deleted' => 1, 'filename' => basename($fileName))
        );

        $this->fixture->deleteUnusedFiles();

        self::assertFalse(
            file_exists($fileName)
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

        $this->fixture->deleteUnusedFiles();

        self::assertFalse(
            file_exists($fileName)
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
            'tx_realty_documents', array('filename' => basename($fileName))
        );

        $this->fixture->deleteUnusedFiles();

        self::assertTrue(
            file_exists($fileName)
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

        $this->fixture->deleteUnusedFiles();

        self::assertContains(
            'Files deleted: 1',
            $this->fixture->getStatistics()
        );
    }

    /**
     * @test
     */
    public function getStatisticsAfterHidingAnImageRecordReturnsImageRecordDeletedInformation()
    {
        $this->testingFramework->createRecord('tx_realty_images');

        $this->fixture->hideUnusedImagesInDatabase();

        self::assertContains(
            ', 1 of those were hidden',
            $this->fixture->getStatistics()
        );
    }
}
