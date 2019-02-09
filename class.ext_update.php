<?php

use TYPO3\CMS\Core\Resource\DuplicationBehavior;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This class offers functions to update the database from the previous major extension version to the current one.
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class ext_update
{
    /**
     * the folder where uploaded images get stored.
     *
     * @var string
     */
    const UPLOAD_FOLDER = 'uploads/tx_realty/';

    /**
     * @var string[]
     */
    private $requiredExtensions = ['oelib', 'realty'];

    /**
     * @var string[]
     */
    private $requiredTables = ['tx_realty_objects', 'tx_realty_images', 'tx_realty_documents'];

    /**
     * @var string[][]
     */
    private $requiredColumns = [
        'tx_realty_objects' => ['attachments', 'images', 'documents'],
    ];

    /**
     * @var int[]
     */
    private $attachmentSortings = [];

    /**
     * Checks whether the update module needs to to anything
     *
     * @return bool
     */
    public function access()
    {
        return $this->extensionsAreInstalled() && $this->needsToUpdateAttachments();
    }

    /**
     * Returns the update module content.
     *
     * @return string
     *         the update module content, will be empty if nothing was updated
     */
    public function main()
    {
        if ($this->needsToUpdateAttachments()) {
            $output = $this->updateAttachments();
        } else {
            $output = '';
        }

        return $output;
    }

    /**
     * @return bool
     */
    private function needsToUpdateAttachments()
    {
        return $this->tablesExist() && $this->columnsExist() && $this->oldAttachmentsExist();
    }

    /**
     * @return bool
     */
    private function extensionsAreInstalled()
    {
        $result = true;
        foreach ($this->requiredExtensions as $key) {
            $result = $result && ExtensionManagementUtility::isLoaded($key);
        }

        return $result;
    }

    /**
     * @return bool
     */
    private function tablesExist()
    {
        $result = true;
        foreach ($this->requiredTables as $table) {
            $result = $result && \Tx_Oelib_Db::existsTable($table);
        }

        return $result;
    }

    /**
     * @return bool
     */
    private function columnsExist()
    {
        $result = true;
        foreach ($this->requiredColumns as $table => $columns) {
            foreach ($columns as $column) {
                $result = $result && \Tx_Oelib_Db::tableHasColumn($table, $column);
            }
        }

        return $result;
    }

    /**
     * @return bool
     */
    private function oldAttachmentsExist()
    {
        $numberOfAffectedObjects = \Tx_Oelib_Db::count(
            'tx_realty_objects',
            'deleted = 0 AND attachments = 0 AND (images > 0 OR documents > 0)'
        );

        return $numberOfAffectedObjects > 0;
    }

    /**
     * @return string
     */
    private function updateAttachments()
    {
        $output = '<h3>Converting the legacy images and documents to FAL attachments.</h3>';
        $realtyObjects = \Tx_Oelib_Db::selectMultiple(
            '*',
            'tx_realty_objects',
            'deleted = 0 AND attachments = 0 AND (images > 0 OR documents > 0)'
        );
        $output .= '<p>' . \count($realtyObjects) . ' objects are affected.</p>';

        /** @var array $realtyObject */
        foreach ($realtyObjects as $realtyObject) {
            $output .= $this->updateAttachmentForSingleObject($realtyObject);
        }

        return $output;
    }

    /**
     * @param array $realtyObject
     *
     * @return string
     */
    private function updateAttachmentForSingleObject(array $realtyObject)
    {
        $objectUid = (int)$realtyObject['uid'];
        $this->attachmentSortings[$objectUid] = 1;
        $output = '<h4>Processing object #' . $objectUid . '</h4>';

        $output .= $this->updateImagesForSingleObject($objectUid);
        $output .= $this->updateDocumentsForSingleObject($objectUid);

        \Tx_Oelib_Db::delete('tx_realty_images', 'object = ' . $objectUid);
        \Tx_Oelib_Db::delete('tx_realty_documents', 'object = ' . $objectUid);
        $numberOfImages = (int)$realtyObject['images'];
        $numberOfDocuments = (int)$realtyObject['documents'];
        \Tx_Oelib_Db::update(
            'tx_realty_objects',
            'uid = ' . $objectUid,
            ['attachments' => $numberOfImages + $numberOfDocuments, 'images' => 0, 'documents' => 0]
        );

        return $output;
    }

    /**
     * @param int $objectUid
     *
     * @return string
     */
    private function updateImagesForSingleObject($objectUid)
    {
        $images = \Tx_Oelib_Db::selectMultiple(
            '*',
            'tx_realty_images',
            'deleted = 0 AND hidden = 0 AND object = ' . $objectUid,
            '',
            'sorting ASC'
        );
        if (empty($images)) {
            return '';
        }

        $output = '<h5>Converting ' . \count($images) . ' image reference(s) to FAL …</h5>';
        foreach ($images as $image) {
            $file = $this->copyFile($image['image'], $objectUid, $image['caption']);
            if ($file === null) {
                continue;
            }
            $output .= '<p>Creating file record for path "' .
                \htmlspecialchars($file->getIdentifier(), ENT_COMPAT | ENT_HTML5) .
                '", file UID: ' . $file->getProperty('uid') . '</p>';
        }

        return $output;
    }

    /**
     * @param int $objectUid
     *
     * @return string
     */
    private function updateDocumentsForSingleObject($objectUid)
    {
        $documents = \Tx_Oelib_Db::selectMultiple(
            '*',
            'tx_realty_documents',
            'deleted = 0 AND object = ' . $objectUid,
            '',
            'sorting ASC'
        );
        if (empty($documents)) {
            return '';
        }

        $output = '<h5>Converting ' . \count($documents) . ' document reference(s) to FAL …</h5>';
        foreach ($documents as $document) {
            $file = $this->copyFile($document['filename'], $objectUid, $document['title']);
            if ($file === null) {
                continue;
            }
            $output .= '<p>Creating file record for path "' .
                \htmlspecialchars($file->getIdentifier(), ENT_COMPAT | ENT_HTML5) .
                '", file UID: ' . $file->getProperty('uid') . '</p>';
        }

        return $output;
    }

    /**
     * @return ResourceStorage
     */
    private function getDefaultStorage()
    {
        return $this->getResourceFactory()->getDefaultStorage();
    }

    /**
     * @return ResourceFactory
     */
    private function getResourceFactory()
    {
        return ResourceFactory::getInstance();
    }

    /**
     * @param string $fileName
     * @param int $objectUid
     * @param string $title
     *
     * @return File|null
     */
    private function copyFile($fileName, $objectUid, $title)
    {
        $absoluteFileName = $this->getAbsoluteRealtyUploadsFolder() . $fileName;
        if (!\is_file($absoluteFileName)) {
            return null;
        }

        $storage = $this->getDefaultStorage();
        $folderPath = 'realty_attachments/' . $objectUid . '/';
        if ($storage->hasFolder($folderPath)) {
            $folder = $storage->getFolder($folderPath);
        } else {
            $folder = $storage->createFolder($folderPath);
        }

        if ($folder->hasFile($fileName)) {
            $fileIdentifier = $folderPath . $fileName;
            /** @var File $file */
            $file = $this->getResourceFactory()
                ->getFileObjectByStorageAndIdentifier($storage->getUid(), $fileIdentifier);
        } else {
            /** @var File $file */
            $file = $storage->addFile(
                $absoluteFileName,
                $folder,
                $fileName,
                DuplicationBehavior::RENAME,
                false
            );
        }
        $fileUid = (int)$file->getProperty('uid');
        \Tx_Oelib_Db::update('sys_file_metadata', 'file = ' . $fileUid, ['title' => $title]);
        $where = 'deleted = 0 AND uid_local = ' . $fileUid . ' AND uid_foreign = ' . $objectUid .
            ' AND tablenames = "tx_realty_objects" AND fieldname = "attachments"';
        if (!\Tx_Oelib_Db::existsRecord('sys_file_reference', $where)) {
            $timestamp = (int)$GLOBALS['SIM_EXEC_TIME'];
            $referenceData = [
                'uid_local' => $fileUid,
                'uid_foreign' => $objectUid,
                'tablenames' => 'tx_realty_objects',
                'fieldname' => 'attachments',
                'table_local' => 'sys_file',
                'crdate' => $timestamp,
                'tstamp' => $timestamp,
                'sorting_foreign' => $this->attachmentSortings[$objectUid],
                'l10n_diffsource' => '',
            ];
            \Tx_Oelib_Db::insert('sys_file_reference', $referenceData);
            $this->attachmentSortings[$objectUid]++;
        }

        return $file;
    }

    /**
     * @return string including the trailing slash
     */
    private function getAbsoluteRealtyUploadsFolder()
    {
        return GeneralUtility::getFileAbsFileName(self::UPLOAD_FOLDER);
    }
}
