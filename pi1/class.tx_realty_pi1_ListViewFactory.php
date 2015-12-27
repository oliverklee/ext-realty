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

		return t3lib_div::makeInstance($viewName, $conf, $cObj);
	}
}