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
 * Class 'tx_realty_filterForm' for the 'realty' extension. This class
 * provides a form to enter filter criteria for the realty list in the realty
 * plugin.
 *
 * @package		TYPO3
 * @subpackage	tx_realty
 *
 * @author		Saskia Metzler <saskia@merlin.owl.de>
 */

class tx_realty_filterForm {
	/** plugin in which the filter form is used */
	private $plugin = null;

	/**
	 * The constructor.
	 *
	 * @param	tx_oelib_templatehelper		plugin which uses this class
	 */
	public function __construct(tx_oelib_templatehelper $plugin) {
		$this->plugin = $plugin;
	}

	/**
	 * Returns the filter form in HTML.
	 *
	 * @return	string		HTML of the filter form, will not be empty
	 */
	public function render() {
		$this->plugin->setMarker(
			'target_url',
			t3lib_div::locationHeaderUrl(
				$this->plugin->cObj->getTypoLink_URL(
					$this->plugin->getConfValueInteger('filterTargetPID')
				)
			)
		);

		return $this->plugin->getSubpart('FILTER_FORM');
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/pi1/class.tx_realty_filterForm.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/pi1/class.tx_realty_filterForm.php']);
}
?>
