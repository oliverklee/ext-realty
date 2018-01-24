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
use TYPO3\CMS\Core\Localization\Locales;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Lang\LanguageService;

/**
 * This class translates localized strings used in this extension's lib/
 * directory.
 *
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 */
class tx_realty_translator
{
    /**
     * @var LanguageService
     */
    protected $languageService = null;

    /**
     * Constructor.
     */
    public function __construct()
    {
        // expected by the LANG object
        global $TYPO3_CONF_VARS;

        if (is_object($GLOBALS['LANG'])) {
            $this->languageService = $GLOBALS['LANG'];
        } else {
            $this->languageService = GeneralUtility::makeInstance('language');
        }
        $cliLanguage = Tx_Oelib_ConfigurationProxy::getInstance('realty')->getAsString('cliLanguage');
        // "default" is used as language key if the configured language key is not within the set of available language keys.
        /** @var Locales $locales */
        $locales = GeneralUtility::makeInstance(Locales::class);
        $languageKey = in_array($cliLanguage, $locales->getLocales())? $cliLanguage : 'default';

        $this->languageService->init($languageKey);
        $this->languageService->includeLLFile('EXT:realty/Resources/Private/Language/locallang_import.xlf');
    }

    /**
     * The destructor.
     */
    public function __destruct()
    {
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
    public function translate($key)
    {
        if ($key == '') {
            throw new InvalidArgumentException('$key must not be empty.', 1333035608);
        }

        $result = $this->languageService->getLL($key);

        return ($result != '') ? $result : $key;
    }
}
