<?php
declare(strict_types=1);

namespace Guerinteed\Slider\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class SliderGroup extends AbstractDb
{
    /**
     * Initializes slider group resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('guerinteed_slider_group', 'group_id');
    }
}
