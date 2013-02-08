<?php
/***************************************************************
* Copyright notice
*
* (c) 2009-2013 Bernd Schönbach <bernd@oliverklee.de>
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
 * Class tx_realty_tests_fixtures_testingListView for the "realty" extension.
 *
 * This class represents a list view for testing purposes.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Bernd Schönbach <bernd@oliverklee.de>
 */
class tx_realty_tests_fixtures_TestingListView extends tx_realty_pi1_AbstractListView {
	/**
	 * @var string the list view type to display
	 */
	protected $currentView = 'realty_list';

	/**
	 * @var string the locallang key to the label of a list view
	 */
	protected $listViewLabel = 'label_weofferyou';

	/**
	 * @var boolean whether Google Maps should be shown in this view
	 */
	protected $isGoogleMapsAllowed = TRUE;

	/**
	 * Initializes some view-specific data.
	 *
	 * @return void
	 */
	protected function initializeView() {}

	/**
	 * Creates the URL of the current page. The URL will contain a flag to
	 * disable caching as this URL also is used for forms with method="post".
	 *
	 * The URL will contain the current piVars that are relevant for the list
	 * view if $keepPiVars is set to TRUE.
	 *
	 * The URL will already be htmlspecialchared.
	 *
	 * @param boolean $keepPiVars whether the current piVars should be kept
	 * @param array $removeKeys
	 *        the keys to remove from the piVar data before processing the URL,
	 *        may be empty
	 *
	 * @return string htmlspecialchared URL of the current page, will not
	 *                be empty
	 */
	public function getSelfUrl($keepPiVars = TRUE, array $removeKeys = array()) {
		return parent::getSelfUrl($keepPiVars, $removeKeys);
	}
}
?>