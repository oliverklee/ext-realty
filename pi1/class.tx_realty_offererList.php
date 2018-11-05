<?php

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * This class provides a list of offerers for the realty plugin.
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 */
class tx_realty_offererList extends tx_realty_pi1_FrontEndView
{
    /**
     * @var bool whether this class is instantiated for testing
     */
    private $isTestMode = false;

    /**
     * The constructor.
     *
     * @param array $configuration TypoScript configuration for the plugin
     * @param ContentObjectRenderer $contentObjectRenderer the parent cObj content, needed for the flexforms
     * @param bool $isTestMode TRUE if this class is instantiated for testing, else FALSE
     */
    public function __construct(
        array $configuration,
        ContentObjectRenderer $contentObjectRenderer,
        $isTestMode = false
    ) {
        $this->isTestMode = $isTestMode;
        parent::__construct($configuration, $contentObjectRenderer);
    }

    /**
     * Returns the offerer list in HTML.
     *
     * @param array $unused unused
     *
     * @return string HTML of the offerer list, will not be empty
     */
    public function render(array $unused = [])
    {
        $listItems = $this->getListItems();

        if ($listItems !== '') {
            $this->setSubpart('offerer_list_item', $listItems);
        } else {
            $this->setMarker('message_noResultsFound', $this->translate('message_noResultsFound_offererList'));
            $this->setSubpart('offerer_list_result', $this->getSubpart('EMPTY_RESULT_VIEW'));
        }

        return $this->getSubpart('OFFERER_LIST');
    }

    /**
     * Returns the HTML for one list item.
     *
     * @param int $offererUid UID of the FE user record for which to get the contact information, must be > 0
     *
     * @return string HTML for one contact data item, will be empty if
     *                $offererUid is not a UID of an enabled user
     */
    public function renderOneItem($offererUid)
    {
        return $this->listItemQuery('uid = ' . $offererUid);
    }

    /**
     * Returns the HTML for one list item.
     *
     * @param string[] $ownerData
     *        owner data array, the keys 'company', 'name', 'first_name', 'last_name', 'address', 'zip', 'city',
     *        'email', 'www' and 'telephone' will be used for the HTML
     *
     * @return string HTML for one contact data item, will be empty if
     *                $ownerData did not contain data to use
     */
    public function renderOneItemWithTheDataProvided(array $ownerData)
    {
        if (isset($ownerData['usergroup'])) {
            throw new BadMethodCallException(
                'To process user group information you need to use render() or renderOneItem().',
                1333036231
            );
        }

        /** @var \tx_realty_Model_FrontEndUser $frontEndUser */
        $frontEndUser = GeneralUtility::makeInstance(\tx_realty_Model_FrontEndUser::class);

        // setData() will not create the relations, but "usergroup" is expected
        // to hold a list instance.
        $dataToSet = $ownerData;
        $dataToSet['usergroup'] = GeneralUtility::makeInstance(Tx_Oelib_List::class);
        $frontEndUser->setData($dataToSet);

        return $this->createListRow($frontEndUser);
    }

    /**
     * Returns the HTML for the list items.
     *
     * @return string HTML for the list items, will be empty if there are
     *                no offerers
     */
    private function getListItems()
    {
        if ($this->hasConfValueString(
            'userGroupsForOffererList',
            's_offererInformation'
        )) {
            $userGroups = str_replace(
                ',',
                '|',
                $this->getConfValueString(
                    'userGroupsForOffererList',
                    's_offererInformation'
                )
            );
            $userGroupRestriction = 'usergroup ' .
                'REGEXP "(^|,)(' . $userGroups . ')(,|$)"';
        } else {
            $userGroupRestriction = '1=1';
        }

        return $this->listItemQuery($userGroupRestriction);
    }

    /**
     * Gets the offerer records in an array.
     *
     * @param string $whereClause WHERE clause for the query, must not be empty
     *
     * @return string HTML for each fetched offerer record, will be empty if
     *                none were found
     */
    private function listItemQuery($whereClause)
    {
        $listItems = '';

        if ($this->isLastNameAvailable()) {
            $fieldOrder = 'usergroup,city,company,last_name,name,username,image';
        } else {
            $fieldOrder = 'usergroup,city,company,name,username,image';
        }

        $offererRecords = Tx_Oelib_Db::selectMultiple(
            '*',
            'fe_users',
            $whereClause . Tx_Oelib_Db::enableFields('fe_users') .
            $this->getWhereClauseForTesting(),
            '',
            $fieldOrder
        );
        /** @var tx_realty_Mapper_FrontEndUser $mapper */
        $mapper = Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_FrontEndUser::class);
        $offererList = $mapper->getListOfModels($offererRecords);

        /** @var tx_realty_Model_FrontEndUser $offerer */
        foreach ($offererList as $offerer) {
            $listItems .= $this->createListRow($offerer);
        }

