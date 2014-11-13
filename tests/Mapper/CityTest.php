<?php
/**
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
			'InvalidArgumentException',
			'$value must not be empty.'
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
	public function findByNameForInexistentNameThrowsException() {
		$this->setExpectedException('tx_oelib_Exception_NotFound');

		$this->fixture->findByName('Hupflingen');
	}
}