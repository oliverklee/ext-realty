<?php

namespace OliverKlee\Realty\Tests\Functional\FrontEnd;

use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Test case.
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
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

    /**
     * UID of the dummy object
     *
     * @var int
     */
    private $dummyObjectUid = 0;

    /**
     * title for the first dummy image
     *
     * @var string
     */
    private static $firstImageTitle = 'first test image';

    /**
     * file name for the first dummy image
     *
     * @var string
     */
    private static $firstImageFileName = 'first.jpg';

    /**
     * title for the second dummy image
     *
     * @var string
     */
    private static $secondImageTitle = 'second test image';

    /**
     * file name for the second dummy image
     *
     * @var string
     */
    private static $secondImageFileName = 'second.jpg';

    /**
     * backup of $GLOBALS['TYPO3_CONF_VARS']['GFX']
     *
     * @var array
     */
    private $graphicsConfigurationBackup = [];

    protected function setUp()
    {
        parent::setUp();
        $this->graphicsConfigurationBackup = $GLOBALS['TYPO3_CONF_VARS']['GFX'];
        $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'] = 'gif,jpg,jpeg,tif,tiff,bmp,pcx,tga,png,pdf,ai';

        $this->testingFramework = new \Tx_Oelib_TestingFramework('tx_realty');
        $this->testingFramework->setResetAutoIncrementThreshold(99999999);
        $this->testingFramework->createFakeFrontEnd($this->testingFramework->createFrontEndPage());

        \Tx_Oelib_MapperRegistry::getInstance()->activateTestingMode($this->testingFramework);

        $this->createDummyRecords();

        $this->subject = new \tx_realty_frontEndImageUpload(
            ['feEditorTemplateFile' => 'EXT:realty/Resources/Private/Templates/FrontEnd/Editor.html'],
            $this->getFrontEndController()->cObj,
            0,
            '',
            true
        );
        $this->subject->setRealtyObjectUid($this->dummyObjectUid);
    }

    protected function tearDown()
    {
        $this->testingFramework->cleanUp();
        $GLOBALS['TYPO3_CONF_VARS']['GFX'] = $this->graphicsConfigurationBackup;
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

    ///////////////////////
    // Utility functions.
    ///////////////////////

    /**
     * Creates dummy records in the DB and logs in a front-end user.
     *
     * @return void
     */
    private function createDummyRecords()
    {
        $userUid = $this->testingFramework->createFrontEndUser();

        $this->dummyObjectUid = $this->testingFramework->createRecord(
            'tx_realty_objects',
            ['owner' => $userUid]
        );
        $this->createImageRecords();
    }

    /**
     * Creates dummy image records in the DB.
     *
     * @return void
     */
    private function createImageRecords()
    {
        $realtyObject = new \tx_realty_Model_RealtyObject(true);
        $realtyObject->loadRealtyObject($this->dummyObjectUid);

        $realtyObject->addImageRecord(self::$firstImageTitle, self::$firstImageFileName);
        $realtyObject->addImageRecord(self::$secondImageTitle, self::$secondImageFileName);
        $realtyObject->writeToDatabase();

        $this->testingFramework->markTableAsDirty('tx_realty_images');
    }

    ////////////////////////////////////////////////////
    // Tests for the functions called in the XML form.
    ////////////////////////////////////////////////////

    /**
     * @test
     */
    public function processImageUploadWritesNewImageRecordForCurrentObjectToTheDatabase()
    {
        $this->subject->processImageUpload(
            [
                'caption' => 'test image',
                'image' => 'image.jpg',
            ]
        );

        self::assertEquals(
            1,
            $this->testingFramework->countRecords(
                'tx_realty_images',
                'image = "image.jpg" AND caption = "test image"'
            )
        );
    }

    /**
     * @test
     */
    public function processImageUploadStoresCurrentObjectUidAsParentForTheImage()
    {
        $this->subject->processImageUpload(
            [
                'caption' => 'test image',
                'image' => 'image.jpg',
            ]
        );

        self::assertEquals(
            1,
            $this->testingFramework->countRecords(
                'tx_realty_images',
                'object=' . $this->dummyObjectUid . ' AND caption="test image" AND image="image.jpg"'
            )
        );
    }

    /**
     * @test
     */
    public function processImageUploadDoesNotInsertAnImageIfOnlyACaptionProvided()
    {
        $this->subject->processImageUpload(
            [
                'caption' => 'test image',
                'image' => '',
            ]
        );

        self::assertEquals(
            0,
            $this->testingFramework->countRecords(
                'tx_realty_images',
                'object=' . $this->dummyObjectUid . ' AND caption="test image"'
            )
        );
    }

    /**
     * @test
     */
    public function processImageUploadDeletesImageRecordForCurrentObjectFromTheDatabase()
    {
        $this->subject->processImageUpload(
            ['imagesToDelete' => 'attached_image_0,']
        );

        self::assertEquals(
            1,
            $this->testingFramework->countRecords(
                'tx_realty_images',
                '1=1' . \Tx_Oelib_Db::enableFields('tx_realty_images')
            )
        );
    }

    /**
     * @test
     */
    public function processImageUploadDeletesImageTwoRecordsForCurrentObjectFromTheDatabase()
    {
        $this->subject->processImageUpload(
            ['imagesToDelete' => 'attached_image_0,attached_image_1,']
        );

        self::assertEquals(
            0,
            $this->testingFramework->countRecords(
                'tx_realty_images',
                '1=1' . \Tx_Oelib_Db::enableFields('tx_realty_images')
            )
        );
    }

    /////////////////////////////////
    // Tests concerning validation.
    /////////////////////////////////

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
    public function checkFileForGifFileReturnsTrue()
    {
        $this->subject->setFakedFormValue('caption', 'foo');

        self::assertTrue($this->subject->checkFile(['value' => 'foo.gif']));
    }

    /**
     * @test
     */
    public function checkFileForPngFileReturnsTrue()
    {
        $this->subject->setFakedFormValue('caption', 'foo');

        self::assertTrue($this->subject->checkFile(['value' => 'foo.png']));
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
    public function checkFileForJpegFileReturnsTrue()
    {
        $this->subject->setFakedFormValue('caption', 'foo');

        self::assertTrue($this->subject->checkFile(['value' => 'foo.jpeg']));
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
