<?php
/***************************************************************
* Copyright notice
*
* (c) 2009-2010 Saskia Metzler <saskia@merlin.owl.de>
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
 * Unit tests for the tx_realty_pi1_ImageThumbnailsView class in the "realty"
 * extension.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 */
class tx_realty_FrontEnd_ImageThumbnailsViewTest extends tx_phpunit_testcase {
	/**
	 * @var tx_realty_pi1_ImageThumbnailsView
	 */
	private $fixture;

	/**
	 * @var tx_oelib_testingFramework
	 */
	private $testingFramework;

	public function setUp() {
		$this->testingFramework = new tx_oelib_testingFramework('tx_realty');
		$this->testingFramework->createFakeFrontEnd();

		$this->configuration = new tx_oelib_Configuration();
		$this->configuration->setData(array(
			'includeJavaScriptLibraries' => 'prototype, scriptaculous, lightbox',
		));
		tx_oelib_ConfigurationRegistry::getInstance()->set(
			'plugin.tx_realty_pi1', $this->configuration
		);

		$this->fixture = new tx_realty_pi1_ImageThumbnailsView(
			array(
				'templateFile' => 'EXT:realty/pi1/tx_realty_pi1.tpl.htm',
				'enableLightbox' => 1,
			),
			$GLOBALS['TSFE']->cObj
		);
	}

	public function tearDown() {
		$this->testingFramework->cleanUp();

		$this->fixture->__destruct();
		unset($this->fixture, $this->testingFramework);
	}


	//////////////////////////////////////
	// Testing the image thumbnails view
	//////////////////////////////////////

	public function testRenderReturnsEmptyResultForUidOfObjectWithoutImagesProvided() {
		$this->assertEquals(
			'',
			$this->fixture->render(array(
				'showUid' => tx_oelib_MapperRegistry
					::get('tx_realty_Mapper_RealtyObject')->getNewGhost()->getUid()))
		);
	}

	public function testRenderReturnsNoUnreplacedMarkersWhileTheResultIsNonEmpty() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getNewGhost();
		$realtyObject->addImageRecord('foo', 'foo.jpg');

		$this->assertNotContains(
			'###',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	/**
	 * @test
	 */
	public function renderReturnsImageWithRelAttribute() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getNewGhost();
		$realtyObject->addImageRecord('foo', 'foo.jpg');

		$this->assertContains(
			'rel="lightbox[objectGallery]"',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	public function testRenderReturnsHtmlspecialcharedImageCaptionForLightboxStyledGallery() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getNewGhost();
		$realtyObject->addImageRecord('foo</br>', 'foo.jpg');

		$this->assertContains(
			htmlspecialchars('foo</br>'),
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	public function testRenderReturnsNoNonHtmlspecialcharedImageCaptionForLightboxStyledGallery() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getNewGhost();
		$realtyObject->addImageRecord('foo</br>', 'foo.jpg');

		$this->assertNotContains(
			'foo</br>',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	/**
	 * @test
	 */
	public function renderIncludesLightboxConfiguration() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getNewGhost();
		$realtyObject->addImageRecord('foo', 'foo.jpg');

		$this->fixture->render(array('showUid' => $realtyObject->getUid()));

		$this->assertTrue(
			array_key_exists(
				'tx_realty_pi1_lightbox_config', $GLOBALS['TSFE']->additionalHeaderData
			)
		);
	}

	/**
	 * @test
	 */
	public function renderIncludesLightboxJsFile() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getNewGhost();
		$realtyObject->addImageRecord('foo', 'foo.jpg');

		$this->fixture->render(array('showUid' => $realtyObject->getUid()));

		$this->assertTrue(
			in_array(
				'<script type="text/javascript" src="../typo3conf/ext/realty' .
					'/pi1/contrib/lightbox.js" ></script>',
				$GLOBALS['TSFE']->additionalHeaderData
			)
		);
	}

	/**
	 * @test
	 */
	public function renderIncludesLightboxCssFile() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getNewGhost();
		$realtyObject->addImageRecord('foo', 'foo.jpg');

		$this->fixture->render(array('showUid' => $realtyObject->getUid()));

		$this->assertTrue(
			in_array(
				'<link rel="stylesheet" type="text/css" href="..' .
					'/typo3conf/ext/realty/pi1/contrib/lightbox.css" />',
				$GLOBALS['TSFE']->additionalHeaderData
			)
		);
	}

	/**
	 * @test
	 */
	public function renderIncludesPrototypeJsFile() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getNewGhost();
		$realtyObject->addImageRecord('foo', 'foo.jpg');

		$this->fixture->render(array('showUid' => $realtyObject->getUid()));

		$this->assertTrue(
			in_array(
				'<script type="text/javascript" src="../typo3conf/ext/realty' .
					'/pi1/contrib/prototype.js"></script>',
				$GLOBALS['TSFE']->additionalHeaderData
			)
		);
	}

	/**
	 * @test
	 */
	public function renderIncludesScriptaculousJsFile() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getNewGhost();
		$realtyObject->addImageRecord('foo', 'foo.jpg');

		$this->fixture->render(array('showUid' => $realtyObject->getUid()));

		$this->assertTrue(
			in_array(
				'<script type="text/javascript"src="../typo3conf/ext/realty/pi1' .
					'/contrib/scriptaculous.js?load=effects,builder"></script>',
				$GLOBALS['TSFE']->additionalHeaderData
			)
		);
	}

	/**
	 * @test
	 */
	public function renderForDisabledLightboxIncludesLightboxJsFile() {
		$this->fixture->setConfigurationValue('enableLightbox' , 0);
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getNewGhost();
		$realtyObject->addImageRecord('foo', 'foo.jpg');

		$this->fixture->render(array('showUid' => $realtyObject->getUid()));

		$this->assertTrue(
			in_array(
				'<script type="text/javascript" src="../typo3conf/ext/realty' .
					'/pi1/contrib/lightbox.js" ></script>',
				$GLOBALS['TSFE']->additionalHeaderData
			)
		);
	}

	/**
	 * @test
	 */
	public function renderForDisabledLightboxIncludesLightboxCssFile() {
		$this->fixture->setConfigurationValue('enableLightbox' , 0);

		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getNewGhost();
		$realtyObject->addImageRecord('foo', 'foo.jpg');

		$this->fixture->render(array('showUid' => $realtyObject->getUid()));

		$this->assertTrue(
			in_array(
				'<link rel="stylesheet" type="text/css" href="..' .
					'/typo3conf/ext/realty/pi1/contrib/lightbox.css" />',
				$GLOBALS['TSFE']->additionalHeaderData
			)
		);
	}

	/**
	 * @test
	 */
	public function renderForDisabledLightboxNotAddsLightboxAttributeToImage() {
		$this->fixture->setConfigurationValue('enableLightbox' , 0);

		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getNewGhost();
		$realtyObject->addImageRecord('foo', 'foo.jpg');


		$this->assertNotContains(
			'rel="lightbox',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	/**
	 * @test
	 */
	public function renderForDisabledLightboxShowsImage() {
		$this->fixture->setConfigurationValue('enableLightbox' , 0);

		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getNewGhost();
		$realtyObject->addImageRecord('fooBar', 'foo.jpg');

		$this->assertContains(
			'fooBar',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	/**
	 * @test
	 */
	public function renderForDisabledLightboxNotLinksImage() {
		$this->fixture->setConfigurationValue('enableLightbox' , 0);

		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getNewGhost();
		$realtyObject->addImageRecord('fooBar', 'foo.jpg');

		$this->assertNotContains(
			'<a href',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}
}
?>