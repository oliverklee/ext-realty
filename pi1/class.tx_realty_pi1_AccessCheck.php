<?php
/***************************************************************
* Copyright notice
*
* (c) 2008-2013 Saskia Metzler <saskia@merlin.owl.de>
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
 * This class provides functionality to check whether a front-end user has
 * access to a front-end page.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 */
class tx_realty_pi1_AccessCheck {
	/**
	 * Checks access for the provided type of view and the current piVars.
	 *
	 * @param string $flavor
	 *        the flavor for which to check the access, must be within the allowed values for "what_to_display"
	 * @param array $piVars
	 *        Form data array with the keys "showUid" and "delete" which can contain the UID of the object to check the access for.
	 *        The fe_editor and image_upload can only be checked properly if "showUid" is provided. A UID provided with "delete"
	 *        is needed for the my_objects view.
	 *
	 * @throws tx_oelib_Exception_AccessDenied
	 *         if access is denied, with the reason of denying as a locallang key
	 *
	 * @return void
	 */
	public function checkAccess($flavor, array $piVars = array()) {
		switch ($flavor) {
			case 'fe_editor':
				$this->isFrontEndUserLoggedIn();
				$this->realtyObjectExistsInDatabase($piVars['showUid']);
				$this->frontEndUserOwnsObject($piVars['showUid']);
				$this->checkObjectLimit($piVars['showUid']);
				break;
			case 'image_upload':
				$this->isFrontEndUserLoggedIn();
				$this->isRealtyObjectUidProvided($piVars['showUid']);
				$this->realtyObjectExistsInDatabase($piVars['showUid']);
				$this->frontEndUserOwnsObject($piVars['showUid']);
				break;
			case 'my_objects':
				$this->isFrontEndUserLoggedIn();
				$this->realtyObjectExistsInDatabase($piVars['delete']);
				$this->frontEndUserOwnsObject($piVars['delete']);
				break;
			case 'single_view':
				// When Bug #1480 is fixed, the access check should become
				// responsible for checking the configuration for
				// "requireLoginForSingleViewPage" and then only check whether
				// a user is logged in if this is at all necessary.
				$this->isFrontEndUserLoggedIn();
				break;
			default:
				break;
		}
	}

	/**
	 * Checks whether a front-end user is logged in. Sets a 403 header and
	 * throws the corresponding error message key if no user is logged in.
	 *
	 * @throws tx_oelib_Exception_AccessDenied if no front-end user is logged in
	 *
	 * @return void
	 */
	private function isFrontEndUserLoggedIn() {
		if (!tx_oelib_FrontEndLoginManager::getInstance()->isLoggedIn()) {
			tx_oelib_headerProxyFactory::getInstance()->getHeaderProxy()
				->addHeader('Status: 403 Forbidden');
			throw new tx_oelib_Exception_AccessDenied('message_please_login', 1333036432);
		}
	}

	/**
	 * Checks whether a non-zero UID for the realty object was provided.
	 *
	 * @throws tx_oelib_Exception_AccessDenied if the realty object UID is zero
	 *
	 * @param integer $realtyObjectUid UID of the object, must be >= 0
	 *
	 * @return void
	 */
	private function isRealtyObjectUidProvided($realtyObjectUid) {
		if ($realtyObjectUid > 0) {
			return;
		}

		tx_oelib_headerProxyFactory::getInstance()->getHeaderProxy()
			->addHeader('Status: 404 Not Found');
		throw new tx_oelib_Exception_AccessDenied('message_noResultsFound_image_upload', 1333036450);
	}

	/**
	 * Checks whether the realty object exists in the database and is
	 * non-deleted. A hidden object is considered to be exsistent. A zero UID
	 * is considered to stand for a new realty record and therefore accepted.
	 *
	 * @throws tx_oelib_Exception_AccessDenied if the realty object does not
	 *                                         exist in the database
	 *
	 * @param integer $realtyObjectUid UID of the object, must be >= 0
	 *
	 * @return void
	 */
	private function realtyObjectExistsInDatabase($realtyObjectUid) {
		if (($realtyObjectUid == 0)
			|| tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
				->existsModel($realtyObjectUid, TRUE)
		) {
			return;
		}

		tx_oelib_headerProxyFactory::getInstance()->getHeaderProxy()
			->addHeader('Status: 404 Not Found');
		throw new tx_oelib_Exception_AccessDenied('message_noResultsFound_fe_editor', 1333036458);
	}

	/**
	 * Checks whether the front-end user is the owner and therefore authorized
	 * to access a realty record. New realty objects (with UID = 0) are
	 * considered to be editable by every logged-in user.
	 *
	 * @param integer $realtyObjectUid UID of the realty object for which to check whether a user is authorized, must be >= 0
	 *
	 * @throws tx_oelib_Exception_AccessDenied if the front-end user does not own the object
	 *
	 * @return void
	 */
	private function frontEndUserOwnsObject($realtyObjectUid) {
		if (($realtyObjectUid == 0)
			|| (tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
					->find($realtyObjectUid)->getProperty('owner')
				== tx_oelib_FrontEndLoginManager::getInstance()
					->getLoggedInUser('tx_realty_Mapper_FrontEndUser')->getUid()
			)
		) {
			return;
		}

		tx_oelib_headerProxyFactory::getInstance()->getHeaderProxy()
			->addHeader('Status: 403 Forbidden');
		throw new tx_oelib_Exception_AccessDenied('message_access_denied', 1333036471);
	}

	/**
	 * Checks if the logged-in front-end user is allowed to enter new objects.
	 *
	 * @param integer $realtyObjectUid UID of the object, must be >= 0
	 *
	 * @throws tx_oelib_Exception_AccessDenied if the front-end user is not allowed to enter a new object
	 *
	 * @return void
	 */
	private function checkObjectLimit($realtyObjectUid) {
		if ($realtyObjectUid > 0) {
			return;
		}
		if (tx_oelib_FrontEndLoginManager::getInstance()
			->getLoggedInUser('tx_realty_Mapper_FrontEndUser')
			->canAddNewObjects()
		) {
			return;
		}

		tx_oelib_headerProxyFactory::getInstance()->getHeaderProxy()
			->addHeader('Status: 403 Forbidden');
		throw new tx_oelib_Exception_AccessDenied('message_no_objects_left', 1333036483);
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/realty/pi1/class.tx_realty_pi1_AccessCheck.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/realty/pi1/class.tx_realty_pi1_AccessCheck.php']);
}