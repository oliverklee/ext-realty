<?php

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This class assumes the image upload for the FE editor in the realty plugin.
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class tx_realty_frontEndImageUpload extends tx_realty_frontEndForm
{
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
    public function render(array $unused = [])
    {
        $result = parent::render($unused);
        tx_realty_lightboxIncluder::includeLightboxFiles(
            $this->prefixId,
            $this->extKey
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
    public function getRedirectUrl()
    {
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
    public function processImageUpload(array $formData)
    {
        $caption = (string)$formData['caption'];
        $fileName = (string)$formData['image'];
        if ($caption !== '' && $fileName !== '') {
            $this->realtyObject->addImageRecord(strip_tags($caption), $fileName);
        }

        $idsOfImagesToDelete = GeneralUtility::trimExplode(
            ',',
            $formData['imagesToDelete'],
            true
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
     * @param array $valueToCheck data to check, must not be empty
     *
     * @return bool whether the provided file is a valid image
     */
    public function checkFile(array $valueToCheck)
    {
        $fileName = (string)$valueToCheck['value'];
        if ($fileName === '') {
            return true;
        }

        $validationErrorLabel = '';
        $imageExtensions = GeneralUtility::trimExplode(
            ',',
            $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'],
            true
        );
        if (in_array('pdf', $imageExtensions, true)) {
            unset($imageExtensions[array_search('pdf', $imageExtensions, true)]);
        }
        if (in_array('ps', $imageExtensions, true)) {
            unset($imageExtensions[array_search('ps', $imageExtensions, true)]);
        }
        $extensionValidator = '/^.+\\.(' . implode('|', $imageExtensions) . ')$/i';

        if ($this->getFormValue('caption') === '') {
            $validationErrorLabel = 'message_empty_caption';
        } elseif (!preg_match($extensionValidator, $fileName)) {
            $validationErrorLabel = 'message_invalid_type';
        }

        $this->validationError = ($validationErrorLabel !== '')
            ? $this->translate($validationErrorLabel)
            : '';

        return $validationErrorLabel === '';
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
    public function getImageUploadErrorMessage()
    {
        return $this->validationError;
    }

    ////////////////////////////////////
    // Miscellaneous helper functions.
    ////////////////////////////////////

    /**
     * Returns the URL to the current page.
     *
     * @return string URL of the current page, will not be empty
     */
    private function getUrlOfCurrentPage()
    {
        return GeneralUtility::locationHeaderUrl(
            $this->cObj->typoLink_URL(
                [
                    'parameter' => $this->getFrontEndController()->id,
                    'additionalParams' => GeneralUtility::implodeArrayForUrl(
                        '',
                        [$this->prefixId => ['showUid' => $this->realtyObjectUid]]
                    ),
                    'useCacheHash' => true,
                ]
            )
        );
    }

    /**
     * Returns HTML for the images as list items with their thumbnails.
     *
     * @param Tx_Oelib_List<tx_realty_Model_Image> $images
     *        the images to render, may be empty
     *
     * @return string listed images with thumbnails in HTML, will not be empty
     */
    private function getRenderedImageList(Tx_Oelib_List $images)
    {
        $result = '';

        $index = 0;
        /** @var tx_realty_Model_Image $image */
        foreach ($images as $image) {
            $imagePath = tx_realty_Model_Image::UPLOAD_FOLDER . $image->getFileName();
            $imageUrl = htmlspecialchars(GeneralUtility::locationHeaderUrl(
                $this->cObj->typoLink_URL(['parameter' => $imagePath, 'useCacheHash' => true])
            ));
            $title = $image->getTitle();

            $imageConfiguration = [
                'altText' => '',
                'titleText' => $title,
                'file' => $imagePath,
                'file.' => [
                    'width' => $this->getConfValueInteger('imageUploadThumbnailWidth') . 'c',
                    'height' => $this->getConfValueInteger('imageUploadThumbnailHeight') . 'c',
                ],
            ];
            $imageTag = $this->cObj->IMAGE($imageConfiguration);
            $this->setMarker(
                'single_image_item',
                '<a href="' . $imageUrl . '" rel="lightbox[objectGallery]" ' .
                'title="' . htmlspecialchars($title) . '"' .
                '>' . $imageTag . '</a>'
            );
            $this->setMarker(
                'image_title',
                htmlspecialchars($title)
            );
            $this->setMarker(
                'image_title_for_js',
                htmlspecialchars(addslashes($title))
            );
            $this->setMarker(
                'single_attached_image_id',
                'attached_image_' . $index
            );
            $result .= $this->getSubpart('SINGLE_ATTACHED_IMAGE');

            $index++;
        }

        return $result;
    }
}
