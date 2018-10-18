<?php

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This class removes unused files from the realty upload folder.
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class tx_realty_cli_ImageCleanUp
{
    /**
     * @var string additional WHERE clause, used for testing
     */
    private $additionalWhereClause = '';

    /**
     * @var string upload folder, relative to PATH_site
     */
    private $uploadFolder = \tx_realty_Model_Image::UPLOAD_FOLDER;

    /**
     * associative array with statistical information collected during clean-up
     *
     * @var string[]
     */
    private $statistics = [];

    /**
     * Checks whether the Realty upload folder exists and is writable.
     *
     * @throws \RuntimeException if the upload folder does not exist
     * @throws \Tx_Oelib_Exception_AccessDenied if the upload folder is not writable
     *
     * @return void
     */
    public function checkUploadFolder()
    {
        $absolutePath = PATH_site . $this->uploadFolder;
        if (!@is_dir($absolutePath)) {
            throw new \RuntimeException(
                'The folder ' . $absolutePath . ' with the uploaded realty files does not exist. ' .
                'Please check your configuration and restart the clean-up.',
                1333035462
            );
        }
        if (!@is_writable($absolutePath)) {
            $ownerUid = fileowner($absolutePath);
            $owner = posix_getpwuid($ownerUid);

            throw new \Tx_Oelib_Exception_AccessDenied(
                'The folder ' . $absolutePath . ' is not writable. Please fix file permissions and restart' .
                ' the import. The folder belongs to the user: ' . $owner['name'] . ', ' . $ownerUid .
                ', and has the following permissions: ' . substr(decoct(fileperms($absolutePath)), 2) .
                '. The user starting this import was: ' . get_current_user() . '.',
                1333035471
            );
        }
    }

    /**
     * Hides unused images in the database. Images in the database are
     * considered as unused if there is no non-deleted realty record related to
     * this image.
     *
     * @return void
     */
    public function hideUnusedImagesInDatabase()
    {
        $nonDeletedRealtyRecordUids = $this->retrieveRealtyObjectUids();
        $imagesForRealtyRecords = ($nonDeletedRealtyRecordUids === '')
            ? []
            : \Tx_Oelib_Db::selectColumnForMultiple(
                'uid',
                'tx_realty_images',
                'object IN (' . $nonDeletedRealtyRecordUids . ')' .
                \Tx_Oelib_Db::enableFields('tx_realty_images', 1) .
                $this->additionalWhereClause
            );
        $this->addToStatistics(
            'Enabled image records with references to realty records',
            count($imagesForRealtyRecords)
        );

        $numberOfImagesHidden = \Tx_Oelib_Db::update(
            'tx_realty_images',
            (
            empty($imagesForRealtyRecords)
                ? '1=1'
                : 'uid NOT IN (' . implode(',', $imagesForRealtyRecords) . ')'
            ) . $this->additionalWhereClause,
            ['hidden' => 1]
        );
        $hiddenImageRecords = \Tx_Oelib_Db::selectSingle(
            'COUNT(*) AS number',
            'tx_realty_images',
            'hidden = 1 OR deleted = 1' . $this->additionalWhereClause
        );
        $this->addToStatistics(
            'Total hidden or deleted image records',
            $hiddenImageRecords['number'] . ', ' . $numberOfImagesHidden .
            ' of those were hidden during this run (due to missing' .
            ' corresponding realty records).'
        );
    }

    /**
     * Deletes unused document records. Documents in the database are
     * considered as unused if there is no non-deleted realty record related to
     * this document.
     *
     * @return void
     */
    public function deleteUnusedDocumentRecords()
    {
        $nonDeletedRealtyRecordUids = $this->retrieveRealtyObjectUids();
        $documentsWithRealtyRecords = ($nonDeletedRealtyRecordUids === '')
            ? []
            : \Tx_Oelib_Db::selectColumnForMultiple(
                'uid',
                'tx_realty_documents',
                'object IN (' . $nonDeletedRealtyRecordUids . ')' .
                \Tx_Oelib_Db::enableFields('tx_realty_documents', 1) .
                $this->additionalWhereClause
            );
        $this->addToStatistics(
            'Enabled document records with references to realty records',
            count($documentsWithRealtyRecords)
        );

        $numberOfDeletedDocumentRecords = \Tx_Oelib_Db::update(
            'tx_realty_documents',
            (
            empty($documentsWithRealtyRecords)
                ? '1=1'
                : 'uid NOT IN (' . implode(',', $documentsWithRealtyRecords) . ')'
            ) . $this->additionalWhereClause,
            ['deleted' => 1]
        );
        $this->addToStatistics(
            'Total deleted document records',
            $numberOfDeletedDocumentRecords
        );
    }

    /**
     * Gets a comma-separated list of UIDs of non-deleted (but potentially
     * hidden) realty records in the database.
     *
     * @return string
     *         comma-separated list of UIDs, will be empty if there are no
     *         matching records
     */
    private function retrieveRealtyObjectUids()
    {
        $uids = \Tx_Oelib_Db::selectColumnForMultiple(
            'uid',
            'tx_realty_objects',
            '1=1' . \Tx_Oelib_Db::enableFields('tx_realty_objects', 1) .
            $this->additionalWhereClause
        );

        return implode(',', $uids);
    }

    /**
     * Deletes all files from the realty upload folder which do not have a
     * corresponding image or document record.
     * (Subfolders, such as /rte, remain untouched.)
     *
     * @return void
     */
    public function deleteUnusedFiles()
    {
        $absolutePath = PATH_site . $this->uploadFolder;
        $filesInUploadFolder = GeneralUtility::getFilesInDir($absolutePath);
        $this->addToStatistics(
            'Files in upload folder',
            count($filesInUploadFolder)
        );

        $imageFileNamesInDatabase = \Tx_Oelib_Db::selectColumnForMultiple(
            'image',
            'tx_realty_images',
            '1=1' . \Tx_Oelib_Db::enableFields('tx_realty_images', 1) .
            $this->additionalWhereClause
        );
        $this->addToStatistics(
            'Files with corresponding image record',
            count($imageFileNamesInDatabase)
        );

        $documentFileNamesInDatabase = \Tx_Oelib_Db::selectColumnForMultiple(
            'filename',
            'tx_realty_documents',
            '1=1' . \Tx_Oelib_Db::enableFields('tx_realty_documents', 1) .
            $this->additionalWhereClause
        );
        $this->addToStatistics(
            'Files with corresponding document record',
            count($documentFileNamesInDatabase)
        );

        $filesToDelete = array_diff(
            $filesInUploadFolder,
            $imageFileNamesInDatabase,
            $documentFileNamesInDatabase
        );
        $this->addToStatistics('Files deleted', count($filesToDelete));
        foreach ($filesToDelete as $image) {
            unlink($absolutePath . $image);
        }

        $filesOnlyInDatabase = array_diff(
            array_merge($imageFileNamesInDatabase, $documentFileNamesInDatabase),
            $filesInUploadFolder
        );
        $numberOfFilesOnlyInDatabase = count($filesOnlyInDatabase);
        $this->addToStatistics(
            'Image and documents records without image file',
            $numberOfFilesOnlyInDatabase . (($numberOfFilesOnlyInDatabase > 0)
                ? ', file names: ' . LF . TAB .
                implode(LF . TAB, $filesOnlyInDatabase)
                : '')
        );
    }

    /**
     * Returns collected statistics.
     *
     * @return string statistical information collected during clean-up
     */
    public function getStatistics()
    {
        return 'Clean-up results:' . LF . implode(LF, $this->statistics);
    }

    /**
     * Stores an information about statistics.
     *
     * @param string $title title of the entry to add, must not be empty
     * @param string $value value to be added to the statistics, must not be empty
     *
     * @return void
     */
    private function addToStatistics($title, $value)
    {
        $this->statistics[] = $title . ': ' . $value;
    }

    /**
     * Sets the test mode.
     *
     * @param string $uploadFolder
     *               folder to use as upload folder for testing, must be
     *               relative to PATH_site, a trailing slash will be appended,
     *               must not be empty
     *
     * @return void
     */
    public function setTestMode($uploadFolder)
    {
        $this->uploadFolder = $uploadFolder . '/';
        $this->additionalWhereClause = ' AND is_dummy_record = 1';
    }
}
