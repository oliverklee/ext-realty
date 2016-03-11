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
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Test case.
 *
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 */
class tx_realty_FrontEnd_ImageUploadTest extends Tx_Phpunit_TestCase
{
    /**
     * @var tx_realty_frontEndImageUpload
     */
    private $fixture = null;

    /**
     * @var Tx_Oelib_TestingFramework
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
    private $graphicsConfigurationBackup = array();

    protected function setUp()
    {
        $this->graphicsConfigurationBackup = $GLOBALS['TYPO3_CONF_VARS']['GFX'];
        $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext']
            = 'gif,jpg,jpeg,tif,tiff,bmp,pcx,tga,png,pdf,ai';

        $this->testingFramework = new Tx_Oelib_TestingFramework('tx_realty');
        $this->testingFramework->createFakeFrontEnd();

        Tx_Oelib_MapperRegistry::getInstance()
            ->activateTestingMode($this->testingFramework);

        $this->createDummyRecords();

        $this->fixture = new tx_realty_frontEndImageUpload(
            array('feEditorTemplateFile'
                => 'EXT:realty/pi1/tx_realty_frontEndEditor.html'
            ),
            $this->getFrontEndController()->cObj,
            0,
            '',
            true
        );
        $this->fixture->setRealtyObjectUid($this->dummyObjectUid);
    }

    protected function tearDown()
    {
        $this->testingFramework->cleanUp();

        $GLOBALS['TYPO3_CONF_VARS']['GFX'] = $this->graphicsConfigurationBackup;
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
            'tx_realty_objects', array('owner' => $userUid)
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
        $realtyObject = new tx_realty_Model_RealtyObject(true);
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
        $this->fixture->processImageUpload(
            array(
                'caption' => 'test image',
                'image' => array('name' => 'image.jpg')
            )
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
        $this->fixture->processImageUpload(
            array(
                'caption' => 'test image',
                'image' => array('name' => 'image.jpg')
            )
        );

        self::assertEquals(
            1,
            $this->testingFramework->countRecords(
                'tx_realty_images',
                'object=' . $this->dummyObjectUid .
                    ' AND caption="test image" AND image="image.jpg"'
            )
        );
    }

    /**
     * @test
     */
    public function processImageUploadDoesNotInsertAnImageIfOnlyACaptionProvided()
    {
        $this->fixture->processImageUpload(
            array(
                'caption' => 'test image',
                'image' => array('name' => '')
            )
        );

        self::assertEquals(
            0,
            $this->testingFramework->countRecords(
                'tx_realty_images',
                'object=' . $this->dummyObjectUid .
                    ' AND caption="test image"'
            )
        );
    }

    /**
     * @test
     */
    public function processImageUploadDeletesImageRecordForCurrentObjectFromTheDatabase()
    {
        $this->fixture->processImageUpload(
            array('imagesToDelete' => 'attached_image_0,')
        );

        self::assertEquals(
            1,
            $this->testingFramework->countRecords(
                'tx_realty_images',
                '1=1' . Tx_Oelib_Db::enableFields('tx_realty_images')
            )
        );
    }

