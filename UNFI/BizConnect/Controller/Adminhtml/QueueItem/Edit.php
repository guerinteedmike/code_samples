<?php

namespace UNFI\BizConnect\Controller\Adminhtml\QueueItem;

/**
 * Class Index
 * @package UNFI\BizConnect\Controller\Adminhtml\QueueItem
 */
class Edit extends \UNFI\BizConnect\Controller\Adminhtml\QueueItem
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'UNFI_BizConnect::queueitem_admin';

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
        $id = $this->getRequest()->getParam("id");

        $resultPage = $this->_resultPageFactory->create();
        $resultPage->setActiveMenu('Magento_BizConnect::bizconnect_queueitem');

        /**
         * Set active menu item
         */
        if(empty($id))
        {
            $resultPage->getConfig()->getTitle()->prepend(__('New Queue Item'));
        } else {

            $model = $this->_queueItemFactory->create();
            $model->load($id);
            if(!$model->getId())
            {
                $this->messageManager->addErrorMessage(__('Queue Item %id does not exists.', ['id' => $id]));
                /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
                $resultRedirect = $this->resultRedirectFactory->create();
                return $resultRedirect->setPath('*/*/');
            } else {
                $data = $this->getRequest()->getPostValue();
                $model->setData($data);
                if($id == $model->getId())
                $model->save();
            }

            $resultPage->getConfig()->getTitle()->prepend(__('Edit Queue Item'));
        }

        /**
         * Add breadcrumb item
         */
        $resultPage->addBreadcrumb(__('QueueItems'), __('QueueItems'));
        $resultPage->addBreadcrumb(__('Manage QueueItems'), __('Manage QueueItems'));

        return $resultPage;
    }


}