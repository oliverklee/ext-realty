<?php
/***************************************************************
* Copyright notice
*
* (c) 2008-2010 Saskia Metzler <saskia@merlin.owl.de>
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
require_once(t3lib_extMgm::extPath('realty') . 'lib/class.tx_realty_cacheManager.php');
require_once(t3lib_extMgm::extPath('realty') . 'lib/class.tx_realty_lightboxIncluder.php');
require_once(t3lib_extMgm::extPath('realty') . 'pi1/class.tx_realty_frontEndForm.php');

/**
 * Class 'tx_realty_frontEndImageUpload' for the 'realty' extension. This class
 * assumes the image upload for the FE editor in the realty plugin.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 */
class tx_realty_frontEndImageUpload extends tx_realty_frontEndForm {
	/**
	 * @var string stores the type of validation error if there was one
	 */
	private $validationError = '';


	////////////////////////////////
	// Functions used by the form.
	////////////////////////////////

	/**
	 * Returns the FE editor in HTML if a user is logged in and authorized, and
	 * if the object to edit actually exists in the database. Otherwise the
	 * result will be an error view.
	 *
	 * If there are no uploaded images for an object, the delete option will
	 * be hidden.
	 *
	 * @param array unused
	 *
	 * @return string HTML for the FE editor or an error view if the
	 *                  requested object is not editable for the current user
	 */
	public function render(array $unused = array()) {
		$result = parent::render($unused);
		tx_realty_lightboxIncluder::includeLightboxFiles(
			$this->prefixId, $this->extKey
		);
		tx_realty_lightboxIncluder::includeMainJavaScript();
		$this->processTemplate($result);
		$this->setLabels();

		$allImageData = $this->realtyObject->getAllImageData();
		if (!empty($allImageData)) {
			$this->setSubpart(
				'single_attached_image',
				$this->getRenderedImageList($allImageData)
			);
		} else {
			$this->hideSubparts('images_to_delete', 'wrapper');
		}

		return $this->getSubpart();
	}

	/**
	 * Gets the URL of the page that should be displayed when an image has been
	 * uploaded.
	 * An URL of the image upload page is returned if "submit_and_stay" was
	 * clicked.
	 *
	 * @return string complete URL of the FE page where to redirect to or of the
	 *                current page, if "submit_and_stay" was clicked
	 */
	public function getRedirectUrl() {
		return $this->getFormValue('proceed_image_upload')
			? $this->getUrlOfCurrentPage()
			: parent::getRedirectUrl();
	}

	/**
	 * Inserts the image record into the database if one has been provided in
	 * $formData.
	 * Deletes image records of the current record if images were checked to be
	 * deleted in the form .
	 *
	 * @param array form data, must not be empty
	 */
	public function processImageUpload(array $formData) {
		if (($formData['caption'] != '') && ($formData['image']['name'] != '')) {
			$this->realtyObject->addImageRecord(
				strip_tags($formData['caption']),
				$this->getFormidablesUniqueFileName($formData['image']['name'])
			);
		}

		$idsOfImagesToDelete = t3lib_div::trimExplode(
			',', $formData['imagesToDelete'], TRUE
		);
		foreach ($idsOfImagesToDelete as $imageId) {
			try {
				// The ID-prefix is "attached_image_" which are 15 charachters.
				$this->realtyObject->markImageRecordAsDeleted(
					substr($imageId, 15)
				);
			} catch (Exception $noSuchImageRecord) {
			}
		}

		// The original PID is provided to ensure the default settings for the
		// PID are not used because this might change the record's location.
		$this->realtyObject->writeToDatabase(
			$this->realtyObject->getProperty('pid')
		);
		tx_realty_cacheManager::clearFrontEndCacheForRealtyPages();
	}

