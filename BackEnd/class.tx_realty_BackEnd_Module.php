<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Bernd Schönbach <bernd@oliverklee.de>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

require_once(PATH_t3lib . 'class.t3lib_scbase.php');

require_once(t3lib_extMgm::extPath('realty') . 'lib/tx_realty_constants.php');
require_once(t3lib_extMgm::extPath('realty') . 'lib/class.tx_realty_openImmoImport.php');

/**
 * Backend module for the 'realty' extension.
 *
 * @author Bernd Schönbach <bernd@oliverklee.de>
 *
 * @package TYPO3
 * @subpackage tx_realty
 */
class tx_realty_BackEnd_Module extends t3lib_SCbase {
	/**
	 * @var tx_oelib_template template object
	 */
	private $template = null;

	/**
	 * @var integer tab import
	 */
	const IMPORT_TAB = 0;

	/**
	 * Initializes the module.
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
				$importer->__destruct();

				$result .= $this->template->getSubpart('IMPORT_RESULT');
			}

			$result .= $this->createImportButton();
		} else {
			$result .= $GLOBALS['LANG']->getLL('message_no_permission') .
				$this->doc->spacer(10);
		}

		$result .= $this->doc->endPage();

		return $this->doc->insertStylesAndJS($result);
	}

	/**
	 * Initializes the template objects.
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
		$tabMenu = $this->doc->getTabMenu(
			array('M' => 'web_txrealtyM1', 'id' => $this->id),
			'tab',
			self::IMPORT_TAB,
			array(self::IMPORT_TAB => $GLOBALS['LANG']->getLL('import_tab'))
			) . $this->doc->spacer(5);

		// $this->doc->getTabMenu adds a surplus ampersand after the "?".
		return str_replace('mod.php?&amp;amp;M=', 'mod.php?M=', $tabMenu);
	}

	/**
	 * Creates an import button which will start the import of the OpenImmo
	 * files.
	 *
	 * @return string the HTML output for the import button
	 */
	private function createImportButton() {
		$this->template->setMarker('action_id', $this->id);
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
	 * @return boolean true if the BE user is an admin or if they have the
	 *                 rights to access the necessary data, false otherwise
	 */
	private function hasAccess() {
		if ($GLOBALS['BE_USER']->isAdmin()) {
			return true;
		}

		return $this->userHasAccessToPages() && $this->userHasAccessToTables();
	}

	/**
	 * Checks if the user has write permissions on the pages configured in
	 * "pidForRealtyObjectsAndImages" and "pidForAuxiliaryRecords".
	 *
	 * @return boolean true if the user has write access to both pages, false
	 *                 otherwise
	 */
	private function userHasAccessToPages() {
		$configurationProxy = tx_oelib_configurationProxy::getInstance('realty');

		$objectsPid = $configurationProxy->getConfigurationValueInteger(
			'pidForRealtyObjectsAndImages'
		);
		$canWriteObjectsPage = $GLOBALS['BE_USER']->doesUserHaveAccess(
			t3lib_BEfunc::getRecord('pages', $objectsPid), 16
		);

		$auxiliaryPid = $configurationProxy->getConfigurationValueInteger(
			'pidForAuxiliaryRecords'
		);
		$canWriteAuxiliaryPage = $GLOBALS['BE_USER']->doesUserHaveAccess(
			t3lib_BEfunc::getRecord('pages', $auxiliaryPid), 16
		);

		return $canWriteObjectsPage && $canWriteAuxiliaryPage;
	}

	/**
	 * Checks if the user has write access to the database tables needed to
	 * create realty objects and auxiliary records.
	 *
	 * @return boolean true if the user has the needed DB table access
	 *                 permissions, false otherwise
	 */
	private function userHasAccessToTables() {
      	$userHasAccessToTables = true;
      	$neededTables = array(
      		REALTY_TABLE_OBJECTS,
      		REALTY_TABLE_APARTMENT_TYPES,
      		REALTY_TABLE_CAR_PLACES,
      		REALTY_TABLE_CITIES,
      		REALTY_TABLE_DISTRICTS,
      		REALTY_TABLE_HOUSE_TYPES,
      		REALTY_TABLE_IMAGES,
      		REALTY_TABLE_PETS,
      	);

      	foreach ($neededTables as $table) {
      		if (!$GLOBALS['BE_USER']->check('tables_modify', $table)) {
      			$userHasAccessToTables = false;
      			break;
      		}
      	}

      	return $userHasAccessToTables;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/BackEnd/class.tx_realty_BackEnd_Module.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/BackEnd/class.tx_realty_BackEnd_Module.php']);
}
?>