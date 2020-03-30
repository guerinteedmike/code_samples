<?php

namespace Guerinteed\Slider\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

/**
 * Interface for slidergroup search results.
 * @api
 */
interface SliderGroupSearchResultsInterface extends SearchResultsInterface
{
    /**
     * Get slidergroup list.
     *
     * @return \Guerinteed\Slider\Api\Data\SliderGroupInterface
     */
    public function getItems();

    /**
     * Set slidergroup list.
     *
     * @param \Guerinteed\Slider\Api\Data\SliderGroupInterface[] $items
     * @return $this
     */
    public function setItems(array $items = null);
}
