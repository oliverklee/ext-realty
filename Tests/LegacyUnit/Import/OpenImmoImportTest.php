<?php

use TYPO3\CMS\Core\Cache\Backend\TaggableBackendInterface;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\AbstractFrontend as AbstractCacheFrontEnd;
use TYPO3\CMS\Core\Mail\MailMessage;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Lang\LanguageService;

/**
 * Test case.
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class tx_realty_Import_OpenImmoImportTest extends \Tx_Phpunit_TestCase
{
    /**
     * @var tx_realty_openImmoImportChild
     */
    private $fixture = null;

    /**
     * @var Tx_Oelib_TestingFramework
     */
    private $testingFramework = null;

    /**
     * @var Tx_Oelib_ConfigurationProxy
     */
    private $globalConfiguration = null;

    /**
     * @var tx_realty_translator
     */
    private $translator = null;

    /**
     * @var int PID of the system folder where imported records will be stored
     */
    private $systemFolderPid = 0;

    /**
     * @var string path to the import folder
     */
    private $importFolder = '';

    /**
     * @var bool whether an import folder has been created
     */
    private $testImportFolderExists = false;

    /**
     * backup of $GLOBALS['TYPO3_CONF_VARS']['GFX']
     *
     * @var array
     */
    private $graphicsConfigurationBackup = [];

    /**
     * backup of $GLOBALS['TYPO3_CONF_VARS']['MAIL']
     *
     * @var array
     */
    private $emailConfigurationBackup = [];

    /**
     * @var MailMessage
     */
    private $message = null;

    /**
     * @var LanguageService|null
     */
    private $languageServiceBackup = null;

    protected function setUp()
    {
        $this->graphicsConfigurationBackup = $GLOBALS['TYPO3_CONF_VARS']['GFX'];
        $this->emailConfigurationBackup = $GLOBALS['TYPO3_CONF_VARS']['MAIL'];

        $this->languageServiceBackup = $this->getLanguageService();
        $GLOBALS['LANG'] = new LanguageService();

        $this->testingFramework = new Tx_Oelib_TestingFramework('tx_realty');
        $this->systemFolderPid = $this->testingFramework->createSystemFolder();
        $this->importFolder = PATH_site . 'typo3temp/tx_realty_fixtures/';
        GeneralUtility::mkdir_deep($this->importFolder);

        Tx_Oelib_MapperRegistry::getInstance()->activateTestingMode($this->testingFramework);

        $this->globalConfiguration = Tx_Oelib_ConfigurationProxy::getInstance('realty');

        $this->translator = new tx_realty_translator();

        $this->fixture = new tx_realty_openImmoImportChild(true);
        $this->setupStaticConditions();

        $this->message = $this->getMock(MailMessage::class, ['send']);
        GeneralUtility::addInstance(MailMessage::class, $this->message);
    }

    protected function tearDown()
    {
        // Get any surplus instances added via GeneralUtility::addInstance.
        GeneralUtility::makeInstance(MailMessage::class);

        $this->testingFramework->cleanUp();
        $this->deleteTestImportFolder();

        tx_realty_cacheManager::purgeCacheManager();
        $GLOBALS['LANG'] = $this->languageServiceBackup;
        $GLOBALS['TYPO3_CONF_VARS']['GFX'] = $this->graphicsConfigurationBackup;
        $GLOBALS['TYPO3_CONF_VARS']['MAIL'] = $this->emailConfigurationBackup;
    }

    /*
     * Utility functions.
     */

    /**
     * Sets the global configuration values which need to be static during the
     * tests.
     *
     * @return void
     */
    private function setupStaticConditions()
    {
        // avoids using the extension's real upload folder
        $this->fixture->setUploadDirectory($this->importFolder);

        // TYPO3 default configuration
        $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'] = 'gif,jpg,jpeg,tif,tiff,bmp,pcx,tga,png,pdf,ai';
        $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress'] = 'sender@example.com';
        $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromName'] = 'import sender';

        $this->globalConfiguration->setAsString('emailAddress', 'default-recipient@example.com');
        $this->globalConfiguration->setAsBoolean('onlyErrors', false);
        $this->globalConfiguration->setAsString('openImmoSchema', $this->importFolder . 'schema.xsd');
        $this->globalConfiguration->setAsString('importFolder', $this->importFolder);
        $this->globalConfiguration->setAsBoolean('deleteZipsAfterImport', true);
        $this->globalConfiguration->setAsBoolean('importCanDeleteRecordsForFullSync', false);
        $this->globalConfiguration->setAsBoolean('notifyContactPersons', true);
        $this->globalConfiguration->setAsInteger('pidForRealtyObjectsAndImages', $this->systemFolderPid);
        $this->globalConfiguration->setAsBoolean('useFrontEndUserDataAsContactDataForImportedRecords', false);
        $this->globalConfiguration->setAsBoolean('onlyImportForRegisteredFrontEndUsers', false);
        $this->globalConfiguration->setAsString('allowedFrontEndUserGroups', '');
        $this->globalConfiguration->setAsString(
            'emailTemplate',
            'EXT:realty/Resources/Private/Templates/Email/Notification.txt'
        );
    }

    /**
     * Disables the XML validation.
     *
     * @return void
     */
    private function disableValidation()
    {
        $this->globalConfiguration->setAsString('openImmoSchema', '');
    }

    /**
     * Copies a file or a folder from the extension's Tests/LegacyUnit/fixtures/ folder
     * into the temporary test import folder.
     *
     * @param string $fileName
     *        File or folder to copy. Must be a relative path to existent files within the Tests/LegacyUnit/fixtures/
     *     folder. Leave empty to create an empty import folder.
     * @param string $newFileName
     *        new file name in case it should be different from the original one, may be empty
     *
     * @return void
     */
    private function copyTestFileIntoImportFolder($fileName, $newFileName = '')
    {
        // creates an import folder if there is none
        if (!is_dir($this->importFolder)) {
            GeneralUtility::mkdir($this->importFolder);
        }
        $this->testImportFolderExists = true;

        if ($fileName !== '') {
            copy(
                ExtensionManagementUtility::extPath('realty') . 'Tests/LegacyUnit/fixtures/tx_realty_fixtures/'
                . $fileName,
                $this->importFolder . (($newFileName !== '') ? $newFileName : basename($fileName))
            );
        }
    }

    /**
     * Deletes the test import folder if it has been created during the tests.
     * Otherwise does nothing.
     *
     * @return void
     */
    private function deleteTestImportFolder()
    {
        if ($this->testImportFolderExists) {
            GeneralUtility::rmdir($this->importFolder, true);
            $this->testImportFolderExists = false;
        }
    }

    /**
     * Checks if the ZIPArchive class is available. If it is not available, the
     * current test will be marked as skipped.
     *
     * @return void
     */
    private function checkForZipArchive()
    {
        if (!in_array('zip', get_loaded_extensions(), true)) {
            self::markTestSkipped('This PHP installation does not provide the ZIPArchive class.');
        }
    }

    /**
     * Creates a ZIP "import.zip" with an xml file "import.xml" with $xml in it.
     *
     * @param string $xml
     *
     * @return void
     */
    private function createZipFile($xml)
    {
        $zip = new \ZipArchive();
        $zip->open($this->importFolder . 'import.zip', \ZipArchive::CREATE);
        $zip->addFromString('import.xml', $xml);
        $zip->close();
    }

    /**
     * @return LanguageService
     */
    private function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }

    /*
     * Tests concerning the ZIP extraction.
     */

    /**
     * @test
     */
    public function getPathsOfZipsToExtract()
    {
        $this->copyTestFileIntoImportFolder('foo.zip');
        $this->copyTestFileIntoImportFolder('bar.zip');

        self::assertSame(
            glob($this->importFolder . '*.zip'),
            array_values($this->fixture->getPathsOfZipsToExtract($this->importFolder))
        );
    }

    /**
     * @test
     */
    public function getNameForExtractionFolder()
    {
        $this->copyTestFileIntoImportFolder('bar.zip');

        self::assertSame(
            'bar/',
            $this->fixture->getNameForExtractionFolder('bar.zip')
        );
    }

    /**
     * @test
     */
    public function unifyPathDoesNotChangeCorrectPath()
    {
        self::assertSame(
            'correct/path/',
            $this->fixture->unifyPath('correct/path/')
        );
    }

    /**
     * @test
     */
    public function unifyPathTrimsAndAddsNecessarySlash()
    {
        self::assertSame(
            'incorrect/path/',
            $this->fixture->unifyPath('incorrect/path')
        );
    }

    /**
     * @test
     */
    public function createExtractionFolderForExistingZip()
    {
        $this->copyTestFileIntoImportFolder('foo.zip');
        $dirName = $this->fixture->createExtractionFolder($this->importFolder . 'foo.zip');

        self::assertTrue(
            is_dir($this->importFolder . 'foo/')
        );
        self::assertSame(
            $this->importFolder . 'foo/',
            $dirName
        );
    }

    /**
     * @test
     */
    public function createExtractionFolderForNonExistingZip()
    {
        $this->copyTestFileIntoImportFolder('');
        $dirName = $this->fixture->createExtractionFolder($this->importFolder . 'foobar.zip');

        self::assertFalse(
            is_dir($this->importFolder . 'foobar/')
        );
        self::assertSame(
            '',
            $dirName
        );
    }

    /**
     * @test
     */
    public function extractZipIfOneZipToExtractExists()
    {
        $this->checkForZipArchive();

        $this->copyTestFileIntoImportFolder('foo.zip');
        $this->fixture->extractZip($this->importFolder . 'foo.zip');

        self::assertTrue(
            is_dir($this->importFolder . 'foo/')
        );
    }

    /**
     * @test
     */
    public function extractZipIfZipDoesNotExist()
    {
        $this->copyTestFileIntoImportFolder('');
        $this->fixture->extractZip($this->importFolder . 'foobar.zip');

        self::assertFalse(
            is_dir($this->importFolder . 'foobar/')
        );
    }

    /**
     * @test
     */
    public function getPathForXmlIfFolderWithOneXmlExists()
    {
        $this->checkForZipArchive();

        $this->copyTestFileIntoImportFolder('foo.zip');
        $this->fixture->extractZip($this->importFolder . 'foo.zip');

        self::assertSame(
            $this->importFolder . 'foo/foo.xml',
            $this->fixture->getPathForXml($this->importFolder . 'foo.zip')
        );
    }

    /**
     * @test
     */
    public function getPathForXmlIfFolderNotExists()
    {
        $this->copyTestFileIntoImportFolder('foo.zip');

        self::assertSame(
            '',
            $this->fixture->getPathForXml($this->importFolder . 'foo.zip')
        );
    }

    /**
     * @test
     */
    public function getPathForXmlIfFolderWithTwoXmlExists()
    {
        $this->checkForZipArchive();

        $this->copyTestFileIntoImportFolder('bar-bar.zip');
        $this->fixture->extractZip($this->importFolder . 'bar-bar.zip');

        self::assertSame(
            '',
            $this->fixture->getPathForXml($this->importFolder . 'bar-bar.zip')
        );
    }

    /**
     * @test
     */
    public function getPathForXmlIfFolderWithoutXmlExists()
    {
        $this->checkForZipArchive();

        $this->copyTestFileIntoImportFolder('empty.zip');
        $this->fixture->extractZip($this->importFolder . 'empty.zip');

        self::assertSame(
            '',
            $this->fixture->getPathForXml($this->importFolder . 'empty.zip')
        );
    }

    /**
     * @test
     */
    public function createZipFileCreatesFile()
    {
        $path = $this->importFolder . 'import.zip';
        $xml = '<openimmo></openimmo>';

        $this->createZipFile($xml);

        self::assertFileExists($path);
    }

    /**
     * @test
     */
    public function createZipFilePutsXmlContentInZipFile()
    {
        $path = $this->importFolder . 'import.zip';
        $xml = '<openimmo></openimmo>';

        $this->createZipFile($xml);

        $zip = new \ZipArchive();
        $zip->open($path);
        $zip->extractTo($this->importFolder);
        $zip->close();

        self::assertFileExists($this->importFolder . 'import.xml');
        self::assertStringEqualsFile($this->importFolder . 'import.xml', $xml);
    }

    ////////////////////////////////////////////////////////////
    // Tests concerning copyImagesAndDocumentsFromExtractedZip
    ////////////////////////////////////////////////////////////

    /**
     * @test
     */
    public function copyImagesAndDocumentsFromExtractedZipCopiesJpgImagesIntoTheUploadFolder()
    {
        $this->checkForZipArchive();

        $this->copyTestFileIntoImportFolder('foo.zip');
        $this->fixture->importFromZip();

        self::assertFileExists($this->importFolder . 'foo.jpg');
        self::assertFileExists($this->importFolder . 'bar.jpg');
    }

    /**
     * @test
     */
    public function copyImagesAndDocumentsFromExtractedZipCopiesPdfFilesIntoTheUploadFolder()
    {
        $this->checkForZipArchive();

        $this->copyTestFileIntoImportFolder('pdf.zip');
        $this->fixture->importFromZip();

        self::assertFileExists($this->importFolder . 'foo.pdf');
    }

    /**
     * @test
     */
    public function copyImagesAndDocumentsFromExtractedZipNotCopiesPsFilesIntoTheUploadFolder()
    {
        $this->checkForZipArchive();

        $this->copyTestFileIntoImportFolder('ps.zip');
        $this->fixture->importFromZip();

        self::assertFileNotExists($this->importFolder . 'foo.ps');
    }

    /**
     * @test
     */
    public function copyImagesAndDocumentsFromExtractedZipCopiesJpgImagesWithUppercasedExtensionsIntoTheUploadFolder()
    {
        $this->checkForZipArchive();

        $this->copyTestFileIntoImportFolder('foo-uppercased.zip');
        $this->fixture->importFromZip();

        self::assertFileExists($this->importFolder . 'foo.JPG');
    }

    /**
     * @test
     */
    public function copyImagesAndDocumentsFromExtractedZipTwiceCopiesImagesUniquelyNamedIntoTheUploadFolder()
    {
        $this->checkForZipArchive();

        $this->copyTestFileIntoImportFolder('foo.zip');
        $this->copyTestFileIntoImportFolder('foo.zip', 'foo2.zip');
        $this->fixture->importFromZip();

        self::assertFileExists($this->importFolder . 'foo.jpg');
        self::assertFileExists($this->importFolder . 'foo_00.jpg');
    }

    /**
     * @test
     */
    public function copyImagesAndDocumentsFromExtractedZipCopiesImagesForRealtyRecord()
    {
        $this->checkForZipArchive();

        $this->copyTestFileIntoImportFolder('foo.zip');
        $this->fixture->importFromZip();

        self::assertFileExists($this->importFolder . 'foo.jpg');
        self::assertFileExists($this->importFolder . 'bar.jpg');
    }

    /**
     * @test
     */
    public function copyImagesAndDocumentsFromExtractedZipNotCopiesImagesForRecordWithDeletionFlagSet()
    {
        $this->checkForZipArchive();

        $this->copyTestFileIntoImportFolder('foo-deleted.zip');
        $this->fixture->importFromZip();

        self::assertFileNotExists($this->importFolder . 'foo.jpg');
        self::assertFileNotExists($this->importFolder . 'bar.jpg');
    }

    ////////////////////////////////
    // Tests concerning cleanUp().
    ////////////////////////////////

    /**
     * @test
     */
    public function cleanUpRemovesAFolderCreatedByTheImporter()
    {
        $this->copyTestFileIntoImportFolder('foo.zip');
        $this->fixture->createExtractionFolder($this->importFolder . 'foo.zip');
        $this->fixture->cleanUp($this->importFolder);

        self::assertFalse(
            is_dir($this->importFolder . 'foo/')
        );
    }

    /**
     * @test
     */
    public function cleanUpDoesNotRemoveAForeignFolderAlthoughItIsNamedLikeAZipToImport()
    {
        $this->copyTestFileIntoImportFolder('foo.zip');
        GeneralUtility::mkdir($this->importFolder . 'foo/');
        $this->fixture->cleanUp($this->importFolder);

        self::assertTrue(
            is_dir($this->importFolder . 'foo/')
        );
    }

    /**
     * @test
     */
    public function cleanUpDoesNotRemoveZipThatIsNotMarkedAsDeletable()
    {
        $this->copyTestFileIntoImportFolder('foo.zip');
        $this->fixture->cleanUp($this->importFolder . 'foo.zip');

        self::assertFileExists($this->importFolder . 'foo.zip');
    }

    /**
     * @test
     */
    public function cleanUpRemovesCreatedFolderAlthoughTheExtractedArchiveContainsAFolder()
    {
        $this->checkForZipArchive();

        $this->copyTestFileIntoImportFolder('contains-folder.zip');
        $this->fixture->importFromZip();

        self::assertFalse(
            is_dir($this->importFolder . 'contains-folder/')
        );
    }

    /**
     * @test
     */
    public function cleanUpDoesNotRemovesZipWithOneXmlInItIfDeletingZipsIsDisabled()
    {
        $this->checkForZipArchive();
        $this->testingFramework->markTableAsDirty('tx_realty_objects');
        $this->testingFramework->markTableAsDirty('tx_realty_house_types');

        $this->globalConfiguration->setAsBoolean('deleteZipsAfterImport', false);
        $this->copyTestFileIntoImportFolder('same-name.zip');
        $this->fixture->importFromZip();

        self::assertFileExists($this->importFolder . 'same-name.zip');
    }

    /**
     * @test
     */
    public function cleanUpRemovesZipWithOneXmlInItIfDeletingZipsIsEnabled()
    {
        $this->checkForZipArchive();
        $this->testingFramework->markTableAsDirty('tx_realty_objects');
        $this->testingFramework->markTableAsDirty('tx_realty_house_types');

        // 'deleteZipsAfterImport' is set to TRUE during setUp()
        $this->copyTestFileIntoImportFolder('same-name.zip');
        $this->fixture->importFromZip();

        self::assertFileNotExists($this->importFolder . 'same-name.zip');
    }

    /**
     * @test
     */
    public function cleanUpDoesNotRemoveZipWithoutXmls()
    {
        $this->checkForZipArchive();

        $this->copyTestFileIntoImportFolder('empty.zip');
        $this->fixture->importFromZip();

        self::assertFileExists($this->importFolder . 'empty.zip');
    }

    /**
     * @test
     */
    public function cleanUpDoesNotRemoveZipWithTwoXmls()
    {
        $this->checkForZipArchive();

        $this->copyTestFileIntoImportFolder('bar-bar.zip');
        $this->fixture->importFromZip();

        self::assertFileExists($this->importFolder . 'bar-bar.zip');
    }

    /**
     * @test
     */
    public function cleanUpRemovesZipFileInASubFolderOfTheImportFolder()
    {
        $this->checkForZipArchive();
        $this->testingFramework->markTableAsDirty('tx_realty_objects');
        $this->testingFramework->markTableAsDirty('tx_realty_house_types');

        // just to ensure the import folder exists
        $this->copyTestFileIntoImportFolder('empty.zip');
        // copyTestFileIntoImportFolder() cannot copy folders
        GeneralUtility::mkdir($this->importFolder . 'changed-copy-of-same-name/');
        copy(
            ExtensionManagementUtility::extPath('realty') . 'Tests/LegacyUnit/fixtures/tx_realty_fixtures/'
            . 'changed-copy-of-same-name/same-name.zip',
            $this->importFolder . 'changed-copy-of-same-name/same-name.zip'
        );

        $this->fixture->importFromZip();

        self::assertFileNotExists($this->importFolder . 'changed-copy-of-same-name/same-name.zip');
    }

    /**
     * @test
     */
    public function cleanUpDoesNotRemoveZipOfUnregisteredOwnerIfOwnerRestrictionIsEnabled()
    {
        $this->checkForZipArchive();
        $this->testingFramework->markTableAsDirty('tx_realty_objects');

        // 'deleteZipsAfterImport' is set to TRUE during setUp()
        $this->globalConfiguration->setAsBoolean('onlyImportForRegisteredFrontEndUsers', true);
        $this->copyTestFileIntoImportFolder('same-name.zip');
        $this->fixture->importFromZip();

        self::assertFileExists($this->importFolder . 'same-name.zip');
    }

    /**
     * @test
     */
    public function cleanUpRemovesZipOfRegisteredOwnerIfOwnerRestrictionIsEnabled()
    {
        $this->checkForZipArchive();
        $this->testingFramework->markTableAsDirty('tx_realty_objects');
        $this->testingFramework->markTableAsDirty('tx_realty_house_types');

        $this->testingFramework->createFrontEndUser('', ['tx_realty_openimmo_anid' => 'foo']);
        // 'deleteZipsAfterImport' is set to TRUE during setUp()
        $this->globalConfiguration->setAsBoolean('onlyImportForRegisteredFrontEndUsers', true);
        $this->copyTestFileIntoImportFolder('same-name.zip');
        $this->fixture->importFromZip();

        self::assertFileNotExists($this->importFolder . 'same-name.zip');
    }

    /**
     * @test
     */
    public function cleanUpDoesNotRemoveZipIfOwnerWhichHasReachedObjectLimitDuringImport()
    {
        $this->checkForZipArchive();
        $this->testingFramework->markTableAsDirty('tx_realty_objects');
        $this->testingFramework->markTableAsDirty('tx_realty_house_types');

        $feUserGroupUid = $this->testingFramework->createFrontEndUserGroup();
        $this->testingFramework->createFrontEndUser(
            $feUserGroupUid,
            [
                'tx_realty_openimmo_anid' => 'foo',
                'tx_realty_maximum_objects' => 1,
            ]
        );

        $this->globalConfiguration->setAsString('allowedFrontEndUserGroups', $feUserGroupUid);
        $this->globalConfiguration->setAsBoolean('useFrontEndUserDataAsContactDataForImportedRecords', true);
        $this->globalConfiguration->setAsBoolean('onlyImportForRegisteredFrontEndUsers', true);

        $this->copyTestFileIntoImportFolder('two-objects.zip');
        $this->fixture->importFromZip();

        self::assertFileExists($this->importFolder . 'two-objects.zip');
    }

    /**
     * @test
     */
    public function cleanUpDoesNotRemoveIfZipOwnerWhichHasNoObjectsLeftToEnter()
    {
        $this->checkForZipArchive();
        $this->testingFramework->markTableAsDirty('tx_realty_objects');
        $this->testingFramework->markTableAsDirty('tx_realty_house_types');

        $feUserGroupUid = $this->testingFramework->createFrontEndUserGroup();
        $feUserUid = $this->testingFramework->createFrontEndUser(
            $feUserGroupUid,
            [
                'tx_realty_openimmo_anid' => 'foo',
                'tx_realty_maximum_objects' => 1,
            ]
        );
        $this->globalConfiguration->setAsString('allowedFrontEndUserGroups', $feUserGroupUid);
        $this->testingFramework->createRecord('tx_realty_objects', ['owner' => $feUserUid]);
        $this->globalConfiguration->setAsBoolean('useFrontEndUserDataAsContactDataForImportedRecords', true);
        $this->globalConfiguration->setAsBoolean('onlyImportForRegisteredFrontEndUsers', true);
        $this->copyTestFileIntoImportFolder('two-objects.zip');
        $this->fixture->importFromZip();

        self::assertFileExists($this->importFolder . 'two-objects.zip');
    }

    ////////////////////////////////////////////////////////
    // Tests concerning loading and importing the XML file.
    ////////////////////////////////////////////////////////

    /**
     * @test
     */
    public function loadXmlFileIfFolderWithOneXmlExists()
    {
        $this->checkForZipArchive();

        $this->copyTestFileIntoImportFolder('foo.zip');
        $this->fixture->extractZip($this->importFolder . 'foo.zip');
        $this->fixture->loadXmlFile($this->importFolder . 'foo.zip');

        self::assertInstanceOf(
            \DOMDocument::class,
            $this->fixture->getImportedXml()
        );
    }

    /**
     * @test
     */
    public function loadXmlFileIfXmlIsValid()
    {
        $this->checkForZipArchive();

        $this->copyTestFileIntoImportFolder('foo.zip');
        $this->fixture->extractZip($this->importFolder . 'foo.zip');
        $this->fixture->loadXmlFile($this->importFolder . 'foo.zip');

        self::assertInstanceOf(
            \DOMDocument::class,
            $this->fixture->getImportedXml()
        );
    }

    /**
     * @test
     */
    public function loadXmlFileIfXmlIsInvalid()
    {
        $this->checkForZipArchive();

        $this->copyTestFileIntoImportFolder('bar.zip');
        $this->fixture->extractZip($this->importFolder . 'bar.zip');
        $this->fixture->loadXmlFile($this->importFolder . 'bar.zip');

        self::assertInstanceOf(\DOMDocument::class, $this->fixture->getImportedXml());
    }

    /**
     * @test
     */
    public function importFromZipKeepsCurrentBackendLanguage()
    {
        $this->checkForZipArchive();
        $this->testingFramework->markTableAsDirty('tx_realty_objects');
        $this->testingFramework->markTableAsDirty('tx_realty_house_types');

        $currentBackEndLanguage = 'fr';
        $this->getLanguageService()->lang = $currentBackEndLanguage;

        $importLanguage = 'de';
        $this->globalConfiguration->setAsString('cliLanguage', $importLanguage);

        $this->copyTestFileIntoImportFolder('same-name.zip');
        $this->disableValidation();
        $this->fixture->importFromZip();

        static::assertSame($currentBackEndLanguage, $this->getLanguageService()->lang);
    }

    /**
     * @test
     */
    public function importARecordAndImportItAgainAfterContentsHaveChanged()
    {
        $this->checkForZipArchive();
        $this->testingFramework->markTableAsDirty('tx_realty_objects');
        $this->testingFramework->markTableAsDirty('tx_realty_house_types');

        $this->copyTestFileIntoImportFolder('same-name.zip');
        $this->disableValidation();
        $this->fixture->importFromZip();
        $result = Tx_Oelib_Db::selectSingle(
            'uid',
            'tx_realty_objects',
            'object_number = "bar1234567" AND zip = "zip"'
        );

        // overwrites "same-name.zip" in the import folder
        $this->copyTestFileIntoImportFolder('changed-copy-of-same-name/same-name.zip');
        $this->fixture->importFromZip();

        self::assertSame(
            1,
            $this->testingFramework->countRecords(
                'tx_realty_objects',
                'object_number="bar1234567" AND zip="changed zip" '
                . 'AND uid=' . $result['uid']
            )
        );
    }

    /**
     * @test
     */
    public function importFromZipSkipsRecordsIfAFolderNamedLikeTheRecordAlreadyExists()
    {
        $this->checkForZipArchive();
        $this->testingFramework->markTableAsDirty('tx_realty_objects');

        $this->copyTestFileIntoImportFolder('foo.zip');
        GeneralUtility::mkdir($this->importFolder . 'foo/');
        $result = $this->fixture->importFromZip();

        self::assertContains(
            $this->translator->translate('message_surplus_folder'),
            $result
        );
        self::assertTrue(
            is_dir($this->importFolder . 'foo/')
        );
    }

    /**
     * @test
     */
    public function importFromZipImportsFromZipFileInASubFolderOfTheImportFolder()
    {
        $this->checkForZipArchive();
        $this->testingFramework->markTableAsDirty('tx_realty_objects');
        $this->testingFramework->markTableAsDirty('tx_realty_house_types');

        // just to ensure the import folder exists
        $this->copyTestFileIntoImportFolder('empty.zip');
        // copyTestFileIntoImportFolder() cannot copy folders
        GeneralUtility::mkdir($this->importFolder . 'changed-copy-of-same-name/');
        copy(
            ExtensionManagementUtility::extPath('realty') . 'Tests/LegacyUnit/fixtures/tx_realty_fixtures/' .
            'changed-copy-of-same-name/same-name.zip',
            $this->importFolder . 'changed-copy-of-same-name/same-name.zip'
        );

        $this->fixture->importFromZip();

        self::assertSame(
            1,
            $this->testingFramework->countRecords(
                'tx_realty_objects',
                'object_number="bar1234567" AND zip="changed zip" '
            )
        );
    }

    /**
     * @test
     */
    public function recordIsNotWrittenToTheDatabaseIfTheRequiredFieldsAreNotSet()
    {
        $objectNumber = 'bar1234567';
        $dummyDocument = new DOMDocument();
        $dummyDocument->loadXML(
            '<openimmo>'
            . '<anbieter>'
            . '<immobilie>'
            . '<geo>'
            . '<strasse>foobar</strasse>'
            . '<plz>bar</plz>'
            . '</geo>'
            . '<freitexte>'
            . '<lage>foo</lage>'
            . '</freitexte>'
            . '<verwaltung_techn>'
            . '<objektnr_extern>' . $objectNumber . '</objektnr_extern>'
            . '</verwaltung_techn>'
            . '</immobilie>'
            . '</anbieter>'
            . '</openimmo>'
        );

        $records = $this->fixture->convertDomDocumentToArray($dummyDocument);
        $this->fixture->writeToDatabase($records[0]);

        self::assertSame(
            0,
            $this->testingFramework->countRecords(
                'tx_realty_objects',
                'object_number="' . $objectNumber . '"' .
                Tx_Oelib_Db::enableFields('tx_realty_objects')
            )
        );
    }

    /**
     * @test
     */
    public function addWithAllRequiredFieldsSavesNewRecord()
    {
        $this->testingFramework->markTableAsDirty('tx_realty_objects' . ',' . 'tx_realty_house_types');

        $objectNumber = 'bar1234567';
        $dummyDocument = new DOMDocument();
        $dummyDocument->loadXML(
            '<openimmo>'
            . '<anbieter>'
            . '<immobilie>'
            . '<objektkategorie>'
            . '<nutzungsart WOHNEN="1"/>'
            . '<vermarktungsart KAUF="1"/>'
            . '<objektart><zimmer/></objektart>'
            . '</objektkategorie>'
            . '<geo>'
            . '<plz>bar</plz>'
            . '</geo>'
            . '<kontaktperson>'
            . '<name>bar</name>'
            . '<email_zentrale>bar</email_zentrale>'
            . '</kontaktperson>'
            . '<verwaltung_techn>'
            . '<openimmo_obid>foo</openimmo_obid>'
            . '<aktion/>'
            . '<objektnr_extern>' . $objectNumber . '</objektnr_extern>'
            . '</verwaltung_techn>'
            . '</immobilie>'
            . '<openimmo_anid>foo</openimmo_anid>'
            . '<firma>bar</firma>'
            . '</anbieter>'
            . '</openimmo>'
        );

        $records = $this->fixture->convertDomDocumentToArray($dummyDocument);
        $this->fixture->writeToDatabase($records[0]);

        self::assertTrue(
            $this->testingFramework->existsRecord('tx_realty_objects', 'object_number="' . $objectNumber . '"')
        );
    }

    /**
     * @test
     */
    public function updateWithAllRequiredFieldsSavesNewRecord()
    {
        $this->testingFramework->markTableAsDirty('tx_realty_objects' . ',' . 'tx_realty_house_types');

        $objectNumber = 'bar1234567';
        $dummyDocument = new DOMDocument();
        $dummyDocument->loadXML(
            '<openimmo>'
            . '<anbieter>'
            . '<immobilie>'
            . '<objektkategorie>'
            . '<nutzungsart WOHNEN="1"/>'
            . '<vermarktungsart KAUF="1"/>'
            . '<objektart><zimmer/></objektart>'
            . '</objektkategorie>'
            . '<geo>'
            . '<plz>bar</plz>'
            . '</geo>'
            . '<kontaktperson>'
            . '<name>bar</name>'
            . '<email_zentrale>bar</email_zentrale>'
            . '</kontaktperson>'
            . '<verwaltung_techn>'
            . '<openimmo_obid>foo</openimmo_obid>'
            . '<aktion aktionart="CHANGE"/>'
            . '<objektnr_extern>' . $objectNumber . '</objektnr_extern>'
            . '</verwaltung_techn>'
            . '</immobilie>'
            . '<openimmo_anid>foo</openimmo_anid>'
            . '<firma>bar</firma>'
            . '</anbieter>'
            . '</openimmo>'
        );

        $records = $this->fixture->convertDomDocumentToArray($dummyDocument);
        $this->fixture->writeToDatabase($records[0]);

        self::assertTrue(
            $this->testingFramework->existsRecord('tx_realty_objects', 'object_number="' . $objectNumber . '"')
        );
    }

    /**
     * @test
     */
    public function deleteWithAllRequiredFieldsWithoutRecordInDatabaseNotSavesNewRecord()
    {
        $this->testingFramework->markTableAsDirty('tx_realty_objects' . ',' . 'tx_realty_house_types');

        $objectNumber = 'bar1234567';
        $dummyDocument = new DOMDocument();
        $dummyDocument->loadXML(
            '<openimmo>'
            . '<anbieter>'
            . '<immobilie>'
            . '<objektkategorie>'
            . '<nutzungsart WOHNEN="1"/>'
            . '<vermarktungsart KAUF="1"/>'
            . '<objektart><zimmer/></objektart>'
            . '</objektkategorie>'
            . '<geo>'
            . '<plz>bar</plz>'
            . '</geo>'
            . '<kontaktperson>'
            . '<name>bar</name>'
            . '<email_zentrale>bar</email_zentrale>'
            . '</kontaktperson>'
            . '<verwaltung_techn>'
            . '<openimmo_obid>foo</openimmo_obid>'
            . '<aktion aktionart="DELETE"/>'
            . '<objektnr_extern>' . $objectNumber . '</objektnr_extern>'
            . '</verwaltung_techn>'
            . '</immobilie>'
            . '<openimmo_anid>foo</openimmo_anid>'
            . '<firma>bar</firma>'
            . '</anbieter>'
            . '</openimmo>'
        );

        $records = $this->fixture->convertDomDocumentToArray($dummyDocument);
        $this->fixture->writeToDatabase($records[0]);

        self::assertFalse(
            $this->testingFramework->existsRecord('tx_realty_objects', 'object_number="' . $objectNumber . '"')
        );
    }

    /**
     * @test
     */
    public function addWithTwoIdenticalObjectsWithAllRequiredFieldsSavesExactlyOneRecord()
    {
        $this->testingFramework->markTableAsDirty('tx_realty_objects' . ',' . 'tx_realty_house_types');

        $objectNumber = 'bar1234567';
        $objectData = '<immobilie>'
            . '<objektkategorie>'
            . '<nutzungsart WOHNEN="1"/>'
            . '<vermarktungsart KAUF="1"/>'
            . '<objektart><zimmer/></objektart>'
            . '</objektkategorie>'
            . '<geo>'
            . '<plz>bar</plz>'
            . '</geo>'
            . '<kontaktperson>'
            . '<name>bar</name>'
            . '<email_zentrale>bar</email_zentrale>'
            . '</kontaktperson>'
            . '<verwaltung_techn>'
            . '<openimmo_obid>foo</openimmo_obid>'
            . '<aktion/>'
            . '<objektnr_extern>' . $objectNumber . '</objektnr_extern>'
            . '</verwaltung_techn>'
            . '</immobilie>';

        $dummyDocument = new DOMDocument();
        $dummyDocument->loadXML(
            '<openimmo>'
            . '<anbieter>'
            . $objectData . $objectData
            . '<openimmo_anid>foo</openimmo_anid>'
            . '<firma>bar</firma>'
            . '</anbieter>'
            . '</openimmo>'
        );

        $records = $this->fixture->convertDomDocumentToArray($dummyDocument);
        $this->fixture->writeToDatabase($records[0]);

        self::assertSame(
            1,
            $this->testingFramework->countRecords('tx_realty_objects', 'object_number="' . $objectNumber . '"')
        );
    }

    /**
     * @test
     */
    public function updateWithTwoIdenticalObjectsWithAllRequiredFieldsSavesExactlyOneRecord()
    {
        $this->testingFramework->markTableAsDirty('tx_realty_objects' . ',' . 'tx_realty_house_types');

        $objectNumber = 'bar1234567';
        $objectData = '<immobilie>'
            . '<objektkategorie>'
            . '<nutzungsart WOHNEN="1"/>'
            . '<vermarktungsart KAUF="1"/>'
            . '<objektart><zimmer/></objektart>'
            . '</objektkategorie>'
            . '<geo>'
            . '<plz>bar</plz>'
            . '</geo>'
            . '<kontaktperson>'
            . '<name>bar</name>'
            . '<email_zentrale>bar</email_zentrale>'
            . '</kontaktperson>'
            . '<verwaltung_techn>'
            . '<openimmo_obid>foo</openimmo_obid>'
            . '<aktion aktionart="CHANGE"/>'
            . '<objektnr_extern>' . $objectNumber . '</objektnr_extern>'
            . '</verwaltung_techn>'
            . '</immobilie>';

        $dummyDocument = new DOMDocument();
        $dummyDocument->loadXML(
            '<openimmo>'
            . '<anbieter>'
            . $objectData . $objectData
            . '<openimmo_anid>foo</openimmo_anid>'
            . '<firma>bar</firma>'
            . '</anbieter>'
            . '</openimmo>'
        );

        $records = $this->fixture->convertDomDocumentToArray($dummyDocument);
        $this->fixture->writeToDatabase($records[0]);

        self::assertSame(
            1,
            $this->testingFramework->countRecords('tx_realty_objects', 'object_number="' . $objectNumber . '"')
        );
    }

    /**
     * @test
     */
    public function deleteWithTwoIdenticalObjectsWithAllRequiredFieldsWithoutRecordInDatabaseNotSavesNewRecord()
    {
        $this->testingFramework->markTableAsDirty('tx_realty_objects' . ',' . 'tx_realty_house_types');

        $objectNumber = 'bar1234567';
        $objectData = '<immobilie>'
            . '<objektkategorie>'
            . '<nutzungsart WOHNEN="1"/>'
            . '<vermarktungsart KAUF="1"/>'
            . '<objektart><zimmer/></objektart>'
            . '</objektkategorie>'
            . '<geo>'
            . '<plz>bar</plz>'
            . '</geo>'
            . '<kontaktperson>'
            . '<name>bar</name>'
            . '<email_zentrale>bar</email_zentrale>'
            . '</kontaktperson>'
            . '<verwaltung_techn>'
            . '<openimmo_obid>foo</openimmo_obid>'
            . '<aktion aktionart="DELETE"/>'
            . '<objektnr_extern>' . $objectNumber . '</objektnr_extern>'
            . '</verwaltung_techn>'
            . '</immobilie>';

        $dummyDocument = new DOMDocument();
        $dummyDocument->loadXML(
            '<openimmo>'
            . '<anbieter>'
            . $objectData . $objectData
            . '<openimmo_anid>foo</openimmo_anid>'
            . '<firma>bar</firma>'
            . '</anbieter>'
            . '</openimmo>'
        );

        $records = $this->fixture->convertDomDocumentToArray($dummyDocument);
        $this->fixture->writeToDatabase($records[0]);

        self::assertFalse(
            $this->testingFramework->existsRecord('tx_realty_objects', 'object_number="' . $objectNumber . '"')
        );
    }

    /**
     * @test
     */
    public function addWithAllRequiredFieldsUpdatesMatchingExistingRecord()
    {
        $this->testingFramework->markTableAsDirty('tx_realty_objects' . ',' . 'tx_realty_house_types');

        $objectNumber = 'bar1234567';
        $objectId = 'foo';
        $this->testingFramework->createRecord(
            'tx_realty_objects',
            [
                'object_number' => $objectNumber,
                'openimmo_obid' => $objectId,
            ]
        );
        $dummyDocument = new DOMDocument();
        $dummyDocument->loadXML(
            '<openimmo>'
            . '<anbieter>'
            . '<immobilie>'
            . '<objektkategorie>'
            . '<nutzungsart WOHNEN="1"/>'
            . '<vermarktungsart KAUF="1"/>'
            . '<objektart><zimmer/></objektart>'
            . '</objektkategorie>'
            . '<geo>'
            . '<plz>bar</plz>'
            . '</geo>'
            . '<kontaktperson>'
            . '<name>bar</name>'
            . '<email_zentrale>bar</email_zentrale>'
            . '</kontaktperson>'
            . '<verwaltung_techn>'
            . '<openimmo_obid>' . $objectId . '</openimmo_obid>'
            . '<aktion/>'
            . '<objektnr_extern>' . $objectNumber . '</objektnr_extern>'
            . '</verwaltung_techn>'
            . '</immobilie>'
            . '<openimmo_anid>foo</openimmo_anid>'
            . '<firma>bar</firma>'
            . '</anbieter>'
            . '</openimmo>'
        );

        $records = $this->fixture->convertDomDocumentToArray($dummyDocument);
        $this->fixture->writeToDatabase($records[0]);

        self::assertSame(
            1,
            $this->testingFramework->countRecords('tx_realty_objects', 'object_number="' . $objectNumber . '"')
        );
    }

    /**
     * @test
     */
    public function updateWithAllRequiredFieldsUpdatesMatchingExistingRecord()
    {
        $this->testingFramework->markTableAsDirty('tx_realty_objects' . ',' . 'tx_realty_house_types');

        $objectNumber = 'bar1234567';
        $objectId = 'foo';
        $this->testingFramework->createRecord(
            'tx_realty_objects',
            [
                'object_number' => $objectNumber,
                'openimmo_obid' => $objectId,
            ]
        );
        $dummyDocument = new DOMDocument();
        $dummyDocument->loadXML(
            '<openimmo>'
            . '<anbieter>'
            . '<immobilie>'
            . '<objektkategorie>'
            . '<nutzungsart WOHNEN="1"/>'
            . '<vermarktungsart KAUF="1"/>'
            . '<objektart><zimmer/></objektart>'
            . '</objektkategorie>'
            . '<geo>'
            . '<plz>bar</plz>'
            . '</geo>'
            . '<kontaktperson>'
            . '<name>bar</name>'
            . '<email_zentrale>bar</email_zentrale>'
            . '</kontaktperson>'
            . '<verwaltung_techn>'
            . '<openimmo_obid>foo</openimmo_obid>'
            . '<aktion aktionart="CHANGE"/>'
            . '<objektnr_extern>' . $objectNumber . '</objektnr_extern>'
            . '</verwaltung_techn>'
            . '</immobilie>'
            . '<openimmo_anid>' . $objectId . '</openimmo_anid>'
            . '<firma>bar</firma>'
            . '</anbieter>'
            . '</openimmo>'
        );

        $records = $this->fixture->convertDomDocumentToArray($dummyDocument);
        $this->fixture->writeToDatabase($records[0]);

        self::assertSame(
            1,
            $this->testingFramework->countRecords('tx_realty_objects', 'object_number="' . $objectNumber . '"')
        );
    }

    /**
     * @test
     */
    public function deleteWithAllRequiredFieldsMarksMatchingExistingRecordAsDeleted()
    {
        $this->testingFramework->markTableAsDirty('tx_realty_objects' . ',' . 'tx_realty_house_types');

        $objectNumber = 'bar1234567';
        $objectId = 'foo';
        $this->testingFramework->createRecord(
            'tx_realty_objects',
            [
                'object_number' => $objectNumber,
                'openimmo_obid' => $objectId,
            ]
        );
        $dummyDocument = new DOMDocument();
        $dummyDocument->loadXML(
            '<openimmo>'
            . '<anbieter>'
            . '<immobilie>'
            . '<objektkategorie>'
            . '<nutzungsart WOHNEN="1"/>'
            . '<vermarktungsart KAUF="1"/>'
            . '<objektart><zimmer/></objektart>'
            . '</objektkategorie>'
            . '<geo>'
            . '<plz>bar</plz>'
            . '</geo>'
            . '<kontaktperson>'
            . '<name>bar</name>'
            . '<email_zentrale>bar</email_zentrale>'
            . '</kontaktperson>'
            . '<verwaltung_techn>'
            . '<openimmo_obid>' . $objectId . '</openimmo_obid>'
            . '<aktion aktionart="DELETE"/>'
            . '<objektnr_extern>' . $objectNumber . '</objektnr_extern>'
            . '</verwaltung_techn>'
            . '</immobilie>'
            . '<openimmo_anid>foo</openimmo_anid>'
            . '<firma>bar</firma>'
            . '</anbieter>'
            . '</openimmo>'
        );

        $records = $this->fixture->convertDomDocumentToArray($dummyDocument);
        $this->fixture->writeToDatabase($records[0]);

        self::assertFalse(
            $this->testingFramework->existsRecord(
                'tx_realty_objects',
                'object_number="' . $objectNumber . '" AND deleted = 0'
            )
        );
        self::assertTrue(
            $this->testingFramework->existsRecord(
                'tx_realty_objects',
                'object_number="' . $objectNumber . '" AND deleted = 1'
            )
        );
    }

    /**
     * @test
     */
    public function deleteTwoTimesWithAllRequiredFieldsMarksMatchingExistingRecordAsDeleted()
    {
        $this->testingFramework->markTableAsDirty('tx_realty_objects' . ',' . 'tx_realty_house_types');

        $objectNumber = 'bar1234567';
        $objectId = 'foo';
        $this->testingFramework->createRecord(
            'tx_realty_objects',
            [
                'object_number' => $objectNumber,
                'openimmo_obid' => $objectId,
            ]
        );

        $objectData = '<immobilie>'
            . '<objektkategorie>'
            . '<nutzungsart WOHNEN="1"/>'
            . '<vermarktungsart KAUF="1"/>'
            . '<objektart><zimmer/></objektart>'
            . '</objektkategorie>'
            . '<geo>'
            . '<plz>bar</plz>'
            . '</geo>'
            . '<kontaktperson>'
            . '<name>bar</name>'
            . '<email_zentrale>bar</email_zentrale>'
            . '</kontaktperson>'
            . '<verwaltung_techn>'
            . '<openimmo_obid>' . $objectId . '</openimmo_obid>'
            . '<aktion aktionart="DELETE"/>'
            . '<objektnr_extern>' . $objectNumber . '</objektnr_extern>'
            . '</verwaltung_techn>'
            . '</immobilie>';

        $dummyDocument = new DOMDocument();
        $dummyDocument->loadXML(
            '<openimmo>'
            . '<anbieter>'
            . $objectData . $objectData
            . '<openimmo_anid>foo</openimmo_anid>'
            . '<firma>bar</firma>'
            . '</anbieter>'
            . '</openimmo>'
        );

        $records = $this->fixture->convertDomDocumentToArray($dummyDocument);
        $this->fixture->writeToDatabase($records[0]);

        self::assertFalse(
            $this->testingFramework->existsRecord(
                'tx_realty_objects',
                'object_number="' . $objectNumber . '" AND deleted = 0'
            )
        );
        self::assertTrue(
            $this->testingFramework->existsRecord(
                'tx_realty_objects',
                'object_number="' . $objectNumber . '" AND deleted = 1'
            )
        );
    }

    /**
     * @test
     */
    public function addWithAllRequiredFieldsAndMatchingExistingDeletedRecordCreatesNewRecord()
    {
        $this->testingFramework->markTableAsDirty('tx_realty_objects' . ',' . 'tx_realty_house_types');

        $objectNumber = 'bar1234567';
        $objectId = 'foo';
        $this->testingFramework->createRecord(
            'tx_realty_objects',
            [
                'object_number' => $objectNumber,
                'openimmo_obid' => $objectId,
                'deleted' => 1,
            ]
        );
        $dummyDocument = new DOMDocument();
        $dummyDocument->loadXML(
            '<openimmo>'
            . '<anbieter>'
            . '<immobilie>'
            . '<objektkategorie>'
            . '<nutzungsart WOHNEN="1"/>'
            . '<vermarktungsart KAUF="1"/>'
            . '<objektart><zimmer/></objektart>'
            . '</objektkategorie>'
            . '<geo>'
            . '<plz>bar</plz>'
            . '</geo>'
            . '<kontaktperson>'
            . '<name>bar</name>'
            . '<email_zentrale>bar</email_zentrale>'
            . '</kontaktperson>'
            . '<verwaltung_techn>'
            . '<openimmo_obid>' . $objectId . '</openimmo_obid>'
            . '<aktion/>'
            . '<objektnr_extern>' . $objectNumber . '</objektnr_extern>'
            . '</verwaltung_techn>'
            . '</immobilie>'
            . '<openimmo_anid>foo</openimmo_anid>'
            . '<firma>bar</firma>'
            . '</anbieter>'
            . '</openimmo>'
        );

        $records = $this->fixture->convertDomDocumentToArray($dummyDocument);
        $this->fixture->writeToDatabase($records[0]);

        self::assertSame(
            1,
            $this->testingFramework->countRecords(
                'tx_realty_objects',
                'object_number="' . $objectNumber . '" AND deleted = 0'
            )
        );
        self::assertSame(
            1,
            $this->testingFramework->countRecords(
                'tx_realty_objects',
                'object_number="' . $objectNumber . '" AND deleted = 1'
            )
        );
    }

    /**
     * @test
     */
    public function updateWithAllRequiredFieldsAndMatchingExistingDeletedRecordCreatesNewRecord()
    {
        $this->testingFramework->markTableAsDirty('tx_realty_objects' . ',' . 'tx_realty_house_types');

        $objectNumber = 'bar1234567';
        $objectId = 'foo';
        $this->testingFramework->createRecord(
            'tx_realty_objects',
            [
                'object_number' => $objectNumber,
                'openimmo_obid' => $objectId,
                'deleted' => 1,
            ]
        );
        $dummyDocument = new DOMDocument();
        $dummyDocument->loadXML(
            '<openimmo>'
            . '<anbieter>'
            . '<immobilie>'
            . '<objektkategorie>'
            . '<nutzungsart WOHNEN="1"/>'
            . '<vermarktungsart KAUF="1"/>'
            . '<objektart><zimmer/></objektart>'
            . '</objektkategorie>'
            . '<geo>'
            . '<plz>bar</plz>'
            . '</geo>'
            . '<kontaktperson>'
            . '<name>bar</name>'
            . '<email_zentrale>bar</email_zentrale>'
            . '</kontaktperson>'
            . '<verwaltung_techn>'
            . '<openimmo_obid>foo</openimmo_obid>'
            . '<aktion aktionart="CHANGE"/>'
            . '<objektnr_extern>' . $objectNumber . '</objektnr_extern>'
            . '</verwaltung_techn>'
            . '</immobilie>'
            . '<openimmo_anid>' . $objectId . '</openimmo_anid>'
            . '<firma>bar</firma>'
            . '</anbieter>'
            . '</openimmo>'
        );

        $records = $this->fixture->convertDomDocumentToArray($dummyDocument);
        $this->fixture->writeToDatabase($records[0]);

        self::assertSame(
            1,
            $this->testingFramework->countRecords(
                'tx_realty_objects',
                'object_number="' . $objectNumber . '" AND deleted = 0'
            )
        );
        self::assertSame(
            1,
            $this->testingFramework->countRecords(
                'tx_realty_objects',
                'object_number="' . $objectNumber . '" AND deleted = 1'
            )
        );
    }

    /**
     * @test
     */
    public function deleteWithAllRequiredFieldsWithMatchingExistingDeletedRecordNotAddsSecondDeletedRecord()
    {
        $this->testingFramework->markTableAsDirty('tx_realty_objects' . ',' . 'tx_realty_house_types');

        $objectNumber = 'bar1234567';
        $objectId = 'foo';
        $this->testingFramework->createRecord(
            'tx_realty_objects',
            [
                'object_number' => $objectNumber,
                'openimmo_obid' => $objectId,
                'deleted' => 1,
            ]
        );
        $dummyDocument = new DOMDocument();
        $dummyDocument->loadXML(
            '<openimmo>'
            . '<anbieter>'
            . '<immobilie>'
            . '<objektkategorie>'
            . '<nutzungsart WOHNEN="1"/>'
            . '<vermarktungsart KAUF="1"/>'
            . '<objektart><zimmer/></objektart>'
            . '</objektkategorie>'
            . '<geo>'
            . '<plz>bar</plz>'
            . '</geo>'
            . '<kontaktperson>'
            . '<name>bar</name>'
            . '<email_zentrale>bar</email_zentrale>'
            . '</kontaktperson>'
            . '<verwaltung_techn>'
            . '<openimmo_obid>' . $objectId . '</openimmo_obid>'
            . '<aktion aktionart="DELETE"/>'
            . '<objektnr_extern>' . $objectNumber . '</objektnr_extern>'
            . '</verwaltung_techn>'
            . '</immobilie>'
            . '<openimmo_anid>foo</openimmo_anid>'
            . '<firma>bar</firma>'
            . '</anbieter>'
            . '</openimmo>'
        );

        $records = $this->fixture->convertDomDocumentToArray($dummyDocument);
        $this->fixture->writeToDatabase($records[0]);

        self::assertFalse(
            $this->testingFramework->existsRecord(
                'tx_realty_objects',
                'object_number="' . $objectNumber . '" AND deleted = 0'
            )
        );
        self::assertSame(
            1,
            $this->testingFramework->countRecords(
                'tx_realty_objects',
                'object_number="' . $objectNumber . '" AND deleted = 1'
            )
        );
    }

    /**
     * @test
     */
    public function addWithAllRequiredFieldsUpdatesMatchingExistingHiddenRecord()
    {
        $this->testingFramework->markTableAsDirty('tx_realty_objects' . ',' . 'tx_realty_house_types');

        $objectNumber = 'bar1234567';
        $objectId = 'foo';
        $this->testingFramework->createRecord(
            'tx_realty_objects',
            [
                'object_number' => $objectNumber,
                'openimmo_obid' => $objectId,
                'hidden' => 1,
            ]
        );
        $dummyDocument = new DOMDocument();
        $dummyDocument->loadXML(
            '<openimmo>'
            . '<anbieter>'
            . '<immobilie>'
            . '<objektkategorie>'
            . '<nutzungsart WOHNEN="1"/>'
            . '<vermarktungsart KAUF="1"/>'
            . '<objektart><zimmer/></objektart>'
            . '</objektkategorie>'
            . '<geo>'
            . '<plz>bar</plz>'
            . '</geo>'
            . '<kontaktperson>'
            . '<name>bar</name>'
            . '<email_zentrale>bar</email_zentrale>'
            . '</kontaktperson>'
            . '<verwaltung_techn>'
            . '<openimmo_obid>' . $objectId . '</openimmo_obid>'
            . '<aktion/>'
            . '<objektnr_extern>' . $objectNumber . '</objektnr_extern>'
            . '</verwaltung_techn>'
            . '</immobilie>'
            . '<openimmo_anid>foo</openimmo_anid>'
            . '<firma>bar</firma>'
            . '</anbieter>'
            . '</openimmo>'
        );

        $records = $this->fixture->convertDomDocumentToArray($dummyDocument);
        $this->fixture->writeToDatabase($records[0]);

        self::assertSame(
            1,
            $this->testingFramework->countRecords(
                'tx_realty_objects',
                'object_number="' . $objectNumber . '" AND hidden = 1'
            )
        );
        self::assertSame(
            0,
            $this->testingFramework->countRecords(
                'tx_realty_objects',
                'object_number="' . $objectNumber . '" AND hidden = 0'
            )
        );
    }

    /**
     * @test
     */
    public function updateWithAllRequiredFieldsUpdatesMatchingExistingHiddenRecord()
    {
        $this->testingFramework->markTableAsDirty('tx_realty_objects' . ',' . 'tx_realty_house_types');

        $objectNumber = 'bar1234567';
        $objectId = 'foo';
        $this->testingFramework->createRecord(
            'tx_realty_objects',
            [
                'object_number' => $objectNumber,
                'openimmo_obid' => $objectId,
                'hidden' => 1,
            ]
        );
        $dummyDocument = new DOMDocument();
        $dummyDocument->loadXML(
            '<openimmo>'
            . '<anbieter>'
            . '<immobilie>'
            . '<objektkategorie>'
            . '<nutzungsart WOHNEN="1"/>'
            . '<vermarktungsart KAUF="1"/>'
            . '<objektart><zimmer/></objektart>'
            . '</objektkategorie>'
            . '<geo>'
            . '<plz>bar</plz>'
            . '</geo>'
            . '<kontaktperson>'
            . '<name>bar</name>'
            . '<email_zentrale>bar</email_zentrale>'
            . '</kontaktperson>'
            . '<verwaltung_techn>'
            . '<openimmo_obid>foo</openimmo_obid>'
            . '<aktion aktionart="CHANGE"/>'
            . '<objektnr_extern>' . $objectNumber . '</objektnr_extern>'
            . '</verwaltung_techn>'
            . '</immobilie>'
            . '<openimmo_anid>' . $objectId . '</openimmo_anid>'
            . '<firma>bar</firma>'
            . '</anbieter>'
            . '</openimmo>'
        );

        $records = $this->fixture->convertDomDocumentToArray($dummyDocument);
        $this->fixture->writeToDatabase($records[0]);

        self::assertSame(
            1,
            $this->testingFramework->countRecords(
                'tx_realty_objects',
                'object_number="' . $objectNumber . '" AND hidden = 1'
            )
        );
        self::assertSame(
            0,
            $this->testingFramework->countRecords(
                'tx_realty_objects',
                'object_number="' . $objectNumber . '" AND hidden = 0'
            )
        );
    }

    /**
     * @test
     */
    public function deleteWithAllRequiredFieldsMarksMatchingExistingHiddenRecordAsDeleted()
    {
        $this->testingFramework->markTableAsDirty('tx_realty_objects' . ',' . 'tx_realty_house_types');

        $objectNumber = 'bar1234567';
        $objectId = 'foo';
        $this->testingFramework->createRecord(
            'tx_realty_objects',
            [
                'object_number' => $objectNumber,
                'openimmo_obid' => $objectId,
                'hidden' => 1,
            ]
        );
        $dummyDocument = new DOMDocument();
        $dummyDocument->loadXML(
            '<openimmo>'
            . '<anbieter>'
            . '<immobilie>'
            . '<objektkategorie>'
            . '<nutzungsart WOHNEN="1"/>'
            . '<vermarktungsart KAUF="1"/>'
            . '<objektart><zimmer/></objektart>'
            . '</objektkategorie>'
            . '<geo>'
            . '<plz>bar</plz>'
            . '</geo>'
            . '<kontaktperson>'
            . '<name>bar</name>'
            . '<email_zentrale>bar</email_zentrale>'
            . '</kontaktperson>'
            . '<verwaltung_techn>'
            . '<openimmo_obid>' . $objectId . '</openimmo_obid>'
            . '<aktion aktionart="DELETE"/>'
            . '<objektnr_extern>' . $objectNumber . '</objektnr_extern>'
            . '</verwaltung_techn>'
            . '</immobilie>'
            . '<openimmo_anid>foo</openimmo_anid>'
            . '<firma>bar</firma>'
            . '</anbieter>'
            . '</openimmo>'
        );

        $records = $this->fixture->convertDomDocumentToArray($dummyDocument);
        $this->fixture->writeToDatabase($records[0]);

        self::assertFalse(
            $this->testingFramework->existsRecord(
                'tx_realty_objects',
                'object_number="' . $objectNumber . '" AND deleted = 0 AND hidden = 1'
            )
        );
        self::assertFalse(
            $this->testingFramework->existsRecord(
                'tx_realty_objects',
                'object_number="' . $objectNumber . '" AND deleted = 0 AND hidden = 0'
            )
        );
        self::assertFalse(
            $this->testingFramework->existsRecord(
                'tx_realty_objects',
                'object_number="' . $objectNumber . '" AND deleted = 1 AND hidden = 0'
            )
        );
        self::assertTrue(
            $this->testingFramework->existsRecord(
                'tx_realty_objects',
                'object_number="' . $objectNumber . '" AND deleted = 1 AND hidden = 1'
            )
        );
    }

    /**
     * @test
     */
    public function addAndChangeWithTwoIdenticalObjectsWithAllRequiredFieldsSavesExactlyOneRecord()
    {
        $this->testingFramework->markTableAsDirty('tx_realty_objects' . ',' . 'tx_realty_house_types');

        $objectNumber = 'bar1234567';
        $dummyDocument = new DOMDocument();
        $dummyDocument->loadXML(
            '<openimmo>'
            . '<anbieter>'
            . '<immobilie>'
            . '<objektkategorie>'
            . '<nutzungsart WOHNEN="1"/>'
            . '<vermarktungsart KAUF="1"/>'
            . '<objektart><zimmer/></objektart>'
            . '</objektkategorie>'
            . '<geo>'
            . '<plz>bar</plz>'
            . '</geo>'
            . '<kontaktperson>'
            . '<name>bar</name>'
            . '<email_zentrale>bar</email_zentrale>'
            . '</kontaktperson>'
            . '<verwaltung_techn>'
            . '<openimmo_obid>foo</openimmo_obid>'
            . '<aktion/>'
            . '<objektnr_extern>' . $objectNumber . '</objektnr_extern>'
            . '</verwaltung_techn>'
            . '</immobilie>'
            . '<immobilie>'
            . '<objektkategorie>'
            . '<nutzungsart WOHNEN="1"/>'
            . '<vermarktungsart KAUF="1"/>'
            . '<objektart><zimmer/></objektart>'
            . '</objektkategorie>'
            . '<geo>'
            . '<plz>bar</plz>'
            . '</geo>'
            . '<kontaktperson>'
            . '<name>bar</name>'
            . '<email_zentrale>bar</email_zentrale>'
            . '</kontaktperson>'
            . '<verwaltung_techn>'
            . '<openimmo_obid>foo</openimmo_obid>'
            . '<aktion aktionart="CHANGE"/>'
            . '<objektnr_extern>' . $objectNumber . '</objektnr_extern>'
            . '</verwaltung_techn>'
            . '</immobilie>'
            . '<openimmo_anid>foo</openimmo_anid>'
            . '<firma>bar</firma>'
            . '</anbieter>'
            . '</openimmo>'
        );

        $records = $this->fixture->convertDomDocumentToArray($dummyDocument);
        $this->fixture->writeToDatabase($records[0]);

        self::assertSame(
            1,
            $this->testingFramework->countRecords('tx_realty_objects', 'object_number="' . $objectNumber . '"')
        );
    }

    /**
     * @test
     */
    public function changeAndAddWithTwoIdenticalObjectsWithAllRequiredFieldsSavesExactlyOneRecord()
    {
        $this->testingFramework->markTableAsDirty('tx_realty_objects' . ',' . 'tx_realty_house_types');

        $objectNumber = 'bar1234567';
        $dummyDocument = new DOMDocument();
        $dummyDocument->loadXML(
            '<openimmo>'
            . '<anbieter>'
            . '<immobilie>'
            . '<objektkategorie>'
            . '<nutzungsart WOHNEN="1"/>'
            . '<vermarktungsart KAUF="1"/>'
            . '<objektart><zimmer/></objektart>'
            . '</objektkategorie>'
            . '<geo>'
            . '<plz>bar</plz>'
            . '</geo>'
            . '<kontaktperson>'
            . '<name>bar</name>'
            . '<email_zentrale>bar</email_zentrale>'
            . '</kontaktperson>'
            . '<verwaltung_techn>'
            . '<openimmo_obid>foo</openimmo_obid>'
            . '<aktion aktionart="CHANGE"/>'
            . '<objektnr_extern>' . $objectNumber . '</objektnr_extern>'
            . '</verwaltung_techn>'
            . '</immobilie>'
            . '<immobilie>'
            . '<objektkategorie>'
            . '<nutzungsart WOHNEN="1"/>'
            . '<vermarktungsart KAUF="1"/>'
            . '<objektart><zimmer/></objektart>'
            . '</objektkategorie>'
            . '<geo>'
            . '<plz>bar</plz>'
            . '</geo>'
            . '<kontaktperson>'
            . '<name>bar</name>'
            . '<email_zentrale>bar</email_zentrale>'
            . '</kontaktperson>'
            . '<verwaltung_techn>'
            . '<openimmo_obid>foo</openimmo_obid>'
            . '<aktion/>'
            . '<objektnr_extern>' . $objectNumber . '</objektnr_extern>'
            . '</verwaltung_techn>'
            . '</immobilie>'
            . '<openimmo_anid>foo</openimmo_anid>'
            . '<firma>bar</firma>'
            . '</anbieter>'
            . '</openimmo>'
        );

        $records = $this->fixture->convertDomDocumentToArray($dummyDocument);
        $this->fixture->writeToDatabase($records[0]);

        self::assertSame(
            1,
            $this->testingFramework->countRecords('tx_realty_objects', 'object_number="' . $objectNumber . '"')
        );
    }

    /**
     * @test
     */
    public function deleteAndChangeWithTwoIdenticalObjectsWithAllRequiredFieldsSavesNoRecord()
    {
        $this->testingFramework->markTableAsDirty('tx_realty_objects' . ',' . 'tx_realty_house_types');

        $objectNumber = 'bar1234567';
        $dummyDocument = new DOMDocument();
        $dummyDocument->loadXML(
            '<openimmo>'
            . '<anbieter>'
            . '<immobilie>'
            . '<objektkategorie>'
            . '<nutzungsart WOHNEN="1"/>'
            . '<vermarktungsart KAUF="1"/>'
            . '<objektart><zimmer/></objektart>'
            . '</objektkategorie>'
            . '<geo>'
            . '<plz>bar</plz>'
            . '</geo>'
            . '<kontaktperson>'
            . '<name>bar</name>'
            . '<email_zentrale>bar</email_zentrale>'
            . '</kontaktperson>'
            . '<verwaltung_techn>'
            . '<openimmo_obid>foo</openimmo_obid>'
            . '<aktion aktionart="DELETE"/>'
            . '<objektnr_extern>' . $objectNumber . '</objektnr_extern>'
            . '</verwaltung_techn>'
            . '</immobilie>'
            . '<immobilie>'
            . '<objektkategorie>'
            . '<nutzungsart WOHNEN="1"/>'
            . '<vermarktungsart KAUF="1"/>'
            . '<objektart><zimmer/></objektart>'
            . '</objektkategorie>'
            . '<geo>'
            . '<plz>bar</plz>'
            . '</geo>'
            . '<kontaktperson>'
            . '<name>bar</name>'
            . '<email_zentrale>bar</email_zentrale>'
            . '</kontaktperson>'
            . '<verwaltung_techn>'
            . '<openimmo_obid>foo</openimmo_obid>'
            . '<aktion aktionart="CHANGE"/>'
            . '<objektnr_extern>' . $objectNumber . '</objektnr_extern>'
            . '</verwaltung_techn>'
            . '</immobilie>'
            . '<openimmo_anid>foo</openimmo_anid>'
            . '<firma>bar</firma>'
            . '</anbieter>'
            . '</openimmo>'
        );

        $records = $this->fixture->convertDomDocumentToArray($dummyDocument);
        $this->fixture->writeToDatabase($records[0]);

        self::assertSame(
            0,
            $this->testingFramework->countRecords('tx_realty_objects', 'object_number="' . $objectNumber . '"')
        );
    }

    /**
     * @test
     */
    public function changeAndDeleteWithTwoIdenticalObjectsWithAllRequiredFieldsSavesOneRecord()
    {
        $this->testingFramework->markTableAsDirty('tx_realty_objects' . ',' . 'tx_realty_house_types');

        $objectNumber = 'bar1234567';
        $dummyDocument = new DOMDocument();
        $dummyDocument->loadXML(
            '<openimmo>'
            . '<anbieter>'
            . '<immobilie>'
            . '<objektkategorie>'
            . '<nutzungsart WOHNEN="1"/>'
            . '<vermarktungsart KAUF="1"/>'
            . '<objektart><zimmer/></objektart>'
            . '</objektkategorie>'
            . '<geo>'
            . '<plz>bar</plz>'
            . '</geo>'
            . '<kontaktperson>'
            . '<name>bar</name>'
            . '<email_zentrale>bar</email_zentrale>'
            . '</kontaktperson>'
            . '<verwaltung_techn>'
            . '<openimmo_obid>foo</openimmo_obid>'
            . '<aktion aktionart="CHANGE"/>'
            . '<objektnr_extern>' . $objectNumber . '</objektnr_extern>'
            . '</verwaltung_techn>'
            . '</immobilie>'
            . '<immobilie>'
            . '<objektkategorie>'
            . '<nutzungsart WOHNEN="1"/>'
            . '<vermarktungsart KAUF="1"/>'
            . '<objektart><zimmer/></objektart>'
            . '</objektkategorie>'
            . '<geo>'
            . '<plz>bar</plz>'
            . '</geo>'
            . '<kontaktperson>'
            . '<name>bar</name>'
            . '<email_zentrale>bar</email_zentrale>'
            . '</kontaktperson>'
            . '<verwaltung_techn>'
            . '<openimmo_obid>foo</openimmo_obid>'
            . '<aktion aktionart="DELETE"/>'
            . '<objektnr_extern>' . $objectNumber . '</objektnr_extern>'
            . '</verwaltung_techn>'
            . '</immobilie>'
            . '<openimmo_anid>foo</openimmo_anid>'
            . '<firma>bar</firma>'
            . '</anbieter>'
            . '</openimmo>'
        );

        $records = $this->fixture->convertDomDocumentToArray($dummyDocument);
        $this->fixture->writeToDatabase($records[0]);

        self::assertSame(
            1,
            $this->testingFramework->countRecords('tx_realty_objects', 'object_number="' . $objectNumber . '"')
        );
    }

    /**
     * @test
     */
    public function changeAndAddDeleteWithTwoIdenticalObjectsWithAllRequiredFieldsAndContactDataNotSavesAnyRecord()
    {
        $this->testingFramework->markTableAsDirty('tx_realty_objects' . ',' . 'tx_realty_house_types');

        $objectNumber = 'bar1234567';
        $dummyDocument = new DOMDocument();
        $dummyDocument->loadXML(
            '<openimmo>
            <uebertragung xmlns="" art="OFFLINE" umfang="TEIL" modus="CHANGE" version="1.2.4" sendersoftware="OOF" senderversion="$Rev: 49210 $" techn_email="heidi.loehr@example.com" timestamp="2015-06-22T13:55:07.0+00:00"/>
            <anbieter xmlns="">
            <firma>Doe Immobilien</firma>
            <openimmo_anid>123456</openimmo_anid>

            <immobilie>
            <objektkategorie>
            <nutzungsart WOHNEN="1" GEWERBE="0"/>
            <vermarktungsart KAUF="1" MIETE_PACHT="0"/>
            <objektart>
            <wohnung wohnungtyp="ETAGE"/>
            </objektart>
            </objektkategorie>
            <geo>
            <plz>55127</plz>
            <ort>Mainz / Lerchenberg</ort>
            <strasse>Rubensallee</strasse>
            <hausnummer>1</hausnummer>
            <land iso_land="DEU"/>
            <etage>3</etage>
            <anzahl_etagen>7</anzahl_etagen>
            </geo>
            <kontaktperson>
            <email_zentrale>offerer@example.com</email_zentrale>
            <email_direkt>offerer@example.com</email_direkt>
            <name>Doe</name>
            <vorname>Jane</vorname>
            <anrede>Frau</anrede>
            <anrede_brief>Sehr geehrte Frau Doe,</anrede_brief>
            <firma>Doe Immobilien</firma>
            <zusatzfeld/>
            <strasse>Dessauer Straße</strasse>
            <hausnummer>1</hausnummer>
            <plz>55000</plz>
            <ort>Bad Kreuznach</ort>
            <land iso_land="DEU"/>
            <url>www.oliverklee.de</url>
            </kontaktperson>
            <preise>
            <kaufpreis>149000.00</kaufpreis>
            <hausgeld>345.00</hausgeld>
            <aussen_courtage mit_mwst="1">5,95 % inkl. 19% MwSt.</aussen_courtage>
            <waehrung iso_waehrung="EUR"/>
            <stp_carport stellplatzmiete="0.00" anzahl="0"/>
            <stp_duplex stellplatzmiete="0.00" anzahl="0"/>
            <stp_freiplatz stellplatzmiete="0.00" anzahl="1"/>
            <stp_garage stellplatzmiete="0.00" anzahl="0"/>
            <stp_parkhaus stellplatzmiete="0.00" anzahl="0"/>
            <stp_tiefgarage stellplatzmiete="0.00" anzahl="0"/>
            <stp_sonstige platzart="SONSTIGES" stellplatzmiete="0.00" anzahl="0"/>
            </preise>
            <versteigerung/>
            <flaechen>
            <wohnflaeche>88.00</wohnflaeche>
            <anzahl_zimmer>3.00</anzahl_zimmer>
            <anzahl_badezimmer>1.00</anzahl_badezimmer>
            <anzahl_sep_wc>1.00</anzahl_sep_wc>
            <anzahl_stellplaetze>1</anzahl_stellplaetze>
            </flaechen>
            <ausstattung>
            <heizungsart FERN="1"/>
            <fahrstuhl PERSONEN="1"/>
            <kabel_sat_tv>1</kabel_sat_tv>
            <unterkellert keller="JA"/>
            </ausstattung>
            <zustand_angaben>
            <baujahr>1971</baujahr>
            <zustand zustand_art="GEPFLEGT"/>
            <verkaufstatus stand="OFFEN"/>
            </zustand_angaben>
            <verwaltung_objekt>
            <objektadresse_freigeben>0</objektadresse_freigeben>
            <verfuegbar_ab>01.08.2015</verfuegbar_ab>
            </verwaltung_objekt>
            <verwaltung_techn>
            <objektnr_intern>550</objektnr_intern>
            <objektnr_extern>OR273</objektnr_extern>
            <aktion aktionart="CHANGE"/>
            <openimmo_obid>123456_550_OR273</openimmo_obid>
            <kennung_ursprung>onOffice Software</kennung_ursprung>
            <stand_vom>2015-06-22</stand_vom>
            <weitergabe_generell>1</weitergabe_generell>
            </verwaltung_techn>
            </immobilie>

            <immobilie>
            <objektkategorie>
            <nutzungsart WOHNEN="1" GEWERBE="0"/>
            <vermarktungsart KAUF="1" MIETE_PACHT="0"/>
            <objektart>
            <wohnung wohnungtyp="ETAGE"/>
            </objektart>
            </objektkategorie>
            <geo>
            <plz>55127</plz>
            <ort>Mainz / Lerchenberg</ort>
            <geokoordinaten breitengrad="49.96550" laengengrad="8.18754"/>
            </geo>
            <kontaktperson>
            <email_zentrale>offerer@example.com</email_zentrale>
            <email_direkt>offerer@example.com</email_direkt>
            <name>Doe</name>
            <vorname>Jane</vorname>
            <anrede>Frau</anrede>
            <name/>
            </kontaktperson>
            <verwaltung_techn>
            <objektnr_intern>550</objektnr_intern>
            <objektnr_extern>OR273</objektnr_extern>
            <aktion aktionart="DELETE"/>
            <openimmo_obid>123456_550_OR273</openimmo_obid>
            <kennung_ursprung>onOffice Software</kennung_ursprung>
            <stand_vom>2015-06-22</stand_vom>
            <weitergabe_generell>1</weitergabe_generell>
            </verwaltung_techn>
            </immobilie>

            </anbieter>
            </openimmo>'
        );

        $records = $this->fixture->convertDomDocumentToArray($dummyDocument);
        $this->fixture->writeToDatabase($records[0]);

        self::assertSame(
            0,
            $this->testingFramework->countRecords('tx_realty_objects', 'object_number="' . $objectNumber . '"')
        );
    }

    /**
     * @test
     */
    public function ensureContactEmailNotChangesAddressIfValidAddressIsSet()
    {
        $this->fixture->loadRealtyObject(
            ['contact_email' => 'foo-valid@example.com']
        );
        $this->fixture->ensureContactEmail();

        self::assertSame(
            'foo-valid@example.com',
            $this->fixture->getContactEmailFromRealtyObject()
        );
    }

    /**
     * @test
     */
    public function ensureContactEmailSetsDefaultAddressIfEmptyAddressSet()
    {
        $this->globalConfiguration->setAsString(
            'emailAddress',
            'default_address@example.com'
        );
        $this->fixture->loadRealtyObject(['contact_email' => '']);
        $this->fixture->ensureContactEmail();

        self::assertSame(
            'default_address@example.com',
            $this->fixture->getContactEmailFromRealtyObject()
        );
    }

    /**
     * @test
     */
    public function ensureContactEmailSetsDefaultAddressIfInvalidAddressIsSet()
    {
        $this->globalConfiguration->setAsString(
            'emailAddress',
            'default_address@example.com'
        );
        $this->fixture->loadRealtyObject(['contact_email' => 'foo']);
        $this->fixture->ensureContactEmail();

        self::assertSame(
            'default_address@example.com',
            $this->fixture->getContactEmailFromRealtyObject()
        );
    }

    /**
     * @test
     */
    public function importStoresZipsWithLeadingZeroesIntoDb()
    {
        $this->testingFramework->markTableAsDirty(
            'tx_realty_objects' . ',' . 'tx_realty_house_types'
        );

        $objectNumber = 'bar1234567';
        $dummyDocument = new DOMDocument();
        $dummyDocument->loadXML(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<objektkategorie>' .
            '<nutzungsart WOHNEN="1"/>' .
            '<vermarktungsart KAUF="1"/>' .
            '<objektart><zimmer/></objektart>' .
            '</objektkategorie>' .
            '<geo>' .
            '<plz>01234</plz>' .
            '</geo>' .
            '<kontaktperson>' .
            '<name>bar</name>' .
            '<email_zentrale>bar</email_zentrale>' .
            '</kontaktperson>' .
            '<verwaltung_techn>' .
            '<openimmo_obid>foo</openimmo_obid>' .
            '<aktion/>' .
            '<objektnr_extern>' .
            $objectNumber .
            '</objektnr_extern>' .
            '</verwaltung_techn>' .
            '</immobilie>' .
            '<openimmo_anid>foo</openimmo_anid>' .
            '<firma>bar</firma>' .
            '</anbieter>' .
            '</openimmo>'
        );

        $records = $this->fixture->convertDomDocumentToArray($dummyDocument);
        $this->fixture->writeToDatabase($records[0]);

        self::assertSame(
            1,
            $this->testingFramework->countRecords(
                'tx_realty_objects',
                'object_number="' . $objectNumber . '" AND zip="01234"' .
                Tx_Oelib_Db::enableFields('tx_realty_objects')
            )
        );
    }

    /**
     * @test
     */
    public function importStoresNumberOfRoomsWithDecimalsIntoDb()
    {
        $this->testingFramework->markTableAsDirty(
            'tx_realty_objects' . ',' . 'tx_realty_house_types'
        );

        $objectNumber = 'bar1234567';
        $dummyDocument = new DOMDocument();
        $dummyDocument->loadXML(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<objektkategorie>' .
            '<nutzungsart WOHNEN="1"/>' .
            '<vermarktungsart KAUF="1"/>' .
            '<objektart><zimmer/></objektart>' .
            '</objektkategorie>' .
            '<flaechen>' .
            '<anzahl_zimmer>1.25</anzahl_zimmer>' .
            '</flaechen>' .
            '<geo>' .
            '<plz>01234</plz>' .
            '</geo>' .
            '<kontaktperson>' .
            '<name>bar</name>' .
            '</kontaktperson>' .
            '<verwaltung_techn>' .
            '<openimmo_obid>foo</openimmo_obid>' .
            '<objektnr_extern>' .
            $objectNumber .
            '</objektnr_extern>' .
            '</verwaltung_techn>' .
            '</immobilie>' .
            '<openimmo_anid>foo</openimmo_anid>' .
            '<firma>bar</firma>' .
            '</anbieter>' .
            '</openimmo>'
        );

        $records = $this->fixture->convertDomDocumentToArray($dummyDocument);
        $this->fixture->writeToDatabase($records[0]);

        self::assertSame(
            1,
            $this->testingFramework->countRecords(
                'tx_realty_objects',
                'object_number="' . $objectNumber . '" AND ' .
                'number_of_rooms = 1.25' .
                Tx_Oelib_Db::enableFields('tx_realty_objects')
            )
        );
    }

    /**
     * @test
     */
    public function importUtf8FileWithCorrectUmlauts()
    {
        $this->checkForZipArchive();
        $this->testingFramework->markTableAsDirty('tx_realty_objects');
        $this->testingFramework->markTableAsDirty('tx_realty_house_types');

        $this->copyTestFileIntoImportFolder('charset-UTF8.zip');
        $this->fixture->importFromZip();

        self::assertTrue(
            $this->testingFramework->existsExactlyOneRecord(
                'tx_realty_objects',
                'openimmo_anid="test-anid-with-umlaut-ü"'
            )
        );
    }

    /**
     * @test
     */
    public function importUtf8FileWithUtf8AsDefaultEncodingAndNoXmlPrologueWithCorrectUmlauts()
    {
        $this->checkForZipArchive();
        $this->testingFramework->markTableAsDirty('tx_realty_objects');
        $this->testingFramework->markTableAsDirty('tx_realty_house_types');

        $this->copyTestFileIntoImportFolder('charset-UTF8-default.zip');
        $this->fixture->importFromZip();

        self::assertTrue(
            $this->testingFramework->existsExactlyOneRecord(
                'tx_realty_objects',
                'openimmo_anid="test-anid-with-umlaut-ü"'
            )
        );
    }

    /**
     * @test
     */
    public function importIso88591FileWithCorrectUmlauts()
    {
        $this->checkForZipArchive();
        $this->testingFramework->markTableAsDirty('tx_realty_objects');
        $this->testingFramework->markTableAsDirty('tx_realty_house_types');

        $this->copyTestFileIntoImportFolder('charset-ISO8859-1.zip');
        $this->fixture->importFromZip();

        self::assertTrue(
            $this->testingFramework->existsExactlyOneRecord(
                'tx_realty_objects',
                'openimmo_anid="test-anid-with-umlaut-ü"'
            )
        );
    }

    //////////////////////////////////////////////////////////////////
    // Tests concerning the restricted import for registered owners.
    //////////////////////////////////////////////////////////////////

    /**
     * @test
     */
    public function recordWithAnidThatMatchesAnExistingFeUserIsImportedForEnabledOwnerRestriction()
    {
        $this->checkForZipArchive();
        $this->testingFramework->markTableAsDirty('tx_realty_objects');
        $this->testingFramework->markTableAsDirty('tx_realty_house_types');

        $feUserUid = $this->testingFramework->createFrontEndUser('', ['tx_realty_openimmo_anid' => 'foo']);
        $this->globalConfiguration->setAsBoolean('onlyImportForRegisteredFrontEndUsers', true);
        $this->copyTestFileIntoImportFolder('same-name.zip');
        $this->fixture->importFromZip();

        self::assertSame(
            1,
            $this->testingFramework->countRecords(
                'tx_realty_objects',
                'openimmo_anid="foo" AND owner=' . $feUserUid
            )
        );
    }

    /**
     * @test
     */
    public function recordWithAnidThatDoesNotMatchAnExistingFeUserIsNotImportedForEnabledOwnerRestriction()
    {
        $this->checkForZipArchive();

        $this->globalConfiguration->setAsBoolean('onlyImportForRegisteredFrontEndUsers', true);
        $this->copyTestFileIntoImportFolder('same-name.zip');
        $this->fixture->importFromZip();

        self::assertSame(
            0,
            $this->testingFramework->countRecords(
                'tx_realty_objects',
                'openimmo_anid="foo"'
            )
        );
    }

    /**
     * @test
     */
    public function recordWithAnidThatMatchesAnExistingFeUserInAnAllowedGroupIsImportedForEnabledOwnerAndGroupRestriction(
    ) {
        $this->checkForZipArchive();
        $this->testingFramework->markTableAsDirty('tx_realty_objects');
        $this->testingFramework->markTableAsDirty('tx_realty_house_types');

        $feUserGroupUid = $this->testingFramework->createFrontEndUserGroup();
        $feUserUid = $this->testingFramework->createFrontEndUser($feUserGroupUid, ['tx_realty_openimmo_anid' => 'foo']);
        $this->globalConfiguration->setAsBoolean('onlyImportForRegisteredFrontEndUsers', true);
        $this->globalConfiguration->setAsString('allowedFrontEndUserGroups', $feUserGroupUid);
        $this->copyTestFileIntoImportFolder('same-name.zip');
        $this->fixture->importFromZip();

        self::assertSame(
            1,
            $this->testingFramework->countRecords(
                'tx_realty_objects',
                'openimmo_anid="foo" AND owner=' . $feUserUid
            )
        );
    }

    /**
     * @test
     */
    public function recordWithAnidThatMatchesAnExistingFeUserInAForbiddenGroupIsNotImportedForEnabledOwnerAndGroupRestriction(
    ) {
        $this->checkForZipArchive();

        $feUserGroupUid = $this->testingFramework->createFrontEndUserGroup();
        $feUserUid = $this->testingFramework->createFrontEndUser($feUserGroupUid, ['tx_realty_openimmo_anid' => 'foo']);
        $this->globalConfiguration->setAsBoolean('onlyImportForRegisteredFrontEndUsers', true);
        $this->globalConfiguration->setAsString('allowedFrontEndUserGroups', $feUserGroupUid + 1);
        $this->copyTestFileIntoImportFolder('same-name.zip');
        $this->fixture->importFromZip();

        self::assertSame(
            0,
            $this->testingFramework->countRecords(
                'tx_realty_objects',
                'openimmo_anid="foo" AND owner=' . $feUserUid
            )
        );
    }

    ////////////////////////////////////////////////
    // Tests concerning the object limit for users
    ////////////////////////////////////////////////

    /**
     * @test
     */
    public function writeToDatabaseForUserWithObjectLimitReachedDoesNotImportAnyFurtherRecords()
    {
        $this->testingFramework->markTableAsDirty('tx_realty_objects' . ',' . 'tx_realty_house_types');

        $feUserGroupUid = $this->testingFramework->createFrontEndUserGroup();
        $feUserUid = $this->testingFramework->createFrontEndUser(
            $feUserGroupUid,
            [
                'tx_realty_openimmo_anid' => 'foo',
                'tx_realty_maximum_objects' => 1,
            ]
        );
        $this->testingFramework->createRecord(
            'tx_realty_objects',
            ['owner' => $feUserUid]
        );

        $this->globalConfiguration->setAsBoolean('useFrontEndUserDataAsContactDataForImportedRecords', true);
        $this->globalConfiguration->setAsString('allowedFrontEndUserGroups', $feUserGroupUid);

        $singleObject = new DOMDocument();
        $singleObject->loadXML(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<objektkategorie>' .
            '<nutzungsart WOHNEN="1"/>' .
            '<vermarktungsart KAUF="1"/>' .
            '<objektart><zimmer/></objektart>' .
            '</objektkategorie>' .
            '<geo>' .
            '<plz>bar</plz>' .
            '</geo>' .
            '<kontaktperson>' .
            '<name>bar</name>' .
            '</kontaktperson>' .
            '<verwaltung_techn>' .
            '<openimmo_obid>foo</openimmo_obid>' .
            '<objektnr_extern>bar1234567</objektnr_extern>' .
            '</verwaltung_techn>' .
            '</immobilie>' .
            '<openimmo_anid>foo</openimmo_anid>' .
            '<firma>bar</firma>' .
            '</anbieter>' .
            '</openimmo>'
        );

        $records = $this->fixture->convertDomDocumentToArray($singleObject);
        $this->fixture->writeToDatabase($records[0]);

        self::assertSame(
            1,
            $this->testingFramework->countRecords(
                'tx_realty_objects',
                'owner =' . $feUserUid
            )
        );
    }

    /**
     * @test
     */
    public function writeToDatabaseForUserWithObjectLimitNotReachedDoesImportRecords()
    {
        $this->testingFramework->markTableAsDirty('tx_realty_objects' . ',' . 'tx_realty_house_types');

        $feUserGroupUid = $this->testingFramework->createFrontEndUserGroup();
        $feUserUid = $this->testingFramework->createFrontEndUser(
            $feUserGroupUid,
            [
                'tx_realty_openimmo_anid' => 'foo',
                'tx_realty_maximum_objects' => 42,
            ]
        );

        $this->globalConfiguration->setAsBoolean('useFrontEndUserDataAsContactDataForImportedRecords', true);
        $this->globalConfiguration->setAsString('allowedFrontEndUserGroups', $feUserGroupUid);

        $multipleRecords = new DOMDocument();
        $multipleRecords->loadXML(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<objektkategorie>' .
            '<nutzungsart WOHNEN="1"/>' .
            '<vermarktungsart KAUF="1"/>' .
            '<objektart><zimmer/></objektart>' .
            '</objektkategorie>' .
            '<geo>' .
            '<plz>bar</plz>' .
            '</geo>' .
            '<kontaktperson>' .
            '<name>bar</name>' .
            '</kontaktperson>' .
            '<verwaltung_techn>' .
            '<openimmo_obid>foo</openimmo_obid>' .
            '<objektnr_extern>bar1234567</objektnr_extern>' .
            '</verwaltung_techn>' .
            '</immobilie>' .
            '<immobilie>' .
            '<objektkategorie>' .
            '<nutzungsart WOHNEN="1"/>' .
            '<vermarktungsart KAUF="1"/>' .
            '<objektart><zimmer/></objektart>' .
            '</objektkategorie>' .
            '<geo>' .
            '<plz>bar</plz>' .
            '</geo>' .
            '<kontaktperson>' .
            '<name>bar</name>' .
            '</kontaktperson>' .
            '<verwaltung_techn>' .
            '<openimmo_obid>foo</openimmo_obid>' .
            '<objektnr_extern>bar2345678</objektnr_extern>' .
            '</verwaltung_techn>' .
            '</immobilie>' .
            '<openimmo_anid>foo</openimmo_anid>' .
            '<firma>bar</firma>' .
            '</anbieter>' .
            '</openimmo>'
        );

        $records = $this->fixture->convertDomDocumentToArray($multipleRecords);
        $this->fixture->writeToDatabase($records[0]);
        $this->fixture->writeToDatabase($records[1]);

        self::assertSame(
            2,
            $this->testingFramework->countRecords(
                'tx_realty_objects',
                'owner =' . $feUserUid
            )
        );
    }

    /**
     * @test
     */
    public function writeToDatabaseForUserWithoutObjectLimitDoesImportRecord()
    {
        $this->testingFramework->markTableAsDirty('tx_realty_objects' . ',' . 'tx_realty_house_types');
        $feUserGroupUid = $this->testingFramework->createFrontEndUserGroup();
        $feUserUid = $this->testingFramework->createFrontEndUser($feUserGroupUid, ['tx_realty_openimmo_anid' => 'foo']);
        $this->globalConfiguration->setAsBoolean('useFrontEndUserDataAsContactDataForImportedRecords', true);
        $this->globalConfiguration->setAsString('allowedFrontEndUserGroups', $feUserGroupUid);

        $singleObject = new DOMDocument();
        $singleObject->loadXML(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<objektkategorie>' .
            '<nutzungsart WOHNEN="1"/>' .
            '<vermarktungsart KAUF="1"/>' .
            '<objektart><zimmer/></objektart>' .
            '</objektkategorie>' .
            '<geo>' .
            '<plz>bar</plz>' .
            '</geo>' .
            '<kontaktperson>' .
            '<name>bar</name>' .
            '</kontaktperson>' .
            '<verwaltung_techn>' .
            '<openimmo_obid>foo</openimmo_obid>' .
            '<objektnr_extern>bar1234567</objektnr_extern>' .
            '</verwaltung_techn>' .
            '</immobilie>' .
            '<openimmo_anid>foo</openimmo_anid>' .
            '<firma>bar</firma>' .
            '</anbieter>' .
            '</openimmo>'
        );

        $records = $this->fixture->convertDomDocumentToArray($singleObject);
        $this->fixture->writeToDatabase($records[0]);

        self::assertSame(
            1,
            $this->testingFramework->countRecords(
                'tx_realty_objects',
                'owner =' . $feUserUid
            )
        );
    }

    /**
     * @test
     */
    public function writeToDatabaseForUserWithOneObjectLeftToLimitImportsOnlyOneRecord()
    {
        $this->testingFramework->markTableAsDirty('tx_realty_objects' . ',' . 'tx_realty_house_types');

        $feUserGroupUid = $this->testingFramework->createFrontEndUserGroup();
        $feUserUid = $this->testingFramework->createFrontEndUser(
            $feUserGroupUid,
            [
                'tx_realty_openimmo_anid' => 'foo',
                'tx_realty_maximum_objects' => 1,
            ]
        );

        $this->globalConfiguration->setAsBoolean('useFrontEndUserDataAsContactDataForImportedRecords', true);
        $this->globalConfiguration->setAsString('allowedFrontEndUserGroups', $feUserGroupUid);

        $multipleRecords = new DOMDocument();
        $multipleRecords->loadXML(
            '<openimmo>' .
            '<anbieter>' .
            '<immobilie>' .
            '<objektkategorie>' .
            '<nutzungsart WOHNEN="1"/>' .
            '<vermarktungsart KAUF="1"/>' .
            '<objektart><zimmer/></objektart>' .
            '</objektkategorie>' .
            '<geo>' .
            '<plz>bar</plz>' .
            '</geo>' .
            '<kontaktperson>' .
            '<name>bar</name>' .
            '</kontaktperson>' .
            '<verwaltung_techn>' .
            '<openimmo_obid>foo</openimmo_obid>' .
            '<objektnr_extern>bar1234567</objektnr_extern>' .
            '</verwaltung_techn>' .
            '</immobilie>' .
            '<immobilie>' .
            '<objektkategorie>' .
            '<nutzungsart WOHNEN="1"/>' .
            '<vermarktungsart KAUF="1"/>' .
            '<objektart><zimmer/></objektart>' .
            '</objektkategorie>' .
            '<geo>' .
            '<plz>bar</plz>' .
            '</geo>' .
            '<kontaktperson>' .
            '<name>bar</name>' .
            '</kontaktperson>' .
            '<verwaltung_techn>' .
            '<openimmo_obid>foo</openimmo_obid>' .
            '<objektnr_extern>bar2345678</objektnr_extern>' .
            '</verwaltung_techn>' .
            '</immobilie>' .
            '<openimmo_anid>foo</openimmo_anid>' .
            '<firma>bar</firma>' .
            '</anbieter>' .
            '</openimmo>'
        );

        $records = $this->fixture->convertDomDocumentToArray($multipleRecords);
        $this->fixture->writeToDatabase($records[0]);
        $this->fixture->writeToDatabase($records[1]);

        self::assertSame(
            1,
            $this->testingFramework->countRecords(
                'tx_realty_objects',
                'owner =' . $feUserUid
            )
        );
    }

    /**
     * @test
     */
    public function importFromZipForUserWithObjectLimitReachedReturnsObjectLimitReachedErrorMessage()
    {
        $this->checkForZipArchive();
        $this->testingFramework->markTableAsDirty('tx_realty_objects');
        $this->testingFramework->markTableAsDirty('tx_realty_house_types');

        $this->testingFramework->createFrontEndUserGroup();
        $feUserGroupUid = $this->testingFramework->createFrontEndUserGroup();
        $feUserUid = $this->testingFramework->createFrontEndUser(
            $feUserGroupUid,
            [
                'tx_realty_openimmo_anid' => 'foo',
                'tx_realty_maximum_objects' => 1,
                'username' => 'fooBar',
            ]
        );
        $this->globalConfiguration->setAsBoolean('onlyImportForRegisteredFrontEndUsers', true);
        $this->globalConfiguration->setAsString('allowedFrontEndUserGroups', $feUserGroupUid);
        $this->globalConfiguration->setAsBoolean('useFrontEndUserDataAsContactDataForImportedRecords', true);
        $this->copyTestFileIntoImportFolder('two-objects.zip');

        self::assertContains(
            sprintf(
                $this->translator->translate('message_object_limit_reached'),
                'fooBar',
                $feUserUid,
                1
            ),
            $this->fixture->importFromZip()
        );
    }

    ////////////////////////////////////////////////////////////////////
    // Tests concerning the preparation of e-mails containing the log.
    ////////////////////////////////////////////////////////////////////

    /**
     * @test
     */
    public function prepareEmailsReturnsEmptyArrayWhenEmptyArrayGiven()
    {
        $emailData = [];

        self::assertSame(
            [],
            $this->fixture->prepareEmails($emailData)
        );
    }

    /**
     * @test
     */
    public function prepareEmailsReturnsEmptyArrayWhenInvalidArrayGiven()
    {
        $emailData = ['invalid' => 'array'];

        self::assertSame(
            [],
            $this->fixture->prepareEmails($emailData)
        );
    }

    /**
     * @test
     */
    public function prepareEmailsFillsEmptyEmailFieldWithDefaultAddressIfNotifyContactPersonsIsEnabled()
    {
        $this->globalConfiguration->setAsString('emailAddress', 'default_address@example.com');

        $emailData = [
            [
                'recipient' => '',
                'objectNumber' => 'foo',
                'logEntry' => 'bar',
                'errorLog' => 'bar',
            ],
        ];

        self::assertSame(
            [
                'default_address@example.com' => [
                    ['foo' => 'bar'],
                ],
            ],
            $this->fixture->prepareEmails($emailData)
        );
    }

    /**
     * @test
     */
    public function prepareEmailsReplacesNonEmptyEmailAddressIfNotifyContactPersonsIsDisabled()
    {
        $this->globalConfiguration->setAsString('emailAddress', 'default_address@example.com');
        $this->globalConfiguration->setAsBoolean('notifyContactPersons', false);
        $emailData = [
            [
                'recipient' => 'foo-valid@example.com',
                'objectNumber' => 'foo',
                'logEntry' => 'bar',
                'errorLog' => 'bar',
            ],
        ];

        self::assertSame(
            [
                'default_address@example.com' => [
                    ['foo' => 'bar'],
                ],
            ],
            $this->fixture->prepareEmails($emailData)
        );
    }

    /**
     * @test
     */
    public function prepareEmailsUsesLogEntryIfOnlyErrorsIsDisabled()
    {
        $this->globalConfiguration->setAsString('emailAddress', 'default_address@example.com');

        $emailData = [
            [
                'recipient' => '',
                'objectNumber' => 'foo',
                'logEntry' => 'log entry',
                'errorLog' => 'error log',
            ],
        ];

        self::assertSame(
            [
                'default_address@example.com' => [
                    ['foo' => 'log entry'],
                ],
            ],
            $this->fixture->prepareEmails($emailData)
        );
    }

    /**
     * @test
     */
    public function prepareEmailsUsesLogEntryIfOnlyErrorsIsEnabled()
    {
        $this->globalConfiguration->setAsBoolean('onlyErrors', true);
        $this->globalConfiguration->setAsString('emailAddress', 'default_address@example.com');

        $emailData = [
            [
                'recipient' => '',
                'objectNumber' => 'foo',
                'logEntry' => 'log entry',
                'errorLog' => 'error log',
            ],
        ];

        self::assertSame(
            [
                'default_address@example.com' => [
                    ['foo' => 'error log'],
                ],
            ],
            $this->fixture->prepareEmails($emailData)
        );
    }

    /**
     * @test
     */
    public function prepareEmailsFillsEmptyObjectNumberFieldWithWrapper()
    {
        $emailData = [
            [
                'recipient' => 'foo',
                'objectNumber' => '',
                'logEntry' => 'bar',
                'errorLog' => 'bar',
            ],
        ];

        self::assertSame(
            [
                'foo' => [
                    ['------' => 'bar'],
                ],
            ],
            $this->fixture->prepareEmails($emailData)
        );
    }

    /**
     * @test
     */
    public function prepareEmailsSortsMessagesForOneRecipientWhichHaveTheSameObjectNumber()
    {
        $emailData = [
            [
                'recipient' => 'foo',
                'objectNumber' => 'number',
                'logEntry' => 'bar',
                'errorLog' => 'bar',
            ],
            [
                'recipient' => 'foo',
                'objectNumber' => 'number',
                'logEntry' => 'foo',
                'errorLog' => 'foo',
            ],
        ];

        self::assertSame(
            [
                'foo' => [
                    ['number' => 'bar'],
                    ['number' => 'foo'],
                ],
            ],
            $this->fixture->prepareEmails($emailData)
        );
    }

    /**
     * @test
     */
    public function prepareEmailsSortsMessagesForTwoRecipientWhichHaveTheSameObjectNumber()
    {
        $emailData = [
            [
                'recipient' => 'foo',
                'objectNumber' => 'number',
                'logEntry' => 'foo',
                'errorLog' => 'foo',
            ],
            [
                'recipient' => 'bar',
                'objectNumber' => 'number',
                'logEntry' => 'bar',
                'errorLog' => 'bar',
            ],
        ];

        self::assertSame(
            [
                'foo' => [
                    ['number' => 'foo'],
                ],
                'bar' => [
                    ['number' => 'bar'],
                ],
            ],
            $this->fixture->prepareEmails($emailData)
        );
    }

    /**
     * @test
     */
    public function prepareEmailsSnipsObjectNumbersWithNothingToReport()
    {
        $emailData = [
            [
                'recipient' => 'foo',
                'objectNumber' => 'number',
                'logEntry' => 'bar',
                'errorLog' => 'bar',
            ],
            [
                'recipient' => 'foo',
                'objectNumber' => 'number',
                'logEntry' => '',
                'errorLog' => '',
            ],
        ];

        self::assertSame(
            [
                'foo' => [
                    ['number' => 'bar'],
                ],
            ],
            $this->fixture->prepareEmails($emailData)
        );
    }

    /**
     * @test
     */
    public function prepareEmailsSnipsRecipientWhoDoesNotReceiveMessages()
    {
        $emailData = [
            [
                'recipient' => 'foo',
                'objectNumber' => 'number',
                'logEntry' => '',
                'errorLog' => '',
            ],
            [
                'recipient' => 'foo',
                'objectNumber' => 'number',
                'logEntry' => '',
                'errorLog' => '',
            ],
        ];

        self::assertSame(
            [],
            $this->fixture->prepareEmails($emailData)
        );
    }

    /////////////////////////////////
    // Test for clearing the cache.
    /////////////////////////////////

    /**
     * @test
     */
    public function importFromZipClearsFrontEndCacheAfterImport()
    {
        $this->testingFramework->markTableAsDirty('tx_realty_objects');

        $this->copyTestFileIntoImportFolder('foo.zip');
        $pageUid = $this->testingFramework->createFrontEndPage();
        $this->testingFramework->createContentElement($pageUid, ['list_type' => 'realty_pi1']);

        /** @var AbstractCacheFrontEnd|PHPUnit_Framework_MockObject_MockObject $cacheFrontEnd */
        $cacheFrontEnd = $this->getMock(
            AbstractCacheFrontEnd::class,
            ['getIdentifier', 'set', 'get', 'getByTag', 'getBackend'],
            [],
            '',
            false
        );
        $cacheFrontEnd->expects(self::once())->method('getIdentifier')->will(self::returnValue('cache_pages'));
        /** @var TaggableBackendInterface|PHPUnit_Framework_MockObject_MockObject $cacheBackEnd */
        $cacheBackEnd = $this->getMock(TaggableBackendInterface::class);
        $cacheFrontEnd->method('getBackend')->will(self::returnValue($cacheBackEnd));
        $cacheBackEnd->expects(self::atLeastOnce())->method('flushByTag');

        $cacheManager = new CacheManager();
        $cacheManager->registerCache($cacheFrontEnd);
        tx_realty_cacheManager::injectCacheManager($cacheManager);

        $this->fixture->importFromZip();
    }

    /*
     * Tests concerning the log messages.
     */

    /**
     * @test
     */
    public function importFromZipReturnsLogMessageNoSchemaFileIfTheSchemaFileWasNotSet()
    {
        $this->checkForZipArchive();
        $this->testingFramework->markTableAsDirty('tx_realty_objects');

        $this->copyTestFileIntoImportFolder('foo.zip');
        $this->globalConfiguration->setAsString('openImmoSchema', '');

        self::assertContains(
            $this->translator->translate('message_no_schema_file'),
            $this->fixture->importFromZip()
        );
    }

    /**
     * @test
     */
    public function importFromZipReturnsLogMessageIncorrectSchemaFileIfTheSchemaFilePathWasIncorrect()
    {
        $this->checkForZipArchive();
        $this->testingFramework->markTableAsDirty('tx_realty_objects');

        $this->copyTestFileIntoImportFolder('foo.zip');
        $this->globalConfiguration->setAsString('openImmoSchema', '/any/not/existing/path');

        self::assertContains(
            $this->translator->translate('message_invalid_schema_file_path'),
            $this->fixture->importFromZip()
        );
    }

    /**
     * @test
     */
    public function importFromZipReturnsLogMessageMissingRequiredFields()
    {
        $this->checkForZipArchive();

        $this->copyTestFileIntoImportFolder('email.zip');
        $this->disableValidation();

        self::assertContains(
            $this->translator->translate('message_fields_required'),
            $this->fixture->importFromZip()
        );
    }

    /**
     * @test
     */
    public function importFromZipReturnsLogMessageThatNoRecordWasLoadedForZipWithNonOpenImmoXml()
    {
        $this->checkForZipArchive();

        $this->copyTestFileIntoImportFolder('bar.zip');
        $this->disableValidation();

        self::assertContains(
            $this->translator->translate('message_object_not_loaded'),
            $this->fixture->importFromZip()
        );
    }

    /**
     * @test
     */
    public function importFromZipReturnsMessageThatTheLogWasSentToTheDefaultAddressIfNoRecordWasLoaded()
    {
        $this->checkForZipArchive();

        $this->copyTestFileIntoImportFolder('foo.zip');
        $this->disableValidation();

        self::assertContains(
            'default-recipient@example.com',
            $this->fixture->importFromZip()
        );
    }

    /**
     * @test
     */
    public function importFromZipForNonExistingImportFolderReturnsFolderNotExistingErrorMessage()
    {
        $this->checkForZipArchive();

        $path = '/any/not/existing/import-path/';
        $this->globalConfiguration->setAsString('importFolder', $path);

        self::assertContains(
            sprintf(
                $this->translator->translate('message_import_directory_not_existing'),
                $path,
                get_current_user()
            ),
            $this->fixture->importFromZip()
        );
    }

    /**
     * @test
     */
    public function importFromZipForNonExistingUploadFolderReturnsFolderNotExistingErrorMessage()
    {
        $this->checkForZipArchive();
        $this->copyTestFileIntoImportFolder('foo.zip');

        $path = '/any/not/existing/upload-path/';
        $this->fixture->setUploadDirectory($path);

        self::assertContains(
            sprintf(
                $this->translator->translate('message_upload_directory_not_existing'),
                $path
            ),
            $this->fixture->importFromZip()
        );
    }

    /*
     * Tests for setting the PID
     */

    /**
     * @test
     */
    public function importedRecordHasTheConfiguredPidByDefault()
    {
        $this->checkForZipArchive();
        $this->testingFramework->markTableAsDirty('tx_realty_objects');
        $this->testingFramework->markTableAsDirty('tx_realty_house_types');

        $this->copyTestFileIntoImportFolder('same-name.zip');
        $this->disableValidation();

        $this->fixture->importFromZip();

        self::assertSame(
            1,
            $this->testingFramework->countRecords(
                'tx_realty_objects',
                'object_number="bar1234567" '
                . 'AND pid=' . $this->systemFolderPid . Tx_Oelib_Db::enableFields('tx_realty_objects')
            )
        );
    }

    /////////////////////////////////
    // Testing the e-mail contents.
    /////////////////////////////////
    // * Tests for the subject.
    /////////////////////////////

    /**
     * @test
     */
    public function emailSubjectIsSetCorrectly()
    {
        $this->checkForZipArchive();
        $this->testingFramework->markTableAsDirty('tx_realty_objects');

        $this->copyTestFileIntoImportFolder('email.zip');
        $this->fixture->importFromZip();

        self::assertSame(
            $this->translator->translate('label_subject_openImmo_import'),
            $this->message->getSubject()
        );
    }

    /*
     * Tests concerning the sender
     */

    /**
     * @test
     */
    public function usesEmailFromSetInInstallTool()
    {
        $this->checkForZipArchive();
        $this->testingFramework->markTableAsDirty('tx_realty_objects');

        $this->copyTestFileIntoImportFolder('email.zip');
        $this->fixture->importFromZip();

        $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress'] = 'sender@example.com';
        $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromName'] = 'import sender';

        self::assertSame(
            ['sender@example.com' => 'import sender'],
            $this->message->getFrom()
        );
    }

    //////////////////////////////////////
    // * Tests concerning the recipient.
    //////////////////////////////////////

    /**
     * @test
     */
    public function emailIsSentToContactEmailForValidContactEmailAndObjectAsContactDataSource()
    {
        $this->checkForZipArchive();
        $this->testingFramework->markTableAsDirty('tx_realty_objects');

        $this->copyTestFileIntoImportFolder('valid-email.zip');
        $this->fixture->importFromZip();

        self::assertArrayHasKey(
            'contact-email-address@example.com',
            $this->message->getTo()
        );
    }

    /**
     * @test
     */
    public function emailIsSentToDefaultEmailForInvalidContactEmailAndObjectAsContactDataSource()
    {
        $this->checkForZipArchive();
        $this->testingFramework->markTableAsDirty('tx_realty_objects');

        $this->copyTestFileIntoImportFolder('email.zip');
        $this->fixture->importFromZip();

        self::assertArrayHasKey(
            'default-recipient@example.com',
            $this->message->getTo()
        );
    }

    /**
     * @test
     */
    public function emailIsSentToDefaultAddressIfARecordIsNotLoadable()
    {
        $this->checkForZipArchive();
        $this->testingFramework->markTableAsDirty('tx_realty_objects');

        $this->copyTestFileIntoImportFolder('foo.zip');
        $this->fixture->importFromZip();

        self::assertArrayHasKey(
            'default-recipient@example.com',
            $this->message->getTo()
        );
    }

    /**
     * @test
     */
    public function emailIsSentToOwnersAddressForMatchingAnidAndNoContactEmailProvidedAndOwnerAsContactDataSource()
    {
        $this->checkForZipArchive();

        $this->testingFramework->createFrontEndUser(
            '',
            [
                'tx_realty_openimmo_anid' => 'test-anid',
                'email' => 'owner-address@example.com',
            ]
        );
        $this->globalConfiguration->setAsBoolean(
            'useFrontEndUserDataAsContactDataForImportedRecords',
            true
        );
        $this->testingFramework->markTableAsDirty('tx_realty_objects');
        $this->copyTestFileIntoImportFolder('with-openimmo-anid.zip');
        $this->fixture->importFromZip();

        self::assertArrayHasKey(
            'owner-address@example.com',
            $this->message->getTo()
        );
    }

    /**
     * @test
     */
    public function emailIsSentToOwnersAddressForMatchingAnidAndSetContactEmailAndOwnerAsContactDataSource()
    {
        $this->checkForZipArchive();

        $this->testingFramework->createFrontEndUser(
            '',
            [
                'tx_realty_openimmo_anid' => 'test-anid',
                'email' => 'owner-address@example.com',
            ]
        );
        $this->globalConfiguration->setAsBoolean(
            'useFrontEndUserDataAsContactDataForImportedRecords',
            true
        );
        $this->testingFramework->markTableAsDirty('tx_realty_objects');
        $this->copyTestFileIntoImportFolder('with-email-and-openimmo-anid.zip');
        $this->fixture->importFromZip();

        self::assertArrayHasKey(
            'owner-address@example.com',
            $this->message->getTo()
        );
    }

    /**
     * @test
     */
    public function emailIsSentToContactAddressForNonMatchingAnidAndSetContactEmailAndOwnerAsContactDataSource()
    {
        $this->checkForZipArchive();

        $this->testingFramework->createFrontEndUser(
            '',
            [
                'tx_realty_openimmo_anid' => 'another-test-anid',
                'email' => 'owner-address@example.com',
            ]
        );
        $this->globalConfiguration->setAsBoolean(
            'useFrontEndUserDataAsContactDataForImportedRecords',
            true
        );
        $this->testingFramework->markTableAsDirty('tx_realty_objects');
        $this->copyTestFileIntoImportFolder('with-email-and-openimmo-anid.zip');
        $this->fixture->importFromZip();

        self::assertArrayHasKey(
            'contact-email-address@example.com',
            $this->message->getTo()
        );
    }

    /**
     * @test
     */
    public function emailIsSentToContactAddressForNoAnidAndSetContactEmailAndOwnerAsContactDataSource()
    {
        $this->checkForZipArchive();

        $this->testingFramework->createFrontEndUser(
            '',
            [
                'tx_realty_openimmo_anid' => 'test-anid',
                'email' => 'owner-address@example.com',
            ]
        );
        $this->globalConfiguration->setAsBoolean(
            'useFrontEndUserDataAsContactDataForImportedRecords',
            true
        );
        $this->testingFramework->markTableAsDirty('tx_realty_objects');
        $this->copyTestFileIntoImportFolder('valid-email.zip');
        $this->fixture->importFromZip();

        self::assertArrayHasKey(
            'contact-email-address@example.com',
            $this->message->getTo()
        );
    }

    /**
     * @test
     */
    public function emailIsSentToDefaultAddressForNonMatchingAnidAndNoContactEmailAndOwnerContactDataSource()
    {
        $this->checkForZipArchive();

        $this->testingFramework->createFrontEndUser(
            '',
            [
                'tx_realty_openimmo_anid' => 'another-test-anid',
                'email' => 'owner-address@example.com',
            ]
        );
        $this->globalConfiguration->setAsBoolean(
            'useFrontEndUserDataAsContactDataForImportedRecords',
            true
        );
        $this->testingFramework->markTableAsDirty('tx_realty_objects');
        $this->copyTestFileIntoImportFolder('with-openimmo-anid.zip');
        $this->fixture->importFromZip();

        self::assertArrayHasKey(
            'default-recipient@example.com',
            $this->message->getTo()
        );
    }

    /**
     * @test
     */
    public function emailIsSentToDefaultAddressForNeitherAnidNorContactEmailProvidedAndOwnerAsContactDataSource()
    {
        $this->checkForZipArchive();

        $this->testingFramework->createFrontEndUser(
            '',
            [
                'tx_realty_openimmo_anid' => 'test-anid',
                'email' => 'owner-address@example.com',
            ]
        );
        $this->globalConfiguration->setAsBoolean(
            'useFrontEndUserDataAsContactDataForImportedRecords',
            true
        );
        $this->testingFramework->markTableAsDirty('tx_realty_objects');
        $this->copyTestFileIntoImportFolder('foo.zip');
        $this->fixture->importFromZip();

        self::assertArrayHasKey(
            'default-recipient@example.com',
            $this->message->getTo()
        );
    }

    ///////////////////////////////////
    // * Testing the e-mail contents.
    ///////////////////////////////////

    /**
     * @test
     */
    public function sentEmailContainsTheObjectNumberLabel()
    {
        $this->checkForZipArchive();
        $this->testingFramework->markTableAsDirty('tx_realty_objects');

        $this->copyTestFileIntoImportFolder('email.zip');
        $this->fixture->importFromZip();

        self::assertContains(
            $this->translator->translate('label_object_number'),
            $this->message->getBody()
        );
    }

    /**
     * @test
     */
    public function sentEmailContainsTheIntroductionMessage()
    {
        $this->checkForZipArchive();
        $this->testingFramework->markTableAsDirty('tx_realty_objects');

        $this->copyTestFileIntoImportFolder('email.zip');
        $this->fixture->importFromZip();

        self::assertContains(
            $this->translator->translate('message_introduction'),
            $this->message->getBody()
        );
    }

    /**
     * @test
     */
    public function sentEmailContainsTheExplanationMessage()
    {
        $this->checkForZipArchive();
        $this->testingFramework->markTableAsDirty('tx_realty_objects');

        $this->copyTestFileIntoImportFolder('email.zip');
        $this->fixture->importFromZip();

        self::assertContains(
            $this->translator->translate('message_explanation'),
            $this->message->getBody()
        );
    }

    /**
     * @test
     */
    public function sentEmailContainsMessageThatARecordWasNotImportedForMismatchingAnidsAndEnabledOwnerRestriction()
    {
        $this->checkForZipArchive();
        $this->testingFramework->markTableAsDirty('tx_realty_objects');

        $this->globalConfiguration->setAsBoolean(
            'onlyImportForRegisteredFrontEndUsers',
            true
        );
        $this->copyTestFileIntoImportFolder('same-name.zip');
        $this->fixture->importFromZip();

        self::assertContains(
            $this->translator->translate('message_openimmo_anid_not_matches_allowed_fe_user'),
            $this->message->getBody()
        );
    }

    /**
     * @test
     */
    public function sentEmailForUserWhoReachedHisObjectLimitContainsMessageThatRecordWasNotImported()
    {
        $this->checkForZipArchive();
        $this->testingFramework->markTableAsDirty('tx_realty_objects');
        $this->testingFramework->markTableAsDirty('tx_realty_house_types');

        $feUserGroupUid = $this->testingFramework->createFrontEndUserGroup();
        $feUserUid = $this->testingFramework->createFrontEndUser(
            $feUserGroupUid,
            [
                'tx_realty_openimmo_anid' => 'foo',
                'tx_realty_maximum_objects' => 1,
                'username' => 'fooBar',
            ]
        );
        $this->globalConfiguration->setAsBoolean(
            'useFrontEndUserDataAsContactDataForImportedRecords',
            true
        );
        $this->globalConfiguration->setAsString(
            'allowedFrontEndUserGroups',
            $feUserGroupUid
        );

        $this->globalConfiguration->setAsBoolean(
            'onlyImportForRegisteredFrontEndUsers',
            true
        );
        $this->copyTestFileIntoImportFolder('two-objects.zip');
        $this->fixture->importFromZip();

        self::assertContains(
            sprintf(
                $this->translator->translate('message_object_limit_reached'),
                'fooBar',
                $feUserUid,
                1
            ),
            $this->message->getBody()
        );
    }

    /*
     * Tests for deleting objects for full sync
     */

    /**
     * @test
     */
    public function defaultSyncWithDeletingEnabledKeepsUnmentionedObjectsWithSameAnid()
    {
        $this->globalConfiguration->setAsBoolean('importCanDeleteRecordsForFullSync', true);
        $this->testingFramework->markTableAsDirty('tx_realty_house_types');

        $anid = '12341-12341-12341';
        $obid = '1v24512-1g423512gv4-1gv2';

        $uid = $this->testingFramework->createRecord(
            'tx_realty_objects',
            [
                'pid' => $this->systemFolderPid,
                'openimmo_anid' => $anid,
                'openimmo_obid' => $obid,
            ]
        );

        $xml =
            '<openimmo>
                <anbieter>
                    <immobilie>
                        <objektkategorie>
                            <nutzungsart WOHNEN="1"/>
                            <vermarktungsart KAUF="1"/>
                            <objektart><zimmer/></objektart>
                        </objektkategorie>
                        <geo>
                            <plz>bar</plz>
                        </geo>
                        <kontaktperson>
                            <name>bar</name>
                            <email_zentrale>bar</email_zentrale>
                        </kontaktperson>
                        <verwaltung_techn>
                            <openimmo_obid>other-obid</openimmo_obid>
                            <aktion/>
                            <objektnr_extern>bar1234567</objektnr_extern>
                        </verwaltung_techn>
                    </immobilie>
                    <openimmo_anid>' . $anid . '</openimmo_anid>
                    <firma>bar</firma>
                </anbieter>
            </openimmo>';
        $this->createZipFile($xml);

        $this->fixture->importFromZip();

        self::assertSame(
            1,
            $this->testingFramework->countRecords('tx_realty_objects', 'uid = ' . $uid . ' AND deleted = 0')
        );
    }

    /**
     * @test
     */
    public function partialSyncWithDeletingEnabledKeepsUnmentionedObjectsWithSameAnid()
    {
        $this->globalConfiguration->setAsBoolean('importCanDeleteRecordsForFullSync', true);
        $this->testingFramework->markTableAsDirty('tx_realty_house_types');

        $anid = '12341-12341-12341';
        $obid = '1v24512-1g423512gv4-1gv2';

        $uid = $this->testingFramework->createRecord(
            'tx_realty_objects',
            [
                'pid' => $this->systemFolderPid,
                'openimmo_anid' => $anid,
                'openimmo_obid' => $obid,
            ]
        );

        $xml =
            '<openimmo>
                <uebertragung umfang="TEIL"/>
                <anbieter>
                    <immobilie>
                        <objektkategorie>
                            <nutzungsart WOHNEN="1"/>
                            <vermarktungsart KAUF="1"/>
                            <objektart><zimmer/></objektart>
                        </objektkategorie>
                        <geo>
                            <plz>bar</plz>
                        </geo>
                        <kontaktperson>
                            <name>bar</name>
                            <email_zentrale>bar</email_zentrale>
                            </kontaktperson>
                        <verwaltung_techn>
                            <openimmo_obid>other-obid</openimmo_obid>
                            <aktion/>
                            <objektnr_extern>bar1234567</objektnr_extern>
                        </verwaltung_techn>
                    </immobilie>
                    <openimmo_anid>' . $anid . '</openimmo_anid>
                    <firma>bar</firma>
                </anbieter>
            </openimmo>';
        $this->createZipFile($xml);

        $this->fixture->importFromZip();

        self::assertSame(
            1,
            $this->testingFramework->countRecords('tx_realty_objects', 'uid = ' . $uid . ' AND deleted = 0')
        );
    }

    /**
     * @test
     */
    public function fullSyncWithDeletingEnabledKeepsMentionedObjectsWithSameAnidAndObjectNumber()
    {
        $this->globalConfiguration->setAsBoolean('importCanDeleteRecordsForFullSync', true);
        $this->testingFramework->markTableAsDirty('tx_realty_house_types');

        $anid = '12341-12341-12341';
        $obid = '1v24512-1g423512gv4-1gv2';
        $objectNumber = 'bar1234567';

        $uid = $this->testingFramework->createRecord(
            'tx_realty_objects',
            [
                'pid' => $this->systemFolderPid,
                'openimmo_anid' => $anid,
                'openimmo_obid' => $obid,
                'object_number' => $objectNumber,
            ]
        );

        $xml =
            '<openimmo>
                <uebertragung umfang="VOLL"/>
                <anbieter>
                    <immobilie>
                        <objektkategorie>
                            <nutzungsart WOHNEN="1"/>
                            <vermarktungsart KAUF="1"/>
                            <objektart><zimmer/></objektart>
                        </objektkategorie>
                        <geo>
                            <plz>bar</plz>
                        </geo>
                        <kontaktperson>
                            <name>bar</name>
                            <email_zentrale>bar</email_zentrale>
                        </kontaktperson>
                        <verwaltung_techn>
                            <openimmo_obid>' . $obid . '</openimmo_obid>
                            <aktion/>
                            <objektnr_extern>' . $objectNumber . '</objektnr_extern>
                        </verwaltung_techn>
                    </immobilie>
                    <openimmo_anid>' . $anid . '</openimmo_anid>
                    <firma>bar</firma>
                </anbieter>
            </openimmo>';
        $this->createZipFile($xml);

        $this->fixture->importFromZip();

        self::assertSame(
            1,
            $this->testingFramework->countRecords('tx_realty_objects', 'uid = ' . $uid . ' AND deleted = 0')
        );
    }

    /**
     * @test
     */
    public function fullSyncWithDeletingEnabledKeepsUnmentionedObjectsWithOtherAnid()
    {
        $this->globalConfiguration->setAsBoolean('importCanDeleteRecordsForFullSync', true);
        $this->testingFramework->markTableAsDirty('tx_realty_house_types');

        $anid = '12341-12341-12341';
        $obid = '1v24512-1g423512gv4-1gv2';

        $uid = $this->testingFramework->createRecord(
            'tx_realty_objects',
            [
                'pid' => $this->systemFolderPid,
                'openimmo_anid' => $anid,
                'openimmo_obid' => $obid,
            ]
        );

        $xml =
            '<openimmo>
                <uebertragung umfang="VOLL"/>
                <anbieter>
                    <immobilie>
                        <objektkategorie>
                            <nutzungsart WOHNEN="1"/>
                            <vermarktungsart KAUF="1"/>
                            <objektart><zimmer/></objektart>
                        </objektkategorie>
                        <geo>
                            <plz>bar</plz>
                        </geo>
                        <kontaktperson>
                            <name>bar</name>
                            <email_zentrale>bar</email_zentrale>
                        </kontaktperson>
                        <verwaltung_techn>
                            <openimmo_obid>other-obid</openimmo_obid>
                            <aktion/>
                            <objektnr_extern>bar1234567</objektnr_extern>
                        </verwaltung_techn>
                    </immobilie>
                    <openimmo_anid>other-anid</openimmo_anid>
                    <firma>bar</firma>
                </anbieter>
            </openimmo>';
        $this->createZipFile($xml);

        $this->fixture->importFromZip();

        self::assertSame(
            1,
            $this->testingFramework->countRecords('tx_realty_objects', 'uid = ' . $uid . ' AND deleted = 0')
        );
    }

    /**
     * @test
     */
    public function fullSyncWithDeletingEnabledDeletesUnmentionedObjectsWithSameNonEmptyAnid()
    {
        $this->globalConfiguration->setAsBoolean('importCanDeleteRecordsForFullSync', true);
        $this->testingFramework->markTableAsDirty('tx_realty_house_types');

        $anid = '12341-12341-12341';
        $obid = '1v24512-1g423512gv4-1gv2';

        $uid = $this->testingFramework->createRecord(
            'tx_realty_objects',
            [
                'pid' => $this->systemFolderPid,
                'openimmo_anid' => $anid,
                'openimmo_obid' => $obid,
            ]
        );

        $xml =
            '<openimmo>
                <uebertragung umfang="VOLL"/>
                <anbieter>
                    <immobilie>
                        <objektkategorie>
                            <nutzungsart WOHNEN="1"/>
                            <vermarktungsart KAUF="1"/>
                            <objektart><zimmer/></objektart>
                        </objektkategorie>
                        <geo>
                            <plz>bar</plz>
                        </geo>
                        <kontaktperson>
                            <name>bar</name>
                            <email_zentrale>bar</email_zentrale>
                            </kontaktperson>
                        <verwaltung_techn>
                            <openimmo_obid>other-obid</openimmo_obid>
                            <aktion/>
                            <objektnr_extern>bar1234567</objektnr_extern>
                            </verwaltung_techn>
                    </immobilie>
                    <openimmo_anid>' . $anid . '</openimmo_anid>
                    <firma>bar</firma>
                </anbieter>
            </openimmo>';
        $this->createZipFile($xml);

        $this->fixture->importFromZip();

        self::assertSame(
            1,
            $this->testingFramework->countRecords('tx_realty_objects', 'uid = ' . $uid . ' AND deleted = 1')
        );
    }

    /**
     * @test
     */
    public function fullSyncWithDeletingEnabledDeletesUnmentionedObjectsWithSameEmptyAnid()
    {
        $this->globalConfiguration->setAsBoolean('importCanDeleteRecordsForFullSync', true);
        $this->testingFramework->markTableAsDirty('tx_realty_house_types');

        $obid = '1v24512-1g423512gv4-1gv2';

        $uid = $this->testingFramework->createRecord(
            'tx_realty_objects',
            [
                'pid' => $this->systemFolderPid,
                'openimmo_obid' => $obid,
            ]
        );

        $xml =
            '<openimmo>
                <uebertragung umfang="VOLL"/>
                <anbieter>
                    <immobilie>
                        <objektkategorie>
                            <nutzungsart WOHNEN="1"/>
                            <vermarktungsart KAUF="1"/>
                            <objektart><zimmer/></objektart>
                        </objektkategorie>
                        <geo>
                            <plz>bar</plz>
                        </geo>
                        <kontaktperson>
                            <name>bar</name>
                            <email_zentrale>bar</email_zentrale>
                            </kontaktperson>
                        <verwaltung_techn>
                            <openimmo_obid>other-obid</openimmo_obid>
                            <aktion/>
                            <objektnr_extern>bar1234567</objektnr_extern>
                            </verwaltung_techn>
                    </immobilie>
                    <openimmo_anid/>
                    <firma>bar</firma>
                </anbieter>
            </openimmo>';
        $this->createZipFile($xml);

        $this->fixture->importFromZip();

        self::assertSame(
            1,
            $this->testingFramework->countRecords('tx_realty_objects', 'uid = ' . $uid . ' AND deleted = 1')
        );
    }

    /**
     * @test
     */
    public function fullSyncWithDeletingEnabledLogsDeletion()
    {
        $this->globalConfiguration->setAsBoolean('importCanDeleteRecordsForFullSync', true);
        $this->testingFramework->markTableAsDirty('tx_realty_house_types');

        $anid = '12341-12341-12341';
        $obid = '1v24512-1g423512gv4-1gv2';

        $uid = $this->testingFramework->createRecord(
            'tx_realty_objects',
            [
                'pid' => $this->systemFolderPid,
                'openimmo_anid' => $anid,
                'openimmo_obid' => $obid,
            ]
        );

        $xml =
            '<openimmo>
                <uebertragung umfang="VOLL"/>
                <anbieter>
                    <immobilie>
                        <objektkategorie>
                            <nutzungsart WOHNEN="1"/>
                            <vermarktungsart KAUF="1"/>
                            <objektart><zimmer/></objektart>
                        </objektkategorie>
                        <geo>
                            <plz>bar</plz>
                        </geo>
                        <kontaktperson>
                            <name>bar</name>
                            <email_zentrale>bar</email_zentrale>
                            </kontaktperson>
                        <verwaltung_techn>
                            <openimmo_obid>other-obid</openimmo_obid>
                            <aktion/>
                            <objektnr_extern>bar1234567</objektnr_extern>
                            </verwaltung_techn>
                    </immobilie>
                    <openimmo_anid>' . $anid . '</openimmo_anid>
                    <firma>bar</firma>
                </anbieter>
            </openimmo>';
        $this->createZipFile($xml);

        $result = $this->fixture->importFromZip();

        $message = $this->translator->translate('message_deleted_objects_from_full_sync') . ' ' . $uid;
        self::assertContains($message, $result);
    }

    /**
     * @test
     */
    public function fullSyncWithDeletingEnabledWithoutAnidKeepsUnmentionedObjectsWithNonImportPid()
    {
        $this->globalConfiguration->setAsBoolean('importCanDeleteRecordsForFullSync', true);
        $this->testingFramework->markTableAsDirty('tx_realty_house_types');

        $anid = '12341-12341-12341';
        $obid = '1v24512-1g423512gv4-1gv2';

        $pid = $this->testingFramework->createSystemFolder();
        $uid = $this->testingFramework->createRecord(
            'tx_realty_objects',
            [
                'pid' => $pid,
                'openimmo_anid' => $anid,
                'openimmo_obid' => $obid,
            ]
        );

        $xml =
            '<openimmo>
                <uebertragung umfang="VOLL"/>
                <anbieter>
                    <immobilie>
                        <objektkategorie>
                            <nutzungsart WOHNEN="1"/>
                            <vermarktungsart KAUF="1"/>
                            <objektart><zimmer/></objektart>
                        </objektkategorie>
                        <geo>
                            <plz>bar</plz>
                        </geo>
                        <kontaktperson>
                            <name>bar</name>
                            <email_zentrale>bar</email_zentrale>
                            </kontaktperson>
                        <verwaltung_techn>
                            <openimmo_obid>other-obid</openimmo_obid>
                            <aktion/>
                            <objektnr_extern>bar1234567</objektnr_extern>
                            </verwaltung_techn>
                    </immobilie>
                    <openimmo_anid>' . $anid . '</openimmo_anid>
                    <firma>bar</firma>
                </anbieter>
            </openimmo>';
        $this->createZipFile($xml);

        $this->fixture->importFromZip();

        self::assertSame(
            1,
            $this->testingFramework->countRecords('tx_realty_objects', 'uid = ' . $uid . ' AND deleted = 0')
        );
    }

    /**
     * @test
     */
    public function fullSyncWithDeletingDisabledKeepsUnmentionedObjectsWithSameAnid()
    {
        $this->globalConfiguration->setAsBoolean('importCanDeleteRecordsForFullSync', false);
        $this->testingFramework->markTableAsDirty('tx_realty_house_types');

        $anid = '12341-12341-12341';
        $obid = '1v24512-1g423512gv4-1gv2';

        $uid = $this->testingFramework->createRecord(
            'tx_realty_objects',
            [
                'pid' => $this->systemFolderPid,
                'openimmo_anid' => $anid,
                'openimmo_obid' => $obid,
            ]
        );

        $xml =
            '<openimmo>
                <uebertragung umfang="VOLL"/>
                <anbieter>
                    <immobilie>
                        <objektkategorie>
                            <nutzungsart WOHNEN="1"/>
                            <vermarktungsart KAUF="1"/>
                            <objektart><zimmer/></objektart>
                        </objektkategorie>
                        <geo>
                            <plz>bar</plz>
                        </geo>
                        <kontaktperson>
                            <name>bar</name>
                            <email_zentrale>bar</email_zentrale>
                            </kontaktperson>
                        <verwaltung_techn>
                            <openimmo_obid>other-obid</openimmo_obid>
                            <aktion/>
                            <objektnr_extern>bar1234567</objektnr_extern>
                            </verwaltung_techn>
                    </immobilie>
                    <openimmo_anid>' . $anid . '</openimmo_anid>
                    <firma>bar</firma>
                </anbieter>
            </openimmo>';
        $this->createZipFile($xml);

        $this->fixture->importFromZip();

        self::assertSame(
            1,
            $this->testingFramework->countRecords('tx_realty_objects', 'uid = ' . $uid . ' AND deleted = 0')
        );
    }

    /**
     * @test
     */
    public function wasSuccessfulInitiallyReturnsTrue()
    {
        static::assertTrue($this->fixture->wasSuccessful());
    }

    /**
     * @test
     */
    public function wasSuccessfulAfterSuccessfulImportReturnsTrue()
    {
        $this->checkForZipArchive();
        $this->testingFramework->markTableAsDirty('tx_realty_objects');
        $this->testingFramework->markTableAsDirty('tx_realty_house_types');

        $this->copyTestFileIntoImportFolder('two-objects.zip');
        $this->fixture->importFromZip();

        static::assertTrue($this->fixture->wasSuccessful());
    }

    /**
     * @test
     */
    public function wasSuccessfulAfterErrorReturnsTrue()
    {
        $this->checkForZipArchive();

        $path = '/any/not/existing/import-path/';
        $this->globalConfiguration->setAsString('importFolder', $path);

        $this->fixture->importFromZip();

        static::assertFalse($this->fixture->wasSuccessful());
    }
}
