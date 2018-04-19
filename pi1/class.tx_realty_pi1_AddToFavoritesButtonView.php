<?php
/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * This class renders the add-to-favorites button.
 *
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
