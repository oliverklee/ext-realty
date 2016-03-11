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
 * This class represents a list view for testing purposes.
 *
 *
 * @author Bernd Schönbach <bernd@oliverklee.de>
 */
class tx_realty_tests_fixtures_TestingListView extends tx_realty_pi1_AbstractListView
{
    /**
     * @var string the list view type to display
     */
    protected $currentView = 'realty_list';

    /**
     * @var string the locallang key to the label of a list view
     */
    protected $listViewLabel = 'label_weofferyou';

    /**
     * @var bool whether Google Maps should be shown in this view
     */
    protected $isGoogleMapsAllowed = true;

    /**
     * Initializes some view-specific data.
     *
     * @return void
     */
    protected function initializeView()
    {
    }

    /**
     * Creates the URL of the current page. The URL will contain a flag to
     * disable caching as this URL also is used for forms with method="post".
     *
     * The URL will contain the current piVars that are relevant for the list
     * view if $keepPiVars is set to TRUE.
     *
     * The URL will already be htmlspecialchared.
     *
     * @param bool $keepPiVars whether the current piVars should be kept
     * @param string[] $removeKeys
     *        the keys to remove from the piVar data before processing the URL,
     *        may be empty
     *
     * @return string htmlspecialchared URL of the current page, will not
     *                be empty
     */
    public function getSelfUrl($keepPiVars = true, array $removeKeys = array())
    {
        return parent::getSelfUrl($keepPiVars, $removeKeys);
    }
}
