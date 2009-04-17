<?php
/***************************************************************
* Copyright notice
*
* (c) 2006-2009 Oliver Klee <typo3-coding@oliverklee.de>
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

require_once(t3lib_extMgm::extPath('oelib') . 'class.tx_oelib_Autoloader.php');

require_once(t3lib_extMgm::extPath('realty') . 'lib/tx_realty_constants.php');
require_once(t3lib_extMgm::extPath('realty') . 'pi1/class.tx_realty_contactForm.php');
require_once(t3lib_extMgm::extPath('realty') . 'pi1/class.tx_realty_frontEndEditor.php');
require_once(t3lib_extMgm::extPath('realty') . 'pi1/class.tx_realty_frontEndImageUpload.php');
require_once(t3lib_extMgm::extPath('realty') . 'pi1/class.tx_realty_filterForm.php');
require_once(t3lib_extMgm::extPath('realty') . 'pi1/class.tx_realty_offererList.php');

/**
 * Plugin 'Realty List' for the 'realty' extension.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 * @author Saskia Metzler <saskia@merlin.owl.de>
 */
class tx_realty_pi1 extends tx_oelib_templatehelper {
	/**
	 * @var string same as class name
	 */
	public $prefixId = 'tx_realty_pi1';

	/**
	 * @var string path to this script relative to the extension directory
	 */
	public $scriptRelPath = 'pi1/class.tx_realty_pi1.php';

	/**
	 * @var string the extension key
	 */
	public $extKey = 'realty';

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
	 * @var string session key for storing the favorites list
	 */
	const FAVORITES_SESSION_KEY = 'tx_realty_favorites';

	/**
	 * @var string session key for storing data of all favorites that
	 *                     currently get displayed
	 */
	const FAVORITES_SESSION_KEY_VERBOSE = 'tx_realty_favorites_verbose';

	/**
	 * @var array the data of the currently displayed favorites using the keys
	 *            [uid][fieldname]
	 */
	private $favoritesDataVerbose;

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
	 * @var boolean whether to check cHash
	 */
	public $pi_checkCHash = true;

	/**
	 * @var boolean whether this class is called in the test mode
	 */
	private $isTestMode = false;

	/**
	 * @var tx_realty_pi1_Formatter formatter for prices, areas etc.
	 */
	private $formatter = null;

	/**
	 * @var array FE user record, will at least contain the element 'uid'
	 */
	private $cachedOwner = array('uid' => 0);

	/**
	 * @var array existing types of list views
	 */
	private static $listViews = array(
		'favorites', 'my_objects', 'objects_by_owner', 'realty_list'
	);

