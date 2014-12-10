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
class tx_realty_FrontEnd_MapMarkerTest extends tx_phpunit_testcase {
	/**
	 * @var tx_realty_mapMarker
	 */
	private $fixture;

	public function setUp() {
		$this->fixture = new tx_realty_mapMarker();
	}

	public function tearDown() {
		unset($this->fixture);
	}

	/**
	 * @test
	 */
	public function renderReturnsEmptyStringIfSetCoordinatesHasNotBeenCalled() {
		$this->assertSame(
			'',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function renderContainsCoordinatesFromSetCoordinates() {
		$latitude = 50.734343;
		$longitude = 7.10211;
		$this->fixture->setCoordinates($latitude, $longitude);

		$this->assertContains(
			(string) $latitude,
			$this->fixture->render()
		);
		$this->assertContains(
			(string) $longitude,
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function renderAfterSetCoordinatesAddsMarkerToMap() {
		$this->fixture->setCoordinates(50.734343, 7.10211);

		$this->assertContains(
			'google.maps.LatLng(',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function renderAfterSetCoordinatesAndSetTitleIncludesTitle() {
		$this->fixture->setCoordinates(50.734343, 7.10211);
		$this->fixture->setTitle('Foo title');

		$this->assertContains(
			'title: "Foo title"',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function renderWithTitleEscapesQuotes() {
		$this->fixture->setCoordinates(50.734343, 7.10211);
		$this->fixture->setTitle('Foo " title');

		$this->assertContains(
			'title: "Foo \" title"',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function renderWithTitleStripsTags() {
		$this->fixture->setCoordinates(50.734343, 7.10211);
		$this->fixture->setTitle('Foo <script>title</script>');

		$this->assertContains(
			'title: "Foo title"',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function getCoordinatesReturnsEmptyStringForNoCoordinates() {
		$this->assertSame(
			'',
			$this->fixture->getCoordinates()
		);
	}

	/**
	 * @test
	 */
	public function getCoordinatesContainsCoordinatesFromSetCoordinates() {
		$latitude = 50.734343;
		$longitude = 7.10211;
		$this->fixture->setCoordinates($latitude, $longitude);

		$this->assertContains(
			(string) $latitude,
			$this->fixture->getCoordinates()
		);
		$this->assertContains(
			(string) $longitude,
			$this->fixture->getCoordinates()
		);
	}

	/**
	 * @test
	 */
	public function getInfoWindowContainsTextFromSetInfoWindowHtml() {
		$this->fixture->setCoordinates(50.734343, 7.10211);
		$this->fixture->setInfoWindowHtml('foo');

		$this->assertContains(
			'myInfoWindow.setContent(\'foo\')',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function getInfoWindowEscapesSingleQuotesFromSetInfoWindowHtml() {
		$this->fixture->setCoordinates(50.734343, 7.10211);
		$this->fixture->setInfoWindowHtml('foo\'bar');

		$this->assertContains(
			'myInfoWindow.setContent(\'foo\\\'bar\')',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function getInfoWindowEscapesBackslashesFromSetInfoWindowHtml() {
		$this->fixture->setCoordinates(50.734343, 7.10211);
		$this->fixture->setInfoWindowHtml('foo\\bar');

		$this->assertContains(
			'myInfoWindow.setContent(\'foo\\\\bar\')',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function getInfoWindowEscapesHtmlClosingTagsFromSetInfoWindowHtml() {
		$this->fixture->setCoordinates(50.734343, 7.10211);
		$this->fixture->setInfoWindowHtml('foo</bar');

		$this->assertContains(
			'myInfoWindow.setContent(\'foo<\\/bar\')',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function getInfoWindowDoesNotEscapeDoubleQuotesFromSetInfoWindowHtml() {
		$this->fixture->setCoordinates(50.734343, 7.10211);
		$this->fixture->setInfoWindowHtml('foo"bar');

		$this->assertContains(
			'myInfoWindow.setContent(\'foo"bar\')',
			$this->fixture->render()
		);
	}

	/**
	 * @test
	 */
	public function getInfoWindowContainsTextWithHtmlFromSetInfoWindowHtml() {
		$this->fixture->setCoordinates(50.734343, 7.10211);
		$this->fixture->setInfoWindowHtml('<strong>foo</strong><br />bar');

		$this->assertContains(
			'myInfoWindow.setContent(\'<strong>foo<\/strong><br />bar\')',
			$this->fixture->render()
		);
	}
}