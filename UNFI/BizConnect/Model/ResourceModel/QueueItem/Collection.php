<?php

namespace UNFI\BizConnect\Model\ResourceModel\QueueItem;

/**
 * QueueItem collection
 */
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\UNFI\BizConnect\Model\QueueItem::class, \UNFI\BizConnect\Model\ResourceModel\QueueItem::class);
    }
}
