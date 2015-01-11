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

/**
 * Test case.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class tx_realty_FrontEnd_LightboxIncluderTest extends Tx_Phpunit_TestCase {
	protected function setUp() {
		$GLOBALS['TSFE'] = $this->getMock('tslib_fe', array(), array(), '', FALSE);

		$configuration = new tx_oelib_Configuration();
		$configuration->setData(array(
			'includeJavaScriptLibraries' => 'prototype, scriptaculous, lightbox'
		));
		tx_oelib_ConfigurationRegistry::getInstance()->set(
			'plugin.tx_realty_pi1', $configuration
		);
	}

	protected function tearDown() {
		$GLOBALS['TSFE'] = NULL;
	}

	/**
	 * Returns the current front-end instance.
	 *
	 * @return tslib_fe
	 */
	private function getFrontEndController() {
		return $GLOBALS['TSFE'];
	}

	///////////////////////////////////////////
	// Tests concerning includeMainJavaScript
	///////////////////////////////////////////

	/**
	 * @test
	 */
	public function includeMainJavaScriptIncludesMainFile() {
		tx_realty_lightboxIncluder::includeMainJavaScript();

		$additionalHeaderData = $this->getFrontEndController()->additionalHeaderData;
		$this->assertTrue(
			isset($additionalHeaderData[tx_realty_lightboxIncluder::PREFIX_ID])
		);
		$this->assertContains(
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
	public function includeLightboxFilesIncludesLightboxCss() {
		tx_realty_lightboxIncluder::includeLightboxFiles();

		$additionalHeaderData = $this->getFrontEndController()->additionalHeaderData;
		$this->assertTrue(
			isset($additionalHeaderData[
				tx_realty_lightboxIncluder::PREFIX_ID . '_lightboxcss'
			])
		);
		$this->assertContains(
			'lightbox.css',
			$additionalHeaderData[
				tx_realty_lightboxIncluder::PREFIX_ID . '_lightboxcss'
			]
		);
	}

	/**
	 * @test
	 */
	public function includeLightboxFilesForLightboxDisabledNotIncludesLightboxCss() {
		tx_oelib_ConfigurationRegistry::get('plugin.tx_realty_pi1')
			->setAsString('includeJavaScriptLibraries', 'prototype, scriptaculous');

		tx_realty_lightboxIncluder::includeLightboxFiles();

		$additionalHeaderData = $this->getFrontEndController()->additionalHeaderData;
		$this->assertFalse(
			isset($additionalHeaderData[
				tx_realty_lightboxIncluder::PREFIX_ID . '_lightboxcss'
			])
		);
	}

	/**
	 * @test
	 */
	public function includeLightboxFilesIncludesPrototype() {
		tx_realty_lightboxIncluder::includeLightboxFiles();

		$additionalHeaderData = $this->getFrontEndController()->additionalHeaderData;
		$this->assertTrue(
			isset($additionalHeaderData[
				tx_realty_lightboxIncluder::PREFIX_ID . '_prototype'
			])
		);
		$this->assertContains(
			'prototype.js',
			$additionalHeaderData[
				tx_realty_lightboxIncluder::PREFIX_ID . '_prototype'
			]
		);
	}

	/**
	 * @test
	 */
	public function includeLightboxFilesForPrototypeDisabledNotIncludesPrototype() {
		tx_oelib_ConfigurationRegistry::get('plugin.tx_realty_pi1')
			->setAsString('includeJavaScriptLibraries', 'scriptaculous, lightbox');

		tx_realty_lightboxIncluder::includeLightboxFiles();

		$additionalHeaderData = $this->getFrontEndController()->additionalHeaderData;
		$this->assertFalse(
			isset($additionalHeaderData[
				tx_realty_lightboxIncluder::PREFIX_ID . '_prototype'
			])
		);
	}

	/**
	 * @test
	 */
	public function includeLightboxFilesIncludesScriptaculous() {
		tx_realty_lightboxIncluder::includeLightboxFiles();

		$additionalHeaderData = $this->getFrontEndController()->additionalHeaderData;
		$this->assertTrue(
			isset($additionalHeaderData[
				tx_realty_lightboxIncluder::PREFIX_ID . '_scriptaculous'
			])
		);
		$this->assertContains(
			'scriptaculous.js',
			$additionalHeaderData[
				tx_realty_lightboxIncluder::PREFIX_ID . '_scriptaculous'
			]
		);
	}

	/**
	 * @test
	 */
	public function includeLightboxFilesForScriptaculousDisabledNotIncludesScriptaculous() {
		tx_oelib_ConfigurationRegistry::get('plugin.tx_realty_pi1')
			->setAsString('includeJavaScriptLibraries', 'prototype, lightbox');

		tx_realty_lightboxIncluder::includeLightboxFiles();

		$additionalHeaderData = $this->getFrontEndController()->additionalHeaderData;
		$this->assertFalse(
			isset($additionalHeaderData[
				tx_realty_lightboxIncluder::PREFIX_ID . '_scriptaculous'
			])
		);
	}

	/**
	 * @test
	 */
	public function includeLightboxFilesIncludesLightbox() {
		tx_realty_lightboxIncluder::includeLightboxFiles();

		$additionalHeaderData = $this->getFrontEndController()->additionalHeaderData;
		$this->assertTrue(
			isset($additionalHeaderData[
				tx_realty_lightboxIncluder::PREFIX_ID . '_lightbox'
			])
		);
		$this->assertContains(
			'lightbox.js',
			$additionalHeaderData[
				tx_realty_lightboxIncluder::PREFIX_ID . '_lightbox'
			]
		);
	}

	/**
	 * @test
	 */
	public function includeLightboxFilesForLightboxDisabledNotIncludesLightbox() {
		tx_oelib_ConfigurationRegistry::get('plugin.tx_realty_pi1')
			->setAsString('includeJavaScriptLibraries', 'prototype, scriptaculous');

		tx_realty_lightboxIncluder::includeLightboxFiles();

		$additionalHeaderData = $this->getFrontEndController()->additionalHeaderData;
		$this->assertFalse(
			isset($additionalHeaderData[
				tx_realty_lightboxIncluder::PREFIX_ID . '_lightbox'
			])
		);
	}

	/**
	 * @test
	 */
	public function includeLightboxFilesIncludesLightboxConfiguration() {
		tx_realty_lightboxIncluder::includeLightboxFiles();

		$additionalHeaderData = $this->getFrontEndController()->additionalHeaderData;
		$this->assertTrue(
			isset($additionalHeaderData[
				tx_realty_lightboxIncluder::PREFIX_ID . '_lightbox_config'
			])
		);
		$this->assertContains(
			'LightboxOptions',
			$additionalHeaderData[
				tx_realty_lightboxIncluder::PREFIX_ID . '_lightbox_config'
			]
		);
	}

	/**
	 * @test
	 */
	public function includeLightboxFilesForLightboxDisabledNotIncludesLightboxConfiguration() {
		tx_oelib_ConfigurationRegistry::get('plugin.tx_realty_pi1')
			->setAsString('includeJavaScriptLibraries', 'prototype, scriptaculous');

		tx_realty_lightboxIncluder::includeLightboxFiles();

		$additionalHeaderData = $this->getFrontEndController()->additionalHeaderData;
		$this->assertFalse(
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
	public function includePrototypeIncludesPrototype() {
		tx_realty_lightboxIncluder::includePrototype();

		$additionalHeaderData = $this->getFrontEndController()->additionalHeaderData;
		$this->assertTrue(
			isset($additionalHeaderData[
				tx_realty_lightboxIncluder::PREFIX_ID . '_prototype'
			])
		);
		$this->assertContains(
			'prototype.js',
			$additionalHeaderData[
				tx_realty_lightboxIncluder::PREFIX_ID . '_prototype'
			]
		);
	}

	/**
	 * @test
	 */
	public function includePrototypeForLightboxPrototypeDisabledIncludesPrototype() {
		tx_oelib_ConfigurationRegistry::get('plugin.tx_realty_pi1')
			->setAsString('includeJavaScriptLibraries', 'scriptaculous, lightbox');

		tx_realty_lightboxIncluder::includePrototype();

		$additionalHeaderData = $this->getFrontEndController()->additionalHeaderData;
		$this->assertTrue(
			isset($additionalHeaderData[
				tx_realty_lightboxIncluder::PREFIX_ID . '_prototype'
			])
		);
		$this->assertContains(
			'prototype.js',
			$additionalHeaderData[
				tx_realty_lightboxIncluder::PREFIX_ID . '_prototype'
			]
		);
	}
}