	/**
	 * Checks whether the provided file is valid.
	 *
	 * @param array form data to check, must not be empty
	 *
	 * @return boolean whether the provided file is a valid image
	 */
	public function checkFile(array $valueToCheck) {
		// nothing to check if there is no file
		if ($valueToCheck['value']['name'] == '') {
			return TRUE;
		}

		$validationErrorLabel = '';
		$maximumFileSizeInBytes
			= $GLOBALS['TYPO3_CONF_VARS']['BE']['maxFileSize'] * 1024;
		$validExtensions = '/^.+\.(' .
			str_replace(
				',', '|', $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext']
			) . ')$/i';

		if ($this->getFormValue('caption') == '') {
			$validationErrorLabel = 'message_empty_caption';
		} elseif ($valueToCheck['value']['size'] > $maximumFileSizeInBytes) {
			$validationErrorLabel = 'message_image_too_large';
		} elseif (!preg_match($validExtensions, $valueToCheck['value']['name'])) {
			$validationErrorLabel = 'message_invalid_type';
		}

		$this->validationError = ($validationErrorLabel != '')
			? $this->translate($validationErrorLabel)
			: '';

		return ($validationErrorLabel == '');
	}

	/**
	 * Returns an error message if the provided file was invalid. The result
	 * will be empty if no error message was set before.
	 *
	 * @return string localized validation error message, will be empty
	 *                if no error message was set
	 *
	 * @see checkFile()
	 */
	public function getImageUploadErrorMessage() {
		return $this->validationError;
	}


	////////////////////////////////////
	// Miscellaneous helper functions.
	////////////////////////////////////

	/**
	 * Returns the file name of an image to upload that will be used to store
	 * this image in the upload directory.
	 * This function can only return the correct result if it is called after
	 * FORMidable has created the file name for writing the image to the upload
	 * directory.
	 *
	 * Note: In the test mode, just the input string will be returned.
	 *
	 * @param string file name derived from the form data, must not be empty
	 *
	 * @return string unique file name used under wich this file is stored
	 *                in the upload directory, will not be empty
	 */
	private function getFormidablesUniqueFileName($fileName) {
		$this->makeFormCreator();

		return ($this->isTestMode)
			? $fileName
			: ($this->formCreator->aORenderlets['image']->sCoolFileName);
	}

	/**
	 * Returns the URL to the current page.
	 *
	 * @return string URL of the current page, will not be empty
	 */
	private function getUrlOfCurrentPage() {
		return t3lib_div::locationHeaderUrl($this->cObj->typoLink_URL(array(
			'parameter' => $GLOBALS['TSFE']->id,
			'additionalParams' => t3lib_div::implodeArrayForUrl('', array(
				$this->prefixId => array('showUid' => $this->realtyObjectUid)
			)),
		)));
	}

	/**
	 * Returns HTML for the images as list items with their thumbnails.
	 *
	 * @param array two-dimensional array of image records, each inner array
	 *              represents one image record and is an associative array with
	 *              the keys 'caption' and 'image', must not be empty
	 *
	 * @return string listed images with thumbnails in HTML, will not be empty
	 */
	private function getRenderedImageList(array $imageData) {
		$result = '';
		foreach ($imageData as $key => $imageRecord) {
			$imagePath = REALTY_UPLOAD_FOLDER . $imageRecord['image'];
			$imageUrl = htmlspecialchars(t3lib_div::locationHeaderUrl(
					$this->cObj->typoLink_URL(array('parameter' => $imagePath))
			));
			$imageTag = $this->createRestrictedImage(
				$imagePath,
				'',
				$this->getConfValueInteger('imageUploadThumbnailWidth'),
				$this->getConfValueInteger('imageUploadThumbnailHeight'),
				0,
				$imageRecord['caption']
			);

			$this->setMarker(
				'single_image_item',
				'<a href="' . $imageUrl . '" rel="lightbox[objectGallery]" ' .
					'title="' . htmlspecialchars($imageRecord['caption']) . '"' .
					'>' . $imageTag . '</a>'
			);
			$this->setMarker(
				'image_title', htmlspecialchars($imageRecord['caption'])
			);
			$this->setMarker(
				'image_title_for_js',
				htmlspecialchars(addslashes($imageRecord['caption']))
			);
			$this->setMarker(
				'single_attached_image_id', 'attached_image_' . $key
			);
			$result .= $this->getSubpart('SINGLE_ATTACHED_IMAGE');
		}

		return $result;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/pi1/class.tx_realty_frontEndImageUpload.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/pi1/class.tx_realty_frontEndImageUpload.php']);
}
?>