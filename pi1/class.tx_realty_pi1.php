<?php

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * Plugin "Realty List".
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 * @author Saskia Metzler <saskia@merlin.owl.de>
 * @author Bernd Schönbach <bernd@oliverklee.de>
 */
class tx_realty_pi1 extends Tx_Oelib_TemplateHelper implements Tx_Oelib_Interface_ConfigurationCheckable
{
    /**
     * @var string same as class name
     */
    public $prefixId = 'tx_realty_pi1';

    /**
     * faking $this->scriptRelPath so the locallang.xlf file is found
     *
     * @var string
     */
    public $scriptRelPath = 'Resources/Private/Language/locallang.xlf';

    /**
     * @var string the extension key
     */
    public $extKey = 'realty';

    /**
     * @var bool whether to check cHash
     */
    public $pi_checkCHash = true;

    /**
     * @var bool whether this class is called in the test mode
     */
    private $isTestMode = false;

    /**
     * The constructor.
     *
     * @param bool $isTestMode whether this class is called in the test mode
     */
    public function __construct($isTestMode = false)
    {
        $this->isTestMode = $isTestMode;
    }

    /**
     * Displays the Realty Manager HTML.
     *
     * @param string $unused (not used)
     * @param array $conf TypoScript configuration for the plugin
     *
     * @return string HTML for the plugin
     */
    public function main($unused, array $conf)
    {
        $this->init($conf);
        $this->pi_initPIflexForm();

        $viewConfiguration = Tx_Oelib_ConfigurationRegistry
            ::get('plugin.tx_realty_pi1.views.' . $this->getCurrentView());

        if (!$viewConfiguration->getAsBoolean('cache')
            && $this->cObj->getUserObjectType() === ContentObjectRenderer::OBJECTTYPE_USER
        ) {
            $this->cObj->convertToUserIntObject();
            return '';
        }

        $this->getTemplateCode();
        $this->setLabels();

        $this->internal['currentTable'] = 'tx_realty_objects';
        $this->ensureIntegerPiVars([
            'remove',
            'showUid',
            'delete',
            'owner',
            'uid',
        ]);

        // Checks the configuration and displays any errors.
        // The direct return value from $this->checkConfiguration() is not
        // used as this would ignore any previous error messages.
        $this->setFlavor($this->getCurrentView());
        $this->checkConfiguration();

        $errorViewHtml = $this->checkAccessAndGetHtmlOfErrorView();
        $result = $this->pi_wrapInBaseClass(
            ($errorViewHtml === '' ? $this->getHtmlForCurrentView() : $errorViewHtml) .
            $this->getWrappedConfigCheckMessage()
        );

        return $result;
    }

    /**
     * Returns the HTML for the current view.
     *
     * @see Bug #2432
     *
     * @return string HTML for the current view, will not be empty
     */
    private function getHtmlForCurrentView()
    {
        $listViewType = '';
        $result = '';

        switch ($this->getCurrentView()) {
            case 'filter_form':
                /** @var \tx_realty_filterForm $filterForm */
                $filterForm = GeneralUtility::makeInstance(\tx_realty_filterForm::class, $this->conf, $this->cObj);
                $result = $filterForm->render($this->piVars);
                break;
            case 'single_view':
                /** @var \tx_realty_pi1_SingleView $singleView */
                $singleView = GeneralUtility::makeInstance(
                    \tx_realty_pi1_SingleView::class,
                    $this->conf,
                    $this->cObj,
                    $this->isTestMode
                );
                $result = $singleView->render($this->piVars);

                if ($result === '') {
                    $this->setEmptyResultView();
                    Tx_Oelib_HeaderProxyFactory::getInstance()->getHeaderProxy()
                        ->addHeader('Status: 404 Not Found');
                    $result = $this->getSubpart('SINGLE_VIEW');
                }
                break;
            case 'contact_form':
                /** @var \tx_realty_contactForm $contactForm */
                $contactForm = GeneralUtility::makeInstance(\tx_realty_contactForm::class, $this->conf, $this->cObj);
                $formData = $this->piVars;
                /** @var \tx_realty_pi1_FavoritesListView $favoritesList */
                $favoritesList = GeneralUtility::makeInstance(
                    \tx_realty_pi1_FavoritesListView::class,
                    $this->conf,
                    $this->cObj
                );
                $formData['summaryStringOfFavorites'] = $favoritesList->createSummaryStringOfFavorites();
                $result = $contactForm->render($formData);
                break;
            case 'fe_editor':
                /** @var \tx_realty_frontEndEditor $frontEndEditor */
                $frontEndEditor = GeneralUtility::makeInstance(
                    \tx_realty_frontEndEditor::class,
                    $this->conf,
                    $this->cObj,
                    $this->piVars['showUid'],
                    'editor'
                );
                $result = $frontEndEditor->render();
                break;
            case 'image_upload':
                /** @var \tx_realty_frontEndImageUpload $imageUpload */
                $imageUpload = GeneralUtility::makeInstance(
                    \tx_realty_frontEndImageUpload::class,
                    $this->conf,
                    $this->cObj,
                    $this->piVars['showUid'],
                    'imageUpload'
                );
                $result = $imageUpload->render();
                break;
            case 'offerer_list':
                /** @var \tx_realty_offererList $offererList */
                $offererList = GeneralUtility::makeInstance(\tx_realty_offererList::class, $this->conf, $this->cObj);
                $result = $offererList->render();
                break;
            case 'favorites':
                $listViewType = 'favorites';
                break;
            case 'my_objects':
                $listViewType = 'my_objects';
                break;
            case 'objects_by_owner':
                $listViewType = 'objects_by_owner';
                break;
            default:
                $listViewType = 'realty_list';
        }
        if ($listViewType !== '') {
            $listView = tx_realty_pi1_ListViewFactory::make(
                $listViewType,
                $this->conf,
                $this->cObj
            );
            $result = $listView->render($this->piVars);
        }

        return $result;
    }

