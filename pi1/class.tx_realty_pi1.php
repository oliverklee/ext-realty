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

			if (strstr($this->cObj->currentRecord, 'tt_content')) {
				$this->conf['pidList'] = $this->getConfValueString('pages');
				$this->conf['recursive'] = $this->getConfValueInteger('recursive');
			}

			$this->internal['currentTable'] = $this->tableNames['objects'];
			$this->ensureIntegerPiVars(array(
				'image', 'remove', 'showUid', 'delete', 'owner', 'uid'
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
		switch ($this->getCurrentView()) {
			case 'gallery':
				$result = $this->createGallery();
				break;
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
				$favoritesList = tx_oelib_ObjectFactory::make(
					'tx_realty_pi1_FavoritesListView', $this->conf, $this->cObj
				);
				$result = $favoritesList->render($this->piVars);
				$favoritesList->__destruct();
				break;
			case 'my_objects':
				$myObjectsList = tx_oelib_ObjectFactory::make(
					'tx_realty_pi1_MyObjectsListView', $this->conf, $this->cObj
				);
				$result = $myObjectsList->render($this->piVars);
				$myObjectsList->__destruct();
				break;
			default:
				// All other return values of getCurrentView stand for list views.
				$listView = tx_oelib_ObjectFactory::make(
					'tx_realty_pi1_ListView', $this->conf, $this->cObj
				);

				$listView->setCurrentView($this->getCurrentView());
				$result = $listView->render($this->piVars);
				$listView->__destruct();
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

		tx_realty_lightboxIncluder::includeMainJavaScript();

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
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/pi1/class.tx_realty_pi1.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/pi1/class.tx_realty_pi1.php']);
}
?>