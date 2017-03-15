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
 * This class offers functions to update the database from one version to
 * another and to reorganize the district-city relations.
 *
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class ext_update
{
    /**
     * Returns the update module content.
     *
     * @return string
     *         the update module content, will be empty if nothing was updated
     */
    public function main()
    {
        return '';
    }

    /**
     * Returns whether the update module may be accessed.
     *
     * @return bool
     *         TRUE if the update module may be accessed, FALSE otherwise
     */
    public function access()
    {
        return false;
    }
}
