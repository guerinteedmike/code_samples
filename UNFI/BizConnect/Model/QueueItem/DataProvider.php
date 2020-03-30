<?php

namespace UNFI\BizConnect\Model\QueueItem;

use UNFI\BizConnect\Model\ResourceModel\QueueItem\Collection;
use UNFI\BizConnect\Model\ResourceModel\QueueItem\CollectionFactory;
use UNFI\BizConnect\Model\QueueItem;

class DataProvider extends \Magento\Ui\DataProvider\AbstractDataProvider
{

    /**
     * @var Collection $collection
     */
    protected $collection;

    /**
     * @var array
     */
    protected $loadedData;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $collectionFactory
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        array $meta = [],
        array $data = []
    ){
        $this->collection = $collectionFactory->create();
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    /**
     * Get data
     *
     * @return array
     */
    public function getData()
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }
        $items = $this->collection->getItems();
        /** @var QueueItem $queueItem */
        foreach ($items as $queueItem) {
            $queueItem->load($queueItem->getId());
            // if needed, manipulate data here
            $this->loadedData[$queueItem->getId()] = $queueItem->getData();
        }
        return $this->loadedData;
    }

}