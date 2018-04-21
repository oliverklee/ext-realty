<?php

/**
 * This class renders the add-to-favorites button.
 *
 * @author Bernd Schönbach <bernd@oliverklee.de>
 */
class tx_realty_pi1_AddToFavoritesButtonView extends tx_realty_pi1_FrontEndView
{
    /**
     * Returns the add-to-favorites button.
     *
     * "favoritesPID" is required to be configured.
     *
     * @param array $piVars piVars array, must contain the key "showUid" with a valid realty object UID as value
     *
     * @return string HTML for the buttons, will not be empty
     */
    public function render(array $piVars = [])
    {
        $favoritesUrl = htmlspecialchars(
            $this->cObj->typoLink_URL(
                [
                    'parameter' => $this->getConfValueInteger('favoritesPID'),
                    'useCacheHash' => true,
                ]
            )
        );

        $this->setMarker('favorites_url', $favoritesUrl);
        $this->setMarker('uid', $piVars['showUid']);

        return $this->getSubpart('FIELD_WRAPPER_ADDTOFAVORITESBUTTON');
    }
}
