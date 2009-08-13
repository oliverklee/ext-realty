<?php
/***************************************************************
* Copyright notice
*
* (c) 2009 Saskia Metzler <saskia@merlin.owl.de>
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
 * Unit tests for the tx_realty_pi1_OverviewTableView class in the 'realty'
 * extension.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 */
class tx_realty_pi1_OverviewTableView_testcase extends tx_phpunit_testcase {
	/**
	 * @var tx_realty_pi1_OverviewTableView
	 */
	private $fixture;

	/**
	 * @var tx_oelib_testingFramework
	 */
	private $testingFramework;

	public function setUp() {
		$this->testingFramework = new tx_oelib_testingFramework('tx_realty');
		$this->testingFramework->createFakeFrontEnd();

		$this->fixture = new tx_realty_pi1_OverviewTableView(
			array('templateFile' => 'EXT:realty/pi1/tx_realty_pi1.tpl.htm'),
			$GLOBALS['TSFE']->cObj
		);
		$this->fixture->setConfigurationValue('fieldsInSingleViewTable', '');
	}

	public function tearDown() {
		$this->testingFramework->cleanUp();

		$this->fixture->__destruct();
		unset($this->fixture, $this->testingFramework);
	}


	////////////////////////////////////
	// Testing the overview table view
	////////////////////////////////////

	public function testRenderReturnsNonEmptyResultForShowUidOfExistingRecord() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array('object_number' => '12345'));

		$this->assertNotEquals(
			'',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	public function testRenderReturnsNoUnreplacedMarkersWhileTheResultIsNonEmpty() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array('object_number' => '12345'));

		$result = $this->fixture->render(array('showUid' => $realtyObject->getUid()));

		$this->assertNotEquals(
			'',
			$result
		);
		$this->assertNotContains(
			'###',
			$result
		);
	}

	public function testRenderReturnsTheRealtyObjectsObjectNumberForValidRealtyObject() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array('object_number' => '12345'));

		$this->assertContains(
			'12345',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	public function testRenderReturnsTheRealtyObjectsTitleHtmlspecialcharedForValidRealtyObject() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array('object_number' => '12345</br>'));

		$this->assertContains(
			htmlspecialchars('12345</br>'),
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	public function testRenderReturnsEmptyResultForValidRealtyObjectWithoutData() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array());

		$this->assertEquals(
			'',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}


	///////////////////////////////////////////////
	// Testing the contents of the overview table
	///////////////////////////////////////////////

	public function testRenderReturnsHasAirConditioningRowForTrue() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array('has_air_conditioning' => 1));

		$this->fixture->setConfigurationValue(
			'fieldsInSingleViewTable', 'has_air_conditioning'
		);

		$this->assertContains(
			$this->fixture->translate('label_has_air_conditioning'),
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	public function testRenderNotReturnsHasAirConditioningRowForFalse() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array('has_air_conditioning' => 0));

		$this->fixture->setConfigurationValue(
			'fieldsInSingleViewTable', 'has_air_conditioning'
		);

		$this->assertNotContains(
			$this->fixture->translate('label_has_air_conditioning'),
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	public function testRenderReturnsHasPoolRowForTrue() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array('has_pool' => 1));

		$this->fixture->setConfigurationValue(
			'fieldsInSingleViewTable', 'has_pool'
		);

		$this->assertContains(
			$this->fixture->translate('label_has_pool'),
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	public function testRenderNotReturnsHasPoolRowForFalse() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array('has_pool' => 0));

		$this->fixture->setConfigurationValue(
			'fieldsInSingleViewTable', 'has_pool'
		);

		$this->assertNotContains(
			$this->fixture->translate('label_has_pool'),
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	public function testRenderReturnsHasCommunityPoolRowForTrue() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array('has_community_pool' => 1));

		$this->fixture->setConfigurationValue(
			'fieldsInSingleViewTable', 'has_community_pool'
		);

		$this->assertContains(
			$this->fixture->translate('label_has_community_pool'),
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	public function testRenderNotReturnsHasCommunityPoolRowForFalse() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array('has_community_pool' => 0));

		$this->fixture->setConfigurationValue(
			'fieldsInSingleViewTable', 'has_community_pool'
		);

		$this->assertNotContains(
			$this->fixture->translate('label_has_community_pool'),
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	public function testRenderReturnsTheLabelForStateIfAValidStateIsSet() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array('state' => 8));

		$this->fixture->setConfigurationValue('fieldsInSingleViewTable', 'state');

		$this->assertContains(
			$this->fixture->translate('label_state'),
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	public function testRenderReturnsTheStateIfAValidStateIsSet() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array('state' => 8));

		$this->fixture->setConfigurationValue('fieldsInSingleViewTable', 'state');

		$this->assertContains(
			$this->fixture->translate('label_state.8'),
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	public function testRenderNotReturnsTheLabelForStateIfNoStateIsSet() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array('state' => 0));

		$this->fixture->setConfigurationValue('fieldsInSingleViewTable', 'state');

		$this->assertNotContains(
			$this->fixture->translate('label_state'),
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	public function testRenderNotReturnsTheLabelForStateIfTheStateIsInvalid() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array('state' => 10000000));

		$this->fixture->setConfigurationValue('fieldsInSingleViewTable', 'state');

		$this->assertNotContains(
			$this->fixture->translate('label_state'),
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	public function testRenderReturnsTheLabelForHeatingTypeIfOneValidHeatingTypeIsSet() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array('heating_type' => '1'));

		$this->fixture->setConfigurationValue('fieldsInSingleViewTable', 'heating_type');

		$this->assertContains(
			$this->fixture->translate('label_heating_type'),
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	public function testRenderReturnsTheHeatingTypeIfOneValidHeatingTypeIsSet() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array('heating_type' => '1'));

		$this->fixture->setConfigurationValue('fieldsInSingleViewTable', 'heating_type');

		$this->assertContains(
			$this->fixture->translate('label_heating_type.1'),
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	public function testRenderReturnsAHeatingTypeListIfMultipleValidHeatingTypesAreSet() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array('heating_type' => '1,3,4'));

		$this->fixture->setConfigurationValue('fieldsInSingleViewTable', 'heating_type');

		$this->assertContains(
			$this->fixture->translate('label_heating_type.1') . ', ' .
				$this->fixture->translate('label_heating_type.3') . ', ' .
				$this->fixture->translate('label_heating_type.4'),
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	public function testRenderNotReturnsTheHeatingTypeLabelIfOnlyAnInvalidHeatingTypeIsSet() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array('heating_type' => '100'));

		$this->fixture->setConfigurationValue('fieldsInSingleViewTable', 'heating_type');

		$this->assertNotContains(
			$this->fixture->translate('label_heating_type'),
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}
}
?>