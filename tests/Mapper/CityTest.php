<?php
/***************************************************************
* Copyright notice
*
* (c) 2009-2010 Oliver Klee (typo3-coding@oliverklee.de)
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
 * Testcase for the tx_realty_Mapper_City in the "realty" extension.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class tx_realty_Mapper_CityTest extends tx_phpunit_testcase {
	/**
	 * @var tx_oelib_testingFramework
	 */
	private $testingFramework;

	/**
	 * @var tx_realty_Mapper_City
	 */
	private $fixture;

	public function setUp() {
		$this->testingFramework = new tx_oelib_testingFramework('tx_realty');

		$this->fixture = new tx_realty_Mapper_City();
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
	public function findWithUidReturnsCityInstance() {
		$this->assertTrue(
			$this->fixture->find(1) instanceof tx_realty_Model_City
		);
	}

	/**
	 * @test
	 */
	public function findWithUidOfExistingRecordReturnsRecordAsModel() {
		$uid = $this->testingFramework->createRecord(
			'tx_realty_cities', array('title' => 'London')
		);

		$this->assertEquals(
			'London',
			$this->fixture->find($uid)->getTitle()
		);
	}


	////////////////////////////////
	// Tests concerning findByName
	////////////////////////////////

	/**
	 * @test
	 */
	public function findByNameForEmptyValueThrowsException() {
		$this->setExpectedException(
			'Exception', '$value must not be empty.'
		);

		$this->fixture->findByName('');
	}

	/**
	 * @test
	 */
	public function findByNameCanFindModelFromCache() {
		$model = $this->fixture->getLoadedTestingModel(
			array('title' => 'Kleinwurzeling')
		);

		$this->assertSame(
			$model,
			$this->fixture->findByName('Kleinwurzeling')
		);
	}

	/**
	 * @test
	 */
	public function findByNameCanLoadModelFromDatabase() {
		$uid = $this->testingFramework->createRecord(
			'tx_realty_cities', array('title' => 'Kleinwurzeling')
		);

		$this->assertEquals(
			$uid,
			$this->fixture->findByName('Kleinwurzeling')->getUid()
		);
	}

	/**
	 * @test
	 */
	public function findByNameForInexistentThrowsException() {
		$this->setExpectedException('tx_oelib_Exception_NotFound');

		$this->fixture->findByName('Hupflingen');
	}
}
?>