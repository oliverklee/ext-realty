<?php

/**
 * This class renders the address of a single realty object.
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 */
class tx_realty_pi1_AddressView extends tx_realty_pi1_FrontEndView
{
    /**
     * Returns the address view as HTML.
     *
     * @param array $piVars piVars array, must contain the key "showUid" with a valid realty object UID as value
     *
     * @return string HTML for the address view or an empty string if the
     *                realty object with the provided UID has no address at all
     */
    public function render(array $piVars = [])
    {
        /** @var tx_realty_Mapper_RealtyObject $mapper */
        $mapper = Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_RealtyObject::class);
        /** @var tx_realty_Model_RealtyObject $object */
        $object = $mapper->find($piVars['showUid']);
        $address = $object->getAddressAsHtml();

        $this->setOrDeleteMarkerIfNotEmpty(
            'address',
            $address,
            '',
            'field_wrapper'
        );

        return $this->getSubpart('FIELD_WRAPPER_ADDRESS');
    }
}
