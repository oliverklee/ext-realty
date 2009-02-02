<?php
/***************************************************************
* Copyright notice
*
* (c) 2008-2009 Saskia Metzler <saskia@merlin.owl.de>
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

require_once(t3lib_extMgm::extPath('realty') . 'lib/class.tx_realty_cacheManager.php');

/**
 * Unit tests for the tx_realty_cacheManager class in the 'realty' extension.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 */
class tx_realty_cacheManager_testcase extends tx_phpunit_testcase {
	/**
	 * @var tx_realty_cacheManager
	 */
	private $fixture;
	/**
	 * @var tx_oelib_testingFramework
	 */
	private $testingFramework;

	public function setUp() {
		$this->testingFramework = new tx_oelib_testingFramework('tx_realty');
		$this->fixture = new tx_realty_cacheManager();
	}

	public function tearDown() {
		$this->testingFramework->cleanUp();

		unset($this->fixture, $this->testingFramework);
	}


	public function testClearFrontEndCacheForRealtyPages() {
		$pageUid = $this->testingFramework->createFrontEndPage();
		$this->testingFramework->createContentElement(
			$pageUid, array('list_type' => 'realty_pi1')
		);
		$this->testingFramework->createPageCacheEntry($pageUid);

		$this->assertEquals(
			1,
			$this->testingFramework->countRecords(
				'cache_pages', 'page_id=' . $pageUid
			)
		);

		$this->fixture->clearFrontEndCacheForRealtyPages();

		$this->assertEquals(
			0,
			$this->testingFramework->countRecords(
				'cache_pages', 'page_id=' . $pageUid
			)
		);
	}
}
?>