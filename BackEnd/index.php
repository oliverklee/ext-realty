<?php

// This checks permissions and exits if the users has no permission for entry.
$BE_USER->modAccess($MCONF, 1);
$LANG->includeLLFile('EXT:realty/Resources/Private/Language/locallang_mod.xlf');

/** @var tx_realty_BackEnd_Module $SOBE */
$SOBE = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_realty_BackEnd_Module');
$SOBE->init();

echo $SOBE->render();
