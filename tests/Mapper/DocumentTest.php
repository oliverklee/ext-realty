<?php
/***************************************************************
* Copyright notice
*
* (c) 2010-2012 Bernd Schönbach <bernd.schoenbach@googlemail.com>
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
 * Testcase for the tx_realty_Mapper_Document class in the "realty" extension.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Bernd Schönbach <bernd.schoenbach@googlemail.com>
 */
class tx_realty_Mapper_DocumentTest extends tx_phpunit_testcase {
	/**
	 * @var tx_realty_Mapper_Document
	 */
	private $fixture;

	/**
	 * @var tx_oelib_testingFramework
	 */
	private $testingFramework;

	public function setUp() {
		$this->testingFramework = new tx_oelib_testingFramework('tx_realty');
		$this->fixture = new tx_realty_Mapper_Document();
	}

	public function tearDown() {
		$this->testingFramework->cleanUp();

		$this->fixture->__destruct();
		unset($this->fixture, $this->testingFramework);
	}


	/////////////////////////////////////////
	// Tests concerning the basic functions
	/////////////////////////////////////////

	/**
	 * @test
	 */
	public function findWithUidReturnsDocumentInstance() {
		$this->assertTrue(
			$this->fixture->find(1) instanceof tx_realty_Model_Document
		);
	}

	/**
	 * @test
	 */
	public function findWithUidOfExistingRecordReturnsRecordAsModel() {
		$uid = $this->testingFramework->createRecord(
			'tx_realty_documents', array('title' => 'an important document')
		);

		$this->assertEquals(
			'an important document',
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
		$document = $this->fixture->getLoadedTestingModel(
			array('object' => $realtyObject->getUid())
		);

		$this->assertSame(
			$realtyObject,
			$document->getObject()
		);
	}


	////////////////////////////
	// Tests concerning delete
	////////////////////////////

	/**
	 * @test
	 */
	public function deleteDeletesDocumentFile() {
		$dummyFile = $this->testingFramework->createDummyFile('foo.pdf');
		$uid = $this->testingFramework->createRecord(
			'tx_realty_documents', array('filename' => basename($dummyFile))
		);

		$this->fixture->delete($this->fixture->find($uid));

		$this->assertFalse(
			file_exists($dummyFile)
		);
	}

	/**
	 * @test
	 */
	public function deleteForInexistentDocumentFileNotThrowsException() {
		$dummyFile = $this->testingFramework->createDummyFile('foo.pdf');
		unlink($dummyFile);
		$uid = $this->testingFramework->createRecord(
			'tx_realty_documents', array('filename' => basename($dummyFile))
		);

		$this->fixture->delete($this->fixture->find($uid));
	}

	/**
	 * @test
	 */
	public function deleteForEmptyFileNameNotThrowsException() {
		$uid = $this->testingFramework->createRecord(
			'tx_realty_documents', array('filename' => '')
		);

		$this->fixture->delete($this->fixture->find($uid));
	}
}
?>