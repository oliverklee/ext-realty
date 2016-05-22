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
 * This class provides functions for the TCA.
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class tx_realty_Tca
{
    /**
     * Gets the districts for a certain city.
     *
     * @param array[] $data the TCEforms data, must at least contain [row][city]
     *
     * @return array[] the TCEforms data with the districts added
     */
    public function getDistrictsForCity(array $data)
    {
        $items = array(array('', 0));

        /** @var tx_realty_Mapper_District $mapper */
        $mapper = Tx_Oelib_MapperRegistry::get('tx_realty_Mapper_District');
        $districts = $mapper->findAllByCityUidOrUnassigned((int)$data['row']['city']);
        /** @var tx_realty_Model_District $district */
        foreach ($districts as $district) {
            $items[] = array($district->getTitle(), $district->getUid());
        }

        $data['items'] = $items;

        return $data;
    }
}
