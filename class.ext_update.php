<?php
/***************************************************************
* Copyright notice
*
* (c) 2009-2013 Oliver Klee <typo3-coding@oliverklee.de>
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
 * This class offers functions to update the database from one version to
 * another and to reorganize the district-city relations.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class ext_update {
	/**
	 * Returns the update module content.
	 *
	 * @return string
	 *         the update module content, will be empty if nothing was updated
	 */
	public function main() {
		$result = '';

		try {
			if ($this->needsToUpdateDistricts()) {
				$result = $this->updateDistricts();
			}
			if ($this->needsToUpdateImages()) {
				$result .= $this->updateImages();
			}
			if ($this->needsToUpdateStatus()) {
				$result .= $this->updateStatus();
			}
		} catch (tx_oelib_Exception_Database $exception) {
		}

		return $result;
	}

	/**
	 * Returns whether the update module may be accessed.
	 *
	 * @return boolean
	 *         TRUE if the update module may be accessed, FALSE otherwise
	 */
	public function access() {
		if (
			!t3lib_extMgm::isLoaded('oelib') || !t3lib_extMgm::isLoaded('realty')
		) {
			return FALSE;
		}
		if (!tx_oelib_db::existsTable('tx_realty_objects')
			|| !tx_oelib_db::existsTable('tx_realty_cities')
			|| !tx_oelib_db::existsTable('tx_realty_districts')
			|| !tx_oelib_db::existsTable('tx_realty_images')
		) {
			return FALSE;
		}

		try {
			$result = $this->needsToUpdateDistricts()
				|| $this->needsToUpdateImages() || $this->needsToUpdateStatus();
		} catch (tx_oelib_Exception_Database $exception) {
			$result = FALSE;
		}

		return $result;
	}

	/**
	 * Checks whether the district -> city relations need to be updated.
	 *
	 * @return boolean TRUE if the relation needs to be updated, FALSE otherwise
	 */
	private function needsToUpdateDistricts() {
		if (!tx_oelib_db::tableHasColumn('tx_realty_districts', 'city')) {
			return FALSE;
		}

		$districtsWithExactlyOneCity = $this->findDistrictsToAssignCity();

		return !empty($districtsWithExactlyOneCity);
	}

	/**
	 * Updates the district -> city relations.
	 *
	 * @return string output of the update function, will not be empty
	 */
	private function updateDistricts() {
		$result = '<h2>Updating district-city relations</h2>' . LF .
			'<table summary="districts and cities">' . LF .
			'<thead>' . LF .
			'<tr><th>District</th><th>City</th></tr>' . LF .
			'</thead>' . LF .
			'<tbody>' . LF;

		$cityCache = array();

		foreach ($this->findDistrictsToAssignCity() as $uids) {
			$districtUid = $uids['district'];
			$cityUid = $uids['city'];

			tx_oelib_db::update(
				'tx_realty_districts', 'uid = ' . $districtUid,
				array('city' => $cityUid)
			);

			$district = tx_oelib_db::selectSingle(
				'title', 'tx_realty_districts', 'uid = ' . $districtUid
			);
			if (!isset($cityCache[$cityUid])) {
				$city = tx_oelib_db::selectSingle(
					'title',  'tx_realty_cities', 'uid = ' . $cityUid
				);

				$cityCache[$cityUid] = $city['title'];
			}

			$result .= '<tr><td>' . htmlspecialchars($district['title']) .
				'</td><td>' . htmlspecialchars($cityCache[$cityUid]) .
				'</td></tr>' . LF;
		}


		$result .= '</tbody>' . LF . '</table>';

		return $result;
	}

	/**
	 * Finds all districts that have no city assigned yet, but have have exactly
	 * one city in the objects table.
	 *
	 * @return array two-dimensional array, the second dimension having the keys
	 *               "city" and "district" with the corresponding UIDs, will be
	 *               empty if there are no matches
	 */
	private function findDistrictsToAssignCity() {
		$districtsWithoutCity = tx_oelib_db::selectColumnForMultiple(
			'uid', 'tx_realty_districts',
			'city = 0' . tx_oelib_db::enableFields('tx_realty_districts')
		);
		if (empty($districtsWithoutCity)) {
			return array();
		}

		return tx_oelib_db::selectMultiple(
			'city, district',
			'tx_realty_objects',
			'district IN ('. implode(',', $districtsWithoutCity) . ') AND city > 0' .
				tx_oelib_db::enableFields('tx_realty_objects'),
			'district HAVING COUNT(DISTINCT city) = 1',
			'city'
		);
	}

	/**
	 * Checks whether the image -> object relations need to be updated.
	 *
	 * @return boolean TRUE if the relation needs to be updated, FALSE otherwise
	 */
	private function needsToUpdateImages() {
		if (!tx_oelib_db::tableHasColumn('tx_realty_images', 'realty_object_uid')
			|| !tx_oelib_db::tableHasColumn('tx_realty_images', 'object')
		) {
			return FALSE;
		}

		return tx_oelib_db::existsRecord(
			'tx_realty_images',
			'realty_object_uid > 0 AND object = 0'
		);
	}

	/**
	 * Updates the image -> object relations.
	 *
	 * @return string output of the update function, will not be empty
	 */
	private function updateImages() {
		$result = '<h2>Updating image-object relations</h2>' . LF;

		$GLOBALS['TYPO3_DB']->sql_query(
			'UPDATE tx_realty_images SET object = realty_object_uid ' .
				'WHERE realty_object_uid > 0 AND object = 0'
		);
		$numberOfAffectedRows = $GLOBALS['TYPO3_DB']->sql_affected_rows();

		$result .= '<p>Updated ' . $numberOfAffectedRows . ' image records.</p>';

		return $result;
	}

	/**
	 * Checks whether the status field need to be updated.
	 *
	 * @return boolean TRUE if the status needs to be updated, FALSE otherwise
	 */
	private function needsToUpdateStatus() {
		if (!tx_oelib_db::tableHasColumn('tx_realty_objects', 'rented')
			|| !tx_oelib_db::tableHasColumn('tx_realty_objects', 'status')
		) {
			return FALSE;
		}

		return tx_oelib_db::existsRecord(
			'tx_realty_objects', 'rented = 1 AND status = 0'
		);
	}

	/**
	 * Updates the "status" field (from the "rented" field).
	 *
	 * @return string output of the update function, will not be empty
	 */
	private function updateStatus() {
		$result = '<h2>Updating the object status</h2>' . LF;

		$GLOBALS['TYPO3_DB']->sql_query(
			'UPDATE tx_realty_objects SET status = ' .
				tx_realty_Model_RealtyObject::STATUS_RENTED .
				' WHERE rented = 1 AND status = 0'
		);
		$numberOfAffectedRows = $GLOBALS['TYPO3_DB']->sql_affected_rows();

		$result .= '<p>Updated ' . $numberOfAffectedRows . ' object records.</p>';

		return $result;
	}

}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/realty/class.ext_update.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/realty/class.ext_update.php']);
}
?>