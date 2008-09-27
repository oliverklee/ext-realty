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

require_once(t3lib_extMgm::extPath('oelib') . 'class.tx_oelib_testingFramework.php');

require_once(t3lib_extMgm::extPath('realty') . 'pi1/class.tx_realty_pi1.php');
require_once(t3lib_extMgm::extPath('realty') . 'pi1/class.tx_realty_filterForm.php');

/**
 * Unit tests for the tx_realty_filterForm class in the 'realty' extension.
 *
 * @package		TYPO3
 * @subpackage	tx_realty
 *
 * @author		Saskia Metzler <saskia@merlin.owl.de>
 */
class tx_realty_filterForm_testcase extends tx_phpunit_testcase {
	/** @var	tx_realty_filterForm */
	private $fixture;
	/** @var	tx_oelib_testingFramework */
	private $testingFramework;
	/** @var	tx_realty_pi1 */
	private $pi1;

	public function setUp() {
		$this->testingFramework = new tx_oelib_testingFramework('tx_realty');
		$this->testingFramework->createFakeFrontEnd();

		$this->pi1 = new tx_realty_pi1();
		$this->pi1->init(array('templateFile' => 'EXT:realty/pi1/tx_realty_pi1.tpl.htm'));
		$this->pi1->getTemplateCode();
		$this->pi1->setLabels();
		$this->pi1->setConfigurationValue('showSiteSearchInFilterForm', 'show');

		$this->fixture = new tx_realty_filterForm($this->pi1);
	}

	public function tearDown() {
		$this->testingFramework->cleanUp();
		unset($this->fixture, $this->pi1, $this->testingFramework);
	}


	///////////////////////////
	// Testing the rendering.
	///////////////////////////

	public function testFilterFormHasSubmitButton() {
		$this->assertContains(
			$this->pi1->translate('label_search'),
			$this->fixture->render(array())
		);
	}

	public function testFilterFormLinksToConfiguredTargetPage() {
		$pageUid = $this->testingFramework->createFrontEndPage();
		$this->pi1->setConfigurationValue('filterTargetPID', $pageUid);

		$this->assertContains(
			'?id=' . $pageUid,
			$this->fixture->render(array())
		);
	}


	//////////////////////////////////////////////
	// Testing the rendering of the site search.
	//////////////////////////////////////////////

	public function testFilterFormHasSiteSearchInputIfEnabledByConfiguration() {
		// "showSiteSearchInFilterForm" is set to "show" during setup().

		$this->assertContains(
			'id="tx_realty_pi1-site"',
			$this->fixture->render(array())
		);
	}

	public function testFilterFormHasNoSiteSearchInputIfDisabledByConfiguration() {
		$this->pi1->setConfigurationValue('showSiteSearchInFilterForm', 'hide');

		$this->assertNotContains(
			'id="tx_realty_pi1-site"',
			$this->fixture->render(array())
		);
	}


	///////////////////////////////////////////////
	// Testing the rendering of the price filter.
	///////////////////////////////////////////////

	public function testFilterFormHasNoPricesSelectboxForUnconfiguredFilter() {
		$this->assertNotContains(
			'<select',
			$this->fixture->render(array())
		);
	}

	public function testFilterFormHasPricesSelectboxForConfiguredFilterOptions() {
		$this->pi1->setConfigurationValue('priceRangesForFilterForm', '1-100');

		$this->assertContains(
			'<select',
			$this->fixture->render(array())
		);
	}

	public function testPriceRangeIsDisplayedWithCurrency() {
		$this->pi1->setConfigurationValue('currencyUnit', '&euro;');
		$this->pi1->setConfigurationValue('priceRangesForFilterForm', '1-100');

		$this->assertContains(
			'&euro;',
			$this->fixture->render(array())
		);
	}

	public function testOptionWithLowerAndUpperPriceLimitCanBeDisplayed() {
		$this->pi1->setConfigurationValue('priceRangesForFilterForm', '1-100');

		$this->assertContains(
			'1 ' . $this->pi1->translate('label_to') . ' 100',
			$this->fixture->render(array())
		);
	}

