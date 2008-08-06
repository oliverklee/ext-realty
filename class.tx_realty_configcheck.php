<?php
/***************************************************************
* Copyright notice
*
* (c) 2008 Saskia Metzler <saskia@merlin.owl.de>
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
 * Class 'tx_realty_configcheck' for the 'realty' extension.
 *
 * This class checks the Realty Manager configuration for basic sanity.
 *
 * @package		TYPO3
 * @subpackage	tx_realty
 *
 * @author		Saskia Metzler <saskia@merlin.owl.de>
 */

require_once(t3lib_extMgm::extPath('oelib').'class.tx_oelib_configcheck.php');

require_once(t3lib_extMgm::extPath('realty').'lib/tx_realty_constants.php');

class tx_realty_configcheck extends tx_oelib_configcheck {
	/**
	 * Checks the configuration for the gallery of the realty manager.
	 */
	public function check_tx_realty_pi1_gallery() {
		$this->checkCommonFrontEndSettings();
		$this->checkImageSizeValuesForGallery();
	}

	/**
	 * Checks the configuration for the city selector of the realty manager.
	 */
	public function check_tx_realty_pi1_city_selector() {
		$this->checkCommonFrontEndSettings();
		$this->checkCitySelectorPid();
	}

	/**
	 * Checks the configuration for the list view of the realty manager.
	 */
	public function check_tx_realty_pi1_realty_list() {
		$this->checkCommonFrontEndSettings();
		$this->checkCheckboxesFilter();
		$this->checkFieldsInSingleView();
		$this->checkImageSizeValuesForListView();
		$this->checkPagesToDisplay();
		$this->checkGalleryPid();
		$this->checkRecursive();
		$this->checkShowAddressOfObjects();
		$this->checkSortCriteria();
		$this->checkNumberOfDecimals();
		$this->checkCurrencyUnit();
		$this->checkSingleViewPid();
		$this->checkFavoritesPid();
	}

	/**
	 * Checks the configuration for the favorites view of the realty manager.
	 */
	public function check_tx_realty_pi1_favorites() {
		$this->check_tx_realty_pi1_realty_list();
		$this->checkContactPid();
		$this->checkFavoriteFieldsInSession();
		$this->checkImageSizeValuesForListView();
	}

	/**
	 * Checks the configuration for the single view of the realty manager.
	 */
	public function check_tx_realty_pi1_single_view() {
		$this->checkCommonFrontEndSettings();
		$this->checkNumberOfDecimals();
		$this->checkCurrencyUnit();
		$this->checkRequireLoginForSingleViewPage();
		$this->checkGalleryPid();
		$this->checkLoginPid();
		$this->checkImageSizeValuesForSingleView();
		$this->checkShowAddressOfObjects();
		$this->checkFavoritesPid();
	}

	/**
	 * Checks the settings that are common to all FE plug-in variations of this
	 * extension: CSS styled content, static TypoScript template included,
	 * template file, CSS file, salutation mode, and CSS class names.
	 */
	private function checkCommonFrontEndSettings() {
		$this->checkStaticIncluded();
		$this->checkCssStyledContent();
		$this->checkTemplateFile();
		$this->checkSalutationMode();
		$this->checkCssFileFromConstants();
		$this->checkCssClassNames();
		$this->checkDateFormat();
		$this->checkWhatToDisplay();
		$this->checkLocale();
	}

	/**
	 * Checks the setting of the configuration value what_to_display.
	 */
	private function checkWhatToDisplay() {
		$this->checkIfSingleInSetNotEmpty(
			'what_to_display',
			true,
			'sDEF',
			'This value specifies the type of the realty plug-in to display. '
				.'If it is not set correctly, it is ignored and the list view '
				.'is displayed.',
			array(
				'gallery',
				'city_selector',
				'favorites',
				'realty_list'
			)
		);
	}

	/**
	 * Checks the setting for the currency unit.
	 */
	private function checkCurrencyUnit() {
		$this->checkForNonEmptyString(
			'currencyUnit',
			false,
			'',
			'This value specifies the currency of displayed prices. '
				.'If this value is empty, prices will be displayed without a '
				.'currency symbol.'
		);
	}

