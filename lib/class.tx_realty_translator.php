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
 * This class translates localized strings used in this extension's lib/
 * directory.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 */
class tx_realty_translator {
	/**
	 * @var language
	 */
	protected $languageService = NULL;

	/**
	 * Constructor.
	 */
	public function __construct() {
		// expected by the LANG object
		global $TYPO3_CONF_VARS;

		if (is_object($GLOBALS['LANG'])) {
			$this->languageService = $GLOBALS['LANG'];
		} else {
			$this->languageService = t3lib_div::makeInstance('language');
		}
		$cliLanguage = tx_oelib_configurationProxy::getInstance('realty')->getAsString('cliLanguage');
		// "default" is used as language key if the configured language key is not within the set of available language keys.
		if (t3lib_utility_VersionNumber::convertVersionNumberToInteger(TYPO3_version) < 4006000) {
			$languageKey = (strpos(TYPO3_languages, '|' . $cliLanguage . '|') !== FALSE) ? $cliLanguage : 'default';
		} else {
			/** @var t3lib_l10n_Locales $locales */
			$locales = t3lib_div::makeInstance('t3lib_l10n_Locales');
			$languageKey = in_array($cliLanguage, $locales->getLocales())? $cliLanguage : 'default';
		}

		$this->languageService->init($languageKey);
		$this->languageService->includeLLFile('EXT:realty/lib/locallang.xml');
	}

	/**
	 * The destructor.
	 */
	public function __destruct() {
		unset($this->languageService);
	}

	/**
	 * Retrieves the localized string for the local language key $key.
	 *
	 * @param string $key the local language key for which to return the value, must not be empty
	 *
	 * @return string the localized string for $key or just the key if
	 *                there is no localized string for the requested key
	 */
	public function translate($key) {
		if ($key == '') {
			throw new InvalidArgumentException('$key must not be empty.', 1333035608);
		}

		$result = $this->languageService->getLL($key);

		return ($result != '') ? $result : $key;
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/realty/lib/class.tx_realty_translator.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/realty/lib/class.tx_realty_translator.php']);
}