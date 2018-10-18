<?php

/**
 * This is merely a class used for unit tests. Don't use it for any other purpose.
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 */
final class tx_realty_openImmoImportChild extends \tx_realty_openImmoImport
{
    /**
     * Checks the correct punctuation of a path to a directory. Adds a slash if
     * missing and strips whitespaces.
     *
     * @param string $directory path to be checked, must not be empty
     *
     * @return string checked path, possibly modified
     */
    public function unifyPath($directory)
    {
        return parent::unifyPath($directory);
    }

    /**
     * Gets an array of the paths of all ZIP archives in the import folder
     * and its subfolders.
     *
     * @param string $importDirectory absolute path of the directory which contains the ZIPs, must not be empty
     *
     * @return string[] absolute paths of ZIPs in the import folder, might be empty
     */
    public function getPathsOfZipsToExtract($importDirectory)
    {
        return parent::getPathsOfZipsToExtract($importDirectory);
    }

    /**
     * Gets a name for a folder according to the ZIP archive to extract to it.
     *
     * @param string $pathOfZip path of a ZIP archive, must not be empty
     *
     * @return string path for a folder named like the ZIP archive, empty
     *                if the passed string is empty
     */
    public function getNameForExtractionFolder($pathOfZip)
    {
        return parent::getNameForExtractionFolder($pathOfZip);
    }

    /**
     * Finds an XML file in the folder named like $pathOfZip without the suffix
     * '.zip' and returns its path. The ZIP archive must have been extracted
     * before. In case no or several XML files are found, an empty string is
     * returned and the error is logged.
     *
     * @param string $pathOfZip absolute path where to find the ZIP archive which includes an XML file, must not be
     *     empty
     *
     * @return string absolute path of the XML file, empty string on error
     */
    public function getPathForXml($pathOfZip)
    {
        return parent::getPathForXml($pathOfZip);
    }

    /**
     * Loads and validates an XML file from a ZIP archive as a DOMDocument which
     * is stored in an array.
     * The ZIP archive must have been extracted to a folder named like the ZIP
     * without the suffix '.zip' before.
     *
     * @param string $pathOfZip absolute path where to find the ZIP archive which includes an XML file, must not be
     *     empty
     *
     * @return void
     */
    public function loadXmlFile($pathOfZip)
    {
        parent::loadXmlFile($pathOfZip);
    }

    /**
     * Tries to write an imported record to the database and checks the contact
     * e-mail address. If the address is invalid, it is replaced by the default
     * address as configured in EM.
     * Note: There is no check for the validity of the default address. If the
     * DOMDocument cannot be loaded, or if required fields are missing, the
     * record will not be inserted to the database. Success and failures are
     * logged.
     *
     * @param array $realtyRecord record to insert, may be empty
     *
     * @return void
     */
    public function writeToDatabase(array $realtyRecord)
    {
        parent::writeToDatabase($realtyRecord);
    }

    /**
     * Returns the current content of the currently loaded XML file as a
     * DOMDocument.
     *
     * @return DOMDocument loaded XML file, may be NULL if no document was
     *                     loaded e.g. due to validation errors
     */
    public function getImportedXml()
    {
        return parent::getImportedXml();
    }

    /**
     * Gets the required fields of a realty object.
     * This function is needed for unit testing only.
     *
     * @return string[] required fields, may be empty if no fields are
     *               required or if the realty object is not initialized
     */
    public function getRequiredFields()
    {
        return parent::getRequiredFields();
    }

    /**
     * Ensures a contact e-mail address for the current realty record. Checks
     * whether there is a valid contact e-mail for the current record. Inserts
     * the default address configured in EM if 'contact_email' if the current
     * record's contact e-mail is empty or invalid.
     *
     * @return void
     */
    public function ensureContactEmail()
    {
        parent::ensureContactEmail();
    }

    /**
     * Prepares the sending of e-mails. Resorts $emailData. Sets the value for
     * 'recipient' to the default e-mail address wherever there is no e-mail
     * address given. Sets the value for 'objectNumber' to '------' if is not
     * set. Purges empty records, so no empty messages are sent.
     * If 'onlyErrors' is enabled in EM, the messages will just contain error
     * messages and no information about success.
     *
     * @param array[] $emailData
     *        Two-dimensional array of e-mail data. Each inner array has the elements 'recipient', 'objectNumber',
     *     'logEntry' and
     *        'errorLog'. May be empty.
     *
     * @return array[] Three dimensional array with e-mail addresses as
     *               keys of the outer array. Innermost there is an array
     *               with only one element: Object number as key and the
     *               corresponding log information as value. This array
     *               is wrapped by a numeric array as object numbers are
     *               not necessarily unique. Empty if the input array is
     *               empty or invalid.
     */
    public function prepareEmails(array $emailData)
    {
        return parent::prepareEmails($emailData);
    }

    /**
     * Returns the contact e-mail address of a realty object.
     *
     * The returned e-mail address depends on the configuration for
     * 'useFrontEndUserDataAsContactDataForImportedRecords'. If this option is
     * enabled and if there is an owner, the owner's e-mail address will be
     * fetched. Otherwise the contact e-mail address found in the realty record
     * will be returned.
     *
     * @return string e-mail address, depending on the configuration either the
     *                field 'contact_email' from the realty record or the
     *                owner's e-mail address, will be empty if no e-mail address
     *                was found or if the realty object is not initialized
     */
    public function getContactEmailFromRealtyObject()
    {
        return parent::getContactEmailFromRealtyObject();
    }

    /**
     * Loads a realty object.
     *
     * The data can either be a database result row or an array which has
     * database column names as keys (may be empty). The data can also be a UID
     * of an existent realty object to load from the database. If the data is of
     * an invalid type the realty object stays empty.
     *
     * @param array|mixed $data
     *        data for the realty object as an array
     *
     * @return void
     */
    public function loadRealtyObject($data)
    {
        parent::loadRealtyObject($data);
    }

    /**
     * Converts a DOMDocument to an array.
     *
     * @param DOMDocument|null $realtyRecords which contains realty records, can be NULL
     *
     * @return array[] $realtyRecords realty records in an array, will be empty if the data was not convertible
     */
    public function convertDomDocumentToArray(DOMDocument $realtyRecords = null)
    {
        return parent::convertDomDocumentToArray($realtyRecords);
    }

    /**
     * Sets the path for the upload directory and updated the fileNameMapper's
     * destination path accordingly. This path must be valid and absolute and
     * may end with a trailing slash.
     *
     * @param string $path absolute path of the upload directory, must not be empty
     *
     * @return void
     */
    public function setUploadDirectory($path)
    {
        parent::setUploadDirectory($path);
    }
}
