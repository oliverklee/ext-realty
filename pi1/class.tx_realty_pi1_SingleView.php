<?php
/***************************************************************
* Copyright notice
*
* (c) 2009 Saskia Metzler <saskia@merlin.owl.de>
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
 * Class 'tx_realty_pi1_SingleView' for the 'realty' extension.
 *
 * This class renders the single view.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 */
class tx_realty_pi1_SingleView extends tx_realty_pi1_FrontEndView {
	/**
	 * @var boolean whether the constructor is called in test mode
	 */
	private $isTestMode = false;

	/**
	 * The constructor.
	 *
	 * @param array TypoScript configuration for the plugin
	 * @param tslib_cObj the parent cObj content, needed for the flexforms
	 * @param boolean whether the class is instantiated in test mode
	 */
	public function __construct(
		array $configuration, tslib_cObj $cObj, $isTestMode = false
	) {
		parent::__construct($configuration, $cObj, $isTestMode);
		$this->isTestMode = $isTestMode;
	}

	/**
	 * Returns the single view as HTML.
	 *
	 * @param array piVars array, must contain the key "showUid" with a valid
	 *              realty object UID as value
	 *
	 * @return string HTML for the single view or an empty string if the
	 *                provided UID is no UID of a valid realty object
	 */
	public function render(array $piVars = array()) {
		if (!$this->existsRealtyObject($piVars['showUid'])) {
			return '';
		}

		$this->createSingleView($piVars['showUid']);

		return $this->getSubpart('SINGLE_VIEW');
	}

	/**
	 * Checks whether the provided UID matches a loadable realty object. It is
	 * loadable if the provided UID is the UID of an existent, non-deleted
	 * realty object that is either non-hidden, or the logged-in FE user owns
	 * the object.
	 *
	 * @param integer UID of the realty object, must be >= 0
	 *
	 * @return boolean true if the object has been loaded, false otherwise
	 */
	private function existsRealtyObject($uid) {
		if ($uid <= 0) {
			return false;
		}

		$realtyObjectMapper = tx_oelib_MapperRegistry
			::get('tx_realty_Mapper_RealtyObject');

		if (!$realtyObjectMapper->existsModel($uid, true)
			|| ($realtyObjectMapper->find($uid)->getProperty('deleted') == 1)
		) {
			return false;
		}

		$result = false;

		if (!$realtyObjectMapper->find($uid)->isHidden()) {
			$result = true;
		} else {
			if (tx_oelib_FrontEndLoginManager::getInstance()->isLoggedIn()) {
				$result = (tx_oelib_FrontEndLoginManager::getInstance()
					->getLoggedInUser('tx_realty_Mapper_FrontEndUser')->getUid()
					== $realtyObjectMapper->find($uid)->getProperty('owner')
				);
			}
		}

		return $result;
	}

	/**
	 * Creates the single view.
	 *
	 * @param integer UID of the realty object for which to create the single
	 *                view, must be > 0
	 */
	private function createSingleView($uid) {
		$this->setPageTitle($uid);

		$hasTextContent = false;
		$configuredViews = $this->getViewsToShow();

		foreach (array(
			'heading', 'address', 'description', 'price', 'overviewTable',
			'offerer', 'contactButton', 'googleMaps', 'actionButtons',
			'furtherDescription', 'imageThumbnails',
		) as $key) {
			$viewContent = in_array($key, $configuredViews)
				? $this->getView($uid, $key)
				: '';

			$this->setSubpart($key, $viewContent, 'field_wrapper');
			if (($viewContent != '') && ($key != 'imageThumbnails')) {
				$hasTextContent = true;
			}
		}

		// Sets an additional class name if the "image thumbnails" view
		// is activated.
		$this->setMarker(
			'with_images',
			in_array('imageThumbnails', $configuredViews) ? ' with-images' : ''
		);

		if (!$hasTextContent) {
			$this->hideSubparts('field_wrapper_texts');
		}
	}

	/**
	 * Sets the title of the page for display and for use in indexed search
	 * results.
	 *
	 * @param integer UID of the realty object for which to set the title,
	 *                must be > 0
	 */
	private function setPageTitle($uid) {
		$title = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->find($uid)->getProperty('title');
		if ($title == '') {
			return;
		}

		$GLOBALS['TSFE']->page['title'] = $title;
		$GLOBALS['TSFE']->indexedDocTitle = $title;
	}

	/**
	 * Returns the result of tx_realty_pi1_[$viewName]View::render().
	 *
	 * @param integer UID of the realty object for which to create the view,
	 *                must be > 0
	 * @param string key of the view to get, must be a part of the class name of
	 *               possible view: tx_realty_pi1_[$viewName]View, must be
	 *               case-sensitive apart from the first letter, must not be
	 *               empty
	 *
	 * @return string the result of tx_realty_pi1_[$viewName]View::render(),
	 *                will be empty if there is no data to display for the
	 *                requested view
	 */
	private function getView($uid, $viewName) {
		$viewClassName = t3lib_div::makeInstanceClassName(
			'tx_realty_pi1_' . ucfirst($viewName) . 'View'
		);
		$view = new $viewClassName($this->conf, $this->cObj, $this->isTestMode);

		if ($viewName == 'googleMaps') {
			$view->setMapMarker($uid);
		}

		$result = $view->render(array('showUid' => $uid));
		$view->__destruct();

		return $result;
	}

	/**
	 * Returns the configuration for which views to show in an array.
	 *
	 * @return array the single view parts to display, will not be empty
	 */
	private function getViewsToShow() {
		$configuredViews = t3lib_div::trimExplode(
			',', $this->getConfValueString('singleViewPartsToDisplay'), true
		);

		if ($this->getConfValueBoolean('showGoogleMaps', 's_googlemaps')) {
			$configuredViews[] = 'googleMaps';
		}

		return $configuredViews;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/pi1/class.tx_realty_pi1_SingleView.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/pi1/class.tx_realty_pi1_SingleView.php']);
}
?>