<?php
/***************************************************************
* Copyright notice
*
* (c) 2008-2010 Bernd Schönbach <bernd@oliverklee.de>
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

require_once(t3lib_extMgm::extPath('realty') . 'lib/tx_realty_constants.php');

/**
 * Class 'tx_realty_Model_FrontEndUser' for the 'realty' extension.
 *
 * This class represents a front-end user and adds functions to check the number
 * of objects a user has or can enter.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Bernd Schönbach <bernd@oliverklee.de>
 */
class tx_realty_Model_FrontEndUser extends tx_oelib_Model_FrontEndUser {
	/**
	 * @var integer the number of objects belonging to the current user
	 */
	private $numberOfObjects = 0;

	/**
	 * @var boolean whether the number of objects has already been calculated
	 */
	private $numberOfObjectsHasBeenCalculated = FALSE;

	/**
	 * Returns the maximum number of objects the user is allowed to enter.
	 *
	 * @return integer the maximum number of objects the user is allowed to
	 *                 enter, will be >= 0
	 */
	public function getTotalNumberOfAllowedObjects() {
		return $this->getAsInteger('tx_realty_maximum_objects');
	}

	/**
	 * Returns the number of objects the user owns, including the hidden
	 * ones.
	 *
	 * @return integer the number of objects belonging to this user, will be zero
	 *                 if the user has no objects
	 */
	public function getNumberOfObjects() {
		if (!$this->numberOfObjectsHasBeenCalculated) {
			$whereClause = REALTY_TABLE_OBJECTS . '.owner=' . $this->getUid() .
				tx_oelib_db::enableFields(REALTY_TABLE_OBJECTS, 1);

			$dbData = tx_oelib_db::selectSingle(
				'COUNT(*) AS number',
				REALTY_TABLE_OBJECTS,
				$whereClause
			);

			$this->numberOfObjects = $dbData['number'];
			$this->numberOfObjectsHasBeenCalculated = TRUE;
		}

		return $this->numberOfObjects;
	}

	/**
	 * Returns the number of objects a user still can enter, depending on the
	 * maximum number set and the number of objects a user already has stored in
	 * the DB.
	 *
	 * @return integer the number of objects a user can enter, will be >= 0
	 */
	public function getObjectsLeftToEnter() {
		return max(
			($this->getTotalNumberOfAllowedObjects()
				- $this->getNumberOfObjects()),
			0
		);
	}

	/**
	 * Checks whether the user is allowed to enter any objects.
	 *
	 * @return boolean TRUE if the user is allowed to enter objects, FALSE
	 *                 otherwise
	 */
	public function canAddNewObjects() {
		return (($this->getTotalNumberOfAllowedObjects() == 0)
			|| ($this->getObjectsLeftToEnter() > 0));
	}

	/**
	 * Forces the function getNumberOfObjects to recalculate the number of
	 * objects.
	 */
	public function resetObjectsHaveBeenCalculated() {
		$this->numberOfObjectsHasBeenCalculated = FALSE;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/Model/class.tx_realty_Model_FrontEndUser.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/Model/class.tx_realty_Model_FrontEndUser.php']);
}
?>