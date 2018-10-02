<?php

use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * This class represents a basic view.
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 */
abstract class tx_realty_pi1_FrontEndView extends Tx_Oelib_TemplateHelper
{
    /**
     * faking $this->scriptRelPath so the locallang.xlf file is found
     *
     * @var string
     */
    public $scriptRelPath = 'Resources/Private/Language/locallang.xlf';

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
     * @param ContentObjectRenderer $contentObjectRenderer the parent cObj content, needed for the flexforms
     * @param bool $isTestMode whether the class is instantiated in test mode
     */
    public function __construct(array $configuration, ContentObjectRenderer $contentObjectRenderer, $isTestMode = false)
    {
        $this->cObj = $contentObjectRenderer;
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
    abstract public function render(array $piVars = []);
}
