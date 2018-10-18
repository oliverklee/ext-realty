<?php

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * This class provides functions used in the realty plugin's forms.
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class tx_realty_frontEndForm extends tx_realty_pi1_FrontEndView
{
    /**
     * @var \tx_mkforms_forms_Base
     */
    protected $form = null;

    /**
     * @var tx_realty_Model_RealtyObject
     */
    protected $realtyObject = null;

    /**
     * @var int UID of the currently edited object, zero if the object is
     *              going to be a new database record.
     */
    protected $realtyObjectUid = 0;

    /**
     * @var bool whether the constructor is called in test mode
     */
    protected $isTestMode = false;

    /**
     * @var array this is used to fake form values for testing
     */
    protected $fakedFormValues = [];

    /**
     * @var string
     */
    private $configurationNamespace = '';

    /**
     * The constructor.
     *
     * @param array $configuration
     *        TypoScript configuration for the plugin
     * @param ContentObjectRenderer $contentObjectRenderer
     *        the parent cObj content, needed for the flexforms
     * @param int $uidOfObjectToEdit
     *        UID of the object to edit, set to 0 to create a new database record, must not be negative
     * @param string $configurationNamespace relative to plugin.tx_realty_pi1 (without the trailing period)
     * @param bool $isTestMode
     *        whether the FE editor is instantiated in test mode
     */
    public function __construct(
        array $configuration,
        ContentObjectRenderer $contentObjectRenderer,
        $uidOfObjectToEdit,
        $configurationNamespace,
        $isTestMode = false
    ) {
        $this->isTestMode = $isTestMode;
        $this->realtyObjectUid = $uidOfObjectToEdit;
        $this->configurationNamespace = $configurationNamespace;

        $this->realtyObject = GeneralUtility::makeInstance(\tx_realty_Model_RealtyObject::class, $this->isTestMode);
        $this->realtyObject->loadRealtyObject($this->realtyObjectUid, true);

        parent::__construct($configuration, $contentObjectRenderer);
    }

    /**
     * Instantiates $this->form (if it hasn't been created yet).
     *
     * This function does nothing if this object is running in test mode.
     *
     * @return void
     */
    protected function makeForm()
    {
        if ($this->isTestMode || $this->form !== null) {
            return;
        }

        \tx_rnbase::load(\tx_mkforms_forms_Base::class);
        \tx_rnbase::load(\Tx_Rnbase_Database_Connection::class);
        \tx_rnbase::load(\tx_mkforms_forms_Factory::class);
        $this->form = GeneralUtility::makeInstance(\tx_mkforms_forms_Base::class);

        /**
         * Configuration instance for plugin data. Necessary for LABEL translation.
         *
         * @var \Tx_Rnbase_Configuration_Processor $pluginConfiguration
         */
        $pluginConfiguration = \tx_rnbase::makeInstance(\Tx_Rnbase_Configuration_Processor::class);
        $pluginConfiguration->init($this->conf, $this->cObj, 'realty', 'tx_realty_pi1_form');

        // mkforms would produce an error message if it is initialized with a non-existing UID.
        // The mkforms object is never initialized for testing.
        if ($this->realtyObjectExistsInDatabase()) {
            // Initialize the form from TypoScript data and provide configuration for the plugin.
            $this->form->initFromTs(
                $this,
                $this->conf[$this->configurationNamespace . '.'],
                $this->realtyObjectUid > 0 ? $this->realtyObjectUid : false,
                $pluginConfiguration,
                $this->configurationNamespace
            );
        }
    }

    /**
     * Returns the FE editor in HTML if a user is logged in and authorized, and
     * if the object to edit actually exists in the database. Otherwise the
     * result will be an error view.
     *
     * @param array $unused unused
     *
     * @return string HTML for the FE editor or an error view if the
     *                requested object is not editable for the current user
     */
    public function render(array $unused = [])
    {
        $this->makeForm();
        return $this->form->render();
    }

    //////////////////////////////////////
    // Functions to be used by the form.
    //////////////////////////////////////

    /**
     * Returns the URL where to redirect to after saving a record.
     *
     * @return string complete URL of the configured FE page, if none is
     *                configured, the redirect will lead to the base URL
     */
    public function getRedirectUrl()
    {
        return GeneralUtility::locationHeaderUrl($this->cObj->typoLink_URL([
            'parameter' => $this->getConfValueInteger(
                'feEditorRedirectPid',
                's_feeditor'
            ),
            'useCacheHash' => true,
        ]));
    }

    /**
     * Gets the path to the HTML template as set in the TS setup.
     * The returned path will always be an absolute path in the file system;
     * EXT: references will automatically get resolved.
     *
     * @return string the path to the HTML template as an absolute path in the
     *                file system, will not be empty in a correct configuration
     */
    public static function getTemplatePath()
    {
        return GeneralUtility::getFileAbsFileName(
            Tx_Oelib_ConfigurationRegistry::get('plugin.tx_realty_pi1')
                ->getAsString('feEditorTemplateFile')
        );
    }

    ////////////////////////////////////
    // Miscellaneous helper functions.
    ////////////////////////////////////

    /**
     * Returns a form value from the mkforms object.
     *
     * Note: In test mode, this function will return faked values.
     *
     * @param string $key column name of tx_realty_objects as key, must not be empty
     *
     * @return string form value or an empty string if the value does not exist
     */
    protected function getFormValue($key)
    {
        $this->makeForm();

        if ($this->isTestMode) {
            $result = $this->getFakedFormValue($key);
        } else {
            $formData = $this->form->getDataHandler()->getFormData();
            $result = isset($formData[$key]) ? (string)$formData[$key] : '';
        }

        return $result;
    }

    /**
     * Checks whether the realty object exists in the database and is enabled.
     * For new objects, the result will always be TRUE.
     *
     * @return bool TRUE if the realty object is available for editing,
     *                 FALSE otherwise
     */
    private function realtyObjectExistsInDatabase()
    {
        if ($this->realtyObjectUid === 0) {
            return true;
        }

        return !$this->realtyObject->isEmpty();
    }

    ///////////////////////////////////
    // Utility functions for testing.
    ///////////////////////////////////

    /**
     * Fakes the setting of the current UID.
     *
     * This function is for testing purposes.
     *
     * @param int $uid
     *        UID of the currently edited realty object. For creating a new database record, $uid must be zero.
     *        Provided values must not be negative.
     *
     * @return void
     */
    public function setRealtyObjectUid($uid)
    {
        $this->realtyObjectUid = $uid;

        if ($this->realtyObject->getUid() !== $uid) {
            $this->realtyObject = GeneralUtility::makeInstance(\tx_realty_Model_RealtyObject::class, $this->isTestMode);
            $this->realtyObject->loadRealtyObject($this->realtyObjectUid, true);
        }
    }

    /**
     * Fakes a form data value that is usually provided by the mkforms object.
     *
     * This function is for testing purposes.
     *
     * @param string $key column name of tx_realty_objects as key, must not be empty
     * @param string $value faked value
     *
     * @return void
     */
    public function setFakedFormValue($key, $value)
    {
        $this->fakedFormValues[$key] = $value;
    }

    /**
     * Gets a faked form data value that is usually provided by the mkforms object.
     *
     * This function is for testing purposes.
     *
     * @param string $key column name of tx_realty_objects as key, must not be empty
     *
     * @return string faked value
     */
    public function getFakedFormValue($key)
    {
        return isset($this->fakedFormValues[$key]) ? (string)$this->fakedFormValues[$key] : '';
    }
}
