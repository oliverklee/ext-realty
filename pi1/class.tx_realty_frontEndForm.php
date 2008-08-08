<?php
/***************************************************************
* Copyright notice
*
* (c) 2008 Saskia Metzler <saskia@merlin.owl.de> All rights reserved
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

require_once(t3lib_extMgm::extPath('oelib') . 'class.tx_oelib_templatehelper.php');
require_once(t3lib_extMgm::extPath('oelib') . 'class.tx_oelib_headerProxyFactory.php');

require_once(t3lib_extMgm::extPath('realty') . 'lib/tx_realty_constants.php');
require_once(t3lib_extMgm::extPath('realty') . 'lib/class.tx_realty_object.php');

/**
 * Class 'tx_realty_frontEndForm' for the 'realty' extension. This class
 * provides functions used in the realty plugin's forms.
 *
 * @package		TYPO3
 * @subpackage	tx_realty
 *
 * @author		Saskia Metzler <saskia@merlin.owl.de>
 * @author		Oliver Klee <typo3-coding@oliverklee.de>
 */
class tx_realty_frontEndForm extends tx_oelib_templatehelper {
	/** the extension key (FORMidable expects this to be public) */
	public $extKey = 'realty';

	/** plugin in which the FE editor is used */
	protected $plugin = null;

	/** formidable object that creates the form */
	protected $formCreator = null;

	/** instance of tx_realty_object */
	protected $realtyObject = null;

	/**
	 * UID of the currently edited object, zero if the object is going to be a
	 * new database record.
	 */
	protected $realtyObjectUid = 0;

	/** whether the constructor is called in test mode */
	protected $isTestMode = false;

	/** this is used to fake form values for testing */
	protected $fakedFormValues = array();

	/**
	 * The constructor.
	 *
	 * @param	tx_oelib_templatehelper		plugin which uses this FE editor
	 * @param	integer		UID of the object to edit, set to 0 to create a new
	 * 						database record, must not be negative
	 * @param	string		path of the XML for the form, relative to this
	 * 						extension, must not begin with a slash and must not
	 * 						be empty
	 * @param	boolean		whether the FE editor is instanciated in test mode
	 */
	public function __construct(
		tx_oelib_templatehelper $plugin, $uidOfObjectToEdit, $xmlPath,
		$isTestMode = false
	) {
		$this->isTestMode = $isTestMode;
		$this->realtyObjectUid = $uidOfObjectToEdit;

		$objectClassName = t3lib_div::makeInstanceClassName('tx_realty_object');
		$this->realtyObject = new $objectClassName($this->isTestMode);
		$this->realtyObject->loadRealtyObject($this->realtyObjectUid, true);

		$this->plugin = $plugin;
		// For the templatehelper's functions about setting labels and filling
		// markers, the plugin's templatehelper object is used as the inherited
		// templatehelper does not have all configuration which would be
		// necessary for this.
		$this->plugin->getTemplateCode();
		$this->plugin->setLabels();
		// For configuration stuff the own inherited templatehelper can be used.
		$this->init($this->plugin->getConfiguration());
		$this->pi_initPIflexForm();

		$this->formCreator = t3lib_div::makeInstance('tx_ameosformidable');
		// FORMidable would produce an error message if it is initialized with
		// a non-existing UID.
		// The FORMidable object is never initialized for testing.
		if ($this->realtyObjectExistsInDatabase() && !$this->isTestMode) {
			$this->formCreator->init(
				$this,
				t3lib_extMgm::extPath('realty').$xmlPath,
				($this->realtyObjectUid > 0) ? $this->realtyObjectUid : false
			);
		}
	}

	/**
	 * Returns the FE editor in HTML if a user is logged in and authorized, and
	 * if the object to edit actually exists in the database. Otherwise the
	 * result will be an error view.
	 *
	 * @return	string		HTML for the FE editor or an error view if the
	 * 						requested object is not editable for the current user
	 */
	public function render() {
		$errorMessage = $this->checkAccess();
		if ($errorMessage != '') {
  			return $errorMessage;
		}

		return $this->formCreator->render();
	}


	///////////////////////////////////////////////////
	// Functions concerning access and authorization.
	///////////////////////////////////////////////////

	/**
	 * Checks whether the current record actually exists and whether the current
	 * FE user is logged in and authorized to change the record. Returns an error
	 * message if these conditions are not given.
	 *
	 * @return	string		empty if the current record actually exists and if
	 * 						the FE user is authorised, otherwise the HTML of a
	 * 						message that tells which condition is not fulfilled
	 */
	public function checkAccess() {
		$result = '';
		if (!$this->realtyObjectExistsInDatabase()) {
			$result = $this->renderObjectDoesNotExistMessage();
		} elseif (!$this->isLoggedIn()) {
			$result = $this->renderPleaseLogInMessage();
		} elseif (!$this->isFrontEndUserAuthorized()) {
			$result = $this->renderNoAccessMessage();
		}

		return $result;
	}

	/**
	 * Returns the HTML for an error view. Therefore the plugin's
	 * template is used.
	 *
	 * @param	string		content for the error view, must not be empty
	 *
	 * @return	string		HTML of the error message, will not be empty
	 */
	private function renderErrorMessage($rawErrorMessage) {
		$this->plugin->setMarker('error_message', $rawErrorMessage);

		return $this->plugin->getSubpart('FRONT_END_EDITOR');
	}

