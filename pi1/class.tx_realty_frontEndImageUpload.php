<?php

use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This class assumes the image upload for the FE editor in the realty plugin.
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class tx_realty_frontEndImageUpload extends \tx_realty_frontEndForm
{
    /**
     * @var string stores the type of validation error if there was one
     */
    private $validationError = '';

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
        $this->processTemplate($result);
        $this->setLabels();

        $images = $this->realtyObject->getJpegAttachments();

        if (empty($images)) {
            $this->hideSubparts('images_to_delete', 'wrapper');
        } else {
            $this->setSubpart('single_attached_image', $this->getRenderedImageList($images));
        }

        return $this->getSubpart();
    }

    /**
     * Inserts the image record into the database if one has been provided in $formData.
     * Deletes image records of the current record if images were checked to be deleted in the form.
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
            $absoluteFileName = GeneralUtility::getFileAbsFileName('uploads/tx_realty/' . $fileName);
            $this->realtyObject->addAndSaveAttachment($absoluteFileName, $caption);
            \unlink($absoluteFileName);
        }

        $uidsOfFilesToDelete = GeneralUtility::intExplode(',', $formData['imagesToDelete'], true);
        foreach ($uidsOfFilesToDelete as $fileUid) {
            $this->realtyObject->removeAttachmentByFileUid($fileUid);
        }

        // The original PID is provided to ensure the default settings for the
        // PID are not used because this might change the record's location.
        $this->realtyObject->writeToDatabase((int)$this->realtyObject->getProperty('pid'));
        \tx_realty_cacheManager::clearFrontEndCacheForRealtyPages();
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

        if ($this->getFormValue('caption') === '') {
            $validationErrorLabel = 'message_empty_caption';
        } elseif (\strtolower(\substr($fileName, -4)) !== '.jpg') {
            $validationErrorLabel = 'message_invalid_type';
        }

        $this->validationError = ($validationErrorLabel !== '') ? $this->translate($validationErrorLabel) : '';

        return $validationErrorLabel === '';
    }

    /**
     * Returns an error message if the provided file was invalid. The result
     * will be empty if no error message was set before.
     *
     * @return string localized validation error message, will be empty if no error message was set
     *
     * @see checkFile()
     */
    public function getImageUploadErrorMessage()
    {
        return $this->validationError;
    }

    /*
     * Miscellaneous helper functions.
     */

    /**
     * Returns HTML for the images as list items with their thumbnails.
     *
     * @param FileReference[] $images
     *
     * @return string listed images with thumbnails in HTML, will not be empty
     */
    private function getRenderedImageList(array $images)
    {
        $result = '';

        foreach ($images as $image) {
            $imagePath = $image->getPublicUrl();
            $url = $this->cObj->typoLink_URL(['parameter' => $imagePath, 'useCacheHash' => true]);
            $encodedUrl = \htmlspecialchars($url, ENT_COMPAT | ENT_HTML5);
            $title = $image->getTitle();
            $encodedTitle = htmlspecialchars($title, ENT_COMPAT | ENT_HTML5);

            $imageConfiguration = [
                'altText' => '',
                'titleText' => $title,
                'file' => $imagePath,
                'file.' => [
                    'width' => $this->getConfValueInteger('imageUploadThumbnailWidth') . 'c',
                    'height' => $this->getConfValueInteger('imageUploadThumbnailHeight') . 'c',
                ],
            ];
            $imageTag = $this->cObj->cObjGetSingle('IMAGE', $imageConfiguration);
            $this->setMarker(
                'single_image_item',
                '<a href="' . $encodedUrl . '" data-lightbox="objectGallery" ' .
                'data-title="' . $encodedTitle . '"' . '>' . $imageTag . '</a>'
            );
            $this->setMarker('image_title', $encodedTitle);
            $this->setMarker('image_title_for_js', $encodedTitle);
            $this->setMarker('single_attached_image_id', $image->getOriginalFile()->getUid());
            $result .= $this->getSubpart('SINGLE_ATTACHED_IMAGE');
        }

        return $result;
    }
}
