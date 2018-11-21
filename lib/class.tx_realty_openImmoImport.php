<?php

use OliverKlee\Oelib\Email\SystemEmailFromBuilder;
use TYPO3\CMS\Core\Mail\MailMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Lang\LanguageService;

/**
 * This class imports ZIPs containing OpenImmo records.
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 */
class tx_realty_openImmoImport
{
    /**
     * @var string
     */
    const FULL_TRANSFER_MODE = 'VOLL';

    /**
     * @var string stores the complete log entry
     */
    private $logEntry = '';

    /**
     * @var string stores the complete error log
     */
    private $errors = '';

    /**
     * @var string Stores log information to be written to '$logEntry'. So it
     *             is possible to use only parts of the entire log e.g. to send
     *             e-mails only about the import of certain records to a certain
     *             contact address.
     */
    private $temporaryLogEntry = '';

    /**
     * @var string Stores log information to be written to $errors. So it is
     *             possible to use only parts of the entire log.
     */
    private $temporaryErrorLog = '';

    /**
     * @var DOMDocument the current imported XML
     */
    private $importedXml = null;

    /**
     * @var Tx_Oelib_ConfigurationProxy to access the EM configuration
     */
    private $globalConfiguration = null;

    /**
     * @var tx_realty_Model_RealtyObject
     */
    private $realtyObject = null;

    /**
     * @var tx_realty_translator
     */
    private static $translator = null;

    /**
     * @var tx_realty_fileNameMapper
     */
    private $fileNameMapper = null;

    /**
     * @var string the upload directory for images
     */
    private $uploadDirectory = '';

    /**
     * @var bool whether the current zip file should be deleted
     */
    private $deleteCurrentZipFile = true;

    /**
     * @var string[] ZIP archives which are deleted at the end of import and
     *            folders which were created during the import.
     *            Archives are added to this array if they contain exactly one
     *            XML file as this is the criterion for trying to import the
     *            XML file as an OpenImmo record.
     */
    private $filesToDelete = [];

    /**
     * whether the class is tested and only dummy records should be created
     *
     * @var bool
     */
    private $isTestMode = false;

    /**
     * @var bool
     */
    private $success = true;

    /**
     * @var LanguageService
     */
    private $languageServiceBackup = null;

    /**
     * Constructor.
     *
     * @param bool $isTestMode
     *        whether the class ist tested and therefore only dummy records should be inserted into the database
     */
    public function __construct($isTestMode = false)
    {
        $this->isTestMode = $isTestMode;
        libxml_use_internal_errors(true);
        $this->globalConfiguration = Tx_Oelib_ConfigurationProxy::getInstance('realty');
        $this->fileNameMapper = GeneralUtility::makeInstance(\tx_realty_fileNameMapper::class);
        $this->setUploadDirectory(PATH_site . tx_realty_Model_Image::UPLOAD_FOLDER);
    }

    /**
     * Extracts ZIP archives from an absolute path of a directory and inserts
     * realty records to database:
     * If the directory, specified in the EM configuration, exists and ZIP
     * archives are found, folders are created. They are named like the ZIP
     * archives without the suffix '.zip'. The ZIP archives are unpacked to
     * these folders. Then for each ZIP file the following is done: The validity
     * of the XML file found in the ZIP archive is checked by using the XSD
     * file defined in the EM. The realty records are fetched and inserted to
     * database. The validation failures are logged.
     * If the records of one XML could be inserted to database, images found
     * in the extracted ZIP archive are copied to the uploads folder.
     * Afterwards the extraction folders are removed and a log string about the
     * proceedings of import is passed back.
     * Depending on the configuration in EM the log or only the errors are sent
     * via e-mail to the contact addresses of each realty record if they are
     * available. Else the information goes to the address configured in EM. If
     * no e-mail address is configured, the sending of e-mails is disabled.
     *
     * @return string log entry with information about the proceedings of
     *                ZIP import, will not be empty, contains at least a
     *                timestamp
     */
    public function importFromZip()
    {
        $this->languageServiceBackup = $this->getLanguageService();
        $GLOBALS['LANG'] = new LanguageService();

        $this->success = true;

        $this->addToLogEntry(date('Y-m-d G:i:s') . LF);
        $checkedImportDirectory = $this->unifyPath($this->globalConfiguration->getAsString('importFolder'));

        if (!$this->canStartImport($checkedImportDirectory)) {
            $this->storeLogsAndClearTemporaryLog();
            return $this->logEntry;
        }

        $emailData = [];
        $zipsToExtract = $this->getPathsOfZipsToExtract($checkedImportDirectory);

        $this->storeLogsAndClearTemporaryLog();

        if (empty($zipsToExtract)) {
            $this->addToLogEntry($this->getTranslator()->translate('message_no_zips'));
        } else {
            foreach ($zipsToExtract as $currentZip) {
                $this->extractZip($currentZip);
                $this->loadXmlFile($currentZip);
                $recordData = $this->processRealtyRecordInsertion($currentZip);
                $emailData = array_merge($emailData, $recordData);
            }
            $this->sendEmails($this->prepareEmails($emailData));
        }

        $this->cleanUp($checkedImportDirectory);
        tx_realty_cacheManager::clearFrontEndCacheForRealtyPages();

        $this->storeLogsAndClearTemporaryLog();

        $GLOBALS['LANG'] = $this->languageServiceBackup;

        return $this->logEntry;
    }

