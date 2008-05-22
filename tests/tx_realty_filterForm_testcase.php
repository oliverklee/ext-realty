<?php
/***************************************************************
* Copyright notice
*
* (c) 2008 Saskia Metzler <saskia@merlin.owl.de>
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
 * Unit tests for the tx_realty_filterForm class in the 'realty' extension.
 *
 * @package		TYPO3
 * @subpackage	tx_realty
 *
 * @author		Saskia Metzler <saskia@merlin.owl.de>
 */

require_once(t3lib_extMgm::extPath('oelib').'class.tx_oelib_testingFramework.php');

require_once(t3lib_extMgm::extPath('realty').'pi1/class.tx_realty_pi1.php');
require_once(t3lib_extMgm::extPath('realty').'pi1/class.tx_realty_filterForm.php');

class tx_realty_filterForm_testcase extends tx_phpunit_testcase {
	/** filter form object to be tested */
	private $fixture;
	/** instance of tx_oelib_testingFramework */
	private $testingFramework;
	/** instance of tx_realty_pi1 */
	private $pi1;

	public function setUp() {
		// Bolsters up the fake front end.
		$GLOBALS['TSFE']->tmpl = t3lib_div::makeInstance('t3lib_tsparser_ext');
		$GLOBALS['TSFE']->tmpl->flattenSetup(array(), '', false);
		$GLOBALS['TSFE']->tmpl->init();
		$GLOBALS['TSFE']->tmpl->getCurrentPageData();

		$this->testingFramework = new tx_oelib_testingFramework('tx_realty');

		$this->pi1 = new tx_realty_pi1();
		$this->pi1->init(array('templateFile' => 'EXT:realty/pi1/tx_realty_pi1.tpl.htm'));
		$this->pi1->getTemplateCode();
		$this->pi1->setLabels();

		$this->fixture = new tx_realty_filterForm($this->pi1);
	}

	public function tearDown() {
		$this->testingFramework->cleanUp();
		unset($this->fixture, $this->pi1, $this->testingFramework);
	}


	///////////////////////
	// Utility functions.
	///////////////////////

	/**
	 * Sets the configuration for price ranges.
	 *
	 * @param	string		price ranges, may be empty
	 */
	private function setPriceRangeConfiguration($ranges) {
		$this->pi1->setConfigurationValue('priceRangesForFilterForm', $ranges);
		$this->fixture->setupPriceRanges();
	}


	///////////////////////////
	// Testing the rendering.
	///////////////////////////

	public function testFilterFormHasSubmitButton() {
		$this->assertContains(
			$this->pi1->translate('label_submit'),
			$this->fixture->render(array())
		);
	}

	public function testFilterFormLinksToConfiguredTargetPage() {
		$pageUid = $this->testingFramework->createFrontEndPage();
		$this->pi1->setConfigurationValue('filterTargetPID', $pageUid);

		$this->assertContains(
			(string) $pageUid,
			$this->fixture->render(array())
		);
	}


	//////////////////////////////
	// Testing the price filter.
	//////////////////////////////

	public function testFilterFormHasNoPricesSelectboxForUnconfiguredFilter() {
		$this->assertNotContains(
			'<select',
			$this->fixture->render(array())
		);
	}

	public function testFilterFormHasPricesSelectboxForConfiguredFilterOptions() {
		$this->setPriceRangeConfiguration('1-100');

		$this->assertContains(
			'<select',
			$this->fixture->render(array())
		);
	}

	public function testPriceRangeIsDisplayedWithCurrency() {
		$this->pi1->setConfigurationValue('currencyUnit', '&euro;');
		$this->setPriceRangeConfiguration('1-100');

		$this->assertContains(
			'&euro;',
			$this->fixture->render(array())
		);
	}

	public function testOptionWithLowerAndUpperLimitCanBeDisplayed() {
		$this->setPriceRangeConfiguration('1-100');

		$this->assertContains(
			'1 ' . $this->pi1->translate('label_to') . ' 100',
			$this->fixture->render(array())
		);
	}

	public function testOptionWithLowerLimitCanBeDisplayed() {
		$this->setPriceRangeConfiguration('1-');

		$this->assertContains(
			$this->pi1->translate('label_greater_than') . ' 1',
			$this->fixture->render(array())
		);
	}

	public function testOptionWithUpperLimitCanBeDisplayed() {
		$this->setPriceRangeConfiguration('-100');

		$this->assertContains(
			$this->pi1->translate('label_less_than') . ' 100',
			$this->fixture->render(array())
		);
	}


	///////////////////////////////////////////////////
	// Testing the price filter's WHERE clause parts.
	///////////////////////////////////////////////////

	public function testWhereClauseOnlyForLowerLimitCanBeCreated() {
		$this->setPriceRangeConfiguration('1-');

		$this->assertEquals(
			' AND ((' . REALTY_TABLE_OBJECTS . '.rent_excluding_bills >= 1) ' .
				'OR (' . REALTY_TABLE_OBJECTS . '.buying_price >= 1))',
			$this->fixture->getWhereClausePart(array('priceRange' => 1))
		);
	}

	public function testWhereClauseOnlyForUpperLimitCanBeCreated() {
		$this->setPriceRangeConfiguration('-10');

		$this->assertEquals(
			' AND ((' . REALTY_TABLE_OBJECTS . '.rent_excluding_bills > 0 ' .
				'AND ' . REALTY_TABLE_OBJECTS . '.rent_excluding_bills <= 10) ' .
				'OR (' . REALTY_TABLE_OBJECTS . '.buying_price > 0 ' .
				'AND ' . REALTY_TABLE_OBJECTS . '.buying_price <= 10))',
			$this->fixture->getWhereClausePart(array('priceRange' => 1))
		);
	}

	public function testWhereClauseForUpperPlusLowerLimitCanBeCreated() {
		$this->setPriceRangeConfiguration('1-10');

		$this->assertEquals(
			' AND ((' . REALTY_TABLE_OBJECTS . '.rent_excluding_bills >= 1 ' .
				'AND ' . REALTY_TABLE_OBJECTS . '.rent_excluding_bills <= 10) ' .
				'OR (' . REALTY_TABLE_OBJECTS . '.buying_price >= 1 ' .
				'AND ' . REALTY_TABLE_OBJECTS . '.buying_price <= 10))',
			$this->fixture->getWhereClausePart(array('priceRange' => 1))
		);
	}

	public function testWhereClauseIsEmptyForInvalidNumericKey() {
		$this->setPriceRangeConfiguration('-100');

		$this->assertEquals(
			'',
			$this->fixture->getWhereClausePart(array('priceRange' => 2))
		);
	}

	public function testWhereClauseIsEmptyForInvalidStringKey() {
		$this->setPriceRangeConfiguration('-100');

		$this->assertEquals(
			'',
			$this->fixture->getWhereClausePart(array('priceRange' => 'foo'))
		);
	}
}
?>
