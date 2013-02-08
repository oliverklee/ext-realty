<?php
/***************************************************************
* Copyright notice
*
* (c) 2009-2013 Bernd Schönbach <bernd@oliverklee.de>
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
 * Test case.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Bernd Schönbach <bernd@oliverklee.de>
 */
class tx_realty_Service_ListViewFactoryTest extends tx_phpunit_testcase {
	/**
	 * @var tslib_cObj a mocked content object
	 */
	private $cObjMock;

	public function setUp() {
		$this->cObjMock = $this->getMock('tslib_cObj');
	}

	public function tearDown() {
		unset($this->cObjMock);
	}


	/////////////////////////////////////////////
	// Tests concerning the basic functionality
	/////////////////////////////////////////////

	/**
	 * @test
	 */
	public function canCreateARealtyListViewInstance() {
		$this->assertTrue(
			tx_realty_pi1_ListViewFactory::make(
				'realty_list', array(), $this->cObjMock
			) instanceof tx_realty_pi1_DefaultListView
		);
	}

	/**
	 * @test
	 */
	public function canCreateAFavoritesListViewInstance() {
		$this->assertTrue(
			tx_realty_pi1_ListViewFactory::make(
				'favorites', array(), $this->cObjMock
			) instanceof tx_realty_pi1_FavoritesListView
		);
	}

	/**
	 * @test
	 */
	public function canCreateAMyObjectsListViewInstance() {
		$this->assertTrue(
			tx_realty_pi1_ListViewFactory::make(
				'my_objects', array(), $this->cObjMock
			) instanceof tx_realty_pi1_MyObjectsListView
		);
	}

	/**
	 * @test
	 */
	public function canCreateAnObjectsByOwnerListViewInstance() {
		$this->assertTrue(
			tx_realty_pi1_ListViewFactory::make(
				'objects_by_owner', array(), $this->cObjMock
			) instanceof tx_realty_pi1_ObjectsByOwnerListView
		);
	}

	/**
	 * @test
	 */
	public function throwsExceptionForInvalidViewType() {
		$this->setExpectedException(
			'InvalidArgumentException',
			'The given list view type "foo" is invalid.'
		);

		tx_realty_pi1_ListViewFactory::make('foo', array(), $this->cObjMock);
	}
}
?>