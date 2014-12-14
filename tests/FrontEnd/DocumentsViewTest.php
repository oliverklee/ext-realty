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
class tx_realty_FrontEnd_DocumentsViewTest extends tx_phpunit_testcase {
	/**
	 * @var tx_realty_pi1_DocumentsView
	 */
	private $fixture;

	/**
	 * @var tx_oelib_testingFramework
	 */
	private $testingFramework;

	protected function setUp() {
		$this->testingFramework = new tx_oelib_testingFramework('tx_realty');
		$this->testingFramework->createFakeFrontEnd();

		$this->fixture = new tx_realty_pi1_DocumentsView(
			array('templateFile' => 'EXT:realty/pi1/tx_realty_pi1.tpl.htm'),
			$GLOBALS['TSFE']->cObj
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
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array());
		$realtyObject->addDocument('new document', 'readme.pdf');

		$result = $this->fixture->render(
			array('showUid' => $realtyObject->getUid())
		);

		$this->assertNotContains(
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
		$uid = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array())->getUid();

		$this->assertEquals(
			'',
			$this->fixture->render(array('showUid' => $uid))
		);
	}

	/**
	 * @test
	 */
	public function renderForObjectWithDocumentContainsDocumentTitle() {
		$object = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array());
		$object->addDocument('object layout', 'foo.pdf');

		$this->assertContains(
			'object layout',
			$this->fixture->render(array('showUid' => $object->getUid()))
		);
	}

	/**
	 * @test
	 */
	public function renderHtmlspecialcharsDocumentTitle() {
		$object = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array());
		$object->addDocument('rise & shine', 'foo.pdf');

		$this->assertContains(
			'rise &amp; shine',
			$this->fixture->render(array('showUid' => $object->getUid()))
		);
	}

	/**
	 * @test
	 */
	public function renderForObjectWithTwoDocumentsContainsBothDocumentTitles() {
		$object = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array());
		$object->addDocument('object layout', 'foo.pdf');
		$object->addDocument('object overview', 'bar.pdf');

		$result = $this->fixture->render(array('showUid' => $object->getUid()));

		$this->assertContains(
			'object layout',
			$result,
			'The first title is missing.'
		);
		$this->assertContains(
			'object overview',
			$result,
			'The second title is missing.'
		);
	}

	/**
	 * @test
	 */
	public function renderContainsLinkToDocumentFile() {
		$object = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array());
		$object->addDocument('object layout', 'foo.pdf');

		$this->assertContains(
			'foo.pdf"',
			$this->fixture->render(array('showUid' => $object->getUid()))
		);
	}

	/**
	 * @test
	 */
	public function renderForObjectWithTwoDocumentsContainsBothDocumentLinks() {
		$object = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array());
		$object->addDocument('object layout', 'foo.pdf');
		$object->addDocument('object overview', 'bar.pdf');

		$result = $this->fixture->render(array('showUid' => $object->getUid()));

		$this->assertContains(
			'foo.pdf',
			$result,
			'The first title is missing.'
		);
		$this->assertContains(
			'bar.pdf',
			$result,
			'The second title is missing.'
		);
	}
}