<?php

namespace OliverKlee\Realty\SchedulerTask;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Scheduler\Task\AbstractTask;

/**
 * Scheduler task for cleaning up image records that are orphaned after the OpenImmo import.
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class ImageCleanup extends AbstractTask
{
    /**
     * @return bool
     */
    public function execute()
    {
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        /** @var \tx_realty_cli_ImageCleanUp $cleanUpService */
        $cleanUpService = $objectManager->get(\tx_realty_cli_ImageCleanUp::class);
        $cleanUpService->checkUploadFolder();
        $cleanUpService->hideUnusedImagesInDatabase();
        $cleanUpService->deleteUnusedDocumentRecords();
        $cleanUpService->deleteUnusedFiles();

        return true;
    }
}
