<?php

namespace OliverKlee\Realty\SchedulerTask;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Scheduler\Task\AbstractTask;

/**
 * Scheduler task for running the OpenImmo import.
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class OpenImmoImport extends AbstractTask
{
    /**
     * @return bool
     */
    public function execute()
    {
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        /** @var \tx_realty_openImmoImport $importService */
        $importService = $objectManager->get(\tx_realty_openImmoImport::class);
        $importService->importFromZip();

        return $importService->wasSuccessful();
    }
}
