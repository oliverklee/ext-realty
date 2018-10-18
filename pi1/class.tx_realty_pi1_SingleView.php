<?php

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * This class renders the single view.
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class tx_realty_pi1_SingleView extends tx_realty_pi1_FrontEndView
{
    /**
     * @var bool whether the constructor is called in test mode
     */
    private $isTestMode = false;

    /**
     * The constructor.
     *
     * @param array $configuration TypoScript configuration for the plugin
     * @param ContentObjectRenderer $contentObjectRenderer the parent cObj content, needed for the flexforms
     * @param bool $isTestMode whether the class is instantiated in test mode
     */
    public function __construct(
        array $configuration,
        ContentObjectRenderer $contentObjectRenderer,
        $isTestMode = false
    ) {
        parent::__construct($configuration, $contentObjectRenderer, $isTestMode);
        $this->isTestMode = $isTestMode;
    }

    /**
     * Returns the single view as HTML.
     *
     * @param array $piVars piVars array, must contain the key "showUid" with a valid object UID as value
     *
     * @return string HTML for the single view or an empty string if the
     *                provided UID is no UID of a valid realty object
     */
    public function render(array $piVars = [])
    {
        if (!$this->existsRealtyObject($piVars['showUid'])) {
            return '';
        }

        $this->piVars = $piVars;

        $this->createSingleView($piVars['showUid']);

        return $this->getSubpart('SINGLE_VIEW');
    }

    /**
     * Checks whether the provided UID matches a loadable realty object. It is
     * loadable if the provided UID is the UID of an existent, non-deleted
     * realty object that is either non-hidden, or the logged-in FE user owns
     * the object.
     *
     * @param int $uid UID of the realty object, must be >= 0
     *
     * @return bool TRUE if the object has been loaded, FALSE otherwise
     */
    private function existsRealtyObject($uid)
    {
        if ($uid <= 0) {
            return false;
        }

        /** @var tx_realty_Mapper_RealtyObject $realtyObjectMapper */
        $realtyObjectMapper = Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_RealtyObject::class);
        if (!$realtyObjectMapper->existsModel($uid, true)) {
            return false;
        }
        /** @var tx_realty_Model_RealtyObject $realtyObject */
        $realtyObject = $realtyObjectMapper->find($uid);
        if ((bool)$realtyObject->getProperty('deleted')) {
            return false;
        }

        $result = false;

        if (!$realtyObject->isHidden()) {
            $result = true;
        } elseif (Tx_Oelib_FrontEndLoginManager::getInstance()->isLoggedIn()) {
            /** @var tx_realty_Model_FrontEndUser $loggedInUser */
            $loggedInUser =
                Tx_Oelib_FrontEndLoginManager::getInstance()->getLoggedInUser('tx_realty_Mapper_FrontEndUser');
            $result = $loggedInUser->getUid() === (int)$realtyObject->getProperty('owner');
        }

        return $result;
    }

    /**
     * Creates the single view.
     *
     * @param int $uid UID of the realty object for which to create the single view, must be > 0
     *
     * @return void
     */
    private function createSingleView($uid)
    {
        $this->setPageTitle($uid);

        $hasTextContent = false;
        $configuredViews = GeneralUtility::trimExplode(
            ',',
            $this->getConfValueString('singleViewPartsToDisplay'),
            true
        );

        foreach (
            [
                'nextPreviousButtons',
                'heading',
                'address',
                'description',
                'documents',
                'price',
                'overviewTable',
                'offerer',
                'contactButton',
                'googleMaps',
                'addToFavoritesButton',
                'furtherDescription',
                'imageThumbnails',
                'backButton',
                'printPageButton',
                'status',
            ] as $key) {
            $viewContent = in_array($key, $configuredViews, true)
                ? $this->getView($uid, $key)
                : '';

            $this->setSubpart($key, $viewContent, 'field_wrapper');
            if ($viewContent !== '' && $key !== 'imageThumbnails') {
                $hasTextContent = true;
            }
        }

        $this->hideActionButtonsIfNecessary($configuredViews);
        // Sets an additional class name if the "image thumbnails" view
        // is activated.
        $this->setMarker(
            'with_images',
            in_array('imageThumbnails', $configuredViews, true) ? ' with-images' : ''
        );

        if (!$hasTextContent) {
            $this->hideSubparts('field_wrapper_texts');
        }
    }

    /**
     * Sets the title of the page for display and for use in indexed search
     * results.
     *
     * @param int $uid UID of the realty object for which to set the title, must be > 0
     *
     * @return void
     */
    private function setPageTitle($uid)
    {
        /** @var tx_realty_Mapper_RealtyObject $realtyObjectMapper */
        $mapper = Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_RealtyObject::class);
        /** @var tx_realty_Model_RealtyObject $realtyObject */
        $realtyObject = $mapper->find($uid);
        $title = $realtyObject->getProperty('title');
        if ($title === '') {
            return;
        }

        $frontEndController = $this->getFrontEndController();
        $frontEndController->page['title'] = $title;
        $frontEndController->indexedDocTitle = $title;
    }

    /**
     * Returns the result of tx_realty_pi1_[$viewName]View::render().
     *
     * @param int $uid
     *        UID of the realty object for which to create the view, must be > 0
     * @param string $viewName
     *        key of the view to get, must be a part of the class name of possible view: tx_realty_pi1_[$viewName]View,
     *     must be case-sensitive apart from the first letter, must not be empty
     *
     * @return string the result of tx_realty_pi1_[$viewName]View::render(),
     *                will be empty if there is no data to display for the
     *                requested view
     */
    private function getView($uid, $viewName)
    {
        /** @var tx_realty_pi1_FrontEndView $view */
        $view = GeneralUtility::makeInstance(
            'tx_realty_pi1_' . ucfirst($viewName) . 'View',
            $this->conf,
            $this->cObj,
            $this->isTestMode
        );
        $view->piVars = $this->piVars;

        if ($viewName === 'googleMaps') {
            /** @var tx_realty_pi1_GoogleMapsView $view */
            $view->setMapMarker($uid);
        }

        return $view->render(['showUid' => $uid]);
    }

    /**
     * Hides the subpart actionButtons if the three action buttons
     * 'addToFavorites', 'printPage' and 'back' are hidden.
     *
     * @param string[] $displayedViews the views which are displayed, may be empty
     *
     * @return void
     */
    private function hideActionButtonsIfNecessary(array $displayedViews)
    {
        /** @var Tx_Oelib_Visibility_Tree $visibilityTree */
        $visibilityTree = GeneralUtility::makeInstance(
            Tx_Oelib_Visibility_Tree::class,
            [
                'actionButtons' => [
                    'addToFavoritesButton' => false,
                    'backButton' => false,
                    'printPageButton' => false,
                ],
            ]
        );
        $visibilityTree->makeNodesVisible($displayedViews);
        $this->hideSubpartsArray(
            $visibilityTree->getKeysOfHiddenSubparts(),
            'FIELD_WRAPPER'
        );
    }
}
