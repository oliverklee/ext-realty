<?php
/***************************************************************
* Copyright notice
*
* (c) 2010-2013 Bernd Schönbach <bernd.schoenbach@googlemail.com>
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

/**
 * Test case.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Bernd Schönbach <bernd.schoenbach@googlemail.com>
 */
class tx_realty_Model_DocumentTest extends tx_phpunit_testcase {
	/**
	 * @var tx_realty_Model_Document
	 */
	private $fixture;

	public function setUp() {
		$this->fixture = new tx_realty_Model_Document();
	}

	public function tearDown() {
		$this->fixture->__destruct();
		unset($this->fixture);
	}


	///////////////////////////////
	// Tests concerning the title
	///////////////////////////////

	/**
	 * @test
	 */
	public function getTitleReturnsTitle() {
		$this->fixture->setData(array('title' => 'Just another document'));

		$this->assertEquals(
			'Just another document',
			$this->fixture->getTitle()
		);
	}

	/**
	 * @test
	 */
	public function setTitleSetsTitle() {
		$this->fixture->setTitle('Just another document');

		$this->assertEquals(
			'Just another document',
			$this->fixture->getTitle()
		);
	}

	/**
	 * @test
	 *
	 * @expectedException InvalidArgumentException
	 */
	public function setTitleForEmptyTitleThrowsException() {
		$this->fixture->setTitle('');
	}


	////////////////////////////////////////////
	// Tests concerning the document file name
	////////////////////////////////////////////

	/**
	 * @test
	 */
	public function getFileNameReturnsDocumentFileName() {
		$this->fixture->setData(array('filename' => 'foo.pdf'));

		$this->assertEquals(
			'foo.pdf',
			$this->fixture->getFileName()
		);
	}

	/**
	 * @test
	 */
	public function setFileNameSetsFileName() {
		$this->fixture->setFileName('bar.pdf');

		$this->assertEquals(
			'bar.pdf',
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
}
?>