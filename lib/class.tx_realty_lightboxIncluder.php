<?php
/***************************************************************
* Copyright notice
*
* (c) 2008-2009 Saskia Metzler <saskia@merlin.owl.de>
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

require_once(t3lib_extMgm::extPath('realty') . 'lib/class.tx_realty_translator.php');

/**
 * Class 'tx_realty_lightboxIncluder' for the 'realty' extension.
 * This class includes all files needed for Lightbox.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 */
class tx_realty_lightboxIncluder {
	/**
	 * Includes the files needed for the Lightbox.
	 *
	 * @param string the extension's prefix ID, must not be empty
	 * @param string extension key, must not be empty
	 */
	public static function includeLightboxFiles($prefixId, $extKey) {
		$GLOBALS['TSFE']->additionalHeaderData[$prefixId . '_lightboxcss']
			= '<link rel="stylesheet" type="text/css" href="' .
			t3lib_extMgm::extRelPath($extKey) .
			'pi1/contrib/lightbox.css" />';

		$GLOBALS['TSFE']->additionalHeaderData[$prefixId . '_prototype']
			= '<script type="text/javascript" ' .
			'src="' . t3lib_extMgm::extRelPath($extKey) .
			'pi1/contrib/prototype.js">' .
			'</script>';

		$GLOBALS['TSFE']->additionalHeaderData[$prefixId . '_scriptaculous']
			= '<script type="text/javascript"' .
			'src="' . t3lib_extMgm::extRelPath($extKey) .
			'pi1/contrib/scriptaculous.js?load=effects,builder">' .
			'</script>';

		self::addLightboxConfigurationToHeader($prefixId, $extKey);

		$GLOBALS['TSFE']->additionalHeaderData[$prefixId . '_lightbox']
			= '<script type="text/javascript" ' .
			'src="' . t3lib_extMgm::extRelPath($extKey) .
			'pi1/contrib/lightbox.js" >' .
			'</script>';
	}

	/**
	 * Adds the configuration for the Lightbox to the header. This function
	 * must be called before the lightbox.js file ist added to the header.
	 *
	 * @param string the extension's prefix ID, must not be empty
	 * @param string extension key, must not be empty
	 */
	private static function addLightboxConfigurationToHeader($prefixId, $extKey) {
		$translator = tx_oelib_ObjectFactory::make('tx_realty_translator');

		$GLOBALS['TSFE']->additionalHeaderData[$prefixId . '_lightbox_config']
			= '<script type="text/javascript">' .
			'LightboxOptions = Object.extend({' .
				'fileLoadingImage: \''.
					t3lib_extMgm::extRelPath($extKey) .
					'pi1/images/loading.gif\',' .
				'fileBottomNavCloseImage: \'' .
					t3lib_extMgm::extRelPath($extKey) .
					'pi1/images/closelabel.gif\',' .
				// controls transparency of shadow overlay
				'overlayOpacity: 0.8,' .
				// toggles resizing animations
				'animate: true,' .
				// controls the speed of the image resizing animations
				// (1=slowest and 10=fastest)
				'resizeSpeed: 7,' .
				// if you adjust the padding in the CSS, you will need to
				// update this variable
				'borderSize: 10,' .
				// When grouping images this is used to write: Image # of #.
				'labelImage: "' . $translator->translate('label_lightbox_image') . '",' .
				'labelOf: "'. $translator->translate('label_lightbox_of') .'"' .
			'}, window.LightboxOptions || {});' .
		'</script>';

		unset($translator);
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/lib/class.tx_realty_lightboxIncluder.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realty/lib/class.tx_realty_lightboxIncluder.php']);
}
?>