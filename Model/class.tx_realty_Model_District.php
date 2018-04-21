<?php

/**
 * This class represents a district.
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class tx_realty_Model_District extends tx_realty_Model_AbstractTitledModel
{
    /**
     * Gets the city this district is part of.
     *
     * @return tx_realty_Model_City this district's city, will be NULL if no
     *                              city is associated with this district
     */
    public function getCity()
    {
        return $this->getAsModel('city');
    }

    /**
     * Sets this district's city.
     *
     * @param tx_realty_Model_City $city the city to set, may be NULL
     *
     * @return void
     */
    public function setCity(tx_realty_Model_City $city = null)
    {
        $this->set('city', $city);
    }
}
