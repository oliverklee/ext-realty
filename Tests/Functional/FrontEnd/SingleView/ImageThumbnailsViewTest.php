<?php

namespace OliverKlee\Realty\Tests\Functional\FrontEnd\SingleView;

use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use OliverKlee\Realty\Tests\Functional\Traits\FalHelper;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * Test case.
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 */
class ImageThumbnailsViewTest extends FunctionalTestCase
{
    use FalHelper;

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
     * @var \Tx_Oelib_Configuration
     */
    private $configuration = null;

    /**
     * @var ContentObjectRenderer|\PHPUnit_Framework_MockObject_MockObject
     */
    private $contentObject = null;

    protected function setUp()
    {
        parent::setUp();

        $this->provideAdminBackEndUserForFal();

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
    }

    protected function tearDown()
    {
        if (\file_exists($this->getPathOfTestFile())) {
            unlink($this->getPathOfTestFile());
        }
        $this->testingFramework->cleanUpWithoutDatabase();
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
}
