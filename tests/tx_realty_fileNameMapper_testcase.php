<?php
/***************************************************************
* Copyright notice
*
* (c) 2007-2010 Saskia Metzler <saskia@merlin.owl.de>
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
 * Unit tests for the tx_realty_fileNameMapper class in the 'realty'
 * extension.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 */
class tx_realty_fileNameMapper_testcase extends tx_phpunit_testcase {
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
		$this->fixture->__destruct();
		unset($this->fixture, $this->testingFramework);
	}

	public function testGetUniqueFileNameAndMapItReturnsTheOriginalFileNameIfNoFileWithThisNameExists() {
		$this->assertEquals(
			'test.txt',
			$this->fixture->getUniqueFileNameAndMapIt('test.txt')
		);
	}

	public function testGetUniqueFileNameAndMapItReturnsNameWith00SuffixIfAFileWithTheOriginalNameIsAlreadyMapped() {
		$this->fixture->getUniqueFileNameAndMapIt('test.txt');

		$this->assertEquals(
			'test_00.txt',
			$this->fixture->getUniqueFileNameAndMapIt('test.txt')
		);
	}

	public function testGetUniqueFileNameAndMapItReturnsNameWhichBeginsWithNumbersWith00SuffixIfAFileWithTheOriginalNameIsAlreadyMapped() {
		$this->fixture->getUniqueFileNameAndMapIt('1234-test.txt');

		$this->assertEquals(
			'1234-test_00.txt',
			$this->fixture->getUniqueFileNameAndMapIt('1234-test.txt')
		);
	}

	public function testGetUniqueFileNameAndMapItReturnsNameWhichAlreadyHas99SuffixWith100SuffixIfAFileWithTheOriginalNameIsAlreadyMapped() {
		$this->fixture->getUniqueFileNameAndMapIt('test_99.txt');

		$this->assertEquals(
			'test_100.txt',
			$this->fixture->getUniqueFileNameAndMapIt('test_99.txt')
		);
	}

	public function testGetUniqueFileNameAndMapItReturnsNameWithTheSpecialCharactersRemovedIfNoFileWithThisNameExists() {
		$this->assertEquals(
			'test_foo.txt',
			$this->fixture->getUniqueFileNameAndMapIt('test,foo.txt')
		);
	}

	public function testGetUniqueFileNameAndMapItReturnsNameWithTheSpecialCharactersRemovedIfAFileWithTheOriginalNameIsAlreadyMapped() {
		$this->fixture->getUniqueFileNameAndMapIt('test,foo.txt');

		$this->assertEquals(
			'test_foo_00.txt',
			$this->fixture->getUniqueFileNameAndMapIt('test,foo.txt')
		);
	}

	public function testGetUniqueFileNameAndMapItReturnsNameWith01SuffixIfAFileWithTheOriginalNameAndOneWithThe00SuffixAreAlreadyMapped() {
		$this->fixture->getUniqueFileNameAndMapIt('test.txt');
		$this->fixture->getUniqueFileNameAndMapIt('test_00.txt');

		$this->assertEquals(
			'test_01.txt',
			$this->fixture->getUniqueFileNameAndMapIt('test.txt')
		);
	}

	public function testGetUniqueFileNameAndMapItReturnsNameWith00SuffixIfAFileWithTheOriginalNameIsAlreadyStored() {
		$this->testingFramework->createDummyFile('test.txt');

		$this->assertEquals(
			'test_00.txt',
			$this->fixture->getUniqueFileNameAndMapIt('test.txt')
		);
	}

	public function testGetUniqueFileNameAndMapItReturnsNameWith01SuffixIfAFileWithTheOriginalNameAndOneWithThe00SuffixAreAlreadyStored() {
		$this->testingFramework->createDummyFile('test.txt');
		$this->testingFramework->createDummyFile('test_00.txt');

		$this->assertEquals(
			'test_01.txt',
			$this->fixture->getUniqueFileNameAndMapIt('test.txt')
		);
	}

	public function testGetUniqueFileNameAndMapItReturnsNameWith01SuffixIfTheOriginalFileNameExistsAndTheNameWithA00SuffixIsAlreadyMapped() {
		$this->testingFramework->createDummyFile('test.txt');
		$this->fixture->getUniqueFileNameAndMapIt('test_00.txt');

		$this->assertEquals(
			'test_01.txt',
			$this->fixture->getUniqueFileNameAndMapIt('test.txt')
		);
	}

	public function testReleaseMappedFileNamesReturnsTheOriginalNameAsMappedFileNameInAnArrayIfNoFileWithThisFilenameExists() {
		$this->fixture->getUniqueFileNameAndMapIt('test.txt');

		$this->assertEquals(
			array('test.txt'),
			$this->fixture->releaseMappedFileNames('test.txt')
		);
	}

	public function testReleaseMappedFileNamesReturnsTheUniqueMappedFileNameInAnArrayIfOneOriginalFileHasBeenMappedTwice() {
		$this->fixture->getUniqueFileNameAndMapIt('test.txt');
		$this->fixture->getUniqueFileNameAndMapIt('test.txt');

		$this->assertEquals(
			array('test.txt', 'test_00.txt'),
			$this->fixture->releaseMappedFileNames('test.txt')
		);
	}

	public function testReleaseMappedFileNamesReturnsAnEmptyArrayIfNoFileWithThisFilenameHasBeenMapped() {
		$this->assertEquals(
			array(),
			$this->fixture->releaseMappedFileNames('test.txt')
		);
	}

	public function testReleaseMappedFileNamesReturnsAnEmptyArrayIfAMappedFileHasBeenFetchedBefore() {
		$this->fixture->getUniqueFileNameAndMapIt('test.txt');
		$this->fixture->releaseMappedFileNames('test.txt');

		$this->assertEquals(
			array(),
			$this->fixture->releaseMappedFileNames('test.txt')
		);
	}
}
?>