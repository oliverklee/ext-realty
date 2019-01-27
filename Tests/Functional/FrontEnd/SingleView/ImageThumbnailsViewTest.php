<?php

namespace OliverKlee\Realty\Tests\Functional\FrontEnd\SingleView;

use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use Prophecy\Prophecy\ObjectProphecy;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * Test case.
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 */
class ImageThumbnailsViewTest extends FunctionalTestCase
{
    /**
     * @var string[]
     */
    protected $testExtensionsToLoad = ['typo3conf/ext/oelib', 'typo3conf/ext/realty'];

    /**
     * @var \tx_realty_pi1_ImageThumbnailsView
     */
    private $subject = null;

    /**
     * @var \Tx_Oelib_TestingFramework
     */
    private $testingFramework = null;

    /**
     * TS Setup configuration for plugin.tx_realty_pi1
     *
     * @var \Tx_Oelib_Configuration
     */
    private $configuration = null;

    /**
     * @var ContentObjectRenderer|\PHPUnit_Framework_MockObject_MockObject
     */
    private $contentObject = null;

    /**
     * @var \tx_realty_Mapper_RealtyObject
     */
    private $realtyObjectMapper = null;

    protected function setUp()
    {
        parent::setUp();
        $this->testingFramework = new \Tx_Oelib_TestingFramework('tx_realty');
        $this->testingFramework->setResetAutoIncrementThreshold(99999999);
        $this->testingFramework->createFakeFrontEnd($this->testingFramework->createFrontEndPage());

        $this->contentObject = $this->getMock(ContentObjectRenderer::class);
        $this->subject = new \tx_realty_pi1_ImageThumbnailsView(
            ['templateFile' => 'EXT:realty/Resources/Private/Templates/FrontEnd/Plugin.html'],
            $this->contentObject
        );

        $configurationRegistry = \Tx_Oelib_ConfigurationRegistry::getInstance();
        $this->configuration = new \Tx_Oelib_Configuration();
        $this->configuration->setData(
            [
                'enableLightbox' => false,
                'singleImageMaxX' => 102,
                'singleImageMaxY' => 77,
                'lightboxImageWidthMax' => 1024,
                'lightboxImageHeightMax' => 768,
            ]
        );
        $configurationRegistry->set('plugin.tx_realty_pi1', $this->configuration);

        /** @var BackendUserAuthentication|ObjectProphecy $backEndUserProphecy */
        $backEndUserProphecy = $this->prophesize(BackendUserAuthentication::class);
        $backEndUserProphecy->isAdmin()->willReturn(true);
        $GLOBALS['BE_USER'] = $backEndUserProphecy->reveal();

        $this->realtyObjectMapper = \Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_RealtyObject::class);
    }

    protected function tearDown()
    {
        if (\file_exists($this->getPathOfTestFile())) {
            unlink($this->getPathOfTestFile());
        }
        $this->testingFramework->cleanUp();
        parent::tearDown();
    }

    /**
     * @return void
     */
    private function copyTestFile()
    {
        \copy(__DIR__ . '/../../Fixtures/test.jpg', $this->getPathOfTestFile());
    }

    /**
     * @return string
     */
    private function getPathOfTestFile()
    {
        return GeneralUtility::getFileAbsFileName('fileadmin/realty_attachments/102/test.jpg');
    }

    /*
     * current tests
     */

    /**
     * @test
     */
    public function renderReturnsEmptyResultForObjectWithoutImages()
    {
        $this->importDataSet(__DIR__ . '/../../Fixtures/RealtyObjects.xml');

        $result = $this->subject->render(['showUid' => 101]);

        self::assertSame('', $result);
    }

    /**
     * @test
     */
    public function renderReturnsNoUnreplacedMarkersForObjectWithAttachments()
    {
        $this->copyTestFile();
        $this->importDataSet(__DIR__ . '/../../Fixtures/Attachments.xml');
        $this->importDataSet(__DIR__ . '/../../Fixtures/RealtyObjects.xml');

        $result = $this->subject->render(['showUid' => 102]);

        self::assertNotContains('###', $result);
    }

    /**
     * @test
     */
    public function renderEnabledLightboxReturnsImageWithRelAttribute()
    {
        $this->configuration->setAsBoolean('enableLightbox', true);

        $this->copyTestFile();
        $this->importDataSet(__DIR__ . '/../../Fixtures/Attachments.xml');
        $this->importDataSet(__DIR__ . '/../../Fixtures/RealtyObjects.xml');

        $result = $this->subject->render(['showUid' => 102]);

        self::assertContains('data-lightbox="objectGallery"', $result);
    }

    /**
     * @test
     */
    public function renderDisabledLightboxNotReturnsImageWithRelAttribute()
    {
        $this->configuration->setAsBoolean('enableLightbox', false);

        $this->copyTestFile();
        $this->importDataSet(__DIR__ . '/../../Fixtures/Attachments.xml');
        $this->importDataSet(__DIR__ . '/../../Fixtures/RealtyObjects.xml');

        $result = $this->subject->render(['showUid' => 102]);

        self::assertNotContains('data-lightbox="objectGallery"', $result);
    }

    /**
     * @test
     */
    public function renderForDisabledLightboxNotAddsImageLink()
    {
        $this->configuration->setAsBoolean('enableLightbox', false);

        $this->copyTestFile();
        $this->importDataSet(__DIR__ . '/../../Fixtures/Attachments.xml');
        $this->importDataSet(__DIR__ . '/../../Fixtures/RealtyObjects.xml');

        $result = $this->subject->render(['showUid' => 102]);

        self::assertNotContains('<a href', $result);
    }

    /**
     * @test
     */
    public function renderForEnabledLightboxAddsImageLink()
    {
        $this->configuration->setAsBoolean('enableLightbox', true);

        $this->copyTestFile();
        $this->importDataSet(__DIR__ . '/../../Fixtures/Attachments.xml');
        $this->importDataSet(__DIR__ . '/../../Fixtures/RealtyObjects.xml');

        $result = $this->subject->render(['showUid' => 102]);

        self::assertContains('<a href', $result);
    }

    /**
     * @test
     */
    public function renderUsesThumbnailImageSize()
    {
        $this->copyTestFile();
        $this->importDataSet(__DIR__ . '/../../Fixtures/Attachments.xml');
        $this->importDataSet(__DIR__ . '/../../Fixtures/RealtyObjects.xml');

        $this->contentObject->expects(self::at(0))->method('cObjGetSingle')->with(
            'IMAGE',
            [
                'altText' => 'JPEG image & file',
                'titleText' => 'JPEG image & file',
                'file' => 'fileadmin/realty_attachments/102/test.jpg',
                'file.' => [
                    'width' => '102c',
                    'height' => '77c',
                ],
            ]
        );

        $this->subject->render(['showUid' => 102]);
    }

    /**
     * @test
     */
    public function renderForEnabledLightboxAlsoUsesLightboxImageSize()
    {
        $this->configuration->setAsBoolean('enableLightbox', true);

        $this->copyTestFile();
        $this->importDataSet(__DIR__ . '/../../Fixtures/Attachments.xml');
        $this->importDataSet(__DIR__ . '/../../Fixtures/RealtyObjects.xml');

        $this->contentObject->expects(self::at(1))->method('cObjGetSingle')->with(
            'IMAGE',
            [
                'altText' => 'JPEG image & file',
                'titleText' => 'JPEG image & file',
                'file' => 'fileadmin/realty_attachments/102/test.jpg',
                'file.' => [
                    'maxW' => '1024',
                    'maxH' => '768',
                ],
            ]
        );

        $this->subject->render(['showUid' => 102]);
    }

    /*
     * legacy tests
     */

    /**
     * @test
     */
    public function renderReturnsNoUnreplacedMarkersWhileTheResultIsNonEmpty()
    {
        /** @var \tx_realty_Model_RealtyObject $realtyObject */
        $realtyObject = $this->realtyObjectMapper->getNewGhost();
        $realtyObject->addImageRecord('foo', 'foo.jpg');

        self::assertNotContains(
            '###',
            $this->subject->render(['showUid' => $realtyObject->getUid()])
        );
    }

    /**
     * @test
     */
    public function renderForLightboxEnabledReturnsImageWithRelAttribute()
    {
        $this->configuration->setAsBoolean('enableLightbox', true);

        /** @var \tx_realty_Model_RealtyObject $realtyObject */
        $realtyObject = $this->realtyObjectMapper->getNewGhost();
        $realtyObject->addImageRecord('foo', 'foo.jpg');

        self::assertContains(
            'data-lightbox="objectGallery"',
            $this->subject->render(['showUid' => $realtyObject->getUid()])
        );
    }

    /**
     * @test
     */
    public function renderForDisabledLightboxNotAddsLightboxAttributeToImage()
    {
        $this->configuration->setAsBoolean('enableLightbox', false);

        /** @var \tx_realty_Model_RealtyObject $realtyObject */
        $realtyObject = $this->realtyObjectMapper->getNewGhost();
        $realtyObject->addImageRecord('foo', 'foo.jpg');

        self::assertNotContains(
            'data-lightbox="objectGallery"',
            $this->subject->render(['showUid' => $realtyObject->getUid()])
        );
    }

    /**
     * @test
     */
    public function renderForDisabledLightboxNotLinksImage()
    {
        $this->configuration->setAsBoolean('enableLightbox', false);

        /** @var \tx_realty_Model_RealtyObject $realtyObject */
        $realtyObject = $this->realtyObjectMapper->getNewGhost();
        $realtyObject->addImageRecord('fooBar', 'foo.jpg');

        self::assertNotContains(
            '<a href',
            $this->subject->render(['showUid' => $realtyObject->getUid()])
        );
    }

    /**
     * @test
     */
    public function renderForEnabledLightboxLinksImage()
    {
        $this->configuration->setAsBoolean('enableLightbox', true);

        /** @var \tx_realty_Model_RealtyObject $realtyObject */
        $realtyObject = $this->realtyObjectMapper->getNewGhost();
        $realtyObject->addImageRecord('fooBar', 'foo.jpg');

        self::assertContains('<a href', $this->subject->render(['showUid' => $realtyObject->getUid()]));
    }

    /**
     * @test
     */
    public function renderSizesImageWithThumbnailSize()
    {
        /** @var \tx_realty_Model_RealtyObject $realtyObject */
        $realtyObject = $this->realtyObjectMapper->getNewGhost();
        $realtyObject->addImageRecord('fooBar', 'foo.jpg');
        $this->contentObject->expects(self::at(0))->method('cObjGetSingle')->with(
            'IMAGE',
            [
                'altText' => 'fooBar',
                'titleText' => 'fooBar',
                'file' => \tx_realty_Model_Image::UPLOAD_FOLDER . 'foo.jpg',
                'file.' => [
                    'width' => '102c',
                    'height' => '77c',
                ],
            ]
        );

        $this->subject->render(['showUid' => $realtyObject->getUid()]);
    }

    /**
     * @test
     */
    public function renderForEnabledLightboxSizesImageWithThumbnailSize()
    {
        $this->configuration->setAsBoolean('enableLightbox', true);

        /** @var \tx_realty_Model_RealtyObject $realtyObject */
        $realtyObject = $this->realtyObjectMapper->getNewGhost();
        $realtyObject->addImageRecord('fooBar', 'foo.jpg');
        $this->contentObject->expects(self::at(0))->method('cObjGetSingle')->with(
            'IMAGE',
            [
                'altText' => 'fooBar',
                'titleText' => 'fooBar',
                'file' => \tx_realty_Model_Image::UPLOAD_FOLDER . 'foo.jpg',
                'file.' => [
                    'width' => '102c',
                    'height' => '77c',
                ],
            ]
        );

        $this->subject->render(['showUid' => $realtyObject->getUid()]);
    }

    /**
     * @test
     */
    public function renderForEnabledLightboxAlsoSizesImageWithLightboxSize()
    {
        $this->configuration->setAsBoolean('enableLightbox', true);

        /** @var \tx_realty_Model_RealtyObject $realtyObject */
        $realtyObject = $this->realtyObjectMapper->getNewGhost();
        $realtyObject->addImageRecord('fooBar', 'foo.jpg');
        $this->contentObject->expects(self::at(1))->method('cObjGetSingle')->with(
            'IMAGE',
            [
                'altText' => 'fooBar',
                'titleText' => 'fooBar',
                'file' => \tx_realty_Model_Image::UPLOAD_FOLDER . 'foo.jpg',
                'file.' => [
                    'maxW' => 1024,
                    'maxH' => 768,
                ],
            ]
        );

        $this->subject->render(['showUid' => $realtyObject->getUid()]);
    }
}
