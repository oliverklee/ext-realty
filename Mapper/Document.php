<?php

/**
 * This class represents a mapper for related documents.
 *
 * @author Bernd Schönbach <bernd.schoenbach@googlemail.com>
 */
class tx_realty_Mapper_Document extends Tx_Oelib_DataMapper
{
    /**
     * @var string the name of the database table for this mapper
     */
    protected $tableName = 'tx_realty_documents';

    /**
     * @var string the model class name for this mapper, must not be empty
     */
    protected $modelClassName = 'tx_realty_Model_Document';

    /**
     * the (possible) relations of the created models in the format DB column name => mapper name
     *
     * @var string[]
     */
    protected $relations = [
        'object' => 'tx_realty_Mapper_RealtyObject',
    ];

    /**
     * Marks $document as deleted, saves it to the DB (if it has a UID) and deletes
     * the corresponding document file.
     *
     * @param Tx_Oelib_Model $document
     *        the document model to delete, must not be a memory-only dummy, must
     *        not be read-only
     *
     * @return void
     */
    public function delete(Tx_Oelib_Model $document)
    {
        /** @var tx_realty_Model_Document $document */
        if ($document->isDead()) {
            return;
        }

        $fileName = $document->getFileName();

        parent::delete($document);

        if ($fileName !== '') {
            $fullPath = PATH_site . tx_realty_Model_Document::UPLOAD_FOLDER .
                $fileName;
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
        }
    }
}
