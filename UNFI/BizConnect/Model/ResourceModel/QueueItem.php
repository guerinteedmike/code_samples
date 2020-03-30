<?php

namespace UNFI\BizConnect\Model\ResourceModel;

/**
 * Class QueueItem
 * @package UNFI\BizConnect\Model\ResourceModel
 */
class QueueItem extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Resource initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('unfi_bizconnect_queueitem', 'queueitem_id');
    }

}