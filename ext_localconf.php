<?php
defined('TYPO3_MODE') or die('Access denied.');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig('

	# ***************************************************************************************
	# CONFIGURATION of RTE in table "tx_realty_objects", field "description"
	# ***************************************************************************************
RTE.config.tx_realty_objects.description {
  hidePStyleItems = H1, H4, H5, H6
  proc.exitHTMLparser_db = 1
  proc.exitHTMLparser_db {
    keepNonMatchedTags = 1
    tags.font.allowedAttribs = color
    tags.font.rmTagIfNoAttrib = 1
    tags.font.nesting = global
  }
}
');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig('

	# ***************************************************************************************
	# CONFIGURATION of RTE in table "tx_realty_objects", field "equipment"
	# ***************************************************************************************
RTE.config.tx_realty_objects.equipment {
  hidePStyleItems = H1, H4, H5, H6
  proc.exitHTMLparser_db = 1
  proc.exitHTMLparser_db {
    keepNonMatchedTags = 1
    tags.font.allowedAttribs = color
    tags.font.rmTagIfNoAttrib = 1
    tags.font.nesting = global
  }
}
');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig('

	# ***************************************************************************************
	# CONFIGURATION of RTE in table "tx_realty_objects", field "location"
	# ***************************************************************************************
RTE.config.tx_realty_objects.location {
  hidePStyleItems = H1, H4, H5, H6
  proc.exitHTMLparser_db = 1
  proc.exitHTMLparser_db {
    keepNonMatchedTags = 1
    tags.font.allowedAttribs = color
    tags.font.rmTagIfNoAttrib = 1
    tags.font.nesting = global
  }
}
');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig('

	# ***************************************************************************************
	# CONFIGURATION of RTE in table "tx_realty_objects", field "misc"
	# ***************************************************************************************
RTE.config.tx_realty_objects.misc {
  hidePStyleItems = H1, H4, H5, H6
  proc.exitHTMLparser_db = 1
  proc.exitHTMLparser_db {
    keepNonMatchedTags = 1
    tags.font.allowedAttribs= color
    tags.font.rmTagIfNoAttrib = 1
    tags.font.nesting = global
  }
}
');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addUserTSConfig('
	options.saveDocNew.tx_realty_objects = 1
');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addUserTSConfig('
	options.saveDocNew.tx_realty_apartment_types = 1
');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addUserTSConfig('
	options.saveDocNew.tx_realty_house_types = 1
');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addUserTSConfig('
	options.saveDocNew.tx_realty_car_places = 1
');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addUserTSConfig('
	options.saveDocNew.tx_realty_pets = 1
');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addUserTSConfig('
	options.saveDocNew.tx_realty_cities = 1
');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addUserTSConfig('
	options.saveDocNew.tx_realty_districts = 1
');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43(
	$_EXTKEY,
	'pi1/class.tx_realty_pi1.php',
	'_pi1',
	'list_type',
	1
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScript(
	$_EXTKEY,
	'setup', '
	tt_content.shortcut.20.conf.tx_realty_objects = < plugin.' . \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getCN($_EXTKEY) . '_pi1
	tt_content.shortcut.20.conf.tx_realty_objects.CMD = singleView
',
	43
);

// registers the key for class.tx_realty_cli.php
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['cliKeys']['openImmoImport']
	= array('EXT:realty/cli/class.tx_realty_cli.php', '_CLI_realty');
// registers the key for class.tx_realty_cli_ImageCleanUpStarter.php
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['cliKeys']['cleanUpRealtyImages']
	= array('EXT:realty/cli/class.tx_realty_cli_ImageCleanUpStarter.php', '_CLI_realty');

// registers the eID functions for AJAX
$GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include'][$_EXTKEY] = 'EXT:' . $_EXTKEY . '/Ajax/tx_realty_Ajax_Dispatcher.php';

// RealURL autoconfiguration
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/realurl/class.tx_realurl_autoconfgen.php']['extensionConfiguration']['realty']
	= 'Tx_Realty_Configuration_RealUrl_Configuration->addConfiguration';
