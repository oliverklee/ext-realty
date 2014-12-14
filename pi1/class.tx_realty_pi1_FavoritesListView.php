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
 * This class represents the favorites list view.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 * @author Saskia Metzler <saskia@merlin.owl.de>
 * @author Bernd Schönbach <bernd@oliverklee.de>
 */
class tx_realty_pi1_FavoritesListView extends tx_realty_pi1_AbstractListView {
	/**
	 * @var string session key for storing the favorites list
	 */
	const FAVORITES_SESSION_KEY = 'tx_realty_favorites';

	/**
	 * @var string session key for storing data of all favorites that
	 *                     currently get displayed
	 */
	const FAVORITES_SESSION_KEY_VERBOSE = 'tx_realty_favorites_verbose';

	/**
	 * @var string the locallang key for the label belonging to this view
	 */
	protected $listViewLabel = 'label_yourfavorites';

	/**
	 * @var array the data of the currently displayed favorites using the keys
	 *            [uid][fieldname]
	 */
	private $favoritesDataVerbose;

	/**
	 * @var bool whether Google Maps should be shown in this view
	 */
	protected $isGoogleMapsAllowed = TRUE;

	/**
	 * @var string the ID of the current view
	 */
	protected $currentView = 'favorites';

	/**
	 * Initializes data for the favorites view.
	 *
	 * @return void
	 */
	protected function initializeView() {
		$this->unhideSubparts(
			'back_link,wrapper_contact,wrapper_checkbox,favorites_url,' .
			'remove_from_favorites_button'
		);
		$this->setMarker('favorites_url', $this->getFavoritesUrl());
		$this->fillOrHideContactWrapper();
		$this->setFavoritesSessionData();
	}

	/**
	 * Fills the wrapper with the link to the contact form if displaying contact
	 * information is enabled for the favorites view. Otherwise hides the
	 * complete wrapper.
	 *
	 * @return void
	 */
	private function fillOrHideContactWrapper() {
		if (!$this->hasConfValueInteger('contactPID')) {
			$this->hideSubparts('contact', 'wrapper');
			return;
		}

		if ($this->getConfValueBoolean('showContactPageLink')
			&& ($this->getConfValueInteger('contactPID')
				!= $this->getConfValueInteger('favoritesPID')
			)
		) {
			$piVars = $this->piVars;
			unset($piVars['DATA']);

			$contactUrl = htmlspecialchars($this->cObj->typoLink_URL(array(
				'parameter' => $this->getConfValueInteger('contactPID'),
				'additionalParams' => t3lib_div::implodeArrayForUrl(
					'', array($this->prefixId => $piVars)
				),
				'useCacheHash' => TRUE,
			)));
			$this->setMarker('contact_url', $contactUrl);
		} else {
			$this->hideSubparts('contact', 'wrapper');
		}
	}

	/**
	 * Sets the current session data for the favorites.
	 *
	 * @return void
	 */
	private function setFavoritesSessionData() {
		if (!$this->hasConfValueString('favoriteFieldsInSession')) {
			return;
		}

		tx_oelib_Session::getInstance(tx_oelib_Session::TYPE_TEMPORARY)->setAsString(
			self::FAVORITES_SESSION_KEY_VERBOSE, json_encode($this->favoritesDataVerbose)
		);
	}

	/**
	 * Gets the WHERE clause part specific to this view.
	 *
	 * @return string the WHERE clause parts to add, will not be empty
	 */
	protected function getViewSpecificWhereClauseParts() {
		$GLOBALS['TSFE']->set_no_cache();
		$this->processSubmittedFavorites();

		$result = ($this->getFavorites() != '')
			? ' AND ' . 'tx_realty_objects' . '.uid ' .
				'IN(' . $this->getFavorites() . ')'
			: ' AND 0 = 1';
		$this->favoritesDataVerbose = array();

		return $result;
	}

	/**
	 * Processes the UIDs submitted in $this->piVars['favorites']
	 * if $this->piVars['favorites'] is set.
	 *
	 * If $this->piVars['remove'] is set to "1", the submitted items will be
	 * removed from the list of favorites.
	 * Otherwise, these items will get added to the list of favorites.
	 *
	 * @return void
	 */
	private function processSubmittedFavorites() {
		if (isset($this->piVars['favorites']) && !empty($this->piVars['favorites'])) {
			if ($this->piVars['remove']) {
				$this->removeFromFavorites($this->piVars['favorites']);
			} else {
				$this->addToFavorites($this->piVars['favorites']);
			}
		}

		$this->writeSummaryStringOfFavoritesToSession();
	}

	/**
	 * Removes some items to the favorites list (which is stored in an anonymous
	 * session). If some of the UIDs in $itemsToRemove are not in the favorites
	 * list, they will silently being ignored (no harm done here).
	 *
	 * @param array $itemsToRemove
	 *        list of realty object UIDs to to remove (will be intvaled by this
	 *        function), may be empty
	 *
	 * @return void
	 */
	private function removeFromFavorites(array $itemsToRemove) {
		if (empty($itemsToRemove)) {
			return;
		}

		$favorites = $this->getFavoritesArray();

		foreach ($itemsToRemove as $currentItem) {
			$key = array_search($currentItem, $favorites);
			// $key will be FALSE if the item has not been found.
			// Zero, on the other hand, is a valid key.
			if ($key !== FALSE) {
				unset($favorites[$key]);
			}
		}
		$this->storeFavorites($favorites);
	}

