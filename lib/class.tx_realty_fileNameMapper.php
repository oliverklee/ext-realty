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
use TYPO3\CMS\Core\Utility\File\BasicFileUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This class maps file names to their unique correspondences in the file system.
 *
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 */
class tx_realty_fileNameMapper
{
    /**
     * associative array with the unique file name as key and the original file name as value
     *
     * @var string[]
     */
    private $fileNames = [];

    /**
     * @var string path of the folder in which to check whether a file exists
     */
    private $destinationPath = '';

    /**
     * @var BasicFileUtility
     */
    private static $fileFunctions = null;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->destinationPath = PATH_site . tx_realty_Model_Image::UPLOAD_FOLDER;
    }

    /**
     * Destructor.
     */
    public function __destruct()
    {
        unset($this->fileNames, $this->destinationPath);
    }

    /**
     * Returns the unique file name for the provided file name within the
     * destination directory and maps both names internally.
     *
     * @param string $fileName file name to receive the unique name for, must not be empty
     *
     * @return string unique file name, will not be empty
     */
    public function getUniqueFileNameAndMapIt($fileName)
    {
        $uniqueFileName = $this->getUniqueFileName($fileName);
        $this->fileNames[$uniqueFileName] = $fileName;

        return $uniqueFileName;
    }

    /**
     * Returns the unique file name for a provided file name by checking that
     * the name neither occurs in the destination folder nor in the internal
     * array yet. Also replaces any character not matching [.a-zA-Z0-9_-] by '_'
     * within the file name.
     *
     * The core's basic file functions class is not used to create the unique name
     * as it only can produce unique names for files which already exist in the
     * file system. Here, also the internal mapping has to be taken into account.
     *
     * @param string $originalFileName original file name, must not be empty
     *
     * @return string cleaned original file name extended with a unique suffix,
     *                will not be empty
     */
    private function getUniqueFileName($originalFileName)
    {
        $splittedFileName = GeneralUtility::split_fileref($originalFileName);
        $newFileName = $this->getCleanedFileNameBody(
                $splittedFileName['filebody']
            ) . '.' . $splittedFileName['realFileext'];

        while (isset($this->fileNames[$newFileName])
            || file_exists($this->destinationPath . $newFileName)
        ) {
            $this->createNewFileName($newFileName);
        }

        return $newFileName;
    }

    /**
     * Returns the given file name body with any character not matching
     * [.a-zA-Z0-9_-] replaced by '_'.
     *
     * @param string $fileNameBody file name body, must not be empty
     *
     * @return string cleaned file name body, will not be empty
     */
    private function getCleanedFileNameBody($fileNameBody)
    {
        if (self::$fileFunctions === null) {
            self::$fileFunctions = GeneralUtility::makeInstance(BasicFileUtility::class);
        }

        return self::$fileFunctions->cleanFileName($fileNameBody);
    }

    /**
     * Increases the appended number of the provided file name.
     *
     * @param string &$fileName file name, will be modified, must not empty
     *
     * @return void
     */
    private function createNewFileName(&$fileName)
    {
        $splittedFileName = GeneralUtility::split_fileref($fileName);

        $matches = [];
        preg_match('/^(.*)_([0-9]+)$/', $splittedFileName['filebody'], $matches);

        if (!empty($matches)) {
            $fileBodyWithoutSuffix = $matches[1];
            $suffixNumber = $matches[2];
        } else {
            $fileBodyWithoutSuffix = $splittedFileName['filebody'];
            $suffixNumber = -1;
        }

        $suffixNumber++;
        $fileName = $fileBodyWithoutSuffix . '_' .
            sprintf('%02d', $suffixNumber) . '.' .
            $splittedFileName['realFileext'];
    }

    /**
     * Returns the unique file names mapped for one original file name and
     * deletes the mappings for this name as they must not be used again to
     * ensure the uniqueness.
     *
     * @param string $originalFileName original file name, must not be empty
     *
     * @return string[] mapped unique file names for one original file name, will
     *               be empty if there were no mappings
     */
    public function releaseMappedFileNames($originalFileName)
    {
        $result = array_keys($this->fileNames, $originalFileName);

        foreach ($result as $usedFileName) {
            unset($this->fileNames[$usedFileName]);
        }

        return $result;
    }

    /**
     * Sets the destination folder where to check whether a file already exists.
     *
     * @param string $folder absolute path of the destination folder, must end with a trailing slash and must not be empty
     *
     * @return void
     */
    public function setDestinationFolder($folder)
    {
        $this->destinationPath = $folder;
    }
}
