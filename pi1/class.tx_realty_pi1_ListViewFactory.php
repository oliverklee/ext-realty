<?php

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * This class can instantiate list views.
 *
 * @author Bernd Schönbach <bernd@oliverklee.de>
 */
class tx_realty_pi1_ListViewFactory
{
    /**
     * Creates an instance of a list view.
     *
     * @param string $type
     *        the list view type to create, must be one of "favorites",
     *        "my_objects", "objects_by_owner" or "realty_list"
     * @param array $conf TypoScript configuration for the plugin
     * @param ContentObjectRenderer $contentObjectRenderer the parent cObj content, needed for the flexforms
     *
     * @return tx_realty_pi1_AbstractListView
     *         an instance of the list view, will be one of "tx_realty_pi1_FavoritesListView", "tx_realty_pi1_MyObjectsListView",
     *         "tx_realty_pi1_ObjectsByOwnerListView", or "tx_realty_pi1_DefaultListView"
     */
    public static function make($type, array $conf, ContentObjectRenderer $contentObjectRenderer)
    {
        switch ($type) {
            case 'favorites':
                $viewName = 'tx_realty_pi1_FavoritesListView';
                break;
            case 'my_objects':
                $viewName = 'tx_realty_pi1_MyObjectsListView';
                break;
            case 'objects_by_owner':
                $viewName = 'tx_realty_pi1_ObjectsByOwnerListView';
                break;
            case 'realty_list':
                $viewName = 'tx_realty_pi1_DefaultListView';
                break;
            default:
                throw new InvalidArgumentException('The given list view type "' . $type . '" is invalid.', 1333036578);
        }

        return GeneralUtility::makeInstance($viewName, $conf, $contentObjectRenderer);
    }
}
