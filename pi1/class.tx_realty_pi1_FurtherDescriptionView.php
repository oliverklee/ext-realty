<?php

/**
 * This class renders the description of "equipment" and "location" and the
 * "misc" text field of a single realty object.
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 */
class tx_realty_pi1_FurtherDescriptionView extends tx_realty_pi1_FrontEndView
{
    /**
     * Returns the further-description view as HTML.
     *
     * @param array $piVars piVars array, must contain the key "showUid" with a valid realty object UID as value
     *
     * @return string HTML for the further-description view will be epmty if
     *                the realty object with the provided UID has neither data
     *                in "equipment" nor "loaction" nor "misc"
     */
    public function render(array $piVars = [])
    {
        $hasContent = false;
        /** @var tx_realty_Mapper_RealtyObject $mapper */
        $mapper = Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_RealtyObject::class);
        /** @var tx_realty_Model_RealtyObject $model */
        $model = $mapper->find($piVars['showUid']);

        foreach (['equipment', 'location', 'misc'] as $key) {
            $value = $this->pi_RTEcssText($model->getProperty($key));

            $hasContent = $this->setOrDeleteMarkerIfNotEmpty($key, $value, '', 'field_wrapper') || $hasContent;
        }

        return $hasContent ? $this->getSubpart('FIELD_WRAPPER_FURTHERDESCRIPTION') : '';
    }
}
