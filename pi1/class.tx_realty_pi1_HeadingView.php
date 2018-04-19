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
 * This class renders the heading of a single realty object.
 *
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 */
class tx_realty_pi1_HeadingView extends tx_realty_pi1_FrontEndView
{
    /**
     * Returns the heading view as HTML.
     *
     * @param array $piVars piVars array, must contain the key "showUid" with a valid
     *              realty object UID as value
     *
     * @return string HTML for the heading view or an empty string if the
     *                realty object with the provided UID has no title
     */
    public function render(array $piVars = [])
    {
        /** @var tx_realty_Mapper_RealtyObject $mapper */
        $mapper = Tx_Oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject');
        /** @var tx_realty_Model_RealtyObject $realtyObject */
        $realtyObject = $mapper->find($piVars['showUid']);
        $title = htmlspecialchars($realtyObject->getProperty('title'));

        $this->setOrDeleteMarkerIfNotEmpty('heading', $title, '', 'field_wrapper');

        return $this->getSubpart('FIELD_WRAPPER_HEADING');
    }
}
