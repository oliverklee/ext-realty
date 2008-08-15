<?php
/***************************************************************
* Copyright notice
*
* (c) 2008 Oliver Klee <typo3-coding@oliverklee.de>
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

require_once(t3lib_extMgm::extPath('realty') . 'lib/class.tx_realty_mapMarker.php');

/**
 * Unit tests for the tx_realty_mapMarker class in the 'realty' extension.
 *
 * @package		TYPO3
 * @subpackage	tx_realty
 *
 * @author		Oliver Klee <typo3-coding@oliverklee.de>
 */
class tx_realty_mapMarker_testcase extends tx_phpunit_testcase {
	/** @var	tx_realty_mapMarker */
	private $fixture;

	public function setUp() {
		$this->fixture = new tx_realty_mapMarker();
	}

	public function tearDown() {
		unset($this->fixture);
	}

	public function testRenderReturnsEmptyStringIfSetCoordinatesHasNotBeenCalled() {
		$this->assertEquals(
			'',
			$this->fixture->render()
		);
	}

	public function testSetCoordinatesThrowsExceptionForEmptyLatitude() {
		$this->setExpectedException(
			'Exception', 'The latitude must not be empty.'
		);

		$this->fixture->setCoordinates('', '7.10211');
	}

	public function testSetCoordinatesThrowsExceptionForEmptyLongitude() {
		$this->setExpectedException(
			'Exception', 'The longitude must not be empty.'
		);

		$this->fixture->setCoordinates('50.734343', '');
	}

	public function testRenderContainsCoordinatesFromSetCoordinates() {
		$latitude = '50.734343';
		$longitude = '7.10211';
		$this->fixture->setCoordinates($latitude, $longitude);

		$this->assertContains(
			$latitude,
			$this->fixture->render()
		);
		$this->assertContains(
			$longitude,
			$this->fixture->render()
		);
	}

	public function testRenderAfterSetCoordinatesAddsMarkerToMap() {
		$this->fixture->setCoordinates('50.734343', '7.10211');

		$this->assertContains(
			'.addOverlay(',
			$this->fixture->render()
		);
	}

	public function testRenderDoesNotCreateTitleIfNoTitleHasBeenSet() {
		$this->fixture->setCoordinates('50.734343', '7.10211');

		$this->assertNotContains(
			'title:',
			$this->fixture->render()
		);
	}

	public function testRenderAfterSetCoordinatesAndSetTitleIncludesTitle() {
		$this->fixture->setCoordinates('50.734343', '7.10211');
		$this->fixture->setTitle('Foo title');

		$this->assertContains(
			'title: "Foo title"',
			$this->fixture->render()
		);
	}

	public function testRenderWithTitleEscapesQuotes() {
		$this->fixture->setCoordinates('50.734343', '7.10211');
		$this->fixture->setTitle('Foo " title');

		$this->assertContains(
			'title: "Foo \" title"',
			$this->fixture->render()
		);
	}

	public function testRenderWithTitleStripsTags() {
		$this->fixture->setCoordinates('50.734343', '7.10211');
		$this->fixture->setTitle('Foo <script>title</script>');

		$this->assertContains(
			'title: "Foo title"',
			$this->fixture->render()
		);
	}

	public function testGetCoordinatesReturnsEmptyStringForEmptyCoordinates() {
		$this->assertEquals(
			'',
			$this->fixture->getCoordinates()
		);
	}

	public function testGetCoordinatesContainsCoordinatesFromSetCoordinates() {
		$latitude = '50.734343';
		$longitude = '7.10211';
		$this->fixture->setCoordinates($latitude, $longitude);

		$this->assertContains(
			$latitude,
			$this->fixture->getCoordinates()
		);
		$this->assertContains(
			$longitude,
			$this->fixture->getCoordinates()
		);
	}

	public function testRenderDoesNotContainInfoWindowIfNoInfoWindowTextHasBeenSet() {
		$this->fixture->setCoordinates('50.734343', '7.10211');

		$this->assertNotContains(
			'.bindInfoWindowHtml(',
			$this->fixture->render()
		);
	}

	public function testRenderDoesNotContainInfoWindowIfEmptyInfoWindowTextHasBeenSet() {
		$this->fixture->setCoordinates('50.734343', '7.10211');
		$this->fixture->setInfoWindowHtml('');

		$this->assertNotContains(
			'.bindInfoWindowHtml(',
			$this->fixture->render()
		);
	}

	public function testGetInfoWindowContainsTextFromSetInfoWindowHtml() {
		$this->fixture->setCoordinates('50.734343', '7.10211');
		$this->fixture->setInfoWindowHtml('foo');

		$this->assertContains(
			'.bindInfoWindowHtml(\'foo\')',
			$this->fixture->render()
		);
	}

	public function testGetInfoWindowEscapesSingleQuotesFromSetInfoWindowHtml() {
		$this->fixture->setCoordinates('50.734343', '7.10211');
		$this->fixture->setInfoWindowHtml('foo\'bar');

		$this->assertContains(
			'.bindInfoWindowHtml(\'foo\\\'bar\')',
			$this->fixture->render()
		);
	}

	public function testGetInfoWindowEscapesBackslashesFromSetInfoWindowHtml() {
		$this->fixture->setCoordinates('50.734343', '7.10211');
		$this->fixture->setInfoWindowHtml('foo\\bar');

		$this->assertContains(
			'.bindInfoWindowHtml(\'foo\\\\bar\')',
			$this->fixture->render()
		);
	}

	public function testGetInfoWindowEscapesHtmlClosingTagsFromSetInfoWindowHtml() {
		$this->fixture->setCoordinates('50.734343', '7.10211');
		$this->fixture->setInfoWindowHtml('foo</bar');

		$this->assertContains(
			'.bindInfoWindowHtml(\'foo<\\/bar\')',
			$this->fixture->render()
		);
	}

	public function testGetInfoWindowDoesNotEscapeDoubleQuotesFromSetInfoWindowHtml() {
		$this->fixture->setCoordinates('50.734343', '7.10211');
		$this->fixture->setInfoWindowHtml('foo"bar');

		$this->assertContains(
			'.bindInfoWindowHtml(\'foo"bar\')',
			$this->fixture->render()
		);
	}

	public function testGetInfoWindowContainsTextWithHtmlFromSetInfoWindowHtml() {
		$this->fixture->setCoordinates('50.734343', '7.10211');
		$this->fixture->setInfoWindowHtml('<strong>foo</strong><br />bar');

		$this->assertContains(
			'.bindInfoWindowHtml(\'<strong>foo<\/strong><br />bar\')',
			$this->fixture->render()
		);
	}
}
?>