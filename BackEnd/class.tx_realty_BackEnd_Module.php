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
		$this->doc->docType = 'xhtml_strict';
		$this->doc->styleSheetFile2 = '../typo3conf/ext/realty/BackEnd.css';

		$result = $this->doc->startPage($GLOBALS['LANG']->getLL('title'));
		$result .= $this->doc->header($GLOBALS['LANG']->getLL('title'));

		if ($GLOBALS['BE_USER']->isAdmin()) {
			$this->template->setMarker(
				'label_hello_world',
				$GLOBALS['LANG']->getLL('label_hello_world')
			);
			$result .= $this->template->getSubpart('FULLDOC');
		} else {
			$result .= $this->doc->spacer(10);
		}

		$result .= $this->doc->endPage();

		return $this->doc->insertStylesAndJS($result);
	}

	/**
	 * Initializes the template objects.
	 */
	private function initializeTemplate() {
		$this->doc = t3lib_div::makeInstance('template');
		$this->doc->setModuleTemplate(
			t3lib_extMgm::extPath('realty') . 'BackEnd/mod_template.html'
		);
		$this->doc->backPath = $GLOBALS['BACK_PATH'];

		$this->template = tx_oelib_TemplateRegistry::getInstance()->getByFileName(
			'EXT:realty/BackEnd/mod_template.html'
		);
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/BackEnd/class.tx_realty_BackEnd_Module.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/BackEnd/class.tx_realty_BackEnd_Module.php']);
}
?>