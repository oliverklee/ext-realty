<?php

/**
 * This class provides functionality to check whether a front-end user has
 * access to a front-end page.
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 */
class tx_realty_pi1_AccessCheck
{
    /**
     * Checks access for the provided type of view and the current piVars.
     *
     * @param string $flavor
     *        the flavor for which to check the access, must be within the allowed values for "what_to_display"
     * @param array $piVars
     *        Form data array with the keys "showUid" and "delete" which can contain the UID of the object to check the
     *     access for. The fe_editor and image_upload can only be checked properly if "showUid" is provided. A UID
     *     provided with "delete" is needed for the my_objects view.
     *
     * @throws Tx_Oelib_Exception_AccessDenied
     *         if access is denied, with the reason of denying as a locallang key
     *
     * @return void
     */
    public function checkAccess($flavor, array $piVars = [])
    {
        switch ($flavor) {
            case 'fe_editor':
                $this->isFrontEndUserLoggedIn();
                $this->realtyObjectExistsInDatabase($piVars['showUid']);
                $this->frontEndUserOwnsObject($piVars['showUid']);
                $this->checkObjectLimit($piVars['showUid']);
                break;
            case 'image_upload':
                $this->isFrontEndUserLoggedIn();
                $this->isRealtyObjectUidProvided($piVars['showUid']);
                $this->realtyObjectExistsInDatabase($piVars['showUid']);
                $this->frontEndUserOwnsObject($piVars['showUid']);
                break;
            case 'my_objects':
                $this->isFrontEndUserLoggedIn();
                $this->realtyObjectExistsInDatabase($piVars['delete']);
                $this->frontEndUserOwnsObject($piVars['delete']);
                break;
            case 'single_view':
                // When Bug #1480 is fixed, the access check should become
                // responsible for checking the configuration for
                // "requireLoginForSingleViewPage" and then only check whether
                // a user is logged in if this is at all necessary.
                $this->isFrontEndUserLoggedIn();
                break;
            default:
                break;
        }
    }

    /**
     * Checks whether a front-end user is logged in. Sets a 403 header and
     * throws the corresponding error message key if no user is logged in.
     *
     * @throws Tx_Oelib_Exception_AccessDenied if no front-end user is logged in
     *
     * @return void
     */
    private function isFrontEndUserLoggedIn()
    {
        if (!Tx_Oelib_FrontEndLoginManager::getInstance()->isLoggedIn()) {
            Tx_Oelib_HeaderProxyFactory::getInstance()->getHeaderProxy()
                ->addHeader('Status: 403 Forbidden');
            throw new Tx_Oelib_Exception_AccessDenied('message_please_login', 1333036432);
        }
    }

    /**
     * Checks whether a non-zero UID for the realty object was provided.
     *
     * @throws Tx_Oelib_Exception_AccessDenied if the realty object UID is zero
     *
     * @param int $realtyObjectUid UID of the object, must be >= 0
     *
     * @return void
     */
    private function isRealtyObjectUidProvided($realtyObjectUid)
    {
        if ($realtyObjectUid > 0) {
            return;
        }

        Tx_Oelib_HeaderProxyFactory::getInstance()->getHeaderProxy()
            ->addHeader('Status: 404 Not Found');
        throw new Tx_Oelib_Exception_AccessDenied('message_noResultsFound_image_upload', 1333036450);
    }

    /**
     * Checks whether the realty object exists in the database and is
     * non-deleted. A hidden object is considered to be exsistent. A zero UID
     * is considered to stand for a new realty record and therefore accepted.
     *
     * @throws Tx_Oelib_Exception_AccessDenied if the realty object does not exist in the database
     *
     * @param int $realtyObjectUid UID of the object, must be >= 0
     *
     * @return void
     */
    private function realtyObjectExistsInDatabase($realtyObjectUid)
    {
        /** @var tx_realty_Mapper_RealtyObject $mapper */
        $mapper = Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_RealtyObject::class);
        if ($realtyObjectUid === 0 || $mapper->existsModel($realtyObjectUid, true)) {
            return;
        }

        Tx_Oelib_HeaderProxyFactory::getInstance()->getHeaderProxy()->addHeader('Status: 404 Not Found');
        throw new Tx_Oelib_Exception_AccessDenied('message_noResultsFound_fe_editor', 1333036458);
    }

    /**
     * Checks whether the front-end user is the owner and therefore authorized
     * to access a realty record. New realty objects (with UID = 0) are
     * considered to be editable by every logged-in user.
     *
     * @param int $realtyObjectUid UID of the realty object for which to the user authorization, must be >= 0
     *
     * @throws Tx_Oelib_Exception_AccessDenied if the front-end user does not own the object
     *
     * @return void
     */
    private function frontEndUserOwnsObject($realtyObjectUid)
    {
        if ($realtyObjectUid === 0) {
            return;
        }

        /** @var tx_realty_Mapper_RealtyObject $mapper */
        $mapper = Tx_Oelib_MapperRegistry::get(\tx_realty_Mapper_RealtyObject::class);
        /** @var tx_realty_Model_RealtyObject $object */
        $object = $mapper->find($realtyObjectUid);
        /** @var tx_realty_Model_FrontEndUser $loggedInUser */
        $loggedInUser = Tx_Oelib_FrontEndLoginManager::getInstance()->getLoggedInUser('tx_realty_Mapper_FrontEndUser');
        if ((int)$object->getProperty('owner') === $loggedInUser->getUid()) {
            return;
        }

        Tx_Oelib_HeaderProxyFactory::getInstance()->getHeaderProxy()->addHeader('Status: 403 Forbidden');
        throw new Tx_Oelib_Exception_AccessDenied('message_access_denied', 1333036471);
    }

    /**
     * Checks if the logged-in front-end user is allowed to enter new objects.
     *
     * @param int $realtyObjectUid UID of the object, must be >= 0
     *
     * @throws Tx_Oelib_Exception_AccessDenied if the front-end user is not allowed to enter a new object
     *
     * @return void
     */
    private function checkObjectLimit($realtyObjectUid)
    {
        if ($realtyObjectUid > 0) {
            return;
        }
        /** @var tx_realty_Model_FrontEndUser $user */
        $user = Tx_Oelib_FrontEndLoginManager::getInstance()->getLoggedInUser('tx_realty_Mapper_FrontEndUser');
        if ($user->canAddNewObjects()) {
            return;
        }

        Tx_Oelib_HeaderProxyFactory::getInstance()->getHeaderProxy()
            ->addHeader('Status: 403 Forbidden');
        throw new Tx_Oelib_Exception_AccessDenied('message_no_objects_left', 1333036483);
    }
}