	public function testOptionWithLowerPriceLimitCanBeDisplayed() {
		$this->pi1->setConfigurationValue('priceRangesForFilterForm', '1-');

		$this->assertContains(
			$this->pi1->translate('label_greater_than') . ' 1',
			$this->fixture->render(array())
		);
	}

	public function testOptionWithUpperPriceLimitCanBeDisplayed() {
		$this->pi1->setConfigurationValue('priceRangesForFilterForm', '-100');

		$this->assertContains(
			$this->pi1->translate('label_less_than') . ' 100',
			$this->fixture->render(array())
		);
	}


	///////////////////////////////////////////
	// Testing the rendering of the ID search
	///////////////////////////////////////////

	public function testSearchFormForUidSearchContainsUidSearchField() {
		$this->pi1->setConfigurationValue('showIdSearchInFilterForm', 'uid');

		$this->assertContains(
			'name="tx_realty_pi1[uid]"',
			$this->fixture->render(array())
		);
	}

	public function testSearchFormForUidSearchDoesNotContainObjectNumberSearchField() {
		$this->pi1->setConfigurationValue('showIdSearchInFilterForm', 'uid');

		$this->assertNotContains(
			'name="tx_realty_pi1[objectNumber]"',
			$this->fixture->render(array())
		);
	}


	public function testSearchFormForObjectNumberSearchContainsObjectNumberSearchField() {
		$this->pi1->setConfigurationValue(
			'showIdSearchInFilterForm',
			'objectNumber'
		);

		$this->assertContains(
			'name="tx_realty_pi1[objectNumber]"',
			$this->fixture->render(array())
		);
	}

	public function testSearchFormForObjectNumberSearchDoesNotContainUidSearchField() {
		$this->pi1->setConfigurationValue(
			'showIdSearchInFilterForm',
			'objectNumber'
		);

		$this->assertNotContains(
			'name="tx_realty_pi1[uid]"',
			$this->fixture->render(array())
		);
	}


	public function testSearchFormForEmptyConfigValueIsHidden() {
		$this->pi1->setConfigurationValue('showIdSearchInFilterForm', '');

		$this->assertNotContains(
			'id="tx_realty_pi1-idsearch"',
			$this->fixture->render(array())
		);
	}


	///////////////////////////////////////////////////
	// Testing the filter forms's WHERE clause parts.
	///////////////////////////////////////////////////

	public function testWhereClauseOnlyForLowerPriceLimitCanBeCreated() {
		$this->assertEquals(
			' AND ((' . REALTY_TABLE_OBJECTS . '.rent_excluding_bills >= 1) ' .
				'OR (' . REALTY_TABLE_OBJECTS . '.buying_price >= 1))',
			$this->fixture->getWhereClausePart(array('priceRange' => '1-'))
		);
	}

	public function testWhereClauseOnlyForUpperPriceLimitCanBeCreated() {
		$this->assertEquals(
			' AND ((' . REALTY_TABLE_OBJECTS . '.rent_excluding_bills > 0 ' .
				'AND ' . REALTY_TABLE_OBJECTS . '.rent_excluding_bills <= 10) ' .
				'OR (' . REALTY_TABLE_OBJECTS . '.buying_price > 0 ' .
				'AND ' . REALTY_TABLE_OBJECTS . '.buying_price <= 10) ' .
				'OR (' . REALTY_TABLE_OBJECTS . '.rent_excluding_bills = 0 ' .
				'AND ' . REALTY_TABLE_OBJECTS . '.buying_price = 0))',
			$this->fixture->getWhereClausePart(array('priceRange' => '-10'))
		);
	}