	/**
	 * The constructor.
	 *
	 * @param boolean whether this class is called in the test mode
	 */
	public function __construct($isTestMode = false) {
		$this->isTestMode = $isTestMode;
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
	 * Displays the Realty Manager HTML.
	 *
	 * @param string (not used)
	 * @param array TypoScript configuration for the plugin
	 *
	 * @return string HTML for the plugin
	 */
	public function main($unused, array $conf) {
		$this->init($conf);
		$this->pi_initPIflexForm();

		$this->setLocaleConvention();
		$this->getTemplateCode();
		$this->setLabels();

		if (strstr($this->cObj->currentRecord, 'tt_content')) {
			$this->conf['pidList'] = $this->getConfValueString('pages');
			$this->conf['recursive'] = $this->getConfValueInteger('recursive');
		}

		$this->internal['currentTable'] = $this->tableNames['objects'];
		$this->ensureIntegerPiVars(array(
			'city', 'image', 'remove', 'showUid', 'delete', 'owner', 'uid'
		));
		$this->cacheSelectedOwner();

		// Checks the configuration and displays any errors.
		// The direct return value from $this->checkConfiguration() is not used
		// as this would ignore any previous error messages.
		$this->setFlavor($this->getCurrentView());
		$this->checkConfiguration();

		$errorViewHtml = $this->checkAccessAndGetHtmlOfErrorView();

		return $this->pi_wrapInBaseClass(
			(($errorViewHtml == '')
				? $this->getHtmlForCurrentView()
				: $errorViewHtml
			) . $this->getWrappedConfigCheckMessage()
		);
	}

	/**
	 * Returns the HTML for the current view.
	 *
	 * @see Bug #2432
	 *
	 * @return string HTML for the current view, will not be empty
	 */
	private function getHtmlForCurrentView() {
		switch ($this->getCurrentView()) {
			case 'gallery':
				$result = $this->createGallery();
				break;
			case 'filter_form':
				$filterFormClassName = t3lib_div::makeInstanceClassName(
					'tx_realty_filterForm'
				);
				$filterForm = new $filterFormClassName($this->conf, $this->cObj);
				$result = $filterForm->render($this->piVars);
				$filterForm->__destruct();
				break;
			case 'single_view':
				$singleViewClassName = t3lib_div::makeInstanceClassName(
					'tx_realty_pi1_SingleView'
				);
				$singleView = new $singleViewClassName(
					$this->conf, $this->cObj, $this->isTestMode
				);
				$result = $singleView->render($this->piVars);
				$singleView->__destruct();

				// TODO: This can be moved to the single view class when
				// Bug #2432 is fixed.
				if ($result == '') {
					$this->setEmptyResultView();
					tx_oelib_headerProxyFactory::getInstance()->getHeaderProxy()
						->addHeader('Status: 404 Not Found');
					$result = $this->getSubpart('SINGLE_VIEW');
				}
				break;
			case 'contact_form':
				$contactFormClassName = t3lib_div::makeInstanceClassName(
					'tx_realty_contactForm'
				);
				$contactForm = new $contactFormClassName($this->conf, $this->cObj);
				$formData = $this->piVars;
				$formData['summaryStringOfFavorites']
					= $this->createSummaryStringOfFavorites();
				$result = $contactForm->render($formData);
				$contactForm->__destruct();
				break;
			case 'fe_editor':
				$frontEndEditorClassName = t3lib_div::makeInstanceClassName(
					'tx_realty_frontEndEditor'
				);
				$frontEndEditor = new $frontEndEditorClassName(
					$this->conf,
					$this->cObj,
					$this->piVars['showUid'],
					'pi1/tx_realty_frontEndEditor.xml'
				);
				$result = $frontEndEditor->render();
				$frontEndEditor->__destruct();
				break;
			case 'image_upload':
				$imageUploadClassName = t3lib_div::makeInstanceClassName(
					'tx_realty_frontEndImageUpload'
				);
				$imageUpload = new $imageUploadClassName(
					$this->conf,
					$this->cObj,
					$this->piVars['showUid'],
					'pi1/tx_realty_frontEndImageUpload.xml'
				);
				$result = $imageUpload->render();
				$imageUpload->__destruct();
				break;
			case 'offerer_list':
				$offererListClassName = t3lib_div::makeInstanceClassName(
					'tx_realty_offererList'
				);
				$offererList = new $offererListClassName($this->conf, $this->cObj);
				$result = $offererList->render();
				$offererList->__destruct();
				break;
			default:
				// All other return values of getCurrentView stand for list views.
				$result = $this->createListView();
				break;
		}

		return $result;
	}

	/**
	 * Checks whether a user has access to the current view and returns the HTML
	 * of an error view if not.
	 *
	 * @return string HTML for the error view, will be empty if a user has
	 *                access
	 */
	private function checkAccessAndGetHtmlOfErrorView() {
		// This will be moved to the access check when Bug #1480 is fixed.
		if (!$this->getConfValueBoolean('requireLoginForSingleViewPage')
			&& in_array($this->getCurrentView(), array('gallery', 'single_view'))
		) {
			return '';
		}

		try {
			t3lib_div::makeInstance('tx_realty_pi1_AccessCheck')->checkAccess(
				$this->getCurrentView(), $this->piVars
			);
			$result = '';
		} catch (tx_oelib_Exception_AccessDenied $exception) {
			$errorViewClassName = t3lib_div::makeInstanceClassName(
				'tx_realty_pi1_ErrorView'
			);
			$errorView = new $errorViewClassName($this->conf, $this->cObj);
			$result = $errorView->render(array($exception->getMessage()));
			$errorView->__destruct();
		}

		return $result;
	}

	/**
	 * Shows a list of database entries.
	 *
	 * @return string HTML list of table entries or the HTML for an error
	 *                view if no items were found
	 */
	private function createListView() {
		$this->addHeaderForListView();

		// Initially most subparts are hidden. Depending on the type of list
		// view, they will be set to unhidden again.
		$this->hideSubparts(
			'list_filter,back_link,new_record_link,wrapper_contact,' .
			'add_to_favorites_button,remove_from_favorites_button,' .
			'wrapper_editor_specific_content,wrapper_checkbox,favorites_url,' .
			'limit_heading, google_map'
		);
		switch ($this->getCurrentView()) {
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

		$googleMapsClassName = t3lib_div::makeInstanceClassName(
			'tx_realty_pi1_GoogleMapsView'
		);
		$googleMapsView = new $googleMapsClassName(
			$this->conf, $this->cObj, $this->isTestMode
		);

		$listItems = '';
		$rowCounter = 0;

		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbResult)) {
			$this->internal['currentRow'] = $row;
			$listItems .= $this->createListRow($rowCounter);
			$googleMapsView->setMapMarker(
				$this->internal['currentRow']['uid'], true
			);
			$rowCounter++;
		}
		$GLOBALS['TYPO3_DB']->sql_free_result($dbResult);

		$this->setSubpart('list_item', $listItems);
		$this->setSubpart('pagination', $this->createPagination());
		$this->setSubpart('wrapper_sorting', $this->createSorting());
		$this->unhideSubparts('google_map');
		$this->setSubpart('google_map', $googleMapsView->render());

		$googleMapsView->__destruct();
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
	 * Processes the deletion of a realty record.
	 */
	private function processDeletion() {
		// no need for a front-end editor if there is nothing to delete
		if ($this->piVars['delete'] == 0) {
			return;
		}

		// For testing, the FE editor's FORMidable object must not be created.
		$frontEndEditorClassName = t3lib_div::makeInstanceClassName(
			'tx_realty_frontEndEditor'
		);
		$frontEndEditor = new $frontEndEditorClassName(
			$this->conf,
			$this->cObj,
			$this->piVars['delete'],
			'pi1/tx_realty_frontEndEditor.xml',
			$this->isTestMode
		);
		$frontEndEditor->deleteRecord();
		$frontEndEditor->__destruct();
	}

	/**
	 * Returns the database query result for the realty records to list.
	 * The result will be limited according to the configuration of
	 * "results_at_a_time" and the current page number. It will also be sorted
	 * according to the current value of "orderBy" (or by UID if there is none)
	 * and the value of "descFlag". The very first records will always be those
	 * with a value for "sorting" set within the record itself.
	 *
	 * @return resource the result of a DB query for the realty objects to list
	 */
	private function initListView() {
		// To ensure that sorting by cities actually sorts the titles and not
		// the cities' UIDs, the JOIN on the cities table is needed.
		$table = REALTY_TABLE_OBJECTS . ' INNER JOIN ' . REALTY_TABLE_CITIES .
			' ON ' . REALTY_TABLE_OBJECTS . '.city = ' .
			REALTY_TABLE_CITIES . '.uid';
		$whereClause = $this->createWhereClause();
		$sortingColumn = REALTY_TABLE_OBJECTS . '.sorting';

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
			throw new Exception(DATABASE_QUERY_ERROR);
		}

		return $dbResult;
	}

