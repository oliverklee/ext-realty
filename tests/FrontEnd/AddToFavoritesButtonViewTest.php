<?php
/***************************************************************
* Copyright notice
*
* (c) 2009-2012 Bernd Schönbach <bernd@oliverklee.de>
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
 * Unit tests for the tx_realty_pi1_AddToFavoritesButtonView class in the
 * "realty" extension.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Bernd Schönbach <bernd@oliverklee.de>
 */
class tx_realty_FrontEnd_AddToFavoritesButtonViewTest extends tx_phpunit_testcase {
	/**
	 * @var tx_realty_pi1_AddToFavoritesButtonView
	 */
	private $fixture;

	/**
	 * @var tx_oelib_testingFramework
	 */
	private $testingFramework;

	public function setUp() {
		$this->testingFramework = new tx_oelib_testingFramework('tx_realty');
		$this->testingFramework->createFakeFrontEnd();

		$this->fixture = new tx_realty_pi1_AddToFavoritesButtonView(
			array('templateFile' => 'EXT:realty/pi1/tx_realty_pi1.tpl.htm'),
			$GLOBALS['TSFE']->cObj
		);
	}

	public function tearDown() {
		$this->testingFramework->cleanUp();

		$this->fixture->__destruct();
		unset($this->fixture, $this->testingFramework);
	}


	////////////////////////////////////
	// Testing the basic functionality
	////////////////////////////////////

	public function testRenderReturnsNonEmptyResultForZeroShowUidAndNoFavoritesPidConfigured() {
		$this->assertNotEquals(
			'',
			$this->fixture->render(array('showUid' => 0))
		);
	}

	public function testRenderReturnsButtonAddToFavorites() {
		$this->assertContains(
			'class="button singleViewAddToFavorites"',
			$this->fixture->render(array('showUid' => 0))
		);
	}

	public function testRenderReturnsProvidedShowUidOfRealtyRecordAsFormValue() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getNewGhost();

		$this->assertContains(
			'value="' . $realtyObject->getUid() . '"',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	public function testRenderReturnsConfiguredFavoritesPidAsLinkTarget() {
		$pageUid = $this->testingFramework->createFrontEndPage();
		$this->fixture->setConfigurationValue('favoritesPID', $pageUid);

		$this->assertContains(
			'?id=' . $pageUid,
			$this->fixture->render(array('showUid' => 0))
		);
	}

	public function testRenderReturnsNoUnreplacedMarkersWhileTheResultIsNonEmpty() {
		$this->assertNotContains(
			'###',
			$this->fixture->render(array('showUid' => 0))
		);
	}
}
?>