<?php

/**
 * This class represents an attached document.
 *
 * @author Bernd Schönbach <bernd.schoenbach@googlemail.com>
 */
class tx_realty_Model_Document extends tx_realty_Model_AbstractTitledModel implements Tx_Oelib_Interface_Sortable
{
    /**
     * the folder where uploaded documents get stored.
     *
     * @var string
     */
    const UPLOAD_FOLDER = 'uploads/tx_realty/';

    /**
     * Gets the file name of this document (relative to the extension's upload
     * directory).
     *
     * @return string this document's file name, will be empty if no file name has been set
     */
    public function getFileName()
    {
        return $this->getAsString('filename');
    }

    /**
     * Sets this document's file name.
     *
     * @param string $fileName
     *        the name of the file relative to the extension's upload
     *        directory, must not be empty
     *
     * @return void
     */
    public function setFileName($fileName)
    {
        if ($fileName === '') {
            throw new InvalidArgumentException('$fileName must not be empty.', 1333036052);
        }

        $this->setAsString('filename', $fileName);
    }

    /**
     * Gets the realty object this document is related to.
     *
     * @return tx_realty_Model_RealtyObject the related object, will be NULL
     *                                      if non has been assigned
     */
    public function getObject()
    {
        return $this->getAsModel('object');
    }

    /**
     * Sets the realty object this document is related to.
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
     * Returns the sorting value for this document.
     *
     * This is the sorting as used in the back end.
     *
     * @return int the sorting value of this document, will be >= 0
     */
    public function getSorting()
    {
        return $this->getAsInteger('sorting');
    }

    /**
     * Sets the sorting value for this document.
     *
     * This is the sorting as used in the back end.
     *
     * @param int $sorting the sorting value of this document, must be >= 0
     *
     * @return void
     */
    public function setSorting($sorting)
    {
        $this->setAsInteger('sorting', $sorting);
    }
}
