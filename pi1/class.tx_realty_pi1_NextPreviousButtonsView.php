<?php

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * This class renders the "next" and "previous" buttons.
 *
 * @author Bernd Schönbach <bernd@oliverklee.de>
 */
class tx_realty_pi1_NextPreviousButtonsView extends tx_realty_pi1_FrontEndView
{
    /**
     * Renders the "previous" and "next" buttons.
     *
     * @param array $piVars piVars array, may be empty
     *
     * @return string the HTML output for the "previous" and "next" buttons,
     *                will be empty if both buttons are hidden
     */
    public function render(array $piVars = [])
    {
        $this->piVars = $this->sanitizePiVars();
        if (!$this->canButtonsBeRendered()) {
            return '';
        }

        /** @var Tx_Oelib_Visibility_Tree $visibilityTree */
        $visibilityTree = GeneralUtility::makeInstance(
            Tx_Oelib_Visibility_Tree::class,
            ['nextPreviousButtons' => ['previousButton' => false, 'nextButton' => false]]
        );

        $recordPosition = $this->piVars['recordPosition'];
        if ($recordPosition > 0) {
            $previousRecordUid = $this->getPreviousRecordUid();
            $this->setMarker(
                'previous_url',
                $this->getButtonUrl($recordPosition - 1, $previousRecordUid)
            );
            $visibilityTree->makeNodesVisible(['previousButton']);
        }

        $nextRecordUid = $this->getNextRecordUid();
        if ($nextRecordUid > 0) {
            $visibilityTree->makeNodesVisible(['nextButton']);
            $this->setMarker(
                'next_url',
                $this->getButtonUrl($recordPosition + 1, $nextRecordUid)
            );
        }

        $this->hideSubpartsArray(
            $visibilityTree->getKeysOfHiddenSubparts(),
            'FIELD_WRAPPER'
        );

        return $this->getSubpart('FIELD_WRAPPER_NEXTPREVIOUSBUTTONS');
    }

    /**
     * Checks whether all preconditions are fulfilled for the rendering of the
     * buttons.
     *
     * @return bool TRUE if the buttons can be rendered, FALSE otherwise
     */
    private function canButtonsBeRendered()
    {
        if (!$this->getConfValueBoolean('enableNextPreviousButtons')) {
            return false;
        }
        if ($this->piVars['recordPosition'] < 0) {
            return false;
        }
        if (!in_array(
                $this->piVars['listViewType'],
                ['my_objects', 'favorites', 'objects_by_offerer', 'realty_list']
            )
        ) {
            return false;
        }
        if ($this->piVars['listUid'] <= 0) {
            return false;
        }

        return Tx_Oelib_Db::existsRecordWithUid(
            'tt_content',
            $this->piVars['listUid'],
            Tx_Oelib_Db::enableFields('tt_content')
        );
    }

    /////////////////////////
    // Sanitizing functions
    /////////////////////////

    /**
     * Sanitizes the piVars needed for this view.
     *
     * This function will store the sanitized piVars into $this->piVars.
     *
     * @return array the sanitized piVars, will be empty if an empty array was
     *               given.
     */
    private function sanitizePiVars()
    {
        $sanitizedPiVars = [];

        $sanitizedPiVars['recordPosition'] = isset($this->piVars['recordPosition'])
            ? (int)$this->piVars['recordPosition'] : -1;
        $sanitizedPiVars['listUid'] = isset($this->piVars['listUid'])
            ? max((int)$this->piVars['listUid'], 0) : 0;

        $sanitizedPiVars['listViewType'] = isset($this->piVars['listViewType'])
            ? $this->piVars['listViewType']
            : '';

        // listViewLimitation will be sanitized, only if it actually is used.
        if (isset($this->piVars['listViewLimitation'])) {
            $sanitizedPiVars['listViewLimitation']
                = $this->piVars['listViewLimitation'];
        }

        return $sanitizedPiVars;
    }

    /**
     * Sanitizes and decodes the listViewLimitation piVar.
     *
     * @return string[] the data stored in the listViewLimitation string as array.
     */
    private function sanitizeAndSplitListViewLimitation()
    {
        $rawData = json_decode($this->piVars['listViewLimitation'], true);
        if (!is_array($rawData) || empty($rawData)) {
            return [];
        }

        $allowedKeys = array_merge(
            ['search', 'orderBy', 'descFlag'],
            tx_realty_filterForm::getPiVarKeys()
        );
        $result = [];

        foreach ($allowedKeys as $allowedKey) {
            if (isset($rawData[$allowedKey])) {
                $result[$allowedKey] = $rawData[$allowedKey];
            }
        }

        return $result;
    }

    /////////////////////////////////////////////
    // Functions for retrieving the record UIDs
    /////////////////////////////////////////////

    /**
     * Retrieves the UID of the record previous to the currently shown one.
     *
     * Before calling this function, ensure that $this->piVars['recordPosition']
     * is >= 1.
     *
     * @return int the UID of the previous record, will be > 0
     */
    private function getPreviousRecordUid()
    {
        return $this->getRecordAtPosition($this->piVars['recordPosition'] - 1);
    }

    /**
     * Retrieves the UID of the record next to to the currently shown one.
     *
     * A return value of 0 means that no record could be found at the given
     * position.
     *
     * @return int the UID of the next record, will be >= 0
     */
    private function getNextRecordUid()
    {
        return $this->getRecordAtPosition($this->piVars['recordPosition'] + 1);
    }

    /**
     * Retrieves the UID for the record at the given record position.
     *
     * @param int $recordPosition
     *        the position of the record to find, must be >= 0
     *
     * @return int the UID of the record at the given position, will be >= 0
     */
    private function getRecordAtPosition($recordPosition)
    {
        $contentData = Tx_Oelib_Db::selectSingle(
            '*',
            'tt_content',
            'uid = ' . (int)$this->piVars['listUid'] . Tx_Oelib_Db::enableFields('tt_content')
        );
        /** @var ContentObjectRenderer $contentObject */
        $contentObject = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        $contentObject->start($contentData, 'tt_content');
        $listView = tx_realty_pi1_ListViewFactory::make(
            $this->piVars['listViewType'],
            $this->conf,
            $contentObject
        );
        // TODO: use tslib_content::readFlexformIntoConf when TYPO3 4.3 is required
        $listView->pi_initPIflexForm();

        $listView->setPiVars($this->sanitizeAndSplitListViewLimitation());

        return $listView->getUidForRecordNumber($recordPosition);
    }

    //////////////////////////////////////////
    // Functions for building the button URL
    //////////////////////////////////////////

    /**
     * Returns the URL for the buttons.
     *
     * @param int $recordPosition
     *        the position of the record the URL points to
     * @param int $recordUid
     *        the UID of the record the URL points to
     *
     * @return string the htmlspecialchared URL for the button, will not be empty
     */
    private function getButtonUrl($recordPosition, $recordUid)
    {
        $additionalParameters = $this->piVars;
        $additionalParameters['recordPosition'] = $recordPosition;
        $additionalParameters['showUid'] = $recordUid;
        $urlParameters = [
            'parameter' => $this->cObj->data['pid'],
            'additionalParams' => GeneralUtility::implodeArrayForUrl(
                $this->prefixId,
                $additionalParameters
            ),
            'useCacheHash' => true,
        ];

        return htmlspecialchars($this->cObj->typoLink_URL($urlParameters));
    }
}
