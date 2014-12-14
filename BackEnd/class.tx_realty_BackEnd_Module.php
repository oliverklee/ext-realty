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
 * Backend module.
 *
 * @author Bernd Schönbach <bernd@oliverklee.de>
 *
 * @package TYPO3
 * @subpackage tx_realty
 */
class tx_realty_BackEnd_Module extends t3lib_SCbase {
	/**
	 * @var string
	 */
	const MODULE_NAME = 'web_txrealtyM1';

	/**
	 * @var tx_oelib_template template object
	 */
	private $template = NULL;

	/**
	 * @var array localized error message for the errors occurred during the
	 *            access check
	 */
	private $errorMessages = array();

	/**
	 * @var int tab import
	 */
	const IMPORT_TAB = 0;

	/**
	 * Initializes the module.
	 *
	 * @return void
	 */
	public function init() {
		parent::init();

		$this->initializeTemplate();
	}

	/**
	 * Renders the module content.
	 *
	 * @return string HTML for the module, will not be empty
	 */
	function render()	{
		$result = $this->doc->startPage($GLOBALS['LANG']->getLL('title'));
		$result .= $this->doc->header($GLOBALS['LANG']->getLL('title'));

		if ($this->hasAccess()) {
			$result .= $this->doc->section(
				'',
				$this->doc->spacer(10) . $this->createTab()
			);

			if (t3lib_div::_GP('action') == 'startImport') {
				$importer = t3lib_div::makeInstance('tx_realty_openImmoImport');
				$this->template->setMarker(
					'import_logs',
					nl2br(htmlspecialchars($importer->importFromZip()))
				);

				$result .= $this->template->getSubpart('IMPORT_RESULT');
			}

			$result .= $this->createImportButton();
		} else {
			$result .= $this->getErrorMessages();
		}

		$result .= $this->doc->endPage();

		return $this->doc->insertStylesAndJS($result);
	}

	/**
	 * Initializes the template objects.
	 *
	 * @return void
	 */
	private function initializeTemplate() {
		$this->doc = t3lib_div::makeInstance('bigDoc');
		$this->doc->backPath = $GLOBALS['BACK_PATH'];
		$this->doc->docType = 'xhtml_strict';
		$this->doc->styleSheetFile2
			= '../typo3conf/ext/realty/BackEnd/BackEnd.css';

		$this->template
			= tx_oelib_TemplateRegistry::getInstance()->getByFileName(
				'EXT:realty/BackEnd/mod_template.html'
		);
	}

	/**
	 * Creates the OpenImmo import tab.
	 *
	 * @return string HTML for the OpenImmo tab, will not be empty
	 */
	private function createTab() {
		$moduleToken = t3lib_formprotection_Factory::get()->generateToken('moduleCall', self::MODULE_NAME);
		return $this->doc->getTabMenu(
			array('M' => self::MODULE_NAME, 'moduleToken' => $moduleToken, 'id' => $this->id),
			'tab',
			self::IMPORT_TAB,
			array(self::IMPORT_TAB => $GLOBALS['LANG']->getLL('import_tab'))
			) . $this->doc->spacer(5);
	}

	/**
	 * Creates an import button which will start the import of the OpenImmo
	 * files.
	 *
	 * @return string the HTML output for the import button
	 */
	private function createImportButton() {
		$moduleUrl = t3lib_BEfunc::getModuleUrl(self::MODULE_NAME, array('id' => $this->id));
		$this->template->setMarker('module_url', htmlspecialchars($moduleUrl));
		$this->template->setMarker(
			'label_start_import',
			$GLOBALS['LANG']->getLL('start_import_button')
		);
		$this->template->setMarker('tab_number', self::IMPORT_TAB);
		$this->template->setMarker(
			'label_import_in_progress',
			$GLOBALS['LANG']->getLL('label_import_in_progress')
		);

		return $this->template->getSubpart('IMPORT_BUTTON');
	}

