<?php

namespace UNFI\BizConnect\Controller\Adminhtml\QueueItem;

/**
 * Class Index
 * @package UNFI\BizConnect\Controller\Adminhtml\QueueItem
 */
class Delete extends \UNFI\BizConnect\Controller\Adminhtml\QueueItem
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
     * @return mixed | \Magento\Backend\Model\View\Result\Page
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam("id");
        $resultPage = $this->_resultPageFactory->create();

        /**
         * Set active menu item
         */
        if(empty($id)) {
            $resultPage->getConfig()->getTitle()->prepend(__('New Queue Item'));
        } else {

            try {
                $model = $this->_queueItemFactory->create();

                /** @var \UNFI\BizConnect\Model\QueueItem $model */
                $model = $this->_queueItemFactory->create();
                $model->setId($id);
                $model->delete();
                $this->messageManager->addSuccess(__('QueueItem was successfully deleted.'));
                $this->_redirect('*/*/');
                return;

            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
                $this->_redirect('*/*/edit', ['id' => $id]);
                return;
            }

        }
        $this->messageManager->addError(__('We can\'t find the QueueItem to delete.'));
        $this->_redirect('*/*/');
    }
}