<?php
/***************************************************************
* Copyright notice
*
* (c) 2007-2013 Saskia Metzler <saskia@merlin.owl.de>
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

require_once(t3lib_extMgm::extPath('realty') . 'lib/tx_realty_constants.php');

/**
 * Test case.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 */
class tx_realty_Service_TranslatorTest extends tx_phpunit_testcase {
	/**
	 * @var tx_realty_translator instance to be tested
	 */
	private $fixture;

	public function setUp() {
		// No instance to test is created here because different languages are
		// set in the tests. The language must be set before the translator
		// is created.
	}

	public function tearDown() {
		unset($this->fixture);
	}

	/**
	 * @test
	 */
	public function translatorReturnsGermanString() {
		tx_oelib_configurationProxy::getInstance('realty')
			->setAsString('cliLanguage', 'de');
		$this->fixture = new tx_realty_translator();

		$this->assertEquals(
			'Erlaubt',
			$this->fixture->translate('label_allowed')
		);
	}

	/**
	 * @test
	 */
	public function translatorReturnsEnglishString() {
		tx_oelib_configurationProxy::getInstance('realty')
			->setAsString('cliLanguage', 'en');
		$this->fixture = new tx_realty_translator();

		$this->assertEquals(
			'Allowed',
			$this->fixture->translate('label_allowed')
		);
	}

	/**
	 * @test
	 */
	public function translatorReturnsDefaultLanguageStringForInvalidLanguageKey() {
		tx_oelib_configurationProxy::getInstance('realty')
			->setAsString('cliLanguage', 'xy');
		$this->fixture = new tx_realty_translator();

		$this->assertEquals(
			'Allowed',
			$this->fixture->translate('label_allowed')
		);
	}

	/**
	 * @test
	 */
	public function translatorReturnsDefaultLanguageStringForEmptyLanguageKey() {
		tx_oelib_configurationProxy::getInstance('realty')
			->setAsString('cliLanguage', '');
		$this->fixture = new tx_realty_translator();

		$this->assertEquals(
			'Allowed',
			$this->fixture->translate('label_allowed')
		);
	}

	/**
	 * @test
	 */
	public function translatorThrowsAnExceptionForEmptyKey() {
		$this->fixture = new tx_realty_translator();

		$this->setExpectedException(
			'InvalidArgumentException',
			'$key must not be empty.'
		);
		$this->fixture->translate('');
	}
}
?>