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

require_once(t3lib_extMgm::extPath('realty') . 'lib/class.tx_realty_lightboxIncluder.php');

/**
 * Class 'tx_realty_pi1_ImageThumbnailsView' for the 'realty' extension.
 *
 * This class renders the images for one realty object as thumbnails.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 */
class tx_realty_pi1_ImageThumbnailsView extends tx_realty_pi1_FrontEndView {
	/**
	 * @var integer UID of the realty object to show
	 */
	private $showUid = 0;

	/**
	 * Returns the image thumbnails for one realty object as HTML. If the
	 * gallery type is configured to be classic, the "galleryPID" is
	 * required to be configured.
	 *
	 * @param array piVars array, must contain the key "showUid" with a valid
	 *              realty object UID as value
	 *
	 * @return string HTML for the image thumbnails, will be empty if there are
	 *                no images to render
	 */
	public function render(array $piVars = array()) {
		$this->showUid = $piVars['showUid'];
		$useLightbox = ($this->getConfValueString('galleryType') == 'lightbox');

		$this->setSubpart('images_list', $this->createImages($useLightbox));

		return $this->getSubpart('IMAGES_LIST');
	}

	/**
	 * Creates all images that are attached to the current record.
	 *
	 * @param boolean whether to use the lightbox-styled gallery
	 *
	 * @return string HTML for the images, will be empty if there are no images
	 */
	private function createImages($useLightbox) {
		if ($useLightbox) {
			tx_realty_lightboxIncluder::includeLightboxFiles(
				$this->prefixId, $this->extKey
			);
		}

		$result = '';
		$counter = 0;

		$currentImage = $this->getLinkedImage($useLightbox);

		while ($currentImage != '') {
			$counter++;
			$this->setMarker('one_image_tag', $currentImage);
			$result .= $this->getSubpart('ONE_IMAGE_CONTAINER');
			$currentImage = $this->getLinkedImage($useLightbox, $counter);
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
	 * @param boolean whether to use the lightbox-styled gallery
	 * @param integer the number of the image to retrieve, must be >= 0
	 *
	 * @return string image tag wrapped in a link, will be empty if there is no
	 *                image with the provided number
	 */
	private function getLinkedImage($useLightbox, $imageNumber = 0) {
		$imageRecord = $this->getImage($imageNumber);

		if (empty($imageRecord)) {
			return '';
		}

		$galleryUrl = $this->createGalleryUrl(
			$useLightbox
				? REALTY_UPLOAD_FOLDER . $imageRecord['image']
				: $this->getConfValueInteger('galleryPID'),
			$imageNumber
		);

		$linkAttribute = $useLightbox
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
	 * @return array the image's file name and htmlspecialchared caption in an
	 *               associative array, will be empty if the image with the
	 *               requested number does not exist
	 */
	private function getImage($imageNumber = 0) {
		$images = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->find($this->getUid())->getAllImageData();

		if (isset($images[$imageNumber])) {
			$result = $images[$imageNumber];
			$result['caption'] = htmlspecialchars($result['caption']);
		} else {
			$result = array();
		}

		return $result;
	}

	/**
	 * Creates the URL of a gallery image.
	 *
	 * @param string the destination of the image link, must not be empty
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
	 * Returns the current "showUid".
	 *
	 * @return UID of the realty record to show
	 */
	private function getUid() {
		return $this->showUid;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/pi1/class.tx_realty_pi1_ImageThumbnailsView.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/pi1/class.tx_realty_pi1_ImageThumbnailsView.php']);
}
?>