	/**
	 * Checks if the current BE user has access to the necessary data to import
	 * realty records.
	 *
	 * @return bool TRUE if the BE user is an admin or if they have the
	 *                 rights to access the necessary data, FALSE otherwise
	 */
	private function hasAccess() {
		if ($GLOBALS['BE_USER']->isAdmin()) {
			return TRUE;
		}

		return $this->userHasAccessToPages() && $this->userHasAccessToTables();
	}

	/**
	 * Checks if the user has write permissions on the pages configured in
	 * "pidForRealtyObjectsAndImages" and "pidForAuxiliaryRecords".
	 *
	 * @return bool TRUE if the user has write access to both pages, FALSE
	 *                 otherwise
	 */
	private function userHasAccessToPages() {
		$configurationProxy = tx_oelib_configurationProxy::getInstance('realty');

		$objectsPid = $configurationProxy->getAsInteger(
			'pidForRealtyObjectsAndImages'
		);
		$canWriteObjectsPage = $GLOBALS['BE_USER']->doesUserHaveAccess(
			t3lib_BEfunc::getRecord('pages', $objectsPid), 16
		);

		$auxiliaryPid = $configurationProxy->getAsInteger(
			'pidForAuxiliaryRecords'
		);
		$canWriteAuxiliaryPage = $GLOBALS['BE_USER']->doesUserHaveAccess(
			t3lib_BEfunc::getRecord('pages', $auxiliaryPid), 16
		);

		if (!$canWriteObjectsPage) {
			$this->storeErrorMessage('objects_pid', $objectsPid);
		}
		if (!$canWriteAuxiliaryPage) {
			$this->storeErrorMessage('auxiliary_pid', $auxiliaryPid);
		}

		return $canWriteObjectsPage && $canWriteAuxiliaryPage;
	}

	/**
	 * Checks if the user has write access to the database tables needed to
	 * create realty objects and auxiliary records.
	 *
	 * @return bool TRUE if the user has the needed DB table access
	 *                 permissions, FALSE otherwise
	 */
	private function userHasAccessToTables() {
		$userHasAccessToTables = TRUE;
		$neededTables = array(
			'tx_realty_objects',
			'tx_realty_apartment_types',
			'tx_realty_car_places',
			'tx_realty_cities',
			'tx_realty_districts',
			'tx_realty_house_types',
			'tx_realty_images',
			'tx_realty_pets',
		);

		foreach ($neededTables as $table) {
			if (!$GLOBALS['BE_USER']->check('tables_modify', $table)) {
				$userHasAccessToTables = FALSE;
				$this->storeErrorMessage('table_access', $table);
				break;
			}
		}

		return $userHasAccessToTables;
	}

	/**
	 * Stores a localized error message in $this->errorMessages.
	 *
	 * @param string $message
	 *        the locallang key of the error message to store,
	 *        must be an existing locallang label without the prefix 'error_message_'
	 * @param mixed $value
	 *        the value which should be included in the locallang message, must not be empty
	 *
	 * @return void
	 */
	private function storeErrorMessage($message, $value) {
		$this->errorMessages[] = sprintf(
			$GLOBALS['LANG']->getLL('error_message_' . $message),
			$value
		);
	}

	/**
	 * Builds the HTML output for the error messages.
	 *
	 * @return string HTML output for the error messages, will be empty if no
	 *                errors occurred during processing
	 */
	private function getErrorMessages() {
		if (empty($this->errorMessages)) {
			return '';
		}

		$this->template->setMarker(
			'message_no_permissions',
			$this->doc->spacer(5) .
				$GLOBALS['LANG']->getLL('message_no_permission')

		);
		$errorList = implode('</li>' . LF . '<li>', $this->errorMessages);
		$this->template->setMarker('error_list', '<li>' . $errorList .'</li>');

		return $this->template->getSubpart('IMPORT_ERRORS');
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/realty/BackEnd/class.tx_realty_BackEnd_Module.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/realty/BackEnd/class.tx_realty_BackEnd_Module.php']);
}