	/**
	 * Returns HTML for the object-does-not-exist error message and sets a 404
	 * header.
	 *
	 * @return	string		HTML for the object-does-not-exist error message
	 */
	private function renderObjectDoesNotExistMessage() {
		tx_oelib_headerProxyFactory::getInstance()->getHeaderProxy()
			->addHeader('Status: 404 Not Found');

		return $this->renderErrorMessage(
			$this->plugin->translate('message_noResultsFound_fe_editor')
		);
	}

	/**
	 * Returns HTML for the please-login error message and sets a 403 header.
	 *
	 * @return	string		HTML for the please-login error message
	 */
	private function renderPleaseLogInMessage() {
		tx_oelib_headerProxyFactory::getInstance()->getHeaderProxy()
			->addHeader('Status: 403 Forbidden');

		$piVars = $this->piVars;
		unset($piVars['DATA']);

		$redirectUrl = t3lib_div::locationHeaderUrl(
			$this->plugin->cObj->typoLink_URL(
				array(
					'parameter' => $GLOBALS['TSFE']->id,
					'additionalParams' => t3lib_div::implodeArrayForUrl(
						$this->prefixId,
						$piVars,
						'',
						true,
						true
					),
				)
			)
		);

		$link = $this->plugin->cObj->typoLink(
			htmlspecialchars($this->plugin->translate('message_please_login')),
			array(
				'parameter' => $this->plugin->getConfValueInteger('loginPID'),
				'additionalParams' => t3lib_div::implodeArrayForUrl(
					'', array('redirect_url' => $redirectUrl)
				),
			)
		);

		return $this->renderErrorMessage($link);
	}

	/**
	 * Returns HTML for the access-denied error message and sets a 403 header.
	 *
	 * @return	string		HTML for the access-denied error message
	 */
	private function renderNoAccessMessage() {
		tx_oelib_headerProxyFactory::getInstance()->getHeaderProxy()
			->addHeader('Status: 403 Forbidden');

		return $this->renderErrorMessage(
			$this->plugin->translate('message_access_denied')
		);
	}

	/**
	 * Checks whether the realty object exists in the database and is enabled.
	 * For new objects, the result will always be true.
	 *
	 * @return	boolean		true if the realty object is available for editing,
	 * 						false otherwise
	 */
	private function realtyObjectExistsInDatabase() {
		if ($this->realtyObjectUid == 0) {
			return true;
		}

		return !$this->realtyObject->isRealtyObjectDataEmpty();
	}

	/**
	 * Checks whether the FE user is allowed to edit the object. New objects are
	 * considered to be editable by every logged in user.
	 *
	 * Note: This function does not check on user group memberships.
	 *
	 * @return	boolean		true if the FE user is allowed to edit the object,
	 * 						false otherwise
	 */
	private function isFrontEndUserAuthorized() {
		if ($this->realtyObjectUid == 0) {
			return true;
		}

		return ($this->realtyObject->getProperty('owner') == $this->getFeUserUid());
	}


	///////////////////////////////////////////////////
	// Functions to be used by the form after submit.
	///////////////////////////////////////////////////

	/**
	 * Returns the URL where to redirect to after saving a record.
	 *
	 * @return	string		complete URL of the configured FE page, if none is
	 * 						configured, the redirect will lead to the base URL
	 */
	public function getRedirectUrl() {
		return t3lib_div::locationHeaderUrl(
			$this->plugin->cObj->typoLink_URL(
				array(
					'parameter' => $this->plugin->getConfValueInteger(
						'feEditorRedirectPid'
					),
				)
			)
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
	 * @param	string		column name of tx_realty_objects as key, must not
	 * 						be empty
	 *
	 * @return	string		form value or an empty string if the value does not
	 * 						exist
	 */
	protected function getFormValue($key) {
		$dataSource = ($this->isTestMode)
			? $this->fakedFormValues
			: $this->formCreator->oDataHandler->__aFormData;

		return isset($dataSource[$key]) ? $dataSource[$key] : '';
	}


	///////////////////////////////////
	// Utility functions for testing.
	///////////////////////////////////

	/**
	 * Fakes the setting of the current UID.
	 *
	 * This function is for testing purposes.
	 *
	 * @param	integer		UID of the currently edited realty object. For
	 * 						creating a new database record, $uid must be zero.
	 * 						Provided values must not be negative.
	 */
	public function setRealtyObjectUid($uid) {
		$this->realtyObjectUid = $uid;
		$this->realtyObject->loadRealtyObject($this->realtyObjectUid, true);
	}

	/**
	 * Fakes a form data value that is usually provided by the FORMidable
	 * object.
	 *
	 * This function is for testing purposes.
	 *
	 * @param	string		column name of tx_realty_objects as key, must not
	 * 						be empty
	 * @param	string		faked value
	 */
	public function setFakedFormValue($key, $value) {
		$this->fakedFormValues[$key] = $value;
	}

	/**
	 * Gets the path to the HTML template as set in the TS setup or flexforms.
	 * The returned path will always be an absolute path in the file system;
	 * EXT: references will automatically get resolved.
	 *
	 * @return	string		the path to the HTML template as an absolute path in
	 * 						the file system, will not be empty in a correct
	 * 						configuration
	 */
	public function getTemplatePath() {
		return t3lib_div::getFileAbsFileName(
			$this->plugin->getConfValueString(
				'feEditorTemplateFile',	's_feeditor', true
			)
		);
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/pi1/class.tx_realty_frontEndForm.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/pi1/class.tx_realty_frontEndForm.php']);
}
?>