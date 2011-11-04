<?php
/***************************************************************
* Copyright notice
*
* (c) 2009-2011 Saskia Metzler <saskia@merlin.owl.de>
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

	/**
	 * TS Setup configuration for plugin.tx_realty_pi1
	 *
	 * @var tx_oelib_Configuration
	 */
	private $configuration = NULL;

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
			array('templateFile' => 'EXT:realty/pi1/tx_realty_pi1.tpl.htm'),
			$GLOBALS['TSFE']->cObj
		);

		$configurationRegistry = tx_oelib_ConfigurationRegistry::getInstance();

		$this->configuration = new tx_oelib_Configuration();
		$this->configuration->setData(array(
			'enableLightbox' => FALSE,
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
		$configurationRegistry->set(
			'plugin.tx_realty_pi1', $this->configuration
		);

		$imagesConfiguration = new tx_oelib_Configuration();
		$imagesConfiguration->setData(array(
			'1.' => array(),
			'2.' => array(),
			'3.' => array(),
			'4.' => array(),
		));
		$configurationRegistry->set(
			'plugin.tx_realty_pi1.images', $imagesConfiguration
		);
	}

	public function tearDown() {
		$this->testingFramework->cleanUp();

		$this->fixture->__destruct();
		unset($this->fixture, $this->configuration, $this->testingFramework);
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
	public function renderForLightboxEnabledReturnsImageWithRelAttribute() {
		$this->configuration->setAsBoolean('enableLightbox' , TRUE);

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
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getNewGhost();
		$realtyObject->addImageRecord('foo', 'foo.jpg');


		$this->assertNotContains(
			'rel="lightbox[objectGallery]"',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	/**
	 * @test
	 */
	public function renderForDisabledLightboxShowsImage() {
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
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getNewGhost();
		$realtyObject->addImageRecord('fooBar', 'foo.jpg');

		$this->assertNotContains(
			'<a href',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	/**
	 * @test
	 */
	public function renderSizesImageWithThumbnailSize() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getNewGhost();
		$realtyObject->addImageRecord('fooBar', 'foo.jpg');

		$fixture = $this->getMock(
			'tx_realty_pi1_ImageThumbnailsView', array('createRestrictedImage'),
			array(), '', FALSE
		);
		$fixture->expects($this->at(0))->method('createRestrictedImage')->with(
			tx_realty_Model_Image::UPLOAD_FOLDER . 'foo.jpg', 'fooBar', 102, 77
		);

		$fixture->render(array('showUid' => $realtyObject->getUid()));
	}

	/**
	 * @test
	 */
	public function renderForEnabledLightboxSizesImageWithThumbnailSize() {
		$this->configuration->setAsBoolean('enableLightbox' , TRUE);

		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getNewGhost();
		$realtyObject->addImageRecord('fooBar', 'foo.jpg');

		$fixture = $this->getMock(
			'tx_realty_pi1_ImageThumbnailsView', array('createRestrictedImage'),
			array(), '', FALSE
		);
		$fixture->expects($this->at(0))->method('createRestrictedImage')->with(
			tx_realty_Model_Image::UPLOAD_FOLDER . 'foo.jpg', 'fooBar', 102, 77
		);

		$fixture->render(array('showUid' => $realtyObject->getUid()));
	}

	/**
	 * @test
	 */
	public function renderForEnabledLightboxAlsoSizesImageWithLightboxSize() {
		$this->configuration->setAsBoolean('enableLightbox' , TRUE);

		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getNewGhost();
		$realtyObject->addImageRecord('fooBar', 'foo.jpg');

		$fixture = $this->getMock(
			'tx_realty_pi1_ImageThumbnailsView', array('createRestrictedImage'),
			array(), '', FALSE
		);
		$fixture->expects($this->at(1))->method('createRestrictedImage')->with(
			tx_realty_Model_Image::UPLOAD_FOLDER . 'foo.jpg', '', 1024, 768
		);

		$fixture->render(array('showUid' => $realtyObject->getUid()));
	}


	/////////////////////////////////////////
	// Tests concerning the image positions
	/////////////////////////////////////////

	/**
	 * @test
	 */
	public function renderForImageInPosition1AndNoSizesSetUsesGlobalThumbnailSizes() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getNewGhost();
		$realtyObject->addImageRecord('fooBar', 'foo.jpg', 1);

		$fixture = $this->getMock(
			'tx_realty_pi1_ImageThumbnailsView', array('createRestrictedImage'),
			array(), '', FALSE
		);
		$fixture->expects($this->at(0))->method('createRestrictedImage')->with(
			tx_realty_Model_Image::UPLOAD_FOLDER . 'foo.jpg', 'fooBar', 102, 77
		);

		$fixture->render(array('showUid' => $realtyObject->getUid()));
	}

	/**
	 * @test
	 */
	public function renderForImageInPosition1AndThumbnailSizesUsesPositionSpecificThumbnailSizes() {
		tx_oelib_ConfigurationRegistry::get('plugin.tx_realty_pi1.images')
			->set('1.', array('singleImageMaxX' => 40, 'singleImageMaxY' => 30));

		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getNewGhost();
		$realtyObject->addImageRecord('fooBar', 'foo.jpg', 1);

		$fixture = $this->getMock(
			'tx_realty_pi1_ImageThumbnailsView', array('createRestrictedImage'),
			array(), '', FALSE
		);
		$fixture->expects($this->at(0))->method('createRestrictedImage')->with(
			tx_realty_Model_Image::UPLOAD_FOLDER . 'foo.jpg', 'fooBar', 40, 30
		);

		$fixture->render(array('showUid' => $realtyObject->getUid()));
	}

	/**
	 * @test
	 */
	public function renderForImageInPosition2WithoutSpecificSettingsIsNotAffectedByPosition1Settings() {
		tx_oelib_ConfigurationRegistry::get('plugin.tx_realty_pi1.images')
			->set('1.', array('singleImageMaxX' => 40, 'singleImageMaxY' => 30));

		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getNewGhost();
		$realtyObject->addImageRecord('fooBar', 'foo.jpg', 1);
		$realtyObject->addImageRecord('fooBar', 'foo.jpg', 2);

		$fixture = $this->getMock(
			'tx_realty_pi1_ImageThumbnailsView', array('createRestrictedImage'),
			array(), '', FALSE
		);
		$fixture->expects($this->at(1))->method('createRestrictedImage')->with(
			tx_realty_Model_Image::UPLOAD_FOLDER . 'foo.jpg', 'fooBar', 102, 77
		);

		$fixture->render(array('showUid' => $realtyObject->getUid()));
	}

	/**
	 * @test
	 */
	public function renderForImageInPosition4AndThumbnailSizesSetUsesPositionSpecificThumbnailSizes() {
		tx_oelib_ConfigurationRegistry::get('plugin.tx_realty_pi1.images')
			->set('4.', array('singleImageMaxX' => 40, 'singleImageMaxY' => 30));

		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getNewGhost();
		$realtyObject->addImageRecord('fooBar', 'foo.jpg', 4);

		$fixture = $this->getMock(
			'tx_realty_pi1_ImageThumbnailsView', array('createRestrictedImage'),
			array(), '', FALSE
		);
		$fixture->expects($this->at(0))->method('createRestrictedImage')->with(
			tx_realty_Model_Image::UPLOAD_FOLDER . 'foo.jpg', 'fooBar', 40, 30
		);

		$fixture->render(array('showUid' => $realtyObject->getUid()));
	}

	/**
	 * @test
	 */
	public function renderForImageInPosition1AndNoSizesSetAndLightboxEnabledSetUsesGlobalThumbnailSizes() {
		$this->configuration->setAsBoolean('enableLightbox' , TRUE);

		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getNewGhost();
		$realtyObject->addImageRecord('fooBar', 'foo.jpg', 1);

		$fixture = $this->getMock(
			'tx_realty_pi1_ImageThumbnailsView', array('createRestrictedImage'),
			array(), '', FALSE
		);
		$fixture->expects($this->at(1))->method('createRestrictedImage')->with(
			tx_realty_Model_Image::UPLOAD_FOLDER . 'foo.jpg', '', 1024, 768
		);

		$fixture->render(array('showUid' => $realtyObject->getUid()));
	}

	/**
	 * @test
	 */
	public function renderForImageInPosition1AndThumbnailSizesSetAndLightboxEnabledSetUsesThumbnailSizesForPosition1() {
		$this->configuration->setAsBoolean('enableLightbox' , TRUE);
		tx_oelib_ConfigurationRegistry::get('plugin.tx_realty_pi1.images')
			->set(
				'1.',
				array('lightboxImageWidthMax' => 40, 'lightboxImageHeightMax' => 30)
			);

		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getNewGhost();
		$realtyObject->addImageRecord('fooBar', 'foo.jpg', 1);

		$fixture = $this->getMock(
			'tx_realty_pi1_ImageThumbnailsView', array('createRestrictedImage'),
			array(), '', FALSE
		);
		$fixture->expects($this->at(1))->method('createRestrictedImage')->with(
			tx_realty_Model_Image::UPLOAD_FOLDER . 'foo.jpg', '', 40, 30
		);

		$fixture->render(array('showUid' => $realtyObject->getUid()));
	}

	/**
	 * @test
	 */
	public function renderForPosition1ImageAndLightboxGloballyDisabledNotAddsLightboxAttributeToImage() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getNewGhost();
		$realtyObject->addImageRecord('foo', 'foo.jpg', 1);

		$this->assertNotContains(
			'rel="lightbox[',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	/**
	 * @test
	 */
	public function renderForPosition1ImageAndLightboxGloballyEnabledAddsLightboxAttributeToImage() {
		$this->configuration->setAsBoolean('enableLightbox' , TRUE);

		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getNewGhost();
		$realtyObject->addImageRecord('foo', 'foo.jpg', 1);

		$this->assertContains(
			'rel="lightbox[objectGallery_1]"',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	/**
	 * @test
	 */
	public function renderForPosition1ImageAndLightboxGloballyDisabledAndLocallyEnabledAddsLightboxAttributeToImage() {
		tx_oelib_ConfigurationRegistry::get('plugin.tx_realty_pi1.images')
			->set('1.', array('enableLightbox' => TRUE));

		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getNewGhost();
		$realtyObject->addImageRecord('foo', 'foo.jpg', 1);

		$this->assertContains(
			'rel="lightbox[objectGallery_1]"',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	/**
	 * @test
	 */
	public function renderForPosition1ImageAndLightboxGloballyEnabledAndLocallyDisabledNotAddsLightboxAttributeToImage() {
		$this->configuration->setAsBoolean('enableLightbox' , TRUE);
		tx_oelib_ConfigurationRegistry::get('plugin.tx_realty_pi1.images')
			->set('1.', array('enableLightbox' => FALSE));

		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getNewGhost();
		$realtyObject->addImageRecord('foo', 'foo.jpg', 1);

		$this->assertNotContains(
			'rel="lightbox[',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	/**
	 * @test
	 */
	public function renderForImagePositionsTwoOneZeroRendersInZeroOneTwoOrder() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getNewGhost();
		$realtyObject->addImageRecord('2', '2.jpg', 2);
		$realtyObject->addImageRecord('1', '1.jpg', 1);
		$realtyObject->addImageRecord('0', '0.jpg', 0);

		$fixture = $this->getMock(
			'tx_realty_pi1_ImageThumbnailsView', array('createRestrictedImage'),
			array(), '', FALSE
		);
		$fixture->expects($this->at(0))->method('createRestrictedImage')->with(
			tx_realty_Model_Image::UPLOAD_FOLDER . '0.jpg', '0', 102, 77
		);
		$fixture->expects($this->at(1))->method('createRestrictedImage')->with(
			tx_realty_Model_Image::UPLOAD_FOLDER . '1.jpg', '1', 102, 77
		);
		$fixture->expects($this->at(2))->method('createRestrictedImage')->with(
			tx_realty_Model_Image::UPLOAD_FOLDER . '2.jpg', '2', 102, 77
		);

		$fixture->render(array('showUid' => $realtyObject->getUid()));
	}

	/**
	 * @test
	 */
	public function renderForOnlyPositionZeroImageHidesPositionOneToFourSubparts() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getNewGhost();
		$realtyObject->addImageRecord('foo', 'foo.jpg', 0);

		$result = $this->fixture->render(
			array('showUid' => $realtyObject->getUid())
		);

		$this->assertNotContains(
			'class="images_position_1',
			$result
		);
		$this->assertNotContains(
			'class="images_position_2',
			$result
		);
		$this->assertNotContains(
			'class="images_position_3',
			$result
		);
		$this->assertNotContains(
			'class="images_position_4',
			$result
		);
	}

	/**
	 * @test
	 */
	public function renderForOnlyPositionOneImageHidesDefaultSubpart() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getNewGhost();
		$realtyObject->addImageRecord('foo', 'foo.jpg', 1);

		$result = $this->fixture->render(
			array('showUid' => $realtyObject->getUid())
		);

		$this->assertNotContains(
			'class="item',
			$result
		);
	}

	/**
	 * @test
	 */
	public function renderForOnlyPositionOneImageHidesSubpartsTwoToFour() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getNewGhost();
		$realtyObject->addImageRecord('foo', 'foo.jpg', 1);

		$result = $this->fixture->render(
			array('showUid' => $realtyObject->getUid())
		);

		$this->assertNotContains(
			'class="images_position_2',
			$result
		);
		$this->assertNotContains(
			'class="images_position_3',
			$result
		);
		$this->assertNotContains(
			'class="images_position_4',
			$result
		);
	}


	/////////////////////////////////////////////
	// Tests concerning the separate thumbnails
	/////////////////////////////////////////////

	/**
	 * @test
	 */
	public function renderWithSeparateThumbnailUsesThumbnailImage() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getNewGhost();
		$realtyObject->addImageRecord('fooBar', 'foo.jpg', 0, 'thumbnail.jpg');

		$fixture = $this->getMock(
			'tx_realty_pi1_ImageThumbnailsView', array('createRestrictedImage'),
			array(), '', FALSE
		);
		$fixture->expects($this->at(0))->method('createRestrictedImage')->with(
			tx_realty_Model_Image::UPLOAD_FOLDER . 'thumbnail.jpg',
			'fooBar',
			102,
			77
		);

		$fixture->render(array('showUid' => $realtyObject->getUid()));
	}
}
?>