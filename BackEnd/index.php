<?php

// This checks permissions and exits if the users has no permission for entry.
$BE_USER->modAccess($MCONF, 1);
$LANG->includeLLFile('EXT:realty/Resources/Private/Language/locallang_mod.xlf');

/** @var tx_realty_BackEnd_Module $SOBE */
$SOBE = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_realty_BackEnd_Module');
$SOBE->init();

echo $SOBE->render();

echo <<<EOT

<h2>Compatibility with TYPO3 CMS 7.6 and 8.7</h2>

<p>

Currently, there is a <a href="https://docs.google.com/spreadsheets/d/1BfSmradrQMrcbnABqCpo0gGbgNpfNtGzy94Lpvd8Grk/pubhtml" target="_blank" style="text-decoration: underline;">
crowdfunding campaign</a> going on to fund the development work needed to make this extension compatible with TYPO3 CMS 7.6 and 8.7. If you would
like to see this extension work on TYPO3 CMS 7.6 and 8.7, please consider contributing to the campaign and
send the extension author an email: <a href="mailto:typo3-coding@oliverklee.de" style="text-decoration: underline;">typo3-coding@oliverklee.de</a>
</p>
EOT;