	/**
	 * Checks the setting for the date format.
	 */
	private function checkDateFormat() {
		$this->checkForNonEmptyString(
			'dateFormat',
			false,
			'',
			'This determines the way dates and times are displayed. '
				.'If this is not set correctly, dates and times might '
				.'be mangled or not get displayed at all.'
		);
	}

	private function checkNumberOfDecimals() {
		$this->checkIfPositiveIntegerOrZero(
			'numberOfDecimals',
			true,
			'sDEF',
			'This value specifies the number of decimal digits for formatting '
				.'prices. If this value is invalid, the standard value of the '
				.'current locale is taken.'
		);
	}

	/**
	 * Checks whether values for image sizes in the list view are set.
	 */
	private function checkImageSizeValuesForListView() {
		$imageSizeItems =  array (
			'listImageMaxX',
			'listImageMaxY'
		);

		foreach ($imageSizeItems as $fieldName) {
			$this->checkIfPositiveInteger(
				$fieldName,
				false,
				'',
				'This value specifies image dimensions. Images will not be '
					.'displayed correctly if this value is invalid.'
			);
		}
	}

		/**
	 * Checks whether values for image sizes in the single view are set.
	 */
	private function checkImageSizeValuesForSingleView() {
		$imageSizeItems =  array (
			'singleImageMaxX',
			'singleImageMaxY'
		);

		foreach ($imageSizeItems as $fieldName) {
			$this->checkIfPositiveInteger(
				$fieldName,
				false,
				'',
				'This value specifies image dimensions. Images will not be '
					.'displayed correctly if this value is invalid.'
			);
		}
	}

	/**
	 * Checks whether values for image sizes in the gallery are set.
	 */
	private function checkImageSizeValuesForGallery() {
		$imageSizeItems =  array (
			'galleryFullSizeImageX',
			'galleryFullSizeImageY',
			'galleryThumbnailX',
			'galleryThumbnailY'
		);

		foreach ($imageSizeItems as $fieldName) {
			$this->checkIfPositiveInteger(
				$fieldName,
				false,
				'',
				'This value specifies image dimensions. Images will not be '
					.'displayed correctly if this value is invalid.'
			);
		}
	}

	/**
	 * Checks the settings of fields in single view.
	 */
	private function checkFieldsInSingleView() {
		$this->checkIfMultiInSetNotEmpty(
			'fieldsInSingleViewTable',
			false,
			'',
			'This value specifies the fields which should be displayed in '
				.'single view. If this value is empty, the single view only '
				.'shows the title of an object.',
			$this->getDbColumnNames(REALTY_TABLE_OBJECTS)
		);
	}

	/**
	 * Checks the settings of favorite fields which should be stored in the
	 * session.
	 */
	private function checkFavoriteFieldsInSession() {
		$this->checkIfMultiInSetOrEmpty(
			'favoriteFieldsInSession',
			false,
			'',
			'This value specifies the field names that will be stored in the '
				.'session when displaying the favorites list. This value may be '
				.'empty. Wrong values cause empty fields in the session data '
				.'array.',
			$this->getDbColumnNames(REALTY_TABLE_OBJECTS)
		);
	}

	/**
	 * Checks the setting of the configuration value
	 * requireLoginForSingleViewPage.
	 */
	private function checkRequireLoginForSingleViewPage() {
		$this->checkIfBoolean(
			'requireLoginForSingleViewPage',
			false,
			'',
			'This value specifies whether a login is required to access the '
				.'single view page. It might be interpreted incorrectly if no '
				.'logical value was set.'
		);
	}

	/**
	 * Checks the setting for the login PID.
	 */
	private function checkLoginPid() {
		if ($this->objectToCheck->getConfValueBoolean(
				'requireLoginForSingleViewPage',
				's_template_special'
			)
		) {
			$this->checkIfSingleFePageNotEmpty(
				'loginPID',
				false,
				'',
				'This value specifies the login page and is needed if a login '
					.'is required. Users could not be directed to the login '
					.'page if this value is invalid.'
			);
		}
	}