    /**
     * Checks whether a user has access to the current view and returns the HTML
     * of an error view if not.
     *
     * @return string HTML for the error view, will be empty if a user has
     *                access
     */
    private function checkAccessAndGetHtmlOfErrorView()
    {
        // This will be moved to the access check when Bug #1480 is fixed.
        if (!$this->getConfValueBoolean('requireLoginForSingleViewPage') && $this->getCurrentView() === 'single_view') {
            return '';
        }

        try {
            /** @var \tx_realty_pi1_AccessCheck $accessCheck */
            $accessCheck = GeneralUtility::makeInstance(\tx_realty_pi1_AccessCheck::class);
            $accessCheck->checkAccess($this->getCurrentView(), $this->piVars);
            $result = '';
        } catch (Tx_Oelib_Exception_AccessDenied $exception) {
            /** @var \tx_realty_pi1_ErrorView $errorView */
            $errorView = GeneralUtility::makeInstance(\tx_realty_pi1_ErrorView::class, $this->conf, $this->cObj);
            $result = $errorView->render([$exception->getMessage()]);
        }

        return $result;
    }

    /**
     * Sets the view to an empty result message specific for the requested view.
     *
     * @return void
     */
    private function setEmptyResultView()
    {
        $view = $this->getCurrentView();
        $noResultsMessage = 'message_noResultsFound_' . $view;

        $this->setMarker(
            'message_noResultsFound',
            $this->translate($noResultsMessage)
        );
        $this->setSubpart(
            $view . '_result',
            $this->getSubpart('EMPTY_RESULT_VIEW')
        );
    }

    /**
     * Returns the current view.
     *
     * @return string Name of the current view ('realty_list',
     *                'contact_form', 'favorites', 'fe_editor',
     *                'filter_form', 'image_upload',
     *                'my_objects', 'offerer_list' or 'objects_by_owner'),
     *                will not be empty.
     *                If no view is set, 'realty_list' is returned as this
     *                is the fallback case.
     */
    private function getCurrentView()
    {
        $whatToDisplay = $this->getConfValueString('what_to_display');

        if (in_array($whatToDisplay, [
            'realty_list',
            'single_view',
            'favorites',
            'filter_form',
            'contact_form',
            'my_objects',
            'offerer_list',
            'objects_by_owner',
            'fe_editor',
            'image_upload',
        ])) {
            $result = $whatToDisplay;
        } else {
            $result = 'realty_list';
        }

        return $result;
    }

    /**
     * Checks whether displaying the single view page currently is allowed. This
     * depends on whether currently a FE user is logged in and whether, per
     * configuration, access to the details page is allowed even when no user is
     * logged in.
     *
     * @return bool TRUE if the details page is allowed to be viewed,
     *                 FALSE otherwise
     */
    public function isAccessToSingleViewPageAllowed()
    {
        return Tx_Oelib_FrontEndLoginManager::getInstance()->isLoggedIn()
            || !$this->getConfValueBoolean('requireLoginForSingleViewPage');
    }

    /**
     * Returns the prefix for the configuration to check, e.g. "plugin.tx_seminars_pi1.".
     *
     * @return string the namespace prefix, will end with a dot
     */
    public function getTypoScriptNamespace()
    {
        return 'plugin.tx_realty_pi1.';
    }
}
