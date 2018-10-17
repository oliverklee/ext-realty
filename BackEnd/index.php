<?php

// This checks permissions and exits if the users has no permission for entry.
/** @var \TYPO3\CMS\Core\Authentication\BackendUserAuthentication $user */
$user = $GLOBALS['BE_USER'];
$user->modAccess($MCONF, 1);

/** @var \TYPO3\CMS\Lang\LanguageService $languageService */
$languageService = $GLOBALS['LANG'];
$languageService->includeLLFile('EXT:realty/Resources/Private/Language/locallang_mod.xlf');

/** @var \tx_realty_BackEnd_Module $module */
$module = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\tx_realty_BackEnd_Module::Class);
$module->init();

echo $module->render();
