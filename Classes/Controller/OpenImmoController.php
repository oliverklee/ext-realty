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
        $result = $this->importService->importFromZip();
        $this->view->assign('importResults', $result);
    }
}