	/**
	 * Creates the WHERE clause for initListView().
	 *
	 * @return string WHERE clause for initListView(), will not be empty
	 */
	private function createWhereClause() {
		$whereClause = '1=1';
		$showHiddenObjects = -1;

		switch ($this->getCurrentView()) {
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

		// finds only cities that match the UID in piVars['city']
		if ($this->piVars['city'] != 0) {
			$whereClause .=  ' AND '.REALTY_TABLE_OBJECTS . '.city' .
				'=' . $this->piVars['city'];
		}

		$searchSelection = implode(',', $this->getSearchSelection());
		if (!empty($searchSelection) && ($this->hasConfValueString('checkboxesFilter'))) {
			$whereClause .= ' AND ' . REALTY_TABLE_OBJECTS .
				'.' . $this->getConfValueString('checkboxesFilter') .
				' IN (' . $searchSelection . ')';
		}

		$filterFormClassName = t3lib_div::makeInstanceClassName(
			'tx_realty_filterForm'
		);
		$filterForm = new $filterFormClassName($this->conf, $this->cObj);
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
				case 'number_of_rooms':
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
	 * @param string table for which to create the LIMIT statement, must
	 *               not be empty
	 * @param string WHERE clause of the query for which the LIMIT
	 *               statement will be, may be empty
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

		// get number of records
		$dbResultCounter = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'COUNT(*) AS number',
			$table,
			$whereClause
		);
		if (!$dbResultCounter) {
			throw new Exception(DATABASE_QUERY_ERROR);
		}

		$counterRow = $GLOBALS['TYPO3_DB']->sql_fetch_row($dbResultCounter);
		$GLOBALS['TYPO3_DB']->sql_free_result($dbResultCounter);
		$this->internal['res_count'] = $counterRow[0];

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
	 * Sets the view to an empty result message specific for the requested view.
	 */
	private function setEmptyResultView() {
		$view = $this->getCurrentView();
		$noResultsMessage = 'message_noResultsFound_' . $view;

		// The objects-by-owner-view has two reasons for being empty.
		if (($view == 'objects_by_owner') && ($this->cachedOwner['uid'] == 0)) {
			$noResultsMessage = 'message_no_such_owner';
		}

		$this->setMarker(
			'message_noResultsFound', $this->translate($noResultsMessage)
		);

		// If the current view is a list view, the subpart to fill will be
		// 'list_result'. All non-list view's subparts to fill here are named
		// '[type of view]_result'.
		if (in_array($view, self::$listViews)) {
			$view = 'list';
		}
		$this->setSubpart(
			$view . '_result', $this->getSubpart('EMPTY_RESULT_VIEW')
		);
	}

	/**
	 * Returns a list row according to the current 'showUid'.
	 *
	 * @return array record to display in the single view, will be empty
	 *               if the record to display does not exist
	 */
	private function getCurrentRowForShowUid() {
		$showUid = 'uid=' . $this->piVars['showUid'];
		$whereClause = '(' . $showUid .
			tx_oelib_db::enableFields(REALTY_TABLE_OBJECTS) . ')';
		// Logged-in users may also see their hidden objects in the single view.
		if (tx_oelib_FrontEndLoginManager::getInstance()->isLoggedIn()) {
			$whereClause .= ' OR (' . $showUid .
				' AND owner=' . $this->getFeUserUid() .
				tx_oelib_db::enableFields(REALTY_TABLE_OBJECTS, 1) . ')';
		}

		$dbResult = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'*',
			REALTY_TABLE_OBJECTS,
			$whereClause
		);
		if (!$dbResult) {
			throw new Exception(DATABASE_QUERY_ERROR);
		}

		$result = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbResult);
		$GLOBALS['TYPO3_DB']->sql_free_result($dbResult);

