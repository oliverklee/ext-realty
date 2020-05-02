<?php

namespace OliverKlee\Realty\Tests\Functional\Import;

use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use OliverKlee\Realty\Tests\Functional\Traits\FalHelper;
use OliverKlee\Realty\Tests\Unit\Import\Fixtures\TestingImmoImport;
use TYPO3\CMS\Core\Cache\Backend\TaggableBackendInterface;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\AbstractFrontend as AbstractCacheFrontEnd;
use TYPO3\CMS\Core\Mail\MailMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Lang\LanguageService;

/**
 * Test case.
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class OpenImmoImportTest extends FunctionalTestCase
{
    use FalHelper;

    /**
     * @var string[]
     */
    protected $testExtensionsToLoad = [
        'typo3conf/ext/static_info_tables',
        'typo3conf/ext/oelib',
        'typo3conf/ext/realty',
    ];

    /**
     * @var TestingImmoImport
     */
    private $subject = null;

    /**
     * @var \Tx_Oelib_TestingFramework
     */
    private $testingFramework = null;

    /**
     * @var \Tx_Oelib_ConfigurationProxy
     */
    private $globalConfiguration = null;

    /**
     * @var \tx_realty_translator
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
     * @var bool
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
     * @var MailMessage|\PHPUnit_Framework_MockObject_MockObject
     */
    private $message = null;

    /**
     * @var LanguageService|null
     */
    private $languageServiceBackup = null;

    protected function setUp()
    {
        parent::setUp();
        $this->graphicsConfigurationBackup = $GLOBALS['TYPO3_CONF_VARS']['GFX'];
        $this->emailConfigurationBackup = $GLOBALS['TYPO3_CONF_VARS']['MAIL'];
        $this->languageServiceBackup = $this->getLanguageService();
        $GLOBALS['LANG'] = new LanguageService();

        $this->provideAdminBackEndUserForFal();

        $this->testingFramework = new \Tx_Oelib_TestingFramework('tx_realty');
        $this->systemFolderPid = $this->testingFramework->createSystemFolder();
        $this->importFolder = PATH_site . 'typo3temp/tx_realty_fixtures/';
        GeneralUtility::mkdir_deep($this->importFolder);

        \Tx_Oelib_MapperRegistry::getInstance()->activateTestingMode($this->testingFramework);

        $this->globalConfiguration = \Tx_Oelib_ConfigurationProxy::getInstance('realty');

        $this->translator = new \tx_realty_translator();

        $this->subject = new TestingImmoImport(true);
        $this->setupStaticConditions();

        $this->message = $this->getMockBuilder(MailMessage::class)->setMethods(['send'])->getMock();
        GeneralUtility::addInstance(MailMessage::class, $this->message);
    }

    protected function tearDown()
    {
        // Get any surplus instances added via GeneralUtility::addInstance.
        GeneralUtility::makeInstance(MailMessage::class);

        $this->testingFramework->cleanUpWithoutDatabase();
        $this->deleteTestFolders();

        \tx_realty_cacheManager::purgeCacheManager();
        $GLOBALS['LANG'] = $this->languageServiceBackup;
        $GLOBALS['TYPO3_CONF_VARS']['GFX'] = $this->graphicsConfigurationBackup;
        $GLOBALS['TYPO3_CONF_VARS']['MAIL'] = $this->emailConfigurationBackup;
        parent::tearDown();
    }

    /*
     * Utility functions.
     */

    /**
     * Sets the global configuration values which need to be static during the tests.
     *
     * @return void
     */
    private function setupStaticConditions()
    {
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
     * Copies a file or a folder from the extension's Fixtures/ folder into the temporary test import folder.
     *
     * @param string $fileName
     *        File or folder to copy. Must be a relative path to existent files within the Fixtures/ folder.
     *        Leave empty to create an empty import folder.
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
                __DIR__ . '/Fixtures/' . $fileName,
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
    private function deleteTestFolders()
    {
        if ($this->testImportFolderExists) {
            GeneralUtility::rmdir($this->importFolder, true);
            $this->testImportFolderExists = false;
        }
        $extractionDirectory = PATH_site . 'typo3temp/var/realty/';
        if (\is_dir($extractionDirectory)) {
            GeneralUtility::rmdir($extractionDirectory, true);
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
            \glob($this->importFolder . '*.zip', GLOB_ERR),
            array_values($this->subject->getPathsOfZipsToExtract($this->importFolder))
        );
    }

    /**
     * @test
     */
    public function getNameForExtractionFolderReturnsPathWithinTypo3Temp()
    {
        $this->copyTestFileIntoImportFolder('bar.zip');

        $result = $this->subject->getNameForExtractionFolder('bar.zip');

        self::assertSame(PATH_site . 'typo3temp/var/realty/bar/', $result);
    }

    /**
     * @test
     */
    public function createExtractionFolderForExistingZipCreatesFolder()
    {
        $this->copyTestFileIntoImportFolder('foo.zip');
        $this->subject->createExtractionFolder($this->importFolder . 'foo.zip');

        self::assertDirectoryExists(PATH_site . 'typo3temp/var/realty/foo/');
    }

    /**
     * @test
     */
    public function createExtractionFolderForNonExistingZipNotCreatesExtractionFolder()
    {
        $this->copyTestFileIntoImportFolder('');
        $this->subject->createExtractionFolder($this->importFolder . 'foobar.zip');

        self::assertDirectoryNotExists(PATH_site . 'typo3temp/var/realty/foobar/');
    }

    /**
     * @test
     */
    public function createExtractionFolderForNonExistingZipReturnsEmptyString()
    {
        $this->copyTestFileIntoImportFolder('');
        $dirName = $this->subject->createExtractionFolder($this->importFolder . 'foobar.zip');

        self::assertSame('', $dirName);
    }

    /**
     * @test
     */
    public function extractZipForExistingZipToExtractCreatesExtractionFolder()
    {
        $this->copyTestFileIntoImportFolder('foo.zip');
        $this->subject->extractZip($this->importFolder . 'foo.zip');

        self::assertDirectoryExists(PATH_site . 'typo3temp/var/realty/foo/');
    }

    /**
     * @test
     */
    public function extractZipForInexistentZipToExtractNotCreatesExtractionFolder()
    {
        $this->copyTestFileIntoImportFolder('');
        $this->subject->extractZip($this->importFolder . 'foobar.zip');

        self::assertDirectoryNotExists(PATH_site . 'typo3temp/var/realty/foobar/');
    }

    /**
     * @test
     */
    public function getPathForXmlIfFolderWithOneXmlExists()
    {
        $this->copyTestFileIntoImportFolder('foo.zip');
        $this->subject->extractZip($this->importFolder . 'foo.zip');

        self::assertSame(
            PATH_site . 'typo3temp/var/realty/foo/foo.xml',
            $this->subject->getPathForXml($this->importFolder . 'foo.zip')
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
            $this->subject->getPathForXml($this->importFolder . 'foo.zip')
        );
    }

    /**
     * @test
     */
    public function getPathForXmlIfFolderWithTwoXmlExists()
    {
        $this->copyTestFileIntoImportFolder('bar-bar.zip');
        $this->subject->extractZip($this->importFolder . 'bar-bar.zip');

        self::assertSame(
            '',
            $this->subject->getPathForXml($this->importFolder . 'bar-bar.zip')
        );
    }

    /**
     * @test
     */
    public function getPathForXmlIfFolderWithoutXmlExists()
    {
        $this->copyTestFileIntoImportFolder('empty.zip');
        $this->subject->extractZip($this->importFolder . 'empty.zip');

        self::assertSame(
            '',
            $this->subject->getPathForXml($this->importFolder . 'empty.zip')
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

    ////////////////////////////////
    // Tests concerning cleanUp().
    ////////////////////////////////

    /**
     * @test
     */
    public function cleanUpRemovesAFolderCreatedByTheImporter()
    {
        $this->copyTestFileIntoImportFolder('foo.zip');
        $this->subject->createExtractionFolder($this->importFolder . 'foo.zip');
        $this->subject->cleanUp($this->importFolder);

        self::assertDirectoryNotExists(
            $this->importFolder . 'foo/'
        );
    }

    /**
     * @test
     */
    public function cleanUpDoesNotRemoveAForeignFolderAlthoughItIsNamedLikeAZipToImport()
    {
        $this->copyTestFileIntoImportFolder('foo.zip');
        GeneralUtility::mkdir($this->importFolder . 'foo/');
        $this->subject->cleanUp($this->importFolder);

        self::assertDirectoryExists(
            $this->importFolder . 'foo/'
        );
    }

    /**
     * @test
     */
    public function cleanUpDoesNotRemoveZipThatIsNotMarkedAsDeletable()
    {
        $this->copyTestFileIntoImportFolder('foo.zip');
        $this->subject->cleanUp($this->importFolder . 'foo.zip');

        self::assertFileExists($this->importFolder . 'foo.zip');
    }

    /**
     * @test
     */
    public function cleanUpRemovesCreatedFolderAlthoughTheExtractedArchiveContainsAFolder()
    {
        $this->copyTestFileIntoImportFolder('contains-folder.zip');
        $this->subject->importFromZip();

        self::assertDirectoryNotExists(
            $this->importFolder . 'contains-folder/'
        );
    }

    /**
     * @test
     */
    public function cleanUpDoesNotRemovesZipWithOneXmlInItIfDeletingZipsIsDisabled()
    {
        $this->globalConfiguration->setAsBoolean('deleteZipsAfterImport', false);
        $this->copyTestFileIntoImportFolder('same-name.zip');
        $this->subject->importFromZip();

        self::assertFileExists($this->importFolder . 'same-name.zip');
    }

    /**
     * @test
     */
    public function cleanUpRemovesZipWithOneXmlInItIfDeletingZipsIsEnabled()
    {
        // 'deleteZipsAfterImport' is set to true during setUp()
        $this->copyTestFileIntoImportFolder('same-name.zip');
        $this->subject->importFromZip();

        self::assertFileNotExists($this->importFolder . 'same-name.zip');
    }

    /**
     * @test
     */
    public function cleanUpDoesNotRemoveZipWithoutXml()
    {
        $this->copyTestFileIntoImportFolder('empty.zip');
        $this->subject->importFromZip();

        self::assertFileExists($this->importFolder . 'empty.zip');
    }

    /**
     * @test
     */
    public function cleanUpDoesNotRemoveZipWithTwoXmlFiles()
    {
        $this->copyTestFileIntoImportFolder('bar-bar.zip');
        $this->subject->importFromZip();

        self::assertFileExists($this->importFolder . 'bar-bar.zip');
    }

    /**
     * @test
     */
    public function cleanUpRemovesZipFileInASubFolderOfTheImportFolder()
    {
        // just to ensure the import folder exists
        $this->copyTestFileIntoImportFolder('empty.zip');
        // copyTestFileIntoImportFolder() cannot copy folders
        GeneralUtility::mkdir($this->importFolder . 'changed-copy-of-same-name/');
        copy(
            __DIR__ . '/Fixtures/changed-copy-of-same-name/same-name.zip',
            $this->importFolder . 'changed-copy-of-same-name/same-name.zip'
        );

        $this->subject->importFromZip();

        self::assertFileNotExists($this->importFolder . 'changed-copy-of-same-name/same-name.zip');
    }

    /**
     * @test
     */
    public function cleanUpDoesNotRemoveZipOfUnregisteredOwnerIfOwnerRestrictionIsEnabled()
    {
        // 'deleteZipsAfterImport' is set to TRUE during setUp()
        $this->globalConfiguration->setAsBoolean('onlyImportForRegisteredFrontEndUsers', true);
        $this->copyTestFileIntoImportFolder('same-name.zip');
        $this->subject->importFromZip();

        self::assertFileExists($this->importFolder . 'same-name.zip');
    }

    /**
     * @test
     */
    public function cleanUpRemovesZipOfRegisteredOwnerIfOwnerRestrictionIsEnabled()
    {
        $this->testingFramework->createFrontEndUser('', ['tx_realty_openimmo_anid' => 'foo']);
        // 'deleteZipsAfterImport' is set to TRUE during setUp()
        $this->globalConfiguration->setAsBoolean('onlyImportForRegisteredFrontEndUsers', true);
        $this->copyTestFileIntoImportFolder('same-name.zip');
        $this->subject->importFromZip();

        self::assertFileNotExists($this->importFolder . 'same-name.zip');
    }

    /**
     * @test
     */
    public function cleanUpDoesNotRemoveZipIfOwnerWhichHasReachedObjectLimitDuringImport()
    {
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
        $this->subject->importFromZip();

        self::assertFileExists($this->importFolder . 'two-objects.zip');
    }

    /**
     * @test
     */
    public function cleanUpDoesNotRemoveIfZipOwnerWhichHasNoObjectsLeftToEnter()
    {
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
        $this->subject->importFromZip();

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
        $this->copyTestFileIntoImportFolder('foo.zip');
        $this->subject->extractZip($this->importFolder . 'foo.zip');
        $this->subject->loadXmlFile($this->importFolder . 'foo.zip');

        self::assertInstanceOf(
            \DOMDocument::class,
            $this->subject->getImportedXml()
        );
    }

    /**
     * @test
     */
    public function loadXmlFileIfXmlIsValid()
    {
        $this->copyTestFileIntoImportFolder('foo.zip');
        $this->subject->extractZip($this->importFolder . 'foo.zip');
        $this->subject->loadXmlFile($this->importFolder . 'foo.zip');

        self::assertInstanceOf(
            \DOMDocument::class,
            $this->subject->getImportedXml()
        );
    }

    /**
     * @test
     */
    public function loadXmlFileIfXmlIsInvalid()
    {
        $this->copyTestFileIntoImportFolder('bar.zip');
        $this->subject->extractZip($this->importFolder . 'bar.zip');
        $this->subject->loadXmlFile($this->importFolder . 'bar.zip');

        self::assertInstanceOf(\DOMDocument::class, $this->subject->getImportedXml());
    }

    /**
     * @test
     */
    public function importFromZipKeepsCurrentBackendLanguage()
    {
        $currentBackEndLanguage = 'fr';
        $this->getLanguageService()->lang = $currentBackEndLanguage;

        $importLanguage = 'de';
        $this->globalConfiguration->setAsString('cliLanguage', $importLanguage);

        $this->copyTestFileIntoImportFolder('same-name.zip');
        $this->disableValidation();
        $this->subject->importFromZip();

        self::assertSame($currentBackEndLanguage, $this->getLanguageService()->lang);
    }

    /**
     * @test
     */
    public function importFromZipSkipsRecordsIfAFolderNamedLikeTheRecordAlreadyExists()
    {
        $extractionFolder = PATH_site . 'typo3temp/var/realty/foo/';
        $this->copyTestFileIntoImportFolder('foo.zip');
        GeneralUtility::mkdir_deep($extractionFolder);

        $result = $this->subject->importFromZip();

        self::assertContains($this->translator->translate('message_surplus_folder'), $result);
    }

    /**
     * @test
     */
    public function importFromZipImportsFromZipFileInASubFolderOfTheImportFolder()
    {
        // just to ensure the import folder exists
        $this->copyTestFileIntoImportFolder('empty.zip');
        // copyTestFileIntoImportFolder() cannot copy folders
        GeneralUtility::mkdir($this->importFolder . 'changed-copy-of-same-name/');
        copy(
            __DIR__ . '/Fixtures/changed-copy-of-same-name/same-name.zip',
            $this->importFolder . 'changed-copy-of-same-name/same-name.zip'
        );

        $this->subject->importFromZip();

        self::assertSame(
            1,
            $this->getDatabaseConnection()->selectCount(
                '*',
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
        $dummyDocument = new \DOMDocument();
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

        $records = $this->subject->convertDomDocumentToArray($dummyDocument);
        $this->subject->writeToDatabase($records[0]);

        self::assertSame(
            0,
            $this->getDatabaseConnection()->selectCount(
                '*',
                'tx_realty_objects',
                'object_number="' . $objectNumber . '"' .
                \Tx_Oelib_Db::enableFields('tx_realty_objects')
            )
        );
    }

    /**
     * @test
     */
    public function addWithAllRequiredFieldsSavesNewRecord()
    {
        $objectNumber = 'bar1234567';
        $dummyDocument = new \DOMDocument();
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

        $records = $this->subject->convertDomDocumentToArray($dummyDocument);
        $this->subject->writeToDatabase($records[0]);

        self::assertSame(
            1,
            $this->getDatabaseConnection()->selectCount(
                '*',
                'tx_realty_objects',
                'object_number="' . $objectNumber . '"'
            )
        );
    }

    /**
     * @test
     */
    public function updateWithAllRequiredFieldsSavesNewRecord()
    {
        $objectNumber = 'bar1234567';
        $dummyDocument = new \DOMDocument();
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

        $records = $this->subject->convertDomDocumentToArray($dummyDocument);
        $this->subject->writeToDatabase($records[0]);

        self::assertSame(
            1,
            $this->getDatabaseConnection()->selectCount(
                '*',
                'tx_realty_objects',
                'object_number="' . $objectNumber . '"'
            )
        );
    }

    /**
     * @test
     */
    public function deleteWithAllRequiredFieldsWithoutRecordInDatabaseNotSavesNewRecord()
    {
        $objectNumber = 'bar1234567';
        $dummyDocument = new \DOMDocument();
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

        $records = $this->subject->convertDomDocumentToArray($dummyDocument);
        $this->subject->writeToDatabase($records[0]);

        self::assertSame(
            0,
            $this->getDatabaseConnection()->selectCount(
                '*',
                'tx_realty_objects',
                'object_number="' . $objectNumber . '"'
            )
        );
    }

    /**
     * @test
     */
    public function addWithTwoIdenticalObjectsWithAllRequiredFieldsSavesExactlyOneRecord()
    {
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

        $dummyDocument = new \DOMDocument();
        $dummyDocument->loadXML(
            '<openimmo>'
            . '<anbieter>'
            . $objectData . $objectData
            . '<openimmo_anid>foo</openimmo_anid>'
            . '<firma>bar</firma>'
            . '</anbieter>'
            . '</openimmo>'
        );

        $records = $this->subject->convertDomDocumentToArray($dummyDocument);
        $this->subject->writeToDatabase($records[0]);

        self::assertSame(
            1,
            $this->getDatabaseConnection()->selectCount(
                '*',
                'tx_realty_objects',
                'object_number="' . $objectNumber . '"'
            )
        );
    }

    /**
     * @test
     */
    public function updateWithTwoIdenticalObjectsWithAllRequiredFieldsSavesExactlyOneRecord()
    {
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

        $dummyDocument = new \DOMDocument();
        $dummyDocument->loadXML(
            '<openimmo>'
            . '<anbieter>'
            . $objectData . $objectData
            . '<openimmo_anid>foo</openimmo_anid>'
            . '<firma>bar</firma>'
            . '</anbieter>'
            . '</openimmo>'
        );

        $records = $this->subject->convertDomDocumentToArray($dummyDocument);
        $this->subject->writeToDatabase($records[0]);

        self::assertSame(
            1,
            $this->getDatabaseConnection()->selectCount(
                '*',
                'tx_realty_objects',
                'object_number="' . $objectNumber . '"'
            )
        );
    }

    /**
     * @test
     */
    public function deleteWithTwoIdenticalObjectsWithAllRequiredFieldsWithoutRecordInDatabaseNotSavesNewRecord()
    {
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

        $dummyDocument = new \DOMDocument();
        $dummyDocument->loadXML(
            '<openimmo>'
            . '<anbieter>'
            . $objectData . $objectData
            . '<openimmo_anid>foo</openimmo_anid>'
            . '<firma>bar</firma>'
            . '</anbieter>'
            . '</openimmo>'
        );

        $records = $this->subject->convertDomDocumentToArray($dummyDocument);
        $this->subject->writeToDatabase($records[0]);

        self::assertSame(
            0,
            $this->getDatabaseConnection()->selectCount(
                '*',
                'tx_realty_objects',
                'object_number="' . $objectNumber . '"'
            )
        );
    }

    /**
     * @test
     */
    public function addWithAllRequiredFieldsUpdatesMatchingExistingRecord()
    {
        $objectNumber = 'bar1234567';
        $objectId = 'foo';
        $this->testingFramework->createRecord(
            'tx_realty_objects',
            [
                'object_number' => $objectNumber,
                'openimmo_obid' => $objectId,
            ]
        );
        $dummyDocument = new \DOMDocument();
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

        $records = $this->subject->convertDomDocumentToArray($dummyDocument);
        $this->subject->writeToDatabase($records[0]);

        self::assertSame(
            1,
            $this->getDatabaseConnection()->selectCount(
                '*',
                'tx_realty_objects',
                'object_number="' . $objectNumber . '"'
            )
        );
    }

    /**
     * @test
     */
    public function updateWithAllRequiredFieldsUpdatesMatchingExistingRecord()
    {
        $objectNumber = 'bar1234567';
        $objectId = 'foo';
        $this->testingFramework->createRecord(
            'tx_realty_objects',
            [
                'object_number' => $objectNumber,
                'openimmo_obid' => $objectId,
            ]
        );
        $dummyDocument = new \DOMDocument();
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

        $records = $this->subject->convertDomDocumentToArray($dummyDocument);
        $this->subject->writeToDatabase($records[0]);

        self::assertSame(
            1,
            $this->getDatabaseConnection()->selectCount(
                '*',
                'tx_realty_objects',
                'object_number="' . $objectNumber . '"'
            )
        );
    }

    /**
     * @test
     */
    public function deleteWithAllRequiredFieldsMarksMatchingExistingRecordAsDeleted()
    {
        $objectNumber = 'bar1234567';
        $objectId = 'foo';
        $this->testingFramework->createRecord(
            'tx_realty_objects',
            [
                'object_number' => $objectNumber,
                'openimmo_obid' => $objectId,
            ]
        );
        $dummyDocument = new \DOMDocument();
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

        $records = $this->subject->convertDomDocumentToArray($dummyDocument);
        $this->subject->writeToDatabase($records[0]);

        self::assertSame(
            0,
            $this->getDatabaseConnection()->selectCount(
                '*',
                'tx_realty_objects',
                'object_number="' . $objectNumber . '" AND deleted = 0'
            )
        );
        self::assertSame(
            1,
            $this->getDatabaseConnection()->selectCount(
                '*',
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

        $dummyDocument = new \DOMDocument();
        $dummyDocument->loadXML(
            '<openimmo>'
            . '<anbieter>'
            . $objectData . $objectData
            . '<openimmo_anid>foo</openimmo_anid>'
            . '<firma>bar</firma>'
            . '</anbieter>'
            . '</openimmo>'
        );

        $records = $this->subject->convertDomDocumentToArray($dummyDocument);
        $this->subject->writeToDatabase($records[0]);

        self::assertSame(
            0,
            $this->getDatabaseConnection()->selectCount(
                '*',
                'tx_realty_objects',
                'object_number="' . $objectNumber . '" AND deleted = 0'
            )
        );
        self::assertSame(
            1,
            $this->getDatabaseConnection()->selectCount(
                '*',
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
        $dummyDocument = new \DOMDocument();
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

        $records = $this->subject->convertDomDocumentToArray($dummyDocument);
        $this->subject->writeToDatabase($records[0]);

        self::assertSame(
            1,
            $this->getDatabaseConnection()->selectCount(
                '*',
                'tx_realty_objects',
                'object_number="' . $objectNumber . '" AND deleted = 0'
            )
        );
        self::assertSame(
            1,
            $this->getDatabaseConnection()->selectCount(
                '*',
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
        $dummyDocument = new \DOMDocument();
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

        $records = $this->subject->convertDomDocumentToArray($dummyDocument);
        $this->subject->writeToDatabase($records[0]);

        self::assertSame(
            1,
            $this->getDatabaseConnection()->selectCount(
                '*',
                'tx_realty_objects',
                'object_number="' . $objectNumber . '" AND deleted = 0'
            )
        );
        self::assertSame(
            1,
            $this->getDatabaseConnection()->selectCount(
                '*',
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
        $dummyDocument = new \DOMDocument();
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

        $records = $this->subject->convertDomDocumentToArray($dummyDocument);
        $this->subject->writeToDatabase($records[0]);

        self::assertSame(
            0,
            $this->getDatabaseConnection()->selectCount(
                '*',
                'tx_realty_objects',
                'object_number="' . $objectNumber . '" AND deleted = 0'
            )
        );
        self::assertSame(
            1,
            $this->getDatabaseConnection()->selectCount(
                '*',
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
        $dummyDocument = new \DOMDocument();
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

        $records = $this->subject->convertDomDocumentToArray($dummyDocument);
        $this->subject->writeToDatabase($records[0]);

        self::assertSame(
            1,
            $this->getDatabaseConnection()->selectCount(
                '*',
                'tx_realty_objects',
                'object_number="' . $objectNumber . '" AND hidden = 1'
            )
        );
        self::assertSame(
            0,
            $this->getDatabaseConnection()->selectCount(
                '*',
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
        $dummyDocument = new \DOMDocument();
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

        $records = $this->subject->convertDomDocumentToArray($dummyDocument);
        $this->subject->writeToDatabase($records[0]);

        self::assertSame(
            1,
            $this->getDatabaseConnection()->selectCount(
                '*',
                'tx_realty_objects',
                'object_number="' . $objectNumber . '" AND hidden = 1'
            )
        );
        self::assertSame(
            0,
            $this->getDatabaseConnection()->selectCount(
                '*',
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
        $dummyDocument = new \DOMDocument();
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

        $records = $this->subject->convertDomDocumentToArray($dummyDocument);
        $this->subject->writeToDatabase($records[0]);

        self::assertSame(
            0,
            $this->getDatabaseConnection()->selectCount(
                '*',
                'tx_realty_objects',
                'object_number="' . $objectNumber . '" AND deleted = 0 AND hidden = 1'
            )
        );
        self::assertSame(
            0,
            $this->getDatabaseConnection()->selectCount(
                '*',
                'tx_realty_objects',
                'object_number="' . $objectNumber . '" AND deleted = 0 AND hidden = 0'
            )
        );
        self::assertSame(
            0,
            $this->getDatabaseConnection()->selectCount(
                '*',
                'tx_realty_objects',
                'object_number="' . $objectNumber . '" AND deleted = 1 AND hidden = 0'
            )
        );
        self::assertSame(
            1,
            $this->getDatabaseConnection()->selectCount(
                '*',
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
        $objectNumber = 'bar1234567';
        $dummyDocument = new \DOMDocument();
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

        $records = $this->subject->convertDomDocumentToArray($dummyDocument);
        $this->subject->writeToDatabase($records[0]);

        self::assertSame(
            1,
            $this->getDatabaseConnection()->selectCount(
                '*',
                'tx_realty_objects',
                'object_number="' . $objectNumber . '"'
            )
        );
    }

    /**
     * @test
     */
    public function changeAndAddWithTwoIdenticalObjectsWithAllRequiredFieldsSavesExactlyOneRecord()
    {
        $objectNumber = 'bar1234567';
        $dummyDocument = new \DOMDocument();
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

        $records = $this->subject->convertDomDocumentToArray($dummyDocument);
        $this->subject->writeToDatabase($records[0]);

        self::assertSame(
            1,
            $this->getDatabaseConnection()->selectCount(
                '*',
                'tx_realty_objects',
                'object_number="' . $objectNumber . '"'
            )
        );
    }

    /**
     * @test
     */
    public function deleteAndChangeWithTwoIdenticalObjectsWithAllRequiredFieldsSavesNoRecord()
    {
        $objectNumber = 'bar1234567';
        $dummyDocument = new \DOMDocument();
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

        $records = $this->subject->convertDomDocumentToArray($dummyDocument);
        $this->subject->writeToDatabase($records[0]);

        self::assertSame(
            0,
            $this->getDatabaseConnection()->selectCount(
                '*',
                'tx_realty_objects',
                'object_number="' . $objectNumber . '"'
            )
        );
    }

    /**
     * @test
     */
    public function changeAndDeleteWithTwoIdenticalObjectsWithAllRequiredFieldsSavesOneRecord()
    {
        $objectNumber = 'bar1234567';
        $dummyDocument = new \DOMDocument();
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

        $records = $this->subject->convertDomDocumentToArray($dummyDocument);
        $this->subject->writeToDatabase($records[0]);

        self::assertSame(
            1,
            $this->getDatabaseConnection()->selectCount(
                '*',
                'tx_realty_objects',
                'object_number="' . $objectNumber . '"'
            )
        );
    }

    /**
     * @test
     */
    public function changeAndAddDeleteWithTwoIdenticalObjectsWithAllRequiredFieldsAndContactDataNotSavesAnyRecord()
    {
        $objectNumber = 'bar1234567';
        $dummyDocument = new \DOMDocument();
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

        $records = $this->subject->convertDomDocumentToArray($dummyDocument);
        $this->subject->writeToDatabase($records[0]);

        self::assertSame(
            0,
            $this->getDatabaseConnection()->selectCount(
                '*',
                'tx_realty_objects',
                'object_number="' . $objectNumber . '"'
            )
        );
    }

    /**
     * @test
     */
    public function ensureContactEmailNotChangesAddressIfValidAddressIsSet()
    {
        $this->subject->loadRealtyObject(
            ['contact_email' => 'foo-valid@example.com']
        );
        $this->subject->ensureContactEmail();

        self::assertSame(
            'foo-valid@example.com',
            $this->subject->getContactEmailFromRealtyObject()
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
        $this->subject->loadRealtyObject(['contact_email' => '']);
        $this->subject->ensureContactEmail();

        self::assertSame(
            'default_address@example.com',
            $this->subject->getContactEmailFromRealtyObject()
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
        $this->subject->loadRealtyObject(['contact_email' => 'foo']);
        $this->subject->ensureContactEmail();

        self::assertSame(
            'default_address@example.com',
            $this->subject->getContactEmailFromRealtyObject()
        );
    }

    /**
     * @test
     */
    public function importStoresZipsWithLeadingZeroesIntoDb()
    {
        $objectNumber = 'bar1234567';
        $dummyDocument = new \DOMDocument();
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

        $records = $this->subject->convertDomDocumentToArray($dummyDocument);
        $this->subject->writeToDatabase($records[0]);

        self::assertSame(
            1,
            $this->getDatabaseConnection()->selectCount(
                '*',
                'tx_realty_objects',
                'object_number="' . $objectNumber . '" AND zip="01234"' .
                \Tx_Oelib_Db::enableFields('tx_realty_objects')
            )
        );
    }

    /**
     * @test
     */
    public function importStoresNumberOfRoomsWithDecimalsIntoDb()
    {
        $objectNumber = 'bar1234567';
        $dummyDocument = new \DOMDocument();
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

        $records = $this->subject->convertDomDocumentToArray($dummyDocument);
        $this->subject->writeToDatabase($records[0]);

        self::assertSame(
            1,
            $this->getDatabaseConnection()->selectCount(
                '*',
                'tx_realty_objects',
                'object_number="' . $objectNumber . '" AND ' .
                'number_of_rooms = 1.25' .
                \Tx_Oelib_Db::enableFields('tx_realty_objects')
            )
        );
    }

    /**
     * @test
     */
    public function importCanUseExistingCity()
    {
        $this->importDataSet(__DIR__ . '/../Fixtures/RealtyObjects.xml');

        $objectNumber = 'bar1234567';
        $dummyDocument = new \DOMDocument();
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
            . '<plz>53111</plz>'
            . '<ort>Bonn</ort>'
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

        $records = $this->subject->convertDomDocumentToArray($dummyDocument);
        $this->subject->writeToDatabase($records[0]);

        $cityUid = 100;
        $where = 'object_number = "' . $objectNumber . '" AND city = ' . $cityUid;
        self::assertSame(1, $this->getDatabaseConnection()->selectCount('*', 'tx_realty_objects', $where));
    }

    /**
     * @test
     */
    public function importCanCreateAndUseNewCity()
    {
        $objectNumber = 'bar1234567';
        $dummyDocument = new \DOMDocument();
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
            . '<plz>53111</plz>'
            . '<ort>Bonn</ort>'
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

        $records = $this->subject->convertDomDocumentToArray($dummyDocument);
        $this->subject->writeToDatabase($records[0]);

        $cityRecord = \Tx_Oelib_Db::selectSingle('*', 'tx_realty_cities', 'title = "Bonn"');
        $cityUid = (int)$cityRecord['uid'];
        $where = 'object_number = "' . $objectNumber . '" AND city = ' . $cityUid;
        self::assertSame(1, $this->getDatabaseConnection()->selectCount('*', 'tx_realty_objects', $where));
    }

    /**
     * @test
     */
    public function importCanUseExistingDistrictWithMatchingCity()
    {
        $this->importDataSet(__DIR__ . '/../Fixtures/RealtyObjects.xml');

        $objectNumber = 'bar1234567';
        $dummyDocument = new \DOMDocument();
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
            . '<plz>53111</plz>'
            . '<ort>Bonn</ort>'
            . '<regionaler_zusatz>Innenstadt</regionaler_zusatz>'
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

        $records = $this->subject->convertDomDocumentToArray($dummyDocument);
        $this->subject->writeToDatabase($records[0]);

        $districtUid = 200;
        $where = 'object_number = "' . $objectNumber . '" AND district = ' . $districtUid;
        self::assertSame(1, $this->getDatabaseConnection()->selectCount('*', 'tx_realty_objects', $where));
    }

    /**
     * @test
     */
    public function importMatchesDistrictByNameAndCity()
    {
        $this->importDataSet(__DIR__ . '/../Fixtures/RealtyObjects.xml');

        $objectNumber1 = 'foo1234567';
        $objectNumber2 = 'bar1234567';
        $dummyDocument = new \DOMDocument();
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
            . '<plz>53111</plz>'
            . '<ort>Bonn</ort>'
            . '<regionaler_zusatz>Innenstadt</regionaler_zusatz>'
            . '</geo>'
            . '<kontaktperson>'
            . '<name>bar</name>'
            . '<email_zentrale>bar</email_zentrale>'
            . '</kontaktperson>'
            . '<verwaltung_techn>'
            . '<openimmo_obid>foo</openimmo_obid>'
            . '<aktion/>'
            . '<objektnr_extern>' . $objectNumber1 . '</objektnr_extern>'
            . '</verwaltung_techn>'
            . '</immobilie>'
            . '<immobilie>'
            . '<objektkategorie>'
            . '<nutzungsart WOHNEN="1"/>'
            . '<vermarktungsart KAUF="1"/>'
            . '<objektart><zimmer/></objektart>'
            . '</objektkategorie>'
            . '<geo>'
            . '<plz>53007</plz>'
            . '<ort>Köln</ort>'
            . '<regionaler_zusatz>Innenstadt</regionaler_zusatz>'
            . '</geo>'
            . '<kontaktperson>'
            . '<name>bar</name>'
            . '<email_zentrale>bar</email_zentrale>'
            . '</kontaktperson>'
            . '<verwaltung_techn>'
            . '<openimmo_obid>foo</openimmo_obid>'
            . '<aktion/>'
            . '<objektnr_extern>' . $objectNumber2 . '</objektnr_extern>'
            . '</verwaltung_techn>'
            . '</immobilie>'
            . '<openimmo_anid>foo</openimmo_anid>'
            . '<firma>bar</firma>'
            . '</anbieter>'
            . '</openimmo>'
        );

        $records = $this->subject->convertDomDocumentToArray($dummyDocument);
        $this->subject->writeToDatabase($records[0]);
        $this->subject->writeToDatabase($records[1]);

        $where1 = 'object_number = "' . $objectNumber1 . '" AND city = 100 AND district = 200';
        self::assertSame(1, $this->getDatabaseConnection()->selectCount('*', 'tx_realty_objects', $where1));
        $where2 = 'object_number = "' . $objectNumber2 . '" AND city = 101 AND district = 201';
        self::assertSame(1, $this->getDatabaseConnection()->selectCount('*', 'tx_realty_objects', $where2));
    }

    /**
     * @test
     */
    public function importCanCreateAndUseNewDistrict()
    {
        $objectNumber = 'bar1234567';
        $dummyDocument = new \DOMDocument();
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
            . '<plz>53173</plz>'
            . '<ort>Bonn</ort>'
            . '<regionaler_zusatz>Bad Godesberg</regionaler_zusatz>'
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

        $records = $this->subject->convertDomDocumentToArray($dummyDocument);
        $this->subject->writeToDatabase($records[0]);

        $districtRecord = \Tx_Oelib_Db::selectSingle('*', 'tx_realty_districts', 'title = "Bad Godesberg"');
        $districtUid = (int)$districtRecord['uid'];
        $where = 'object_number = "' . $objectNumber . '" AND district = ' . $districtUid;
        self::assertSame(1, $this->getDatabaseConnection()->selectCount('*', 'tx_realty_objects', $where));
    }

    /**
     * @test
     */
    public function importForDistrictWithoutCityCreatesNoDistrict()
    {
        $objectNumber = 'bar1234567';
        $dummyDocument = new \DOMDocument();
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
            . '<plz>53173</plz>'
            . '<regionaler_zusatz>Bad Godesberg</regionaler_zusatz>'
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

        $records = $this->subject->convertDomDocumentToArray($dummyDocument);
        $this->subject->writeToDatabase($records[0]);

        self::assertSame(0, \Tx_Oelib_Db::count('tx_realty_districts'));
    }

    /**
     * @test
     */
    public function importForDistrictWithEmptyCityCreatesNoDistrict()
    {
        $objectNumber = 'bar1234567';
        $dummyDocument = new \DOMDocument();
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
            . '<plz>53173</plz>'
            . '<ort></ort>'
            . '<regionaler_zusatz>Bad Godesberg</regionaler_zusatz>'
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

        $records = $this->subject->convertDomDocumentToArray($dummyDocument);
        $this->subject->writeToDatabase($records[0]);

        self::assertSame(0, \Tx_Oelib_Db::count('tx_realty_districts'));
    }

    /**
     * @test
     */
    public function importSetsExistingCityForNewlyCreatedDistrict()
    {
        $this->importDataSet(__DIR__ . '/../Fixtures/RealtyObjects.xml');

        $objectNumber = 'bar1234567';
        $dummyDocument = new \DOMDocument();
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
            . '<plz>53173</plz>'
            . '<ort>Bonn</ort>'
            . '<regionaler_zusatz>Bad Godesberg</regionaler_zusatz>'
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

        $records = $this->subject->convertDomDocumentToArray($dummyDocument);
        $this->subject->writeToDatabase($records[0]);

        $where = 'title = "Bad Godesberg" AND city = 100';
        self::assertSame(1, $this->getDatabaseConnection()->selectCount('*', 'tx_realty_districts', $where));
    }

    /**
     * @test
     */
    public function importSetsNewlyCreatedCityForNewlyCreatedDistrict()
    {
        $objectNumber = 'bar1234567';
        $dummyDocument = new \DOMDocument();
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
            . '<plz>53173</plz>'
            . '<ort>Bonn</ort>'
            . '<regionaler_zusatz>Bad Godesberg</regionaler_zusatz>'
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

        $records = $this->subject->convertDomDocumentToArray($dummyDocument);
        $this->subject->writeToDatabase($records[0]);

        $cityRecord = \Tx_Oelib_Db::selectSingle('*', 'tx_realty_cities', 'title = "Bonn"');
        $cityUid = (int)$cityRecord['uid'];

        $where = 'title = "Bad Godesberg" AND city = ' . $cityUid;
        self::assertSame(1, $this->getDatabaseConnection()->selectCount('*', 'tx_realty_districts', $where));
    }

    /**
     * @test
     */
    public function importWithCityAndWithoutDistrictCreatesNoDistrict()
    {
        $objectNumber = 'bar1234567';
        $dummyDocument = new \DOMDocument();
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
            . '<plz>53111</plz>'
            . '<ort>Bonn</ort>'
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

        $records = $this->subject->convertDomDocumentToArray($dummyDocument);
        $this->subject->writeToDatabase($records[0]);

        self::assertSame(0, $this->getDatabaseConnection()->selectCount('*', 'tx_realty_districts'));
    }

    /**
     * @test
     */
    public function importWithoutCityAndWithoutDistrictCreatesNoDistrict()
    {
        $objectNumber = 'bar1234567';
        $dummyDocument = new \DOMDocument();
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
            . '<plz>53111</plz>'
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

        $records = $this->subject->convertDomDocumentToArray($dummyDocument);
        $this->subject->writeToDatabase($records[0]);

        self::assertSame(0, $this->getDatabaseConnection()->selectCount('*', 'tx_realty_districts'));
    }

    /**
     * @test
     */
    public function importWithCityAndWithEmptyDistrictCreatesNoDistrict()
    {
        $objectNumber = 'bar1234567';
        $dummyDocument = new \DOMDocument();
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
            . '<plz>53111</plz>'
            . '<ort>Bonn</ort>'
            . '<regionaler_zusatz></regionaler_zusatz>'
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

        $records = $this->subject->convertDomDocumentToArray($dummyDocument);
        $this->subject->writeToDatabase($records[0]);

        self::assertSame(0, $this->getDatabaseConnection()->selectCount('*', 'tx_realty_districts'));
    }

    /**
     * @test
     */
    public function updatingRealtyObjectNotCreatesAdditionalCities()
    {
        $objectNumber = 'bar1234567';
        $dummyDocument = new \DOMDocument();
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
            . '<plz>53173</plz>'
            . '<ort>Bonn</ort>'
            . '<regionaler_zusatz>Bad Godesberg</regionaler_zusatz>'
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

        $records = $this->subject->convertDomDocumentToArray($dummyDocument);
        $this->subject->writeToDatabase($records[0]);
        $numberOfCityRecordsBefore = \Tx_Oelib_Db::count('tx_realty_cities');

        $this->subject->writeToDatabase($records[0]);

        $numberOfCityRecordsAfter = \Tx_Oelib_Db::count('tx_realty_cities');
        self::assertSame($numberOfCityRecordsBefore, $numberOfCityRecordsAfter);
    }

    /**
     * @test
     */
    public function updatingRealtyObjectNotCreatesAdditionalDistricts()
    {
        $objectNumber = 'bar1234567';
        $dummyDocument = new \DOMDocument();
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
            . '<plz>53173</plz>'
            . '<ort>Bonn</ort>'
            . '<regionaler_zusatz>Bad Godesberg</regionaler_zusatz>'
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

        $records = $this->subject->convertDomDocumentToArray($dummyDocument);
        $this->subject->writeToDatabase($records[0]);
        $numberOfDistrictRecordsBefore = \Tx_Oelib_Db::count('tx_realty_districts');

        $this->subject->writeToDatabase($records[0]);

        $numberOfDistrictRecordsAfter = \Tx_Oelib_Db::count('tx_realty_districts');
        self::assertSame($numberOfDistrictRecordsBefore, $numberOfDistrictRecordsAfter);
    }

    /**
     * @test
     */
    public function importUtf8FileWithCorrectUmlauts()
    {
        $this->copyTestFileIntoImportFolder('charset-UTF8.zip');
        $this->subject->importFromZip();

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
        $this->copyTestFileIntoImportFolder('charset-UTF8-default.zip');
        $this->subject->importFromZip();

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
        $this->copyTestFileIntoImportFolder('charset-ISO8859-1.zip');
        $this->subject->importFromZip();

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
        $feUserUid = $this->testingFramework->createFrontEndUser('', ['tx_realty_openimmo_anid' => 'foo']);
        $this->globalConfiguration->setAsBoolean('onlyImportForRegisteredFrontEndUsers', true);
        $this->copyTestFileIntoImportFolder('same-name.zip');
        $this->subject->importFromZip();

        self::assertSame(
            1,
            $this->getDatabaseConnection()->selectCount(
                '*',
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
        $this->globalConfiguration->setAsBoolean('onlyImportForRegisteredFrontEndUsers', true);
        $this->copyTestFileIntoImportFolder('same-name.zip');
        $this->subject->importFromZip();

        self::assertSame(
            0,
            $this->getDatabaseConnection()->selectCount(
                '*',
                'tx_realty_objects',
                'openimmo_anid="foo"'
            )
        );
    }

    /**
     * @test
     */
    public function recordWithAnidThatMatchesAnExistingFeUserInAnAllowedGroupIsImportedForEnabledOwnerAndGroupRestriction(
    )
    {
        $feUserGroupUid = $this->testingFramework->createFrontEndUserGroup();
        $feUserUid = $this->testingFramework->createFrontEndUser($feUserGroupUid, ['tx_realty_openimmo_anid' => 'foo']);
        $this->globalConfiguration->setAsBoolean('onlyImportForRegisteredFrontEndUsers', true);
        $this->globalConfiguration->setAsString('allowedFrontEndUserGroups', $feUserGroupUid);
        $this->copyTestFileIntoImportFolder('same-name.zip');
        $this->subject->importFromZip();

        self::assertSame(
            1,
            $this->getDatabaseConnection()->selectCount(
                '*',
                'tx_realty_objects',
                'openimmo_anid="foo" AND owner=' . $feUserUid
            )
        );
    }

    /**
     * @test
     */
    public function recordWithAnidThatMatchesAnExistingFeUserInAForbiddenGroupIsNotImportedForEnabledOwnerAndGroupRestriction(
    )
    {
        $feUserGroupUid = $this->testingFramework->createFrontEndUserGroup();
        $feUserUid = $this->testingFramework->createFrontEndUser($feUserGroupUid, ['tx_realty_openimmo_anid' => 'foo']);
        $this->globalConfiguration->setAsBoolean('onlyImportForRegisteredFrontEndUsers', true);
        $this->globalConfiguration->setAsString('allowedFrontEndUserGroups', $feUserGroupUid + 1);
        $this->copyTestFileIntoImportFolder('same-name.zip');
        $this->subject->importFromZip();

        self::assertSame(
            0,
            $this->getDatabaseConnection()->selectCount(
                '*',
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

        $singleObject = new \DOMDocument();
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

        $records = $this->subject->convertDomDocumentToArray($singleObject);
        $this->subject->writeToDatabase($records[0]);

        self::assertSame(
            1,
            $this->getDatabaseConnection()->selectCount(
                '*',
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

        $multipleRecords = new \DOMDocument();
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

        $records = $this->subject->convertDomDocumentToArray($multipleRecords);
        $this->subject->writeToDatabase($records[0]);
        $this->subject->writeToDatabase($records[1]);

        self::assertSame(
            2,
            $this->getDatabaseConnection()->selectCount(
                '*',
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
        $feUserGroupUid = $this->testingFramework->createFrontEndUserGroup();
        $feUserUid = $this->testingFramework->createFrontEndUser($feUserGroupUid, ['tx_realty_openimmo_anid' => 'foo']);
        $this->globalConfiguration->setAsBoolean('useFrontEndUserDataAsContactDataForImportedRecords', true);
        $this->globalConfiguration->setAsString('allowedFrontEndUserGroups', $feUserGroupUid);

        $singleObject = new \DOMDocument();
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

        $records = $this->subject->convertDomDocumentToArray($singleObject);
        $this->subject->writeToDatabase($records[0]);

        self::assertSame(
            1,
            $this->getDatabaseConnection()->selectCount(
                '*',
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

        $multipleRecords = new \DOMDocument();
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

        $records = $this->subject->convertDomDocumentToArray($multipleRecords);
        $this->subject->writeToDatabase($records[0]);
        $this->subject->writeToDatabase($records[1]);

        self::assertSame(
            1,
            $this->getDatabaseConnection()->selectCount(
                '*',
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
            $this->subject->importFromZip()
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
        $this->copyTestFileIntoImportFolder('foo.zip');
        $this->importDataSet(__DIR__ . '/../Fixtures/ContentElements.xml');

        /** @var AbstractCacheFrontEnd|\PHPUnit_Framework_MockObject_MockObject $cacheFrontEnd */
        $cacheFrontEnd = $this->getMock(
            AbstractCacheFrontEnd::class,
            ['getIdentifier', 'set', 'get', 'getByTag', 'getBackend'],
            [],
            '',
            false
        );
        $cacheFrontEnd->expects(self::once())->method('getIdentifier')->willReturn('cache_pages');
        /** @var TaggableBackendInterface|\PHPUnit_Framework_MockObject_MockObject $cacheBackEnd */
        $cacheBackEnd = $this->createMock(TaggableBackendInterface::class);
        $cacheFrontEnd->method('getBackend')->willReturn($cacheBackEnd);
        $cacheBackEnd->expects(self::atLeastOnce())->method('flushByTag');

        $cacheManager = new CacheManager();
        $cacheManager->registerCache($cacheFrontEnd);
        \tx_realty_cacheManager::injectCacheManager($cacheManager);

        $this->subject->importFromZip();
    }

    /*
     * Tests concerning the log messages.
     */

    /**
     * @test
     */
    public function importFromZipReturnsLogMessageNoSchemaFileIfTheSchemaFileWasNotSet()
    {
        $this->copyTestFileIntoImportFolder('foo.zip');
        $this->globalConfiguration->setAsString('openImmoSchema', '');

        self::assertContains(
            $this->translator->translate('message_no_schema_file'),
            $this->subject->importFromZip()
        );
    }

    /**
     * @test
     */
    public function importFromZipReturnsLogMessageIncorrectSchemaFileIfTheSchemaFilePathWasIncorrect()
    {
        $this->copyTestFileIntoImportFolder('foo.zip');
        $this->globalConfiguration->setAsString('openImmoSchema', '/any/not/existing/path');

        self::assertContains(
            $this->translator->translate('message_invalid_schema_file_path'),
            $this->subject->importFromZip()
        );
    }

    /**
     * @test
     */
    public function importFromZipReturnsLogMessageMissingRequiredFields()
    {
        $this->copyTestFileIntoImportFolder('email.zip');
        $this->disableValidation();

        self::assertContains(
            $this->translator->translate('message_fields_required'),
            $this->subject->importFromZip()
        );
    }

    /**
     * @test
     */
    public function importFromZipReturnsLogMessageThatNoRecordWasLoadedForZipWithNonOpenImmoXml()
    {
        $this->copyTestFileIntoImportFolder('bar.zip');
        $this->disableValidation();

        self::assertContains(
            $this->translator->translate('message_object_not_loaded'),
            $this->subject->importFromZip()
        );
    }

    /**
     * @test
     */
    public function importFromZipReturnsMessageThatTheLogWasSentToTheDefaultAddressIfNoRecordWasLoaded()
    {
        $this->copyTestFileIntoImportFolder('foo.zip');
        $this->disableValidation();

        self::assertContains(
            'default-recipient@example.com',
            $this->subject->importFromZip()
        );
    }

    /**
     * @test
     */
    public function importFromZipForNonExistingImportFolderReturnsFolderNotExistingErrorMessage()
    {
        $path = '/any/not/existing/import-path/';
        $this->globalConfiguration->setAsString('importFolder', $path);

        self::assertContains(
            sprintf(
                $this->translator->translate('message_import_directory_not_existing'),
                $path,
                get_current_user()
            ),
            $this->subject->importFromZip()
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
        $this->copyTestFileIntoImportFolder('same-name.zip');
        $this->disableValidation();

        $this->subject->importFromZip();

        self::assertSame(
            1,
            $this->getDatabaseConnection()->selectCount(
                '*',
                'tx_realty_objects',
                'object_number="bar1234567" '
                . 'AND pid=' . $this->systemFolderPid . \Tx_Oelib_Db::enableFields('tx_realty_objects')
            )
        );
    }

    /*
     * Tests concerning the attachments
     */

    /**
     * @test
     */
    public function importFromZipCopiesAttachmentFiles()
    {
        $this->copyTestFileIntoImportFolder('foo.zip');
        $this->subject->importFromZip();

        $writtenData = \Tx_Oelib_Db::selectSingle('uid', 'tx_realty_objects', 'openimmo_obid = "111"');
        $uid = (int)$writtenData['uid'];

        self::assertFileExists(
            GeneralUtility::getFileAbsFileName('fileadmin/realty_attachments/' . $uid . '/foo.jpg')
        );
    }

    /**
     * @test
     */
    public function importFromZipCreatesFileRecord()
    {
        $this->copyTestFileIntoImportFolder('foo.zip');
        $this->subject->importFromZip();

        $numberOfFileRecordMatches = \Tx_Oelib_Db::count('sys_file', 'name = "foo.jpg"');

        self::assertSame(1, $numberOfFileRecordMatches);
    }

    /**
     * @test
     *
     * @doesNotPerformAssertions
     */
    public function importFromZipWithReferencedImageMissingDoesNotThrowException()
    {
        $this->copyTestFileIntoImportFolder('missing-referenced-images.zip');
        $this->subject->importFromZip();
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
        $this->copyTestFileIntoImportFolder('email.zip');
        $this->subject->importFromZip();

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
        $this->copyTestFileIntoImportFolder('email.zip');
        $this->subject->importFromZip();

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
        $this->copyTestFileIntoImportFolder('valid-email.zip');
        $this->subject->importFromZip();

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
        $this->copyTestFileIntoImportFolder('email.zip');
        $this->subject->importFromZip();

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
        $this->copyTestFileIntoImportFolder('foo.zip');
        $this->subject->importFromZip();

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
        $this->copyTestFileIntoImportFolder('with-openimmo-anid.zip');
        $this->subject->importFromZip();

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
        $this->copyTestFileIntoImportFolder('with-email-and-openimmo-anid.zip');
        $this->subject->importFromZip();

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
        $this->copyTestFileIntoImportFolder('with-email-and-openimmo-anid.zip');
        $this->subject->importFromZip();

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
        $this->copyTestFileIntoImportFolder('valid-email.zip');
        $this->subject->importFromZip();

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
        $this->copyTestFileIntoImportFolder('with-openimmo-anid.zip');
        $this->subject->importFromZip();

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
        $this->copyTestFileIntoImportFolder('foo.zip');
        $this->subject->importFromZip();

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
        $this->copyTestFileIntoImportFolder('email.zip');
        $this->subject->importFromZip();

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
        $this->copyTestFileIntoImportFolder('email.zip');
        $this->subject->importFromZip();

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
        $this->copyTestFileIntoImportFolder('email.zip');
        $this->subject->importFromZip();

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
        $this->globalConfiguration->setAsBoolean(
            'onlyImportForRegisteredFrontEndUsers',
            true
        );
        $this->copyTestFileIntoImportFolder('same-name.zip');
        $this->subject->importFromZip();

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
        $this->subject->importFromZip();

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
     * Tests for deleting objects
     */

    /**
     * @test
     */
    public function defaultSyncWithDeletingEnabledKeepsUnmentionedObjectsWithSameAnid()
    {
        $this->globalConfiguration->setAsBoolean('importCanDeleteRecordsForFullSync', true);

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

        $this->subject->importFromZip();

        self::assertSame(
            1,
            $this->getDatabaseConnection()->selectCount('*', 'tx_realty_objects', 'uid = ' . $uid . ' AND deleted = 0')
        );
    }

    /**
     * @test
     */
    public function partialSyncWithDeletingEnabledKeepsUnmentionedObjectsWithSameAnid()
    {
        $this->globalConfiguration->setAsBoolean('importCanDeleteRecordsForFullSync', true);

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

        $this->subject->importFromZip();

        self::assertSame(
            1,
            $this->getDatabaseConnection()->selectCount('*', 'tx_realty_objects', 'uid = ' . $uid . ' AND deleted = 0')
        );
    }

    /**
     * @test
     */
    public function fullSyncWithDeletingEnabledKeepsMentionedObjectsWithSameAnidAndObjectNumber()
    {
        $this->globalConfiguration->setAsBoolean('importCanDeleteRecordsForFullSync', true);

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

        $this->subject->importFromZip();

        self::assertSame(
            1,
            $this->getDatabaseConnection()->selectCount('*', 'tx_realty_objects', 'uid = ' . $uid . ' AND deleted = 0')
        );
    }

    /**
     * @test
     */
    public function fullSyncWithDeletingEnabledKeepsUnmentionedObjectsWithOtherAnid()
    {
        $this->globalConfiguration->setAsBoolean('importCanDeleteRecordsForFullSync', true);

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

        $this->subject->importFromZip();

        self::assertSame(
            1,
            $this->getDatabaseConnection()->selectCount('*', 'tx_realty_objects', 'uid = ' . $uid . ' AND deleted = 0')
        );
    }

    /**
     * @test
     */
    public function fullSyncWithDeletingEnabledDeletesUnmentionedObjectsWithSameNonEmptyAnid()
    {
        $this->globalConfiguration->setAsBoolean('importCanDeleteRecordsForFullSync', true);

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

        $this->subject->importFromZip();

        self::assertSame(
            1,
            $this->getDatabaseConnection()->selectCount('*', 'tx_realty_objects', 'uid = ' . $uid . ' AND deleted = 1')
        );
    }

    /**
     * @test
     */
    public function fullSyncWithDeletingEnabledDeletesUnmentionedObjectsWithSameEmptyAnid()
    {
        $this->globalConfiguration->setAsBoolean('importCanDeleteRecordsForFullSync', true);

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

        $this->subject->importFromZip();

        self::assertSame(
            1,
            $this->getDatabaseConnection()->selectCount('*', 'tx_realty_objects', 'uid = ' . $uid . ' AND deleted = 1')
        );
    }

    /**
     * @test
     */
    public function fullSyncWithDeletingEnabledLogsDeletion()
    {
        $this->globalConfiguration->setAsBoolean('importCanDeleteRecordsForFullSync', true);

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

        $result = $this->subject->importFromZip();

        $message = $this->translator->translate('message_deleted_objects_from_full_sync') . ' ' . $uid;
        self::assertContains($message, $result);
    }

    /**
     * @test
     */
    public function fullSyncWithDeletingEnabledWithoutAnidKeepsUnmentionedObjectsWithNonImportPid()
    {
        $this->globalConfiguration->setAsBoolean('importCanDeleteRecordsForFullSync', true);

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

        $this->subject->importFromZip();

        self::assertSame(
            1,
            $this->getDatabaseConnection()->selectCount('*', 'tx_realty_objects', 'uid = ' . $uid . ' AND deleted = 0')
        );
    }

    /**
     * @test
     */
    public function fullSyncWithDeletingDisabledKeepsUnmentionedObjectsWithSameAnid()
    {
        $this->globalConfiguration->setAsBoolean('importCanDeleteRecordsForFullSync', false);

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

        $this->subject->importFromZip();

        self::assertSame(
            1,
            $this->getDatabaseConnection()->selectCount('*', 'tx_realty_objects', 'uid = ' . $uid . ' AND deleted = 0')
        );
    }

    /**
     * @test
     */
    public function writeToDatabaseForPartialSyncForDeletingInexistentObjectWithoutAttachmentsNotCreatesRecord()
    {
        $document = new \DOMDocument();
        $document->loadXML(
            '<?xml version="1.0" encoding="UTF-8"?>
            <openimmo>
                <uebertragung umfang="TEIL"/>
                <anbieter>
                    <firma>ACME</firma>
                    <openimmo_anid>AFFA20090122174601064K1R1J6H6I4</openimmo_anid>
                    <immobilie>
                        <objektkategorie>
                            <vermarktungsart MIETE_PACHT="true"/>
                            <objektart>
                                <wohnung wohnungtyp="ETAGE"/>
                            </objektart>
                        </objektkategorie>
                        <geo>
                            <plz>22391</plz>
                        </geo>
                        <kontaktperson>
                            <name>Max Doe</name>
                        </kontaktperson>
                        <verwaltung_techn>
                            <aktion aktionart="DELETE"/>
                            <objektnr_extern>5873</objektnr_extern>
                            <openimmo_obid>OFFA20200414145437077I2A4G0I5E1</openimmo_obid>
                        </verwaltung_techn>
                    </immobilie>
                </anbieter>
            </openimmo>'
        );

        $records = $this->subject->convertDomDocumentToArray($document);
        $this->subject->writeToDatabase($records[0]);

        self::assertSame(0, $this->getDatabaseConnection()->selectCount('*', 'tx_realty_objects'));
    }

    /**
     * @test
     */
    public function writeToDatabaseForPartialSyncForDeletingExistingObjectWithoutAttachmentsMarksRecordAsDeleted()
    {
        $objectId = 'OFFA20200414145437077I2A4G0I5E1';
        $objectNumber = '5873';
        $this->getDatabaseConnection()->insertArray(
            'tx_realty_objects',
            ['openimmo_obid' => $objectId, 'object_number' => $objectNumber]
        );

        $document = new \DOMDocument();
        $document->loadXML(
            '<?xml version="1.0" encoding="UTF-8"?>
            <openimmo>
                <uebertragung umfang="TEIL"/>
                <anbieter>
                    <firma>ACME</firma>
                    <openimmo_anid>AFFA20090122174601064K1R1J6H6I4</openimmo_anid>
                    <immobilie>
                        <objektkategorie>
                            <vermarktungsart MIETE_PACHT="true"/>
                            <objektart>
                                <wohnung wohnungtyp="ETAGE"/>
                            </objektart>
                        </objektkategorie>
                        <geo>
                            <plz>22391</plz>
                        </geo>
                        <kontaktperson>
                            <name>Max Doe</name>
                        </kontaktperson>
                        <verwaltung_techn>
                            <aktion aktionart="DELETE"/>
                            <objektnr_extern>' . $objectNumber . '</objektnr_extern>
                            <openimmo_obid>' . $objectId . '</openimmo_obid>
                        </verwaltung_techn>
                    </immobilie>
                </anbieter>
            </openimmo>'
        );

        $records = $this->subject->convertDomDocumentToArray($document);
        $this->subject->writeToDatabase($records[0]);

        $count = $this->getDatabaseConnection()->selectCount(
            '*',
            'tx_realty_objects',
            'openimmo_obid = "' . $objectId . '" AND object_number = "' . $objectNumber . '" AND deleted = 1'
        );
        self::assertSame(1, $count);
    }

    /**
     * @test
     */
    public function writeToDatabaseForPartialSyncForDeletingInexistentObjectWithOneAttachmentNotCreatesRecord()
    {
        $document = new \DOMDocument();
        $document->loadXML(
            '<?xml version="1.0" encoding="UTF-8"?>
            <openimmo>
                <uebertragung umfang="TEIL"/>
                <anbieter>
                    <firma>ACME</firma>
                    <openimmo_anid>AFFA20090122174601064K1R1J6H6I4</openimmo_anid>
                    <immobilie>
                        <objektkategorie>
                            <vermarktungsart MIETE_PACHT="true"/>
                            <objektart>
                                <wohnung wohnungtyp="ETAGE"/>
                            </objektart>
                        </objektkategorie>
                        <geo>
                            <plz>22391</plz>
                        </geo>
                        <kontaktperson>
                            <name>Max Doe</name>
                        </kontaktperson>
                        <verwaltung_techn>
                            <aktion aktionart="DELETE"/>
                            <objektnr_extern>5873</objektnr_extern>
                            <openimmo_obid>OFFA20200414145437077I2A4G0I5E1</openimmo_obid>
                        </verwaltung_techn>
                        <anhaenge>
                            <anhang location="EXTERN">
                                <anhangtitel>Herzlich Willkommen</anhangtitel>
                                <format>jpg</format>
                                <daten>
                                    <pfad>5873-kurz_herzlich_willkommen.jpg</pfad>
                                </daten>
                            </anhang>
                        </anhaenge>
                    </immobilie>
                </anbieter>
            </openimmo>'
        );

        $records = $this->subject->convertDomDocumentToArray($document);
        $this->subject->writeToDatabase($records[0]);

        $this->subject->importFromZip();

        self::assertSame(0, $this->getDatabaseConnection()->selectCount('*', 'tx_realty_objects'));
    }

    /**
     * @test
     */
    public function writeToDatabaseForPartialSyncForDeletingExistingObjectWithOneAttachmentMarksRecordAsDeleted()
    {
        self::markTestIncomplete('This is a different bug.');

        $objectId = 'OFFA20200414145437077I2A4G0I5E1';
        $objectNumber = '5873';
        $this->getDatabaseConnection()->insertArray(
            'tx_realty_objects',
            ['openimmo_obid' => $objectId, 'object_number' => $objectNumber]
        );

        $document = new \DOMDocument();
        $document->loadXML(
            '<?xml version="1.0" encoding="UTF-8"?>
            <openimmo>
                <uebertragung umfang="TEIL"/>
                <anbieter>
                    <firma>ACME</firma>
                    <openimmo_anid>AFFA20090122174601064K1R1J6H6I4</openimmo_anid>
                    <immobilie>
                        <objektkategorie>
                            <vermarktungsart MIETE_PACHT="true"/>
                            <objektart>
                                <wohnung wohnungtyp="ETAGE"/>
                            </objektart>
                        </objektkategorie>
                        <geo>
                            <plz>22391</plz>
                        </geo>
                        <kontaktperson>
                            <name>Max Doe</name>
                        </kontaktperson>
                        <verwaltung_techn>
                            <aktion aktionart="DELETE"/>
                            <objektnr_extern>' . $objectNumber . '</objektnr_extern>
                            <openimmo_obid>' . $objectId . '</openimmo_obid>
                        </verwaltung_techn>
                        <anhaenge>
                            <anhang location="EXTERN">
                                <anhangtitel>Herzlich Willkommen</anhangtitel>
                                <format>jpg</format>
                                <daten>
                                    <pfad>5873-kurz_herzlich_willkommen.jpg</pfad>
                                </daten>
                            </anhang>
                        </anhaenge>
                    </immobilie>
                </anbieter>
            </openimmo>'
        );

        $records = $this->subject->convertDomDocumentToArray($document);
        $this->subject->writeToDatabase($records[0]);

        $count = $this->getDatabaseConnection()->selectCount(
            '*',
            'tx_realty_objects',
            'openimmo_obid = "' . $objectId . '" AND object_number = "' . $objectNumber . '" AND deleted = 1'
        );
        self::assertSame(1, $count);
    }

    /**
     * @test
     */
    public function importFromZipForPartialSyncForDeletingInexistentObjectWithoutAttachmentsNotCreatesRecord()
    {
        $xml =
            '<?xml version="1.0" encoding="UTF-8"?>
            <openimmo>
                <uebertragung umfang="TEIL"/>
                <anbieter>
                    <firma>ACME</firma>
                    <openimmo_anid>AFFA20090122174601064K1R1J6H6I4</openimmo_anid>
                    <immobilie>
                        <objektkategorie>
                            <vermarktungsart MIETE_PACHT="true"/>
                            <objektart>
                                <wohnung wohnungtyp="ETAGE"/>
                            </objektart>
                        </objektkategorie>
                        <geo>
                            <plz>22391</plz>
                        </geo>
                        <kontaktperson>
                            <name>Max Doe</name>
                        </kontaktperson>
                        <verwaltung_techn>
                            <aktion aktionart="DELETE"/>
                            <objektnr_extern>5873</objektnr_extern>
                            <openimmo_obid>OFFA20200414145437077I2A4G0I5E1</openimmo_obid>
                        </verwaltung_techn>
                    </immobilie>
                </anbieter>
            </openimmo>';
        $this->createZipFile($xml);

        $this->subject->importFromZip();

        self::assertSame(0, $this->getDatabaseConnection()->selectCount('*', 'tx_realty_objects'));
    }

    /**
     * @test
     */
    public function importFromZipForPartialSyncForDeletingExistingObjectWithoutAttachmentsMarksRecordAsDeleted()
    {
        $objectId = 'OFFA20200414145437077I2A4G0I5E1';
        $objectNumber = '5873';
        $this->getDatabaseConnection()->insertArray(
            'tx_realty_objects',
            ['openimmo_obid' => $objectId, 'object_number' => $objectNumber]
        );

        $xml =
            '<?xml version="1.0" encoding="UTF-8"?>
            <openimmo>
                <uebertragung umfang="TEIL"/>
                <anbieter>
                    <firma>ACME</firma>
                    <openimmo_anid>AFFA20090122174601064K1R1J6H6I4</openimmo_anid>
                    <immobilie>
                        <objektkategorie>
                            <vermarktungsart MIETE_PACHT="true"/>
                            <objektart>
                                <wohnung wohnungtyp="ETAGE"/>
                            </objektart>
                        </objektkategorie>
                        <geo>
                            <plz>22391</plz>
                        </geo>
                        <kontaktperson>
                            <name>Max Doe</name>
                        </kontaktperson>
                        <verwaltung_techn>
                            <aktion aktionart="DELETE"/>
                            <objektnr_extern>' . $objectNumber . '</objektnr_extern>
                            <openimmo_obid>' . $objectId . '</openimmo_obid>
                        </verwaltung_techn>
                    </immobilie>
                </anbieter>
            </openimmo>';
        $this->createZipFile($xml);

        $this->subject->importFromZip();

        $count = $this->getDatabaseConnection()->selectCount(
            '*',
            'tx_realty_objects',
            'openimmo_obid = "' . $objectId . '" AND object_number = "' . $objectNumber . '" AND deleted = 1'
        );
        self::assertSame(1, $count);
    }

    /**
     * @test
     */
    public function importFromZipForPartialSyncForDeletingInexistentObjectWithOneAttachmentNotCreatesRecord()
    {
        $xml =
            '<?xml version="1.0" encoding="UTF-8"?>
            <openimmo>
                <uebertragung umfang="TEIL"/>
                <anbieter>
                    <firma>ACME</firma>
                    <openimmo_anid>AFFA20090122174601064K1R1J6H6I4</openimmo_anid>
                    <immobilie>
                        <objektkategorie>
                            <vermarktungsart MIETE_PACHT="true"/>
                            <objektart>
                                <wohnung wohnungtyp="ETAGE"/>
                            </objektart>
                        </objektkategorie>
                        <geo>
                            <plz>22391</plz>
                        </geo>
                        <kontaktperson>
                            <name>Max Doe</name>
                        </kontaktperson>
                        <verwaltung_techn>
                            <aktion aktionart="DELETE"/>
                            <objektnr_extern>5873</objektnr_extern>
                            <openimmo_obid>OFFA20200414145437077I2A4G0I5E1</openimmo_obid>
                        </verwaltung_techn>
                        <anhaenge>
                            <anhang location="EXTERN">
                                <anhangtitel>Herzlich Willkommen</anhangtitel>
                                <format>jpg</format>
                                <daten>
                                    <pfad>5873-kurz_herzlich_willkommen.jpg</pfad>
                                </daten>
                            </anhang>
                        </anhaenge>
                    </immobilie>
                </anbieter>
            </openimmo>';
        $this->createZipFile($xml);

        $this->subject->importFromZip();

        self::assertSame(0, $this->getDatabaseConnection()->selectCount('*', 'tx_realty_objects'));
    }

    /**
     * @test
     */
    public function importFromZipForPartialSyncForDeletingExistingObjectWithOneAttachmentMarksRecordAsDeleted()
    {
        self::markTestIncomplete('This is a different bug.');

        $objectId = 'OFFA20200414145437077I2A4G0I5E1';
        $objectNumber = '5873';
        $this->getDatabaseConnection()->insertArray(
            'tx_realty_objects',
            ['openimmo_obid' => $objectId, 'object_number' => $objectNumber]
        );

        $xml =
            '<?xml version="1.0" encoding="UTF-8"?>
            <openimmo>
                <uebertragung umfang="TEIL"/>
                <anbieter>
                    <firma>ACME</firma>
                    <openimmo_anid>AFFA20090122174601064K1R1J6H6I4</openimmo_anid>
                    <immobilie>
                        <objektkategorie>
                            <vermarktungsart MIETE_PACHT="true"/>
                            <objektart>
                                <wohnung wohnungtyp="ETAGE"/>
                            </objektart>
                        </objektkategorie>
                        <geo>
                            <plz>22391</plz>
                        </geo>
                        <kontaktperson>
                            <name>Max Doe</name>
                        </kontaktperson>
                        <verwaltung_techn>
                            <aktion aktionart="DELETE"/>
                            <objektnr_extern>' . $objectNumber . '</objektnr_extern>
                            <openimmo_obid>' . $objectId . '</openimmo_obid>
                        </verwaltung_techn>
                        <anhaenge>
                            <anhang location="EXTERN">
                                <anhangtitel>Herzlich Willkommen</anhangtitel>
                                <format>jpg</format>
                                <daten>
                                    <pfad>5873-kurz_herzlich_willkommen.jpg</pfad>
                                </daten>
                            </anhang>
                        </anhaenge>
                    </immobilie>
                </anbieter>
            </openimmo>';
        $this->createZipFile($xml);

        $this->subject->importFromZip();

        $count = $this->getDatabaseConnection()->selectCount(
            '*',
            'tx_realty_objects',
            'openimmo_obid = "' . $objectId . '" AND object_number = "' . $objectNumber . '" AND deleted = 1'
        );
        self::assertSame(1, $count);
    }

    /**
     * @test
     */
    public function wasSuccessfulInitiallyReturnsTrue()
    {
        self::assertTrue($this->subject->wasSuccessful());
    }

    /**
     * @test
     */
    public function wasSuccessfulAfterSuccessfulImportReturnsTrue()
    {
        $this->copyTestFileIntoImportFolder('two-objects.zip');
        $this->subject->importFromZip();

        self::assertTrue($this->subject->wasSuccessful());
    }

    /**
     * @test
     */
    public function wasSuccessfulAfterErrorReturnsTrue()
    {
        $path = '/any/not/existing/import-path/';
        $this->globalConfiguration->setAsString('importFolder', $path);

        $this->subject->importFromZip();

        self::assertFalse($this->subject->wasSuccessful());
    }
}
