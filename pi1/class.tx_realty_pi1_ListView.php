<?php
/***************************************************************
* Copyright notice
*
* (c) 2009 Bernd Schönbach <bernd@oliverklee.de>
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
 * Class 'tx_realty_ListView' for the 'realty' extension.
 *
 * This class is the basic class for all list views in the front end.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 * @author Saskia Metzler <saskia@merlin.owl.de>
 * @author Bernd Schönbach <bernd@oliverklee.de>
 */
class tx_realty_pi1_ListView extends tx_realty_pi1_FrontEndView {
	/**
	 * @var string same as class name
	 */
	public $prefixId = 'tx_realty_pi1';

	/**
	 * @var string path to this script relative to the extension directory
	 */
	public $scriptRelPath = 'pi1/class.tx_realty_pi1_ListView.php';

	/**
	 * @var boolean whether this class is called in the test mode
	 */
	private $isTestMode = false;

	/**
	 * @var array the data of the currently displayed favorites using the keys
	 *            [uid][fieldname]
	 */
	private $favoritesDataVerbose;

	/**
	 * @var tx_realty_pi1_Formatter formatter for prices, areas etc.
	 */
	private $formatter = null;

	/**
	 * @var string the list view type to display
	 */
	private $currentView = '';

	/**
	 * @var array the names of the database tables for foreign keys
	 */
	private $tableNames = array(
		'objects' => REALTY_TABLE_OBJECTS,
		'city' => REALTY_TABLE_CITIES,
		'district' => REALTY_TABLE_DISTRICTS,
		'country' => STATIC_COUNTRIES,
		'apartment_type' => REALTY_TABLE_APARTMENT_TYPES,
		'house_type' => REALTY_TABLE_HOUSE_TYPES,
		'garage_type' => REALTY_TABLE_CAR_PLACES,
		'pets' => REALTY_TABLE_PETS,
		'images' => REALTY_TABLE_IMAGES,
	);

	/**
	 * @var array sort criteria that can be selected in the BE flexforms.
	 */
	private static $sortCriteria = array(
		'object_number',
		'title',
		'city',
		'district',
		'buying_price',
		'rent_excluding_bills',
		'number_of_rooms',
		'living_area',
		'tstamp',
		'random',
	);

	/**
	 * @var array existing types of list views
	 */
	private static $listViews = array(
		'favorites', 'my_objects', 'objects_by_owner', 'realty_list'
	);

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
	 * @var integer character length for cropped titles
	 */
	const CROP_SIZE = 74;

	/**
	 * The constructor.
	 *
	 * @param array $configuration TypoScript configuration for the plugin
	 * @param tslib_cObj $cObj the parent cObj content, needed for the flexforms
	 * @param boolean $isTestMode
	 *        whether this class should be instantiated for testing
	 */
	public function __construct(
		array $configuration, tslib_cObj $cObj, $isTestMode = false
	) {
		$this->isTestMode = $isTestMode;
		parent::__construct($configuration, $cObj);
	}

	/**
	 * Frees as much memory that has been used by this object as possible.
	 */
	public function __destruct() {
		if ($this->formatter) {
			$this->formatter->__destruct();
		}
		unset($this->formatter);
		parent::__destruct();
	}

	/**
	 * Shows a list of database entries.
	 *
	 * @param array $piVars the piVars array, may be empty
	 *
	 * @return string HTML list of table entries or the HTML for an error
	 *                view if no items were found
	 */
	public function render(array $piVars = array()) {
		$this->piVars = $piVars;
		$this->addHeaderForListView();
		// Initially most subparts are hidden. Depending on the type of list
		// view, they will be set to unhidden again.
		$this->hideSubparts(
			'list_filter,back_link,new_record_link,wrapper_contact,' .
			'add_to_favorites_button,remove_from_favorites_button,' .
			'wrapper_editor_specific_content,wrapper_checkbox,favorites_url,' .
			'limit_heading, google_map'
		);
		$this->cacheSelectedOwner();

		switch ($this->currentView) {
			case 'favorites':
				$listLabel = 'label_yourfavorites';
				$this->unhideSubparts(
					'back_link,wrapper_contact,wrapper_checkbox,favorites_url,' .
					'remove_from_favorites_button'
				);
				$this->setMarker('favorites_url', $this->getFavoritesUrl());
				$this->fillOrHideContactWrapper();
				$this->setFavoritesSessionData();
				break;
			case 'my_objects':
				$listLabel = 'label_your_objects';
				$this->unhideSubparts(
					'wrapper_editor_specific_content,new_record_link'
				);

				if (tx_oelib_FrontEndLoginManager::getInstance()->isLoggedIn()) {
					$this->setLimitHeading();
					$this->setEditorLinkMarker();
				}
				$this->setMarker(
					'empty_editor_link',
					$this->createLinkToFeEditorPage('editorPID', 0)
				);
				$this->processDeletion();
				break;
			case 'objects_by_owner':
				$listLabel = $this->getTitleForTheObjectsByOwnerList();
				$this->unhideSubparts(
					'favorites_url,add_to_favorites_button,wrapper_checkbox,' .
					'back_link'
				);
				break;
			case 'realty_list':
				// intended fall-through
			default:
				$listLabel = 'label_weofferyou';
				$this->unhideSubparts(
					'favorites_url,list_filter,add_to_favorites_button,' .
					'wrapper_checkbox'
				);
				$this->setSubpart('list_filter', $this->createCheckboxesFilter());
				break;
		}

		$this->setMarker('list_heading', $this->translate($listLabel));
		$this->setSubpart('favorites_url', $this->getFavoritesUrl());
		$this->fillListRows();
		$this->setRedirectHeaderForSingleResult();

		return $this->getSubpart('LIST_VIEW');
	}

