<?php
/***************************************************************
* Copyright notice
*
* (c) 2009-2010 Bernd Schönbach <bernd@oliverklee.de>
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
abstract class tx_realty_pi1_AbstractListView extends tx_realty_pi1_FrontEndView {
	/**
	 * @var string same as class name
	 */
	public $prefixId = 'tx_realty_pi1';

	/**
	 * @var string path to this script relative to the extension directory
	 */
	public $scriptRelPath = 'pi1/class.tx_realty_pi1_AbstractListView.php';

	/**
	 * @var boolean whether this class is called in the test mode
	 */
	private $isTestMode = false;

	/**
	 * @var tx_realty_pi1_Formatter formatter for prices, areas etc.
	 */
	private $formatter = null;

	/**
	 * @var string the list view type to display
	 */
	protected $currentView = '';

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
	 * @var string the locallang key to the label of a list view
	 */
	protected $listViewLabel = '';

	/**
	 * @var integer character length for cropped titles
	 */
	const CROP_SIZE = 74;

	/**
	 * @var boolean whether Google Maps should be shown in this view
	 */
	protected $isGoogleMapsAllowed = TRUE;

	/**
	 * @var integer the start of the limit expression for the list query
	 */
	private $startingRecordNumber = 0;

	/**
	 * @var string the table statement for the SQL query to retrieve the
	 *             list entries
	 */
	const TABLES = 'tx_realty_objects INNER JOIN tx_realty_cities ON tx_realty_objects.city = tx_realty_cities.uid';

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
		$this->checkMemberVariables();
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
	 * Checks the member variables which need to be set.
	 *
	 * Checks the member variables $listViewLabel and $currentView for
	 * non-emptiness, and $isGoogleMapsAllowed if it is set.
	 *
	 * @throws Exception one of the three checked variables has illegal values
	 */
	private function checkMemberVariables() {
		if ($this->listViewLabel == '') {
			throw new Exception(
				'The member variable $listViewLabel must not be empty.'
			);
		}
		if ($this->currentView == '') {
			throw new Exception(
				'The member variable $currentView must not be empty.'
			);
		}
		if (!isset($this->isGoogleMapsAllowed)) {
			throw new Exception(
				'The member variable $isGoogleMapsAllowed must be set.'
			);
		}
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
		$this->setPiVars($piVars);
		$this->addHeaderForListView();
		// Initially most subparts are hidden. Depending on the type of list
		// view, they will be set to unhidden again.
		$this->hideSubparts(
			'list_filter,back_link,new_record_link,wrapper_contact,' .
			'add_to_favorites_button,remove_from_favorites_button,' .
			'wrapper_editor_specific_content,wrapper_checkbox,favorites_url,' .
			'limit_heading, google_map'
		);

		$this->initializeView();

		$this->setMarker('list_heading', $this->translate($this->listViewLabel));
		$this->setSubpart('favorites_url', $this->getFavoritesUrl());
		$this->fillListRows();
		$this->setRedirectHeaderForSingleResult();

		return $this->getSubpart('LIST_VIEW');
	}

	/**
	 * Initializes some view-specific data.
	 */
	abstract protected function initializeView();

	/**
	 * Fills in the data for each list row.
	 */
	private function fillListRows() {
		$dbResult = $this->initListView();

		if ($this->internal['res_count'] == 0) {
			$this->setEmptyResultView();
			return;
		}

		$listItems = '';
		$rowCounter = 0;
		$listedObjectsUids = array();

		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbResult)) {
			$this->internal['currentRow'] = $row;
			$this->internal['currentRow']['recordPosition']
				= $this->startingRecordNumber + $rowCounter;
			$listItems .= $this->createListRow($rowCounter);
			$listedObjectsUids[] = $this->internal['currentRow']['uid'];
			$rowCounter++;
		}
		$GLOBALS['TYPO3_DB']->sql_free_result($dbResult);

		$this->setSubpart('list_item', $listItems);
		$this->setSubpart('pagination', $this->createPagination());
		$this->setSubpart('wrapper_sorting', $this->createSorting());
		$this->showGoogleMapsIfEnabled($listedObjectsUids);
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
		$whereClause = $this->createWhereClause();

		$dbResult = $GLOBALS['TYPO3_DB']->sql_query(
			$this->getSelectForListView($whereClause) .
				' LIMIT ' . $this->createLimitStatement($whereClause)
		);

		if (!$dbResult) {
			throw new tx_oelib_Exception_Database();
		}

		return $dbResult;
	}

	/**
	 * Creates the SQL statement to retrieve the list view entries.
	 *
	 * @param string $whereClause WHERE clause for the query, must not be empty
	 *
	 * @return string the SQL statement to retrieve the list view entries for.
	 */
	private function getSelectForListView($whereClause) {
		$sortingColumn = REALTY_TABLE_OBJECTS . '.sorting';
		tx_oelib_db::enableQueryLogging();

		return '(' .
				'SELECT ' . REALTY_TABLE_OBJECTS . '.*' .
				' FROM ' . self::TABLES .
				' WHERE ' . $whereClause . ' AND ' . $sortingColumn . '>0' .
				' ORDER BY ' . $sortingColumn .
				// ORDER BY within the SELECT call of a UNION requires a LIMIT.
				' LIMIT 10000000000000' .
			') UNION (' .
				'SELECT ' . REALTY_TABLE_OBJECTS . '.*' .
				' FROM ' . self::TABLES .
				' WHERE ' . $whereClause . ' AND ' . $sortingColumn . '<1' .
				' ORDER BY ' . $this->createOrderByStatement() .
				' LIMIT 10000000000000' .
			')';
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
	protected function getFavoritesUrl() {
		$pageId = $this->getConfValueInteger('favoritesPID');

		if (!$pageId) {
			$pageId = $GLOBALS['TSFE']->id;
		}

		return htmlspecialchars(
			$this->cObj->typoLink_URL(array('parameter' => $pageId))
		);
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
		$this->setMarker(
			'message_noResultsFound', $this->createNoResultsMessage()
		);
		$this->setSubpart(
			'list_result', $this->getSubpart('EMPTY_RESULT_VIEW')
		);
	}

	/**
	 * Creates the message for "no results found".
	 *
	 * @return string the localized message, will not be empty
	 */
	protected function createNoResultsMessage() {
		return $this->translate('message_noResultsFound_' . $this->currentView);
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

		$this->setViewSpecificListRowContents();

		return $this->getSubpart('LIST_ITEM');
	}

	/**
	 * Sets the row contents specific to this view.
	 */
	protected function setViewSpecificListRowContents() {}

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

		$whereClause .= $this->getViewSpecificWhereClauseParts();

		// The result may only contain non-deleted and non-hidden records except
		// for the my objects view.
		$whereClause .= tx_oelib_db::enableFields(
			REALTY_TABLE_OBJECTS, $this->shouldShowHiddenObjects()
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
	 * @param string $whereClause
	 *        WHERE clause of the query for which the LIMIT statement will be,
	 *        may be empty
	 *
	 * @return string LIMIT statement for initListView(), will not be empty
	 */
	private function createLimitStatement($whereClause) {
		// number of results to show in a listing
		$this->internal['results_at_a_time'] = t3lib_div::intInRange(
			$this->getListViewConfValueInteger('results_at_a_time'), 0, 1000, 3
		);

		// the maximum number of "pages" in the browse-box: "Page 1", "Page 2", etc.
		$this->internal['maxPages'] = t3lib_div::intInRange(
			$this->getListViewConfValueInteger('maxPages'), 1, 1000, 2
		);

		$this->internal['res_count'] = tx_oelib_db::count(
			self::TABLES, $whereClause
		);

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
		$this->startingRecordNumber = $lowerLimit;

		return $lowerLimit . ',' . $upperLimit;
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
	protected function getSelfUrl($keepPiVars = true, array $removeKeys = array()) {
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
		$useCache = $this->useCacheForSinglePageLink();

		if ($hasSeparateSingleViewPage) {
			$completeLink = $this->cObj->typoLink(
				$linkText,
				array('parameter' => $separateSingleViewPage)
			);
		} else {
			$additionalParameters
				= $this->getAdditionalParametersForSingleViewLink($uid);
			$completeLink = $this->cObj->typoLink(
				$linkText,
				array(
					'parameter' => $this->getConfValueInteger('singlePID'),
					'additionalParams' => t3lib_div::implodeArrayForUrl(
						$this->prefixId, $additionalParameters
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
	 * Checks whether to use caching for the link to the single view page.
	 *
	 * @return boolean true if caching should be used, false otherwise
	 */
	protected function useCacheForSinglePageLink() {
		return TRUE;
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
	 *                is FALSE and the $pageNum is the current page
	 */
	private function createPaginationLink(
		$pageNum, $linkText, $alsoShowNonLinks = TRUE
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

			$this->setMarker('url', htmlspecialchars($url));
			$result = $this->getSubpart('LINK_TO_OTHER_PAGE');
		}

		return $result;
	}

	/**
	 * Returns the WHERE clause part for the list of allowed PIDs within the
	 * realty objects table.
	 *
	 * @return string WHERE clause part starting with ' AND', containing a
	 *                comma-separated PID list, will be empty if no list
	 *                could be fetched
	 */
	protected function getWhereClausePartForPidList() {
		$pidList = tx_oelib_db::createRecursivePageList(
			$this->getConfValueString('pages'),
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
	 * Checks whether a search selection exists.
	 *
	 * @return boolean true if a search selection is provided in the
	 *                 current piVars, false otherwise
	 */
	protected function searchSelectionExists() {
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
	 * Gets the WHERE clause part specific to this view.
	 *
	 * @return string the WHERE clause parts to add, will be empty if no view
	 *                specific WHERE clause parts are needed
	 */
	protected function getViewSpecificWhereClauseParts() {
		return '';
	}

	/**
	 * Determines whether hidden results should be shown.
	 *
	 * This will be used for tx_oelib_db::enableFields.
	 *
	 * @return integer 1 if hidden records should be shown, -1 otherwise
	 */
	protected function shouldShowHiddenObjects() {
		return -1;
	}

	/**
	 * Sets the google maps marker content if it is enabled.
	 *
	 * @param array $shownObjectsUids
	 *        the UIDs of the objects to show on the map, may be empty
	 */
	private function showGoogleMapsIfEnabled(array $shownObjectsUids) {
		if (!$this->isGoogleMapsAllowed || !$this->getConfValueBoolean(
			'showGoogleMaps', 's_googlemaps'
		)) {
			return;
		}

		$googleMapsView = tx_oelib_ObjectFactory::make(
			'tx_realty_pi1_GoogleMapsView', $this->conf, $this->cObj,
			$this->isTestMode
		);
		foreach ($shownObjectsUids as $objectUid) {
			$googleMapsView->setMapMarker($objectUid, true);
		}
		$this->unhideSubparts('google_map');
		$this->setSubpart('google_map', $googleMapsView->render());
		$googleMapsView->__destruct();
	}

	/**
	 * Gets the additional parameters to add to the link to the single view page.
	 *
	 * @param integer $uid
	 *        the UID of the object to create the link for, must be > 0
	 *
	 * @return array additional parameters to the single view page for usage
	 *               with t3lib_div::implodeArrayForUrl, will not be empty
	 */
	private function getAdditionalParametersForSingleViewLink($uid) {
		$result = array('showUid' => $uid);
		if (!$this->getConfValueBoolean('enableNextPreviousButtons')) {
			return $result;
		}

		$filterFormPiVars = tx_realty_filterForm::getPiVarKeys();
		$parametersToSerialize = array();

		foreach ($filterFormPiVars as $key) {
			if (isset($this->piVars[$key])) {
				$parametersToSerialize[$key] = $this->piVars[$key];
			}
		}

		if (isset($this->piVars['search'])) {
			$parametersToSerialize['search'] = $this->piVars['search'];

		}
		if (isset($this->piVars['orderBy'])) {
			$parametersToSerialize['orderBy'] = $this->piVars['orderBy'];
			$parametersToSerialize['descFlag'] = $this->piVars['descFlag'];
		}

		$result['listViewLimitation'] = base64_encode
			(serialize($parametersToSerialize)
		);

		$result['listUid'] = $this->cObj->data['uid'];
		$result['listViewType'] = $this->currentView;
		$result['recordPosition']
			= $this->internal['currentRow']['recordPosition'];

		return $result;
	}

	/**
	 * Retrieves the UID for the record a the provided position.
	 *
	 * The record position is zero based, so 0 is the first postion.
	 *
	 * @param integer $recordPosition
	 *        the position of the searched record, must be >= 0
	 *
	 * @return integer the record UID, will be zero if no record for the given
	 *                 record number could be found
	 */
	public function getUidForRecordNumber($recordPosition) {
		if ($recordPosition < 0) {
			throw new Exception(
				'The record position must be a non-negative integer.'
			);
		}

		$dbResult = $GLOBALS['TYPO3_DB']->sql_query(
			$this->getSelectForListView($this->createWhereClause()) .
				' LIMIT ' . $recordPosition . ',1'
		);

		if (!$dbResult) {
			throw new tx_oelib_Exception_Database();
		}

		$result = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbResult);

		return (is_array($result)) ? $result['uid'] : 0;
	}

	/**
	 * Sets the piVars.
	 *
	 * @param array $piVars the piVar array to store, may be empty
	 */
	public function setPiVars(array $piVars) {
		$this->piVars = $piVars;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/pi1/class.tx_realty_pi1_AbstractListView.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/pi1/class.tx_realty_pi1_AbstractListView.php']);
}
?>