        return $listItems;
    }

    /**
     * Checks whether the field last_name exists in the fe_users table.
     *
     * @return bool TRUE if the field last_name exists, FALSE otherwise
     */
    private function isLastNameAvailable()
    {
        return Tx_Oelib_Db::tableHasColumn('fe_users', 'last_name');
    }

    /**
     * Returns a single table row for the offerer list.
     *
     * @param tx_realty_Model_FrontEndUser $offerer FE user for which to create the row
     *
     * @return string HTML for one list row, will be empty if there is no
     *                no content (or only the user group) for the row
     */
    private function createListRow(tx_realty_Model_FrontEndUser $offerer)
    {
        $rowHasContent = false;
        $this->resetSubpartsHiding();

        foreach ($this->getListRowContent($offerer) as $key => $value) {
            $this->setMarker(
                'emphasized_' . $key,
                (!$rowHasContent && $value !== '') ? 'emphasized' : ''
            );

            if (!in_array(
                $key,
                ['www', 'objects_by_owner_link', 'image']
            )
            ) {
                $value = htmlspecialchars($value);
            }

            if ($this->setOrDeleteMarkerIfNotEmpty($key, $value, '', 'wrapper')) {
                $rowHasContent = $key !== 'usergroup';
            } else {
                $this->hideSubparts($key, 'wrapper');
            }
        }

        // Apart from in the single view, the user group is appended to the
        // company (if displayed) or to else the offerer name.
        if ($this->getConfValueString('what_to_display') !== 'single_view') {
            $this->hideSubparts('usergroup', 'wrapper');
        }

        return $rowHasContent ? $this->getSubpart('OFFERER_LIST_ITEM') : '';
    }

    /**
     * Returns an array of data for a list row.
     *
     * @param tx_realty_Model_FrontEndUser $offerer offerer for which to create the row
     *
     * @return string[] associative array with the marker names as keys and the
     *               content to replace them with as values, will not be empty
     */
    private function getListRowContent(tx_realty_Model_FrontEndUser $offerer)
    {
        $result = [];

        $maximumRowContent = [
            'usergroup' => $this->getFirstUserGroup($offerer->getUserGroups()),
            'company' => $this->getCompany($offerer),
            'offerer_label' => $this->getOffererLabel($offerer),
            'street' => $offerer->getStreet(),
            'city' => $offerer->getZipAndCity(),
            'telephone' => $offerer->getPhoneNumber(),
            'email' => $offerer->getEmailAddress(),
            'objects_by_owner_link' => $this->getObjectsByOwnerUrl(
                $offerer->getUid()
            ),
            'www' => $this->cObj->typoLink(
                htmlspecialchars($offerer->getHomepage()),
                ['parameter' => $offerer->getHomepage()]
            ),
            'image' => $this->getImageMarkerContent($offerer),
        ];

        foreach ($maximumRowContent as $key => $value) {
            $result[$key] = $this->mayDisplayInformation($offerer, $key)
                ? trim($value) : '';
        }

        return $result;
    }

    /**
     * Checks wether an item of offerer information may be displayed.
     *
     * @param tx_realty_Model_FrontEndUser $offerer offerer
     * @param string $keyOfInformation key of the information for which to check visibility, must not be emtpy
     *
     * @return bool TRUE if it is configured to display the information of
     *                 the provided offerer, FALSE otherwise
     */
    private function mayDisplayInformation(tx_realty_Model_FrontEndUser $offerer, $keyOfInformation)
    {
        $configurationKey = 'displayedContactInformation';

        $specialGroups = $this->getConfValueString(
            'groupsWithSpeciallyDisplayedContactInformation',
            's_offererInformation'
        );

        if ($specialGroups !== '' && $offerer->hasGroupMembership($specialGroups)) {
            $configurationKey .= 'Special';
        }

        return in_array(
            $keyOfInformation,
            GeneralUtility::trimExplode(
                ',',
                $this->getConfValueString($configurationKey, 's_offererInformation'),
                true
            ),
            true
        );
    }

    /**
     * Returns a FE user's first name and last name if provided, else the name.
     * If none of these is provided, the user name will be returned.
     * FE user records are expected to have at least a user name.
     *
     * @param tx_realty_Model_FrontEndUser $offerer offerer of which to get the name
     *
     * @return string label for the owner with the first user group appended if
     *                no company will be displayed (which usually has the user
     *                group appended) and if the offerer list is not used in the
     *                single view, will be empty if no owner record was cached
     *                or if the cached record is an invalid FE user record
     *                without a user name
     */
    private function getOffererLabel(tx_realty_Model_FrontEndUser $offerer)
    {
        $result = $offerer->getName();

        if (!$offerer->hasCompany()
            || !$this->mayDisplayInformation($offerer, 'company')
        ) {
            $this->appendUserGroup($result, $offerer);
        }

        return trim($result);
    }

    /**
     * Returns the company with the user group appended.
     *
     * @param tx_realty_Model_FrontEndUser $offerer the offerer of which to get the company, must not be empty
     *
     * @return string the company with the user group appended if the offerer
     *                list is not used in the single view, will be empty if
     *                there is no company
     */
    private function getCompany(tx_realty_Model_FrontEndUser $offerer)
    {
        $result = $offerer->getCompany();
        $this->appendUserGroup($result, $offerer);

        return trim($result);
    }

    /**
     * Appends the user group if $information is non-empty and if the current
     * view is not single view and if the user group may be displayed and is
     * non-empty.
     *
     * @param string &$information information to which the user group should be appended, may be empty, will be
     *     modified
     * @param tx_realty_Model_FrontEndUser $offerer the offerer of which to append the user group
     *
     * @return void
     */
    private function appendUserGroup(
        &$information,
        tx_realty_Model_FrontEndUser $offerer
    ) {
        if ($information !== ''
            && ($this->getConfValueString('what_to_display') !== 'single_view')
            && $this->mayDisplayInformation($offerer, 'usergroup')
        ) {
            $information
                .= ' ' . $this->getFirstUserGroup($offerer->getUserGroups());
        }
    }

    /**
     * Returns the title of the first user group a user belongs to and which is
     * within the list of allowed user groups.
     *
     * @param Tx_Oelib_List $userGroups
     *        the offerer's user groups of which to get the first which is within the list of allowed user groups
     *
     * @return string title of the first allowed user group of the given
     *                FE user, will be empty if the user has no group
     */
    private function getFirstUserGroup(Tx_Oelib_List $userGroups)
    {
        $result = '';

        $allowedGroups = GeneralUtility::intExplode(
            ',',
            $this->getConfValueString('userGroupsForOffererList', 's_offererInformation'),
            true
        );

        /** @var Tx_Oelib_Model_FrontEndUserGroup $group */
        foreach ($userGroups as $group) {
            if (in_array($group->getUid(), $allowedGroups, true)) {
                $result = $group->getTitle();
                break;
            }
        }

        return ($result !== '') ? ' (' . $result . ')' : '';
    }

    /**
     * Returns the URL to the list of objects by the provided owner.
     *
     * @param int $ownerUid UID of the owner for which to create the URL, must be >= 0
     *
     * @return string URL to the objects-by-owner list, will be empty if the
     *                owner UID is zero
     */
    private function getObjectsByOwnerUrl($ownerUid)
    {
        // There might be no UID if the data to render as offerer information
        // was initially provided in an array.
        if ($ownerUid === 0) {
            return '';
        }

        return GeneralUtility::locationHeaderUrl($this->cObj->typoLink_URL(
            [
                'parameter' => $this->getConfValueInteger(
                    'objectsByOwnerPID',
                    's_offererInformation'
                ),
                'additionalParams' => GeneralUtility::implodeArrayForUrl(
                    $this->prefixId,
                    ['owner' => $ownerUid]
                ),
                'useCacheHash' => true,
            ]
        ));
    }

    /**
     * Returns a WHERE clause part for the test mode. So only dummy records will
     * be retrieved for testing.
     *
     * @return string WHERE clause part for testing starting with ' AND'
     *                if the test mode is enabled, an empty string otherwise
     */
    private function getWhereClauseForTesting()
    {
        return $this->isTestMode ? ' AND Tx_Oelib_is_dummy_record=1' : '';
    }

    /**
     * Returns the image tag for the offerer image with the image resized to the
     * maximum width and height as configured in TS Setup.
     *
     * @param tx_realty_Model_FrontEndUser $offerer the offerer to show the image for
     *
     * @return string the image tag with the image, will be empty if user has no
     *                image
     */
    private function getImageMarkerContent(tx_realty_Model_FrontEndUser $offerer)
    {
        if (!$offerer->hasImage()) {
            return '';
        }

        $configuredUploadFolder =
            Tx_Oelib_ConfigurationProxy::getInstance('sr_feuser_register')->getAsString('uploadFolder');

        $uploadFolder = $configuredUploadFolder === '' ? 'uploads/tx_srfeuserregister' : $configuredUploadFolder;

        if (substr($uploadFolder, -1) !== '/') {
            $uploadFolder .= '/';
        }

        $imageConfiguration = [
            'altText' => '',
            'titleText' => $offerer->getName(),
            'file' => $uploadFolder . $offerer->getImage(),
            'file.' => [
                'maxW' => $this->getConfValueInteger('offererImageMaxWidth'),
                'maxH' => $this->getConfValueInteger('offererImageMaxHeight'),
            ],
        ];

        return $this->cObj->cObjGetSingle('IMAGE', $imageConfiguration);
    }
}
