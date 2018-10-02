<?php

namespace OliverKlee\Realty\BackEnd;

/**
 * This class provides functions for the TCA.
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class Tca
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
        $items = [['', 0]];

        /** @var \tx_realty_Mapper_District $mapper */
        $mapper = \Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_District::class);
        $districts = $mapper->findAllByCityUidOrUnassigned((int)$data['row']['city']);
        /** @var \tx_realty_Model_District $district */
        foreach ($districts as $district) {
            $items[] = [$district->getTitle(), $district->getUid()];
        }

        $data['items'] = $items;

        return $data;
    }
}
