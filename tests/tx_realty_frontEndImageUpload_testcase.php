<?php
/***************************************************************
* Copyright notice
*
* (c) 2008-2009 Saskia Metzler <saskia@merlin.owl.de>
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

require_once(t3lib_extMgm::extPath('oelib') . 'class.tx_oelib_testingFramework.php');

require_once(t3lib_extMgm::extPath('realty') . 'lib/tx_realty_constants.php');
require_once(t3lib_extMgm::extPath('realty') . 'lib/class.tx_realty_object.php');
require_once(t3lib_extMgm::extPath('realty') . 'pi1/class.tx_realty_pi1.php');
require_once(t3lib_extMgm::extPath('realty') . 'pi1/class.tx_realty_frontEndImageUpload.php');

/**
 * Unit tests for the tx_realty_frontEndImageUpload class in the 'realty'
 * extension.
 *
 * @package		TYPO3
 * @subpackage	tx_realty
 *
 * @author		Saskia Metzler <saskia@merlin.owl.de>
 */
class tx_realty_frontEndImageUpload_testcase extends tx_phpunit_testcase {
	/** FE editor object to be tested */
	private $fixture;
	/** instance of tx_realty_pi1 */
	private $pi1;
	/** instance of tx_oelib_testingFramework */
	private $testingFramework;

	/** dummy FE user UID */
	private $feUserUid;
	/** UID of the dummy object */
	private $dummyObjectUid = 0;

	/** title for the first dummy image */
	private static $firstImageTitle = 'first test image';
	/** file name for the first dummy image */
	private static $firstImageFileName = 'first.jpg';

	/** title for the second dummy image */
	private static $secondImageTitle = 'second test image';
	/** file name for the second dummy image */
	private static $secondImageFileName = 'second.jpg';

	public function setUp() {
		$this->testingFramework = new tx_oelib_testingFramework('tx_realty');
		$this->testingFramework->createFakeFrontEnd();

		$this->createDummyRecords();
		$this->testingFramework->loginFrontEndUser($this->feUserUid);

		$this->pi1 = new tx_realty_pi1();
		$this->pi1->init(
			array('templateFile' => 'EXT:realty/pi1/tx_realty_pi1.tpl.htm')
		);
		$this->pi1->getTemplateCode();

		$this->fixture = new tx_realty_frontEndImageUpload(
			$this->pi1, 0, '', true
		);
		$this->fixture->setRealtyObjectUid($this->dummyObjectUid);
	}

	public function tearDown() {
		$this->testingFramework->logoutFrontEndUser();
		$this->testingFramework->cleanUp();
		unset($this->fixture, $this->pi1, $this->testingFramework);
	}


	///////////////////////
	// Utility functions.
	///////////////////////

	/**
	 * Creates dummy records in the DB.
	 */
	private function createDummyRecords() {
		$this->feUserUid = $this->testingFramework->createFrontEndUser(
			$this->testingFramework->createFrontEndUserGroup()
		);
		$this->dummyObjectUid = $this->testingFramework->createRecord(
			REALTY_TABLE_OBJECTS, array('owner' => $this->feUserUid)
		);
		$this->createImageRecords();
	}

	/**
	 * Creates dummy image records in the DB.
	 */
	private function createImageRecords() {
		$realtyObject = new tx_realty_object(true);
		$realtyObject->loadRealtyObject($this->dummyObjectUid);

		$realtyObject->addImageRecord(self::$firstImageTitle, self::$firstImageFileName);
		$realtyObject->addImageRecord(self::$secondImageTitle, self::$secondImageFileName);
		$realtyObject->writeToDatabase();

		$this->testingFramework->markTableAsDirty(REALTY_TABLE_IMAGES);
	}


	//////////////////////////////////////////
	// Tests for rendering the image upload.
	//////////////////////////////////////////

