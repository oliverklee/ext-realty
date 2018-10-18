<?php

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This class represents the "my objects" list view.
 *
 * This view may only be rendered if a user is logged-in at the front end.
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 * @author Saskia Metzler <saskia@merlin.owl.de>
 * @author Bernd Schönbach <bernd@oliverklee.de>
 */
class tx_realty_pi1_MyObjectsListView extends tx_realty_pi1_AbstractListView
{
    /**
     * @var string the list view type to display
     */
    protected $currentView = 'my_objects';

    /**
     * @var string the locallang key for the label belonging to this view
     */
    protected $listViewLabel = 'label_your_objects';

    /**
     * @var bool whether Google Maps should be shown in this view
     */
    protected $isGoogleMapsAllowed = false;

    /**
     * Initializes some view-specific data.
     *
     * @return void
     */
    protected function initializeView()
    {
        $this->unhideSubparts(
            'wrapper_editor_specific_content,new_record_link'
        );

        $this->setLimitHeading();
        $this->setEditorLinkMarker();
        $this->setMarker(
            'empty_editor_link',
            $this->createLinkToFeEditorPage('editorPID', 0)
        );
        $this->processDeletion();
    }

    /**
     * Sets the message how many objects the currently logged-in front-end user still can enter.
     *
     * This function should only be called when a user is logged-in at the front end.
     *
     * @return void
     */
    private function setLimitHeading()
    {
        /** @var tx_realty_Model_FrontEndUser $user */
        $user = Tx_Oelib_FrontEndLoginManager::getInstance()->getLoggedInUser('tx_realty_Mapper_FrontEndUser');
        if ($user->getTotalNumberOfAllowedObjects() === 0) {
            $this->hideSubparts('limit_heading');
            return;
        }

        $objectsLeftToEnter = $user->getObjectsLeftToEnter();
        $this->unhideSubparts('limit_heading');
        $this->setMarker(
            'objects_limit_heading',
            sprintf(
                $this->translate('label_objects_already_entered'),
                $user->getNumberOfObjects(),
                $user->getTotalNumberOfAllowedObjects()
            )
        );
        switch ($objectsLeftToEnter) {
            case 0:
                $labelLeftToEnter = $this->translate('label_no_objects_left');
                break;
            case 1:
                $labelLeftToEnter = $this->translate('label_one_object_left');
                break;
            default:
                $labelLeftToEnter = sprintf($this->translate('label_multiple_objects_left'), $objectsLeftToEnter);
        }

        $this->setMarker('objects_left_to_enter', $labelLeftToEnter);
    }

    /**
     * Sets the link to the new record button of the my objects view and hides
     * it if the user cannot enter any more objects.
     *
     * This function should only be called when a user is logged in at the front
     * end.
     *
     * @return void
     */
    private function setEditorLinkMarker()
    {
        /** @var tx_realty_Model_FrontEndUser $user */
        $user = Tx_Oelib_FrontEndLoginManager::getInstance()->getLoggedInUser('tx_realty_Mapper_FrontEndUser');
        if ($user->canAddNewObjects()) {
            $this->setMarker('empty_editor_link', $this->createLinkToFeEditorPage('editorPID', 0));
        } else {
            $this->hideSubparts('new_record_link');
        }
    }

    /**
     * Processes the deletion of a realty record.
     *
     * @return void
     */
    private function processDeletion()
    {
        $uid = (int)$this->piVars['delete'];
        // no need for a front-end editor if there is nothing to delete
        if ($uid === 0) {
            return;
        }

        // For testing, the FE editor's mkforms object must not be created.
        /** @var \tx_realty_frontEndEditor $frontEndEditor */
        $frontEndEditor = GeneralUtility::makeInstance(
            \tx_realty_frontEndEditor::class,
            $this->conf,
            $this->cObj,
            $uid,
            'pi1/tx_realty_frontEndEditor.xml',
            $this->isTestMode
        );
        $frontEndEditor->deleteRecord();
    }

    /**
     * Gets the WHERE clause part specific to this view.
     *
     * @return string the WHERE clause parts to add, will be empty if no view
     *                specific WHERE clause parts are needed
     */
    protected function getViewSpecificWhereClauseParts()
    {
        return ' AND tx_realty_objects.owner = ' .
            Tx_Oelib_FrontEndLoginManager::getInstance()->getLoggedInUser('tx_realty_Mapper_FrontEndUser')->getUid();
    }

