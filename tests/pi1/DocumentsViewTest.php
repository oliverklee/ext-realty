<?php
/***************************************************************
* Copyright notice
*
* (c) 2010 Oliver Klee (typo3-coding@oliverklee.de)
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
 * Testcase for the tx_realty_pi1_DocumentsView class in the "realty"
 * extension.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class tx_realty_pi1_DocumentsViewTest extends tx_phpunit_testcase {
	/**
	 * @var tx_realty_pi1_DocumentsView
	 */
	private $fixture;

	/**
	 * @var tx_oelib_testingFramework
	 */
	private $testingFramework;

	public function setUp() {
		$this->testingFramework = new tx_oelib_testingFramework('tx_realty');
		$this->testingFramework->createFakeFrontEnd();

		$this->fixture = new tx_realty_pi1_DocumentsView(
			array('templateFile' => 'EXT:realty/pi1/tx_realty_pi1.tpl.htm'),
			$GLOBALS['TSFE']->cObj
		);
	}

	public function tearDown() {
		$this->testingFramework->cleanUp();

		$this->fixture->__destruct();
		unset($this->fixture, $this->testingFramework);
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
?>