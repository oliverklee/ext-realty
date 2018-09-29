<?php

/**
 * This class creates a district drop-down for a selected city.
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class tx_realty_Ajax_DistrictSelector
{
    /**
     * Creates a drop-down for all districts within a city. Districts without a city will also be listed.
     *
     * At the top, an empty option with the value 0 will always be included.
     *
     * @param int $cityUid
     *        the UID of a city for which to get the districts, must be > 0,
     *        may also point to an inexistent record
     * @param bool $showWithNumbers
     *        if true, the number of matching objects will be displayed behind
     *        the district name, and districts without matches will be omitted;
     *        if false, the number of matches will not be displayed, and
     *        districts without matches will also be displayed
     *
     * @return string the HTML of the drop-down items with the districts, will not be empty
     */
    public static function render($cityUid, $showWithNumbers = false)
    {
        $options = '<option value="0">&nbsp;</option>';

        /** @var \tx_realty_Mapper_RealtyObject $objectMapper */
        $objectMapper = \Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_RealtyObject::class);

        /** @var \tx_realty_Mapper_District $districtMapper */
        $districtMapper = \Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_District::class);
        $districts = $districtMapper->findAllByCityUidOrUnassigned($cityUid);
        /** @var \tx_realty_Model_District $district */
        foreach ($districts as $district) {
            if ($showWithNumbers) {
                $numberOfMatches = $objectMapper->countByDistrict($district);
                if ($numberOfMatches === 0) {
                    continue;
                }
                $displayedNumber = ' (' . $numberOfMatches . ')';
            } else {
                $displayedNumber = '';
            }

            $options .= '<option value="' . $district->getUid() . '">' .
                htmlspecialchars($district->getTitle()) .
                $displayedNumber . '</option>' . LF;
        }

        return $options;
    }
}
