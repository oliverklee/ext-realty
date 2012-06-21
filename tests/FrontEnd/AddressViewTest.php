<?php
/***************************************************************
* Copyright notice
*
* (c) 2009-2012 Saskia Metzler <saskia@merlin.owl.de>
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
 * Unit tests for the tx_realty_pi1_AddressView class in the "realty"
 * extension.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 */
class tx_realty_FrontEnd_AddressViewTest extends tx_phpunit_testcase {
	/**
	 * @var tx_realty_pi1_AddressView
	 */
	private $fixture;

	/**
	 * @var tx_oelib_testingFramework
	 */
	private $testingFramework;

	public function setUp() {
		$this->testingFramework = new tx_oelib_testingFramework('tx_realty');
		$this->testingFramework->createFakeFrontEnd();

		$this->fixture = new tx_realty_pi1_AddressView(
			array('templateFile' => 'EXT:realty/pi1/tx_realty_pi1.tpl.htm'),
			$GLOBALS['TSFE']->cObj
		);
	}

	public function tearDown() {
		$this->testingFramework->cleanUp();

		$this->fixture->__destruct();
		unset($this->fixture, $this->testingFramework);
	}


	/////////////////////////////
	// Testing the address view
	/////////////////////////////

	public function testRenderReturnsNonEmptyResultForShowUidOfExistingRecordWithZip() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array('zip' => '12345'));

		$this->assertNotEquals(
			'',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	public function testRenderReturnsNoUnreplacedMarkersWhileTheResultIsNonEmpty() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array('zip' => '12345'));

		$result = $this->fixture->render(
			array('showUid' => $realtyObject->getUid())
		);

		$this->assertNotEquals(
			'',
			$result
		);
		$this->assertNotContains(
			'###',
			$result
		);
	}

	public function testRenderReturnsTheRealtyObjectsAddressForValidRealtyObject() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array('zip' => '12345'));

		$this->assertContains(
			'12345',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	public function testRenderReturnsEmptyResultForEmptyAddressDataOfValidRealtyObject() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array());

		$this->assertEquals(
			'',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}
}
?>