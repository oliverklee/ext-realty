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
class tx_realty_FrontEnd_DocumentsViewTest extends Tx_Phpunit_TestCase {
	/**
	 * @var tx_realty_pi1_DocumentsView
	 */
	private $fixture = NULL;

	/**
	 * @var Tx_Oelib_TestingFramework
	 */
	private $testingFramework = NULL;

	/**
	 * @var tx_realty_Mapper_RealtyObject
	 */
	private $realtyObjectMapper = NULL;

	protected function setUp() {
		$this->testingFramework = new Tx_Oelib_TestingFramework('tx_realty');
		$this->testingFramework->createFakeFrontEnd();

		$this->realtyObjectMapper = Tx_Oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject');

		/** @var tslib_fe $frontEndController */
		$frontEndController = $GLOBALS['TSFE'];
		$this->fixture = new tx_realty_pi1_DocumentsView(
			array('templateFile' => 'EXT:realty/pi1/tx_realty_pi1.tpl.htm'), $frontEndController->cObj
		);
	}

	protected function tearDown() {
		$this->testingFramework->cleanUp();
	}


	//////////////////////////////////
	// Tests for the basic functions
	//////////////////////////////////

	/**
	 * @test
	 */
	public function renderReturnsNoUnreplacedMarkers() {
		/** @var tx_realty_Model_RealtyObject $realtyObject */
		$realtyObject = $this->realtyObjectMapper->getLoadedTestingModel(array());
		$realtyObject->addDocument('new document', 'readme.pdf');

		$result = $this->fixture->render(
			array('showUid' => $realtyObject->getUid())
		);

		self::assertNotContains(
			'###',
			$result
		);
	}


	////////////////////////////////
	// Tests for the render result
	////////////////////////////////

	/**
	 * @test
	 */
	public function renderForObjectWithoutDocumentsReturnsEmptyString() {
		$uid = $this->realtyObjectMapper->getLoadedTestingModel(array())->getUid();

		self::assertEquals(
			'',
			$this->fixture->render(array('showUid' => $uid))
		);
	}

	/**
	 * @test
	 */
	public function renderForObjectWithDocumentContainsDocumentTitle() {
		/** @var tx_realty_Model_RealtyObject $object */
		$object = $this->realtyObjectMapper->getLoadedTestingModel(array());
		$object->addDocument('object layout', 'foo.pdf');

		self::assertContains(
			'object layout',
			$this->fixture->render(array('showUid' => $object->getUid()))
		);
	}

	/**
	 * @test
	 */
	public function renderHtmlspecialcharsDocumentTitle() {
		/** @var tx_realty_Model_RealtyObject $object */
		$object = $this->realtyObjectMapper->getLoadedTestingModel(array());
		$object->addDocument('rise & shine', 'foo.pdf');

		self::assertContains(
			'rise &amp; shine',
			$this->fixture->render(array('showUid' => $object->getUid()))
		);
	}

	/**
	 * @test
	 */
	public function renderForObjectWithTwoDocumentsContainsBothDocumentTitles() {
		/** @var tx_realty_Model_RealtyObject $object */
		$object = $this->realtyObjectMapper->getLoadedTestingModel(array());
		$object->addDocument('object layout', 'foo.pdf');
		$object->addDocument('object overview', 'bar.pdf');

		$result = $this->fixture->render(array('showUid' => $object->getUid()));

		self::assertContains(
			'object layout',
			$result,
			'The first title is missing.'
		);
		self::assertContains(
			'object overview',
			$result,
			'The second title is missing.'
		);
	}

	/**
	 * @test
	 */
	public function renderContainsLinkToDocumentFile() {
		/** @var tx_realty_Model_RealtyObject $object */
		$object = $this->realtyObjectMapper->getLoadedTestingModel(array());
		$object->addDocument('object layout', 'foo.pdf');

		self::assertContains(
			'foo.pdf"',
			$this->fixture->render(array('showUid' => $object->getUid()))
		);
	}

	/**
	 * @test
	 */
	public function renderForObjectWithTwoDocumentsContainsBothDocumentLinks() {
		/** @var tx_realty_Model_RealtyObject $object */
		$object = $this->realtyObjectMapper->getLoadedTestingModel(array());
		$object->addDocument('object layout', 'foo.pdf');
		$object->addDocument('object overview', 'bar.pdf');

		$result = $this->fixture->render(array('showUid' => $object->getUid()));

		self::assertContains(
			'foo.pdf',
			$result,
			'The first title is missing.'
		);
		self::assertContains(
			'bar.pdf',
			$result,
			'The second title is missing.'
		);
	}
}