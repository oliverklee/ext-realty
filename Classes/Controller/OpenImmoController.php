<?php

namespace OliverKlee\Realty\Controller;

use TYPO3\CMS\Backend\View\BackendTemplateView;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

/**
 * Controller for the OpenImmo back-end module.
 *
 * @author Oliver Klee <typo3-coding@oliverklee.de>
 */
class OpenImmoController extends ActionController
{
    /**
     * @var
     */
    protected $defaultViewObjectName = BackendTemplateView::class;

    /**
     * @var \tx_realty_openImmoImport
     */
    protected $importService = null;

    /**
     * @param \tx_realty_openImmoImport $importService
     *
     * @return void
     */
    public function injectImportService(\tx_realty_openImmoImport $importService)
    {
        $this->importService = $importService;
    }

    /**
     * @return void
     */
    public function indexAction()
    {
    }

    /**
     * @return void
     */
    public function importAction()
    {
        $importResults = '';
        try {
            $importResults = $this->importService->importFromZip();
            $success = $this->importService->wasSuccessful();
        } catch (\Exception $exception) {
            $backTrace = \json_encode(
                $exception->getTrace(),
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
            );
            $importResults .= $exception->getMessage() . "\n" . $backTrace;
            $success = false;
        }
        $this->view->assign('importResults', $importResults);
        $this->view->assign('importStatus', $success ? 0 : 2);
    }
}