    /**
     * Sets the row contents specific to this view.
     *
     * @return void
     */
    protected function setViewSpecificListRowContents()
    {
        $this->setMarker(
            'editor_link',
            $this->createLinkToFeEditorPage(
                'editorPID',
                $this->internal['currentRow']['uid']
            )
        );
        $this->setMarker(
            'image_upload_link',
            $this->createLinkToFeEditorPage(
                'imageUploadPID',
                $this->internal['currentRow']['uid']
            )
        );
        $this->setMarker(
            'really_delete',
            htmlspecialchars(
                $this->translate('label_really_delete') . LF .
                $this->translate('label_object_number') . ' ' .
                $this->internal['currentRow']['object_number'] . ': ' .
                $this->internal['currentRow']['title']
            )
        );
        $this->setMarker(
            'delete_link',
            $this->cObj->typoLink_URL(
                [
                    'parameter' => $this->getFrontEndController()->id,
                    'additionalParams' => GeneralUtility::implodeArrayForUrl(
                        $this->prefixId,
                        ['delete' => $this->internal['currentRow']['uid']]
                    ),
                    'useCacheHash' => true,
                ]
            )
        );
        $this->setMarker(
            'record_state',
            $this->translate($this->internal['currentRow']['hidden'] ? 'label_pending' : 'label_published')
        );

        $this->setAdvertisementMarkers();
    }

    /**
     * Determines whether hidden results should be shown.
     *
     * This will be used for Tx_Oelib_Db::enableFields.
     *
     * @return int 1 if hidden records should be shown, -1 otherwise
     */
    protected function shouldShowHiddenObjects()
    {
        return 1;
    }

    /**
     * Creates a link to the FE editor page.
     *
     * @param string $pidKey
     *        key of the configuration value with the PID, must not be empty
     * @param int $uid
     *        UID of the object to be loaded for editing, must be >= 0
     *        (Zero will open the FE editor for a new record to insert.)
     *
     * @return string the link to the FE editor page, will not be empty
     */
    private function createLinkToFeEditorPage($pidKey, $uid)
    {
        return GeneralUtility::locationHeaderUrl(
            $this->cObj->typoLink_URL(
                [
                    'parameter' => $this->getConfValueInteger($pidKey),
                    'additionalParams' => GeneralUtility::implodeArrayForUrl(
                        $this->prefixId,
                        ['showUid' => $uid]
                    ),
                    'useCacheHash' => true,
                ]
            )
        );
    }

    /**
     * Sets the markers for the "advertise" link for one row.
     *
     * @return void
     */
    private function setAdvertisementMarkers()
    {
        if (!$this->hasConfValueInteger(
            'advertisementPID',
            's_advertisements'
        )) {
            $this->hideSubparts('wrapper_advertising');
            return;
        }

        if ($this->isCurrentObjectAdvertised()) {
            $this->hideSubparts('wrapper_advertise_button');
            $this->unhideSubparts('wrapper_advertised_status');
            return;
        }

        $this->unhideSubparts('wrapper_advertise_button');
        $this->hideSubparts('wrapper_advertised_status');

        if ($this->hasConfValueString(
            'advertisementParameterForObjectUid',
            's_advertisements'
        )) {
            $linkParameters = GeneralUtility::implodeArrayForUrl(
                '',
                [
                    $this->getConfValueString(
                        'advertisementParameterForObjectUid',
                        's_advertisements'
                    ) => $this->internal['currentRow']['uid'],
                ]
            );
        } else {
            $linkParameters = '';
        }

        $this->setMarker(
            'advertise_link',
            $this->cObj->typoLink_URL(
                [
                    'parameter' => $this->getConfValueInteger(
                        'advertisementPID',
                        's_advertisements'
                    ),
                    'additionalParams' => $linkParameters,
                    'useCacheHash' => true,
                ]
            )
        );
    }

    /**
     * Checks whether the current object is advertised and the advertisement
     * has not expired yet.
     *
     * @return bool TRUE if the current object is advertised and the
     *                 advertisement has not expired yet, FALSE otherwise
     */
    private function isCurrentObjectAdvertised()
    {
        $advertisementDate = (int)$this->internal['currentRow']['advertised_date'];
        if ($advertisementDate === 0) {
            return false;
        }

        $expiryInDays = $this->getConfValueInteger('advertisementExpirationInDays', 's_advertisements');
        if ($expiryInDays === 0) {
            return true;
        }

        return ($advertisementDate + $expiryInDays * Tx_Oelib_Time::SECONDS_PER_DAY) < $GLOBALS['SIM_EXEC_TIME'];
    }
}
