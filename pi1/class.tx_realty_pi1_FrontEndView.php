<?php
/***************************************************************
* Copyright notice
*
* (c) 2009-2010 Saskia Metzler <saskia@merlin.owl.de>
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
 * Class 'tx_realty_pi1_FrontEndView' for the 'realty' extension.
 *
 * This class represents a basic view.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 */
abstract class tx_realty_pi1_FrontEndView extends tx_oelib_templatehelper {
	/**
	 * @var string path to this script relative to the extension dir
	 */
	public $scriptRelPath = 'pi1/class.tx_realty_pi1_FrontEndView.php';

	/**
	 * @var string same as plugin name
	 */
	public $prefixId = 'tx_realty_pi1';

	/**
	 * @var string the extension key
	 */
	public $extKey = 'realty';

	/**
	 * The constructor. Initializes the TypoScript configuration, initializes
	 * the flexforms, gets the template HTML code, sets the localized labels
	 * and set the CSS classes from TypoScript.
	 *
	 * @param array TypoScript configuration for the plugin
	 * @param tslib_cObj the parent cObj content, needed for the flexforms
	 * @param boolean whether the class is instantiated in test mode
	 */
	public function __construct(
		array $configuration, tslib_cObj $cObj, $isTestMode = FALSE
	) {
		$this->cObj = $cObj;
		$this->init($configuration);
		$this->getTemplateCode();
		$this->setLabels();
	}

	/**
	 * Renders this view and returns its content.
	 *
	 * @param array form data array (piVars)
	 *
	 * @return string the view's content
	 */
	abstract public function render(array $piVars = array());
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/pi1/class.tx_realty_pi1_FrontEndView.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/pi1/class.tx_realty_pi1_FrontEndView.php']);
}
?>