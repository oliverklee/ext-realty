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
 * This class assumes the image upload for the FE editor in the realty plugin.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 * @author Oliver Klee <typo3-coding@oliverklee.de>
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
	 * @param array $unused unused
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

		$images = $this->realtyObject->getImages();

		if (!$images->isEmpty()) {
			$this->setSubpart(
				'single_attached_image',
				$this->getRenderedImageList($images)
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
	 * @param array $formData form data, must not be empty
	 *
	 * @return void
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
					(int)substr($imageId, 15)
				);
			} catch (Exception $exception) {
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
	 * @param array $valueToCheck  data to check, must not be empty
	 *
	 * @return bool whether the provided file is a valid image
	 */
	public function checkFile(array $valueToCheck) {
		// nothing to check if there is no file
		if ($valueToCheck['value']['name'] == '') {
			return TRUE;
		}

		$validationErrorLabel = '';
		$maximumFileSizeInBytes
			= $GLOBALS['TYPO3_CONF_VARS']['BE']['maxFileSize'] * 1024;
		$imageExtensions = t3lib_div::trimExplode(
			',', $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'], TRUE
		);
		if (in_array('pdf', $imageExtensions)) {
			unset($imageExtensions[array_search('pdf', $imageExtensions)]);
		}
		if (in_array('ps', $imageExtensions)) {
			unset($imageExtensions[array_search('ps', $imageExtensions)]);
		}
		$extensionValidator = '/^.+\.(' . implode('|', $imageExtensions) . ')$/i';

		if ($this->getFormValue('caption') == '') {
			$validationErrorLabel = 'message_empty_caption';
		} elseif ($valueToCheck['value']['size'] > $maximumFileSizeInBytes) {
			$validationErrorLabel = 'message_image_too_large';
		} elseif (!preg_match($extensionValidator, $valueToCheck['value']['name'])) {
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
	 * @param string $fileName file name derived from the form data, must not be empty
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
			'useCacheHash' => TRUE,
		)));
	}

	/**
	 * Returns HTML for the images as list items with their thumbnails.
	 *
	 * @param tx_oelib_List<tx_realty_Model_Image> $images
	 *        the images to render, may be empty
	 *
	 * @return string listed images with thumbnails in HTML, will not be empty
	 */
	private function getRenderedImageList(tx_oelib_List $images) {
		$result = '';

		$index = 0;
		/** @var tx_realty_Model_Image $image */
		foreach ($images as $image) {
			$imagePath = tx_realty_Model_Image::UPLOAD_FOLDER . $image->getFileName();
			$imageUrl = htmlspecialchars(t3lib_div::locationHeaderUrl(
					$this->cObj->typoLink_URL(array('parameter' => $imagePath, 'useCacheHash' => TRUE))
			));
			$title = $image->getTitle();
			$imageTag = $this->createRestrictedImage(
				$imagePath,
				'',
				$this->getConfValueInteger('imageUploadThumbnailWidth'),
				$this->getConfValueInteger('imageUploadThumbnailHeight'),
				0,
				$title
			);

			$this->setMarker(
				'single_image_item',
				'<a href="' . $imageUrl . '" rel="lightbox[objectGallery]" ' .
					'title="' . htmlspecialchars($title) . '"' .
					'>' . $imageTag . '</a>'
			);
			$this->setMarker(
				'image_title', htmlspecialchars($title)
			);
			$this->setMarker(
				'image_title_for_js',
				htmlspecialchars(addslashes($title))
			);
			$this->setMarker(
				'single_attached_image_id', 'attached_image_' . $index
			);
			$result .= $this->getSubpart('SINGLE_ATTACHED_IMAGE');

			$index++;
		}

		return $result;
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/realty/pi1/class.tx_realty_frontEndImageUpload.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/realty/pi1/class.tx_realty_frontEndImageUpload.php']);
}