	public function testImageUploadDisplaysEmptyResultMessageIfAZeroObjectUidWasProvided() {
		$this->fixture->setRealtyObjectUid(0);

		$this->assertContains(
			$this->pi1->translate('message_noResultsFound_image_upload'),
			$this->fixture->render()
		);
	}


	////////////////////////////////////////////////////
	// Tests for the functions called in the XML form.
	////////////////////////////////////////////////////

	public function testPopulateImageListReturnsListedImages() {
		$this->assertEquals(
			array(
				array(
					'caption' => self::$firstImageTitle
						.' ('.self::$firstImageFileName.')',
					'value' => 0
				),
				array(
					'caption' => self::$secondImageTitle
						.' ('.self::$secondImageFileName.')',
					'value' => 1
					)
			),
			$this->fixture->populateImageList()
		);
	}

	public function testProcessImageUploadWritesNewImageRecordForCurrentObjectToTheDatabase() {
		$this->fixture->processImageUpload(
			array(
				'caption' => 'test image',
				'image' => array('name' => 'image.jpg')
			)
		);

		$this->assertEquals(
			1,
			$this->testingFramework->countRecords(
				REALTY_TABLE_IMAGES,
				'image = "image.jpg" AND caption = "test image"'
			)
		);
	}

	public function testProcessImageUploadStoresCurrentObjectUidAsParentForTheImage() {
		$this->fixture->processImageUpload(
			array(
				'caption' => 'test image',
				'image' => array('name' => 'image.jpg')
			)
		);

		$this->assertEquals(
			1,
			$this->testingFramework->countRecords(
				REALTY_TABLE_IMAGES,
				'realty_object_uid=' . $this->dummyObjectUid .
					' AND caption="test image" AND image="image.jpg"'
			)
		);
	}

	public function testProcessImageUploadDoesNotInsertAnImageIfOnlyACaptionProvided() {
		$this->fixture->processImageUpload(
			array(
				'caption' => 'test image',
				'image' => array('name' => '')
			)
		);

		$this->assertEquals(
			0,
			$this->testingFramework->countRecords(
				REALTY_TABLE_IMAGES,
				'realty_object_uid=' . $this->dummyObjectUid .
					' AND caption="test image"'
			)
		);
	}

	public function testProcessImageUploadDeletesImageRecordForCurrentObjectFromTheDatabase() {
		$this->fixture->processImageUpload(
			array('imagesToDelete' => array('0'))
		);

		$this->assertEquals(
			1,
			$this->testingFramework->countRecords(
				REALTY_TABLE_IMAGES,
				'1=1' . $this->fixture->enableFields(REALTY_TABLE_IMAGES)
			)
		);
	}

	public function testProcessImageUploadDeletesImageTwoRecordsForCurrentObjectFromTheDatabase() {
		$this->fixture->processImageUpload(
			array('imagesToDelete' => array('0', '1'))
		);

		$this->assertEquals(
			0,
			$this->testingFramework->countRecords(
				REALTY_TABLE_IMAGES,
				'1=1' . $this->fixture->enableFields(REALTY_TABLE_IMAGES)
			)
		);
	}


	/////////////////////////////////
	// Tests concerning validation.
	/////////////////////////////////

	public function testCheckFileReturnsTrueIfNoImageWasProvided() {
		$this->assertTrue(
			$this->fixture->checkFile(array('value' => array('name')))
		);
	}

	public function testCheckFileReturnsTrueForGifFile() {
		$this->fixture->setFakedFormValue('caption', 'foo');
		$this->assertTrue(
			$this->fixture->checkFile(
				array('value' => array('name' => 'foo.gif', 'size' => 1))
			)
		);
	}

	public function testCheckFileReturnsTrueForPngFile() {
		$this->fixture->setFakedFormValue('caption', 'foo');
		$this->assertTrue(
			$this->fixture->checkFile(
				array('value' => array('name' => 'foo.png', 'size' => 1))
			)
		);
	}

