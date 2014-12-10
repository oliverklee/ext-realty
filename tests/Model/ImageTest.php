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
class tx_realty_Model_ImageTest extends tx_phpunit_testcase {
	/**
	 * @var tx_realty_Model_Image
	 */
	private $fixture;

	public function setUp() {
		$this->fixture = new tx_realty_Model_Image();
	}

	public function tearDown() {
		unset($this->fixture);
	}


	///////////////////////////////
	// Tests concerning the title
	///////////////////////////////

	/**
	 * @test
	 */
	public function getTitleReturnsCaption() {
		$this->fixture->setData(array('caption' => 'Just another room'));

		$this->assertEquals(
			'Just another room',
			$this->fixture->getTitle()
		);
	}

	/**
	 * @test
	 */
	public function setTitleSetsTitle() {
		$this->fixture->setTitle('Just another room');

		$this->assertEquals(
			'Just another room',
			$this->fixture->getTitle()
		);
	}

	/**
	 * @test
	 */
	public function setTitleForEmptyTitleSetsEmptyTitle() {
		$this->fixture->setTitle('');

		$this->assertEquals(
			'',
			$this->fixture->getTitle()
		);
	}


	/////////////////////////////////////////
	// Tests concerning the image file name
	/////////////////////////////////////////

	/**
	 * @test
	 */
	public function getFileNameReturnsImageFileName() {
		$this->fixture->setData(array('image' => 'foo.jpg'));

		$this->assertEquals(
			'foo.jpg',
			$this->fixture->getFileName()
		);
	}

	/**
	 * @test
	 */
	public function setFileNameSetsFileName() {
		$this->fixture->setFileName('bar.jpg');

		$this->assertEquals(
			'bar.jpg',
			$this->fixture->getFileName()
		);
	}

	/**
	 * @test
	 *
	 * @expectedException InvalidArgumentException
	 */
	public function setFileNameForEmptyFileNameThrowsException() {
		$this->fixture->setFileName('');
	}


	/////////////////////////////////////////////
	// Tests concerning the thumbnail file name
	/////////////////////////////////////////////

	/**
	 * @test
	 */
	public function getThumbnailFileNameReturnsThumbnailFileName() {
		$this->fixture->setData(array('thumbnail' => 'foo.jpg'));

		$this->assertEquals(
			'foo.jpg',
			$this->fixture->getThumbnailFileName()
		);
	}

	/**
	 * @test
	 */
	public function setThumbnailFileNameSetsThumbnailFileName() {
		$this->fixture->setThumbnailFileName('bar.jpg');

		$this->assertEquals(
			'bar.jpg',
			$this->fixture->getThumbnailFileName()
		);
	}

	/**
	 * @test
	 */
	public function setThumbnailFileNameCanSetThumbnailFileNameToEmptyString() {
		$this->fixture->setData(array('thumbnail' => 'foo.jpg'));
		$this->fixture->setThumbnailFileName('');

		$this->assertEquals(
			'',
			$this->fixture->getThumbnailFileName()
		);
	}

	/**
	 * @test
	 */
	public function hasThumbnailFileNameForNoThumbnailReturnsFalse() {
		$this->fixture->setData(array());

		$this->assertFalse(
			$this->fixture->hasThumbnailFileName()
		);
	}

	/**
	 * @test
	 */
	public function hasThumbnailFileNameForNonEmptyThumbnailReturnsFalse() {
		$this->fixture->setData(array('thumbnail' => 'foo.jpg'));

		$this->assertTrue(
			$this->fixture->hasThumbnailFileName()
		);
	}


	///////////////////////////////////////////////////////
	// Tests concerning the relation to the realty object
	///////////////////////////////////////////////////////

	/**
	 * @test
	 */
	public function getObjectReturnsObject() {
		$realtyObject = new tx_realty_Model_RealtyObject();
		$this->fixture->setData(array('object' => $realtyObject));

		$this->assertSame(
			$realtyObject,
			$this->fixture->getObject()
		);
	}

	/**
	 * @test
	 */
	public function setObjectSetsObject() {
		$realtyObject = new tx_realty_Model_RealtyObject();
		$this->fixture->setObject($realtyObject);

		$this->assertSame(
			$realtyObject,
			$this->fixture->getObject()
		);
	}


	/////////////////////////////////
	// Tests concerning the sorting
	/////////////////////////////////

	/**
	 * @test
	 */
	public function getSortingInitiallyReturnsZero() {
		$this->fixture->setData(array());

		$this->assertEquals(
			0,
			$this->fixture->getSorting()
		);
	}

	/**
	 * @test
	 */
	public function getSortingReturnsSorting() {
		$this->fixture->setData(array('sorting' => 42));

		$this->assertEquals(
			42,
			$this->fixture->getSorting()
		);
	}

	/**
	 * @test
	 */
	public function setSortingSetsSorting() {
		$this->fixture->setSorting(21);

		$this->assertEquals(
			21,
			$this->fixture->getSorting()
		);
	}


	///////////////////////////////////////////////
	// Tests concerning the position of the image
	///////////////////////////////////////////////

	/**
	 * @test
	 */
	public function getPositionWithoutDataSetReturnsZero() {
		$this->fixture->setData(array());

		$this->assertEquals(
			0,
			$this->fixture->getPosition()
		);
	}

	/**
	 * @test
	 */
	public function getPositionWithPositionSetReturnsPosition() {
		$this->fixture->setData(array('position' => 1));

		$this->assertEquals(
			1,
			$this->fixture->getPosition()
		);
	}

	/**
	 * @test
	 */
	public function setPositionSetsPosition() {
		$this->fixture->setPosition(5);

		$this->assertEquals(
			5,
			$this->fixture->getPosition()
		);
	}
}