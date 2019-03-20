<?php

/**
 * This class renders the back button.
 *
 * @author Bernd Schönbach <bernd@oliverklee.de>
 */
class tx_realty_pi1_BackButtonView extends tx_realty_pi1_FrontEndView
{
    /**
     * Returns the back button.
     *
     * @param array $piVars piVars array, may be empty
     *
     * @return string HTML for the back button, will not be empty
     */
    public function render(array $piVars = [])
    {
        return $this->getSubpart('FIELD_WRAPPER_BACKBUTTON');
    }
}
