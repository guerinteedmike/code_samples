<?php


namespace UNFI\BizConnect\Api\Data;

interface ShipReqSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{

    /**
     * Get ShipReq list.
     * @return \UNFI\BizConnect\Api\Data\ShipReqInterface[]
     */
    public function getItems();

    /**
     * Set ship_req_id list.
     * @param \UNFI\BizConnect\Api\Data\ShipReqInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
