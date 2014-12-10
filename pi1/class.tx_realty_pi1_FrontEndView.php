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
	 * @param array $configuration TypoScript configuration for the plugin
	 * @param tslib_cObj $cObj the parent cObj content, needed for the flexforms
	 * @param boolean $isTestMode whether the class is instantiated in test mode
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
	 * @param array $piVars form data array (piVars)
	 *
	 * @return string the view's content
	 */
	abstract public function render(array $piVars = array());
}