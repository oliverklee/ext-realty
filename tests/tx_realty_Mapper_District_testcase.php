<?php
/***************************************************************
* Copyright notice
*
* (c) 2009 Oliver Klee (typo3-coding@oliverklee.de)
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
 * Testcase for the tx_realty_Mapper_District class in the "realty" extension.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class tx_realty_Mapper_District_testcase extends tx_phpunit_testcase {
	/**
	 * @var tx_oelib_testingFramework
	 */
	private $testingFramework;

	/**
	 * @var tx_realty_Mapper_District
	 */
	private $fixture;

	public function setUp() {
		$this->testingFramework = new tx_oelib_testingFramework('tx_realty');

		$this->fixture = new tx_realty_Mapper_District();
	}

	public function tearDown() {
		$this->testingFramework->cleanUp();

		$this->fixture->__destruct();
		unset($this->fixture, $this->testingFramework);
	}


	//////////////////////////
	// Tests concerning find
	//////////////////////////

	/**
	 * @test
	 */
	public function findWithUidReturnsDistrictInstance() {
		$this->assertTrue(
			$this->fixture->find(1) instanceof tx_realty_Model_District
		);
	}

	/**
	 * @test
	 */
	public function findWithUidOfExistingRecordReturnsRecordAsModel() {
		$uid = $this->testingFramework->createRecord(
			'tx_realty_districts', array('title' => 'Bad Godesberg')
		);

		$this->assertEquals(
			'Bad Godesberg',
			$this->fixture->find($uid)->getTitle()
		);
	}


	///////////////////////////
	// Test for the relations
	///////////////////////////

	/**
	 * @test
	 */
	public function getCityReturnsCityFromRelation() {
		$city = tx_oelib_MapperRegistry::get('tx_realty_Mapper_City')
			->getNewGhost();

		$model = $this->fixture->getLoadedTestingModel(
			array('city' => $city->getUid())
		);

		$this->assertSame(
			$city,
			$model->getCity()
		);
	}
}
?>