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
 * Unit tests for the tx_realty_pi1_ImageThumbnailsView class in the 'realty'
 * extension.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 */
class tx_realty_pi1_ImageThumbnailsView_testcase extends tx_phpunit_testcase {
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

		$this->fixture = new tx_realty_pi1_ImageThumbnailsView(
			array('templateFile' => 'EXT:realty/pi1/tx_realty_pi1.tpl.htm'),
			$GLOBALS['TSFE']->cObj
		);

		$this->fixture->setConfigurationValue('galleryType', 'classic');
		$this->fixture->setConfigurationValue('galleryPID', '');
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

	public function testRenderReturnsLinkedImage() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getNewGhost();
		$realtyObject->addImageRecord('foo', 'foo.jpg');

		$this->fixture->setConfigurationValue(
			'galleryPID', $this->testingFramework->createFrontEndPage()
		);

		$this->assertContains(
			'tx_realty_pi1[image]=0',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	public function testRenderReturnsHtmlspecialcharedImageCaptionForClassicGallery() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getNewGhost();
		$realtyObject->addImageRecord('foo</br>', 'foo.jpg');

		$this->fixture->setConfigurationValue(
			'galleryPID', $this->testingFramework->createFrontEndPage()
		);

		$this->assertContains(
			htmlspecialchars('foo</br>'),
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	public function testRenderReturnsNoNonHtmlspecialcharedImageCaptionForClassicGallery() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getNewGhost();
		$realtyObject->addImageRecord('foo</br>', 'foo.jpg');

		$this->fixture->setConfigurationValue(
			'galleryPID', $this->testingFramework->createFrontEndPage()
		);

		$this->assertNotContains(
			'foo</br>',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	public function testRenderReturnsLinkedImageWithGalleryPid() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getNewGhost();
		$realtyObject->addImageRecord('foo', 'foo.jpg');

		$galleryPid = $this->testingFramework->createFrontEndPage();
		$this->fixture->setConfigurationValue('galleryPID', $galleryPid);

		$this->assertContains(
			'?id=' . $galleryPid,
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	public function testRenderReturnsLinkedImageWithCacheHashInTheLink() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getNewGhost();
		$realtyObject->addImageRecord('foo', 'foo.jpg');

		$this->fixture->setConfigurationValue(
			'galleryPID', $this->testingFramework->createFrontEndPage()
		);

		$this->assertContains(
			'cHash=',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	public function testRenderReturnsImageWithFullUrlForPopUp() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getNewGhost();
		$realtyObject->addImageRecord('foo', 'foo.jpg');

		$this->fixture->setConfigurationValue(
			'galleryPID', $this->testingFramework->createFrontEndPage()
		);

		$this->fixture->setConfigurationValue(
			'galleryPopupParameters', 'width=600,height=400'
		);

		$this->assertContains(
			'window.open(\'http://',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	public function testRenderReturnsImageForActivatedLightboxWithRelAttribute() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getNewGhost();
		$realtyObject->addImageRecord('foo', 'foo.jpg');

		$this->fixture->setConfigurationValue('galleryType', 'lightbox');

		$this->assertContains(
			'rel="lightbox[objectGallery]"',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	public function testRenderReturnsHtmlspecialcharedImageCaptionForLightboxStyledGallery() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getNewGhost();
		$realtyObject->addImageRecord('foo</br>', 'foo.jpg');

		$this->fixture->setConfigurationValue('galleryType', 'lightbox');

		$this->assertContains(
			htmlspecialchars('foo</br>'),
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	public function testRenderReturnsNoNonHtmlspecialcharedImageCaptionForLightboxStyledGallery() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getNewGhost();
		$realtyObject->addImageRecord('foo</br>', 'foo.jpg');

		$this->fixture->setConfigurationValue('galleryType', 'lightbox');

		$this->assertNotContains(
			'foo</br>',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	public function testRenderReturnsImageForDeactivatedLightboxWithoutRelAttribute() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getNewGhost();
		$realtyObject->addImageRecord('foo', 'foo.jpg');

		$this->assertNotContains(
			'rel="lightbox[objectGallery]"',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	public function testRenderIncludesLightboxConfigurationForActivatedLightbox() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getNewGhost();
		$realtyObject->addImageRecord('foo', 'foo.jpg');

		$this->fixture->setConfigurationValue('galleryType', 'lightbox');
		$this->fixture->render(array('showUid' => $realtyObject->getUid()));

		$this->assertTrue(
			array_key_exists(
				'tx_realty_pi1_lightbox_config', $GLOBALS['TSFE']->additionalHeaderData
			)
		);
	}

	public function testRenderIncludesLightboxJsFileForActivatedLightbox() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getNewGhost();
		$realtyObject->addImageRecord('foo', 'foo.jpg');

		$this->fixture->setConfigurationValue('galleryType', 'lightbox');
		$this->fixture->render(array('showUid' => $realtyObject->getUid()));

		$this->assertTrue(
			in_array(
				'<script type="text/javascript" src="../typo3conf/ext/realty' .
					'/pi1/contrib/lightbox.js" ></script>',
				$GLOBALS['TSFE']->additionalHeaderData
			)
		);
	}

	public function testRenderIncludesLightboxCssFileForActivatedLightbox() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getNewGhost();
		$realtyObject->addImageRecord('foo', 'foo.jpg');

		$this->fixture->setConfigurationValue('galleryType', 'lightbox');
		$this->fixture->render(array('showUid' => $realtyObject->getUid()));

		$this->assertTrue(
			in_array(
				'<link rel="stylesheet" type="text/css" href="..' .
					'/typo3conf/ext/realty/pi1/contrib/lightbox.css" />',
				$GLOBALS['TSFE']->additionalHeaderData
			)
		);
	}

	public function testRenderIncludesPrototypeJsFileForActivatedLightbox() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getNewGhost();
		$realtyObject->addImageRecord('foo', 'foo.jpg');

		$this->fixture->setConfigurationValue('galleryType', 'lightbox');
		$this->fixture->render(array('showUid' => $realtyObject->getUid()));

		$this->assertTrue(
			in_array(
				'<script type="text/javascript" src="../typo3conf/ext/realty' .
					'/pi1/contrib/prototype.js"></script>',
				$GLOBALS['TSFE']->additionalHeaderData
			)
		);
	}

	public function testRenderIncludesScriptaculousJsFileForActivatedLightbox() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getNewGhost();
		$realtyObject->addImageRecord('foo', 'foo.jpg');

		$this->fixture->setConfigurationValue('galleryType', 'lightbox');
		$this->fixture->render(array('showUid' => $realtyObject->getUid()));

		$this->assertTrue(
			in_array(
				'<script type="text/javascript"src="../typo3conf/ext/realty/pi1' .
					'/contrib/scriptaculous.js?load=effects,builder"></script>',
				$GLOBALS['TSFE']->additionalHeaderData
			)
		);
	}

	public function testRenderNotIncludeLightboxConfigurationForDeactivatedLightboxDoes() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getNewGhost();
		$realtyObject->addImageRecord('foo', 'foo.jpg');

		$this->fixture->render(array('showUid' => $realtyObject->getUid()));

		$this->assertFalse(
			array_key_exists(
				'tx_realty_pi1_lightbox_config',
				$GLOBALS['TSFE']->additionalHeaderData
			)
		);
	}

	public function testRenderNotIncludesLightboxJsFileForDeactivatedLightbox() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getNewGhost();
		$realtyObject->addImageRecord('foo', 'foo.jpg');

		$this->fixture->render(array('showUid' => $realtyObject->getUid()));

		$this->assertFalse(
			in_array(
				'<script type="text/javascript" src="../typo3conf/ext/realty' .
					'/pi1/contrib/lightbox.js" ></script>',
				$GLOBALS['TSFE']->additionalHeaderData
			)
		);
	}

	public function testRenderNotIncludesLightboxCssFileForDeactivatedLightbox() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getNewGhost();
		$realtyObject->addImageRecord('foo', 'foo.jpg');

		$this->fixture->render(array('showUid' => $realtyObject->getUid()));

		$this->assertFalse(
			in_array(
				'<link rel="stylesheet" type="text/css" href="..' .
					'/typo3conf/ext/realty/pi1/contrib/lightbox.css" />',
				$GLOBALS['TSFE']->additionalHeaderData
			)
		);
	}

	public function testRenderNotIncludesPrototypeJsFileForDeactivatedLightbox() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getNewGhost();
		$realtyObject->addImageRecord('foo', 'foo.jpg');

		$this->fixture->render(array('showUid' => $realtyObject->getUid()));

		$this->assertFalse(
			in_array(
				'<script type="text/javascript" src="../typo3conf/ext/realty' .
					'/pi1/contrib/prototype.js"></script>',
				$GLOBALS['TSFE']->additionalHeaderData
			)
		);
	}

	public function testRenderNotIncludesScriptaculousJsFileForDeactivatedLightbox() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getNewGhost();
		$realtyObject->addImageRecord('foo', 'foo.jpg');

		$this->fixture->render(array('showUid' => $realtyObject->getUid()));

		$this->assertFalse(
			in_array(
				'<script type="text/javascript"src="../typo3conf/ext/realty/pi1' .
					'/contrib/scriptaculous.js?load=effects,builder"></script>',
				$GLOBALS['TSFE']->additionalHeaderData
			)
		);
	}
}
?>