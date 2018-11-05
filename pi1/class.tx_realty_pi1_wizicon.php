<?php

use TYPO3\CMS\Core\Localization\Parser\XliffParser;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Lang\LanguageService;

/**
 * Class that adds the wizard icon.
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class tx_realty_pi1_wizicon
{
    /**
     * Processing the wizard items array
     *
     * @param array[] $wizardItems the wizard items, may be empty
     *
     * @return array[] modified array with wizard items
     */
    public function proc(array $wizardItems)
    {
        $languageData = $this->includeLocalLang();

        /** @var LanguageService $languageService */
        $languageService = $GLOBALS['LANG'];
        $wizardItems['plugins_tx_realty_pi1'] = [
            'icon' => ExtensionManagementUtility::extRelPath('realty') .
            'Resources/Public/Images/ContentElementWizard.gif',
            'title' => $languageService->getLLL('pi1_title', $languageData),
            'description' => $languageService->getLLL('pi1_description', $languageData),
            'params' => '&defVals[tt_content][CType]=list&' .
                'defVals[tt_content][list_type]=realty_pi1',
        ];

        return $wizardItems;
    }

    /**
     * Reads the [extDir]/Resources/Private/Language/locallang.xlf and returns the $LOCAL_LANG array found
     * in that file.
     *
     * @return array[] the language labels
     */
    public function includeLocalLang()
    {
        $languageFile = ExtensionManagementUtility::extPath('realty') . 'Resources/Private/Language/locallang.xlf';
        /** @var LanguageService $languageService */
        $languageService = $GLOBALS['LANG'];
        /** @var XliffParser $xliffParser */
        $xliffParser = GeneralUtility::makeInstance(XliffParser::class);
        $localLanguage = $xliffParser->getParsedData($languageFile, $languageService->lang);

        return $localLanguage;
    }
}
