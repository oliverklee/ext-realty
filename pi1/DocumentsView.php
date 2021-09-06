<?php

/**
 * This class represents a view that contains the PDF documents attached to an object.
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class tx_realty_pi1_DocumentsView extends \tx_realty_pi1_FrontEndView
{
    /**
     * Returns the rendered view.
     *
     * @param array $piVars piVars, must contain the key "showUid" with a valid realty object UID as value
     *
     * @return string HTML for this view or an empty string if the realty object
     *                with the provided UID has no attachment
     */
    public function render(array $piVars = [])
    {
        /** @var tx_realty_Mapper_RealtyObject $realtyObjectMapper */
        $realtyObjectMapper = \Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_RealtyObject::class);
        /** @var tx_realty_Model_RealtyObject $realtyObject */
        $realtyObject = $realtyObjectMapper->find((int)$piVars['showUid']);
        $attachments = $realtyObject->getPdfAttachments();
        if (empty($attachments)) {
            return '';
        }

        $renderedDocuments = [];

        foreach ($attachments as $attachment) {
            $renderedDocuments[] = $this->renderSingleDocument($attachment->getPublicUrl(), $attachment->getTitle());
        }

        $this->setSubpart('DOCUMENT_ITEM', implode("\n", $renderedDocuments));

        return $this->getSubpart('FIELD_WRAPPER_DOCUMENTS');
    }

    /**
     * @param string $relativeUrl
     * @param string $title
     *
     * @return string
     */
    protected function renderSingleDocument($relativeUrl, $title)
    {
        $link = $this->cObj->typoLink(\htmlspecialchars($title, ENT_COMPAT | ENT_HTML5), ['parameter' => $relativeUrl]);
        $this->setMarker('document_file', $link);

        return $this->getSubpart('DOCUMENT_ITEM');
    }
}
