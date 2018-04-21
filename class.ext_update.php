<?php

/**
 * This class offers functions to update the database from one version to
 * another and to reorganize the district-city relations.
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
