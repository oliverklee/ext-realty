<?php

/**
 * This class renders the description of a single realty object.
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 */
class tx_realty_pi1_DescriptionView extends tx_realty_pi1_FrontEndView
{
    /**
     * Returns the description view as HTML.
     *
     * @param array $piVars piVars array, must contain the key "showUid" with a valid realty object UID as value
     *
     * @return string HTML for the description view, will be empty if the
     *                realty object with the provided UID has no description
     */
    public function render(array $piVars = [])
    {
        /** @var tx_realty_Mapper_RealtyObject $mapper */
        $mapper = Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_RealtyObject::class);
        /** @var tx_realty_Model_RealtyObject $object */
        $object = $mapper->find($piVars['showUid']);
        $description = $this->pi_RTEcssText($object->getProperty('description'));

        $this->setOrDeleteMarkerIfNotEmpty(
            'description',
            $description,
            '',
            'field_wrapper'
        );

        return $this->getSubpart('FIELD_WRAPPER_DESCRIPTION');
    }
}
