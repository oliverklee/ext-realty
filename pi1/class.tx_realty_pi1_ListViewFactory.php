<?php
/***************************************************************
* Copyright notice
*
* (c) 2009-2012 Bernd Schönbach <bernd@oliverklee.de>
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
 * Class tx_realty_pi1_ListViewFactory for the "realty" extension.
 *
 * This class can instantiate list views.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Bernd Schönbach <bernd@oliverklee.de>
 */
class tx_realty_pi1_ListViewFactory {
	/**
	 * Creates an instance of a list view.
	 *
	 * @param string $type
	 *        the list view type to create, must be one of "favorites",
	 *        "my_objects", "objects_by_owner" or "realty_list"
	 * @param array $conf TypoScript configuration for the plugin
	 * @param tslib_cObj $cObj the parent cObj content, needed for the flexforms
	 *
	 * @return tx_realty_pi1_AbstractListView
	 *         an instance of the list view, will be one of "tx_realty_pi1_FavoritesListView", "tx_realty_pi1_MyObjectsListView",
	 *         "tx_realty_pi1_ObjectsByOwnerListView", or "tx_realty_pi1_DefaultListView"
	 */
	static public function make($type, array $conf, tslib_cObj $cObj) {
		switch ($type) {
			case 'favorites':
				$viewName = 'tx_realty_pi1_FavoritesListView';
				break;
			case 'my_objects':
				$viewName = 'tx_realty_pi1_MyObjectsListView';
				break;
			case 'objects_by_owner':
				$viewName = 'tx_realty_pi1_ObjectsByOwnerListView';
				break;
			case 'realty_list':
				$viewName = 'tx_realty_pi1_DefaultListView';
				break;
			default:
				throw new InvalidArgumentException('The given list view type "' . $type . '" is invalid.', 1333036578);
		}

		return tx_oelib_ObjectFactory::make($viewName, $conf, $cObj);
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/realty/pi1/class.tx_realty_pi1_ListViewFactory.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/realty/pi1/class.tx_realty_pi1_ListViewFactory.php']);
}
?>