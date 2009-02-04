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

require_once(t3lib_extMgm::extPath('realty') . 'lib/tx_realty_constants.php');
require_once(t3lib_extMgm::extPath('realty') . 'lib/class.tx_realty_lightboxIncluder.php');
require_once(t3lib_extMgm::extPath('realty') . 'pi1/class.tx_realty_offererList.php');

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
	 * @var tx_realty_pi1_Formatter formatter for prices, areas etc.
	 */
	private $formatter = null;

	/**
	 * @var integer UID of the realty object to show
	 */
	private $showUid = 0;

	/**
	 * @var array field names in the realty objects table
	 */
	private $allowedFieldNames = array();

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

		$this->showUid = $piVars['showUid'];
		if ($this->isTestMode) {
			tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
				->find($piVars['showUid'])->setTestMode();
		}
		$this->createSingleView();

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

		if (!tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->existsModel($uid, true)
		) {
			return false;
		}

		$result = false;

		if (!tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->find($uid)->isHidden()
		) {
			$result = true;
		} else {
			$loggedInUser = tx_oelib_MapperRegistry
				::get('tx_realty_Mapper_FrontEndUser')->getLoggedInUser();

			if ($loggedInUser) {
				$result = ($loggedInUser->getUid() == tx_oelib_MapperRegistry
					::get('tx_realty_Mapper_RealtyObject')
					->find($uid)->getProperty('owner')
				);
			}
		}

		return $result;
	}

	/**
	 * Creates a single view.
	 */
	private function createSingleView() {
		$this->includeLightboxFiles();
		$this->includeGoogleMap();
		$this->setPageTitle();

		foreach (array(
			'title', 'uid', 'object_number', 'description', 'location',
			'equipment', 'misc', 'address'
		) as $key) {
			$this->setOrDeleteMarkerIfNotEmpty(
				$key, $this->getFormatter()->getProperty($key), '', 'field_wrapper'
			);
		}

		$this->fillOrHideOffererWrapper();
		$this->fillOrHideContactWrapper();

		$this->createOverviewTable();
		$this->setMarker('favorites_url', $this->getFavoritesUrl());
		$this->setSubpart('images_list', $this->createImages());
	}

	/**
	 * Includes Lightbox files if configured.
	 */
	private function includeLightboxFiles() {
		if ($this->getConfValueString('galleryType') != 'lightbox') {
			return;
		}

		tx_realty_lightboxIncluder::includeLightboxFiles(
			$this->prefixId, $this->extKey
		);
	}

	/**
	 * Includes a Google Map if configured.
	 */
	private function includeGoogleMap() {
		$googleMapsClassName = t3lib_div::makeInstanceClassName(
			'tx_realty_pi1_GoogleMapsView'
		);
		$googleMapsView = new $googleMapsClassName(
			$this->conf, $this->cObj, $this->isTestMode
		);
		$googleMapsView->setMapMarker($this->getUid());
		$this->setSubpart('google_map', $googleMapsView->render());
		$googleMapsView->__destruct();
	}

	/**
	 * Sets the title of the page for display and for use in indexed search
	 * results.
	 */
	private function setPageTitle() {
		$title = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->find($this->getUid())->getProperty('title');
		if ($title == '') {
			return;
		}

		$GLOBALS['TSFE']->page['title'] = $title;
		$GLOBALS['TSFE']->indexedDocTitle = $title;
	}

	/**
	 * Fills the field wrapper "offerer" if displaying contact information is
	 * enabled and if there is data for this wrapper. Otherwise the complete
	 * wrapper is hidden.
	 */
	private function fillOrHideOffererWrapper() {
		$contactData = $this->fetchContactDataFromSource();

		if ($contactData != '') {
			$this->setMarker('OFFERER_INFORMATION', $contactData);
		} else {
			$this->hideSubparts('offerer', 'field_wrapper');
		}
	}

	/**
	 * Fetches the contact data from the source defined in the realty record and
	 * returns it in an array.
	 *
	 * @return string HTML with the contact data, will be empty if none was
	 *                found
	 */
	private function fetchContactDataFromSource() {
		$offererListClassName = t3lib_div::makeInstanceClassName(
			'tx_realty_offererList'
		);
		$offererList = new $offererListClassName($this->conf, $this->cObj);
		$realtyObject = tx_oelib_MapperRegistry
			::get('tx_realty_Mapper_RealtyObject')->find($this->getUid());

		switch ($realtyObject->getProperty('contact_data_source')) {
			case REALTY_CONTACT_FROM_OWNER_ACCOUNT:
				$result = $offererList->renderOneItem(
					$realtyObject->getProperty('owner')
				);
				break;
			case REALTY_CONTACT_FROM_REALTY_OBJECT:
				$result = $offererList->renderOneItemWithTheDataProvided(array(
					'email' => $realtyObject->getProperty('contact_email'),
					'company' => $realtyObject->getProperty('employer'),
					'telephone' => $realtyObject->getProperty('contact_phone'),
					'name' => $realtyObject->getProperty('contact_person'),
				));
				break;
			default:
				$result = '';
				break;
		}
		$offererList->__destruct();

		return $result;
	}

	/**
	 * Fills the wrapper with the link to the contact form if displaying contact
	 * information is enabled for the single view. Otherwise hides the complete
	 * wrapper.
	 */
	private function fillOrHideContactWrapper() {
		if (!$this->hasConfValueInteger('contactPID')) {
			$this->hideSubparts('contact', 'wrapper');
			return;
		}

		if ($this->getConfValueBoolean('showContactPageLink')
			&& ($this->getConfValueInteger('contactPID') != $GLOBALS['TSFE']->id)
		) {
			$contactUrl = htmlspecialchars($this->cObj->typoLink_URL(array(
				'parameter' => $this->getConfValueInteger('contactPID'),
				'additionalParams' => t3lib_div::implodeArrayForUrl(
					'',
					array($this->prefixId => array('showUid' => $this->getUid()))
				),
			)));
			$this->setMarker('contact_url', $contactUrl);
		} else {
			$this->hideSubparts('contact', 'wrapper');
		}
	}

	/**
	 * Creates the URL to the favorites page using the PID configured in
	 * "favoritesPID".
	 *
	 * @return string htmlspecialchared URL of the page set in "favoritesPID",
	 *                will not be empty
	 */
	private function getFavoritesUrl() {
		return htmlspecialchars(
			$this->cObj->typoLink_URL(
				array('parameter' => $this->getConfValueInteger('favoritesPID'))
			)
		);
	}

	/**
	 * Fills the subpart "OVERVIEW_TABLE" with the contents of the current
	 * record's database fields specified via the TS setup variable
	 * "fieldsInSingleViewTable".
	 *
	 * @return boolean true if at least one row has been filled, false otherwise
	 */
	private function createOverviewTable() {
		$result = false;

		$rows = array();
		$rowCounter = 0;
		$fieldNames = t3lib_div::trimExplode(
			',', $this->getConfValueString('fieldsInSingleViewTable')
		);

		foreach ($fieldNames as $fieldName) {
			if ($this->isAllowedFieldName($fieldName)) {
				if ($this->setMarkerIfNotEmpty(
					'data_current_row',
					$this->getFormatter()->getProperty($fieldName)
				)) {
					$position = ($rowCounter % 2) ? 'odd' : 'even';
					$this->setMarker('class_position_in_list', $position);
					$this->setMarker(
						'label_current_row',
						$this->translate('label_' . $fieldName)
					);
					$rows[] = $this->getSubpart('OVERVIEW_ROW');
					$rowCounter++;
					$result = true;
				}
			}
		}

		$this->setSubpart('overview_table', implode(LF, $rows));

		return $result;
	}

	/**
	 * Creates all images that are attached to the current record.
	 *
	 * @return string HTML for the images, will be empty if there are no images
	 */
	private function createImages() {
		$result = '';
		$counter = 0;

		$currentImage = $this->getLinkedImage();

		while ($currentImage != '') {
			$counter++;
			$this->setMarker('one_image_tag', $currentImage);
			$result .= $this->getSubpart('ONE_IMAGE_CONTAINER');
			$currentImage = $this->getLinkedImage($counter);
		}

		return $result;
	}

	/**
	 * Gets an image from the current record's image list as a complete IMG tag
	 * with alt text and title text, wrapped in a link pointing to the gallery
	 * and seized according do the configuration in "singleImageMaxX" and
	 * "singleImageMaxY".
	 *
	 * If "galleryPopupParameters" is set in the TS setup, the link will have
	 * an additional onclick handler to open the gallery in a pop-up window.
	 *
	 * If the gallery type "lightbox" is set in TS setup, the lightbox "rel"
	 * attribute will be added to the a tag and the URL will link to the
	 * full-size picture.
	 *
	 * If no image is found, an empty string is returned.
	 *
	 * @param integer the number of the image to retrieve, must be >= 0
	 *
	 * @return string image tag wrapped in a link, will be empty if there is no
	 *                image with the provided number
	 */
	private function getLinkedImage($imageNumber = 0) {
		$isLightboxGallery
			= ($this->getConfValueString('galleryType') == 'lightbox');

		if (!$this->hasConfValueInteger('galleryPID') && !$isLightboxGallery) {
			return '';
		}

		$imageRecord = $this->getImage($imageNumber);

		if (empty($imageRecord)) {
			return '';
		}

		$galleryUrl = $this->createGalleryUrl(
			$isLightboxGallery
				? REALTY_UPLOAD_FOLDER . $imageRecord['image']
				: $this->getConfValueInteger('galleryPID'),
			$imageNumber
		);

		$linkAttribute = $isLightboxGallery
			? ' rel="lightbox[objectGallery]" title="' .
				$imageRecord['caption'] . '"'
			: $this->getGalleryPopUpParameters($galleryUrl);

		$imageTag = $this->createRestrictedImage(
			REALTY_UPLOAD_FOLDER . $imageRecord['image'],
			$imageRecord['caption'],
			$this->getConfValueInteger('singleImageMaxX'),
			$this->getConfValueInteger('singleImageMaxY'),
			0,
			$imageRecord['caption']
		);

		return '<a href="' . $galleryUrl . '"' . $linkAttribute . '>' .
			$imageTag . '</a>';
	}

	/**
	 * Returns an image record of the realty object.
	 *
	 * @param integer the number of the image to retrieve, must be >= 0
	 *
	 * @return array the image's caption and file name in an associative
	 *               array, will be empty if the image with the requested number
	 *               does not exist
	 */
	private function getImage($imageNumber = 0) {
		$images = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->find($this->getUid())->getAllImageData();

		return (isset($images[$imageNumber]) ? $images[$imageNumber] : array());
	}

	/**
	 * Creates the URL of a gallery image.
	 *
	 * @param string the destination of the image link, must not be empty.
	 * @param integer the number of the image to retrieve, must be >= 0
	 *
	 * @return string the URL to the current gallery image, will not be empty
	 */
	private function createGalleryUrl($linkDestination, $imageNumber = 0) {
		return htmlspecialchars(
			t3lib_div::locationHeaderUrl($this->cObj->typoLink_URL(array(
				'parameter' => $linkDestination,
				'additionalParams' => t3lib_div::implodeArrayForUrl(
					$this->prefixId,
					array('showUid' => $this->getUid(), 'image' => $imageNumber)
				),
				'useCacheHash' => true,
			)))
		);
	}

	/**
	 * Returns the gallery pop-up parameters as an onclick attribute.
	 *
	 * @param string URL to the gallery, must not be empty
	 *
	 * @return string gallery pop-up parameters as an onclick attribute
	 *                beginning with " onclick=", will be empty if these
	 *                parameters are not configured
	 */
	private function getGalleryPopUpParameters($galleryUrl) {
		if (!$this->hasConfValueString('galleryPopupParameters')) {
			return '';
		}

		return ' onclick="window.open(' .
			'\'' . $galleryUrl . '\', ' .
			'\'' . $this->getConfValueString('galleryPopupWindowName') . '\', ' .
			'\'' . $this->getConfValueString('galleryPopupParameters') . '\'' .
			'); ' . 'return false;"';
	}

	/**
	 * Checks whether a $key is a field name of the realty objects table.
	 *
	 * @param string key to check whether it is a field name, must not be empty
	 *
	 * @return boolean true if $key is a field name of the realty objects table,
	 *                 false otherwise
	 */
	private function isAllowedFieldName($key) {
		if (empty($this->allowedFieldNames)) {
			$this->allowedFieldNames = array_keys(
				$GLOBALS['TYPO3_DB']->admin_get_fields(REALTY_TABLE_OBJECTS)
			);
		}

		return in_array($key, $this->allowedFieldNames);
	}

	/**
	 * Returns the current "showUid".
	 *
	 * @return UID of the realty record to show
	 */
	private function getUid() {
		return $this->showUid;
	}

	/**
	 * Returns a formatter instance for the current realty object.
	 *
	 * @return tx_realty_pi1_Formatter a formatter for the current realty object
	 */
	private function getFormatter() {
		if ($this->formatter
			&& ($this->formatter->getProperty('uid') != $this->getUid())
		) {
			$this->formatter->__destruct();
			unset($this->formatter);
		}

		if (!$this->formatter) {
			$className = t3lib_div::makeInstanceClassName('tx_realty_pi1_Formatter');
			$this->formatter = new $className(
				$this->getUid(), $this->conf, $this->cObj
			);
		}

		return $this->formatter;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/pi1/class.tx_realty_pi1_SingleView.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/pi1/class.tx_realty_pi1_SingleView.php']);
}
?>