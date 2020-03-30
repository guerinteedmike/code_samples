<?php

namespace UNFI\BizConnect\Ui\Component\Listing\Column\QueueItem;

use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Ui\Component\Listing\Columns\Column;

class MessageType extends Column
{
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$items) {

                // @todo use model constants

                if ($items['message_type'] == 1) {
                    $items['message_type'] = 'Confirmation';
                } elseif ($items['message_type'] == 2) {
                    $items['message_type'] = 'Invoice';
                } elseif ($items['message_type'] == 3) {
                    $items['message_type'] = 'Tracking';
                } else {
                    $items['message_type'] = 'None';
                }

            }
        }
        return $dataSource;
    }
}