<?php

/**
 * This class renders the images for one realty object as thumbnails.
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class tx_realty_pi1_ImageThumbnailsView extends tx_realty_pi1_FrontEndView
{
    /**
     * @var int UID of the realty object to show
     */
    private $showUid = 0;

    /**
     * Returns the image thumbnails for one realty object as HTML.
     *
     * @param array $piVars piVars array, must contain the key "showUid" with a valid realty object UID as value
     *
     * @return string HTML for the image thumbnails, will be empty if there are no images to render
     *
     * @throws \Tx_Oelib_Exception_NotFound
     */
    public function render(array $piVars = [])
    {
        $this->showUid = (int)$piVars['showUid'];

        return ($this->renderLegacyImages() > 0) ? $this->getSubpart('FIELD_WRAPPER_IMAGETHUMBNAILS') : '';
    }

    /**
     * Creates all images that are attached to the current record and puts them
     * in their particular subparts.
     *
     * @return int the total number of rendered images
     *
     * @throws \Tx_Oelib_Exception_NotFound
     */
    private function renderLegacyImages()
    {
        $enableLightbox = $this->getPluginConfiguration()->getAsBoolean('enableLightbox');
        /** @var \tx_realty_Mapper_RealtyObject $realtyObjectMapper */
        $realtyObjectMapper = \Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_RealtyObject::class);
        /** @var \tx_realty_Model_RealtyObject $realtyObject */
        $realtyObject = $realtyObjectMapper->find($this->getUid());

        $result = '';
        /** @var \tx_realty_Model_Image $image */
        foreach ($realtyObject->getImages() as $image) {
            $currentImage = $enableLightbox
                ? $this->createLegacyLightboxThumbnail($image) : $this->createLegacyThumbnail($image);
            $this->setMarker('one_image_tag', $currentImage);
            $result .= $this->getSubpart('ONE_IMAGE_CONTAINER');
        }

        $this->setSubpart('ONE_IMAGE_CONTAINER', $result);

        return \count($realtyObject->getImages());
    }

    /**
     * Creates a thumbnail (without Lightbox) of $image sized as per the configuration.
     *
     * @param \tx_realty_Model_Image $image
     *
     * @return string image tag, will not be empty
     */
    protected function createLegacyThumbnail(\tx_realty_Model_Image $image)
    {
        $configuration = $this->getPluginConfiguration();
        $imageConfiguration = [
            'altText' => $image->getTitle(),
            'titleText' => $image->getTitle(),
            'file' => \tx_realty_Model_Image::UPLOAD_FOLDER . $image->getFileName(),
            'file.' => [
                'width' => $configuration->getAsInteger('singleImageMaxX') . 'c',
                'height' => $configuration->getAsInteger('singleImageMaxY') . 'c',
            ],
        ];

        return $this->cObj->cObjGetSingle('IMAGE', $imageConfiguration);
    }

    /**
     * Creates a Lightbox thumbnail of $image sized as per the configuration.
     *
     * @param \tx_realty_Model_Image $image
     *        the image to render
     *
     * @return string
     *         image tag wrapped in a Lightbox link, will not be empty
     */
    protected function createLegacyLightboxThumbnail(\tx_realty_Model_Image $image)
    {
        $thumbnailTag = $this->createLegacyThumbnail($image);
        $configuration = $this->getPluginConfiguration();
        $imageConfiguration = [
            'altText' => $image->getTitle(),
            'titleText' => $image->getTitle(),
            'file' => \tx_realty_Model_Image::UPLOAD_FOLDER . $image->getFileName(),
            'file.' => [
                'maxW' => $configuration->getAsInteger('lightboxImageWidthMax'),
                'maxH' => $configuration->getAsInteger('lightboxImageHeightMax'),
            ],
        ];
        $imageWithTag = $this->cObj->cObjGetSingle('IMAGE', $imageConfiguration);

        $imagePath = [];
        \preg_match('/src="([^"]*)"/', $imageWithTag, $imagePath);
        $fullSizeImageUrl = $imagePath[1];

        $linkAttribute = ' data-lightbox="objectGallery" data-title="' .
            \htmlspecialchars($image->getTitle(), ENT_COMPAT | ENT_HTML5) . '"';

        return '<a href="' . $fullSizeImageUrl . '"' . $linkAttribute . '>' . $thumbnailTag . '</a>';
    }

    /**
     * Returns the current "showUid".
     *
     * @return int UID of the realty record to show
     */
    private function getUid()
    {
        return $this->showUid;
    }

    /**
     * @return \Tx_Oelib_Configuration
     */
    private function getPluginConfiguration()
    {
        return \Tx_Oelib_ConfigurationRegistry::get('plugin.tx_realty_pi1');
    }
}
