<?php
/***************************************************************
* Copyright notice
*
* (c) 2010-2013 Bernd Schönbach <bernd.schoenbach@googlemail.com>
* All rights reserved
*
* This script is part of the TYPO3 project. The TYPO3 project is
* free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation; either version 2 of the License, or
* (at your option) any later version.
*
* The GNU General Public License can be found at
* http://www.gnu.org/copyleft/gpl.html.
*
* This script is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * This class represents a mapper for related documents.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Bernd Schönbach <bernd.schoenbach@googlemail.com>
 */
class tx_realty_Mapper_Document extends tx_oelib_DataMapper {
	/**
	 * @var string the name of the database table for this mapper
	 */
	protected $tableName = 'tx_realty_documents';

	/**
	 * @var string the model class name for this mapper, must not be empty
	 */
	protected $modelClassName = 'tx_realty_Model_Document';

	/**
	 * the (possible) relations of the created models in the format
	 * DB column name => mapper name
	 *
	 * @var array
	 */
	protected $relations = array(
		'object' => 'tx_realty_Mapper_RealtyObject',
	);

	/**
	 * Marks $document as deleted, saves it to the DB (if it has a UID) and deletes
	 * the corresponding document file.
	 *
	 * @param tx_realty_Model_Document $document
	 *        the document model to delete, must not be a memory-only dummy, must
	 *        not be read-only
	 *
	 * @return void
	 */
	public function delete(tx_realty_Model_Document $document) {
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

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/realty/Mapper/Document.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/realty/Mapper/Document.php']);
}