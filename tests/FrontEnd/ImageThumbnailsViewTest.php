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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Test case.
 *
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 */
class tx_realty_FrontEnd_ImageThumbnailsViewTest extends Tx_Phpunit_TestCase
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
            array('templateFile' => 'EXT:realty/pi1/tx_realty_pi1.tpl.htm'), $this->contentObject
        );

        $configurationRegistry = Tx_Oelib_ConfigurationRegistry::getInstance();
        $this->configuration = new Tx_Oelib_Configuration();
        $this->configuration->setData(array(
            'enableLightbox' => false,
            'singleImageMaxX' => 102,
            'singleImageMaxY' => 77,
            'lightboxImageWidthMax' => 1024,
            'lightboxImageHeightMax' => 768,
            'images.' => array(
                '1.' => array(),
                '2.' => array(),
                '3.' => array(),
                '4.' => array(),
            ),
            'includeJavaScriptLibraries' => 'prototype, scriptaculous, lightbox',
        ));
        $configurationRegistry->set('plugin.tx_realty_pi1', $this->configuration);

        $this->imagesConfiguration = new Tx_Oelib_Configuration();
        $this->imagesConfiguration->setData(array(
            '1.' => array(),
            '2.' => array(),
            '3.' => array(),
            '4.' => array(),
        ));
        $configurationRegistry->set('plugin.tx_realty_pi1.images', $this->imagesConfiguration);

        $this->realtyObjectMapper = Tx_Oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject');
    }

    protected function tearDown()
    {
        $this->testingFramework->cleanUp();
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
     * Testing the image thumbnails view
     */

    /**
     * @test
     */
    public function renderReturnsEmptyResultForUidOfObjectWithoutImagesProvided()
    {
        self::assertEquals(
            '',
            $this->fixture->render(array('showUid' => $this->realtyObjectMapper->getNewGhost()->getUid()))
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
            $this->fixture->render(array('showUid' => $realtyObject->getUid()))
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
            'rel="lightbox[objectGallery]"',
            $this->fixture->render(array('showUid' => $realtyObject->getUid()))
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
            $this->fixture->render(array('showUid' => $realtyObject->getUid()))
        );
    }

    /**
     * @test
     */
    public function renderIncludesLightboxConfiguration()
    {
        /** @var tx_realty_Model_RealtyObject $realtyObject */
        $realtyObject = $this->realtyObjectMapper->getNewGhost();
        $realtyObject->addImageRecord('foo', 'foo.jpg');

        $this->fixture->render(array('showUid' => $realtyObject->getUid()));

        self::assertTrue(
            array_key_exists(
                'tx_realty_pi1_lightbox_config', $this->getFrontEndController()->additionalHeaderData
            )
        );
    }

    /**
     * @test
     */
    public function renderIncludesLightboxJsFile()
    {
        /** @var tx_realty_Model_RealtyObject $realtyObject */
        $realtyObject = $this->realtyObjectMapper->getNewGhost();
        $realtyObject->addImageRecord('foo', 'foo.jpg');

        $this->fixture->render(array('showUid' => $realtyObject->getUid()));

        self::assertTrue(
            in_array(
                '<script type="text/javascript" src="' . GeneralUtility::getIndpEnv('TYPO3_SITE_PATH') . 'typo3conf/ext/realty' .
                    '/pi1/contrib/lightbox.js" ></script>',
                $this->getFrontEndController()->additionalHeaderData
            )
        );
    }

    /**
     * @test
     */
    public function renderIncludesLightboxCssFile()
    {
        /** @var tx_realty_Model_RealtyObject $realtyObject */
        $realtyObject = $this->realtyObjectMapper->getNewGhost();
        $realtyObject->addImageRecord('foo', 'foo.jpg');

        $this->fixture->render(array('showUid' => $realtyObject->getUid()));

        self::assertTrue(
            in_array(
                '<link rel="stylesheet" type="text/css" href="' . GeneralUtility::getIndpEnv('TYPO3_SITE_PATH') .
                    'typo3conf/ext/realty/pi1/contrib/lightbox.css" />',
                $this->getFrontEndController()->additionalHeaderData
            )
        );
    }

    /**
     * @test
     */
    public function renderIncludesPrototypeJsFile()
    {
        /** @var tx_realty_Model_RealtyObject $realtyObject */
        $realtyObject = $this->realtyObjectMapper->getNewGhost();
        $realtyObject->addImageRecord('foo', 'foo.jpg');

        $this->fixture->render(array('showUid' => $realtyObject->getUid()));

        self::assertTrue(
            in_array(
                '<script type="text/javascript" src="' . GeneralUtility::getIndpEnv('TYPO3_SITE_PATH') . 'typo3conf/ext/realty' .
                    '/pi1/contrib/prototype.js"></script>',
                $this->getFrontEndController()->additionalHeaderData
            )
        );
    }

    /**
     * @test
     */
    public function renderIncludesScriptaculousJsFile()
    {
        /** @var tx_realty_Model_RealtyObject $realtyObject */
        $realtyObject = $this->realtyObjectMapper->getNewGhost();
        $realtyObject->addImageRecord('foo', 'foo.jpg');

        $this->fixture->render(array('showUid' => $realtyObject->getUid()));

        self::assertTrue(
            in_array(
                '<script type="text/javascript"src="' . GeneralUtility::getIndpEnv('TYPO3_SITE_PATH') . 'typo3conf/ext/realty/pi1' .
                    '/contrib/scriptaculous.js?load=effects,builder"></script>',
                $this->getFrontEndController()->additionalHeaderData
            )
        );
    }

    /**
     * @test
     */
    public function renderForDisabledLightboxIncludesLightboxJsFile()
    {
        /** @var tx_realty_Model_RealtyObject $realtyObject */
        $realtyObject = $this->realtyObjectMapper->getNewGhost();
        $realtyObject->addImageRecord('foo', 'foo.jpg');

        $this->fixture->render(array('showUid' => $realtyObject->getUid()));

        self::assertTrue(
            in_array(
                '<script type="text/javascript" src="' . GeneralUtility::getIndpEnv('TYPO3_SITE_PATH') . 'typo3conf/ext/realty' .
                    '/pi1/contrib/lightbox.js" ></script>',
                $this->getFrontEndController()->additionalHeaderData
            )
        );
    }

    /**
     * @test
     */
    public function renderForDisabledLightboxIncludesLightboxCssFile()
    {
        /** @var tx_realty_Model_RealtyObject $realtyObject */
        $realtyObject = $this->realtyObjectMapper->getNewGhost();
        $realtyObject->addImageRecord('foo', 'foo.jpg');

        $this->fixture->render(array('showUid' => $realtyObject->getUid()));

        self::assertTrue(
            in_array(
                '<link rel="stylesheet" type="text/css" href="' . GeneralUtility::getIndpEnv('TYPO3_SITE_PATH') .
                    'typo3conf/ext/realty/pi1/contrib/lightbox.css" />',
                $this->getFrontEndController()->additionalHeaderData
            )
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
            'rel="lightbox[objectGallery]"',
            $this->fixture->render(array('showUid' => $realtyObject->getUid()))
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
            $this->fixture->render(array('showUid' => $realtyObject->getUid()))
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

        $this->contentObject->expects(self::at(0))->method('IMAGE')->with(
            array(
                'altText' => 'fooBar',
                'titleText' => 'fooBar',
                'file' => tx_realty_Model_Image::UPLOAD_FOLDER . 'foo.jpg',
                'file.' => array(
                    'width' => '102c',
                    'height' => '77c',
                ),
            )
        );

        $this->fixture->render(array('showUid' => $realtyObject->getUid()));
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

        $this->contentObject->expects(self::at(0))->method('IMAGE')->with(
            array(
                'altText' => 'fooBar',
                'titleText' => 'fooBar',
                'file' => tx_realty_Model_Image::UPLOAD_FOLDER . 'foo.jpg',
                'file.' => array(
                    'width' => '102c',
                    'height' => '77c',
                ),
            )
        );

        $this->fixture->render(array('showUid' => $realtyObject->getUid()));
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

        $this->contentObject->expects(self::at(1))->method('IMAGE')->with(
            array(
                'altText' => 'fooBar',
                'titleText' => 'fooBar',
                'file' => tx_realty_Model_Image::UPLOAD_FOLDER . 'foo.jpg',
                'file.' => array(
                    'maxW' => 1024,
                    'maxH' => 768,
                ),
            )
        );

        $this->fixture->render(array('showUid' => $realtyObject->getUid()));
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

        $this->contentObject->expects(self::at(0))->method('IMAGE')->with(
            array(
                'altText' => 'fooBar',
                'titleText' => 'fooBar',
                'file' => tx_realty_Model_Image::UPLOAD_FOLDER . 'foo.jpg',
                'file.' => array(
                    'width' => '102c',
                    'height' => '77c',
                ),
            )
        );

        $this->fixture->render(array('showUid' => $realtyObject->getUid()));
    }

    /**
     * @test
     */
    public function renderForImageInPosition1AndThumbnailSizesUsesPositionSpecificThumbnailSizes()
    {
        $this->imagesConfiguration->set('1.', array('singleImageMaxX' => 40, 'singleImageMaxY' => 30));

        /** @var tx_realty_Model_RealtyObject $realtyObject */
        $realtyObject = $this->realtyObjectMapper->getNewGhost();
        $realtyObject->addImageRecord('fooBar', 'foo.jpg', 1);

        $this->contentObject->expects(self::at(0))->method('IMAGE')->with(
            array(
                'altText' => 'fooBar',
                'titleText' => 'fooBar',
                'file' => tx_realty_Model_Image::UPLOAD_FOLDER . 'foo.jpg',
                'file.' => array(
                    'width' => '40c',
                    'height' => '30c',
                ),
            )
        );

        $this->fixture->render(array('showUid' => $realtyObject->getUid()));
    }

    /**
     * @test
     */
    public function renderForImageInPosition2WithoutSpecificSettingsIsNotAffectedByPosition1Settings()
    {
        $this->imagesConfiguration->set('1.', array('singleImageMaxX' => 40, 'singleImageMaxY' => 30));

        /** @var tx_realty_Model_RealtyObject $realtyObject */
        $realtyObject = $this->realtyObjectMapper->getNewGhost();
        $realtyObject->addImageRecord('fooBar', 'foo.jpg', 1);
        $realtyObject->addImageRecord('fooBar', 'foo.jpg', 2);

        $this->contentObject->expects(self::at(1))->method('IMAGE')->with(
            array(
                'altText' => 'fooBar',
                'titleText' => 'fooBar',
                'file' => tx_realty_Model_Image::UPLOAD_FOLDER . 'foo.jpg',
                'file.' => array(
                    'width' => '102c',
                    'height' => '77c',
                ),
            )
        );

        $this->fixture->render(array('showUid' => $realtyObject->getUid()));
    }

    /**
     * @test
     */
    public function renderForImageInPosition4AndThumbnailSizesSetUsesPositionSpecificThumbnailSizes()
    {
        $this->imagesConfiguration->set('4.', array('singleImageMaxX' => 40, 'singleImageMaxY' => 30));

        /** @var tx_realty_Model_RealtyObject $realtyObject */
        $realtyObject = $this->realtyObjectMapper->getNewGhost();
        $realtyObject->addImageRecord('fooBar', 'foo.jpg', 4);

        $this->contentObject->expects(self::at(0))->method('IMAGE')->with(
            array(
                'altText' => 'fooBar',
                'titleText' => 'fooBar',
                'file' => tx_realty_Model_Image::UPLOAD_FOLDER . 'foo.jpg',
                'file.' => array(
                    'width' => '40c',
                    'height' => '30c',
                ),
            )
        );

        $this->fixture->render(array('showUid' => $realtyObject->getUid()));
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

        $this->contentObject->expects(self::at(1))->method('IMAGE')->with(
            array(
                'altText' => 'fooBar',
                'titleText' => 'fooBar',
                'file' => tx_realty_Model_Image::UPLOAD_FOLDER . 'foo.jpg',
                'file.' => array(
                    'maxW' => 1024,
                    'maxH' => 768,
                ),
            )
        );

        $this->fixture->render(array('showUid' => $realtyObject->getUid()));
    }

    /**
     * @test
     */
    public function renderForImageInPosition1AndThumbnailSizesSetAndLightboxEnabledSetUsesThumbnailSizesForPosition1()
    {
        $this->configuration->setAsBoolean('enableLightbox', true);
        $this->imagesConfiguration->set('1.', array('lightboxImageWidthMax' => 400, 'lightboxImageHeightMax' => 300));

        /** @var tx_realty_Model_RealtyObject $realtyObject */
        $realtyObject = $this->realtyObjectMapper->getNewGhost();
        $realtyObject->addImageRecord('fooBar', 'foo.jpg', 1);

        $this->contentObject->expects(self::at(1))->method('IMAGE')->with(
            array(
                'altText' => 'fooBar',
                'titleText' => 'fooBar',
                'file' => tx_realty_Model_Image::UPLOAD_FOLDER . 'foo.jpg',
                'file.' => array(
                    'maxW' => 400,
                    'maxH' => 300,
                ),
            )
        );

        $this->fixture->render(array('showUid' => $realtyObject->getUid()));
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
            'rel="lightbox[',
            $this->fixture->render(array('showUid' => $realtyObject->getUid()))
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
            'rel="lightbox[objectGallery_1]"',
            $this->fixture->render(array('showUid' => $realtyObject->getUid()))
        );
    }

    /**
     * @test
     */
    public function renderForPosition1ImageAndLightboxGloballyDisabledAndLocallyEnabledAddsLightboxAttributeToImage()
    {
        $this->imagesConfiguration->set('1.', array('enableLightbox' => true));

        /** @var tx_realty_Model_RealtyObject $realtyObject */
        $realtyObject = $this->realtyObjectMapper->getNewGhost();
        $realtyObject->addImageRecord('foo', 'foo.jpg', 1);

        self::assertContains(
            'rel="lightbox[objectGallery_1]"',
            $this->fixture->render(array('showUid' => $realtyObject->getUid()))
        );
    }

    /**
     * @test
     */
    public function renderForPosition1ImageAndLightboxGloballyEnabledAndLocallyDisabledNotAddsLightboxAttributeToImage()
    {
        $this->configuration->setAsBoolean('enableLightbox', true);
        $this->imagesConfiguration->set('1.', array('enableLightbox' => false));

        /** @var tx_realty_Model_RealtyObject $realtyObject */
        $realtyObject = $this->realtyObjectMapper->getNewGhost();
        $realtyObject->addImageRecord('foo', 'foo.jpg', 1);

        self::assertNotContains(
            'rel="lightbox[',
            $this->fixture->render(array('showUid' => $realtyObject->getUid()))
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

        $this->contentObject->expects(self::at(0))->method('IMAGE')->with(
            array(
                'altText' => '0',
                'titleText' => '0',
                'file' => tx_realty_Model_Image::UPLOAD_FOLDER . '0.jpg',
                'file.' => array(
                    'width' => '102c',
                    'height' => '77c',
                ),
            )
        );
        $this->contentObject->expects(self::at(1))->method('IMAGE')->with(
            array(
                'altText' => '1',
                'titleText' => '1',
                'file' => tx_realty_Model_Image::UPLOAD_FOLDER . '1.jpg',
                'file.' => array(
                    'width' => '102c',
                    'height' => '77c',
                ),
            )
        );
        $this->contentObject->expects(self::at(2))->method('IMAGE')->with(
            array(
                'altText' => '2',
                'titleText' => '2',
                'file' => tx_realty_Model_Image::UPLOAD_FOLDER . '2.jpg',
                'file.' => array(
                    'width' => '102c',
                    'height' => '77c',
                ),
            )
        );

        $this->fixture->render(array('showUid' => $realtyObject->getUid()));
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
            array('showUid' => $realtyObject->getUid())
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
            array('showUid' => $realtyObject->getUid())
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
            array('showUid' => $realtyObject->getUid())
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

        $this->contentObject->expects(self::at(0))->method('IMAGE')->with(
            array(
                'altText' => 'fooBar',
                'titleText' => 'fooBar',
                'file' => tx_realty_Model_Image::UPLOAD_FOLDER . 'thumbnail.jpg',
                'file.' => array(
                    'width' => '102c',
                    'height' => '77c',
                ),
            )
        );

        $this->fixture->render(array('showUid' => $realtyObject->getUid()));
    }
}
