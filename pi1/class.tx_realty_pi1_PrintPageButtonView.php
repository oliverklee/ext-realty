<?php

/**
 * This class renders the print button.
 *
 * @author Bernd Schönbach <bernd@oliverklee.de>
 */
class tx_realty_pi1_PrintPageButtonView extends tx_realty_pi1_FrontEndView
{
    /**
     * Returns the print button as HTML.
     *
     * @param array $piVars piVars array, may be empty
     *
     * @return string HTML for the print button, will not be empty
     */
    public function render(array $piVars = [])
    {
        return $this->getSubpart('FIELD_WRAPPER_PRINTPAGEBUTTON');
    }
}
