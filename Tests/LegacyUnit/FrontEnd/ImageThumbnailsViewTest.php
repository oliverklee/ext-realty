<?php

use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * Test case.
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 */
class tx_realty_FrontEnd_ImageThumbnailsViewTest extends \Tx_Phpunit_TestCase
{
    /**
     * @var tx_realty_pi1_ImageThumbnailsView
     */
    private $fixture = null;

    /**
     * @var Tx_Oelib_TestingFramework
     */
    private $testingFramework = null;

    /**
     * TS Setup configuration for plugin.tx_realty_pi1
     *
     * @var Tx_Oelib_Configuration
     */
    private $configuration = null;

    /**
     * TS Setup configuration for plugin.tx_realty_pi1.images
     *
     * @var Tx_Oelib_Configuration
     */
    private $imagesConfiguration = null;

    /**
     * @var ContentObjectRenderer|PHPUnit_Framework_MockObject_MockObject
     */
    private $contentObject = null;

    /**
     * @var tx_realty_Mapper_RealtyObject
     */
    private $realtyObjectMapper = null;

    protected function setUp()
    {
        $this->testingFramework = new Tx_Oelib_TestingFramework('tx_realty');
        $this->testingFramework->createFakeFrontEnd();

        $this->contentObject = $this->getMock(ContentObjectRenderer::class);
        $this->fixture = new tx_realty_pi1_ImageThumbnailsView(
            ['templateFile' => 'EXT:realty/Resources/Private/Templates/FrontEnd/Plugin.html'],
            $this->contentObject
        );

        $configurationRegistry = Tx_Oelib_ConfigurationRegistry::getInstance();
        $this->configuration = new Tx_Oelib_Configuration();
        $this->configuration->setData(
            [
                'enableLightbox' => false,
                'singleImageMaxX' => 102,
                'singleImageMaxY' => 77,
                'lightboxImageWidthMax' => 1024,
                'lightboxImageHeightMax' => 768,
                'images.' => [
                    '1.' => [],
                    '2.' => [],
                    '3.' => [],
                    '4.' => [],
                ],
            ]
        );
        $configurationRegistry->set('plugin.tx_realty_pi1', $this->configuration);

        $this->imagesConfiguration = new Tx_Oelib_Configuration();
        $this->imagesConfiguration->setData([
            '1.' => [],
            '2.' => [],
            '3.' => [],
            '4.' => [],
        ]);
        $configurationRegistry->set('plugin.tx_realty_pi1.images', $this->imagesConfiguration);

        $this->realtyObjectMapper = Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_RealtyObject::class);
    }

    protected function tearDown()
    {
        $this->testingFramework->cleanUp();
    }

    /*
     * Testing the image thumbnails view
     */

    /**
     * @test
     */
    public function renderReturnsEmptyResultForUidOfObjectWithoutImagesProvided()
    {
        self::assertEquals(
            '',
            $this->fixture->render(['showUid' => $this->realtyObjectMapper->getNewGhost()->getUid()])
        );
    }

    /**
     * @test
     */
    public function renderReturnsNoUnreplacedMarkersWhileTheResultIsNonEmpty()
    {
        /** @var tx_realty_Model_RealtyObject $realtyObject */
        $realtyObject = $this->realtyObjectMapper->getNewGhost();
        $realtyObject->addImageRecord('foo', 'foo.jpg');

        self::assertNotContains(
            '###',
            $this->fixture->render(['showUid' => $realtyObject->getUid()])
        );
    }

    /**
     * @test
     */
    public function renderForLightboxEnabledReturnsImageWithRelAttribute()
    {
        $this->configuration->setAsBoolean('enableLightbox', true);

        /** @var tx_realty_Model_RealtyObject $realtyObject */
        $realtyObject = $this->realtyObjectMapper->getNewGhost();
        $realtyObject->addImageRecord('foo', 'foo.jpg');

        self::assertContains(
            'data-lightbox="objectGallery"',
            $this->fixture->render(['showUid' => $realtyObject->getUid()])
        );
    }

    /**
     * @test
     */
    public function renderReturnsNoNonHtmlspecialcharedImageCaptionForLightboxStyledGallery()
    {
        /** @var tx_realty_Model_RealtyObject $realtyObject */
        $realtyObject = $this->realtyObjectMapper->getNewGhost();
        $realtyObject->addImageRecord('foo</br>', 'foo.jpg');

        self::assertNotContains(
            'foo</br>',
            $this->fixture->render(['showUid' => $realtyObject->getUid()])
        );
    }

    /**
     * @test
     */
    public function renderForDisabledLightboxNotAddsLightboxAttributeToImage()
    {
        /** @var tx_realty_Model_RealtyObject $realtyObject */
        $realtyObject = $this->realtyObjectMapper->getNewGhost();
        $realtyObject->addImageRecord('foo', 'foo.jpg');

        self::assertNotContains(
            'data-lightbox="objectGallery"',
            $this->fixture->render(['showUid' => $realtyObject->getUid()])
        );
    }

    /**
     * @test
     */
    public function renderForDisabledLightboxNotLinksImage()
    {
        /** @var tx_realty_Model_RealtyObject $realtyObject */
        $realtyObject = $this->realtyObjectMapper->getNewGhost();
        $realtyObject->addImageRecord('fooBar', 'foo.jpg');

        self::assertNotContains(
            '<a href',
            $this->fixture->render(['showUid' => $realtyObject->getUid()])
        );
    }

    /**
     * @test
     */
    public function renderSizesImageWithThumbnailSize()
    {
        /** @var tx_realty_Model_RealtyObject $realtyObject */
        $realtyObject = $this->realtyObjectMapper->getNewGhost();
        $realtyObject->addImageRecord('fooBar', 'foo.jpg');
        $this->contentObject->expects(self::at(0))->method('cObjGetSingle')->with(
            'IMAGE',
            [
                'altText' => 'fooBar',
                'titleText' => 'fooBar',
                'file' => tx_realty_Model_Image::UPLOAD_FOLDER . 'foo.jpg',
                'file.' => [
                    'width' => '102c',
                    'height' => '77c',
                ],
            ]
        );

        $this->fixture->render(['showUid' => $realtyObject->getUid()]);
    }

    /**
     * @test
     */
    public function renderForEnabledLightboxSizesImageWithThumbnailSize()
    {
        $this->configuration->setAsBoolean('enableLightbox', true);

        /** @var tx_realty_Model_RealtyObject $realtyObject */
        $realtyObject = $this->realtyObjectMapper->getNewGhost();
        $realtyObject->addImageRecord('fooBar', 'foo.jpg');
        $this->contentObject->expects(self::at(0))->method('cObjGetSingle')->with(
            'IMAGE',
            [
                'altText' => 'fooBar',
                'titleText' => 'fooBar',
                'file' => tx_realty_Model_Image::UPLOAD_FOLDER . 'foo.jpg',
                'file.' => [
                    'width' => '102c',
                    'height' => '77c',
                ],
            ]
        );

        $this->fixture->render(['showUid' => $realtyObject->getUid()]);
    }

    /**
     * @test
     */
    public function renderForEnabledLightboxAlsoSizesImageWithLightboxSize()
    {
        $this->configuration->setAsBoolean('enableLightbox', true);

        /** @var tx_realty_Model_RealtyObject $realtyObject */
        $realtyObject = $this->realtyObjectMapper->getNewGhost();
        $realtyObject->addImageRecord('fooBar', 'foo.jpg');
        $this->contentObject->expects(self::at(1))->method('cObjGetSingle')->with(
            'IMAGE',
            [
                'altText' => 'fooBar',
                'titleText' => 'fooBar',
                'file' => tx_realty_Model_Image::UPLOAD_FOLDER . 'foo.jpg',
                'file.' => [
                    'maxW' => 1024,
                    'maxH' => 768,
                ],
            ]
        );

        $this->fixture->render(['showUid' => $realtyObject->getUid()]);
    }

    /*
     * Tests concerning the image positions
     */

    /**
     * @test
     */
    public function renderForImageInPosition1AndNoSizesSetUsesGlobalThumbnailSizes()
    {
        /** @var tx_realty_Model_RealtyObject $realtyObject */
        $realtyObject = $this->realtyObjectMapper->getNewGhost();
        $realtyObject->addImageRecord('fooBar', 'foo.jpg', 1);
        $this->contentObject->expects(self::at(0))->method('cObjGetSingle')->with(
            'IMAGE',
            [
                'altText' => 'fooBar',
                'titleText' => 'fooBar',
                'file' => tx_realty_Model_Image::UPLOAD_FOLDER . 'foo.jpg',
                'file.' => [
                    'width' => '102c',
                    'height' => '77c',
                ],
            ]
        );

        $this->fixture->render(['showUid' => $realtyObject->getUid()]);
    }

    /**
     * @test
     */
    public function renderForImageInPosition1AndThumbnailSizesUsesPositionSpecificThumbnailSizes()
    {
        $this->imagesConfiguration->set('1.', ['singleImageMaxX' => 40, 'singleImageMaxY' => 30]);

        /** @var tx_realty_Model_RealtyObject $realtyObject */
        $realtyObject = $this->realtyObjectMapper->getNewGhost();
        $realtyObject->addImageRecord('fooBar', 'foo.jpg', 1);
        $this->contentObject->expects(self::at(0))->method('cObjGetSingle')->with(
            'IMAGE',
            [
                'altText' => 'fooBar',
                'titleText' => 'fooBar',
                'file' => tx_realty_Model_Image::UPLOAD_FOLDER . 'foo.jpg',
                'file.' => [
                    'width' => '40c',
                    'height' => '30c',
                ],
            ]
        );

        $this->fixture->render(['showUid' => $realtyObject->getUid()]);
    }

    /**
     * @test
     */
    public function renderForImageInPosition2WithoutSpecificSettingsIsNotAffectedByPosition1Settings()
    {
        $this->imagesConfiguration->set('1.', ['singleImageMaxX' => 40, 'singleImageMaxY' => 30]);

        /** @var tx_realty_Model_RealtyObject $realtyObject */
        $realtyObject = $this->realtyObjectMapper->getNewGhost();
        $realtyObject->addImageRecord('fooBar', 'foo.jpg', 1);
        $realtyObject->addImageRecord('fooBar', 'foo.jpg', 2);
        $this->contentObject->expects(self::at(1))->method('cObjGetSingle')->with(
            'IMAGE',
            [
                'altText' => 'fooBar',
                'titleText' => 'fooBar',
                'file' => tx_realty_Model_Image::UPLOAD_FOLDER . 'foo.jpg',
                'file.' => [
                    'width' => '102c',
                    'height' => '77c',
                ],
            ]
        );

        $this->fixture->render(['showUid' => $realtyObject->getUid()]);
    }

    /**
     * @test
     */
    public function renderForImageInPosition4AndThumbnailSizesSetUsesPositionSpecificThumbnailSizes()
    {
        $this->imagesConfiguration->set('4.', ['singleImageMaxX' => 40, 'singleImageMaxY' => 30]);

        /** @var tx_realty_Model_RealtyObject $realtyObject */
        $realtyObject = $this->realtyObjectMapper->getNewGhost();
        $realtyObject->addImageRecord('fooBar', 'foo.jpg', 4);
        $this->contentObject->expects(self::at(0))->method('cObjGetSingle')->with(
            'IMAGE',
            [
                'altText' => 'fooBar',
                'titleText' => 'fooBar',
                'file' => tx_realty_Model_Image::UPLOAD_FOLDER . 'foo.jpg',
                'file.' => [
                    'width' => '40c',
                    'height' => '30c',
                ],
            ]
        );

        $this->fixture->render(['showUid' => $realtyObject->getUid()]);
    }

    /**
     * @test
     */
    public function renderForImageInPosition1AndNoSizesSetAndLightboxEnabledSetUsesGlobalThumbnailSizes()
    {
        $this->configuration->setAsBoolean('enableLightbox', true);

        /** @var tx_realty_Model_RealtyObject $realtyObject */
        $realtyObject = $this->realtyObjectMapper->getNewGhost();
        $realtyObject->addImageRecord('fooBar', 'foo.jpg', 1);
        $this->contentObject->expects(self::at(1))->method('cObjGetSingle')->with(
            'IMAGE',
            [
                'altText' => 'fooBar',
                'titleText' => 'fooBar',
                'file' => tx_realty_Model_Image::UPLOAD_FOLDER . 'foo.jpg',
                'file.' => [
                    'maxW' => 1024,
                    'maxH' => 768,
                ],
            ]
        );

        $this->fixture->render(['showUid' => $realtyObject->getUid()]);
    }

    /**
     * @test
     */
    public function renderForImageInPosition1AndThumbnailSizesSetAndLightboxEnabledSetUsesThumbnailSizesForPosition1()
    {
        $this->configuration->setAsBoolean('enableLightbox', true);
        $this->imagesConfiguration->set('1.', ['lightboxImageWidthMax' => 400, 'lightboxImageHeightMax' => 300]);

        /** @var tx_realty_Model_RealtyObject $realtyObject */
        $realtyObject = $this->realtyObjectMapper->getNewGhost();
        $realtyObject->addImageRecord('fooBar', 'foo.jpg', 1);
        $this->contentObject->expects(self::at(1))->method('cObjGetSingle')->with(
            'IMAGE',
            [
                'altText' => 'fooBar',
                'titleText' => 'fooBar',
                'file' => tx_realty_Model_Image::UPLOAD_FOLDER . 'foo.jpg',
                'file.' => [
                    'maxW' => 400,
                    'maxH' => 300,
                ],
            ]
        );

        $this->fixture->render(['showUid' => $realtyObject->getUid()]);
    }

    /**
     * @test
     */
    public function renderForPosition1ImageAndLightboxGloballyDisabledNotAddsLightboxAttributeToImage()
    {
        /** @var tx_realty_Model_RealtyObject $realtyObject */
        $realtyObject = $this->realtyObjectMapper->getNewGhost();
        $realtyObject->addImageRecord('foo', 'foo.jpg', 1);

        self::assertNotContains(
            'data-lightbox',
            $this->fixture->render(['showUid' => $realtyObject->getUid()])
        );
    }

    /**
     * @test
     */
    public function renderForPosition1ImageAndLightboxGloballyEnabledAddsLightboxAttributeToImage()
    {
        $this->configuration->setAsBoolean('enableLightbox', true);

        /** @var tx_realty_Model_RealtyObject $realtyObject */
        $realtyObject = $this->realtyObjectMapper->getNewGhost();
        $realtyObject->addImageRecord('foo', 'foo.jpg', 1);

        self::assertContains(
            'data-lightbox="objectGallery_1"',
            $this->fixture->render(['showUid' => $realtyObject->getUid()])
        );
    }

    /**
     * @test
     */
    public function renderForPosition1ImageAndLightboxGloballyDisabledAndLocallyEnabledAddsLightboxAttributeToImage()
    {
        $this->imagesConfiguration->set('1.', ['enableLightbox' => true]);

        /** @var tx_realty_Model_RealtyObject $realtyObject */
        $realtyObject = $this->realtyObjectMapper->getNewGhost();
        $realtyObject->addImageRecord('foo', 'foo.jpg', 1);

        self::assertContains(
            'data-lightbox="objectGallery_1"',
            $this->fixture->render(['showUid' => $realtyObject->getUid()])
        );
    }

    /**
     * @test
     */
    public function renderForPosition1ImageAndLightboxGloballyEnabledAndLocallyDisabledNotAddsLightboxAttributeToImage()
    {
        $this->configuration->setAsBoolean('enableLightbox', true);
        $this->imagesConfiguration->set('1.', ['enableLightbox' => false]);

        /** @var tx_realty_Model_RealtyObject $realtyObject */
        $realtyObject = $this->realtyObjectMapper->getNewGhost();
        $realtyObject->addImageRecord('foo', 'foo.jpg', 1);

        self::assertNotContains(
            'data-lightbox',
            $this->fixture->render(['showUid' => $realtyObject->getUid()])
        );
    }

    /**
     * @test
     */
    public function renderForImagePositionsTwoOneZeroRendersInZeroOneTwoOrder()
    {
        /** @var tx_realty_Model_RealtyObject $realtyObject */
        $realtyObject = $this->realtyObjectMapper->getNewGhost();
        $realtyObject->addImageRecord('2', '2.jpg', 2);
        $realtyObject->addImageRecord('1', '1.jpg', 1);
        $realtyObject->addImageRecord('0', '0.jpg', 0);

        $this->contentObject->expects(self::at(0))->method('cObjGetSingle')->with(
            'IMAGE',
            [
                'altText' => '0',
                'titleText' => '0',
                'file' => tx_realty_Model_Image::UPLOAD_FOLDER . '0.jpg',
                'file.' => [
                    'width' => '102c',
                    'height' => '77c',
                ],
            ]
        );
        $this->contentObject->expects(self::at(1))->method('cObjGetSingle')->with(
            'IMAGE',
            [
                'altText' => '1',
                'titleText' => '1',
                'file' => tx_realty_Model_Image::UPLOAD_FOLDER . '1.jpg',
                'file.' => [
                    'width' => '102c',
                    'height' => '77c',
                ],
            ]
        );
        $this->contentObject->expects(self::at(2))->method('cObjGetSingle')->with(
            'IMAGE',
            [
                'altText' => '2',
                'titleText' => '2',
                'file' => tx_realty_Model_Image::UPLOAD_FOLDER . '2.jpg',
                'file.' => [
                    'width' => '102c',
                    'height' => '77c',
                ],
            ]
        );

        $this->fixture->render(['showUid' => $realtyObject->getUid()]);
    }

    /**
     * @test
     */
    public function renderForOnlyPositionZeroImageHidesPositionOneToFourSubparts()
    {
        /** @var tx_realty_Model_RealtyObject $realtyObject */
        $realtyObject = $this->realtyObjectMapper->getNewGhost();
        $realtyObject->addImageRecord('foo', 'foo.jpg', 0);

        $result = $this->fixture->render(
            ['showUid' => $realtyObject->getUid()]
        );

        self::assertNotContains(
            'class="images_position_1',
            $result
        );
        self::assertNotContains(
            'class="images_position_2',
            $result
        );
        self::assertNotContains(
            'class="images_position_3',
            $result
        );
        self::assertNotContains(
            'class="images_position_4',
            $result
        );
    }

    /**
     * @test
     */
    public function renderForOnlyPositionOneImageHidesDefaultSubpart()
    {
        /** @var tx_realty_Model_RealtyObject $realtyObject */
        $realtyObject = $this->realtyObjectMapper->getNewGhost();
        $realtyObject->addImageRecord('foo', 'foo.jpg', 1);

        $result = $this->fixture->render(
            ['showUid' => $realtyObject->getUid()]
        );

        self::assertNotContains(
            'class="item',
            $result
        );
    }

    /**
     * @test
     */
    public function renderForOnlyPositionOneImageHidesSubpartsTwoToFour()
    {
        /** @var tx_realty_Model_RealtyObject $realtyObject */
        $realtyObject = $this->realtyObjectMapper->getNewGhost();
        $realtyObject->addImageRecord('foo', 'foo.jpg', 1);

        $result = $this->fixture->render(
            ['showUid' => $realtyObject->getUid()]
        );

        self::assertNotContains(
            'class="images_position_2',
            $result
        );
        self::assertNotContains(
            'class="images_position_3',
            $result
        );
        self::assertNotContains(
            'class="images_position_4',
            $result
        );
    }

    /*
     * Tests concerning the separate thumbnails
     */

    /**
     * @test
     */
    public function renderWithSeparateThumbnailUsesThumbnailImage()
    {
        /** @var tx_realty_Model_RealtyObject $realtyObject */
        $realtyObject = $this->realtyObjectMapper->getNewGhost();
        $realtyObject->addImageRecord('fooBar', 'foo.jpg', 0, 'thumbnail.jpg');
        $this->contentObject->expects(self::at(0))->method('cObjGetSingle')->with(
            'IMAGE',
            [
                'altText' => 'fooBar',
                'titleText' => 'fooBar',
                'file' => tx_realty_Model_Image::UPLOAD_FOLDER . 'thumbnail.jpg',
                'file.' => [
                    'width' => '102c',
                    'height' => '77c',
                ],
            ]
        );

        $this->fixture->render(['showUid' => $realtyObject->getUid()]);
    }
}