	/**
	 * Fills in the data for each list row.
	 */
	private function fillListRows() {
		$dbResult = $this->initListView();

		if ($this->internal['res_count'] == 0) {
			$this->setEmptyResultView();
			return;
		}

		$isGoogleMapsEnabled = $this->getConfValueBoolean(
			'showGoogleMaps', 's_googlemaps'
		);
		if ($isGoogleMapsEnabled) {
			$googleMapsView = tx_oelib_ObjectFactory::make(
				'tx_realty_pi1_GoogleMapsView', $this->conf, $this->cObj,
				$this->isTestMode
			);
		}

		$listItems = '';
		$rowCounter = 0;

		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbResult)) {
			$this->internal['currentRow'] = $row;
			$listItems .= $this->createListRow($rowCounter);
			if ($isGoogleMapsEnabled) {
				$googleMapsView->setMapMarker(
					$this->internal['currentRow']['uid'], true
				);
			}
			$rowCounter++;
		}
		$GLOBALS['TYPO3_DB']->sql_free_result($dbResult);

		$this->setSubpart('list_item', $listItems);
		$this->setSubpart('pagination', $this->createPagination());
		$this->setSubpart('wrapper_sorting', $this->createSorting());
		if ($isGoogleMapsEnabled) {
			$this->unhideSubparts('google_map');
			$this->setSubpart('google_map', $googleMapsView->render());
			$googleMapsView->__destruct();
			$googleMapsView = null;
		}
	}

	/**
	 * Returns the database query result for the realty records to list.
	 *
	 * The result will be limited according to the configuration of
	 * "results_at_a_time" and the current page number. It will also be sorted
	 * according to the current value of "orderBy" (or by UID if there is none)
	 * and the value of "descFlag". The very first records will always be those
	 * with a value for "sorting" set within the record itself.
	 *
	 * @return pointer the realty records to list as a mysql result resource
	 */
	private function initListView() {
		// To ensure that sorting by cities actually sorts the titles and not
		// the cities' UIDs, the JOIN on the cities table is needed.
		$table = REALTY_TABLE_OBJECTS . ' INNER JOIN ' . REALTY_TABLE_CITIES .
			' ON ' . REALTY_TABLE_OBJECTS . '.city = ' .
			REALTY_TABLE_CITIES . '.uid';
		$whereClause = $this->createWhereClause();
		$sortingColumn = REALTY_TABLE_OBJECTS . '.sorting';
		tx_oelib_db::enableQueryLogging();

		$dbResult = $GLOBALS['TYPO3_DB']->sql_query(
			'(' .
				'SELECT ' . REALTY_TABLE_OBJECTS . '.*' .
				' FROM ' . $table .
				' WHERE ' . $whereClause . ' AND ' . $sortingColumn . '>0' .
				' ORDER BY ' . $sortingColumn .
				// ORDER BY within the SELECT call of a UNION requires a LIMIT.
				' LIMIT 10000000000000' .
			') UNION (' .
				'SELECT ' . REALTY_TABLE_OBJECTS . '.*' .
				' FROM ' . $table .
				' WHERE ' . $whereClause . ' AND ' . $sortingColumn . '<1' .
				' ORDER BY ' . $this->createOrderByStatement() .
				' LIMIT 10000000000000' .
			')' .
			' LIMIT ' . $this->createLimitStatement($table, $whereClause)
		);

		if (!$dbResult) {
			throw new tx_oelib_Exception_Database();
		}

		return $dbResult;
	}

	/**
	 * Adds a header for the list view.
	 *
	 * Overides the line Cache-control if POST data of realty has been sent.
	 * This assures, that the page is loaded correctly after hitting the back
	 * button in IE (see also Bug 2636).
	 */
	private function addHeaderForListView() {
		$postValues = t3lib_div::_POST();
		if (isset($postValues['tx_realty_pi1'])) {
			tx_oelib_headerProxyFactory::getInstance()
				->getHeaderProxy()->addHeader(
					'Cache-Control: max-age=86400, must-revalidate'
				);
		}
	}

	/**
	 * Creates the URL to the favorites page.
	 *
	 * If $this->getConfValueInteger('favoritesPID') is not set, a link to the
	 * current page will be returned.
	 *
	 * The URL will already be htmlspecialchared.
	 *
	 * @return string htmlspecialchared URL of the page set in
	 *                $this->getConfValueInteger('favoritesPID'), will
	 *                not be empty
	 */
	private function getFavoritesUrl() {
		$pageId = $this->getConfValueInteger('favoritesPID');

		if (!$pageId) {
			$pageId = $GLOBALS['TSFE']->id;
		}

		return htmlspecialchars(
			$this->cObj->typoLink_URL(array('parameter' => $pageId))
		);
	}

	/**
	 * Fills the wrapper with the link to the contact form if displaying contact
	 * information is enabled for the favorites view. Otherwise hides the
	 * complete wrapper.
	 */
	private function fillOrHideContactWrapper() {
		if (($this->currentView != 'favorites')
			|| !$this->hasConfValueInteger('contactPID')
		) {
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
			)));
			$this->setMarker('contact_url', $contactUrl);
		} else {
			$this->hideSubparts('contact', 'wrapper');
		}
	}

	/**
	 * Sets the current session data for the favorites.
	 */
	private function setFavoritesSessionData() {
		if (!$this->hasConfValueString('favoriteFieldsInSession')) {
			return;
		}

		tx_oelib_Session::getInstance(tx_oelib_Session::TYPE_TEMPORARY)->setAsString(
			self::FAVORITES_SESSION_KEY_VERBOSE,
			serialize($this->favoritesDataVerbose)
		);
	}

	/**
	 * Sets the message how many objects the currently logged-in front-end user
	 * still can enter.
	 *
	 * This function should only be called when a user is logged-in at the front
	 * end.
	 */
	private function setLimitHeading() {
		$user = tx_oelib_FrontEndLoginManager::getInstance()
			->getLoggedInUser('tx_realty_Mapper_FrontEndUser');
		if ($user->getTotalNumberOfAllowedObjects() == 0) {
			$this->hideSubparts('limit_heading');
			return;
		}

		$objectsLeftToEnter = $user->getObjectsLeftToEnter();
		$this->unhideSubparts('limit_heading');
		$this->setMarker(
			'objects_limit_heading',
			sprintf(
				$this->translate('label_objects_already_entered'),
				$user->getNumberOfObjects(),
				$user->getTotalNumberOfAllowedObjects()
			)
		);
		switch ($objectsLeftToEnter) {
			case 0:
				$labelLeftToEnter = $this->translate('label_no_objects_left');
				break;
			case 1:
				$labelLeftToEnter = $this->translate('label_one_object_left');
				break;
			default:
				$labelLeftToEnter = sprintf(
					$this->translate('label_multiple_objects_left'),
					$objectsLeftToEnter
				);
				break;
		}

		$this->setMarker(
			'objects_left_to_enter',
			$labelLeftToEnter
		);
	}

	/**
	 * Sets the link to the new record button of the my objects view and hides
	 * it if the user cannot enter any more objects.
	 *
	 * This function should only be called when a user is logged in at the front
	 * end.
	 */
	private function setEditorLinkMarker() {
		if (tx_oelib_FrontEndLoginManager::getInstance()
			->getLoggedInUser('tx_realty_Mapper_FrontEndUser')
			->canAddNewObjects()
		) {
			$this->setMarker(
				'empty_editor_link',
				$this->createLinkToFeEditorPage('editorPID', 0)
			);
		} else {
			$this->hideSubparts('new_record_link');
		}
	}

	/**
	 * Creates a link to the FE editor page.
	 *
	 * @param string $pidKey
	 *        key of the configuration value with the PID, must not be empty
	 * @param integer $uid
	 *        UID of the object to be loaded for editing, must be >= 0
	 *        (Zero will open the FE editor for a new record to insert.)
	 *
	 * @return string the link to the FE editor page, will not be empty
	 */
	private function createLinkToFeEditorPage($pidKey, $uid) {
		return t3lib_div::locationHeaderUrl(
			$this->cObj->typoLink_URL(
				array(
					'parameter' => $this->getConfValueInteger($pidKey),
					'additionalParams' => t3lib_div::implodeArrayForUrl(
						$this->prefixId, array('showUid' => $uid)
					),
				)
			)
		);
	}

	/**
	 * Processes the deletion of a realty record.
	 */
	private function processDeletion() {
		// no need for a front-end editor if there is nothing to delete
		if ($this->piVars['delete'] == 0) {
			return;
		}

		// For testing, the FE editor's FORMidable object must not be created.
		$frontEndEditor = tx_oelib_ObjectFactory::make(
			'tx_realty_frontEndEditor', $this->conf, $this->cObj,
			$this->piVars['delete'], 'pi1/tx_realty_frontEndEditor.xml',
			$this->isTestMode
		);
		$frontEndEditor->deleteRecord();
		$frontEndEditor->__destruct();
	}

	/**
	 * Returns the title for the list of objects by one owner.
	 * The title will contain a localized string for 'label_offerings_by' plus
	 * the owner's label.
	 *
	 * If there is no cached owner, the return value will be 'label_sorry'
	 * as a localized string. (setEmptyResultView will add the corresponding
	 * error message then.) An owner is cached through cacheSelectedOwner in
	 * the main function if a valid UID was provided by $this->piVars.
	 *
	 * @return string localized string for 'label_offerings_by' plus the
	 *                owner's label or the string for 'label_sorry' if
	 *                there is no owner at all
	 *
	 * @see getOwnerLabel()
	 */
	private function getTitleForTheObjectsByOwnerList() {
		$result = $this->translate('label_sorry');

		if ($this->cachedOwner['uid'] != 0) {
			$result = $this->translate('label_offerings_by') . ' ' .
				$this->getOwnerLabel();
		}

		return $result;
	}

	/**
	 * Creates the search checkboxes for the DB field selected in the BE.
	 * If no field is selected in the BE or there are not DB records with
	 * non-empty data for that field, this function returns an empty string.
	 *
	 * This function will also return an empty string if "city" is selected in
	 * the BE and $this->piVars['city'] is set (by the city selector).
	 *
	 * @return string HTML for the search bar, may be empty
	 */
	private function createCheckboxesFilter() {
		if (!$this->mayCheckboxesFilterBeCreated()) {
			return '';
		}

		$items = $this->getCheckboxItems();
		if (!empty($items)) {
			$this->setSubpart('search_item', implode(LF, $items));
			$this->setMarker(
				'self_url_without_pivars',
				$this->getSelfUrl(TRUE, array('search'))
			);

			$result = $this->getSubpart('LIST_FILTER');
		} else {
			$result = '';
		}

		return $result;
	}

	/**
	 * Sets a redirect header if there is only one record in the list and if
	 * this is because an ID filter was applied in the search form.
	 */
	private function setRedirectHeaderForSingleResult() {
		if (($this->internal['res_count'] != 1)
			|| (($this->piVars['uid'] == 0) && empty($this->piVars['objectNumber']))
		) {
			return;
		}

		$this->createLinkToSingleViewPageForAnyLinkText(
			'|', $this->internal['currentRow']['uid']
		);
		tx_oelib_headerProxyFactory::getInstance()->getHeaderProxy()->addHeader(
			'Location: ' .
			t3lib_div::locationHeaderUrl($this->cObj->lastTypoLinkUrl)
		);
	}

	/**
	 * Sets the view to an empty result message specific for the requested view.
	 */
	private function setEmptyResultView() {
		$noResultsMessage = 'message_noResultsFound_' . $this->currentView;

		// The objects-by-owner-view has two reasons for being empty.
		if (($this->currentView == 'objects_by_owner')
			&& ($this->cachedOwner['uid'] == 0)
		) {
			$noResultsMessage = 'message_no_such_owner';
		}

		$this->setMarker(
			'message_noResultsFound', $this->translate($noResultsMessage)
		);

		// If the current view is a list view, the subpart to fill will be
		// 'list_result'. All non-list view's subparts to fill here are named
		// '[type of view]_result'.
		if (in_array($this->currentView, self::$listViews)) {
			$this->currentView = 'list';
		}
		$this->setSubpart(
			$this->currentView . '_result', $this->getSubpart('EMPTY_RESULT_VIEW')
		);
	}

	/**
	 * Returns a single table row for the list view.
	 *
	 * @param integer $rowCounter
	 *       the row counter, starts at 0 (zero), and is used for alternating
	 *       class values in the output rows, must be >= 0
	 *
	 * @return string HTML output, a table row with a class attribute set
	 *                (alternative based on odd/even rows)
	 */
	private function createListRow($rowCounter = 0) {
		$this->resetListViewSubparts();

		$position = ($rowCounter == 0) ? 'first' : '';
		$this->setMarker('class_position_in_list', $position);

		foreach (array(
			'uid' => $this->internal['currentRow']['uid'],
			'object_number' => $this->internal['currentRow']['object_number'],
			'teaser' => $this->internal['currentRow']['teaser'],
			'linked_title' => $this->getLinkedTitle(),
			'features' => $this->getFeatureList(),
			'list_image_right' => $this->getImageLinkedToSingleView('listImageMax'),
			'list_image_left' => $this->getImageLinkedToSingleView('listImageMax', 1),
		) as $key => $value) {
			$this->setOrDeleteMarkerIfNotEmpty($key, $value, '', 'wrapper');
		}

		foreach (array(
			'city', 'district', 'living_area', 'number_of_rooms', 'heating_type',
			'buying_price', 'extra_charges', 'rent_excluding_bills',
		) as $key) {
			$this->setOrDeleteMarkerIfNotEmpty(
				$key, $this->getFormatter()->getProperty($key), '', 'wrapper'
			);
		}

		switch ($this->internal['currentRow']['object_type']) {
			case REALTY_FOR_SALE:
				$this->hideSubparts(
					'rent_excluding_bills,extra_charges', 'wrapper'
				);
				break;
			case REALTY_FOR_RENTING:
				$this->hideSubparts('buying_price', 'wrapper');
				break;
			default:
				break;
		}

		switch ($this->currentView) {
			case 'favorites':
				if (!$this->hasConfValueString('favoriteFieldsInSession')) {
					break;
				}
				$uid = $this->internal['currentRow']['uid'];
				$this->favoritesDataVerbose[$uid] = array();
				foreach (t3lib_div::trimExplode(
					',',
					$this->getConfValueString('favoriteFieldsInSession'),
					true
				) as $key) {
					$this->favoritesDataVerbose[$uid][$key]
						= $this->getFormatter()->getProperty($key);
				}
				break;
			case 'my_objects':
				$this->setListRowContentsForMyObjectsView();
				break;
			default:
				break;
		}

		return $this->getSubpart('LIST_ITEM');
	}

	/**
	 * Creates a result browser for the list view with the current page
	 * highlighted (and not linked). In addition, there will be links to the
	 * previous and the next page.
	 *
	 * @return string HTML code for the page browser, will be empty if there is
	 *                only 1 page of results
	 */
	private function createPagination() {
		if ($this->internal['lastPage'] <= 0) {
			return '';
		}

		$this->setMarker('number_of_results', $this->internal['res_count']);

		$links = $this->createPaginationLink(
			max(0, $this->piVars['pointer'] - 1), '&lt;', false
		);
		$links .= $this->createPageList();
		$links .= $this->createPaginationLink(
			min($this->internal['lastPage'], $this->piVars['pointer'] + 1),
			'&gt;',
			false
		);
		$this->setSubpart('links_to_result_pages', $links);

		return $this->getSubpart('PAGINATION');
	}

	/**
	 * Creates the UI for sorting the list view. Depending on the selection of
	 * sort criteria in the BE, the drop-down list will be populated
	 * correspondingly, with the current sort criterion selected.
	 *
	 * In addition, the radio button for the current sort order is selected.
	 *
	 * @return string HTML for the WRAPPER_SORTING subpart, will be empty if
	 *                no search criteria has been selected in the BE
	 */
	private function createSorting() {
		// Only have the sort form if at least one sort criteria is selected in
		// the BE.
		if (!$this->hasConfValueString('sortCriteria')) {
			return '';
		}

		$this->setMarker('self_url', $this->getSelfUrl());
		$selectedSortCriteria = t3lib_div::trimExplode(
			',', $this->getConfValueString('sortCriteria'), true
		);
		$options = array();
		foreach ($selectedSortCriteria as $selectedSortCriterion) {
			if (in_array($selectedSortCriterion, self::$sortCriteria)) {
				if ($selectedSortCriterion == $this->internal['orderBy']) {
					$selected = ' selected="selected"';
				} else {
					$selected = '';
				}
				$this->setMarker('sort_value', $selectedSortCriterion);
				$this->setMarker('sort_selected', $selected);
				$this->setMarker(
					'sort_label',
					$this->translate('label_' . $selectedSortCriterion)
				);
				$options[] = $this->getSubpart('SORT_OPTION');
			}
		}
		$this->setSubpart('sort_option', implode(LF, $options));
		if (!$this->internal['descFlag']) {
				$this->setMarker('sort_checked_asc', ' checked="checked"');
				$this->setMarker('sort_checked_desc', '');
		} else {
				$this->setMarker('sort_checked_asc', '');
				$this->setMarker('sort_checked_desc', ' checked="checked"');
		}

		return $this->getSubpart('WRAPPER_SORTING');
	}

	/**
	 * Creates the WHERE clause for initListView().
	 *
	 * @return string WHERE clause for initListView(), will not be empty
	 */
	private function createWhereClause() {
		$whereClause = '1=1';
		$showHiddenObjects = -1;

		switch ($this->currentView) {
			case 'favorites':
				// The favorites page should never get cached.
				$GLOBALS['TSFE']->set_no_cache();
				// The favorites list is the only content element that may
				// accept changes to the favorites list.
				$this->processSubmittedFavorites();
				// If the favorites list is empty, make sure to create a valid query
				// that will produce zero results.
				$whereClause .= ($this->getFavorites() != '')
					? ' AND ' . REALTY_TABLE_OBJECTS . '.uid ' .
						'IN(' . $this->getFavorites() . ')'
					: ' AND 0=1';
				$this->favoritesDataVerbose = array();
				break;
			case 'my_objects':
				$whereClause .= ' AND ' . REALTY_TABLE_OBJECTS . '.owner' .
					'=' . $this->getFeUserUid();
				$showHiddenObjects = 1;
				break;
			case 'objects_by_owner':
				$whereClause .= ($this->cachedOwner['uid'] != 0)
					? ' AND ' . REALTY_TABLE_OBJECTS . '.owner' .
						'=' . $this->cachedOwner['uid']
					: ' AND 0=1';
				break;
			default:
				break;
		}

		// The result may only contain non-deleted and non-hidden records except
		// for the my objects view.
		$whereClause .= tx_oelib_db::enableFields(
			REALTY_TABLE_OBJECTS, $showHiddenObjects
		) . tx_oelib_db::enableFields(REALTY_TABLE_CITIES);

		$whereClause .= $this->getWhereClausePartForPidList();

		$whereClause .= ($this->hasConfValueString('staticSqlFilter'))
			? ' AND ' . $this->getConfValueString('staticSqlFilter')
			: '';

		$searchSelection = implode(',', $this->getSearchSelection());
		if (!empty($searchSelection) && ($this->hasConfValueString('checkboxesFilter'))) {
			$whereClause .= ' AND ' . REALTY_TABLE_OBJECTS .
				'.' . $this->getConfValueString('checkboxesFilter') .
				' IN (' . $searchSelection . ')';
		}

		$filterForm = tx_oelib_ObjectFactory::make(
			'tx_realty_filterForm', $this->conf, $this->cObj
		);
		$whereClause .= $filterForm->getWhereClausePart($this->piVars);
		$filterForm->__destruct();

		return $whereClause;
	}

	/**
	 * Creates the ORDER BY statement for initListView().
	 *
	 * @return string ORDER BY statement for initListView(), will be 'uid'
	 *                if 'orderBy' was empty or not within the set of
	 *                allowed sort criteria
	 */
	private function createOrderByStatement() {
		$result = REALTY_TABLE_OBJECTS . '.uid';

		$sortCriterion = isset($this->piVars['orderBy'])
			? $this->piVars['orderBy']
			: $this->getConfValueString('orderBy');
		$descendingFlag = isset($this->piVars['descFlag'])
			? (boolean) $this->piVars['descFlag']
			: $this->getListViewConfValueBoolean('descFlag');

		// checks whether the sort criterion is allowed
		if (in_array($sortCriterion, self::$sortCriteria)) {
			switch ($sortCriterion) {
				// '+0' converts the database column's type to NUMERIC as the
				// columns in the array below are regularly used for numeric
				// values but also might need to contain strings.
				case 'buying_price':
					// intended fall-through
				case 'object_number':
					// intended fall-through
				case 'rent_excluding_bills':
					// intended fall-through
				case 'living_area':
					$result = REALTY_TABLE_OBJECTS . '.' . $sortCriterion . ' +0';
					break;
				// The objects' table only contains the cities' UIDs. The result
				// needs to be sorted by the cities' titles which are in a
				// separate table.
				case 'city':
					$result = REALTY_TABLE_CITIES . '.title';
					break;
				case 'random':
					$result = 'RAND()';
					break;
				case 'number_of_rooms':
					// intended fall-through
				default:
					$result = REALTY_TABLE_OBJECTS . '.' . $sortCriterion;
					break;
			}
			$result .= ($descendingFlag ? ' DESC' : ' ASC');
		}

		return $result;
	}


	/**
	 * Creates the LIMIT statement for initListView().
	 *
	 * @throws Exception if a database query error occurs
	 *
	 * @param string $table
	 *        table for which to create the LIMIT statement, must not be empty
	 * @param string $whereClause
	 *        WHERE clause of the query for which the LIMIT statement will be,
	 *        may be empty
	 *
	 * @return string LIMIT statement for initListView(), will not be empty
	 */
	private function createLimitStatement($table, $whereClause) {
		// number of results to show in a listing
		$this->internal['results_at_a_time'] = t3lib_div::intInRange(
			$this->getListViewConfValueInteger('results_at_a_time'), 0, 1000, 3
		);

		// the maximum number of "pages" in the browse-box: "Page 1", "Page 2", etc.
		$this->internal['maxPages'] = t3lib_div::intInRange(
			$this->getListViewConfValueInteger('maxPages'), 1, 1000, 2
		);

		$this->internal['res_count'] = tx_oelib_db::count($table, $whereClause);

		// The number of the last possible page in a listing
		// (which is the number of pages minus one as the numbering starts at zero).
		// If there are no results, the last page still has the number 0.
		$this->internal['lastPage'] = max(
			0,
			ceil($this->internal['res_count'] / $this->internal['results_at_a_time']) - 1
		);

		$lowerLimit
			= $this->piVars['pointer'] * intval($this->internal['results_at_a_time']);
		$upperLimit = t3lib_div::intInRange(
			$this->internal['results_at_a_time'], 1, 1000
		);

		return $lowerLimit . ',' . $upperLimit;
	}

	/**
	 * Returns a FE user's company if set in $this->cachedOwner, else the first
	 * name and last name if provided, else the name. If none of these is
	 * provided, the user name will be returned. FE user records are expected
	 * to have at least a user name.
	 *
	 * @return string label for the owner, will be empty if no owner
	 *                record was cached or if the cached record is an
	 *                invalid FE user record without a user name
	 */
	private function getOwnerLabel() {
		$result = '';

		// As sr_feuser_register might not be installed, each field needs to be
		// checked to exist.
		foreach (array('username', 'name', 'last_name', 'company') as $key) {
			if (isset($this->cachedOwner[$key])
				&& ($this->cachedOwner[$key] != '')
			) {
				$result = $this->cachedOwner[$key];

				// tries to add a first name if there is a last name
				if (($key == 'last_name')
					&& (isset($this->cachedOwner['first_name']))
				) {
					$result = trim(
						$this->cachedOwner['first_name'] . ' ' .
						$this->cachedOwner[$key]
					);
				}
			}
		}

		return $result;
	}

	/**
	 * Checks whether the checkboxes filter may be created.
	 *
	 * @return boolean true if there is a sort criterion configured and if the
	 *                 criterion is not "city" while the city selector is
	 *                 active, false otherwise
	 */
	private function mayCheckboxesFilterBeCreated() {
		if (!$this->hasConfValueString('checkboxesFilter')) {
			return false;
		}

		return (($this->getConfValueString('checkboxesFilter') != 'city')
			|| !$this->isCitySelectorInUse()
		);
	}

	/**
	 * Returns an array of checkbox items for the list filter.
	 *
	 * @return array HTML for each checkbox item in an array, will be
	 *               empty if there are no entries found for the
	 *               configured filter
	 */
	private function getCheckboxItems() {
		$result = array();

		$filterCriterion = $this->getConfValueString('checkboxesFilter');
		$currentTable = $this->tableNames[$filterCriterion];
		$currentSearch = $this->searchSelectionExists()
			? $this->piVars['search']
			: array();

		$whereClause = 'EXISTS ' . '(' .
			'SELECT * ' .
			'FROM ' . REALTY_TABLE_OBJECTS . ' ' .
			'WHERE ' . REALTY_TABLE_OBJECTS . '.' . $filterCriterion .
				' = ' . $currentTable . '.uid ' .
				$this->getWhereClausePartForPidList() .
				tx_oelib_db::enableFields(REALTY_TABLE_OBJECTS) .
			')' . tx_oelib_db::enableFields($currentTable);

		$checkboxItems = tx_oelib_db::selectMultiple(
			'uid, title', $currentTable, $whereClause
		);

		foreach ($checkboxItems as $checkboxItem) {
			if (in_array($checkboxItem['uid'], $currentSearch)) {
				$checked = ' checked="checked"';
			} else {
				$checked = '';
			}
			$this->setMarker('search_checked', $checked);
			$this->setMarker('search_value', $checkboxItem['uid']);
			$this->setMarker(
				'search_label', htmlspecialchars($checkboxItem['title'])
			);
			$result[] = $this->getSubpart('SEARCH_ITEM');
		}

		return $result;
	}

	/**
	 * Creates the URL of the current page. The URL will contain a flag to
	 * disable caching as this URL also is used for forms with method="post".
	 *
	 * The URL will contain the current piVars if $keepPiVars is set to true.
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
	private function getSelfUrl($keepPiVars = true, array $removeKeys = array()) {
		$piVars = $keepPiVars ? $this->piVars : array();
		unset($piVars['DATA']);
		foreach ($removeKeys as $removeThisKey) {
			if (isset($piVars[$removeThisKey])) {
				unset($piVars[$removeThisKey]);
			}
		}

		return htmlspecialchars(
			$this->cObj->typoLink_URL(
				array(
					'parameter' => $GLOBALS['TSFE']->id,
					'additionalParams' => t3lib_div::implodeArrayForUrl(
						'',
						array($this->prefixId => $piVars),
						'',
						true,
						true
					),
				)
			)
		);
	}

	/**
	 * Creates a link to the single view page.
	 *
	 * $linkText will be used as link text, can also be another tag, e.g. IMG.
	 *
	 * If the current FE user is denied access to the single view page, the
	 * created link will lead to the login page instead, including a
	 * redirect_url parameter to the single view page.
	 *
	 * @param string $linkText
	 *        link text with or without the wrapper marker "|", may also be empty
	 * @param integer $uid UID of the realty object to show, must be > 0
	 * @param string $separateSingleViewPage
	 *        PID or URL of the single view page, set to '' to use the default
	 *        single view page
	 *
	 * @return string link tag, either to the single view page or to the
	 *                login page, will be empty if no link text was provided
	 */
	private function createLinkToSingleViewPageForAnyLinkText(
		$linkText, $uid, $separateSingleViewPage = ''
	) {
		if ($linkText == '') {
			return '';
		}

		$hasSeparateSingleViewPage = ($separateSingleViewPage != '');
		// disables the caching if we are in the favorites list
		$useCache = ($this->currentView != 'favorites');

		if ($hasSeparateSingleViewPage) {
			$completeLink = $this->cObj->typoLink(
				$linkText,
				array('parameter' => $separateSingleViewPage)
			);
		} else {
			$completeLink = $this->cObj->typoLink(
				$linkText,
				array(
					'parameter' => $this->getConfValueInteger('singlePID'),
					'additionalParams' => t3lib_div::implodeArrayForUrl(
						$this->prefixId, array('showUid' => $uid)
					),
					'useCacheHash' => $useCache,
				)
			);
		}

		if ($this->isAccessToSingleViewPageAllowed()) {
			$result = $completeLink;
		} else {
			$result = $this->createLoginPageLink(
				$linkText, $hasSeparateSingleViewPage
			);
		}

		return $result;
	}

	/**
	 * Unhides all subparts that might have been hidden after filling one list
	 * row.
	 *
	 * All subparts that are conditionally displayed, depending on the data of
	 * each list item are affected.
	 */
	private function resetListViewSubparts() {
		$this->unhideSubparts(
			'linked_title,features,teaser,city,living_area,rent_excluding_bills,' .
				'buying_price,district,number_of_rooms,extra_charges,' .
				'list_image_left,list_image_right',
			'',
			'wrapper'
		);
	}

	/**
	 * Gets the title of the current object linked to the single view page.
	 *
	 * @return string the title of the current object linked to the single view
	 *                page, will not be empty
	 */
	private function getLinkedTitle() {
		$realtyObject = tx_oelib_MapperRegistry
			::get('tx_realty_Mapper_RealtyObject')
			->find($this->internal['currentRow']['uid']);

		return $this->createLinkToSingleViewPage(
			$realtyObject->getCroppedTitle(self::CROP_SIZE),
			$this->internal['currentRow']['uid'],
			$this->internal['currentRow']['details_page']
		);
	}

	/**
	 * Gets a comma-separated short list of important features of the current
	 * realty object:
	 * DB relations: apartment_type, house_type, garage_type
	 * boolean: balcony, garden, elevator, barrier_free, assisted_living,
	 * fitted_kitchen
	 * integer: year of construction, first possible usage date, object number
	 *
	 * @return string comma-separated list of features, may be empty
	 */
	private function getFeatureList() {
		$features = array();

		// get features described by DB relations
		foreach (array('apartment_type', 'house_type', 'garage_type') as $key) {
			$propertyTitle = $this->getFormatter()->getProperty($key);
			if ($propertyTitle != '') {
				$features[] = $propertyTitle;
			}
		}

		// get features set with (boolean) checkboxes
		foreach (array(
			'balcony', 'garden', 'elevator', 'barrier_free',
			'assisted_living', 'fitted_kitchen',)
		as $key) {
			if ($this->internal['currentRow'][$key]) {
				$features[] = ($this->translate('label_' . $key . '_short') != '')
					? $this->translate('label_' . $key . '_short')
					: $this->translate('label_' . $key);
			}
		}

		if ($this->internal['currentRow']['old_or_new_building'] > 0) {
			$features[] = $this->translate('label_old_or_new_building_' .
				$this->internal['currentRow']['old_or_new_building']
			);
		}
		if ($this->internal['currentRow']['construction_year'] > 0) {
			$features[] = $this->translate('label_construction_year') . ' ' .
				$this->internal['currentRow']['construction_year'];
		}
		if ($this->internal['currentRow']['usable_from'] != '') {
			$features[] = $this->translate('label_usable_from_short') . ' ' .
				$this->getFormatter()->getProperty('usable_from');
		}
		if (!empty($this->internal['currentRow']['object_number'])) {
			$features[] = $this->translate('label_object_number') . ' ' .
				$this->internal['currentRow']['object_number'];
		}

		return implode(', ', $features);
	}

	/**
	 * Gets an image from the current record's image list as a complete IMG tag
	 * with alternative text and title text, wrapped in a link pointing to the
	 * single view page of the current record.
	 *
	 * The image's size can be limited by two TS setup variables. Their names
	 * need to begin with the string defined as $maxSizeVariable. The variable
	 * for the maximum width will then have the name set in $maxSizVariable with
	 * a "X" appended, the variable for the maximum height with a "Y" appended.
	 *
	 * @param string $maxSizeVariable
	 *        prefix to the TS setup variables that define the max size, will be
	 *        prepended to "X" and "Y", must not be empty
	 * @param integer $offset
	 *        the number of the image to retrieve, zero-based, may be zero
	 *
	 * @return string IMG tag wrapped in a link, will be empty if no image
	 *                is found
	 */
	private function getImageLinkedToSingleView($maxSizeVariable, $offset = 0) {
		return $this->createLinkToSingleViewPageForAnyLinkText(
			$this->getImageTag($maxSizeVariable, $offset),
			$this->internal['currentRow']['uid'],
			$this->internal['currentRow']['details_page']
		);
	}

	/**
	 * Creates a formatter instance for $this->internal['currentRow']['uid'].
	 *
	 * @throws Exception if $this->internal['currentRow'] is not set or empty
	 *
	 * @return tx_realty_pi1_Formatter a formatter for the current row
	 */
	private function getFormatter() {
		if (!isset($this->internal['currentRow'])
			|| empty($this->internal['currentRow'])
		) {
			throw new Exception(
				'$this->internal[\'currentRow\'] must not be empty.'
			);
		}

		$currentUid = $this->internal['currentRow']['uid'];
		if ($this->formatter
			&& ($this->formatter->getProperty('uid') != $currentUid)
		) {
			$this->formatter->__destruct();
			unset($this->formatter);
		}

		if (!$this->formatter) {
			$this->formatter = tx_oelib_ObjectFactory::make(
				'tx_realty_pi1_Formatter', $currentUid, $this->conf, $this->cObj
			);
		}

		return $this->formatter;
	}

	/**
	 * Sets subparts and markers for a list row in the my objects view.
	 */
	private function setListRowContentsForMyObjectsView() {
		$this->setMarker(
			'editor_link',
			$this->createLinkToFeEditorPage(
				'editorPID', $this->internal['currentRow']['uid']
			)
		);
		$this->setMarker(
			'image_upload_link',
			$this->createLinkToFeEditorPage(
				'imageUploadPID', $this->internal['currentRow']['uid']
			)
		);
		$this->setMarker(
			'really_delete',
			$this->translate('label_really_delete') . '\n' .
				$this->translate('label_object_number') . ' ' .
				$this->internal['currentRow']['object_number'] . ': ' .
				$this->internal['currentRow']['title']
		);
		$this->setMarker(
			'delete_link',
			$this->cObj->typoLink_URL(
				array(
					'parameter' => $GLOBALS['TSFE']->id,
					'additionalParams' => t3lib_div::implodeArrayForUrl(
						$this->prefixId,
						array('delete' => $this->internal['currentRow']['uid'])
					),
				)
			)
		);
		$this->setMarker(
			'record_state',
			$this->translate($this->internal['currentRow']['hidden']
				? 'label_pending' : 'label_published'
			)
		);

		$this->setAdvertisementMarkers();
	}

	/**
	 * Creates HTML for a list of links to result pages.
	 *
	 * @return string HTML for the pages list (will not be empty)
	 */
	private function createPageList() {
		/** how many links to the left and right we want to have at most */
		$surroundings = round(($this->internal['maxPages'] - 1) / 2);

		$minPage = max(0, $this->piVars['pointer'] - $surroundings);
		$maxPage = min(
			$this->internal['lastPage'],
			$this->piVars['pointer'] + $surroundings
		);

		$pageLinks = array();
		for ($i = $minPage; $i <= $maxPage; $i++) {
			$pageLinks[] = $this->createPaginationLink($i, $i + 1);
		}

		return implode(LF, $pageLinks);
	}

	/**
	 * Creates a link to the page number $pageNum (starting with 0)
	 * with $linkText as link text. If $pageNum is the current page,
	 * the text is not linked.
	 *
	 * @param integer $pageNum the page number to link to, must be >= 0
	 * @param string $linkText link text, must not be empty
	 * @param boolean $alsoShowNonLinks
	 *        whether to output the link text nonetheless if $pageNum is the
	 *        current page
	 *
	 * @return string HTML code of the link, will be empty if $alsoShowNonLinks
	 *                is false and the $pageNum is the current page
	 */
	private function createPaginationLink(
		$pageNum, $linkText, $alsoShowNonLinks = true
	) {
		$result = '';
		$this->setMarker('linktext', $linkText);

		// Don't link to the current page (for usability reasons).
		if ($pageNum == $this->piVars['pointer']) {
			if ($alsoShowNonLinks) {
				$result = $this->getSubpart('NO_LINK_TO_CURRENT_PAGE');
			}
		} else {
			$piVars = $this->piVars;
			unset($piVars['DATA']);

			$url = $this->cObj->typoLink_URL(
				array(
					'parameter' => $GLOBALS['TSFE']->id,
					'additionalParams' => t3lib_div::implodeArrayForUrl(
						$this->prefixId,
						t3lib_div::array_merge_recursive_overrule(
							$piVars, array('pointer' => $pageNum)
						)
					),
				)
			);

			$this->setMarker('url', $url);
			$result = $this->getSubpart('LINK_TO_OTHER_PAGE');
		}

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
	 * Please note that $this->piVars['remove'] is expected to already be
	 * int-safe.
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
	 *                favorites list (may be empty)
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
	 * Returns the WHERE clause part for the list of allowed PIDs within the
	 * realty objects table.
	 *
	 * @return string WHERE clause part starting with ' AND', containing a
	 *                comma-separated PID list, will be empty if no list
	 *                could be fetched
	 */
	private function getWhereClausePartForPidList() {
		$pidList = tx_oelib_db::createRecursivePageList(
			$this->getConfValueString('pidList'),
			$this->getConfValueInteger('recursive')
		);

		return !empty($pidList)
			? ' AND ' . REALTY_TABLE_OBJECTS . '.pid IN (' . $pidList . ')'
			: '';
	}

	/**
	 * Gets the selected values of the search checkboxes from
	 * $this->piVars['search'].
	 *
	 * @return array array of unique, int-safe values from
	 *               $this->piVars['search'], may be empty, but not null
	 */
	private function getSearchSelection() {
		$result = array();

		if ($this->searchSelectionExists()) {
			foreach ($this->piVars['search'] as $currentItem) {
				$result[] = intval($currentItem);
			}
		}

		return array_unique($result);
	}

	/**
	 * Checks whether the current piVars contain a value for the city selector.
	 *
	 * @return boolean whether the city selector is currently used
	 */
	private function isCitySelectorInUse() {
		return $this->piVars['city'] > 0;
	}

	 /**
	 * Checks whether a search selection exists.
	 *
	 * @return boolean true if a search selection is provided in the
	 *                 current piVars, false otherwise
	 */
	private function searchSelectionExists() {
		return (isset($this->piVars['search'])
			&& is_array($this->piVars['search']));
	}

	/**
	 * Checks whether displaying the single view page currently is allowed. This
	 * depends on whether currently a FE user is logged in and whether, per
	 * configuration, access to the details page is allowed even when no user is
	 * logged in.
	 *
	 * @return boolean true if the details page is allowed to be viewed,
	 *                 false otherwise
	 */
	private function isAccessToSingleViewPageAllowed() {
		return (tx_oelib_FrontEndLoginManager::getInstance()->isLoggedIn()
			|| !$this->getConfValueBoolean('requireLoginForSingleViewPage'));
	}

	/**
	 * Creates a link to the login page. The link will contain a redirect URL to
	 * the page which contains the link.
	 *
	 * @param string $linkText
	 *        link text, HTML tags will not be replaced, may be '|' but not
	 *        empty
	 * @param boolean $hasExternalSingleViewPage
	 *        whether the redirect link needs to be created for an external
	 *        single view page
	 *
	 * @return string link text wrapped by the link to the login page, will not
	 *                be empty
	 */
	private function createLoginPageLink($linkText, $hasExternalSingleViewPage = false) {
		$redirectPage = ($hasExternalSingleViewPage)
			? $this->cObj->lastTypoLinkUrl
			: $this->getSelfUrl(false);

		return $this->cObj->typoLink(
			$linkText,
			array(
				'parameter' => $this->getConfValueInteger('loginPID'),
				'additionalParams' => t3lib_div::implodeArrayForUrl(
					$this->prefixId,
					array(
						'redirect_url' => t3lib_div::locationHeaderUrl(
							$redirectPage
						),
					)
				),
			)
		);
	}

	/**
	 * Creates a link to the single view page.
	 *
	 * $linkText will be used as link text. HTML tags will be replaced with
	 * entities in $linkText.
	 *
	 * If the current FE user is denied access to the single view page, the
	 * created link will lead to the login page instead, including a
	 * redirect_url parameter to the single view page.
	 *
	 * @param string $linkText link text, must not be empty
	 * @param integer $uid UID of the realty object to show, must be > 0
	 * @param string $separateSingleViewPage
	 *        PID or URL of the single view page, leave empty to use the default
	 *        single view page
	 *
	 * @return string link tag, either to the single view page or to the
	 *                login page, will be empty if no link text was provided
	 */
	public function createLinkToSingleViewPage(
		$linkText, $uid, $separateSingleViewPage = ''
	) {
		return $this->createLinkToSingleViewPageForAnyLinkText(
			htmlspecialchars($linkText), $uid, $separateSingleViewPage
		);
	}

	/**
	 * Gets an image from the current record's image list as a complete IMG tag
	 * with alt text and title text (the image caption as defined in the DB).
	 * The image's size can be limited by two TS setup variables.
	 * They names need to begin with the string defined as $maxSizeVariable.
	 * The variable for the maximum width will then have the name set in
	 * $maxSizVariable with a "X" appended. The variable for the maximum height
	 * works the same, just with a "Y" appended.
	 *
	 * Example: If $maxSizeVariable is set to "listImageMax", the maximum width
	 * and height should be stored in the TS setup variables "listImageMaxX" and
	 * "listImageMaxY".
	 *
	 * If no image is found, an empty string is returned.
	 *
	 * @param string $maxSizeVariable
	 *        prefix to the TS setup variables that define the max size, will be
	 *        prepended to "X" and "Y"
	 * @param integer $offset
	 *        the number of the image to retrieve, zero-based, must be >= 0
	 * @param string $id the id attribute, may be empty
	 *
	 * @return string IMG tag, will be empty if there is no current realty
	 *                object or if the current object does not have images
	 */
	private function getImageTag($maxSizeVariable, $offset = 0, $id = '') {
		$result = '';

		$image = $this->getImage($offset);
		if (!empty($image)) {
			$result = $this->createImageTag(
				$image['image'], $maxSizeVariable, $image['caption'], $id
			);
		}

		return $result;
	}

	/**
	 * Sets the markers for the "advertise" link for one row.
	 */
	private function setAdvertisementMarkers() {
		if (!$this->hasConfValueInteger(
			'advertisementPID', 's_advertisements'
		)) {
			$this->hideSubparts('wrapper_advertising');
			return;
		}

		if ($this->isCurrentObjectAdvertised()) {
			$this->hideSubparts('wrapper_advertise_button');
			$this->unhideSubparts('wrapper_advertised_status');
			return;
		}

		$this->unhideSubparts('wrapper_advertise_button');
		$this->hideSubparts('wrapper_advertised_status');

		if ($this->hasConfValueString(
			'advertisementParameterForObjectUid', 's_advertisements'
		)) {
			$linkParameters = t3lib_div::implodeArrayForUrl(
				'',
				array(
					$this->getConfValueString(
						'advertisementParameterForObjectUid',
						's_advertisements'
					) => $this->internal['currentRow']['uid']
				)
			);
		} else {
			$linkParameters = '';
		}

		$this->setMarker(
			'advertise_link',
			$this->cObj->typoLink_URL(
				array(
					'parameter' => $this->getConfValueInteger(
						'advertisementPID', 's_advertisements'
					),
					'additionalParams' => $linkParameters,
				)
			)
		);
	}

	/**
	 * Removes some items to the favorites list (which is stored in an anonymous
	 * session). If some of the UIDs in $itemsToRemove are not in the favorites
	 * list, they will silently being ignored (no harm done here).
	 *
	 * @param array $itemsToRemove
	 *        list of realty object UIDs to to remove (will be intvaled by this
	 *        function), may be empty
	 */
	private function removeFromFavorites(array $itemsToRemove) {
		if ($itemsToRemove) {
			$favorites = $this->getFavoritesArray();

			foreach ($itemsToRemove as $currentItem) {
				$key = array_search($currentItem, $favorites);
				// $key will be false if the item has not been found.
				// Zero, on the other hand, is a valid key.
				if ($key !== false) {
					unset($favorites[$key]);
				}
			}
			$this->storeFavorites($favorites);
		}
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
	 */
	public function addToFavorites(array $itemsToAdd) {
		if ($itemsToAdd) {
			$favorites = $this->getFavoritesArray();

			foreach ($itemsToAdd as $currentItem) {
				$favorites[] = intval($currentItem);
			}
			$this->storeFavorites(array_unique($favorites));
		}
	}

	/**
	 * Writes a formatted string containing object numbers and titles of objects
	 * on the favorites list to session.
	 */
	 public function writeSummaryStringOfFavoritesToSession() {
		tx_oelib_Session::getInstance(tx_oelib_Session::TYPE_TEMPORARY)
			->setAsString(
				'summaryStringOfFavorites',
				$this->createSummaryStringOfFavorites()
			);
	 }

	/**
	 * Returns an image record that is associated with the current realty record.
	 *
	 * @throws Exception if a database query error occurs
	 *
	 * @param integer $offset
	 *        the number of the image to retrieve (zero-based, may be zero)
	 *
	 * @return array the image's caption and file name in an associative
	 *               array, will be empty if no current row was set or if
	 *               the queried image does not exist
	 */
	private function getImage($offset = 0) {
		// The UID will not be set if a hidden or deleted record was requested.
		if (!isset($this->internal['currentRow']['uid'])) {
			return array();
		}

		try {
			$image = tx_oelib_db::selectSingle(
				'image, caption',
				REALTY_TABLE_IMAGES,
				'realty_object_uid = ' . $this->internal['currentRow']['uid'] .
					tx_oelib_db::enableFields(REALTY_TABLE_IMAGES),
				'',
				'uid',
				intval($offset)
			);
		} catch (tx_oelib_Exception_EmptyQueryResult $exception) {
			$image = array();
		}

		return $image;
	}

	/**
	 * Creates an IMG tag for a resized image version of $filename in
	 * this extension's upload directory.
	 *
	 * @param string $filename
	 *        filename of the original image relative to this extension's upload
	 *        directory, must not be empty
	 * @param string $maxSizeVariable
	 *        prefix to the TS setup variables that define the max size, will be
	 *        prepended to "X" and "Y"
	 * @param string $caption
	 *        text used for the alt and title attribute, may be empty
	 * @param string $id the ID attribute, may be empty
	 *
	 * @return string IMG tag
	 */
	private function createImageTag(
		$filename, $maxSizeVariable, $caption = '', $id = ''
	) {
		$fullPath = REALTY_UPLOAD_FOLDER . $filename;
		$maxWidth = $this->getConfValueInteger($maxSizeVariable . 'X');
		$maxHeight = $this->getConfValueInteger($maxSizeVariable . 'Y');

		return $this->createRestrictedImage(
			$fullPath, $caption, $maxWidth, $maxHeight, 0, $caption, $id
		);
	}

	/**
	 * Checks whether the current object is advertised and the advertisement
	 * has not expired yet.
	 *
	 * @return boolean true if the current object is advertised and the
	 *                 advertisement has not expired yet, false otherwise
	 */
	private function isCurrentObjectAdvertised() {
		$advertisementDate = $this->internal['currentRow']['advertised_date'];
		if ($advertisementDate == 0) {
			return false;
		}

		$expiryInDays = $this->getConfValueInteger(
			'advertisementExpirationInDays', 's_advertisements'
		);
		if ($expiryInDays == 0) {
			return true;
		}

		return (
			($advertisementDate + $expiryInDays * ONE_DAY)
				< $GLOBALS['SIM_ACCESS_TIME']
		);
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
	 * @param array list of UIDs in the favorites list to store, must
	 *              already be int-safe, may be empty
	 */
	private function storeFavorites(array $favorites) {
		tx_oelib_Session::getInstance(tx_oelib_Session::TYPE_TEMPORARY)
			->setAsArray(self::FAVORITES_SESSION_KEY, $favorites);
	}

	/**
	 * Creates a formatted string to prefill an e-mail form. The string contains
	 * the object numbers and titles of the objects on the current favorites list.
	 * If there are no selected favorites, an empty string is returned.
	 *
	 * @return string formatted string to use in an e-mail form, may be empty
	 */
	 private function createSummaryStringOfFavorites() {
		$summaryStringOfFavorites = '';

		$currentFavorites = $this->getFavorites();
		if ($currentFavorites != '') {
			$table = $this->tableNames['objects'];
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
	 * Caches the record of the currently selected owner.
	 *
	 * If no value is provided by piVars or if the provided value does not match
	 * a valid owner (a FE user who is non-hidden and non-deleted),
	 * $this->cachedOwner will only contain the element 'uid' which will be
	 * zero then.
	 */
	private function cacheSelectedOwner() {
		$owner = array('uid' => 0);

		if ($this->piVars['owner'] > 0) {
			try {
				$owner = tx_oelib_db::selectSingle(
					'*',
					'fe_users',
					'uid = ' . $this->piVars['owner'] .
						tx_oelib_db::enableFields('fe_users')
				);
			} catch (tx_oelib_Exception_EmptyQueryResult $exception) {}
		}
		$this->cachedOwner = $owner;
	}

	/**
	 * Sets the current list view.
	 *
	 * @param string $currentListView
	 *        the list view to display, must be one of "realty_list", "favorites",
	 *        "my_objects" or "objects_by_owner"
	 */
	public function setCurrentView($currentView) {
		if(!in_array(
			$currentView,
			array('realty_list', 'favorites', 'my_objects', 'objects_by_owner'))
		) {
			throw new Exception(
				'The given list view type "' . $currentView . '" is not defined.'
			);
		}

		$this->currentView = $currentView;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/pi1/class.tx_realty_pi1_ListView.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/pi1/class.tx_realty_pi1_ListView.php']);
}
?>