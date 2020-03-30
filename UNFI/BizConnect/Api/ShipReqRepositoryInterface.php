<?php

namespace UNFI\BizConnect\Api;

interface ShipReqRepositoryInterface
{

    /**
     * Save ShipReq
     * @param \UNFI\BizConnect\Api\Data\ShipReqInterface $shipReq
     * @return \UNFI\BizConnect\Api\Data\ShipReqInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(
        \UNFI\BizConnect\Api\Data\ShipReqInterface $shipReq
    );

    /**
     * Retrieve ShipReq
     * @param string $shipreqId
     * @return \UNFI\BizConnect\Api\Data\ShipReqInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getById($shipreqId);

    /**
     * Retrieve ShipReq matching the specified criteria.
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \UNFI\BizConnect\Api\Data\ShipReqSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    );

    /**
     * Delete ShipReq
     * @param \UNFI\BizConnect\Api\Data\ShipReqInterface $shipReq
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(
        \UNFI\BizConnect\Api\Data\ShipReqInterface $shipReq
    );

    /**
     * Delete ShipReq by ID
     * @param string $shipreqId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($shipreqId);
}
