<?php

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This class renders the contact button.
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 */
class tx_realty_pi1_ContactButtonView extends tx_realty_pi1_FrontEndView
{
    /**
     * Returns the contact button as HTML. For this, requires a "contactPID" to
     * be configured.
     *
     * @param array $piVars
     *        PiVars array, must contain the key "showUid" with a valid realty object UID or zero as value. Note that
     *     for zero, the linked contact form will not contain any realty object information.
     *
     * @return string HTML for the contact button or an empty string if the
     *                configured "contactPID" equals the current page or is not
     *                set at all
     */
    public function render(array $piVars = [])
    {
        if (!$this->hasConfValueInteger('contactPID')
            || $this->getConfValueInteger('contactPID') === (int)$this->getFrontEndController()->id
        ) {
            return '';
        }

        $contactUrl = htmlspecialchars($this->cObj->typoLink_URL([
            'parameter' => $this->getConfValueInteger('contactPID'),
            'additionalParams' => GeneralUtility::implodeArrayForUrl(
                '',
                [$this->prefixId => ['showUid' => $piVars['showUid']]]
            ),
            'useCacheHash' => true,
        ]));
        $this->setMarker('contact_url', $contactUrl);

        return $this->getSubpart('FIELD_WRAPPER_CONTACTBUTTON');
    }
}
