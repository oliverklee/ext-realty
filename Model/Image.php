<?php

/**
 * This class represents a titled image.
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class tx_realty_Model_Image extends tx_realty_Model_AbstractTitledModel implements Tx_Oelib_Interface_Sortable
{
    /**
     * the folder where uploaded images get stored.
     *
     * @var string
     */
    const UPLOAD_FOLDER = 'uploads/tx_realty/';

    /**
     * @var string
     */
    protected $titleFieldName = 'caption';

    /**
     * @var bool
     */
    protected $allowEmptyTitle = true;

    /**
     * Gets the file name of this image (relative to the extension's upload
     * directory).
     *
     * @return string the image file name, will be empty if no file name has
     *                been set
     */
    public function getFileName()
    {
        return $this->getAsString('image');
    }

    /**
     * Sets the image file name.
     *
     * @param string $fileName
     *        the name of the image file relative to the extension's upload
     *        directory, must not be empty
     *
     * @return void
     */
    public function setFileName($fileName)
    {
        if ($fileName === '') {
            throw new InvalidArgumentException('$fileName must not be empty.', 1333036064);
        }

        $this->setAsString('image', $fileName);
    }

    /**
     * Gets the realty object this image is related to.
     *
     * @return tx_realty_Model_RealtyObject the related object, will be NULL
     *                                      if non has been assigned
     */
    public function getObject()
    {
        return $this->getAsModel('object');
    }

    /**
     * Sets the realty object this image is related to.
     *
     * @param tx_realty_Model_RealtyObject $realtyObject
     *        the related object to assign
     *
     * @return void
     */
    public function setObject(tx_realty_Model_RealtyObject $realtyObject)
    {
        $this->set('object', $realtyObject);
    }

    /**
     * Returns the sorting value for this image.
     *
     * This is the sorting as used in the back end.
     *
     * @return int the sorting value of this image, will be >= 0
     */
    public function getSorting()
    {
        return $this->getAsInteger('sorting');
    }

    /**
     * Sets the sorting value for this image.
     *
     * This is the sorting as used in the back end.
     *
     * @param int $sorting the sorting value of this image, must be >= 0
     *
     * @return void
     */
    public function setSorting($sorting)
    {
        $this->setAsInteger('sorting', $sorting);
    }
}
