<?php
/***************************************************************
* Copyright notice
*
* (c) 2009-2010 Saskia Metzler <saskia@merlin.owl.de>
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

require_once(t3lib_extMgm::extPath('realty') . 'lib/tx_realty_constants.php');

/**
 * Unit tests for the tx_realty_pi1_PriceView class in the "realty"
 * extension.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class tx_realty_FrontEnd_PriceViewTest extends tx_phpunit_testcase {
	/**
	 * @var tx_realty_pi1_PriceView
	 */
	private $fixture;

	/**
	 * @var tx_oelib_testingFramework
	 */
	private $testingFramework;

	public function setUp() {
		$this->testingFramework = new tx_oelib_testingFramework('tx_realty');
		$this->testingFramework->createFakeFrontEnd();

		$this->fixture = new tx_realty_pi1_PriceView(
			array(
				'templateFile' => 'EXT:realty/pi1/tx_realty_pi1.tpl.htm',
				'priceOnlyIfAvailable' => FALSE,
			),
			$GLOBALS['TSFE']->cObj
		);
	}

	public function tearDown() {
		$this->testingFramework->cleanUp();

		$this->fixture->__destruct();
		unset($this->fixture, $this->testingFramework);
	}


	///////////////////////////
	// Testing the price view
	///////////////////////////

	public function testRenderReturnsNonEmptyResultForShowUidOfExistingRecord() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array(
				'object_type' => REALTY_FOR_RENTING,
				'rent_excluding_bills' => '123',
		));

		$this->assertNotEquals(
			'',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	public function testRenderReturnsEmptyResultForShowUidOfObjectWithInvalidObjectType() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array('object_type' => 2));

		$this->assertEquals(
			'',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	public function testRenderReturnsNoUnreplacedMarkersWhileTheResultIsNonEmpty() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array(
				'object_type' => REALTY_FOR_RENTING,
				'rent_excluding_bills' => '123',
		));

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

	public function testRenderReturnsTheRealtyObjectsBuyingPriceForObjectForSale() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array(
				'object_type' => REALTY_FOR_SALE,
				'buying_price' => '123',
		));

		$this->assertContains(
			'123',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	/**
	 * @test
	 */
	public function renderWithPriceOnlyIfAvailableForVacantObjectForSaleReturnsBuyingPrice() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array(
				'object_type' => REALTY_FOR_SALE,
				'buying_price' => '123',
				'status' => tx_realty_Model_RealtyObject::STATUS_VACANT
		));

		$this->fixture->setConfigurationValue('priceOnlyIfAvailable', TRUE);

		$this->assertContains(
			'123',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	/**
	 * @test
	 */
	public function renderWithPriceOnlyIfAvailableForReservedObjectForSaleReturnsBuyingPrice() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array(
				'object_type' => REALTY_FOR_SALE,
				'buying_price' => '123',
				'status' => tx_realty_Model_RealtyObject::STATUS_RESERVED
		));

		$this->fixture->setConfigurationValue('priceOnlyIfAvailable', TRUE);

		$this->assertContains(
			'123',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	/**
	 * @test
	 */
	public function renderWithPriceOnlyIfAvailableForSoldObjectForSaleReturnsEmptyString() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array(
				'object_type' => REALTY_FOR_SALE,
				'buying_price' => '123',
				'status' => tx_realty_Model_RealtyObject::STATUS_SOLD
		));

		$this->fixture->setConfigurationValue('priceOnlyIfAvailable', TRUE);

		$this->assertEquals(
			'',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	public function testRenderNotReturnsTheRealtyObjectsBuyingPriceForObjectForRenting() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array(
				'object_type' => REALTY_FOR_RENTING,
				'buying_price' => '123',
		));

		$this->assertNotContains(
			'123',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	public function testRenderReturnsTheRealtyObjectsRentForObjectForRenting() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array(
				'object_type' => REALTY_FOR_RENTING,
				'rent_excluding_bills' => '123',
		));

		$this->assertContains(
			'123',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	/**
	 * @test
	 */
	public function renderWithPriceOnlyIfAvailableForVacantObjectForRentReturnsBuyingPrice() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array(
				'object_type' => REALTY_FOR_RENTING,
				'rent_excluding_bills' => '123',
				'status' => tx_realty_Model_RealtyObject::STATUS_VACANT
		));

		$this->fixture->setConfigurationValue('priceOnlyIfAvailable', TRUE);

		$this->assertContains(
			'123',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	/**
	 * @test
	 */
	public function renderWithPriceOnlyIfAvailableForReservedObjectForRentReturnsBuyingPrice() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array(
				'object_type' => REALTY_FOR_RENTING,
				'rent_excluding_bills' => '123',
				'status' => tx_realty_Model_RealtyObject::STATUS_RESERVED
		));

		$this->fixture->setConfigurationValue('priceOnlyIfAvailable', TRUE);

		$this->assertContains(
			'123',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	/**
	 * @test
	 */
	public function renderWithPriceOnlyIfAvailableForRentedObjectForRentReturnsEmptyString() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array(
				'object_type' => REALTY_FOR_RENTING,
				'rent_excluding_bills' => '123',
				'status' => tx_realty_Model_RealtyObject::STATUS_RENTED
		));

		$this->fixture->setConfigurationValue('priceOnlyIfAvailable', TRUE);

		$this->assertEquals(
			'',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	public function testRenderNotReturnsTheRealtyObjectsRentForObjectForSale() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array(
				'object_type' => REALTY_FOR_SALE,
				'rent_excluding_bills' => '123',
		));

		$this->assertNotContains(
			'123',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	public function testRenderReturnsEmptyResultForEmptyBuyingPriceOfObjectForSale() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array(
				'object_type' => REALTY_FOR_SALE,
				'buying_price' => '',
		));

		$this->assertEquals(
			'',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}
}
?>