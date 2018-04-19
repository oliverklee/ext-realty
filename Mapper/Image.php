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
 * This class represents a mapper for images.
 *
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class tx_realty_Mapper_Image extends Tx_Oelib_DataMapper
{
    /**
     * @var string the name of the database table for this mapper
     */
    protected $tableName = 'tx_realty_images';

    /**
     * @var string the model class name for this mapper, must not be empty
     */
    protected $modelClassName = 'tx_realty_Model_Image';

    /**
     * the (possible) relations of the created models in the format DB column name => mapper name
     *
     * @var string[]
     */
    protected $relations = [
        'object' => 'tx_realty_Mapper_RealtyObject',
    ];

    /**
     * Marks $image as deleted, saves it to the DB (if it has a UID) and deletes
     * the corresponding image file.
     *
     * @param Tx_Oelib_Model $image
     *        the image model to delete, must not be a memory-only dummy, must
     *        not be read-only
     *
     * @return void
     */
    public function delete(Tx_Oelib_Model $image)
    {
        /** @var tx_realty_Model_Image $image */
        if ($image->isDead()) {
            return;
        }

        $fileName = $image->getFileName();

        parent::delete($image);

        if ($fileName !== '') {
            $fullPath = PATH_site . tx_realty_Model_Image::UPLOAD_FOLDER .
                $fileName;
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
        }
    }
}