	/**
	 * Checks the setting for the contact PID.
	 */
	private function checkContactPid() {
		$this->checkIfSingleFePageOrEmpty(
			'contactPID',
			false,
			'',
			'This value specifies the contact page which will be linked from '
				.'the favorites list. An invalid link will be created if this '
				.'value is invalid.'
		);
	}

	/**
	 * Checks the setting of the pages that contain realty records to be
	 * displayed.
	 */
	private function checkPagesToDisplay() {
		$this->checkIfPidListNotEmpty(
			'pages',
			true,
			'sDEF',
			'This value specifies the list of PIDs that contain the realty '
				.'records to be displayed. If this list is empty, there is only '
				.'a message about no search results displayed.'
		);
	}

	/**
	 * Checks the setting for the recursion level for the pages list.
	 */
	private function checkRecursive() {
		$this->checkIfPositiveIntegerOrZero(
			'recursive',
			true,
			'sDEF',
			'This value specifies the recursion level for the pages list. The '
				.'recursion can only be set to include subfolders of the '
				.'folders in "pages". It is impossible to access superior '
				.'folders with this option.'
		);
	}

	/**
	 * Checks the setting of the configuration value showAddressOfObjects.
	 */
	private function checkShowAddressOfObjects() {
		$this->checkIfBoolean(
			'showAddressOfObjects',
			true,
			'sDEF',
			'This value specifies whether the address of a realty object is '
				.'shown in the FE. It might be interpreted incorrectly if no '
				.'logical value was set.'
		);
	}

	/**
	 * Checks the setting of the checkboxes filter.
	 */
	private function checkCheckboxesFilter() {
		$this->checkIfSingleInTableOrEmpty(
			'checkboxesFilter',
			true,
			'sDEF',
			'This value specifies the name of the DB field to create the search '
				.'filter checkboxes from. Searching will not work properly if '
				.'non-database fields are set.',
			REALTY_TABLE_OBJECTS
		);
	}

	/**
	 * Checks the settings for the sort criteria.
	 */
	private function checkSortCriteria() {
		// checks whether the value is non-empty
		if ($this->objectToCheck->hasConfValueString('sortCriteria')) {
			$this->checkIfPositiveIntegerOrZero(
				'sortCriteria',
				true,
				'sDEF',
				'This value specifies the database field names by which a FE user '
					.'can sort the list view. This value is usually set via '
					.'flexforms.'
			);
		}
	}

	/**
	 * Checks the settings for the PID for the single view.
	 */
	private function checkSingleViewPid() {
		$this->checkIfSingleFePageNotEmpty(
			'singlePID',
			true,
			'sDEF',
			'This value specifies the PID of the page for the single view. If '
				.'this value is empty or invalid, the single view is shown on '
				.'the same page as the list view.'
		);
	}

	/**
	 * Checks the settings for the PID for the gallery.
	 */
	private function checkGalleryPid() {
		$this->checkIfSingleFePageNotEmpty(
			'galleryPID',
			true,
			'sDEF',
			'This value specifies the PID of the page with the gallery. If this '
				.'value is empty, the gallery will be disabled.'
		);
	}

	/**
	 * Checks the settings for the PID for the favorites view.
	 */
	private function checkFavoritesPid() {
		$this->checkIfSingleFePageNotEmpty(
			'favoritesPID',
			true,
			'sDEF',
			'This value specifies the PID of the page for the favorites view. '
				.'Favorites cannot be displayed if this value is invalid.'
		);
	}

	/**
	 * Checks the settings for the PID for the city selector.
	 */
	private function checkCitySelectorPid() {
		$this->checkIfSingleFePageNotEmpty(
			'citySelectorTargetPID',
			true,
			'sDEF',
			'This value specifies the PID of the target page for the city '
				.'selector. The city selector cannot be displayed if this value '
				.'is invalid.'
		);
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/class.tx_realty_configcheck.php']) {
	include_once ($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/class.tx_realty_configcheck.php']);
}
?>
