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
class tx_realty_Mapper_DocumentTest extends Tx_Phpunit_TestCase {
	/**
	 * @var tx_realty_Mapper_Document
	 */
	private $fixture = NULL;

	/**
	 * @var Tx_Oelib_TestingFramework
	 */
	private $testingFramework = NULL;

	protected function setUp() {
		$this->testingFramework = new Tx_Oelib_TestingFramework('tx_realty');
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
		self::assertTrue(
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

		/** @var tx_realty_Model_Document $model */
		$model = $this->fixture->find($uid);
		self::assertEquals(
			'an important document',
			$model->getTitle()
		);
	}


	///////////////////////////////////////////
	// Tests concerning the "object" relation
	///////////////////////////////////////////

	/**
	 * @test
	 */
	public function getObjectReturnsRelatedRealtyObject() {
		/** @var tx_realty_Model_RealtyObject $realtyObject */
		$realtyObject = Tx_Oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')->getNewGhost();
		/** @var tx_realty_Model_Document $document */
		$document = $this->fixture->getLoadedTestingModel(
			array('object' => $realtyObject->getUid())
		);

		self::assertSame(
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

		/** @var tx_realty_Model_Document $model */
		$model = $this->fixture->find($uid);
		$this->fixture->delete($model);

		self::assertFalse(
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

		/** @var tx_realty_Model_Document $model */
		$model = $this->fixture->find($uid);
		$this->fixture->delete($model);
	}

	/**
	 * @test
	 */
	public function deleteForEmptyFileNameNotThrowsException() {
		$uid = $this->testingFramework->createRecord(
			'tx_realty_documents', array('filename' => '')
		);

		/** @var tx_realty_Model_Document $model */
		$model = $this->fixture->find($uid);
		$this->fixture->delete($model);
	}
}