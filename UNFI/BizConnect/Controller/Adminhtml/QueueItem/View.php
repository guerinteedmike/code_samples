<?php

namespace UNFI\BizConnect\Controller\Adminhtml\QueueItem;

/**
 * Class Index
 * @package UNFI\BizConnect\Controller\Adminhtml\QueueItem
 */
class View extends \UNFI\BizConnect\Controller\Adminhtml\QueueItem
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'UNFI_BizConnect::queueitem_view';

    /**
     * Execute action based on request and return result
     *
     * Note: Request will be added as operation argument in future
     *
     * @return \Magento\Backend\Model\View\Result\Page
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function execute()
    {
        // if no id we send them back to index
        $id = $this->getRequest()->getParam("id");
        if(!$id)
        {
            $this->_redirect('bizconnect/queueitem/index');
        }

        $resultPage = $this->_resultPageFactory->create();

        /**
         * Set active menu item
         */
        $resultPage->setActiveMenu('UNFI_BizConnect::bizconnect');
        $resultPage->getConfig()->getTitle()->prepend(__('View Queue Item'));

        /**
         * Add breadcrumb item
         */
       $resultPage->addBreadcrumb(__('QueueItems'), __('QueueItems'));
       $resultPage->addBreadcrumb(__('Manage QueueItems'), __('Manage QueueItems'));

        return $resultPage;
    }
}