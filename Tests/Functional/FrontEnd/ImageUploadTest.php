<?php

namespace OliverKlee\Realty\Tests\Functional\FrontEnd;

use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use Prophecy\Prophecy\ObjectProphecy;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Test case.
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class ImageUploadTest extends FunctionalTestCase
{
    /**
     * @var string[]
     */
    protected $testExtensionsToLoad = ['typo3conf/ext/oelib', 'typo3conf/ext/realty'];

    /**
     * @var \tx_realty_frontEndImageUpload
     */
    private $subject = null;

    /**
     * @var \Tx_Oelib_TestingFramework
     */
    private $testingFramework = null;

    protected function setUp()
    {
        parent::setUp();

        /** @var BackendUserAuthentication|ObjectProphecy $backEndUserProphecy */
        $backEndUserProphecy = $this->prophesize(BackendUserAuthentication::class);
        $backEndUserProphecy->isAdmin()->willReturn(true);
        $GLOBALS['BE_USER'] = $backEndUserProphecy->reveal();

        $this->testingFramework = new \Tx_Oelib_TestingFramework('tx_realty');
        $this->testingFramework->setResetAutoIncrementThreshold(99999999);
        $this->testingFramework->createFakeFrontEnd($this->testingFramework->createFrontEndPage());

        \Tx_Oelib_MapperRegistry::getInstance()->activateTestingMode($this->testingFramework);

        $this->importRecords();

        $this->subject = new \tx_realty_frontEndImageUpload(
            ['feEditorTemplateFile' => 'EXT:realty/Resources/Private/Templates/FrontEnd/Editor.html'],
            $this->getFrontEndController()->cObj,
            0,
            '',
            true
        );
        $this->subject->setRealtyObjectUid(102);
    }

    protected function tearDown()
    {
        GeneralUtility::rmdir($this->getAbsoluteAttachmentsPath(), true);
        GeneralUtility::rmdir($this->getAbsoluteUploadsPath(), true);

        $this->testingFramework->cleanUpWithoutDatabase();
        parent::tearDown();
    }

    /**
     * Returns the current front-end instance.
     *
     * @return TypoScriptFrontendController
     */
    private function getFrontEndController()
    {
        return $GLOBALS['TSFE'];
    }

    /*
     * Utility functions.
     */

    /**
     * Imports the dummy records into the DB.
     *
     * @return void
     *
     * @throws \Nimut\TestingFramework\Exception\Exception
     */
    private function importRecords()
    {
        $this->importDataSet(__DIR__ . '/../Fixtures/Attachments.xml');
        $this->importDataSet(__DIR__ . '/../Fixtures/RealtyObjects.xml');
        $this->importDataSet(__DIR__ . '/../Fixtures/FrontEndUsers.xml');
    }

    /**
     * @return string
     */
    private function getAbsoluteUploadsPath()
    {
        return GeneralUtility::getFileAbsFileName('uploads/tx_realty/');
    }

    /**
     * @return string
     */
    private function getAbsoluteAttachmentsPath()
    {
        return GeneralUtility::getFileAbsFileName('fileadmin/realty_attachments/');
    }

    /**
     * @param string $fileName
     *
     * @return void
     */
    private function copyFixtureFileToUploads($fileName)
    {
        $uploadsPath = $this->getAbsoluteUploadsPath();
        if (!\file_exists($uploadsPath)) {
            GeneralUtility::mkdir_deep($uploadsPath);
        }
        \copy(__DIR__ . '/../Fixtures/' . $fileName, $uploadsPath . $fileName);
    }

    /*
     * Tests for the functions called in the XML form.
     */

    /**
     * @test
     */
    public function processImageUploadCreatesFile()
    {
        $fileName = 'test2.jpg';
        $this->copyFixtureFileToUploads($fileName);

        $numberOfFiles = \Tx_Oelib_Db::count('sys_file');

        $this->subject->processImageUpload(['caption' => 'test image', 'image' => $fileName]);

        self::assertSame($numberOfFiles + 1, \Tx_Oelib_Db::count('sys_file'));
    }

    /**
     * @test
     */
    public function processImageUploadIncreasesNumberOfAttachments()
    {
        $fileName = 'test2.jpg';
        $this->copyFixtureFileToUploads($fileName);

        $this->subject->processImageUpload(['caption' => 'test image', 'image' => $fileName]);

        $recordData = \Tx_Oelib_Db::selectSingle('*', 'tx_realty_objects', 'uid = 102');
        self::assertSame(4, (int)$recordData['attachments']);
    }

    /**
     * @test
     */
    public function processImageUploadSavesCaption()
    {
        $fileName = 'test2.jpg';
        $this->copyFixtureFileToUploads($fileName);

        $this->subject->processImageUpload(['caption' => 'test image', 'image' => $fileName]);

        self::assertSame(1, \Tx_Oelib_Db::count('sys_file_metadata', 'title = "test image"'));
    }

    /**
     * @test
     */
    public function processImageUploadForEmptyFileNameNotCreatesFile()
    {
        $numberOfFiles = \Tx_Oelib_Db::count('sys_file');

        $this->subject->processImageUpload(['caption' => 'test image', 'image' => '']);

        self::assertSame($numberOfFiles, \Tx_Oelib_Db::count('sys_file'));
    }

    /**
     * @test
     */
    public function processImageUploadForEmptyCaptionNotCreatesFile()
    {
        $fileName = 'test2.jpg';
        $this->copyFixtureFileToUploads($fileName);

        $numberOfFiles = \Tx_Oelib_Db::count('sys_file');

        $this->subject->processImageUpload(['caption' => '', 'image' => $fileName]);

        self::assertSame($numberOfFiles, \Tx_Oelib_Db::count('sys_file'));
    }

    /**
     * @test
     */
    public function processImageUploadForEmptyCaptionNotIncreasesNumberOfAttachments()
    {
        $fileName = 'test2.jpg';
        $this->copyFixtureFileToUploads($fileName);

        $this->subject->processImageUpload(['caption' => '', 'image' => $fileName]);

        $recordData = \Tx_Oelib_Db::selectSingle('*', 'tx_realty_objects', 'uid = 102');
        self::assertSame(3, (int)$recordData['attachments']);
    }

    /**
     * @test
     */
    public function processImageUploadCanDeleteAttachedFile()
    {
        $fileName = 'test2.jpg';
        $this->copyFixtureFileToUploads($fileName);

        $numberOfFiles = \Tx_Oelib_Db::count('sys_file');

        $this->subject->processImageUpload(['imagesToDelete' => '10']);

        self::assertSame($numberOfFiles - 1, \Tx_Oelib_Db::count('sys_file'));
    }

    /**
     * @test
     */
    public function processImageUploadForDeletedFileDecreasesOfAttachments()
    {
        $this->subject->processImageUpload(['imagesToDelete' => '10']);

        $recordData = \Tx_Oelib_Db::selectSingle('*', 'tx_realty_objects', 'uid = 102');
        self::assertSame(2, (int)$recordData['attachments']);
    }

    /**
     * @test
     */
    public function processImageUploadNotDeletesNonAttachedFile()
    {
        $fileName = 'test2.jpg';
        $this->copyFixtureFileToUploads($fileName);

        $numberOfFiles = \Tx_Oelib_Db::count('sys_file');

        $this->subject->processImageUpload(['imagesToDelete' => '13']);

        self::assertSame($numberOfFiles, \Tx_Oelib_Db::count('sys_file'));
    }

    /*
     * Tests concerning validation.
     */

    /**
     * @test
     */
    public function checkFileForNoImageReturnsTrue()
    {
        self::assertTrue($this->subject->checkFile(['value' => '']));
    }

    /**
     * @test
     */
    public function checkFileForJpgFileReturnsTrue()
    {
        $this->subject->setFakedFormValue('caption', 'foo');

        self::assertTrue($this->subject->checkFile(['value' => 'foo.jpg']));
    }

    /**
     * @test
     */
    public function checkFileForPdfFileReturnsFalse()
    {
        $this->subject->setFakedFormValue('caption', 'foo');

        self::assertFalse($this->subject->checkFile(['value' => 'foo.pdf']));
    }

    /**
     * @test
     */
    public function checkFileForPsFileReturnsFalse()
    {
        $this->subject->setFakedFormValue('caption', 'foo');

        self::assertFalse($this->subject->checkFile(['value' => 'foo.ps']));
    }

    /**
     * @test
     */
    public function checkFileWithoutCaptionReturnsFalse()
    {
        self::assertFalse($this->subject->checkFile(['value' => 'foo.jpg']));
    }

    /**
     * @test
     */
    public function checkFileForInvalidFooExtensionReturnsFalse()
    {
        $this->subject->setFakedFormValue('caption', 'foo');

        self::assertFalse($this->subject->checkFile(['value' => 'foo.foo']));
    }

    /**
     * @test
     */
    public function getImageUploadErrorMessageForEmptyCaption()
    {
        $this->subject->checkFile(['value' => 'foo.jpg']);

        self::assertSame(
            $this->subject->translate('message_empty_caption'),
            $this->subject->getImageUploadErrorMessage()
        );
    }

    /**
     * @test
     */
    public function getImageUploadErrorMessageForInvalidExtension()
    {
        $this->subject->setFakedFormValue('caption', 'foo');
        $this->subject->checkFile(['value' => 'foo.foo']);

        self::assertSame(
            $this->subject->translate('message_invalid_type'),
            $this->subject->getImageUploadErrorMessage()
        );
    }
}
