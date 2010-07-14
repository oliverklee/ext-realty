<?php
/***************************************************************
* Copyright notice
*
* (c) 2009-2010 Oliver Klee (typo3-coding@oliverklee.de)
* All rights reserved
*
* This script is part of the TYPO3 project. The TYPO3 project is
* free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation; either version 2 of the License, or
* (at your option) any later version.
*
* The GNU General Public License can be found at
* http://www.gnu.org/copyleft/gpl.html.
*
* This script is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

require_once(t3lib_extMgm::extPath('oelib') . 'class.tx_oelib_Autoloader.php');

/**
 * Testcase for the tx_realty_lightboxIncluder class in the "realty" extension.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class tx_realty_lightboxIncluder_testcase extends tx_phpunit_testcase {
	public function setUp() {
		$GLOBALS['TSFE'] = $this->getMock('tslib_fe', array(), array(), '', FALSE);
	}

	public function tearDown() {
		$GLOBALS['TSFE'] = null;
	}


	///////////////////////////////////////////
	// Tests concerning includeMainJavaScript
	///////////////////////////////////////////

	/**
	 * @test
	 */
	public function includeMainJavaScriptIncludesMainFile() {
		tx_realty_lightboxIncluder::includeMainJavaScript();

		$additionalHeaderData = $GLOBALS['TSFE']->additionalHeaderData;
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

		$additionalHeaderData = $GLOBALS['TSFE']->additionalHeaderData;
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
	public function includeLightboxFilesIncludesPrototype() {
		tx_realty_lightboxIncluder::includeLightboxFiles();

		$additionalHeaderData = $GLOBALS['TSFE']->additionalHeaderData;
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
	public function includeLightboxFilesIncludesScriptaculous() {
		tx_realty_lightboxIncluder::includeLightboxFiles();

		$additionalHeaderData = $GLOBALS['TSFE']->additionalHeaderData;
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
	public function includeLightboxFilesIncludesLightbox() {
		tx_realty_lightboxIncluder::includeLightboxFiles();

		$additionalHeaderData = $GLOBALS['TSFE']->additionalHeaderData;
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
	public function includeLightboxFilesIncludesLightboxConfiguration() {
		tx_realty_lightboxIncluder::includeLightboxFiles();

		$additionalHeaderData = $GLOBALS['TSFE']->additionalHeaderData;
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


	//////////////////////////////////////
	// Tests concerning includePrototype
	//////////////////////////////////////

	/**
	 * @test
	 */
	public function includePrototypeIncludesPrototype() {
		tx_realty_lightboxIncluder::includePrototype();

		$additionalHeaderData = $GLOBALS['TSFE']->additionalHeaderData;
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
?>