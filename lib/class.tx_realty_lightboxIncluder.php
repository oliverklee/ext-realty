<?php

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * This class includes JavaScript and CSS files, for example the main JavaScript
 * file, Prototype, Scriptaculous and Lightbox.
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class tx_realty_lightboxIncluder
{
    /**
     * @var string the prefix ID for frontend output
     */
    const PREFIX_ID = 'tx_realty_pi1';

    /**
     * @var string the extension key
     */
    const EXTENSION_KEY = 'realty';

    /**
     * Includes the extension's main JavaScript file.
     *
     * @return void
     */
    public static function includeMainJavaScript()
    {
        $frontEndController = self::getFrontEndController();
        $frontEndController->additionalHeaderData[self::PREFIX_ID]
            = '<script src="' . GeneralUtility::getIndpEnv('TYPO3_SITE_PATH') . ExtensionManagementUtility::siteRelPath(self::EXTENSION_KEY) .
                'pi1/tx_realty_pi1.js" type="text/javascript">' .
                '</script>';
    }

    /**
     * Includes the files needed for the Lightbox.
     *
     * @return void
     */
    public static function includeLightboxFiles()
    {
        $frontEndController = self::getFrontEndController();
        $configuration = Tx_Oelib_ConfigurationRegistry::get('plugin.tx_realty_pi1')
            ->getAsTrimmedArray('includeJavaScriptLibraries');
        if (in_array('prototype', $configuration)) {
            self::includePrototype();
        }

        if (in_array('scriptaculous', $configuration, true)) {
            $frontEndController->additionalHeaderData[self::PREFIX_ID . '_scriptaculous']
                = '<script type="text/javascript"' .
                'src="' . GeneralUtility::getIndpEnv('TYPO3_SITE_PATH') . ExtensionManagementUtility::siteRelPath(self::EXTENSION_KEY) .
                'pi1/contrib/scriptaculous.js?load=effects,builder">' .
                '</script>';
        }

        if (in_array('lightbox', $configuration, true)) {
            self::addLightboxConfigurationToHeader();

            $frontEndController->additionalHeaderData[self::PREFIX_ID . '_lightbox']
                = '<script type="text/javascript" ' .
                'src="' . GeneralUtility::getIndpEnv('TYPO3_SITE_PATH') . ExtensionManagementUtility::siteRelPath(self::EXTENSION_KEY) .
                'pi1/contrib/lightbox.js" >' .
                '</script>';
            $frontEndController->additionalHeaderData[self::PREFIX_ID . '_lightboxcss']
                = '<link rel="stylesheet" type="text/css" href="' .
                    GeneralUtility::getIndpEnv('TYPO3_SITE_PATH') . ExtensionManagementUtility::siteRelPath(self::EXTENSION_KEY) .
                'pi1/contrib/lightbox.css" />';
        }
    }

    /**
     * Includes the Prototype files.
     *
     * @return void
     */
    public static function includePrototype()
    {
        $frontEndController = self::getFrontEndController();
        $frontEndController->additionalHeaderData[self::PREFIX_ID . '_prototype']
            = '<script type="text/javascript" ' .
            'src="' . GeneralUtility::getIndpEnv('TYPO3_SITE_PATH') . ExtensionManagementUtility::siteRelPath(self::EXTENSION_KEY) .
            'pi1/contrib/prototype.js">' .
            '</script>';
    }

    /**
     * Adds the configuration for the Lightbox to the header. This function
     * must be called before the lightbox.js file ist added to the header.
     *
     * @return void
     */
    private static function addLightboxConfigurationToHeader()
    {
        /** @var tx_realty_translator $translator */
        $translator = GeneralUtility::makeInstance('tx_realty_translator');

        $frontEndController = self::getFrontEndController();
        $frontEndController->additionalHeaderData[self::PREFIX_ID . '_lightbox_config']
            = '<script type="text/javascript">' .
            'LightboxOptions = Object.extend({' .
                'fileLoadingImage: \'' .
                    GeneralUtility::getIndpEnv('TYPO3_SITE_PATH') . ExtensionManagementUtility::siteRelPath(self::EXTENSION_KEY) .
                    'pi1/images/loading.gif\',' .
                'fileBottomNavCloseImage: \'' .
                    GeneralUtility::getIndpEnv('TYPO3_SITE_PATH') . ExtensionManagementUtility::siteRelPath(self::EXTENSION_KEY) .
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
                'labelOf: "' . $translator->translate('label_lightbox_of') . '"' .
            '}, window.LightboxOptions || {});' .
        '</script>';
    }

    /**
     * Returns the current front-end instance.
     *
     * @return TypoScriptFrontendController
     */
    protected static function getFrontEndController()
    {
        return $GLOBALS['TSFE'];
    }
}
