<?php

/**
 * This is merely a class used for unit tests. Don't use it for any other purpose.
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 */
final class tx_realty_Model_RealtyObjectChild extends \tx_realty_Model_RealtyObject
{
    /**
     * Creates a new record with the contents of the array $realtyData, unless
     * it is empty, in the database. All fields to insert must already exist in
     * the database.
     * The values for PID, 'tstamp' and 'crdate' are provided by this function.
     *
     * @param array $realtyData
     *        database column names as keys, must not be empty and must not contain the key 'uid'
     * @param string $table
     *        name of the database table, must not be empty
     * @param int $overridePid PID
     *        for new realty and image records (omit this parameter to use the PID set in the global configuration)
     *
     * @throws InvalidArgumentException
     *
     * @return int UID of the new database entry, will be zero if no new
     *                 record could be created, will be -1 if the deleted flag
     *                 was set
     */
    public function createNewDatabaseEntry(
        array $realtyData,
        $table = 'tx_realty_objects',
        $overridePid = 0
    ) {
        return parent::createNewDatabaseEntry($realtyData, $table, $overridePid);
    }

    /**
     * Checks the type of data input. Returns the type if it is valid, else
     * returns an empty string.
     *
     * @param mixed $realtyData
     *        data for the realty object: an array or a UID (of integer > 0)
     *
     * @return string
     *         type of data: 'array', 'uid' or empty in case case of any other
     *         type
     */
    public function getDataType($realtyData)
    {
        return parent::getDataType($realtyData);
    }

    /**
     * Prepares the realty object for insertion and inserts records to
     * the related tables.
     * It writes the values of 'city', 'apartment_type', 'house_type',
     * 'district', 'pets' and 'garage_type', in case they are defined, as
     * records to their own tables and stores the UID of the record instead of
     * the value.
     *
     * @return void
     */
    public function prepareInsertionAndInsertRelations()
    {
        parent::prepareInsertionAndInsertRelations();
    }

    /**
     * Returns all data from a realty object as an array.
     *
     * @return array current realty object data, may be empty
     */
    public function getAllProperties()
    {
        return parent::getAllProperties();
    }
}
