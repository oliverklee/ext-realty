<?php
defined('TYPO3_MODE') or die('Access denied.');

$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['realty_pi1'] = 'layout,select_key,pages,recursive';
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['realty_pi1'] = 'pi_flexform';

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(
    [
        'LLL:EXT:realty/Resources/Private/Language/locallang_db.xlf:tt_content.list_type_pi1',
        'realty_pi1',
        'EXT:realty/Resources/Public/Images/ContentElement.gif',
    ],
    'list_type',
    'realty'
);
