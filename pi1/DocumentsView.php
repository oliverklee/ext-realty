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
 * This class represents a view that contains the PDF documents attached to an object.
 *
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class tx_realty_pi1_DocumentsView extends tx_realty_pi1_FrontEndView
{
    /**
     * Returns the rendered view.
     *
     * @param array $piVars
     *        piVars, must contain the key "showUid" with a valid realty object
     *        UID as value
     *
     * @return string HTML for this view or an empty string if the realty object
     *                with the provided UID has no documents
     */
    public function render(array $piVars = array())
    {
        /** @var tx_realty_Mapper_RealtyObject $realtyObjectMapper */
        $realtyObjectMapper = Tx_Oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject');
        /** @var tx_realty_Model_RealtyObject $realtyObject */
        $realtyObject = $realtyObjectMapper->find($piVars['showUid']);
        $documents = $realtyObject->getDocuments();
        if ($documents->isEmpty()) {
            return '';
        }

        $result = '';
        /** @var tx_realty_Model_Document $document */
        foreach ($documents as $document) {
            $link = $this->cObj->typoLink(
                htmlspecialchars($document->getTitle()),
                array('parameter' => tx_realty_Model_Document::UPLOAD_FOLDER . $document->getFileName())
            );

            $this->setMarker('document_file', $link);
            $result .= $this->getSubpart('DOCUMENT_ITEM');
        }

        $this->setSubpart('DOCUMENT_ITEM', $result);

        return $this->getSubpart('FIELD_WRAPPER_DOCUMENTS');
    }
}
