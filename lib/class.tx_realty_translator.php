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
 * Class 'tx_realty_translator' for the 'realty' extension.
 *
 * This class translates localized strings used in this extenstion's lib/
 * directory.
 *
 * @package		TYPO3
 * @subpackage	tx_realty
 *
 * @author		Saskia Metzler <saskia@merlin.owl.de>
 */

require_once (PATH_typo3.'sysext/lang/lang.php');

require_once(t3lib_extMgm::extPath('oelib').'class.tx_oelib_configurationProxy.php');

class tx_realty_translator {
	/**
	 * Constructor.
	 */
	public function __construct() {
		// expected by the LANG object
		global $TYPO3_CONF_VARS;

		if (!is_object($GLOBALS['LANG'])) {
			$GLOBALS['LANG'] = t3lib_div::makeInstance('language');
		}
		$cliLanguage = tx_oelib_configurationProxy::getInstance('realty')
			->getConfigurationValueString('cliLanguage');
		// 'default' is used as language key if the configured language key is
		// not within the set of available language keys.
		$languageKey = (strpos(TYPO3_languages, '|'.$cliLanguage.'|') !== false)
			? $cliLanguage
			: 'default';
		$GLOBALS['LANG']->init($languageKey);
		$GLOBALS['LANG']->includeLLFile('EXT:realty/lib/locallang.xml');
	}

	/**
 	 * Retrieves the localized string for the local language key $key.
 	 *
	 * @param	string		the local language key for which to return the value,
	 * 						must not be empty
	 *
	 * @return	string		the localized string for $key or just the key if
	 * 						there is no localized string for the requested key
	 */
	public function translate($key) {
		if ($key == '') {
			throw new Exception('$key must not be empty.');
		}

		$result = $GLOBALS['LANG']->getLL($key);

		return ($result != '') ? $result : $key;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/lib/class.tx_realty_translator.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/lib/class.tx_realty_translator.php']);
}
?>