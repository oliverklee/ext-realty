<?php
/***************************************************************
* Copyright notice
*
* (c) 2008 Bernd Schönbach <bernd@oliverklee.de>
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

require_once(t3lib_extMgm::extPath('realty') . 'Model/class.tx_realty_Model_FrontEndUser.php');

require_once(t3lib_extMgm::extPath('oelib') . 'Mapper/class.tx_oelib_Mapper_FrontEndUser.php');

/**
 * Class 'tx_realty_Mapper_FrontEndUser' for the 'realty' extension.
 *
 * This class represents a mapper for front-end users.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Bernd Schönbach <bernd@oliverklee.de>
 */
class tx_realty_Mapper_FrontEndUser extends tx_oelib_Mapper_FrontEndUser {
	/**
	 * Creates a realty front-end user model and fills it with the provided data.
	 *
	 * @param array the data with which the model should be filled, may be empty
	 *
	 * @return tx_realty_Model_FrontEndUser the filled user model
 	 */
	protected function createAndFillModel(array $data) {
		$model = t3lib_div::makeInstance('tx_realty_Model_FrontEndUser');
		$model->setData($data);

		return $model;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/Mapper/class.tx_realty_Mapper_FrontEndUser.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/Mapper/class.tx_realty_Mapper_FrontEndUser.php']);
}
?>