	/**
	 * Adds some items to the favorites list (which is stored in an anonymous
	 * session). The object UIDs are added to the list regardless of whether
	 * there actually are objects with those UIDs. That case is harmless
	 * because the favorites list serves as a filter merely.
	 *
	 * @param array $itemsToAdd
	 *        list of realty object UIDs to add (will be intvaled by this
	 *        function), may be empty
	 *
	 * @return void
	 */
	public function addToFavorites(array $itemsToAdd) {
		if (empty($itemsToAdd)) {
			return;
		}

		$favorites = $this->getFavoritesArray();

		foreach ($itemsToAdd as $currentItem) {
			$favorites[] = intval($currentItem);
		}
		$this->storeFavorites(array_unique($favorites));
	}

	/**
	 * Gets the favorites list (which is stored in an anonymous session) as a
	 * comma-separated list of UIDs. The UIDs are int-safe (this is ensured by
	 * addToFavorites()), but they are not guaranteed to point to existing
	 * records. In addition, each element is ensured to be unique
	 * (by storeFavorites()).
	 *
	 * If the list is empty (or has not been created yet), an empty string will
	 * be returned.
	 *
	 * @return string comma-separated list of UIDs of the objects on the
	 *                favorites list, may be empty
	 *
	 * @see getFavoritesArray
	 * @see addToFavorites
	 * @see storeFavorites
	 */
	private function getFavorites() {
		return tx_oelib_Session::getInstance(tx_oelib_Session::TYPE_TEMPORARY)
			->getAsString(self::FAVORITES_SESSION_KEY);
	}

	/**
	 * Gets the favorites list (which is stored in an anonymous session) as an
	 * array of UIDs. The UIDs are int-safe (this is ensured by
	 * addToFavorites()), but they are not guaranteed to point to existing
	 * records. In addition, each array element is ensured to be unique
	 * (by storeFavorites()).
	 *
	 * If the list is empty (or has not been created yet), an empty array will
	 * be returned.
	 *
	 * @return array list of UIDs of the objects on the favorites list,
	 *               may be empty
	 *
	 * @see getFavorites
	 * @see addToFavorites
	 * @see storeFavorites
	 */
	private function getFavoritesArray() {
		return tx_oelib_Session::getInstance(tx_oelib_Session::TYPE_TEMPORARY)
			->getAsIntegerArray(self::FAVORITES_SESSION_KEY);
	}

	/**
	 * Stores the favorites given in $favorites in an anonymous session.
	 *
	 * Before storing, the list of favorites is clear of duplicates.
	 *
	 * @param array $favorites list of UIDs in the favorites list to store, must already be int-safe, may be empty
	 *
	 * @return void
	 */
	private function storeFavorites(array $favorites) {
		tx_oelib_Session::getInstance(tx_oelib_Session::TYPE_TEMPORARY)
			->setAsArray(self::FAVORITES_SESSION_KEY, $favorites);
	}

	/**
	 * Writes a formatted string containing object numbers and titles of objects
	 * on the favorites list to the session.
	 *
	 * @return void
	 */
	public function writeSummaryStringOfFavoritesToSession() {
		tx_oelib_Session::getInstance(tx_oelib_Session::TYPE_TEMPORARY)
			->setAsString(
				'summaryStringOfFavorites',
				$this->createSummaryStringOfFavorites()
			);
	}

	/**
	 * Creates a formatted string to prefill an e-mail form. The string contains
	 * the object numbers and titles of the objects on the current favorites list.
	 * If there are no selected favorites, an empty string is returned.
	 *
	 * @return string formatted string to use in an e-mail form, may be empty
	 */
	public function createSummaryStringOfFavorites() {
		$summaryStringOfFavorites = '';

		$currentFavorites = $this->getFavorites();
		if ($currentFavorites != '') {
			$table = 'tx_realty_objects';
			$objects = tx_oelib_db::selectMultiple(
				'object_number, title',
				$table,
				'uid IN (' . $currentFavorites . ')' .
					tx_oelib_db::enableFields($table)
			);

			$summaryStringOfFavorites
				= $this->translate('label_on_favorites_list') . LF;

			foreach ($objects as $object) {
				$objectNumber = $object['object_number'];
				$objectTitle = $object['title'];
				$summaryStringOfFavorites
					.= '* ' . $objectNumber . ' ' . $objectTitle . LF;
			}
		}

		return $summaryStringOfFavorites;
	}

	/**
	 * Sets the row contents specific to this view.
	 *
	 * @return void
	 */
	protected function setViewSpecificListRowContents() {
		if (!$this->hasConfValueString('favoriteFieldsInSession')) {
			return;
		}

		$uid = $this->internal['currentRow']['uid'];
		$this->favoritesDataVerbose[$uid] = array();
		foreach (t3lib_div::trimExplode(
			',', $this->getConfValueString('favoriteFieldsInSession'), TRUE
		) as $key) {
			$this->favoritesDataVerbose[$uid][$key]
				= $this->getFormatter()->getProperty($key);
		}
	}

	/**
	 * Checks whether to use caching for the link to the single view page.
	 *
	 * @return bool TRUE if caching should be used, FALSE otherwise
	 */
	protected function useCacheForSinglePageLink() {
		return FALSE;
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/realty/pi1/class.tx_realty_pi1_FavoritesListView.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/realty/pi1/class.tx_realty_pi1_FavoritesListView.php']);
}