<?php

namespace UNFI\BizConnect\Controller\Adminhtml\QueueItem;

use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class Index
 * @package UNFI\BizConnect\Controller\Adminhtml\QueueItem
 */
class Save extends \UNFI\BizConnect\Controller\Adminhtml\QueueItem
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'UNFI_BizConnect::queueitem_admin';

    /**
     * @var DataPersistorInterface
     */
    protected $dataPersistor;

    /**
     * Save constructor.
     * @param \Magento\Backend\App\Action\Context $context
     * @param DataPersistorInterface $dataPersistor
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \UNFI\BizConnect\Model\QueueItemFactory $queueItemFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        DataPersistorInterface $dataPersistor,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \UNFI\BizConnect\Model\QueueItemFactory $queueItemFactory

    )
    {
        $this->dataPersistor = $dataPersistor;
        parent::__construct($context, $resultPageFactory, $queueItemFactory);
    }

    /**
     * Execute action based on request and return result
     *
     * Note: Request will be added as operation argument in future
     *
     * @return mixed | \Magento\Backend\Model\View\Result\Page
     * @throws \Magento\Framework\Exception\NotFoundException
     * @throws LocalizedException
     */
    public function execute()
    {

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        if ($this->getRequest()->getPostValue() && $this->_authorization->isAllowed('UNFI_BizConnect::queueitem_save')) {
            $data = $this->getRequest()->getPostValue();
            //var_dump($data);

            $id = $this->getRequest()->getParam('queueitem_id');

            $model = $this->_queueItemFactory->create();

            if ($id) {
                $model->load($id);
                if ($id != $model->getId()) {
                    throw new \Magento\Framework\Exception\LocalizedException(__('QueueItem to be updated was not found.'));
                }
            }

            unset($data['created_at']);
            unset($data['updated_at']);

            $model->setData($data);
            try {
                $model->save();
                $this->messageManager->addSuccessMessage(__('Queue Item was saved.'));
                $this->dataPersistor->clear('bizconnect_queueitem');
                if ($this->getRequest()->getParam('back')) {
                    return $resultRedirect->setPath('*/*/edit', ['queueitem_id' => $model->getId()]);
                }
                return $resultRedirect->setPath('*/*/');
            } catch (LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addExceptionMessage($e, __('Something went wrong while saving the Queue Item.'));
            }

            $this->dataPersistor->set('bizconnect_queueitem', $data);
            return $resultRedirect->setPath('*/*/edit', ['queueitem_id' => $this->getRequest()->getParam('queueitem_id')]);

        }
        return $resultRedirect->setPath('*/*/');
    }


}