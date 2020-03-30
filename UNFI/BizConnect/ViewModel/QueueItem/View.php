<?php

namespace UNFI\BizConnect\ViewModel\QueueItem;

use UNFI\BizConnect\Model\QueueItemFactory;
use UNFI\BizConnect\Api\Data\QueueItemInterface;
use Magento\Framework\App\RequestInterface;

class View implements \Magento\Framework\View\Element\Block\ArgumentInterface
{
    /**
     * @var \UNFI\BizConnect\Model\QueueItemFactory
     */
    protected $queueItemFactory;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    public function __construct(
        \UNFI\BizConnect\Model\QueueItemFactory $queueItemFactory,
        \Magento\Framework\App\RequestInterface $request
    ){
        $this->queueItemFactory = $queueItemFactory;
        $this->request = $request;
    }

    public function getQueueItemData()
    {
        $id = $this->request->getParam("id");
        $model = $this->queueItemFactory->create();
        return $model->load($id);
    }


}