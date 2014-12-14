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
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class tx_realty_Ajax_DistrictSelectorTest extends tx_phpunit_testcase {
	/**
	 * @var tx_oelib_testingFramework
	 */
	private $testingFramework;

	protected function setUp() {
		$this->testingFramework = new tx_oelib_testingFramework('tx_realty');
	}

	protected function tearDown() {
		$this->testingFramework->cleanUp();
		unset($this->testingFramework);
	}


	////////////////////////////
	// Tests concerning render
	////////////////////////////

	/**
	 * @test
	 */
	public function renderForExistingCityPrependsEmptyOption() {
		$cityUid = $this->testingFramework->createRecord('tx_realty_cities');

		$this->assertContains(
			'<option value="0">&nbsp;</option>',
			tx_realty_Ajax_DistrictSelector::render($cityUid)
		);
	}

	/**
	 * @test
	 */
	public function renderForInexistentCityPrependsEmptyOption() {
		$cityUid = $this->testingFramework->getAutoIncrement('tx_realty_cities');

		$this->assertContains(
			'<option value="0">&nbsp;</option>',
			tx_realty_Ajax_DistrictSelector::render($cityUid)
		);
	}

	/**
	 * @test
	 */
	public function renderShowsNameOfDistrictOfGivenCity() {
		$cityUid = $this->testingFramework->createRecord('tx_realty_cities');
		$this->testingFramework->createRecord(
			'tx_realty_districts',
			array('title' => 'Kreuzberg', 'city' => $cityUid)
		);

		$this->assertContains(
			'Kreuzberg',
			tx_realty_Ajax_DistrictSelector::render($cityUid)
		);
	}

	/**
	 * @test
	 */
	public function renderShowsHtmlspecialcharedNameOfDistrictOfGivenCity() {
		$cityUid = $this->testingFramework->createRecord('tx_realty_cities');
		$this->testingFramework->createRecord(
			'tx_realty_districts',
			array('title' => 'A & B', 'city' => $cityUid)
		);

		$this->assertContains(
			'A &amp; B',
			tx_realty_Ajax_DistrictSelector::render($cityUid)
		);
	}

	/**
	 * @test
	 */
	public function renderContainsUidOfDistrictOfGivenCityAsValue() {
		$cityUid = $this->testingFramework->createRecord('tx_realty_cities');
		$districtUid = $this->testingFramework->createRecord(
			'tx_realty_districts', array('city' => $cityUid)
		);

		$this->assertContains(
			'<option value="' . $districtUid . '">',
			tx_realty_Ajax_DistrictSelector::render($cityUid)
		);
	}

	/**
	 * @test
	 */
	public function renderCanContainUidsOfTwoDistrictsOfGivenCity() {
		$cityUid = $this->testingFramework->createRecord('tx_realty_cities');
		$districtUid1 = $this->testingFramework->createRecord(
			'tx_realty_districts', array('city' => $cityUid)
		);
		$districtUid2 = $this->testingFramework->createRecord(
			'tx_realty_districts', array('city' => $cityUid)
		);

		$output = tx_realty_Ajax_DistrictSelector::render($cityUid);

		$this->assertContains(
			'<option value="' . $districtUid1 . '">',
			$output
		);
		$this->assertContains(
			'<option value="' . $districtUid2 . '">',
			$output
		);
	}

	/**
	 * @test
	 */
	public function renderContainsUidOfDistrictWithoutCity() {
		$cityUid = $this->testingFramework->createRecord('tx_realty_cities');
		$districtUid = $this->testingFramework->createRecord(
			'tx_realty_districts'
		);

		$this->assertContains(
			'value="' . $districtUid . '"',
			tx_realty_Ajax_DistrictSelector::render($cityUid)
		);
	}

	/**
	 * @test
	 */
	public function renderNotContainsUidOfDistrictOfOtherCity() {
		$cityUid = $this->testingFramework->createRecord('tx_realty_cities');
		$otherCityUid = $this->testingFramework->createRecord('tx_realty_cities');
		$districtUid = $this->testingFramework->createRecord(
			'tx_realty_districts', array('city' => $otherCityUid)
		);

		$this->assertNotContains(
			'value="' . $districtUid . '"',
			tx_realty_Ajax_DistrictSelector::render($cityUid)
		);
	}


	/////////////////////////////////////////////////////////
	// Tests concerning render with $showWithNumbers = TRUE
	/////////////////////////////////////////////////////////

	/**
	 * @test
	 */
	public function renderWithCountTrueContainsNumberOfMatchesOfDistrict() {
		$cityUid = $this->testingFramework->createRecord('tx_realty_cities');
		$districtUid = $this->testingFramework->createRecord(
			'tx_realty_districts', array('city' => $cityUid, 'title' => 'Beuel')
		);
		$this->testingFramework->createRecord(
			'tx_realty_objects', array('district' => $districtUid)
		);
		$this->testingFramework->createRecord(
			'tx_realty_objects', array('district' => $districtUid)
		);

		$this->assertContains(
			'Beuel (2)',
			tx_realty_Ajax_DistrictSelector::render($cityUid, TRUE)
		);
	}

	/**
	 * @test
	 */
	public function renderWithCountTrueNotContainsDistrictWithoutMatches() {
		$cityUid = $this->testingFramework->createRecord('tx_realty_cities');
		$districtUid = $this->testingFramework->createRecord(
			'tx_realty_districts', array('city' => $cityUid)
		);

		$this->assertNotContains(
			'value="' . $districtUid . '"',
			tx_realty_Ajax_DistrictSelector::render($cityUid, TRUE)
		);
	}


	//////////////////////////////////////////////////////////
	// Tests concerning render with $showWithNumbers = FALSE
	//////////////////////////////////////////////////////////

	/**
	 * @test
	 */
	public function renderWithCountFalseNotContainsNumberOfMatchesOfDistrict() {
		$cityUid = $this->testingFramework->createRecord('tx_realty_cities');
		$districtUid = $this->testingFramework->createRecord(
			'tx_realty_districts', array('city' => $cityUid, 'title' => 'Beuel')
		);
		$this->testingFramework->createRecord(
			'tx_realty_objects', array('district' => $districtUid)
		);
		$this->testingFramework->createRecord(
			'tx_realty_objects', array('district' => $districtUid)
		);

		$this->assertNotContains(
			'Beuel (2)',
			tx_realty_Ajax_DistrictSelector::render($cityUid, FALSE)
		);
	}

	/**
	 * @test
	 */
	public function renderWithCountFalseContainsDistrictWithoutMatches() {
		$cityUid = $this->testingFramework->createRecord('tx_realty_cities');
		$districtUid = $this->testingFramework->createRecord(
			'tx_realty_districts', array('city' => $cityUid)
		);

		$this->assertContains(
			'value="' . $districtUid . '"',
			tx_realty_Ajax_DistrictSelector::render($cityUid, FALSE)
		);
	}
}