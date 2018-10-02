<?php

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This class represents a view that contains the status of an object.
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class tx_realty_pi1_StatusView extends tx_realty_pi1_FrontEndView
{
    /**
     * Returns the rendered view.
     *
     * @param array $piVars
     *        piVars, must contain the key "showUid" with a valid realty object
     *        UID as value
     *
     * @return string HTML for this view, will not be empty
     */
    public function render(array $piVars = [])
    {
        /** @var tx_realty_Mapper_RealtyObject $mapper */
        $mapper = Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_RealtyObject::class);
        /** @var tx_realty_Model_RealtyObject $realtyObject */
        $realtyObject = $mapper->find($piVars['showUid']);

        $statusClasses = [
            tx_realty_Model_RealtyObject::STATUS_VACANT => 'vacant',
            tx_realty_Model_RealtyObject::STATUS_RESERVED => 'reserved',
            tx_realty_Model_RealtyObject::STATUS_SOLD => 'sold',
            tx_realty_Model_RealtyObject::STATUS_RENTED => 'rented',
        ];

        $this->setMarker(
            'statusclass',
            $statusClasses[$realtyObject->getStatus()]
        );

        /** @var \tx_realty_pi1_Formatter $formatter */
        $formatter = GeneralUtility::makeInstance(
            \tx_realty_pi1_Formatter::class,
            $piVars['showUid'],
            $this->conf,
            $this->cObj
        );
        $this->setMarker('status', $formatter->getProperty('status'));

        return $this->getSubpart('FIELD_WRAPPER_STATUS');
    }
}
