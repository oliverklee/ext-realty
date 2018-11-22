<?php

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This class checks the Realty Manager configuration for basic sanity.
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class tx_realty_configcheck extends \Tx_Oelib_ConfigCheck
{
    /**
     * Checks the configuration for the filter form of the Realty Manager.
     *
     * @return void
     */
    public function check_tx_realty_pi1_filter_form()
    {
        $this->checkCommonFrontEndSettings();
        $this->checkDisplayedSearchWidgetFields();
        $this->checkFilterTargetPid();
        $this->checkPriceRangesForFilterForm();
    }

    /**
     * Checks the configuration for the list view of the Realty Manager.
     *
     * @return void
     */
    public function check_tx_realty_pi1_realty_list()
    {
        $this->checkListViewRelatedConfiguration();
        $this->checkGoogleMaps();
        $this->checkFavoritesPid();
    }

    /**
     * Checks all list view related configuration of the Realty Manager.
     *
     * @return void
     */
    public function checkListViewRelatedConfiguration()
    {
        $this->checkCommonFrontEndSettings();
        $this->checkCheckboxesFilter();
        $this->checkImageSizeValuesForListView();
        $this->checkPagesToDisplay();
        $this->checkRecursive();
        $this->checkOrderBy();
        $this->checkSortCriteria();
        $this->checkCurrencyUnit();
        $this->checkSingleViewPid();
        $this->checkEnableNextPreviousButtons();
        $this->checkPriceOnlyIfAvailable();
    }

    /**
     * Checks the configuration for the favorites view of the Realty Manager.
     *
     * @return void
     */
    public function check_tx_realty_pi1_favorites()
    {
        $this->check_tx_realty_pi1_realty_list();
        $this->checkFavoriteFieldsInSession();
        $this->checkImageSizeValuesForListView();
        $this->checkShowContactPageLink();
        if ($this->objectToCheck->getConfValueBoolean('showContactPageLink')) {
            $this->checkContactPid();
        }
    }

    /**
     * Checks the configuration for the single view of the Realty Manager.
     *
     * @return void
     */
    public function check_tx_realty_pi1_single_view()
    {
        $this->checkCommonFrontEndSettings();
        $this->checkSingleViewPartsToDisplay();
        $this->checkCurrencyUnit();
        $this->checkRequireLoginForSingleViewPage();
        if ($this->objectToCheck->getConfValueBoolean(
            'requireLoginForSingleViewPage',
            's_template_special'
        )) {
            $this->checkLoginPid();
        }
        if ($this->isSingleViewPartToDisplay('imageThumbnails')) {
            $this->checkImageSizeValuesForSingleView();
            $this->checkLightboxImageConfiguration();
        }
        if ($this->isSingleViewPartToDisplay('contactButton')) {
            $this->checkContactPid();
        }
        if ($this->isSingleViewPartToDisplay('overviewTable')) {
            $this->checkFieldsInSingleViewTable();
        }
        if ($this->isSingleViewPartToDisplay('actionButtons')) {
            $this->checkFavoritesPid();
        }
        $this->checkGoogleMaps();
        $this->checkObjectsByOwnerPid();
        $this->checkUserGroupsForOffererList();
        $this->checkDisplayedContactInformation();
        $this->checkDisplayedContactInformationSpecial();
        $this->checkGroupsWithSpeciallyDisplayedContactInformation();
        $this->checkOffererImageConfiguration();
        $this->checkEnableNextPreviousButtonsForSingleView();
        $this->checkPriceOnlyIfAvailable();
    }

    /**
     * Checks the configuration for the contact form of the Realty Manager.
     *
     * @return void
     */
    public function check_tx_realty_pi1_contact_form()
    {
        $this->checkCommonFrontEndSettings();
        $this->checkIsValidDefaultFromEmailAddress();
        $this->checkDefaultContactEmail();
        $this->checkBlindCarbonCopyAddress();
        $this->checkVisibleContactFormFields();
        $this->checkRequiredContactFormFields();
        if ($this->hasTermsInContactForm()) {
            $this->checkTermsPid();
        }
    }

    /**
     * Checks the configuration for the my objects view of the Realty Manager.
     *
     * @return void
     */
    public function check_tx_realty_pi1_my_objects()
    {
        $this->checkListViewRelatedConfiguration();
        $this->checkEditorPid();
        $this->checkLoginPid();
        $this->checkImageUploadPid();
        $this->checkAdvertisementPid();
        if ($this->objectToCheck->hasConfValueInteger(
            'advertisementPID',
            's_advertisements'
        )) {
            $this->checkAdvertisementParameterForObjectUid();
            $this->checkAdvertisementExpirationInDays();
        }
    }

    /**
     * Checks the configuration for the Realty Manager's list view of objects by
     * a certain owner.
     *
     * @return void
     */
    public function check_tx_realty_pi1_objects_by_owner()
    {
        $this->check_tx_realty_pi1_realty_list();
    }

    /**
     * Checks the configuration for the Realty Manager's offerer list.
     *
     * @return void
     */
    public function check_tx_realty_pi1_offerer_list()
    {
        $this->checkCommonFrontEndSettings();
        $this->checkObjectsByOwnerPid(false);
        $this->checkUserGroupsForOffererList();
        $this->checkDisplayedContactInformation(false);
        $this->checkDisplayedContactInformationSpecial();
        $this->checkGroupsWithSpeciallyDisplayedContactInformation();
        $this->checkOffererImageConfiguration();
    }

    /**
     * Checks the configuration for the FE editor of the Realty Manager.
     *
     * @return void
     */
    public function check_tx_realty_pi1_fe_editor()
    {
        // TODO: Check the FE editor template file once we can check other
        // templates than the default template.
        // @see https://bugs.oliverklee.com/show_bug.cgi?id=2061
        $this->checkCommonFrontEndSettings();
        $this->checkIsValidDefaultFromEmailAddress();
        $this->checkSysFolderForFeCreatedRecords();
        $this->checkSysFolderForFeCreatedAuxiliaryRecords();
        $this->checkFeEditorRedirectPid();
        $this->checkFeEditorNotifyEmail();
        $this->checkLoginPid();
    }

    /**
     * Checks the configuration for the FE editor of the Realty Manager.
     *
     * @return void
     */
    public function check_tx_realty_pi1_image_upload()
    {
        // TODO: Check the FE editor template file once we can check other
        // templates than the default template.
        // @see https://bugs.oliverklee.com/show_bug.cgi?id=2061
        $this->checkCommonFrontEndSettings();
        $this->checkSysFolderForFeCreatedRecords();
        $this->checkFeEditorRedirectPid();
        $this->checkLoginPid();
        $this->checkImageUploadThumbnailConfiguration();
    }

    /**
     * Checks the settings that are common to all FE plug-in variations of this
     * extension: CSS styled content, static TypoScript template included,
     * template file, CSS file, salutation mode, and CSS class names.
     *
     * @return void
     */
    private function checkCommonFrontEndSettings()
    {
        $this->checkStaticIncluded();
        $this->checkTemplateFile();
        $this->checkSalutationMode();
        $this->checkCssFileFromConstants();
        $this->checkDateFormat();
        $this->checkWhatToDisplay();
    }

    /**
     * Returns whether $viewPart is enabled in the current configuration for
     * 'singleViewPartsToDisplay'.
     *
     * @param string $viewPart
     *        key of the view part to check for visibility, must not be empty
     *
     * @return bool TRUE if $viewPart is configured to become rendered, FALSE
     *                 otherwise
     */
    private function isSingleViewPartToDisplay($viewPart)
    {
        $configuredValues = GeneralUtility::trimExplode(
            ',',
            $this->objectToCheck->getConfValueString('singleViewPartsToDisplay'),
            true
        );

        return in_array($viewPart, $configuredValues, true);
    }

    /**
     * Checks the settings for Google Maps.
     *
     * @return void
     */
    private function checkGoogleMaps()
    {
        $this->checkShowGoogleMaps();
        if ($this->objectToCheck->getConfValueBoolean(
            'showGoogleMaps',
            's_googlemaps'
        )) {
            $this->checkDefaultCountry();
        }
    }

    /**
     * Checks the setting of the configuration value what_to_display.
     *
     * @return void
     */
    private function checkWhatToDisplay()
    {
        $this->checkIfSingleInSetNotEmpty(
            'what_to_display',
            true,
            'sDEF',
            'This value specifies the type of the realty plug-in to display. '
            . 'If it is not set correctly, it is ignored and the list view '
            . 'is displayed.',
            [
                'realty_list',
                'single_view',
                'favorites',
                'filter_form',
                'contact_form',
                'my_objects',
                'offerer_list',
                'objects_by_owner',
                'fe_editor',
                'image_upload',
            ]
        );
    }

    /**
     * Checks the setting for 'singleViewPartsToDisplay'.
     *
     * @return void
     */
    private function checkSingleViewPartsToDisplay()
    {
        $this->checkIfMultiInSetNotEmpty(
            'singleViewPartsToDisplay',
            true,
            'sDEF',
            'This setting specifies which single view parts to render, ' .
            'incorrect keys will not be displayed and the single view will ' .
            'be an empty page if no value is provided.',
            [
                'nextPreviousButtons',
                'heading',
                'address',
                'description',
                'documents',
                'price',
                'overviewTable',
                'contactButton',
                'addToFavoritesButton',
                'furtherDescription',
                'imageThumbnails',
                'offerer',
                'status',
                'googleMaps',
                'printPageButton',
                'backButton',
            ]
        );
    }

    /**
     * Checks if a record for the currency unit exists in the static_currencies table.
     *
     * @return void
     */
    protected function checkCurrencyUnit()
    {
        $quotedCurrencyUnit = \Tx_Oelib_Db::getDatabaseConnection()->quoteStr(
            $this->objectToCheck->getConfValueString('currencyUnit'),
            'static_currencies'
        );
        if (!\Tx_Oelib_Db::existsRecord('static_currencies', 'cu_iso_3 = "' . $quotedCurrencyUnit . '"')) {
            $this->setErrorMessageAndRequestCorrection(
                'currencyUnit',
                false,
                'This value specifies the ISO alpha 3 code of the currency used for displayed prices. ' .
                'If this value is empty, prices of objects that do not provide ' .
                'their own currency will be displayed without a currency. ' .
                'You have set it to a non valid ISO code, or the table static_currencies is not installed.'
            );
        }
    }

    /**
     * Checks the setting for the date format.
     *
     * @return void
     */
    private function checkDateFormat()
    {
        $this->checkForNonEmptyString(
            'dateFormat',
            false,
            '',
            'This determines the way dates and times are displayed. '
            . 'If this is not set correctly, dates and times might '
            . 'be mangled or not get displayed at all.'
        );
    }

    /**
     * Checks whether values for image sizes in the list view are set.
     *
     * @return void
     */
    private function checkImageSizeValuesForListView()
    {
        $imageSizeItems = [
            'listImageMaxX',
            'listImageMaxY',
        ];

        foreach ($imageSizeItems as $fieldName) {
            $this->checkIfPositiveInteger(
                $fieldName,
                false,
                '',
                'This value specifies image dimensions. Images will not be '
                . 'displayed correctly if this value is invalid.'
            );
        }
    }

    /**
     * Checks whether values for image sizes in the single view are set.
     *
     * @return void
     */
    private function checkImageSizeValuesForSingleView()
    {
        $imageSizeItems = [
            'singleImageMaxX',
            'singleImageMaxY',
        ];

        foreach ($imageSizeItems as $fieldName) {
            $this->checkIfPositiveInteger(
                $fieldName,
                false,
                '',
                'This value specifies image dimensions. Images will not be '
                . 'displayed correctly if this value is invalid.'
            );
        }
    }

    /**
     * Checks the settings of fields in the overview table.
     *
     * @return void
     */
    private function checkFieldsInSingleViewTable()
    {
        $this->checkIfMultiInSetNotEmpty(
            'fieldsInSingleViewTable',
            false,
            '',
            'This value specifies the fields which should be displayed in '
            . 'single view. If this value is empty, the single view only '
            . 'shows the title of an object.',
            $this->getDbColumnNames('tx_realty_objects')
        );
    }

    /**
     * Checks the settings of favorite fields which should be stored in the
     * session.
     *
     * @return void
     */
    private function checkFavoriteFieldsInSession()
    {
        $this->checkIfMultiInSetOrEmpty(
            'favoriteFieldsInSession',
            false,
            '',
            'This value specifies the field names that will be stored in the '
            . 'session when displaying the favorites list. This value may be '
            . 'empty. Wrong values cause empty fields in the session data '
            . 'array.',
            $this->getDbColumnNames('tx_realty_objects')
        );
    }

    /**
     * Checks the setting of the configuration value
     * requireLoginForSingleViewPage.
     *
     * @return void
     */
    private function checkRequireLoginForSingleViewPage()
    {
        $this->checkIfBoolean(
            'requireLoginForSingleViewPage',
            false,
            '',
            'This value specifies whether a login is required to access the '
            . 'single view page. It might be interpreted incorrectly if no '
            . 'logical value was set.'
        );
    }

    /**
     * Checks the setting for the login PID.
     *
     * @return void
     */
    private function checkLoginPid()
    {
        $this->checkIfSingleFePageNotEmpty(
            'loginPID',
            false,
            '',
            'This value specifies the login page and is needed if a login ' .
            'is required. Users could not be directed to the login ' .
            'page if this value is invalid.'
        );
    }

    /**
     * Checks the setting of the configuration value showContactPageLink.
     *
     * @return void
     */
    private function checkShowContactPageLink()
    {
        $this->checkIfBoolean(
            'showContactPageLink',
            true,
            'sDEF',
            'This value specifies whether a link to the contact form should be ' .
            'displayed in the current view. A misconfigured value might lead ' .
            'to undesired results.'
        );
    }

    /**
     * Checks the setting for the contact PID.
     *
     * @return void
     */
    private function checkContactPid()
    {
        $this->checkIfSingleFePageNotEmpty(
            'contactPID',
            false,
            '',
            'This value specifies the contact page which will be linked from ' .
            'the current page. The link to the contact form will not work ' .
            'as long as this value is misconfigured.'
        );
    }

    /**
     * Checks the setting for displayedSearchWidgetFields.
     *
     * @return void
     */
    private function checkDisplayedSearchWidgetFields()
    {
        $this->checkIfMultiInSetNotEmpty(
            'displayedSearchWidgetFields',
            true,
            's_searchForm',
            'This value specifies which search widget fields to display in the ' .
            'front-end. The search widget will not display any fields at ' .
            'all if this value is empty or contains only invalid keys.',
            [
                'site',
                'priceRanges',
                'uid',
                'objectNumber',
                'city',
                'district',
                'objectType',
                'rent',
                'livingArea',
                'houseType',
                'numberOfRooms',
            ]
        );
    }

    /**
     * Checks the setting for the price ranges for the filter form.
     *
     * @return void
     */
    private function checkPriceRangesForFilterForm()
    {
        $displayedWidgetFields = GeneralUtility::trimExplode(
            ',',
            $this->objectToCheck->getConfValueString(
                'displayedSearchWidgetFields',
                's_searchForm'
            ),
            true
        );
        if (!in_array('priceRanges', $displayedWidgetFields, true)) {
            return;
        }

        $this->checkRegExp(
            'priceRangesForFilterForm',
            true,
            's_searchForm',
            'This value defines the ranges to be displayed in the filter ' .
            'form\'s selectbox for prices. With an invalid configuration, ' .
            'price ranges will not be displayed correctly.',
            '/^(((\\d+-\\d+|-\\d+|\\d+-), *)*(\\d+-\\d+|-\\d+|\\d+-))$/'
        );
    }

    /**
     * Checks the setting of the pages that contain realty records to be
     * displayed.
     *
     * @return void
     */
    private function checkPagesToDisplay()
    {
        $this->checkIfPidListNotEmpty(
            'pages',
            true,
            'sDEF',
            'This value specifies the list of PIDs that contain the realty '
            . 'records to be displayed. If this list is empty, there is only '
            . 'a message about no search results displayed.'
        );
    }

    /**
     * Checks the setting for the recursion level for the pages list.
     *
     * @return void
     */
    private function checkRecursive()
    {
        $this->checkIfPositiveIntegerOrZero(
            'recursive',
            true,
            'sDEF',
            'This value specifies the recursion level for the pages list. The '
            . 'recursion can only be set to include subfolders of the '
            . 'folders in "pages". It is impossible to access superior '
            . 'folders with this option.'
        );
    }

    /**
     * Checks the setting of the configuration value objectsByOwnerPID.
     *
     * @param bool $mayBeEmpty TRUE if the configuration may be empty
     *
     * @return void
     */
    private function checkObjectsByOwnerPid($mayBeEmpty = true)
    {
        if ($mayBeEmpty) {
            $checkFunction = 'checkIfSingleFePageOrEmpty';
            $errorText = 'This value specifies the page ID of the list of ' .
                'objects by one offerer. The link to this list might not work ' .
                'correctly if this value is misconfigured.';
        } else {
            $checkFunction = 'checkIfSingleFePageNotEmpty';
            $errorText = 'This value specifies the page ID of the list of ' .
                'objects by one offerer. The link to this list will not be ' .
                'displayed if this value is empty. The link might not work ' .
                'correctly if this value is misconfigured.';
        }

        $this->$checkFunction(
            'objectsByOwnerPID',
            true,
            's_offererInformation',
            $errorText
        );
    }

    /**
     * Checks the setting of the configuration value userGroupsForOffererList.
     *
     * @return void
     */
    private function checkUserGroupsForOffererList()
    {
        $this->checkIfPidListOrEmpty(
            'userGroupsForOffererList',
            true,
            's_offererInformation',
            'This value specifies the group from which the users are displayed ' .
            'in the offerer list. The list will be empty if this value is ' .
            'invalid. All front-end user will be displayed if this value is ' .
            'empty.'
        );
    }

    /**
     * Checks the setting for displayedContactInformation.
     *
     * @param bool $mayBeEmpty TRUE if the configuration may be empty
     *
     * @return void
     */
    private function checkDisplayedContactInformation($mayBeEmpty = true)
    {
        if ($mayBeEmpty) {
            $checkFunction = 'checkIfMultiInSetOrEmpty';
        } else {
            $checkFunction = 'checkIfMultiInSetNotEmpty';
        }

        $this->$checkFunction(
            'displayedContactInformation',
            true,
            's_offererInformation',
            'This value specifies which contact data to display in the front-end. ' .
            'The contact data will not be displayed at all if this value is ' .
            'empty or contains only invalid keys.',
            [
                'company',
                'offerer_label',
                'usergroup',
                'street',
                'city',
                'telephone',
                'email',
                'www',
                'image',
                'objects_by_owner_link',
            ]
        );
    }

    /**
     * Checks the setting for displayedContactInformationSpecial.
     *
     * @return void
     */
    private function checkDisplayedContactInformationSpecial()
    {
        $this->checkIfMultiInSetOrEmpty(
            'displayedContactInformationSpecial',
            true,
            's_offererInformation',
            'This value specifies which contact data to display in the front-end. ' .
            'This value only defines which contact data to display of ' .
            'offerers which are members in the front-end user groups for ' .
            'which to display special contact data. The contact data will ' .
            'not be displayed at all if this value is empty or contains only' .
            'invalid keys.',
            [
                'company',
                'offerer_label',
                'usergroup',
                'street',
                'city',
                'telephone',
                'email',
                'www',
                'image',
                'objects_by_owner_link',
            ]
        );
    }

    /**
     * Checks the setting for displayedContactInformationSpecial.
     *
     * @return void
     */
    private function checkGroupsWithSpeciallyDisplayedContactInformation()
    {
        // checkIfPidListOrEmpty checks for a comma-separated list of integers
        $this->checkIfPidListOrEmpty(
            'groupsWithSpeciallyDisplayedContactInformation',
            true,
            's_offererInformation',
            'This value specifies of which front-end user group\'s offerers ' .
            'special contact data should be displayed. If this value is ' .
            'empty or invalid, the special contact data will not be displayed ' .
            'for any owner.'
        );
    }

    /**
     * Checks the setting for the default contact e-mail address.
     *
     * @return void
     */
    private function checkDefaultContactEmail()
    {
        $this->checkIsValidEmailNotEmpty(
            'defaultContactEmail',
            true,
            's_contactForm',
            true,
            'This value specifies the recipient for requests on objects. ' .
            'This address is always used if direct requests for objects ' .
            'are disabled and it is used if a direct request is not ' .
            'possible because an object\'s contact data cannot be found.'
        );
    }

    /**
     * Checks the setting for the BCC e-mail address.
     *
     * @return void
     */
    private function checkBlindCarbonCopyAddress()
    {
        $this->checkIsValidEmailOrEmpty(
            'blindCarbonCopyAddress',
            true,
            's_contactForm',
            true,
            'This value specifies the recipient for for a blind carbon copy of ' .
            'each request on objects and may be left empty.'
        );
    }

    /**
     * Checks the setting for visibleContactFormFields.
     *
     * @return void
     */
    private function checkVisibleContactFormFields()
    {
        $this->checkIfMultiInSetOrEmpty(
            'visibleContactFormFields',
            true,
            's_contactForm',
            'This value specifies which fields are visible in the contact form. ' .
            'Some fields will be not be visible if this configuration is ' .
            'incorrect.',
            [
                'name',
                'street',
                'zip_and_city',
                'telephone',
                'request',
                'viewing',
                'information',
                'callback',
                'terms',
                'law',
            ]
        );
    }

    /**
     * Checks whether the "terms" checkbox is visible in the contact form.
     *
     * @return bool TRUE if the "terms" checkbox is visible, FALSE otherwise
     */
    private function hasTermsInContactForm()
    {
        $visibleFormFields = GeneralUtility::trimExplode(
            ',',
            $this->objectToCheck->getConfValueString(
                'visibleContactFormFields',
                's_contactForm'
            ),
            true
        );

        return in_array('terms', $visibleFormFields, true);
    }

    /**
     * Checks the configuration for requiredContactFormFields.
     *
     * @return void
     */
    private function checkRequiredContactFormFields()
    {
        $this->checkIfMultiInSetOrEmpty(
            'requiredContactFormFields',
            true,
            's_contactForm',
            'This value specifies which fields are required to be filled when ' .
            'committing a contact request. Some fields will be not be ' .
            'required if this configuration is incorrect.',
            ['name', 'street', 'zip', 'city', 'telephone', 'request']
        );

        // checks whether the required fields are visible
        $this->checkIfMultiInSetOrEmpty(
            'requiredContactFormFields',
            true,
            's_contactForm',
            'This value specifies which fields are required to be filled when ' .
            'committing a contact request. Some fields are set to required ' .
            'but are actually not configured to be visible in the form. ' .
            'The form cannot be submitted as long as this inconsistency ' .
            'remains.',
            GeneralUtility::trimExplode(
                ',',
                // Replaces "zip_and_city" with "zip,city" as visiblity can only
                // be configured for ZIP plus city but requirements can be set
                // separately.
                str_replace(
                    '_and_',
                    ',',
                    $this->objectToCheck->getConfValueString(
                        'visibleContactFormFields',
                        's_contactForm'
                    )
                ),
                true
            )
        );
    }

    /**
     * Checks the variable termsPID.
     *
     * @return void
     */
    private function checkTermsPid()
    {
        $this->checkIfSingleFePageNotEmpty(
            'termsPID',
            true,
            's_contactForm',
            'This value specifies the PID containing the terms linked from ' .
            'the contact form. If this value is invalid, the link to ' .
            'the terms page will not work.'
        );
    }

    /**
     * Checks the setting of the checkboxes filter.
     *
     * @return void
     */
    private function checkCheckboxesFilter()
    {
        $this->checkIfSingleInTableOrEmpty(
            'checkboxesFilter',
            true,
            's_searchForm',
            'This value specifies the name of the DB field to create the search ' .
            'filter checkboxes from. Searching will not work properly if ' .
            'non-database fields are set.',
            'tx_realty_objects'
        );
    }

    /**
     * Checks the setting for orderBy.
     *
     * @return void
     */
    private function checkOrderBy()
    {
        $this->checkIfSingleInSetOrEmpty(
            'orderBy',
            true,
            'sDEF',
            'This value specifies the database field name by which the list view ' .
            'should be sorted initially. Displaying the list view might not ' .
            'work properly if this value is misconfigured.',
            [
                'object_number',
                'title',
                'city',
                'district',
                'buying_price',
                'rent_excluding_bills',
                'number_of_rooms',
                'living_area',
                'tstamp',
                'random',
            ]
        );
    }

    /**
     * Checks the settings for the sort criteria.
     *
     * @return void
     */
    private function checkSortCriteria()
    {
        $this->checkIfMultiInSetOrEmpty(
            'sortCriteria',
            true,
            'sDEF',
            'This value specifies the database field names by which a FE user ' .
            'can sort the list view. This value is usually set via ' .
            'flexforms.',
            [
                'object_number',
                'title',
                'city',
                'district',
                'buying_price',
                'rent_excluding_bills',
                'number_of_rooms',
                'living_area',
                'tstamp',
                'random',
            ]
        );
    }

    /**
     * Checks the settings for the PID for the single view.
     *
     * @return void
     */
    private function checkSingleViewPid()
    {
        $this->checkIfSingleFePageNotEmpty(
            'singlePID',
            true,
            'sDEF',
            'This value specifies the PID of the page for the single view. If '
            . 'this value is empty or invalid, the single view is shown on '
            . 'the same page as the list view.'
        );
    }

    /**
     * Checks the settings for the PID for the favorites view.
     *
     * @return void
     */
    private function checkFavoritesPid()
    {
        $this->checkIfSingleFePageNotEmpty(
            'favoritesPID',
            true,
            'sDEF',
            'This value specifies the PID of the page for the favorites view. '
            . 'Favorites cannot be displayed if this value is invalid.'
        );
    }

    /**
     * Checks the settings for the PID for the FE editor.
     *
     * @return void
     */
    private function checkEditorPid()
    {
        $this->checkIfSingleFePageNotEmpty(
            'editorPID',
            true,
            'sDEF',
            'This value specifies the PID of the page for the FE editor. '
            . 'This page cannot be displayed if this value is invalid.'
        );
    }

    /**
     * Checks the settings for the target PID for the filter form and the
     * city selector.
     *
     * @return void
     */
    private function checkFilterTargetPid()
    {
        $this->checkIfSingleFePageNotEmpty(
            'filterTargetPID',
            true,
            's_searchForm',
            'This value specifies the PID of the target page for the filter '
            . 'form and the city selector. These forms will not direct to '
            . 'the correct page after submit if this value is invalid.'
        );
    }

    /**
     * Checks the settings for the PID for the FE image upload.
     *
     * @return void
     */
    private function checkImageUploadPid()
    {
        $this->checkIfSingleFePageNotEmpty(
            'imageUploadPID',
            true,
            'sDEF',
            'This value specifies the PID of the page with the image upload for '
            . 'the FE editor. The image upload cannot be displayed if this '
            . 'value is invalid.'
        );
    }

    /**
     * Checks the settings for the PID of the system folder for FE-created
     * records.
     *
     * @return void
     */
    private function checkSysFolderForFeCreatedRecords()
    {
        $this->checkIfSingleSysFolderNotEmpty(
            'sysFolderForFeCreatedRecords',
            true,
            's_feeditor',
            'This value specifies the PID of the system folder for FE-created '
            . 'records. New records will be stored on the root page if this '
            . 'value is invalid.'
        );
    }

    /**
     * Checks the settings for the PID of the system folder for FE-created
     * records.
     *
     * @return void
     */
    private function checkSysFolderForFeCreatedAuxiliaryRecords()
    {
        $this->checkIfSingleSysFolderNotEmpty(
            'sysFolderForFeCreatedAuxiliaryRecords',
            false,
            '',
            'This value specifies the PID of the system folder for FE-created ' .
            'auxiliary records. New cities and districts will be stored on' .
            'the root page if this value is invalid.'
        );
    }

    /**
     * Checks the setting of the configuration value priceOnlyIfAvailable.
     *
     * @return void
     */
    private function checkPriceOnlyIfAvailable()
    {
        $this->checkIfBoolean(
            'priceOnlyIfAvailable',
            false,
            '',
            'This value specifies whether a the price will be shown for sold ' .
            'or rented objects. If this value is set incorrectly, the ' .
            'price might get shown although this is not intended (or ' .
            'vice versa).'
        );
    }

    /**
     * Checks the settings for the PID of the FE page where to redirect to after
     * saving a FE-created record.
     *
     * @return void
     */
    private function checkFeEditorRedirectPid()
    {
        $this->checkIfSingleFePageNotEmpty(
            'feEditorRedirectPid',
            true,
            's_feeditor',
            'This value specifies the PID of the FE page to which users will ' .
            'be redirected after a FE-created record or an image was saved. ' .
            'This redirecting will not proceed correctly if this value is ' .
            'invalid or empty.'
        );
    }

    /**
     * Checks the setting for the FE editor's notification e-mail address.
     *
     * @return void
     */
    private function checkFeEditorNotifyEmail()
    {
        $this->checkIsValidEmailNotEmpty(
            'feEditorNotifyEmail',
            true,
            's_feeditor',
            true,
            'This value specifies the recipient for a notification when a new '
            . 'record has been created in the FE. No e-mail will be send if '
            . 'this value is not configured correctly.'
        );
    }

    /**
     * Checks the default country.
     *
     * @return void
     */
    private function checkDefaultCountry()
    {
        $this->checkIfPositiveInteger(
            'defaultCountryUID',
            true,
            's_googlemaps',
            'This value specifies the UID of the default country for realty ' .
            'objects. If this value is not configured correctly, the ' .
            'objects will be mislocated in Google Maps.'
        );
    }

    /**
     * Checks the configuration value showGoogleMaps.
     *
     * @return void
     */
    private function checkShowGoogleMaps()
    {
        $this->checkIfBoolean(
            'showGoogleMaps',
            true,
            's_googlemaps',
            'This value specifies whether a Google Map of an object should be ' .
            'shown. If this value is not set correctly, the map might not ' .
            'get shown although it should be shown (or vice versa).'
        );
    }

    /**
     * Checks the settings for the thumbnail images in the front-end image
     * upload.
     *
     * @return void
     */
    private function checkImageUploadThumbnailConfiguration()
    {
        $this->checkImageUploadThumbnailWidth();
        $this->checkImageUploadThumbnailHeight();
    }

    /**
     * Checks the settings of the maximum width of the thumbnail images at the
     * front-end image upload.
     *
     * @return void
     */
    private function checkImageUploadThumbnailWidth()
    {
        $this->checkIfPositiveInteger(
            'imageUploadThumbnailWidth',
            false,
            '',
            'This value specifies the width of the thumbnails in the image ' .
            'upload. If it is not configured properly, the image will be ' .
            'shown at original size.'
        );
    }

    /**
     * Checks the settings of the maximum height of the thumbnail images at the
     * front-end image upload.
     *
     * @return void
     */
    private function checkImageUploadThumbnailHeight()
    {
        $this->checkIfPositiveInteger(
            'imageUploadThumbnailHeight',
            false,
            '',
            'This value specifies the height of the thumbnails in the image ' .
            'upload. If it is not configured properly, the image will be ' .
            'shown at original size.'
        );
    }

    /**
     * Checks the configuration value advertisementPID.
     *
     * @return void
     */
    private function checkAdvertisementPid()
    {
        $this->checkIfSingleFePageOrEmpty(
            'advertisementPID',
            true,
            's_advertisements',
            'This value specifies the page that contains the advertisement ' .
            'form. If this value is incorrect, the link to the form ' .
            'will not work.'
        );
    }

    /**
     * Checks the configuration value advertisementParameterForObjectUid.
     *
     * @return void
     */
    private function checkAdvertisementParameterForObjectUid()
    {
        // Nothing to do - every string is allowed.
    }

    /**
     * Checks the configuration value advertisementExpirationInDays.
     *
     * @return void
     */
    private function checkAdvertisementExpirationInDays()
    {
        $this->checkIfPositiveIntegerOrZero(
            'advertisementExpirationInDays',
            true,
            's_advertisements',
            'This value specifies the period after which an advertisement ' .
            'expires. If this value is invalid, advertisements will ' .
            'not expire at all.'
        );
    }

    /**
     * Checks the settings for the offerer image.
     *
     * @return void
     */
    private function checkOffererImageConfiguration()
    {
        $this->checkOffererImageWidth();
        $this->checkOffererImageHeight();
    }

    /**
     * Checks the value of offererImageMaxWidth.
     *
     * @return void
     */
    private function checkOffererImageWidth()
    {
        $this->checkIfPositiveInteger(
            'offererImageMaxWidth',
            false,
            '',
            'This value specifies the width of the offerer image in the ' .
            'offerer list view and the single view. If it is not ' .
            'configured properly, the image will be shown in its original ' .
            'size.'
        );
    }

    /**
     * Checks the value of offererImageMaxHeight.
     *
     * @return void
     */
    private function checkOffererImageHeight()
    {
        $this->checkIfPositiveInteger(
            'offererImageMaxHeight',
            false,
            '',
            'This value specifies the height of the offerer image in the ' .
            'offerer list view and the single view. If it is not ' .
            'configured properly, the image will be shown in its original ' .
            'size.'
        );
    }

    /**
     * Checks the settings for the gallery image when using the lightbox.
     *
     * @return void
     */
    private function checkLightboxImageConfiguration()
    {
        $this->checkEnableLightbox();
        if ($this->objectToCheck->getConfValueBoolean('enableLightbox')) {
            $this->checkLightboxImageWidthMax();
            $this->checkLightboxImageHeightMax();
        }
        // The inclusion of JavaScript libraries is not influenced by the
        // enableLightbox configuration.
    }

    /**
     * Checks the value of enableLightbox.
     *
     * @return void
     */
    private function checkEnableLightbox()
    {
        $this->checkIfBoolean(
            'enableLightbox',
            false,
            '',
            'This value specifies whether the Lightbox for the images in the ' .
            'single view should be enabled. If this is not set correctly, ' .
            'the Lighbtox might not be enabled although it should be ' .
            '(or vice versa).'
        );
    }

    /**
     * Checks the value of lightboxImageWidthMax.
     *
     * @return void
     */
    private function checkLightboxImageWidthMax()
    {
        $this->checkIfPositiveInteger(
            'lightboxImageWidthMax',
            false,
            '',
            'This value specifies the width of the gallery images in the ' .
            'lightbox window. If it is not configured properly, the ' .
            'images will be shown in their original size.'
        );
    }

    /**
     * Checks the value of lightboxImageHeightMax.
     *
     * @return void
     */
    private function checkLightboxImageHeightMax()
    {
        $this->checkIfPositiveInteger(
            'lightboxImageHeightMax',
            false,
            '',
            'This value specifies the height of the gallery images in the ' .
            'lightbox window. If it is not configured properly, the ' .
            'images will be shown in their original size.'
        );
    }

    /**
     * Checks the value of enableNextPreviousButtons.
     *
     * @return void
     */
    private function checkEnableNextPreviousButtons()
    {
        $this->checkIfBoolean(
            'enableNextPreviousButtons',
            false,
            '',
            'This value specifies whether the next and previous buttons should ' .
            'be shown. If this value is not set correctly, the buttons ' .
            'might not get shown although they should be shown (or vice ' .
            'versa).'
        );
    }

    /**
     * Checks the configuration of the next previous buttons for the single view.
     *
     * @return void
     */
    private function checkEnableNextPreviousButtonsForSingleView()
    {
        $this->checkEnableNextPreviousButtons();

        if (
            !$this->objectToCheck->getConfValueBoolean('enableNextPreviousButtons')
            && $this->isSingleViewPartToDisplay('nextPreviousButtons')
        ) {
            $this->setErrorMessageAndRequestCorrection(
                'enableNextPreviousButtons',
                false,
                'The TS setup variable <strong>' .
                $this->getTSSetupPath() . 'enableNextPreviousButtons' .
                '</strong> is set to <strong>0</strong> but needs to be set to ' .
                '<strong>1</strong>.<br/>' .
                'This value specifies whether the next and previous buttons should ' .
                'be shown. If this value is not set correctly, the buttons ' .
                'will not get shown.'
            );
        }
    }
}