    /**
     * @return LanguageService
     */
    private function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }

    /**
     * @return bool
     */
    public function wasSuccessful()
    {
        return $this->success;
    }

    /**
     * Gets a cached translator object (and creates it first, if necessary).
     *
     * @return tx_realty_translator the cached translator object
     */
    private function getTranslator()
    {
        if (self::$translator === null) {
            self::$translator = GeneralUtility::makeInstance(\tx_realty_translator::class);
        }

        return self::$translator;
    }

    /**
     * Processes the insertion of realty records to database. Tries to fetch the
     * data from the currently loaded XML file. If there is data, it is
     * checked whether the record should be inserted or set to deleted.
     * Success and failures are logged and an array with data for e-mails about
     * the proceedings is returned.
     *
     * @param string $currentZip path of the current ZIP file, only used for log, may be empty
     *
     * @return mixed[][]
     *         Two-dimensional array of e-mail data. Each inner array has the elements "recipient", "objectNumber",
     *         "logEntry" and "errorLog". Will be empty if there are no records to insert.
     */
    private function processRealtyRecordInsertion($currentZip)
    {
        $emailData = [];
        $savedRealtyObjects = new \Tx_Oelib_List();
        $offererId = '';
        $transferMode = null;

        $xml = $this->getImportedXml();
        if ($xml !== null) {
            $offererXpath = new \DOMXPath($xml);
            $offererNodes = $offererXpath->query('//openimmo/anbieter/openimmo_anid');
            if ($offererNodes->length > 0) {
                $offererNode = $offererNodes->item(0);
                $offererId = (string)$offererNode->nodeValue;
            }

            $transferXpath = new \DOMXPath($xml);
            $transferNodes = $transferXpath->query('//openimmo/uebertragung');
            if ($transferNodes->length > 0) {
                $transferNode = $transferNodes->item(0);
                $transferMode = $transferNode->getAttribute('umfang');
            }
        }

        $recordsToInsert = $this->convertDomDocumentToArray($xml);

        if (empty($recordsToInsert)) {
            // Ensures that the foreach-loop is passed at least once, so the log gets processed correctly.
            $recordsToInsert = [[]];
        } else {
            $this->copyImagesAndDocumentsFromExtractedZip($currentZip, $recordsToInsert);
            // Only ZIP archives that have a valid owner and therefore can be
            // imported are marked as deletable.
            // The owner is the same for each record within one ZIP archive.
            $this->loadRealtyObject($recordsToInsert[0]);
            if ($this->hasValidOwnerForImport()) {
                $this->filesToDelete[] = $currentZip;
            }
        }

        foreach ($recordsToInsert as $record) {
            $this->writeToDatabase($record);

            if (!$this->realtyObject->isDead()) {
                $savedRealtyObjects->add($this->realtyObject);
                $emailData[] = $this->createEmailRawDataArray(
                    $this->getContactEmailFromRealtyObject(),
                    $this->getObjectNumberFromRealtyObject()
                );
            }
            $this->storeLogsAndClearTemporaryLog();
        }

        if ($transferMode === self::FULL_TRANSFER_MODE
            && $this->globalConfiguration->getAsBoolean('importCanDeleteRecordsForFullSync')
        ) {
            $pid = \Tx_Oelib_ConfigurationProxy::getInstance('realty')->getAsInteger('pidForRealtyObjectsAndImages');
            /** @var \tx_realty_Mapper_RealtyObject $realtyObjectMapper */
            $realtyObjectMapper = \Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_RealtyObject::class);
            $deletedObjects = $realtyObjectMapper->deleteByAnidAndPidWithExceptions(
                $offererId,
                $pid,
                $savedRealtyObjects
            );
            if (!empty($deletedObjects)) {
                /** @var string[] $uids */
                $uids = [];
                foreach ($deletedObjects as $deletedObject) {
                    $uids[] = $deletedObject->getUid();
                }
                $this->addToLogEntry(
                    $this->getTranslator()->translate('message_deleted_objects_from_full_sync') . ' ' .
                    implode(', ', $uids)
                );
            }
        }

        if (!$this->deleteCurrentZipFile) {
            $this->filesToDelete = array_diff($this->filesToDelete, [$currentZip]);
            $this->deleteCurrentZipFile = true;
        }

        return $emailData;
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
    protected function writeToDatabase(array $realtyRecord)
    {
        $this->loadRealtyObject($realtyRecord);
        $this->ensureContactEmail();

        if (!$this->hasValidOwnerForImport()) {
            $this->addToErrorLog(
                $this->getTranslator()->translate('message_openimmo_anid_not_matches_allowed_fe_user') . ' "'
                . $this->realtyObject->getProperty('openimmo_anid') . '".' . LF
            );
            return;
        }

        // 'TRUE' allows to add an owner to the realty record if it hasn't got one.
        $errorMessage = $this->realtyObject->writeToDatabase(0, true);

        switch ($errorMessage) {
            case '':
                $this->addToLogEntry($this->getTranslator()->translate('message_written_to_database') . LF);
                break;
            case 'message_deleted_flag_set':
                // The fall-through is intended.
            case 'message_deleted_flag_causes_deletion':
                // A set deleted flag is no real error, so is not stored in the error log.
                $this->addToLogEntry($this->getTranslator()->translate($errorMessage) . LF);
                break;
            case 'message_fields_required':
                $this->addToErrorLog(
                    $this->getTranslator()->translate($errorMessage) . ': ' .
                    implode(', ', $this->realtyObject->checkForRequiredFields()) .
                    '. ' . $this->getPleaseActivateValidationMessage() . LF
                );
                break;
            case 'message_object_limit_reached':
                $this->deleteCurrentZipFile = false;
                $owner = $this->realtyObject->getOwner();
                $this->addToErrorLog(
                    sprintf(
                        $this->getTranslator()->translate($errorMessage),
                        $owner->getName(),
                        $owner->getUid(),
                        $owner->getTotalNumberOfAllowedObjects()
                    ) . LF
                );
                break;
            default:
                $this->addToErrorLog(
                    $this->getTranslator()->translate($errorMessage) . ' ' .
                    $this->getPleaseActivateValidationMessage() . LF
                );
        }
    }

    /**
     * Returns a localized message that validation should be activated. Will be
     * empty if validation is active.
     *
     * @return string localized message that validation should be enabled,
     *                an empty string if this is already enabled
     */
    private function getPleaseActivateValidationMessage()
    {
        return ($this->globalConfiguration->getAsString('openImmoSchema') !== '')
            ? $this->getTranslator()->translate('message_please_validate')
            : '';
    }

    /**
     * Checks whether the current realty object's supposed owner is in an
     * allowed FE user group.
     * Returns TRUE if this check is disabled by configuration.
     *
     * @return bool TRUE if the current realty object's owner matches
     *                 an allowed FE user, also TRUE if this check is
     *                 disabled by configuration, FALSE otherwise
     */
    private function hasValidOwnerForImport()
    {
        if (!$this->globalConfiguration->getAsBoolean('onlyImportForRegisteredFrontEndUsers')) {
            return true;
        }

        try {
            $this->realtyObject->getOwner();
        } catch (Tx_Oelib_Exception_NotFound $exception) {
            return false;
        }

        $allowedFrontEndUserGroups = $this->globalConfiguration->getAsString('allowedFrontEndUserGroups');

        // An empty string is interpreted as any FE user group being allowed.
        $result = ($allowedFrontEndUserGroups === '')
            || $this->realtyObject->getOwner()->hasGroupMembership($allowedFrontEndUserGroups);

        return $result;
    }

    /**
     * Logs information about the proceedings of the import.
     * This function is to be used for positive information only. Errors should
     * get logged through 'addToErrorLog()' instead.
     *
     * @param string $message message to log, may be empty
     *
     * @return void
     */
    private function addToLogEntry($message)
    {
        $this->temporaryLogEntry .= $message . LF;
    }

    /**
     * Logs errors to a special error log and also provides 'addToLogEntry()'
     * with the given string.
     *
     * @param string $errorMessage error message to log, may be empty
     *
     * @return void
     */
    private function addToErrorLog($errorMessage)
    {
        $this->success = false;
        $this->temporaryErrorLog .= $errorMessage . LF;
        $this->addToLogEntry($errorMessage);
    }

    /**
     * Stores available log messages to be returned at the end of the import.
     * This function is needed to use only parts of the log.
     *
     * @return void
     */
    private function storeLogsAndClearTemporaryLog()
    {
        $this->errors .= $this->temporaryErrorLog;
        $this->temporaryErrorLog = '';

        $this->logEntry .= $this->temporaryLogEntry;
        $this->temporaryLogEntry = '';
    }

    /**
     * Checks whether the import may start. Will return TRUE if the class for
     * ZIP extraction is available and if the import directory is writable.
     * Otherwise, the result will be FALSE and the reason will be logged.
     *
     * @param string $importDirectory unified path of the import directory, must not be empty
     *
     * @return bool TRUE if the requirements to start the import are fulfilled, FALSE otherwise
     */
    private function canStartImport($importDirectory)
    {
        $result = true;

        if (!in_array('zip', get_loaded_extensions(), true)) {
            $this->addToErrorLog($this->getTranslator()->translate('message_zip_archive_not_installed'));
            $result = false;
        }

        return $result && $this->isImportDirectoryAccessible($importDirectory) && $this->isUploadDirectoryAccessible();
    }

    /**
     * Checks that the import directory exists and is readable and writable.
     *
     * @param string $importDirectory unified path of the import directory, must not be empty
     *
     * @return bool TRUE if the import directory exists and is readable and
     *                 writable, FALSE otherwise
     */
    private function isImportDirectoryAccessible($importDirectory)
    {
        $isAccessible = false;

        if (!@is_dir($importDirectory)) {
            $this->addToErrorLog(
                sprintf(
                    $this->getTranslator()->translate('message_import_directory_not_existing'),
                    $importDirectory,
                    get_current_user()
                )
            );
        } elseif (!@is_readable($importDirectory)) {
            $this->addFolderAccessErrorMessage('message_import_directory_not_readable', $importDirectory);
        } elseif (@is_writable($importDirectory)) {
            $isAccessible = true;
        } else {
            $this->addFolderAccessErrorMessage('message_import_directory_not_writable', $importDirectory);
        }

        return $isAccessible;
    }

    /**
     * Checks that the realty upload path exists and is writable.
     *
     * @return bool TRUE if the realty upload path exists and is writable,
     *                 FALSE otherwise
     */
    private function isUploadDirectoryAccessible()
    {
        $isAccessible = false;

        if (!@is_dir($this->uploadDirectory)) {
            $this->addToErrorLog(
                sprintf(
                    $this->getTranslator()->translate('message_upload_directory_not_existing'),
                    $this->uploadDirectory
                )
            );
        } elseif (@is_writable($this->uploadDirectory)) {
            $isAccessible = true;
        } else {
            $this->addFolderAccessErrorMessage('message_upload_directory_not_writable', $this->uploadDirectory);
        }

        return $isAccessible;
    }

    /**
     * Adds the given error message to the error log.
     *
     * @param string $message locallang label for the error message to add to the log, must not be empty
     * @param string $path the path to be displayed in the error message, must not be empty
     *
     * @return void
     */
    private function addFolderAccessErrorMessage($message, $path)
    {
        $ownerUid = fileowner($path);
        if (function_exists('posix_getpwuid')) {
            $ownerName = posix_getpwuid($ownerUid) . ', ' . $ownerUid;
        } else {
            $ownerName = $ownerUid;
        }

        $this->addToErrorLog(
            sprintf(
                $this->getTranslator()->translate($message),
                $path,
                $ownerName,
                substr(decoct(fileperms($path)), 2),
                get_current_user()
            )
        );
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
    protected function setUploadDirectory($path)
    {
        $this->uploadDirectory = $this->unifyPath($path);
        $this->fileNameMapper->setDestinationFolder($this->uploadDirectory);
    }

    /**
     * Checks if the configuration in the EM enables sending errors only.
     *
     * @return bool TRUE if 'onlyErrors' is enabled, FALSE otherwise
     */
    private function isErrorLogOnlyEnabled()
    {
        return $this->globalConfiguration->getAsBoolean('onlyErrors');
    }

    /**
     * Returns the default e-mail address, configured in the EM.
     *
     * @return string default e-mail address, may be empty
     */
    private function getDefaultEmailAddress()
    {
        return $this->globalConfiguration->getAsString('emailAddress');
    }

    /**
     * Checks whether contact persons of each record should receive e-mails
     * about the import of their records.
     *
     * @return bool TRUE if contact persons should receive e-mails,
     *                 FALSE otherwise
     */
    private function isNotifyContactPersonsEnabled()
    {
        return $this->globalConfiguration->getAsBoolean('notifyContactPersons');
    }

    /**
     * Stores all information for an e-mail to an array with the keys
     * 'recipient', 'objectNumber', 'logEntry' and 'errorLog'.
     *
     * @param string $email e-mail address, may be empty
     * @param string $objectNumber object number, may be empty
     *
     * @return string[] e-mail raw data, contains the elements 'recipient',
     *               'objectNumber', 'logEntry' and 'errorLog', will not
     *               be empty
     */
    private function createEmailRawDataArray($email, $objectNumber)
    {
        return [
            'recipient' => $email,
            'objectNumber' => $objectNumber,
            'logEntry' => $this->temporaryLogEntry,
            'errorLog' => $this->temporaryErrorLog,
        ];
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
     * @return array[] Three -dimensional array with e-mail addresses as
     *               keys of the outer array. Innermost there is an array
     *               with only one element: Object number as key and the
     *               corresponding log information as value. This array
     *               is wrapped by a numeric array as object numbers are
     *               not necessarily unique. Empty if the input array is
     *               empty or invalid.
     */
    protected function prepareEmails(array $emailData)
    {
        if (!$this->validateEmailDataArray($emailData)) {
            return [];
        }

        $result = [];
        $emailDataToPrepare = $emailData;
        if ($this->isErrorLogOnlyEnabled()) {
            $log = 'errorLog';
        } else {
            $log = 'logEntry';
        }

        foreach ($emailDataToPrepare as $record) {
            if ((string)($record['recipient'] === '') || !$this->isNotifyContactPersonsEnabled()) {
                $record['recipient'] = $this->getDefaultEmailAddress();
            }

            if ((string)$record['objectNumber'] === '') {
                $record['objectNumber'] = '------';
            }

            $result[$record['recipient']][] = [
                $record['objectNumber'] => $record[$log],
            ];
        }

        $this->purgeRecordsWithoutLogMessages($result);
        $this->purgeRecipientsForEmptyMessages($result);

        return $result;
    }

    /**
     * Validates an e-mail data array which is used to prepare e-mails. Returns
     * TRUE if the structure is correct, FALSE otherwise.
     * The structure is correct, if there are arrays as values for each numeric
     * key and if those arrays contain the elements 'recipient', 'objectNumber',
     * 'logEntry' and 'errorLog' as keys.
     *
     * @param array[] $emailData
     *        e-mail data array to validate with arrays as values for each numeric key and if those arrays contain the
     *        elements 'recipient', 'objectNumber', 'logEntry' and 'errorLog' as keys, may be empty
     *
     * @return bool TRUE if the structure of the array is valid, FALSE
     *                 otherwise
     */
    private function validateEmailDataArray(array $emailData)
    {
        $isValidDataArray = true;
        $requiredKeys = [
            'recipient',
            'objectNumber',
            'logEntry',
            'errorLog',
        ];

        foreach ($emailData as $dataArray) {
            if (!is_array($dataArray)) {
                $isValidDataArray = false;
                break;
            }
            $numberOfValidArrays = count(array_intersect(array_keys($dataArray), $requiredKeys));

            if ($numberOfValidArrays !== 4) {
                $isValidDataArray = false;
                break;
            }
        }

        return $isValidDataArray;
    }

    /**
     * Deletes object numbers from $emailData if there is no message to report.
     * Messages could only be empty if 'onlyErrors' is activated in the EM
     * configuration.
     *
     * @param array[] &$emailData prepared e-mail data, must not be empty
     *
     * @return void
     */
    private function purgeRecordsWithoutLogMessages(array &$emailData)
    {
        foreach ($emailData as $recipient => $data) {
            foreach ($data as $key => $emailContent) {
                if (implode('', $emailContent) === '') {
                    unset($emailData[$recipient][$key]);
                }
            }
        }
    }

    /**
     * Deletes e-mail recipients from a $emailData if are no records to report
     * about.
     *
     * @param array[] &$emailData prepared e-mail data, must not be empty
     *
     * @return void
     */
    private function purgeRecipientsForEmptyMessages(array &$emailData)
    {
        foreach ($emailData as $recipient => $data) {
            if (empty($data)) {
                unset($emailData[$recipient]);
            }
        }
    }

    /**
     * Fills a template file, which has already been included, with data for one
     * e-mail.
     *
     * @param array[] $recordsForOneEmail
     *        Wrapped message content for one e-mail: Each object number-message pair is wrapped by a numeric key as
     *     object numbers are not necessarily unique. Must not be empty.
     *
     * @return string e-mail body
     */
    private function fillEmailTemplate($recordsForOneEmail)
    {
        /** @var $template \Tx_Oelib_TemplateHelper */
        $template = GeneralUtility::makeInstance(\Tx_Oelib_TemplateHelper::class);
        $template->init(['templateFile' => $this->globalConfiguration->getAsString('emailTemplate')]);
        $template->getTemplateCode();
        $contentItem = [];

        // collects data for the subpart 'CONTENT_ITEM'
        $template->setMarker('label_object_number', $this->getTranslator()->translate('label_object_number'));
        foreach ($recordsForOneEmail as $record) {
            // $record is an array of the object number associated with the log
            $template->setMarker('object_number', key($record));
            $template->setMarker('log', implode('', $record));
            $contentItem[] = $template->getSubpart('CONTENT_ITEM');
        }

        // fills the subpart 'EMAIL_BODY'
        $template->setMarker('header', $this->getTranslator()->translate('message_introduction'));
        $template->setSubpart('CONTENT_ITEM', implode(LF, $contentItem));
        $template->setMarker('footer', $this->getTranslator()->translate('message_explanation'));

        return $template->getSubpart('EMAIL_BODY');
    }

    /**
     * Sends an e-mail with log information to each address given as a key of
     * $addressesAndMessages.
     * If there is no default address configured in the EM, no messages will be
     * sent at all.
     *
     * @param array[] $addressesAndMessages
     *        Three-dimensional array with e-mail addresses as keys of the outer array. Innermost there is an array
     *     with only one element: Object number as key and the corresponding log information as value. This array is
     *     wrapped by a numeric array as object numbers are not necessarily unique. Must not be empty.
     *
     * @return void
     */
    private function sendEmails(array $addressesAndMessages)
    {
        /** @var SystemEmailFromBuilder $emailRoleBuilder */
        $emailRoleBuilder = GeneralUtility::makeInstance(SystemEmailFromBuilder::class);
        if ($this->getDefaultEmailAddress() === '' || !$emailRoleBuilder->canBuild()) {
            return;
        }

        $fromRole = $emailRoleBuilder->build();
        foreach ($addressesAndMessages as $address => $content) {
            /** @var MailMessage $email */
            $email = GeneralUtility::makeInstance(MailMessage::class);
            $email->setFrom([$fromRole->getEmailAddress() => $fromRole->getName()]);
            $email->setTo([$address => '']);
            $email->setSubject($this->getTranslator()->translate('label_subject_openImmo_import'));
            $email->setBody($this->fillEmailTemplate($content));
            $email->send();
        }

        if (!empty($addressesAndMessages)) {
            $this->addToLogEntry(
                $this->getTranslator()->translate('message_log_sent_to') . ': ' . implode(
                    ', ',
                    array_keys($addressesAndMessages)
                )
            );
        }
    }

    /**
     * Ensures a contact e-mail address for the current realty record. Checks
     * whether there is a valid contact e-mail for the current record. Inserts
     * the default address configured in EM if 'contact_email' if the current
     * record's contact e-mail is empty or invalid.
     *
     * @return void
     */
    protected function ensureContactEmail()
    {
        $address = $this->getContactEmailFromRealtyObject();
        $isValid = ($address !== '') && GeneralUtility::validEmail($address);

        if (!$isValid) {
            $this->setContactEmailOfRealtyObject($this->getDefaultEmailAddress());
        }
    }

    /**
     * Checks the correct punctuation of a path to a directory. Adds a slash if
     * missing and strips whitespaces.
     *
     * @param string $directory path to be checked, must not be empty
     *
     * @return string checked path, possibly modified
     */
    protected function unifyPath($directory)
    {
        $checkedPath = trim($directory);
        $pathWithoutTrailingSlash = rtrim($checkedPath, '/');

        return $pathWithoutTrailingSlash . '/';
    }

    /**
     * Gets an array of the paths of all ZIP archives in the import folder
     * and its subfolders.
     *
     * @param string $importDirectory absolute path of the directory which contains the ZIPs, must not be empty
     *
     * @return string[] absolute paths of ZIPs in the import folder, might be empty
     */
    protected function getPathsOfZipsToExtract($importDirectory)
    {
        $result = [];

        if (is_dir($importDirectory)) {
            $result = GeneralUtility::getAllFilesAndFoldersInPath([], $importDirectory, 'zip');
        }

        return $result;
    }

    /**
     * Extracts each ZIP archive into a directory in the import folder which is
     * named like the ZIP archive without the suffix '.zip'.
     * Logs success and failures.
     *
     * @param string $zipToExtract path to the ZIP archive to extract, must not be empty
     *
     * @return void
     */
    public function extractZip($zipToExtract)
    {
        if (!file_exists($zipToExtract)) {
            return;
        }

        $zip = new ZipArchive();
        if ($zip->open($zipToExtract)) {
            $extractionDirectory = $this->createExtractionFolder($zipToExtract);
            if ($extractionDirectory !== '') {
                $zip->extractTo($extractionDirectory);
                $this->addToLogEntry($zipToExtract . ': ' . $this->getTranslator()
                        ->translate('message_extracted_successfully'));
            }
            $zip->close();
        } else {
            $this->addToErrorLog($zipToExtract . ': ' . $this->getTranslator()->translate('message_extraction_failed'));
        }
    }

    /**
     * Gets a name for a folder according to the ZIP archive to extract to it.
     *
     * @param string $pathOfZip path of a ZIP archive, must not be empty
     *
     * @return string path for a folder named like the ZIP archive, empty if the passed string is empty
     */
    protected function getNameForExtractionFolder($pathOfZip)
    {
        return str_replace('.zip', '/', $pathOfZip);
    }

    /**
     * Creates a folder to extract a ZIP archive to.
     *
     * @param string $pathOfZip path of a ZIP archive to get the folders name, must not be empty
     *
     * @return string path for folder named like the ZIP archive without
     *                the suffix '.zip', may be empty if the provided ZIP
     *                file does not exists or if the folder to create
     *                already exists
     */
    public function createExtractionFolder($pathOfZip)
    {
        if (!file_exists($pathOfZip)) {
            return '';
        }

        $folderForZipExtraction = $this->getNameForExtractionFolder($pathOfZip);
        if (is_dir($folderForZipExtraction)) {
            $this->addToErrorLog($folderForZipExtraction . ': ' . $this->getTranslator()
                    ->translate('message_surplus_folder'));
            $folderForZipExtraction = '';
        } elseif (GeneralUtility::mkdir($folderForZipExtraction)) {
            $this->filesToDelete[] = $folderForZipExtraction;
            if (!is_writable($folderForZipExtraction)) {
                $this->addToErrorLog(
                    sprintf(
                        $this->getTranslator()->translate('message_folder_not_writable'),
                        $folderForZipExtraction
                    )
                );
            }
        } else {
            $this->addToErrorLog(
                sprintf(
                    $this->getTranslator()->translate('message_folder_creation_failed'),
                    $folderForZipExtraction
                )
            );
        }

        return $folderForZipExtraction;
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
    protected function getPathForXml($pathOfZip)
    {
        $result = '';

        $errorMessage = '';
        $folderWithXml = $this->getNameForExtractionFolder($pathOfZip);

        if (is_dir($folderWithXml)) {
            $pathOfXml = glob($folderWithXml . '*.xml');

            if (count($pathOfXml) === 1) {
                $result = implode('', $pathOfXml);
            } elseif (count($pathOfXml) > 1) {
                $errorMessage = 'message_too_many_xml';
            } else {
                $errorMessage = 'message_no_xml';
            }
        } else {
            $errorMessage = 'message_invalid_xml_path';
        }

        if ($errorMessage !== '') {
            $this->addToErrorLog(basename($pathOfZip) . ': ' . $this->getTranslator()->translate($errorMessage));
        }

        return $result;
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
    protected function loadXmlFile($pathOfZip)
    {
        $xmlPath = $this->getPathForXml($pathOfZip);
        if ($xmlPath === '') {
            return;
        }

        $this->importedXml = new DOMDocument();
        $this->importedXml->load($xmlPath);
        $this->validateXml();
    }

    /**
     * Returns the current content of the currently loaded XML file as a
     * DOMDocument.
     *
     * @return DOMDocument loaded XML file, may be NULL if no document was
     *                     loaded e.g. due to validation errors
     */
    protected function getImportedXml()
    {
        return $this->importedXml;
    }

    /**
     * Validates an XML file and writes the validation result to the log.
     * The XML file must have been loaded before. The schema to validate
     * against is taken from the path in '$this- >schemaFile'. If this path is
     * empty or invalid, validation is considered to be successful and the
     * absence of a schema file is logged.
     *
     * @return void
     */
    private function validateXml()
    {
        $validationResult = '';
        $schemaFile = $this->globalConfiguration->getAsString('openImmoSchema');

        if ($schemaFile === '') {
            $validationResult = 'message_no_schema_file';
        } elseif (!file_exists($schemaFile)) {
            $validationResult = 'message_invalid_schema_file_path';
        } elseif (!$this->getImportedXml()) {
            $validationResult = 'message_validation_impossible';
        } elseif (!$this->importedXml->schemaValidate($schemaFile)) {
            $errors = libxml_get_errors();
            /** @var LibXMLError $error */
            foreach ($errors as $error) {
                $validationResult .= $this->getTranslator()->translate('message_line') .
                    ' ' . $error->line . ': ' . $error->message;
            }
        }

        $this->logValidationResult($validationResult);
    }

    /**
     * Logs the validation result of the XML file.
     *
     * @param string $validationResult
     *        result of the validation, can be either one of the locallang keys 'message_no_schema_file',
     *        'message_invalid_schema_file_path' or 'message_validation_impossible' or an already localized error
     *     message or an empty string if success should be logged
     *
     * @return void
     */
    private function logValidationResult($validationResult)
    {
        switch ($validationResult) {
            case '':
                $this->addToLogEntry($this->getTranslator()->translate('message_successful_validation') . LF);
                break;
            case 'message_no_schema_file':
                $this->addToLogEntry(
                    $this->getTranslator()->translate($validationResult) . ' ' .
                    $this->getTranslator()->translate('message_import_without_validation')
                );
                break;
            case 'message_invalid_schema_file_path':
                $this->addToLogEntry(
                    $this->getTranslator()->translate($validationResult) . ' ' .
                    $this->getTranslator()->translate('message_import_without_validation')
                );
                break;
            case 'message_validation_impossible':
                $this->addToErrorLog($this->getTranslator()->translate($validationResult));
                break;
            default:
                $this->addToErrorLog($validationResult);
        }
    }

    /**
     * Copies images and documents for OpenImmo records to the local upload
     * folder.
     *
     * @param string $pathOfZip
     *        path of the extracted ZIP archive, must not be empty
     * @param array[] $realtyRecords
     *        realty record data derived from the XML file, must not be empty
     *
     * @return void
     */
    public function copyImagesAndDocumentsFromExtractedZip($pathOfZip, array $realtyRecords)
    {
        $folderWithImages = $this->getNameForExtractionFolder($pathOfZip);
        $imagesNotToCopy = $this->findFileNamesOfDeletedRecords($realtyRecords);

        /** @var string[] $lowercaseFileExtensions */
        $lowercaseFileExtensions = GeneralUtility::trimExplode(
            ',',
            $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'],
            true
        );
        if (!in_array('pdf', $lowercaseFileExtensions, true)) {
            $lowercaseFileExtensions[] = 'pdf';
        }
        if (in_array('ps', $lowercaseFileExtensions, true)) {
            unset($lowercaseFileExtensions[(int)array_search('ps', $lowercaseFileExtensions, true)]);
        }

        $allCaseFileExtensions = $lowercaseFileExtensions;
        foreach ($lowercaseFileExtensions as $extension) {
            $allCaseFileExtensions[] = strtoupper($extension);
        }

        foreach ($allCaseFileExtensions as $extension) {
            $files = glob($folderWithImages . '*.' . $extension);
            if (!is_array($files)) {
                continue;
            }

            /** @var string $file */
            foreach ($files as $file) {
                $uniqueFileNames = $this->fileNameMapper->releaseMappedFileNames(basename($file));

                foreach ($uniqueFileNames as $uniqueName) {
                    if (!in_array($uniqueName, $imagesNotToCopy, true)) {
                        copy($file, $this->uploadDirectory . $uniqueName);
                    }
                }
            }
        }
    }

    /**
     * Finds file names of images and documents which must not be copied into
     * the uploads folder because their corresponding realty records are marked
     * as deleted.
     *
     * @param array[] $records realty records, must not be empty
     *
     * @return string[] names files which must not be copied
     */
    private function findFileNamesOfDeletedRecords(array $records)
    {
        $filesNotToCopy = [];

        foreach ($records as $record) {
            if ($record['deleted'] && is_array($record['images'])) {
                foreach ($record['images'] as $image) {
                    $filesNotToCopy[] = $image['image'];
                }
                if (isset($record['documents']) && is_array($record['documents'])) {
                    /** @var string[] $document */
                    foreach ($record['documents'] as $document) {
                        $filesNotToCopy[] = $document['filename'];
                    }
                }
            }
        }

        return $filesNotToCopy;
    }

    /**
     * Removes the ZIP archives which have been imported and the folders which
     * have been created to extract the ZIP archives.
     * Logs which ZIP archives have been deleted.
     *
     * @param string $importDirectory absolute path of the folder which contains the ZIP archives, must not be empty
     *
     * @return void
     */
    public function cleanUp($importDirectory)
    {
        if (!is_dir($importDirectory)) {
            return;
        }

        $removedFiles = [];
        $deleteImportedZips = $this->globalConfiguration->getAsBoolean('deleteZipsAfterImport');

        foreach ($this->getPathsOfZipsToExtract($importDirectory) as $currentPath) {
            if ($deleteImportedZips) {
                $removedZipArchive = $this->deleteFile($currentPath);
                if ($removedZipArchive !== '') {
                    $removedFiles[] = $removedZipArchive;
                }
            }
            $this->deleteFile($this->getNameForExtractionFolder($currentPath));
        }

        if (!empty($removedFiles)) {
            $this->addToLogEntry(
                $this->getTranslator()->translate('message_files_removed') . ': ' . implode(', ', $removedFiles)
            );
        }
    }

    /**
     * Removes a file if it occurs in the list of files for which deletion is
     * allowed.
     *
     * @param string $pathOfFile path of the file to delete, must not be empty
     *
     * @return string basename of the deleted file or an empty string if
     *                no file was deleted
     */
    private function deleteFile($pathOfFile)
    {
        $removedFile = '';
        if (in_array($pathOfFile, $this->filesToDelete, true)) {
            GeneralUtility::rmdir($pathOfFile, true);
            $removedFile = basename($pathOfFile);
        }

        return $removedFile;
    }

    /**
     * Converts a DOMDocument to an array.
     *
     * @param DOMDocument|null $realtyRecords which contains realty records, can be NULL
     *
     * @return array[] $realtyRecords realty records in an array, will be empty if the data was not convertible
     */
    protected function convertDomDocumentToArray(DOMDocument $realtyRecords = null)
    {
        if ($realtyRecords === null) {
            return [];
        }

        /** @var \tx_realty_domDocumentConverter $domDocumentConverter */
        $domDocumentConverter = GeneralUtility::makeInstance(
            \tx_realty_domDocumentConverter::class,
            $this->fileNameMapper
        );

        return $domDocumentConverter->getConvertedData($realtyRecords);
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
    protected function loadRealtyObject($data)
    {
        $this->realtyObject = GeneralUtility::makeInstance(\tx_realty_Model_RealtyObject::class, $this->isTestMode);
        $this->realtyObject->loadRealtyObject($data, true);
    }

    /**
     * Returns the object number of a realty object if it is set.
     *
     * @return string object number, may be empty if no object number
     *                was set or if the realty object is not initialized
     */
    private function getObjectNumberFromRealtyObject()
    {
        if ($this->realtyObject === null || $this->realtyObject->isDead()) {
            return '';
        }

        return $this->realtyObject->getProperty('object_number');
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
    protected function getContactEmailFromRealtyObject()
    {
        if ($this->realtyObject === null || $this->realtyObject->isDead()) {
            return '';
        }

        $emailAddress = $this->realtyObject->getProperty('contact_email');

        if ($this->mayUseOwnerData()) {
            try {
                $emailAddress = $this->realtyObject->getOwner()->getEmailAddress();
            } catch (Tx_Oelib_Exception_NotFound $exception) {
            }
        }

        return $emailAddress;
    }

    /**
     * Checks whether the owner's data may be used.
     *
     * @return bool TRUE it is allowed by configuration to use the
     *                 owner's data, FALSE otherwise
     */
    private function mayUseOwnerData()
    {
        return $this->globalConfiguration->getAsBoolean('useFrontEndUserDataAsContactDataForImportedRecords');
    }

    /**
     * Sets the contact e-mail address of a realty object.
     *
     * @param string $address contact e-mail address, must not be empty
     *
     * @return void
     */
    private function setContactEmailOfRealtyObject($address)
    {
        if (!is_object($this->realtyObject)) {
            return;
        }

        $this->realtyObject->setProperty('contact_email', $address);
    }

    /**
     * Gets the required fields of a realty object.
     * This function is needed for unit testing only.
     *
     * @return string[] required fields, may be empty if no fields are
     *               required or if the realty object is not initialized
     */
    protected function getRequiredFields()
    {
        if (!is_object($this->realtyObject)) {
            return [];
        }

        return $this->realtyObject->getRequiredFields();
    }
}
