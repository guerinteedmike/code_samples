<?php

namespace UNFI\BizConnect\Model\QueueItem\Source;

use UNFI\BizConnect\Api\Data\QueueItemInterface;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class MessageType
 */
class MessageType implements OptionSourceInterface
{

    /**
     * @var \UNFI\BizConnect\Model\QueueItem
     */
    protected $queueItem;

    /**
     * Constructor
     *
     * @param QueueItemInterface $queueItem
     */
    public function __construct(
        QueueItemInterface $queueItem
    ){
        $this->queueItem = $queueItem;
    }

    /**
     * @inheritdoc
     */
    public function toOptionArray()
    {
        //var_dump($this->queueItem->MessageTypeSelectOptions());
        return $this->queueItem->MessageTypeSelectOptions();
    }
}
