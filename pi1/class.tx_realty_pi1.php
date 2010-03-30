<?php
/***************************************************************
* Copyright notice
*
* (c) 2006-2010 Oliver Klee <typo3-coding@oliverklee.de>
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
require_once(t3lib_extMgm::extPath('realty') . 'lib/class.tx_realty_lightboxIncluder.php');
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
 * @author Bernd Schönbach <bernd@oliverklee.de>
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
	 * @var boolean whether to check cHash
	 */
	public $pi_checkCHash = true;

	/**
	 * @var boolean whether this class is called in the test mode
	 */
	private $isTestMode = false;

	/**
	 * The constructor.
	 *
	 * @param boolean whether this class is called in the test mode
	 */
	public function __construct($isTestMode = false) {
		$this->isTestMode = $isTestMode;
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
		$result = '';

		try {
			$this->init($conf);
			$this->pi_initPIflexForm();

			$this->setLocaleConvention();
			$this->getTemplateCode();
			$this->setLabels();

			$this->internal['currentTable'] = REALTY_TABLE_OBJECTS;
			$this->ensureIntegerPiVars(array(
				'remove', 'showUid', 'delete', 'owner', 'uid'
			));

			// Checks the configuration and displays any errors.
			// The direct return value from $this->checkConfiguration() is not
			// used as this would ignore any previous error messages.
			$this->setFlavor($this->getCurrentView());
			$this->checkConfiguration();

			$errorViewHtml = $this->checkAccessAndGetHtmlOfErrorView();
			$result = $this->pi_wrapInBaseClass(
				(($errorViewHtml == '')
					? $this->getHtmlForCurrentView()
					: $errorViewHtml
				) . $this->getWrappedConfigCheckMessage()
			);
		} catch (Exception $exception) {
			$result .= '<p style="border: 2px solid red; padding: 1em; ' .
				'font-weight: bold;">' . LF .
				htmlspecialchars($exception->getMessage()) . LF .
				'<br /><br />' . LF .
				nl2br(htmlspecialchars($exception->getTraceAsString())) . LF .
				'</p>' . LF;
		}

		return $result;
	}

	/**
	 * Returns the HTML for the current view.
	 *
	 * @see Bug #2432
	 *
	 * @return string HTML for the current view, will not be empty
	 */
	private function getHtmlForCurrentView() {
		$listViewType = '';

		switch ($this->getCurrentView()) {
			case 'filter_form':
				$filterForm = tx_oelib_ObjectFactory::make(
					'tx_realty_filterForm', $this->conf, $this->cObj
				);
				$result = $filterForm->render($this->piVars);
				$filterForm->__destruct();
				break;
			case 'single_view':
				$singleView = tx_oelib_ObjectFactory::make(
					'tx_realty_pi1_SingleView', $this->conf, $this->cObj,
					$this->isTestMode
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
				$contactForm = tx_oelib_ObjectFactory::make(
					'tx_realty_contactForm', $this->conf, $this->cObj
				);
				$formData = $this->piVars;
				$favoritesList = tx_oelib_ObjectFactory::make(
					'tx_realty_pi1_FavoritesListView', $this->conf, $this->cObj
				);
				$formData['summaryStringOfFavorites']
					= $favoritesList->createSummaryStringOfFavorites();
				$favoritesList->__destruct();
				$result = $contactForm->render($formData);
				$contactForm->__destruct();
				break;
			case 'fe_editor':
				$frontEndEditor = tx_oelib_ObjectFactory::make(
					'tx_realty_frontEndEditor', $this->conf, $this->cObj,
					$this->piVars['showUid'], 'pi1/tx_realty_frontEndEditor.xml'
				);
				$result = $frontEndEditor->render();
				$frontEndEditor->__destruct();
				break;
			case 'image_upload':
				$imageUpload = tx_oelib_ObjectFactory::make(
					'tx_realty_frontEndImageUpload', $this->conf, $this->cObj,
					$this->piVars['showUid'], 'pi1/tx_realty_frontEndImageUpload.xml'
				);
				$result = $imageUpload->render();
				$imageUpload->__destruct();
				break;
			case 'offerer_list':
				$offererList = tx_oelib_ObjectFactory::make(
					'tx_realty_offererList', $this->conf, $this->cObj
				);
				$result = $offererList->render();
				$offererList->__destruct();
				break;
			case 'favorites':
				$listViewType = 'favorites';
				break;
			case 'my_objects':
				$listViewType = 'my_objects';
				break;
			case 'objects_by_owner':
				$listViewType = 'objects_by_owner';
				break;
			default:
				$listViewType = 'realty_list';
				break;
		}
		if ($listViewType != '') {
			$listView = tx_realty_pi1_ListViewFactory::make(
				$listViewType, $this->conf, $this->cObj
			);
			$result = $listView->render($this->piVars);
			$listView->__destruct();
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
			&& ($this->getCurrentView() == 'single_view')
		) {
			return '';
		}

		try {
			tx_oelib_ObjectFactory::make('tx_realty_pi1_AccessCheck')->checkAccess(
				$this->getCurrentView(), $this->piVars
			);
			$result = '';
		} catch (tx_oelib_Exception_AccessDenied $exception) {
			$errorView = tx_oelib_ObjectFactory::make(
				'tx_realty_pi1_ErrorView', $this->conf, $this->cObj
			);
			$result = $errorView->render(array($exception->getMessage()));
			$errorView->__destruct();
		}

		return $result;
	}

	/**
	 * Sets the view to an empty result message specific for the requested view.
	 */
	private function setEmptyResultView() {
		$view = $this->getCurrentView();
		$noResultsMessage = 'message_noResultsFound_' . $view;

		$this->setMarker(
			'message_noResultsFound', $this->translate($noResultsMessage)
		);
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
	 * Returns the current view.
	 *
	 * @return string Name of the current view ('realty_list',
	 *                'contact_form', 'favorites', 'fe_editor',
	 *                'filter_form', 'image_upload',
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
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/pi1/class.tx_realty_pi1.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/pi1/class.tx_realty_pi1.php']);
}
?>