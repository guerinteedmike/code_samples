<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace UNFI\BizConnect\Controller\Adminhtml\QueueItem;

class NewAction extends \UNFI\BizConnect\Controller\Adminhtml\QueueItem
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'UNFI_BizConnect::queueitem_admin';

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|\Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Forward $resultForward */
        $resultPage = $this->_resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->prepend(__('New Queue Item'));
        $resultPage->setActiveMenu('Magento_BizConnect::bizconnect_queueitem');
        $resultPage->addBreadcrumb(__('QueueItems'), __('QueueItems'));
        $resultPage->addBreadcrumb(__('Manage QueueItems'), __('Manage QueueItems'));
        return $resultPage;
    }
}
