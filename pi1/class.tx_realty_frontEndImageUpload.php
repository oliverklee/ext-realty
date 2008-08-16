<?php
/***************************************************************
* Copyright notice
*
* (c) 2008 Saskia Metzler <saskia@merlin.owl.de> All rights reserved
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
 * Class 'tx_realty_frontEndImageUpload' for the 'realty' extension. This class
 * assumes the image upload for the FE editor in the realty plugin.
 *
 * @package		TYPO3
 * @subpackage	tx_realty
 *
 * @author		Saskia Metzler <saskia@merlin.owl.de>
 */

require_once(t3lib_extMgm::extPath('realty').'lib/tx_realty_constants.php');
require_once(t3lib_extMgm::extPath('realty').'lib/class.tx_realty_object.php');
require_once(t3lib_extMgm::extPath('realty').'lib/class.tx_realty_cacheManager.php');
require_once(t3lib_extMgm::extPath('realty').'pi1/class.tx_realty_frontEndForm.php');

class tx_realty_frontEndImageUpload extends tx_realty_frontEndForm{
	/** stores the type of validation error if there was one */
	private $validationError = '';


	////////////////////////////////
	// Functions used by the form.
	////////////////////////////////

	/**
	 * Inserts the image record into the database if one has been provided in
	 * $formData.
	 * Deletes image records of the current record if images were checked to be
	 * deleted in the form .
	 *
	 * @param	array		form data, must not be empty
	 */
	public function processImageUpload(array $formData) {
		if (($formData['caption'] != '') && ($formData['image']['name'] != '')) {
			$this->realtyObject->addImageRecord(
				strip_tags($formData['caption']),
				$this->getFormidablesUniqueFileName($formData['image']['name'])
			);
		}
		if (is_array($formData['imagesToDelete'])) {
			foreach ($formData['imagesToDelete'] as $key) {
				$this->realtyObject->markImageRecordAsDeleted($key);
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
	 * Returns an array of caption-value pairs of currently appended images.
	 *
	 * @return	array		caption-value pairs to fill the images checkbox, will
	 * 						be empty if the current record does not have images
	 */
	public function populateImageList() {
		$result = array();

		foreach ($this->realtyObject->getAllImageData() as $key => $imageRecord) {
			$result[] = array(
				'caption' => $imageRecord['caption'].' ('.$imageRecord['image'].')',
				'value' => $key
			);
		}

		return $result;
	}

	/**
	 * Checks whether the provided file is valid.
	 *
	 * @param	array		form data to check, must not be empty
	 *
	 * @return	boolean		whether the provided file is a valid image
	 */
	public function checkFile(array $valueToCheck) {
		$maximumFileSizeInByte
			= $GLOBALS['TYPO3_CONF_VARS']['BE']['maxFileSize'] * 1000;

		if ($valueToCheck['value']['name'] == '') {
			$this->validationError = '';
		} elseif ($this->getFormValue('caption') == '') {
			$this->validationError = 'emptyCaption';
		} elseif ($valueToCheck['value']['size'] > $maximumFileSizeInByte) {
			$this->validationError = 'filesize';
		} elseif (!preg_match(
			'/^.+\.(jpg|jpeg|png|gif)$/i', $valueToCheck['value']['name']
		)) {
			$this->validationError = 'filetype';
		}

		return ($this->validationError == '');
	}

	/**
	 * Returns an error message if the provided file was invalid.
	 *
	 * Note: This function must only be called if there is an error message
	 * to return.
	 *
	 * @return	string		localized validation error message, will not be empty
	 */
	public function getImageUploadErrorMessage() {
		switch ($this->validationError) {
			case 'filesize':
				$label = 'message_image_too_large';
				break;
			case 'filetype':
				$label = 'message_invalid_type';
				break;
			case 'emptyCaption':
				$label = 'message_empty_caption';
				break;
			default:
				$label = '';
				break;
		}

		return $this->plugin->translate($label);
	}

	/**
	 * Returns the self-URL with the current "showUid" as link parameter.
	 *
	 * @return	string		self-URL of the image upload page, will not be empty
	 */
	public function getSelfUrlWithShowUid() {
		return $this->plugin->cObj->typoLink_URL(
			array(
				'parameter' => $GLOBALS['TSFE']->id,
				'additionalParams' => t3lib_div::implodeArrayForUrl(
					$this->plugin->prefixId,
					array('showUid' => $this->plugin->piVars['showUid']),
					'',
					true
				),
			)
		);
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
	 * @param	string		file name derived from the form data, must not be
	 * 						empty
	 *
	 * @return	string		unique file name used under wich this file is stored
	 * 						in the upload directory, will not be empty
	 */
	private function getFormidablesUniqueFileName($fileName) {
		return ($this->isTestMode)
			? $fileName
			: ($this->formCreator->aORenderlets['image']->sCoolFileName);
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/pi1/class.tx_realty_frontEndImageUpload.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/pi1/class.tx_realty_frontEndImageUpload.php']);
}
?>