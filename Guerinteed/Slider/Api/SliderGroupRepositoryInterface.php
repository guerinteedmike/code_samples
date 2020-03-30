<?php

namespace Guerinteed\Slider\Api;

/**
 * SliderGroup CRUD interface.
 * @api
 */
interface SliderGroupRepositoryInterface
{
    /**
     * Save SliderGroup.
     *
     * @param \Guerinteed\Slider\Api\Data\SliderGroupInterface $sliderGroup
     * @return \Guerinteed\Slider\Api\Data\SliderGroupInterface
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function save(\Guerinteed\Slider\Api\Data\SliderGroupInterface $sliderGroup);

    /**
     * @param int $sliderGroupId
     * @return \Guerinteed\Slider\Api\Data\SliderGroupInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById($sliderGroupId);

    /**
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Guerinteed\Slider\Api\Data\SliderGroupSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria);

    /**
     * @param \Guerinteed\Slider\Api\Data\SliderGroupInterface $sliderGroup
     * @return bool true on success
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     */
    public function delete(\Guerinteed\Slider\Api\Data\SliderGroupInterface $sliderGroup);

    /**
     * @param int $sliderGroupId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function deleteById($sliderGroupId);
}