	public function testWhereClauseForUpperPlusLowerPriceLimitCanBeCreated() {
		$this->assertEquals(
			' AND ((' . REALTY_TABLE_OBJECTS . '.rent_excluding_bills >= 1 ' .
				'AND ' . REALTY_TABLE_OBJECTS . '.rent_excluding_bills <= 10) ' .
				'OR (' . REALTY_TABLE_OBJECTS . '.buying_price >= 1 ' .
				'AND ' . REALTY_TABLE_OBJECTS . '.buying_price <= 10))',
			$this->fixture->getWhereClausePart(array('priceRange' => '1-10'))
		);
	}

	public function testSearchStringForZipIsNotLongerThanTwoCharacters() {
		$this->assertContains(
			REALTY_TABLE_OBJECTS . '.zip LIKE "fo%"',
			$this->fixture->getWhereClausePart(array('site' => 'foo'))
		);
	}

	public function testSearchStringForSiteIsEscapedForLike() {
		$this->assertContains(
			REALTY_TABLE_CITIES . '.title LIKE "%f\\\%oo%")',
			$this->fixture->getWhereClausePart(array('site' => 'f%oo'))
		);
	}

	public function testWhereClauseForSiteSearchIsCanBeAppendedToPriceRangeWhereClause() {
		$this->assertEquals(
			' AND ((' . REALTY_TABLE_OBJECTS . '.rent_excluding_bills >= 1 ' .
				'AND ' . REALTY_TABLE_OBJECTS . '.rent_excluding_bills <= 10) ' .
				'OR (' . REALTY_TABLE_OBJECTS . '.buying_price >= 1 ' .
				'AND ' . REALTY_TABLE_OBJECTS . '.buying_price <= 10)) ' .
				'AND (' . REALTY_TABLE_OBJECTS . '.zip LIKE "fo%" ' .
				'OR ' . REALTY_TABLE_CITIES . '.title LIKE "%foo%")',
			$this->fixture->getWhereClausePart(
				array('site' => 'foo', 'priceRange' => '1-10')
			)
		);
	}

	public function testWhereClauseIsEmptyForInvalidNumericKeyForPriceRange() {
		$this->assertEquals(
			'',
			$this->fixture->getWhereClausePart(array('priceRange' => '-100-'))
		);
	}

	public function testWhereClauseIsEmptyForInvalidStringKeyForPriceRange() {
		$this->assertEquals(
			'',
			$this->fixture->getWhereClausePart(array('priceRange' => 'foo'))
		);
	}

	public function testWhereClauseIsEmptyForEmptySite() {
		$this->assertEquals(
			'',
			$this->fixture->getWhereClausePart(array('site' => ''))
		);
	}

	public function testWhereClauseForUidSearchWithNonZeroUidCanBeCreated() {
		$this->pi1->setConfigurationValue('showIdSearchInFilterForm', 'uid');

		$this->assertEquals(
			' AND ' . REALTY_TABLE_OBJECTS . '.uid=1',
			$this->fixture->getWhereClausePart(array('uid' => 1))
		);
	}

	public function testWhereClauseForUidSearchWithZeroUidIsEmpty() {
		$this->pi1->setConfigurationValue('showIdSearchInFilterForm', 'uid');

		$this->assertEquals(
			'',
			$this->fixture->getWhereClausePart(array('uid' => 0))
		);
	}

	public function testWhereClauseForObjectNumberSearchWithNonEmptyObjectNumberCanBeCreated() {
		$this->pi1->setConfigurationValue('showIdSearchInFilterForm', 'objectNumber');

		$this->assertEquals(
			' AND ' . REALTY_TABLE_OBJECTS . '.object_number="foo"',
			$this->fixture->getWhereClausePart(array('objectNumber' => 'foo'))
		);
	}

	public function testWhereClauseForObjectNumberSearchWithEmptyObjectNumberIsEmpty() {
		$this->pi1->setConfigurationValue('showIdSearchInFilterForm', 'objectNumber');

		$this->assertEquals(
			'',
			$this->fixture->getWhereClausePart(array('objectNumber' => ''))
		);
	}
}
?>