    /**
     * @test
     */
    public function processImageUploadDeletesImageTwoRecordsForCurrentObjectFromTheDatabase()
    {
        $this->fixture->processImageUpload(
            array('imagesToDelete' => 'attached_image_0,attached_image_1,')
        );

        self::assertEquals(
            0,
            $this->testingFramework->countRecords(
                'tx_realty_images',
                '1=1' . Tx_Oelib_Db::enableFields('tx_realty_images')
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
        self::assertTrue(
            $this->fixture->checkFile(array('value' => array('name')))
        );
    }

    /**
     * @test
     */
    public function checkFileForGifFileReturnsTrue()
    {
        $this->fixture->setFakedFormValue('caption', 'foo');

        self::assertTrue(
            $this->fixture->checkFile(
                array('value' => array('name' => 'foo.gif', 'size' => 1))
            )
        );
    }

    /**
     * @test
     */
    public function checkFileForPngFileReturnsTrue()
    {
        $this->fixture->setFakedFormValue('caption', 'foo');

        self::assertTrue(
            $this->fixture->checkFile(
                array('value' => array('name' => 'foo.png', 'size' => 1))
            )
        );
    }

    /**
     * @test
     */
    public function checkFileForJpgFileReturnsTrue()
    {
        $this->fixture->setFakedFormValue('caption', 'foo');

        self::assertTrue(
            $this->fixture->checkFile(
                array('value' => array('name' => 'foo.jpg', 'size' => 1))
            )
        );
    }

    /**
     * @test
     */
    public function checkFileForJpegFileReturnsTrue()
    {
        $this->fixture->setFakedFormValue('caption', 'foo');

        self::assertTrue(
            $this->fixture->checkFile(
                array('value' => array('name' => 'foo.jpeg', 'size' => 1))
            )
        );
    }

    /**
     * @test
     */
    public function checkFileForPdfFileReturnsFalse()
    {
        $this->fixture->setFakedFormValue('caption', 'foo');

        self::assertFalse(
            $this->fixture->checkFile(
                array('value' => array('name' => 'foo.pdf', 'size' => 1))
            )
        );
    }

    /**
     * @test
     */
    public function checkFileForPsFileReturnsFalse()
    {
        $this->fixture->setFakedFormValue('caption', 'foo');

        self::assertFalse(
            $this->fixture->checkFile(
                array('value' => array('name' => 'foo.ps', 'size' => 1))
            )
        );
    }

    /**
     * @test
     */
    public function checkFileWithoutCaptionReturnsFalse()
    {
        self::assertFalse(
            $this->fixture->checkFile(
                array('value' => array('name' => 'foo.jpg', 'size' => 1))
            )
        );
    }

    /**
     * @test
     */
    public function checkFileForTooLargeImageReturnsFalse()
    {
        $tooLarge = ($GLOBALS['TYPO3_CONF_VARS']['BE']['maxFileSize'] * 1024) + 1;
        $this->fixture->setFakedFormValue('caption', 'foo');

        self::assertFalse(
            $this->fixture->checkFile(
                array('value' => array('name' => 'foo.jpg', 'size' => $tooLarge))
            )
        );
    }

    /**
     * @test
     */
    public function checkFileForInvalidFooExtensionReturnsFalse()
    {
        $this->fixture->setFakedFormValue('caption', 'foo');

        self::assertFalse(
            $this->fixture->checkFile(
                array('value' => array('name' => 'foo.foo', 'size' => 1))
            )
        );
    }

    /**
     * @test
     */
    public function getImageUploadErrorMessageForEmptyCaption()
    {
        $this->fixture->checkFile(
            array('value' => array('name' => 'foo.jpg', 'size' => 1))
        );

        self::assertEquals(
            $this->fixture->translate('message_empty_caption'),
            $this->fixture->getImageUploadErrorMessage()
        );
    }

    /**
     * @test
     */
    public function getImageUploadErrorMessageForInvalidExtension()
    {
        $this->fixture->setFakedFormValue('caption', 'foo');
        $this->fixture->checkFile(
            array('value' => array('name' => 'foo.foo', 'size' => 1))
        );

        self::assertEquals(
            $this->fixture->translate('message_invalid_type'),
            $this->fixture->getImageUploadErrorMessage()
        );
    }

    /**
     * @test
     */
    public function getImageUploadErrorMessageForTooLargeImage()
    {
        $tooLarge = ($GLOBALS['TYPO3_CONF_VARS']['BE']['maxFileSize'] * 1024) + 1;
        $this->fixture->setFakedFormValue('caption', 'foo');
        $this->fixture->checkFile(
            array('value' => array('name' => 'foo.jpg', 'size' => $tooLarge))
        );

        self::assertEquals(
            $this->fixture->translate('message_image_too_large'),
            $this->fixture->getImageUploadErrorMessage()
        );
    }

    //////////////////////////////////////////////////
    // Tests concerning functions used after submit.
    //////////////////////////////////////////////////

    /**
     * @test
     */
    public function getRedirectUrlReturnsUrlWithCurrentPageIdAsTargetPageIfProceedUploadWasTrue()
    {
        $pageUid = $this->testingFramework->createFrontEndPage();
        $this->fixture->setConfigurationValue('feEditorRedirectPid', $pageUid);
        $this->fixture->setFakedFormValue('proceed_image_upload', 1);

        self::assertContains(
            '?id=' . $this->getFrontEndController()->id,
            $this->fixture->getRedirectUrl()
        );
    }

    /**
     * @test
     */
    public function getRedirectUrlReturnsUrlShowUidInUrlIfProceedUploadWasTrue()
    {
        $pageUid = $this->testingFramework->createFrontEndPage();
        $this->fixture->setConfigurationValue('feEditorRedirectPid', $pageUid);
        $this->fixture->setFakedFormValue('proceed_image_upload', 1);

        self::assertContains(
            'tx_realty_pi1[showUid]',
            $this->fixture->getRedirectUrl()
        );
    }

    /**
     * @test
     */
    public function getRedirectUrlReturnsUrlWithCurrentConfiguredRedirectPageIdAsTargetPageIfProceedUploadWasFalse()
    {
        $pageUid = $this->testingFramework->createFrontEndPage();
        $this->fixture->setConfigurationValue('feEditorRedirectPid', $pageUid);
        $this->fixture->setFakedFormValue('proceed_image_upload', 0);

        self::assertContains(
            '?id=' . $pageUid,
            $this->fixture->getRedirectUrl()
        );
    }
}
