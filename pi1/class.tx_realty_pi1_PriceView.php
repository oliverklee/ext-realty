<?php

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This class renders the buying price or rent (depending on the object type)
 * of a single realty object.
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class tx_realty_pi1_PriceView extends tx_realty_pi1_FrontEndView
{
    /**
     * Returns this view as HTML.
     *
     * @param array $piVars piVars array, must contain the key "showUid" with a valid realty object UID as value
     *
     * @return string HTML for this view, will be empty if the realty object
     *                with the provided UID has no prices for the defined object
     *                type
     */
    public function render(array $piVars = [])
    {
        /** @var tx_realty_Mapper_RealtyObject $mapper */
        $mapper = Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_RealtyObject::class);
        /** @var tx_realty_Model_RealtyObject $realtyObject */
        $realtyObject = $mapper->find($piVars['showUid']);
        if ($this->getConfValueBoolean('priceOnlyIfAvailable')
            && $realtyObject->isRentedOrSold()
        ) {
            return '';
        }

        switch ($realtyObject->getProperty('object_type')) {
            case tx_realty_Model_RealtyObject::TYPE_FOR_SALE:
                $keyToShow = 'buying_price';
                $keyToHide = 'rent_excluding_bills';
                break;
            case tx_realty_Model_RealtyObject::TYPE_FOR_RENT:
                $keyToShow = 'rent_excluding_bills';
                $keyToHide = 'buying_price';
                break;
            default:
                $keyToShow = '';
                $keyToHide = '';
        }

        if ($keyToShow !== '' && $keyToHide !== '') {
            /** @var \tx_realty_pi1_Formatter $formatter */
            $formatter = GeneralUtility::makeInstance(
                \tx_realty_pi1_Formatter::class,
                $piVars['showUid'],
                $this->conf,
                $this->cObj
            );
            $hasValidContent = $this->setOrDeleteMarkerIfNotEmpty(
                $keyToShow,
                $formatter->getProperty($keyToShow),
                '',
                'field_wrapper'
            );
            $this->hideSubparts($keyToHide, 'field_wrapper');
        } else {
            $hasValidContent = false;
        }

        return $hasValidContent ? $this->getSubpart('FIELD_WRAPPER_PRICE') : '';
    }
}
