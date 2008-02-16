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

/**
 * Class 'tx_realty_frontEndEditor' for the 'realty' extension. This class
 * provides a FE editor the realty plugin.
 *
 * @package		TYPO3
 * @subpackage	tx_realty
 * @author		Saskia Metzler <saskia@merlin.owl.de>
 */

require_once(t3lib_extMgm::extPath('oelib').'class.tx_oelib_templatehelper.php');

class tx_realty_frontEndEditor extends tx_oelib_templatehelper {
	/** plugin in which the FE editor is used */
	private $plugin = null;

	/**
	 * The constructor.
	 *
	 * @param	tx_oelib_templatehelper		plugin in which uses this FE editor
	 */
	public function __construct(tx_oelib_templatehelper $plugin) {
		$this->plugin = $plugin;
		// For the templatehelper's functions about setting labels and filling
		// markers, the plugin's templatehelper object is used as the inherited
		// templatehelper does not have all configuration which would be
		// necessary for this.
		$this->plugin->getTemplateCode();
		$this->plugin->setLabels();
		// For configuration stuff the own inherited templatehelper can be used.
		$this->init($this->plugin->getConfiguration);
		$this->pi_initPIflexForm();
	}

	/**
	 * Returns the FE editor in HTML.
	 * Currently just "Hello world!" is returned.
	 *
	 * @return	string		"Hello world!"
	 */
	public function render() {
		return 'Hello world!';
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/pi1/class.tx_realty_frontEndEditor.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/pi1/class.tx_realty_frontEndEditor.php']);
}
?>
