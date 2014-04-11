<?php
/***************************************************************
* Copyright notice
*
* (c) 2007-2013 Saskia Metzler <saskia@merlin.owl.de>
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
 * @author Saskia Metzler <saskia@merlin.owl.de>
 */
class tx_realty_Import_FileNameMapperTest extends tx_phpunit_testcase {
	/**
	 * @var tx_realty_fileNameMapper instance to be tested
	 */
	private $fixture;
	/**
	 * @var tx_oelib_testingFramework
	 */
	private $testingFramework;

	public function setUp() {
		$this->testingFramework = new tx_oelib_testingFramework('tx_realty');
		$this->fixture = new tx_realty_fileNameMapper();
	}

	public function tearDown() {
		$this->testingFramework->cleanUp();

		unset($this->fixture, $this->testingFramework);
	}

	/**
	 * @test
	 */
	public function getUniqueFileNameAndMapItReturnsTheOriginalFileNameIfNoFileWithThisNameExists() {
		$this->assertEquals(
			'test.txt',
			$this->fixture->getUniqueFileNameAndMapIt('test.txt')
		);
	}

	/**
	 * @test
	 */
	public function getUniqueFileNameAndMapItReturnsNameWith00SuffixIfAFileWithTheOriginalNameIsAlreadyMapped() {
		$this->fixture->getUniqueFileNameAndMapIt('test.txt');

		$this->assertEquals(
			'test_00.txt',
			$this->fixture->getUniqueFileNameAndMapIt('test.txt')
		);
	}

	/**
	 * @test
	 */
	public function getUniqueFileNameAndMapItReturnsNameWhichBeginsWithNumbersWith00SuffixIfAFileWithTheOriginalNameIsAlreadyMapped() {
		$this->fixture->getUniqueFileNameAndMapIt('1234-test.txt');

		$this->assertEquals(
			'1234-test_00.txt',
			$this->fixture->getUniqueFileNameAndMapIt('1234-test.txt')
		);
	}

	/**
	 * @test
	 */
	public function getUniqueFileNameAndMapItReturnsNameWhichAlreadyHas99SuffixWith100SuffixIfAFileWithTheOriginalNameIsAlreadyMapped() {
		$this->fixture->getUniqueFileNameAndMapIt('test_99.txt');

		$this->assertEquals(
			'test_100.txt',
			$this->fixture->getUniqueFileNameAndMapIt('test_99.txt')
		);
	}

	/**
	 * @test
	 */
	public function getUniqueFileNameAndMapItReturnsNameWithTheSpecialCharactersRemovedIfNoFileWithThisNameExists() {
		$this->assertEquals(
			'test_foo.txt',
			$this->fixture->getUniqueFileNameAndMapIt('test,foo.txt')
		);
	}

	/**
	 * @test
	 */
	public function getUniqueFileNameAndMapItReturnsNameWithTheSpecialCharactersRemovedIfAFileWithTheOriginalNameIsAlreadyMapped() {
		$this->fixture->getUniqueFileNameAndMapIt('test,foo.txt');

		$this->assertEquals(
			'test_foo_00.txt',
			$this->fixture->getUniqueFileNameAndMapIt('test,foo.txt')
		);
	}

	/**
	 * @test
	 */
	public function getUniqueFileNameAndMapItReturnsNameWith01SuffixIfAFileWithTheOriginalNameAndOneWithThe00SuffixAreAlreadyMapped() {
		$this->fixture->getUniqueFileNameAndMapIt('test.txt');
		$this->fixture->getUniqueFileNameAndMapIt('test_00.txt');

		$this->assertEquals(
			'test_01.txt',
			$this->fixture->getUniqueFileNameAndMapIt('test.txt')
		);
	}

	/**
	 * @test
	 */
	public function getUniqueFileNameAndMapItReturnsNameWith00SuffixIfAFileWithTheOriginalNameIsAlreadyStored() {
		$this->testingFramework->createDummyFile('test.txt');

		$this->assertEquals(
			'test_00.txt',
			$this->fixture->getUniqueFileNameAndMapIt('test.txt')
		);
	}

	/**
	 * @test
	 */
	public function getUniqueFileNameAndMapItReturnsNameWith01SuffixIfAFileWithTheOriginalNameAndOneWithThe00SuffixAreAlreadyStored() {
		$this->testingFramework->createDummyFile('test.txt');
		$this->testingFramework->createDummyFile('test_00.txt');

		$this->assertEquals(
			'test_01.txt',
			$this->fixture->getUniqueFileNameAndMapIt('test.txt')
		);
	}

	/**
	 * @test
	 */
	public function getUniqueFileNameAndMapItReturnsNameWith01SuffixIfTheOriginalFileNameExistsAndTheNameWithA00SuffixIsAlreadyMapped() {
		$this->testingFramework->createDummyFile('test.txt');
		$this->fixture->getUniqueFileNameAndMapIt('test_00.txt');

		$this->assertEquals(
			'test_01.txt',
			$this->fixture->getUniqueFileNameAndMapIt('test.txt')
		);
	}

	/**
	 * @test
	 */
	public function releaseMappedFileNamesReturnsTheOriginalNameAsMappedFileNameInAnArrayIfNoFileWithThisFilenameExists() {
		$this->fixture->getUniqueFileNameAndMapIt('test.txt');

		$this->assertEquals(
			array('test.txt'),
			$this->fixture->releaseMappedFileNames('test.txt')
		);
	}

	/**
	 * @test
	 */
	public function releaseMappedFileNamesReturnsTheUniqueMappedFileNameInAnArrayIfOneOriginalFileHasBeenMappedTwice() {
		$this->fixture->getUniqueFileNameAndMapIt('test.txt');
		$this->fixture->getUniqueFileNameAndMapIt('test.txt');

		$this->assertEquals(
			array('test.txt', 'test_00.txt'),
			$this->fixture->releaseMappedFileNames('test.txt')
		);
	}

	/**
	 * @test
	 */
	public function releaseMappedFileNamesReturnsAnEmptyArrayIfNoFileWithThisFilenameHasBeenMapped() {
		$this->assertEquals(
			array(),
			$this->fixture->releaseMappedFileNames('test.txt')
		);
	}

	/**
	 * @test
	 */
	public function releaseMappedFileNamesReturnsAnEmptyArrayIfAMappedFileHasBeenFetchedBefore() {
		$this->fixture->getUniqueFileNameAndMapIt('test.txt');
		$this->fixture->releaseMappedFileNames('test.txt');

		$this->assertEquals(
			array(),
			$this->fixture->releaseMappedFileNames('test.txt')
		);
	}
}