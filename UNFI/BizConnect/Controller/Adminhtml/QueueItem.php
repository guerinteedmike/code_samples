<?php

namespace UNFI\BizConnect\Controller\Adminhtml;

/**
 * Class QueueItem
 */
abstract class QueueItem extends \Magento\Backend\App\Action
{
    /**
     * @var \UNFI\BizConnect\Model\QueueItemFactory;
     */
    protected $_queueItemFactory;

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $_resultPageFactory;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory ,
     * @param \UNFI\BizConnect\Model\QueueItemFactory $queueItemFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \UNFI\BizConnect\Model\QueueItemFactory $queueItemFactory
    ){
        parent::__construct($context);
        $this->_resultPageFactory = $resultPageFactory;
        $this->_queueItemFactory = $queueItemFactory;
    }

}