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

require_once(PATH_formidableapi);

require_once(t3lib_extMgm::extPath('realty') . 'lib/tx_realty_constants.php');

/**
 * This class provides functions used in the realty plugin's forms.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class tx_realty_frontEndForm extends tx_realty_pi1_FrontEndView {
	/**
	 * @var tx_ameosformidable object that creates the form
	 */
	protected $formCreator = NULL;

	/**
	 * @var tx_realty_Model_RealtyObject realty object
	 */
	protected $realtyObject = NULL;

	/**
	 * @var integer UID of the currently edited object, zero if the object is
	 *              going to be a new database record.
	 */
	protected $realtyObjectUid = 0;

	/**
	 * @var boolean whether the constructor is called in test mode
	 */
	protected $isTestMode = FALSE;

	/**
	 * @var array this is used to fake form values for testing
	 */
	protected $fakedFormValues = array();

	/**
	 * @var string the path to the FORMidable XML file
	 */
	private $xmlPath;

	/**
	 * The constructor.
	 *
	 * @param array $configuration
	 *        TypoScript configuration for the plugin
	 * @param tslib_cObj $cObj
	 *        the parent cObj content, needed for the flexforms
	 * @param integer $uidOfObjectToEdit
	 *        UID of the object to edit, set to 0 to create a new database record, must not be negative
	 * @param string $xmlPath
	 *        path of the XML for the form, relative to this extension, must not begin with a slash and must not be empty
	 * @param boolean $isTestMode
	 *        whether the FE editor is instantiated in test mode
	 */
	public function __construct(
		array $configuration, tslib_cObj $cObj, $uidOfObjectToEdit, $xmlPath,
		$isTestMode = FALSE
	) {
		$this->isTestMode = $isTestMode;
		$this->realtyObjectUid = $uidOfObjectToEdit;
		$this->xmlPath = $xmlPath;

		$this->realtyObject = t3lib_div::makeInstance(
			'tx_realty_Model_RealtyObject', $this->isTestMode
		);
		$this->realtyObject->loadRealtyObject($this->realtyObjectUid, TRUE);

		parent::__construct($configuration, $cObj);
	}

	/**
	 * Frees as much memory that has been used by this object as possible.
	 */
	public function __destruct() {
		if (is_object($this->realtyObject)) {
			$this->realtyObject->__destruct();
		}

		unset($this->formCreator, $this->realtyObject);

		parent::__destruct();
	}

	/**
	 * Instantiates $this->formCreator (if it hasn't been created yet).
	 *
	 * This function does nothing if this object is running in test mode.
	 *
	 * @return void
	 */
	protected function makeFormCreator() {
		if ($this->formCreator || $this->isTestMode) {
			return;
		}

		$this->formCreator = t3lib_div::makeInstance('tx_ameosformidable');
		// FORMidable would produce an error message if it is initialized with
		// a non-existing UID.
		// The FORMidable object is never initialized for testing.
		if ($this->realtyObjectExistsInDatabase()) {
			$this->formCreator->init(
				$this,
				t3lib_extMgm::extPath('realty') . $this->xmlPath,
				($this->realtyObjectUid > 0) ? $this->realtyObjectUid : FALSE
			);
		}
	}

	/**
	 * Returns the FE editor in HTML if a user is logged in and authorized, and
	 * if the object to edit actually exists in the database. Otherwise the
	 * result will be an error view.
	 *
	 * @param array $unused unused
	 *
	 * @return string HTML for the FE editor or an error view if the
	 *                requested object is not editable for the current user
	 */
	public function render(array $unused = array()) {
		$this->addOnLoadHandler();
		$this->makeFormCreator();
		return $this->formCreator->render();
	}

	/**
	 * Adds an onload handler to the page header.
	 *
	 * This function is intended to be overridden by subclasses if needed.
	 *
	 * @return void
	 */
	public function addOnLoadHandler() {
	}


	//////////////////////////////////////
	// Functions to be used by the form.
	//////////////////////////////////////

	/**
	 * Returns the URL where to redirect to after saving a record.
	 *
	 * @return string complete URL of the configured FE page, if none is
	 *                configured, the redirect will lead to the base URL
	 */
	public function getRedirectUrl() {
		return t3lib_div::locationHeaderUrl($this->cObj->typoLink_URL(array(
			'parameter' => $this->getConfValueInteger(
				'feEditorRedirectPid', 's_feeditor'
			),
		)));
	}

	/**
	 * Gets the path to the HTML template as set in the TS setup.
	 * The returned path will always be an absolute path in the file system;
	 * EXT: references will automatically get resolved.
	 *
	 * @return string the path to the HTML template as an absolute path in the
	 *                file system, will not be empty in a correct configuration
	 */
	static public function getTemplatePath() {
		return t3lib_div::getFileAbsFileName(
			tx_oelib_ConfigurationRegistry::get('plugin.tx_realty_pi1')
				->getAsString('feEditorTemplateFile')
		);
	}


	////////////////////////////////////
	// Miscellaneous helper functions.
	////////////////////////////////////

	/**
	 * Returns a form value from the FORMidable object.
	 *
	 * Note: In test mode, this function will return faked values.
	 *
	 * @param string $key column name of tx_realty_objects as key, must not be empty
	 *
	 * @return string form value or an empty string if the value does not exist
	 */
	protected function getFormValue($key) {
		$this->makeFormCreator();

		if ($this->isTestMode) {
			return $this->getFakedFormValue($key);
		} else {
			return $this->formCreator->oDataHandler->getThisFormData($key);
		}
	}

	/**
	 * Checks whether the realty object exists in the database and is enabled.
	 * For new objects, the result will always be TRUE.
	 *
	 * @return boolean TRUE if the realty object is available for editing,
	 *                 FALSE otherwise
	 */
	private function realtyObjectExistsInDatabase() {
		if ($this->realtyObjectUid == 0) {
			return TRUE;
		}

		return !$this->realtyObject->isEmpty();
	}


	///////////////////////////////////
	// Utility functions for testing.
	///////////////////////////////////

	/**
	 * Fakes the setting of the current UID.
	 *
	 * This function is for testing purposes.
	 *
	 * @param integer $uid
	 *        UID of the currently edited realty object. For creating a new database record, $uid must be zero.
	 *        Provided values must not be negative.
	 *
	 * @return void
	 */
	public function setRealtyObjectUid($uid) {
		$this->realtyObjectUid = $uid;

		if ($this->realtyObject->getUid() != $uid) {
			$this->realtyObject->__destruct();
			$this->realtyObject = t3lib_div::makeInstance(
				'tx_realty_Model_RealtyObject', $this->isTestMode
			);
			$this->realtyObject->loadRealtyObject($this->realtyObjectUid, TRUE);
		}
	}

	/**
	 * Fakes a form data value that is usually provided by the FORMidable
	 * object.
	 *
	 * This function is for testing purposes.
	 *
	 * @param string $key column name of tx_realty_objects as key, must not be empty
	 * @param string $value faked value
	 *
	 * @return void
	 */
	public function setFakedFormValue($key, $value) {
		$this->fakedFormValues[$key] = $value;
	}

	/**
	 * Gets a faked form data value that is usually provided by the FORMidable
	 * object.
	 *
	 * This function is for testing purposes.
	 *
	 * @param string $key column name of tx_realty_objects as key, must not be empty
	 *
	 * @return string faked value
	 */
	public function getFakedFormValue($key) {
		return isset($this->fakedFormValues[$key]) ? $this->fakedFormValues[$key] : '';
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/realty/pi1/class.tx_realty_frontEndForm.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/realty/pi1/class.tx_realty_frontEndForm.php']);
}