	public function testCheckFileReturnsTrueForJpgFile() {
		$this->fixture->setFakedFormValue('caption', 'foo');
		$this->assertTrue(
			$this->fixture->checkFile(
				array('value' => array('name' => 'foo.jpg', 'size' => 1))
			)
		);
	}

	public function testCheckFileReturnsTrueForJpegFile() {
		$this->fixture->setFakedFormValue('caption', 'foo');
		$this->assertTrue(
			$this->fixture->checkFile(
				array('value' => array('name' => 'foo.jpeg', 'size' => 1))
			)
		);
	}

	public function testCheckFileReturnsFalseIfNoCaptionWasProvidedForTheImage() {
		$this->assertFalse(
			$this->fixture->checkFile(
				array('value' => array('name' => 'foo.jpg', 'size' => 1))
			)
		);
	}

	public function testCheckFileReturnsFalseForTooLargeImage() {
		$tooLarge = ($GLOBALS['TYPO3_CONF_VARS']['BE']['maxFileSize'] * 1024) + 1;
		$this->fixture->setFakedFormValue('caption', 'foo');
		$this->assertFalse(
			$this->fixture->checkFile(
				array('value' => array('name' => 'foo.jpg', 'size' => $tooLarge))
			)
		);
	}

	public function testCheckFileReturnsFalseForInvalidExtension() {
		$this->fixture->setFakedFormValue('caption', 'foo');
		$this->assertFalse(
			$this->fixture->checkFile(
				array('value' => array('name' => 'foo.foo', 'size' => 1))
			)
		);
	}

	public function testGetImageUploadErrorMessageForEmptyCaption() {
		$this->fixture->checkFile(
			array('value' => array('name' => 'foo.jpg', 'size' => 1))
		);

		$this->assertEquals(
			$this->pi1->translate('message_empty_caption'),
			$this->fixture->getImageUploadErrorMessage()
		);
	}

	public function testGetImageUploadErrorMessageForInvalidExtension() {
		$this->fixture->setFakedFormValue('caption', 'foo');
		$this->fixture->checkFile(
			array('value' => array('name' => 'foo.foo', 'size' => 1))
		);

		$this->assertEquals(
			$this->pi1->translate('message_invalid_type'),
			$this->fixture->getImageUploadErrorMessage()
		);
	}

	public function testGetImageUploadErrorMessageForTooLargeImage() {
		$tooLarge = ($GLOBALS['TYPO3_CONF_VARS']['BE']['maxFileSize'] * 1024) + 1;
		$this->fixture->setFakedFormValue('caption', 'foo');
		$this->fixture->checkFile(
			array('value' => array('name' => 'foo.jpg', 'size' => $tooLarge))
		);

		$this->assertEquals(
			$this->pi1->translate('message_image_too_large'),
			$this->fixture->getImageUploadErrorMessage()
		);
	}


	//////////////////////////////////////////////////
	// Tests concerning functions used after submit.
	//////////////////////////////////////////////////

	public function testGetSelfUrlWithShowUidReturnsUrlWithCurrentPageIdAsTargetPage() {
		$this->assertContains(
			'?id=' . $GLOBALS['TSFE']->id,
			$this->fixture->getSelfUrlWithShowUid()
		);
	}

	public function testGetSelfUrlWithShowUidReturnsUrlWithCurrentShowUidAsLinkParameter() {
		$this->pi1->piVars['showUid'] = $this->dummyObjectUid;

		$this->assertContains(
			'tx_realty_pi1[showUid]=' . $this->dummyObjectUid,
			$this->fixture->getSelfUrlWithShowUid()
		);
	}

	public function testGetSelfUrlWithShowUidReturnsAbsoluteUrl() {
		$this->pi1->piVars['showUid'] = $this->dummyObjectUid;

		$this->assertContains(
			'http://',
			$this->fixture->getSelfUrlWithShowUid()
		);
	}
}
?>