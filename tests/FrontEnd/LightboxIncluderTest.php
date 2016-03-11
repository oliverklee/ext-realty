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
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class tx_realty_FrontEnd_LightboxIncluderTest extends Tx_Phpunit_TestCase
{
    protected function setUp()
    {
        $GLOBALS['TSFE'] = $this->getMock(TypoScriptFrontendController::class, array(), array(), '', false);

        $configuration = new Tx_Oelib_Configuration();
        $configuration->setData(array(
            'includeJavaScriptLibraries' => 'prototype, scriptaculous, lightbox'
        ));
        Tx_Oelib_ConfigurationRegistry::getInstance()->set(
            'plugin.tx_realty_pi1', $configuration
        );
    }

    protected function tearDown()
    {
        $GLOBALS['TSFE'] = null;
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

    ///////////////////////////////////////////
    // Tests concerning includeMainJavaScript
    ///////////////////////////////////////////

    /**
     * @test
     */
    public function includeMainJavaScriptIncludesMainFile()
    {
        tx_realty_lightboxIncluder::includeMainJavaScript();

        $additionalHeaderData = $this->getFrontEndController()->additionalHeaderData;
        self::assertTrue(
            isset($additionalHeaderData[tx_realty_lightboxIncluder::PREFIX_ID])
        );
        self::assertContains(
            'tx_realty_pi1.js',
            $additionalHeaderData[tx_realty_lightboxIncluder::PREFIX_ID]
        );
    }

    //////////////////////////////////////////
    // Tests concerning includeLightboxFiles
    //////////////////////////////////////////

    /**
     * @test
     */
    public function includeLightboxFilesIncludesLightboxCss()
    {
        tx_realty_lightboxIncluder::includeLightboxFiles();

        $additionalHeaderData = $this->getFrontEndController()->additionalHeaderData;
        self::assertTrue(
            isset($additionalHeaderData[
                tx_realty_lightboxIncluder::PREFIX_ID . '_lightboxcss'
            ])
        );
        self::assertContains(
            'lightbox.css',
            $additionalHeaderData[
                tx_realty_lightboxIncluder::PREFIX_ID . '_lightboxcss'
            ]
        );
    }

    /**
     * @test
     */
    public function includeLightboxFilesForLightboxDisabledNotIncludesLightboxCss()
    {
        Tx_Oelib_ConfigurationRegistry::get('plugin.tx_realty_pi1')
            ->setAsString('includeJavaScriptLibraries', 'prototype, scriptaculous');

        tx_realty_lightboxIncluder::includeLightboxFiles();

        $additionalHeaderData = $this->getFrontEndController()->additionalHeaderData;
        self::assertFalse(
            isset($additionalHeaderData[
                tx_realty_lightboxIncluder::PREFIX_ID . '_lightboxcss'
            ])
        );
    }

    /**
     * @test
     */
    public function includeLightboxFilesIncludesPrototype()
    {
        tx_realty_lightboxIncluder::includeLightboxFiles();

        $additionalHeaderData = $this->getFrontEndController()->additionalHeaderData;
        self::assertTrue(
            isset($additionalHeaderData[
                tx_realty_lightboxIncluder::PREFIX_ID . '_prototype'
            ])
        );
        self::assertContains(
            'prototype.js',
            $additionalHeaderData[
                tx_realty_lightboxIncluder::PREFIX_ID . '_prototype'
            ]
        );
    }

    /**
     * @test
     */
    public function includeLightboxFilesForPrototypeDisabledNotIncludesPrototype()
    {
        Tx_Oelib_ConfigurationRegistry::get('plugin.tx_realty_pi1')
            ->setAsString('includeJavaScriptLibraries', 'scriptaculous, lightbox');

        tx_realty_lightboxIncluder::includeLightboxFiles();

        $additionalHeaderData = $this->getFrontEndController()->additionalHeaderData;
        self::assertFalse(
            isset($additionalHeaderData[
                tx_realty_lightboxIncluder::PREFIX_ID . '_prototype'
            ])
        );
    }

    /**
     * @test
     */
    public function includeLightboxFilesIncludesScriptaculous()
    {
        tx_realty_lightboxIncluder::includeLightboxFiles();

        $additionalHeaderData = $this->getFrontEndController()->additionalHeaderData;
        self::assertTrue(
            isset($additionalHeaderData[
                tx_realty_lightboxIncluder::PREFIX_ID . '_scriptaculous'
            ])
        );
        self::assertContains(
            'scriptaculous.js',
            $additionalHeaderData[
                tx_realty_lightboxIncluder::PREFIX_ID . '_scriptaculous'
            ]
        );
    }

    /**
     * @test
     */
    public function includeLightboxFilesForScriptaculousDisabledNotIncludesScriptaculous()
    {
        Tx_Oelib_ConfigurationRegistry::get('plugin.tx_realty_pi1')
            ->setAsString('includeJavaScriptLibraries', 'prototype, lightbox');

        tx_realty_lightboxIncluder::includeLightboxFiles();

        $additionalHeaderData = $this->getFrontEndController()->additionalHeaderData;
        self::assertFalse(
            isset($additionalHeaderData[
                tx_realty_lightboxIncluder::PREFIX_ID . '_scriptaculous'
            ])
        );
    }

    /**
     * @test
     */
    public function includeLightboxFilesIncludesLightbox()
    {
        tx_realty_lightboxIncluder::includeLightboxFiles();

        $additionalHeaderData = $this->getFrontEndController()->additionalHeaderData;
        self::assertTrue(
            isset($additionalHeaderData[
                tx_realty_lightboxIncluder::PREFIX_ID . '_lightbox'
            ])
        );
        self::assertContains(
            'lightbox.js',
            $additionalHeaderData[
                tx_realty_lightboxIncluder::PREFIX_ID . '_lightbox'
            ]
        );
    }

    /**
     * @test
     */
    public function includeLightboxFilesForLightboxDisabledNotIncludesLightbox()
    {
        Tx_Oelib_ConfigurationRegistry::get('plugin.tx_realty_pi1')
            ->setAsString('includeJavaScriptLibraries', 'prototype, scriptaculous');

        tx_realty_lightboxIncluder::includeLightboxFiles();

        $additionalHeaderData = $this->getFrontEndController()->additionalHeaderData;
        self::assertFalse(
            isset($additionalHeaderData[
                tx_realty_lightboxIncluder::PREFIX_ID . '_lightbox'
            ])
        );
    }

    /**
     * @test
     */
    public function includeLightboxFilesIncludesLightboxConfiguration()
    {
        tx_realty_lightboxIncluder::includeLightboxFiles();

        $additionalHeaderData = $this->getFrontEndController()->additionalHeaderData;
        self::assertTrue(
            isset($additionalHeaderData[
                tx_realty_lightboxIncluder::PREFIX_ID . '_lightbox_config'
            ])
        );
        self::assertContains(
            'LightboxOptions',
            $additionalHeaderData[
                tx_realty_lightboxIncluder::PREFIX_ID . '_lightbox_config'
            ]
        );
    }

    /**
     * @test
     */
    public function includeLightboxFilesForLightboxDisabledNotIncludesLightboxConfiguration()
    {
        Tx_Oelib_ConfigurationRegistry::get('plugin.tx_realty_pi1')
            ->setAsString('includeJavaScriptLibraries', 'prototype, scriptaculous');

        tx_realty_lightboxIncluder::includeLightboxFiles();

        $additionalHeaderData = $this->getFrontEndController()->additionalHeaderData;
        self::assertFalse(
            isset($additionalHeaderData[
                tx_realty_lightboxIncluder::PREFIX_ID . '_lightbox_config'
            ])
        );
    }

    //////////////////////////////////////
    // Tests concerning includePrototype
    //////////////////////////////////////

    /**
     * @test
     */
    public function includePrototypeIncludesPrototype()
    {
        tx_realty_lightboxIncluder::includePrototype();

        $additionalHeaderData = $this->getFrontEndController()->additionalHeaderData;
        self::assertTrue(
            isset($additionalHeaderData[
                tx_realty_lightboxIncluder::PREFIX_ID . '_prototype'
            ])
        );
        self::assertContains(
            'prototype.js',
            $additionalHeaderData[
                tx_realty_lightboxIncluder::PREFIX_ID . '_prototype'
            ]
        );
    }

    /**
     * @test
     */
    public function includePrototypeForLightboxPrototypeDisabledIncludesPrototype()
    {
        Tx_Oelib_ConfigurationRegistry::get('plugin.tx_realty_pi1')
            ->setAsString('includeJavaScriptLibraries', 'scriptaculous, lightbox');

        tx_realty_lightboxIncluder::includePrototype();

        $additionalHeaderData = $this->getFrontEndController()->additionalHeaderData;
        self::assertTrue(
            isset($additionalHeaderData[
                tx_realty_lightboxIncluder::PREFIX_ID . '_prototype'
            ])
        );
        self::assertContains(
            'prototype.js',
            $additionalHeaderData[
                tx_realty_lightboxIncluder::PREFIX_ID . '_prototype'
            ]
        );
    }
}
