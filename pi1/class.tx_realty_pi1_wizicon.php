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
 * Class that adds the wizard icon.
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class tx_realty_pi1_wizicon {
	/**
	 * Processing the wizard items array
	 *
	 * @param array[] $wizardItems the wizard items, may be empty
	 *
	 * @return array[] modified array with wizard items
	 */
	public function proc(array $wizardItems) {
		global $LANG;

		$LL = $this->includeLocalLang();

		$wizardItems['plugins_tx_realty_pi1'] = array(
			'icon' => t3lib_extMgm::extRelPath('realty') . 'pi1/ce_wiz.gif',
			'title' => $LANG->getLLL('pi1_title', $LL),
			'description' => $LANG->getLLL('pi1_description', $LL),
			'params' => '&defVals[tt_content][CType]=list&' .
				'defVals[tt_content][list_type]=realty_pi1'
		);

		return $wizardItems;
	}

	/**
	 * Reads the [extDir]/locallang.xml and returns the $LOCAL_LANG array found
	 * in that file.
	 *
	 * @return array[] the language labels
	 */
	public function includeLocalLang() {
		$languageFile = t3lib_extMgm::extPath('realty') . 'locallang.xml';
		/** @var language $languageService */
		$languageService = $GLOBALS['LANG'];
		if (class_exists('t3lib_l10n_parser_Llxml')) {
			/** @var t3lib_l10n_parser_Llxml $xmlParser */
			$xmlParser = t3lib_div::makeInstance('t3lib_l10n_parser_Llxml');
			$localLanguage = $xmlParser->getParsedData($languageFile, $languageService->lang);
		} else {
			$localLanguage = t3lib_div::readLLXMLfile($languageFile, $languageService->lang);
		}

		return $localLanguage;
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/realty/pi1/class.tx_realty_pi1_wizicon.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/realty/pi1/class.tx_realty_pi1_wizicon.php']);
}