		return ($result !== false) ? $result : array();
	}

	/**
	 * Fills the wrapper with the link to the contact form if displaying contact
	 * information is enabled for the favorites view. Otherwise hides the
	 * complete wrapper.
	 */
	private function fillOrHideContactWrapper() {
		if (($this->getCurrentView() != 'favorites')
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
	 * Returns a single table row for list view.
	 *
	 * @param integer Row counter. Starts at 0 (zero). Used for
	 *                alternating class values in the output rows.
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

		switch ($this->getCurrentView()) {
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
	 * Returns the title of the current object linked to the single view page.
	 */
	private function getLinkedTitle() {
		return $this->createLinkToSingleViewPage(
			$this->getFormatter()->getProperty('cropped_title'),
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
	 * @return string comma-separated list of features
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
	 * @param string prefix to the TS setup variables that define the
	 *               max size, will be prepended to "X" and "Y"
	 * @param integer the number of the image to retrieve, zero-based,
	 *                may be zero
	 * @param string the id attribute, may be empty
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
	 * Gets an image from the current record's image list as a complete IMG tag
	 * with alternative text and title text, wrapped in a link pointing to the
	 * single view page of the current record.
	 *
	 * The image's size can be limited by two TS setup variables. Their names
	 * need to begin with the string defined as $maxSizeVariable. The variable
	 * for the maximum width will then have the name set in $maxSizVariable with
	 * a "X" appended, the variable for the maximum height with a "Y" appended.
	 *
	 * @param string prefix to the TS setup variables that define the max size,
	 *               will be prepended to "X" and "Y", must not be empty
	 * @param integer the number of the image to retrieve, zero-based, may be
	 *                zero
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
	 * Returns an image record that is associated with the current realty record.
	 *
	 * @throws Exception if a database query error occurs
	 *
	 * @param integer the number of the image to retrieve (zero-based,
	 *                may be zero)
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

		$dbResult = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'image, caption',
			REALTY_TABLE_IMAGES,
			'realty_object_uid=' . $this->internal['currentRow']['uid'] .
				tx_oelib_db::enableFields(REALTY_TABLE_IMAGES),
			'',
			'uid',
			intval($offset) . ',1'
		);
		if (!$dbResult) {
			throw new Exception(DATABASE_QUERY_ERROR);
		}

		$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbResult);
		$GLOBALS['TYPO3_DB']->sql_free_result($dbResult);

		return $row ? $row : array();
	}

	/**
	 * Counts the images that are associated with the current record.
	 *
	 * @return integer the number of images associated with the current
	 *                 record, may be zero
	 */
	private function countImages() {
		// The UID will not be set if a hidden or deleted record was requested.
		if (!isset($this->internal['currentRow']['uid'])) {
			return 0;
		}

		$dbResult = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'COUNT(*) as number',
			REALTY_TABLE_IMAGES,
			'realty_object_uid=' . $this->internal['currentRow']['uid'] .
				tx_oelib_db::enableFields(REALTY_TABLE_IMAGES)
		);
		if (!$dbResult) {
			throw new Exception(DATABASE_QUERY_ERROR);
		}

		$dbResultRow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbResult);
		$GLOBALS['TYPO3_DB']->sql_free_result($dbResult);

		return $dbResultRow['number'];
	}

	/**
	 * Creates an IMG tag for a resized image version of $filename in
	 * this extension's upload directory.
	 *
	 * @param string filename of the original image relative to this
	 *               extension's upload directory, must not be empty
	 * @param string prefix to the TS setup variables that define the
	 *               max size, will be prepended to "X" and "Y"
	 * @param string text used for the alt and title attribute, may be empty
	 * @param string the id attribute, may be empty
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
	 * Creates an image gallery for the selected gallery item.
	 * If that item contains no images or the image number is invalid, an error
	 * message will be displayed instead.
	 *
	 * @return string HTML of the gallery (will not be empty)
	 */
	private function createGallery() {
		$result = '';
		$isOkay = false;

		$this->includeJavaScriptForGallery();

		if ($this->hasShowUidInUrl()) {
			$this->internal['currentRow'] = $this->getCurrentRowForShowUid();

			// This sets the title of the page for display and for use in indexed search results.
			if (!empty($this->internal['currentRow']['title'])) {
				$GLOBALS['TSFE']->page['title'] = $this->internal['currentRow']['title'];
				$GLOBALS['TSFE']->indexedDocTitle = $this->internal['currentRow']['title'];
			}

			$numberOfImages = $this->countImages();
			if ($numberOfImages
				&& ($this->piVars['image'] >= 0)
				&& ($this->piVars['image'] < $numberOfImages)
			) {
				$this->setMarker(
					'title',
					htmlspecialchars($this->internal['currentRow']['title'])
				);
 				$this->createGalleryFullSizeImage();
				$this->setSubpart('thumbnail_item', $this->createGalleryThumbnails());
				$result = $this->getSubpart('GALLERY_VIEW');
 				$isOkay = true;
			}
		}

		if (!$isOkay) {
			$this->setMarker(
				'message_invalidImage', $this->translate('message_invalidImage')
			);
			$result = $this->getSubpart('GALLERY_ERROR');
			// sends a 404 to inform crawlers that this URL is invalid
			tx_oelib_headerProxyFactory::getInstance()->getHeaderProxy()
				->addHeader('Status: 404 Not Found');
		}

		return $result;
	}

 	/**
	 * Includes the JavaScript used to display fullsize images in the gallery.
	 */
	private function includeJavaScriptForGallery() {
		$GLOBALS['TSFE']->additionalHeaderData[$this->prefixId . '_gallery']
			= '<script src="' . t3lib_extMgm::extRelPath($this->extKey) .
				'pi1/tx_realty_pi1.js" type="text/javascript">' .
				'</script>';
	}

	/**
	 * Creates the gallery's full size image for the image specified in
	 * $this->piVars['image'] and fills in the corresponding markers and
	 * subparts.
	 *
	 * The image's size is limited by galleryFullSizeImageX and
	 * galleryFullSizeImageY in TS setup.
	 */
	private function createGalleryFullSizeImage() {
		$this->setMarker(
			'image_fullsize',
			$this->getImageTag(
				'galleryFullSizeImage',
				$this->piVars['image'],
				'tx_realty_fullsizeImage'
			)
		);

		$image = $this->getImage($this->piVars['image']);
		$this->setMarker(
			'caption_fullsize',
			(!empty($image) ? $image['caption'] : '')
		);
	}

	/**
	 * Creates thumbnails of the current record for the gallery. The thumbnails
	 * are linked for the full-size display of the corresponding image (except
	 * for the thumbnail of the current image which is not linked).
	 *
	 * Each image's size is limited by galleryThumbnailX and galleryThumbnailY
	 * in TS setup.
	 *
	 * @return string HTML for all thumbnails
	 */
	private function createGalleryThumbnails() {
		$result = '';
		$totalNumberOfImages = $this->countImages();

		for ($imageNumber = 0; $imageNumber < $totalNumberOfImages; $imageNumber++) {
			// the current image needs a unique class name
			$suffixForCurrent
				= ($imageNumber == $this->piVars['image']) ? '-current' : '';

			$currentImageTag = $this->getImageTag(
				'galleryThumbnail', $imageNumber, 'tx_realty_thumbnail_' . $imageNumber
			);

			$this->setMarker(
				'image_thumbnail',
				'<a ' .
					$this->getHrefAttribute($imageNumber) .
					'id="tx_realty_imageLink_' . $imageNumber . '" ' .
					'class="tx-realty-pi1-thumbnail' . $suffixForCurrent . '" ' .
					$this->getOnclickAttribute($imageNumber) .
					'>' . $currentImageTag . '</a>'
			);

			$result .= $this->getSubpart('THUMBNAIL_ITEM');
		}

		return $result;
	}

	/**
	 * Returns the href attribute for a thumbnail.
	 *
	 * @param integer number of the image for which to get the href
	 *                attribute, must be >= 0
	 *
	 * @return string href attribute, will not be empty
	 */
	private function getHrefAttribute($image) {
		$piVars = $this->piVars;
		unset($piVars['DATA']);

		return 'href="' . htmlspecialchars($this->cObj->typoLink_URL(array(
			'parameter' => $GLOBALS['TSFE']->id,
			'additionalParams' => t3lib_div::implodeArrayForUrl(
				$this->prefixId,
				t3lib_div::array_merge_recursive_overrule(
					$piVars, array('image' => $image)
				)
			),
			'useCacheHash' => true,
		))) . '" ';
	}

	/**
	 * Returns the onclick attribute for a thumbnail.
	 *
	 * @param integer number of the image for which to get the onclick
	 *                attribute, must be >= 0
	 *
	 * @return string onclick attribute, will not be empty
	 */
	private function getOnclickAttribute($image) {
		$imageTag = $this->getImageTag('galleryFullSizeImage', $image);
		// getImageTag will always return the img tag beginning with '<img src="',
		// which is 10 characters long followed by the link we need and the
		// width attribute afterwards.
		$linkToFullsizeImage = substr(
			$imageTag, 10, (strrpos($imageTag, ' width="') - 11)
		);

		return 'onclick=' .
			'"showFullsizeImage(this.id, \'' . $linkToFullsizeImage . '\'); ' .
			'return false;"';
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
	 * Adds some items to the favorites list (which is stored in an anonymous
	 * session). The object UIDs are added to the list regardless of whether
	 * there actually are objects with those UIDs. That case is harmless
	 * because the favorites list serves as a filter merely.
	 *
	 * @param array list of realty object UIDs to add (will be intvaled by this
	 *              function), may be empty or even null
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
	 * Removes some items to the favorites list (which is stored in an anonymous
	 * session). If some of the UIDs in $itemsToRemove are not in the favorites
	 * list, they will silently being ignored (no harm done here).
	 *
	 * @param array list of realty object UIDs to to remove (will be intvaled by
	 *              this function), may be empty
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
	public function storeFavorites(array $favorites) {
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
	 public function createSummaryStringOfFavorites() {
	 	$summaryStringOfFavorites = '';

	 	$currentFavorites = $this->getFavorites();
		if ($currentFavorites != '') {
		 	$table = $this->tableNames['objects'];
		 	$dbResult = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'object_number, title',
				$table,
				'uid IN (' . $currentFavorites . ')' .
					tx_oelib_db::enableFields($table)
			);
			if (!$dbResult) {
				throw new Exception(DATABASE_QUERY_ERROR);
			}

			$summaryStringOfFavorites
				= $this->translate('label_on_favorites_list') . LF;

			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbResult)) {
				$objectNumber = $row['object_number'];
				$objectTitle = $row['title'];
				$summaryStringOfFavorites
					.= '* ' . $objectNumber . ' ' . $objectTitle . LF;
			}

			$GLOBALS['TYPO3_DB']->sql_free_result($dbResult);
		}

	 	return $summaryStringOfFavorites;
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
	 * Gets the selected values of the search checkboxes from
	 * $this->piVars['search'].
	 *
	 * @return array array of unique, int-safe values from
	 *               $this->piVars['search'] (may be empty, but not null)
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
	 * Creates the URL to the favorites page. If
	 * $this->getConfValueInteger('favoritesPID') is not set, a link to the
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
	 * Creates the URL of the current page. The URL will contain a flag to
	 * disable caching as this URL also is used for forms with method="post".
	 *
	 * The URL will contain the current piVars if $keepPiVars is set to true.
	 * The URL will already be htmlspecialchared.
	 *
	 * @param boolean whether the current piVars should be kept
	 *
	 * @return string htmlspecialchared URL of the current page, will not
	 *                be empty
	 */
	private function getSelfUrl($keepPiVars = true) {
		$piVars = $keepPiVars ? $this->piVars : array();
		unset($piVars['DATA']);

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
	 * Creates a result browser for the list view with the current page
	 * highlighted (and not linked). In addition, there will be links to the
	 * previous and the next page.
	 *
	 * This function will return an empty string if there is only 1 page of
	 * results.
	 *
	 * @return string HTML code for the page browser (may be empty)
	 */
	private function createPagination() {
		if ($this->internal['lastPage'] <= 0) {
			return '';
		}

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
	 * @param integer the page number to link to
	 * @param string link text (may not be empty)
	 * @param boolean whether to output the link text nonetheless if $pageNum is
	 *                the current page
	 *
	 * @return string HTML code of the link (will be empty if $alsoShowNonLinks
	 *                is false and the $pageNum is the current page)
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
	 * Creates the UI for sorting the list view. Depending on the selection of
	 * sort criteria in the BE, the drop-down list will be populated
	 * correspondingly, with the current sort criterion selected.
	 *
	 * In addition, the radio button for the current sort order is selected.
	 *
	 * If there are no search criteria selected in the BE, this function will
	 * return an empty string.
	 *
	 * @return string HTML for the WRAPPER_SORTING subpart
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
	 * Creates the search checkboxes for the DB field selected in the BE.
	 * If no field is selected in the BE or there are not DB records with
	 * non-empty data for that field, this function returns an empty string.
	 *
	 * This function will also return an empty string if "city" is selected in
	 * the BE and $this->piVars['city'] is set (by the city selector).
	 *
	 * @return string HTML for the search bar (may be empty)
	 */
	private function createCheckboxesFilter() {
		if (!$this->mayCheckboxesFilterBeCreated()) {
			return '';
		}

		$items = $this->getCheckboxItems();
		if (!empty($items)) {
			$this->setSubpart('search_item', implode(LF, $items));
			$this->setMarker(
				'self_url_without_pivars', $this->getSelfUrl(false)
			);

			$result = $this->getSubpart('LIST_FILTER');
		} else {
			$result = '';
		}

		return $result;
	}

	/**
	 * Checks whether the checkboxes filter may be created. It should only be
	 * displayed if there is a sort criterion configured and if the criterion is
	 * not "city" while the city selector is active.
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

		$dbResult = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'uid, title',
			$currentTable,
			'EXISTS ' . '(' .
				'SELECT * ' .
				'FROM ' . REALTY_TABLE_OBJECTS . ' ' .
				'WHERE ' . REALTY_TABLE_OBJECTS . '.' . $filterCriterion .
					'=' . $currentTable . '.uid ' .
					$this->getWhereClausePartForPidList() .
					tx_oelib_db::enableFields(REALTY_TABLE_OBJECTS) .
				')' . tx_oelib_db::enableFields($currentTable)
		);
		if (!$dbResult) {
			throw new Exception(DATABASE_QUERY_ERROR);
		}

		while ($dbResultRow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbResult)) {
			if (in_array($dbResultRow['uid'], $currentSearch)) {
				$checked = ' checked="checked"';
			} else {
				$checked = '';
			}
			$this->setMarker('search_checked', $checked);
			$this->setMarker('search_value', $dbResultRow['uid']);
			$this->setMarker(
				'search_label', htmlspecialchars($dbResultRow['title'])
			);
			$result[] = $this->getSubpart('SEARCH_ITEM');
		}

		$GLOBALS['TYPO3_DB']->sql_free_result($dbResult);

		return $result;
	}

	/**
	 * Returns the title for the list of objects by one owner. The title will
	 * contain a localized string for 'label_offerings_by' plus the owner's
	 * label.
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
	 * Checks whether the current piVars contain a value for the city selector.
	 *
	 * @return boolean whether the city selector is currently used
	 */
	private function isCitySelectorInUse() {
		return $this->piVars['city'] > 0;
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
	 * Returns the current view.
	 *
	 * @return string Name of the current view ('realty_list',
	 *                'contact_form', 'favorites', 'fe_editor',
	 *                'filter_form', 'gallery', 'image_upload',
	 *                'my_objects', 'offerer_list' or 'objects_by_owner'),
	 *                will not be empty.
	 *                If no view is set, 'realty_list' is returned as this
	 *                is the fallback case.
	 */
	private function getCurrentView() {
		$whatToDisplay = $this->getConfValueString('what_to_display');

		if (in_array($whatToDisplay, array(
			'realty_list',
			'single_view',
			'gallery',
			'favorites',
			'filter_form',
			'contact_form',
			'my_objects',
			'offerer_list',
			'objects_by_owner',
			'fe_editor',
			'image_upload',
		))) {
			$result = $whatToDisplay;
		} else {
			$result = 'realty_list';
		}

		return $result;
	}

	/**
	 * Checks whether the showUid parameter is set and contains a positive
	 * number.
	 *
	 * @return boolean true if showUid is set and is a positive integer,
	 *                 false otherwise
	 */
	private function hasShowUidInUrl() {
		return $this->piVars['showUid'] > 0;
	}

	/**
	 * Checks that we are properly initialized.
	 *
	 * @return boolean true if we are properly initialized, false otherwise
	 */
	public function isInitialized() {
		return $this->isInitialized;
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
	public function isAccessToSingleViewPageAllowed() {
		return (tx_oelib_FrontEndLoginManager::getInstance()->isLoggedIn()
			|| !$this->getConfValueBoolean('requireLoginForSingleViewPage'));
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
	 * @param string link text, must not be empty
	 * @param integer UID of the realty object to show
	 * @param string PID or URL of the single view page, set to '' to use
	 *               the default single view page
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
	 * Creates a link to the single view page.
	 *
	 * $linkText will be used as link text, can also be another tag, e.g. IMG.
	 *
	 * If the current FE user is denied access to the single view page, the
	 * created link will lead to the login page instead, including a
	 * redirect_url parameter to the single view page.
	 *
	 * @param string link text, may be '|' but not empty
	 * @param integer UID of the realty object to show
	 * @param string PID or URL of the single view page, set to '' to use
	 *               the default single view page
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
		$useCache = ($this->getCurrentView() != 'favorites');

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
	 * Creates a link to the login page. The link will contain a redirect URL to
	 * the page which contains the link.
	 *
	 * @param string link text, HTML tags will not be replaced, may be '|'
	 *               but not empty
	 * @param boolean whether the redirect link needs to be created for an
	 *                external single view page
	 *
	 * @return string link text wrapped by the link to the login page,
	 *                will not be empty
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
	 * Creates a link to the FE editor page.
	 *
	 * @param string key of the configuration value with the PID, must
	 *               not be empty
	 * @param integer UID of the object to be loaded for editing, must be
	 *                integer >= 0 (Zero will open the FE editor for a new
	 *                record to insert.)
	 *
	 * @return string $linkText wrapped in link tags, will not be empty
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
	 * Sets the data for the current row in the list view.
	 *
	 * This function is intended to be used for testing purposes only.
	 *
	 * @param array associative array with the data for the current
	 *              row like it could have been retrieved from the DB,
	 *              must be neither empty nor null
	 */
	public function setCurrentRow(array $currentRow) {
		if (!is_array($this->internal)) {
			$this->internal = array();
		}

		$this->internal['currentRow'] = $currentRow;
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
			$className = t3lib_div::makeInstanceClassName('tx_realty_pi1_Formatter');
			$this->formatter = new $className($currentUid, $this->conf, $this->cObj);
		}

		return $this->formatter;
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
		$row = false;

		if ($this->piVars['owner'] > 0) {
			$dbResult = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'*',
				'fe_users',
				'uid=' . $this->piVars['owner'] .
					tx_oelib_db::enableFields('fe_users')
			);
			if (!$dbResult) {
				throw new Exception(DATABASE_QUERY_ERROR);
			}
			$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbResult);
			$GLOBALS['TYPO3_DB']->sql_free_result($dbResult);
		}

		$this->cachedOwner = $row ? $row : array('uid' => 0);
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
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/pi1/class.tx_realty_pi1.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/pi1/class.tx_realty_pi1.php']);
}
?>