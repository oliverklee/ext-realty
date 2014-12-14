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

	protected function setUp() {
		$this->testingFramework = new tx_oelib_testingFramework('tx_realty');
		$this->fixture = new tx_realty_Mapper_Document();
	}

	protected function tearDown() {
		$this->testingFramework->cleanUp();
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