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
 * This class represents a mapper for cities.
 *
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class tx_realty_Mapper_City extends Tx_Oelib_DataMapper
{
    /**
     * @var string the name of the database table for this mapper
     */
    protected $tableName = 'tx_realty_cities';

    /**
     * @var string the model class name for this mapper, must not be empty
     */
    protected $modelClassName = 'tx_realty_Model_City';

    /**
     * the column names of additional string keys
     *
     * @var string[]
     */
    protected $additionalKeys = array('title');

    /**
     * Finds a city by its name.
     *
     * @throws Tx_Oelib_Exception_NotFound if there is no city with the
     *                                     given name
     *
     * @param string $name the name of the city to find, must not be empty
     *
     * @return tx_realty_Model_City the city with the given name
     */
    public function findByName($name)
    {
        return $this->findOneByKey('title', $name);
    }
}
