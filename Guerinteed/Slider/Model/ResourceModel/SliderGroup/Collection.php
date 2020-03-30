<?php
declare(strict_types=1);

namespace Guerinteed\Slider\Model\ResourceModel\SliderGroup;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \Guerinteed\Slider\Model\SliderGroup::class,
            \Guerinteed\Slider\Model\ResourceModel\SliderGroup::class
        );
    }
}
