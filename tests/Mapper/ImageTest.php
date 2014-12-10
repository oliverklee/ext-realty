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
class tx_realty_Mapper_ImageTest extends tx_phpunit_testcase {
	/**
	 * @var tx_realty_Mapper_Image
	 */
	private $fixture;

	/**
	 * @var tx_oelib_testingFramework
	 */
	private $testingFramework;

	public function setUp() {
		$this->testingFramework = new tx_oelib_testingFramework('tx_realty');
		$this->fixture = new tx_realty_Mapper_Image();
	}

	public function tearDown() {
		$this->testingFramework->cleanUp();

		unset($this->fixture, $this->testingFramework);
	}


	/////////////////////////////////////////
	// Tests concerning the basic functions
	/////////////////////////////////////////

	/**
	 * @test
	 */
	public function findWithUidReturnsImageInstance() {
		$this->assertTrue(
			$this->fixture->find(1) instanceof tx_realty_Model_Image
		);
	}

	/**
	 * @test
	 */
	public function findWithUidOfExistingRecordReturnsRecordAsModel() {
		$uid = $this->testingFramework->createRecord(
			'tx_realty_images', array('caption' => 'a nice green lawn')
		);

		$this->assertEquals(
			'a nice green lawn',
			$this->fixture->find($uid)->getTitle()
		);
	}


	///////////////////////////////////////////
	// Tests concerning the "object" relation
	///////////////////////////////////////////

	/**
	 * @test
	 */
	public function getObjectReturnsRelatedRealtyObject() {
		$realtyObject = tx_oelib_MapperRegistry
			::get('tx_realty_Mapper_RealtyObject')->getNewGhost();
		$image = $this->fixture->getLoadedTestingModel(
			array('object' => $realtyObject->getUid())
		);

		$this->assertSame(
			$realtyObject,
			$image->getObject()
		);
	}


	////////////////////////////
	// Tests concerning delete
	////////////////////////////

	/**
	 * @test
	 */
	public function deleteDeletesImageFile() {
		$dummyFile = $this->testingFramework->createDummyFile('foo.jpg');
		$uid = $this->testingFramework->createRecord(
			'tx_realty_images', array('image' => basename($dummyFile))
		);

		$this->fixture->delete($this->fixture->find($uid));

		$this->assertFalse(
			file_exists($dummyFile)
		);
	}

	/**
	 * @test
	 */
	public function deleteForInexistentImageFileNotThrowsException() {
		$dummyFile = $this->testingFramework->createDummyFile('foo.jpg');
		unlink($dummyFile);
		$uid = $this->testingFramework->createRecord(
			'tx_realty_images', array('image' => basename($dummyFile))
		);

		$this->fixture->delete($this->fixture->find($uid));
	}

	/**
	 * @test
	 */
	public function deleteForEmptyImageFileNameNotThrowsException() {
		$uid = $this->testingFramework->createRecord(
			'tx_realty_images', array('image' => '')
		);

		$this->fixture->delete($this->fixture->find($uid));
	}
}