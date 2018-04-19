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
 * This class represents a view for testing purposes.
 *
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 */
class tx_realty_tests_fixtures_testingFrontEndView extends tx_realty_pi1_FrontEndView
{
    /**
     * Renders the view and returns its content.
     *
     * @param array $piVars form data array (piVars)
     *
     * @return string the view's content
     */
    public function render(array $piVars = [])
    {
        return 'Hi, I am the testingFrontEndView!';
    }
}
