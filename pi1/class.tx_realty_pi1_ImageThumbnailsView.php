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
     * size and lightbox configuration for the images using the image position
     * number (0...n) as first-level array keys
     *
     * @var array[]
     */
    private $imageConfiguration = [];

    /**
     * the number of image subparts in the default HTML template which will be
     * be hidden if there are no images for that position
     *
     * @var int
     */
    const IMAGE_POSITIONS_IN_DEFAULT_TEMPLATE = 4;

    /**
     * Returns the image thumbnails for one realty object as HTML.
     *
     * @param array $piVars piVars array, must contain the key "showUid" with a valid realty object UID as value
     *
     * @return string HTML for the image thumbnails, will be empty if there are
     *                no images to render
     */
    public function render(array $piVars = [])
    {
        $this->showUid = $piVars['showUid'];

        $this->createImageConfiguration();

        return ($this->renderImages() > 0)
            ? $this->getSubpart('FIELD_WRAPPER_IMAGETHUMBNAILS') : '';
    }

    /**
     * Creates all images that are attached to the current record and puts them
     * in their particular subparts.
     *
     * @return int the total number of rendered images, will be >= 0
     */
    private function renderImages()
    {
        /** @var tx_realty_Mapper_RealtyObject $realtyObjectMapper */
        $realtyObjectMapper = Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_RealtyObject::class);
        /** @var tx_realty_Model_RealtyObject $realtyObject */
        $realtyObject = $realtyObjectMapper->find($this->getUid());
        $allImages = $realtyObject->getImages();

        $imagesByPosition = [];
        $usedPositions = max(
            self::IMAGE_POSITIONS_IN_DEFAULT_TEMPLATE,
            $this->findHighestConfiguredPositionIndex()
        );
        for ($i = 0; $i <= $usedPositions; $i++) {
            $imagesByPosition[$i] = [];
        }

        /** @var tx_realty_Model_Image $image */
        foreach ($allImages as $image) {
            $position = $image->getPosition();
            $imagesByPosition[$position][] = $image;
        }

        /** @var tx_realty_Model_Image[] $images */
        foreach ($imagesByPosition as $position => $images) {
            $this->renderImagesInPosition($position, $images);
        }

        return $allImages->count();
    }

    /**
     * Renders all images for a given position and fills the corresponding
     * subpart in the template.
     *
     * @param int $position the zero-based position index of the images
     * @param tx_realty_Model_Image[] $images
     *        the images to render, must all be in position $position
     *
     * @return void
     */
    private function renderImagesInPosition($position, array $images)
    {
        $containerSubpartName = ($position > 0) ? 'IMAGES_POSITION_' . $position : 'ONE_IMAGE_CONTAINER';
        if (empty($images)) {
            $this->hideSubparts($containerSubpartName);
            return;
        }

        $itemSubpartName = ($position > 0)
            ? 'ONE_IMAGE_CONTAINER_' . $position : 'ONE_IMAGE_CONTAINER';

        $result = '';
        foreach ($images as $image) {
            $configuration = $this->getImageConfigurationForContainer($position);
            $currentImage = $configuration['enableLightbox']
                ? $this->createLightboxThumbnail($image)
                : $this->createThumbnail($image);
            $this->setMarker('one_image_tag', $currentImage);
            $result .= $this->getSubpart($itemSubpartName);
        }

        $this->setSubpart($itemSubpartName, $result);
    }

    /**
     * Creates a thumbnail (without Lightbox) of $image sized as per the
     * configuration.
     *
     * @param tx_realty_Model_Image $image
     *        the image to render
     *
     * @return string
     *         image tag, will not be empty
     */
    protected function createThumbnail(tx_realty_Model_Image $image)
    {
        $containerImageConfiguration = $this->getImageConfigurationForContainer($image->getPosition());

        $fileName = $image->hasThumbnailFileName() ? $image->getThumbnailFileName() : $image->getFileName();
        $title = $image->getTitle();

        $imageConfiguration = [
            'altText' => $title,
            'titleText' => $title,
            'file' => tx_realty_Model_Image::UPLOAD_FOLDER . $fileName,
            'file.' => [
                'width' => $containerImageConfiguration['thumbnailSizeX'] . 'c',
                'height' => $containerImageConfiguration['thumbnailSizeY'] . 'c',
            ],
        ];

        return $this->cObj->cObjGetSingle('IMAGE', $imageConfiguration);
    }

    /**
     * Creates a Lightbox thumbnail of $image sized as per the configuration.
     *
     * @param tx_realty_Model_Image $image
     *        the image to render
     *
     * @return string
     *         image tag wrapped in a Lightbox link, will not be empty
     */
    protected function createLightboxThumbnail(tx_realty_Model_Image $image)
    {
        $thumbnailTag = $this->createThumbnail($image);

        $position = $image->getPosition();
        $configuration = $this->getImageConfigurationForContainer($position);

        $imageConfiguration = [
            'altText' => $image->getTitle(),
            'titleText' => $image->getTitle(),
            'file' => tx_realty_Model_Image::UPLOAD_FOLDER . $image->getFileName(),
            'file.' => [
                'maxW' => $configuration['lightboxSizeX'],
                'maxH' => $configuration['lightboxSizeY'],
            ],
        ];
        $imageWithTag = $this->cObj->cObjGetSingle('IMAGE', $imageConfiguration);

        $imagePath = [];
        preg_match('/src="([^"]*)"/', $imageWithTag, $imagePath);
        $fullSizeImageUrl = $imagePath[1];

        $lightboxGallerySuffix = ($position > 0) ? '_' . $position : '';
        $linkAttribute = ' data-lightbox="objectGallery' . $lightboxGallerySuffix .
            '" data-title="' . htmlspecialchars($image->getTitle()) . '"';

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
     * Gathers the image configuration for all configured image containers in
     * $this->imageConfiguration.
     *
     * @return void
     */
    private function createImageConfiguration()
    {
        $configuration = Tx_Oelib_ConfigurationRegistry::get('plugin.tx_realty_pi1');

        $highestPositionIndex = $this->findHighestConfiguredPositionIndex();
        for ($position = 0; $position <= $highestPositionIndex; $position++) {
            $accumulatedConfiguration = [
                'enableLightbox' => $configuration->getAsBoolean('enableLightbox'),
                'thumbnailSizeX' => $configuration->getAsInteger('singleImageMaxX'),
                'thumbnailSizeY' => $configuration->getAsInteger('singleImageMaxY'),
                'lightboxSizeX' => $configuration->getAsInteger('lightboxImageWidthMax'),
                'lightboxSizeY' => $configuration->getAsInteger('lightboxImageHeightMax'),
            ];

            if ($position > 0) {
                $specificConfiguration = Tx_Oelib_ConfigurationRegistry::get('plugin.tx_realty_pi1.images')
                    ->getAsMultidimensionalArray($position . '.');
                if (isset($specificConfiguration['enableLightbox'])) {
                    $accumulatedConfiguration['enableLightbox'] = (bool)$specificConfiguration['enableLightbox'];
                }
                if (isset($specificConfiguration['singleImageMaxX'])) {
                    $accumulatedConfiguration['thumbnailSizeX'] = (int)$specificConfiguration['singleImageMaxX'];
                }
                if (isset($specificConfiguration['singleImageMaxY'])) {
                    $accumulatedConfiguration['thumbnailSizeY'] = (int)$specificConfiguration['singleImageMaxY'];
                }
                if (isset($specificConfiguration['lightboxImageWidthMax'])) {
                    $accumulatedConfiguration['lightboxSizeX'] = (int)$specificConfiguration['lightboxImageWidthMax'];
                }
                if (isset($specificConfiguration['lightboxImageHeightMax'])) {
                    $accumulatedConfiguration['lightboxSizeY'] = (int)$specificConfiguration['lightboxImageHeightMax'];
                }
            }

            $this->imageConfiguration[$position] = $accumulatedConfiguration;
        }
    }

    /**
     * Gets the image configuration for the image container with the index
     * $containerIndex.
     *
     * @param int $containerIndex
     *        index of the image container, must be >= 0
     *
     * @return string[]
     *         the configuration for the image container with the requested
     *         index using the array keys "enableLightbox", "singleImageMaxX",
     *         "singleImageMaxY", "lightboxImageWidthMax" and
     *         "lightboxImageHeightMax"
     */
    private function getImageConfigurationForContainer($containerIndex)
    {
        return $this->imageConfiguration[$containerIndex];
    }

    /**
     * Finds the highest position index that has been configured via TS setup.
     *
     * @return int the highest container index in use, will be >= 0
     */
    private function findHighestConfiguredPositionIndex()
    {
        $highestIndex = 0;

        $imageConfigurations = Tx_Oelib_ConfigurationRegistry::get('plugin.tx_realty_pi1')->getAsMultidimensionalArray('images.');

        foreach (array_keys($imageConfigurations) as $key) {
            $index = (int)$key;
            if ($index > $highestIndex) {
                $highestIndex = $index;
            }
        }

        return $highestIndex;
    }
}
