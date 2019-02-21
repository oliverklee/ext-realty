<?php

namespace OliverKlee\Realty\Import;

/**
 * This class handles the import (or update) of attachments for a single realty object.
 *
 * Usage:
 *
 * 1. create instance and pass the realty object
 * 2. start transaction
 * 3. add attachments
 * 4. finish transaction
 *
 * This process will save the updated realty object, save all new attachments, keep all updates attachments,
 * and will delete those attachments that have not been updated.
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class AttachmentImporter
{
    /**
     * @var \tx_realty_Model_RealtyObject
     */
    private $realtyObject = null;

    /**
     * @var bool
     */
    private $transactionIsInProgress = false;

    /**
     * sub keys: fullPath and title
     *
     * @var string[][]
     */
    private $attachmentsToBeAdded = [];

    /**
     * For better performance, the keys are the UIDs, and the values are "true"
     *
     * @var bool[]
     */
    private $uidsOfFilesToRemove = [];

    public function __construct(\tx_realty_Model_RealtyObject $realtyObject)
    {
        $this->realtyObject = $realtyObject;
    }

    /**
     * @return void
     *
     * @throws \BadMethodCallException
     */
    public function startTransaction()
    {
        $this->assertNoTransactionIsInProgress();
        $this->transactionIsInProgress = true;

        $this->ensureUidForRealtyObject();
        $this->extractAttachmentUids();
    }

    /**
     * @return void
     *
     * @throws \BadMethodCallException
     */
    private function assertNoTransactionIsInProgress()
    {
        if ($this->transactionIsInProgress) {
            throw new \BadMethodCallException(
                'This method cannot be called while a transaction is in progress.',
                1550840989
            );
        }
    }

    /**
     * @return void
     */
    private function ensureUidForRealtyObject()
    {
        if (!$this->realtyObject->hasUid()) {
            $this->realtyObject->writeToDatabase();
        }
    }

    /**
     * @return void
     */
    private function extractAttachmentUids()
    {
        foreach ($this->realtyObject->getAttachments() as $attachment) {
            $attachmentUid = $attachment->getOriginalFile()->getUid();
            $this->uidsOfFilesToRemove[$attachmentUid] = true;
        }
    }

    /**
     * Marks the given attachment to be added/updated.
     *
     * Note: This method does not save anything yet. Saving will be done in finishTransaction().
     *
     * @param string $fullPath
     * @param string $title
     *
     * @return void
     *
     * @throws \BadMethodCallException
     */
    public function addAttachment($fullPath, $title)
    {
        $this->assertTransactionIsInProgress();

        $this->attachmentsToBeAdded[] = ['fullPath' => $fullPath, 'title' => $title];
    }

    /**
     * @return void
     *
     * @throws \BadMethodCallException
     */
    private function assertTransactionIsInProgress()
    {
        if (!$this->transactionIsInProgress) {
            throw new \BadMethodCallException(
                'This method cannot be called without a transaction. Please call startTransaction first.',
                1550840936
            );
        }
    }

    /**
     * @return void
     *
     * @throws \BadMethodCallException
     */
    public function finishTransaction()
    {
        $this->assertTransactionIsInProgress();

        $this->processAddedAttachments();
        $this->processRemovedAttachments();
        $this->realtyObject->writeToDatabase();

        $this->transactionIsInProgress = false;
    }

    /**
     * @return void
     */
    private function processAddedAttachments()
    {
        foreach ($this->attachmentsToBeAdded as $attachment) {
            $fullPath = $attachment['fullPath'];
            $baseName = \basename($fullPath);
            $existingAttachment = $this->realtyObject->getAttachmentByBaseName($baseName);
            if ($existingAttachment !== null) {
                $fileUid = $existingAttachment->getOriginalFile()->getUid();
                unset($this->uidsOfFilesToRemove[$fileUid]);
            } else {
                $title = $attachment['title'];
                $this->realtyObject->addAndSaveAttachment($fullPath, $title);
            }
        }
    }

    /**
     * @return void
     */
    private function processRemovedAttachments()
    {
        foreach ($this->uidsOfFilesToRemove as $uid => $_) {
            $this->realtyObject->removeAttachmentByFileUid($uid);
        }
    }
}
