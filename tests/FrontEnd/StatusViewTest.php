<?php
/***************************************************************
* Copyright notice
*
* (c) 2010-2013 Oliver Klee (typo3-coding@oliverklee.de)
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
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class tx_realty_FrontEnd_StatusViewTest extends tx_phpunit_testcase {
	/**
	 * @var tx_realty_pi1_StatusView
	 */
	private $fixture;

	/**
	 * @var tx_oelib_testingFramework
	 */
	private $testingFramework;

	public function setUp() {
		$this->testingFramework = new tx_oelib_testingFramework('tx_realty');
		$this->testingFramework->createFakeFrontEnd();

		$this->fixture = new tx_realty_pi1_StatusView(
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
	public function renderForVacantStatusContainsVacantLabel() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(
				array('status' => tx_realty_Model_RealtyObject::STATUS_VACANT)
			);

		$result = $this->fixture->render(
			array('showUid' => $realtyObject->getUid())
		);

		$this->assertContains(
			$this->fixture->translate('label_status_0'),
			$result
		);
	}

	/**
	 * @test
	 */
	public function renderForRentedStatusContainsRentedLabel() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(
				array('status' => tx_realty_Model_RealtyObject::STATUS_RENTED)
			);

		$result = $this->fixture->render(
			array('showUid' => $realtyObject->getUid())
		);

		$this->assertContains(
			$this->fixture->translate('label_status_3'),
			$result
		);
	}

	/**
	 * @test
	 */
	public function renderForVacantStatusContainsVacantClass() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(
				array('status' => tx_realty_Model_RealtyObject::STATUS_VACANT)
			);

		$result = $this->fixture->render(
			array('showUid' => $realtyObject->getUid())
		);

		$this->assertContains(
			'class="status_vacant"',
			$result
		);
	}

	/**
	 * @test
	 */
	public function renderForReservedStatusContainsReservedClass() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(
				array('status' => tx_realty_Model_RealtyObject::STATUS_RESERVED)
			);

		$result = $this->fixture->render(
			array('showUid' => $realtyObject->getUid())
		);

		$this->assertContains(
			'class="status_reserved"',
			$result
		);
	}

	/**
	 * @test
	 */
	public function renderForSoldStatusContainsSoldClass() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(
				array('status' => tx_realty_Model_RealtyObject::STATUS_SOLD)
			);

		$result = $this->fixture->render(
			array('showUid' => $realtyObject->getUid())
		);

		$this->assertContains(
			'class="status_sold"',
			$result
		);
	}

	/**
	 * @test
	 */
	public function renderForRentedStatusContainsRentedClass() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(
				array('status' => tx_realty_Model_RealtyObject::STATUS_RENTED)
			);

		$result = $this->fixture->render(
			array('showUid' => $realtyObject->getUid())
		);

		$this->assertContains(
			'class="status_rented"',
			$result
		);
	}
}
?>