<?php

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This class renders error messages.
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 */
class tx_realty_pi1_ErrorView extends tx_realty_pi1_FrontEndView
{
    /**
     * Returns the HTML for an error view.
     *
     * @param string[] $errorMessage
     *        key of the error message to render (must be in the first element a numeric array, the rest is ignored)
     *
     * @return string HTML of the error message, will not be empty
     */
    public function render(array $errorMessage = [])
    {
        if ($errorMessage[0] === 'message_please_login') {
            $message = $this->getLinkedPleaseLogInMessage();
        } else {
            $message = $this->translate($errorMessage[0]);
        }

        $this->setMarker('error_message', $message);

        return $this->getSubpart('ERROR_VIEW');
    }

    /**
     * Returns the linked please-login error message. The message is linked to
     * the page configured in "loginPID".
     *
     * @return string linked please-login error message with a redirect URL to
     *                the current page, will not be empty
     */
    private function getLinkedPleaseLogInMessage()
    {
        $piVars = $this->piVars;
        unset($piVars['DATA']);

        $redirectUrl = GeneralUtility::locationHeaderUrl(
            $this->cObj->typoLink_URL([
                'parameter' => $this->getFrontEndController()->id,
                'additionalParams' => GeneralUtility::implodeArrayForUrl(
                    $this->prefixId,
                    $piVars,
                    '',
                    true,
                    true
                ),
                'useCacheHash' => true,
            ])
        );

        return $this->cObj->typoLink(
            $this->translate('message_please_login'),
            [
                'parameter' => $this->getConfValueInteger('loginPID'),
                'additionalParams' => GeneralUtility::implodeArrayForUrl(
                    '',
                    ['redirect_url' => $redirectUrl]
                ),
            ]
        );
    }
}
