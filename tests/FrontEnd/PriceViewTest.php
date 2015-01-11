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
 * @author Saskia Metzler <saskia@merlin.owl.de>
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class tx_realty_FrontEnd_PriceViewTest extends Tx_Phpunit_TestCase {
	/**
	 * @var tx_realty_pi1_PriceView
	 */
	private $fixture = NULL;

	/**
	 * @var Tx_Oelib_TestingFramework
	 */
	private $testingFramework = NULL;

	protected function setUp() {
		$this->testingFramework = new Tx_Oelib_TestingFramework('tx_realty');
		$this->testingFramework->createFakeFrontEnd();

		/** @var tslib_fe $frontEndController */
		$frontEndController = $GLOBALS['TSFE'];
		$this->fixture = new tx_realty_pi1_PriceView(
			array(
				'templateFile' => 'EXT:realty/pi1/tx_realty_pi1.tpl.htm',
				'currencyUnit' => 'EUR',
				'priceOnlyIfAvailable' => FALSE,
			),
			$frontEndController->cObj
		);
	}

	protected function tearDown() {
		$this->testingFramework->cleanUp();
	}


	///////////////////////////
	// Testing the price view
	///////////////////////////

	/**
	 * @test
	 */
	public function renderReturnsNonEmptyResultForShowUidOfExistingRecord() {
		$realtyObject = Tx_Oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array(
				'object_type' => tx_realty_Model_RealtyObject::TYPE_FOR_RENT,
				'rent_excluding_bills' => '123',
		));

		$this->assertNotEquals(
			'',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	/**
	 * @test
	 */
	public function renderReturnsEmptyResultForShowUidOfObjectWithInvalidObjectType() {
		$realtyObject = Tx_Oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array('object_type' => 2));

		$this->assertEquals(
			'',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	/**
	 * @test
	 */
	public function renderReturnsNoUnreplacedMarkersWhileTheResultIsNonEmpty() {
		$realtyObject = Tx_Oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array(
				'object_type' => tx_realty_Model_RealtyObject::TYPE_FOR_RENT,
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

	/**
	 * @test
	 */
	public function renderReturnsTheRealtyObjectsBuyingPriceForObjectForSale() {
		$realtyObject = Tx_Oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array(
				'object_type' => tx_realty_Model_RealtyObject::TYPE_FOR_SALE,
				'buying_price' => '123',
		));

		$this->assertContains(
			'&euro; 123',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	/**
	 * @test
	 */
	public function renderWithPriceOnlyIfAvailableForVacantObjectForSaleReturnsBuyingPrice() {
		$realtyObject = Tx_Oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array(
				'object_type' => tx_realty_Model_RealtyObject::TYPE_FOR_SALE,
				'buying_price' => '123',
				'status' => tx_realty_Model_RealtyObject::STATUS_VACANT
		));

		$this->fixture->setConfigurationValue('priceOnlyIfAvailable', TRUE);

		$this->assertContains(
			'&euro; 123',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	/**
	 * @test
	 */
	public function renderWithPriceOnlyIfAvailableForReservedObjectForSaleReturnsBuyingPrice() {
		$realtyObject = Tx_Oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array(
				'object_type' => tx_realty_Model_RealtyObject::TYPE_FOR_SALE,
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
		$realtyObject = Tx_Oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array(
				'object_type' => tx_realty_Model_RealtyObject::TYPE_FOR_SALE,
				'buying_price' => '123',
				'status' => tx_realty_Model_RealtyObject::STATUS_SOLD
		));

		$this->fixture->setConfigurationValue('priceOnlyIfAvailable', TRUE);

		$this->assertEquals(
			'',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	/**
	 * @test
	 */
	public function renderNotReturnsTheRealtyObjectsBuyingPriceForObjectForRenting() {
		$realtyObject = Tx_Oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array(
				'object_type' => tx_realty_Model_RealtyObject::TYPE_FOR_RENT,
				'buying_price' => '123',
		));

		$this->assertNotContains(
			'123',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	/**
	 * @test
	 */
	public function renderReturnsTheRealtyObjectsRentForObjectForRenting() {
		$realtyObject = Tx_Oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array(
				'object_type' => tx_realty_Model_RealtyObject::TYPE_FOR_RENT,
				'rent_excluding_bills' => '123',
		));

		$this->assertContains(
			'&euro; 123',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	/**
	 * @test
	 */
	public function renderWithPriceOnlyIfAvailableForVacantObjectForRentReturnsBuyingPrice() {
		$realtyObject = Tx_Oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array(
				'object_type' => tx_realty_Model_RealtyObject::TYPE_FOR_RENT,
				'rent_excluding_bills' => '123',
				'status' => tx_realty_Model_RealtyObject::STATUS_VACANT
		));

		$this->fixture->setConfigurationValue('priceOnlyIfAvailable', TRUE);

		$this->assertContains(
			'&euro; 123',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	/**
	 * @test
	 */
	public function renderWithPriceOnlyIfAvailableForReservedObjectForRentReturnsBuyingPrice() {
		$realtyObject = Tx_Oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array(
				'object_type' => tx_realty_Model_RealtyObject::TYPE_FOR_RENT,
				'rent_excluding_bills' => '123',
				'status' => tx_realty_Model_RealtyObject::STATUS_RESERVED
		));

		$this->fixture->setConfigurationValue('priceOnlyIfAvailable', TRUE);

		$this->assertContains(
			'&euro; 123',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	/**
	 * @test
	 */
	public function renderWithPriceOnlyIfAvailableForRentedObjectForRentReturnsEmptyString() {
		$realtyObject = Tx_Oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array(
				'object_type' => tx_realty_Model_RealtyObject::TYPE_FOR_RENT,
				'rent_excluding_bills' => '123',
				'status' => tx_realty_Model_RealtyObject::STATUS_RENTED
		));

		$this->fixture->setConfigurationValue('priceOnlyIfAvailable', TRUE);

		$this->assertEquals(
			'',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	/**
	 * @test
	 */
	public function renderNotReturnsTheRealtyObjectsRentForObjectForSale() {
		$realtyObject = Tx_Oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array(
				'object_type' => tx_realty_Model_RealtyObject::TYPE_FOR_SALE,
				'rent_excluding_bills' => '123',
		));

		$this->assertNotContains(
			'&euro; 123',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	/**
	 * @test
	 */
	public function renderReturnsEmptyResultForEmptyBuyingPriceOfObjectForSale() {
		$realtyObject = Tx_Oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array(
				'object_type' => tx_realty_Model_RealtyObject::TYPE_FOR_SALE,
				'buying_price' => '',
		));

		$this->assertEquals(
			'',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}
}