<?php

// @TODO update all loggers to work with JSON

namespace UNFI\BizConnect\Cron;

use Magento\Framework\Exception\AlreadyExistsException;
use UNFI\BizConnect\Api\Data\QueueItemInterface;
use UNFI\BizConnect\Model\QueueItem;

class ProcessQueueItemTracking extends \UNFI\BizConnect\Cron\ProcessQueueItemBase
{
    /**
     * @var array
     */
    protected $carrierData = [
        '1' => [
            'carrier_code' => 'ups',
            'title' => 'United Parcel Service',
        ],
        '9' => [
            'carrier_code' => 'usps',
            'title' => 'United States Postal Service',
        ],
        '8' => [
            'carrier_code' => 'minn',
            'title' => 'UPS Mail Innovations',
        ],
        '7' => [
            'carrier_code' => 'fedex',
            'title' => 'FedEx',
        ]
    ];

    public function execute()
    {
        if (!$this->_dataHelper->getIsIntegrationEnabled(strtolower($this->_queueItemInterface::MESSAGE_TYPE_TRACKING_LABEL), self::AREA)) {
            $this->_logger->critical(
                sprintf("[%s]: The %s integration is disabled.",
                    strtoupper($this->_queueItemInterface::MESSAGE_TYPE_TRACKING_LABEL),
                    $this->_queueItemInterface::MESSAGE_TYPE_TRACKING_LABEL
                )
            );
            return;
        }

        $this->_init(
            $this->_queueItemInterface::MESSAGE_TYPE_TRACKING,
            $this->_queueItemInterface::MESSAGE_TYPE_TRACKING_LABEL
        );

        /** @var \UNFI\BizConnect\Model\ResourceModel\QueueItem\Collection $queueCollectionItems */
        $queueCollectionItems = $this->getQueueItemsToProcess();

        if ($queueCollectionItems->count() == 0) {
            $this->_logger->critical(
                sprintf("[%s]: No tracking queue items found.",
                    strtoupper($this->_queueItemInterface::MESSAGE_TYPE_TRACKING_LABEL)
                )
            );
            return;
        }

        /** @var /UNFI\BizConnect\Api\Data\QueueItemInterface $q_item */
        foreach ($queueCollectionItems as $q_item) {

            /** @var array $order_info */
            $order_info = $this->_parseTrackingMessage($q_item->getMessage());
            //print_r($order_info);
            /** @var array $validation */
            $validation = $this->validateOrderInfo($order_info);
            //echo '<pre>';print_r($validation);exit;
            if ($validation['has_errors']) {
                $q_item->setAppendError(['error' => $validation["errors"]]);
                $q_item->save();
                continue;
            }

            echo "\nBEFORE sending to process tracking\n";
            print_r($order_info);
            echo "\n--------------\n";

            $this->_processTracking($order_info, $q_item);
        }
    }

    /**
     * @param array $orderInfo
     * @param QueueItem $qItem
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     * @throws \Exception
     */
    private function _processTracking(Array $orderInfo, QueueItem $qItem)
    {
        // array variable for setting process messages
        $processing_messages = [];

        // update the queue item record with new known data
        $qItem->setUnfiOrderId($orderInfo['order']['ubs_order']);
        $qItem->setOrderIncrementId($orderInfo['order']['customer_po']);
        $qItem->setIncrementProcessAttempt();

        $ship_rest_identifier = $orderInfo['order']['request_filter'] . "-" . $orderInfo['order']['customer_po'];

        echo "\n1- Collection Filters: " . $ship_rest_identifier . "\n";

        $searchCriteria = $this->_searchCriteriaBuilder
            ->addFilter(\UNFI\Integration\Api\Data\ShipReqInterface::OMS_REQUEST_ID, $ship_rest_identifier, "eq")
            ->create();

        $ship_req_item = null;
        try {
            /** @var \UNFI\Integration\Api\Data\ShipReqSearchResultsInterface $ship_req_items */
            $ship_req_items = $this->_shipReqRepository->getList($searchCriteria);
            // we should only ever have a single record returned
            /** @var \UNFI\Integration\Api\Data\ShipReqInterface $ship_req_item */
            $ship_req_item = $ship_req_items->getItems()[0];

            echo "\n past - no error\n";

        } catch (\Exception $exception) {

            echo "\n2- No Ship Request Item found\n";

            // not item was found
            $processing_messages[] = ['error' => sprintf("No shipping request for %s was found", $ship_rest_identifier)];
            $qItem->setAppendError($processing_messages);
            $updated_date = new \DateTime();
            $qItem->setUpdatedAt($updated_date->format($updated_date::ATOM));
            $qItem->setReadyToProcess(0);
            try {
                $this->_queueItemResourceModel->save($qItem);
            } catch (\Exception | AlreadyExistsException $e) {
                $this->_logger->critical(
                    sprintf("[TRACKING] 1 Unable to save QueueItem model. ID %s: %s",
                        $qItem->getOrderId(),
                        $e->getMessage()
                    )
                );
            }
            return;
        }

        // add magento order id
        $qItem->setOrderId($ship_req_item->getMagentoOrderId());

        $order = $this->_orderRepository->get($ship_req_item->getMagentoOrderId());
        //$order = null;
        if (!$order || !$order->getEntityId()) {
            $qItem->setReadyToProcess(0);
            $processing_messages[] = ['error' => sprintf('Unable to locate order with increment id %s', $ship_req_item->getMagentoOrderIncrementId())];
            $qItem->setAppendError($processing_messages);
            $updated_date = new \DateTime();
            $qItem->setUpdatedAt($updated_date->format($updated_date::ATOM));
            try {
                $this->_queueItemResourceModel->save($qItem);
            } catch (\Exception | AlreadyExistsException $e) {
                $this->_logger->critical(
                    sprintf("[TRACKING] 2 Unable to save QueueItem model. ID %s: %s",
                        $qItem->getOrderId(),
                        $e->getMessage()
                    )
                );
            }
            return;
        }

        $shipments = $order->getShipmentsCollection();
        $found_shipment = null;
        echo "\ncount of shipments - " . count($shipments);
        if (count($shipments)) {
            echo "\ncount good - HAS SHIP ITEMS\n";
            foreach ($shipments as $shipment) {
                echo "\Found shipment - looking for " . $shipment->getWarehouse() . ' - ' . $orderInfo['order']['magento_warehouse_id'] . "\n";
                if ($shipment->getWarehouse() == $orderInfo['order']['magento_warehouse_id']) {
                    $found_shipment = $shipment;
                    break;
                }
            }
        }

        if (is_null($found_shipment)) {

            echo "\nINSIDE IS NILL\n";

            // check if we have hit the process attempts limit
            $allowed_process_attempts = $this->_dataHelper->getRetryAttempts() ? $this->_dataHelper->getRetryAttempts() : 6;
            if($qItem->getProcessAttempts() > $allowed_process_attempts) {
                // we have hit the limit - mark as error
                $qItem->setReadyToProcess(0);
                $processing_messages[] = ['error' => sprintf('Set to Reprocess. Unable to locate a shipment with warehouse %s for Order with increment id %s',
                    $orderInfo['order']['warehouse'],
                    $ship_req_item->getMagentoOrderIncrementId()
                )];
            } else {
                // we still got more tries
                $qItem->setReadyToProcess(1);
                $qItem->setHasError(1);
                $qItem->setIncrementProcessAttempt();
                $processing_messages[] = ['info' => sprintf('Set to Reprocess. Unable to locate a shipment with warehouse %s for Order with increment id %s',
                    $orderInfo['order']['warehouse'],
                    $ship_req_item->getMagentoOrderIncrementId()
                )];
            }

            /*$updated_date = new \DateTime();
            $qItem->setUpdatedAt($updated_date->format($updated_date::ATOM));*/

            // save queueitem
            try {
                $this->_queueItemResourceModel->save($qItem);
                $this->_logger->critical(
                    sprintf("[TRACKING] Error saving queue item. ID: %s", $qItem->getOrderIncrementId())
                );
            } catch (\Exception $exception) {
                // @TODO need to log and send email
                $this->_logger->critical(
                    sprintf("[TRACKING] Error saving QueueItem %s", $qItem->getId())
                );
            }

            return;
        }

        /** @var \Magento\Sales\Model\Order\Shipment $found_shipment */
        $tracks = $found_shipment->getTracksCollection();
        $this->addTrackDetails($tracks, $this->getTrackingNumbersByFiles($orderInfo, $qItem), $found_shipment, $qItem);
        $this->_shipmentNotifier->notify($order, $found_shipment);

        try {
            // update shipReq info
            $ship_req_item->setStatus($ship_req_item::STATUS_COMPLETE);
            $this->_shipmentRepository->save($found_shipment);
            $this->_shipReqRepository->save($ship_req_item);

            // update queue item
            $updated_date = new \DateTime();
            $qItem->setProcessedSuccessfully($updated_date->format($updated_date::ATOM));
            $qItem->setUpdatedAt($updated_date->format($updated_date::ATOM));
            $qItem->setReadyToProcess(0);
        } catch (\Exception $ex) {
            $processing_messages[] = ['error' => 'Unable to save updated shipment notification record or order shipment. Message: ' . $ex->getMessage()];
            $qItem->setAppendError($processing_messages);
            $qItem->setReadyToProcess(0);
            try {
                $this->_queueItemResourceModel->save($qItem);
            } catch (\Exception | AlreadyExistsException $e) {
                $this->_logger->critical(
                    sprintf("[TRACKING] 4 Unable to save QueueItem model. ID %s: %s",
                        $qItem->getOrderId(),
                        $e->getMessage()
                    )
                );
            }
            return;
        }

        // save queueitem
        try {
            $processing_messages[] = ['info' => 'Tracking processed successfully'];
            $qItem->setAppendError($processing_messages);
            $this->_queueItemResourceModel->save($qItem);
            $this->_logger->critical(
                sprintf("[TRACKING] Order %s processed correctly.", $qItem->getOrderIncrementId())
            );
        } catch (\Exception $exception) {
            // @TODO need to log and send email
            $this->_logger->critical(   
                sprintf("[TRACKING] Error saving QueueItem %s", $qItem->getId())
            );
        }
    }

    /**
     * break down tracking message to an array
     *
     * @param $message
     * @return array
     */
    private function _parseTrackingMessage($message)
    {
        $info = [];

        /**
         * line 0 - order info / ship to
         * line 1 - Tracking lines
         */
        $lines = explode(PHP_EOL, $message);

        /**
         * Order line
         *
         * 0 - UBS order #              - "45933092"
         * 1 - Ship to name             - ""Sheila Piala""
         * 2 - Ship to address 1        - "615 W Ojai Ave"
         * 3 - Ship to address 2        - ""
         * 4 - Ship to city             - "Ojai"
         * 5 - ship to state            - "NY"
         * 6 - Ship to zip              - "12549-0000"
         * 7 - Ship to contact          - "0123456789"
         * 8 - Customer PO              - "U000002446-02"
         * 9 - A
         * 10- UBS order #              - "47581520"
         * 11 - Sales order date        - "09/10/2019"
         * 12 - 32591                   - "47795"
         */
        $order_info = explode(',', $lines[0]);

        // @TODO add validation for each item ??? if needed
        $info['order']['ubs_order'] = str_replace('"', '', $order_info[0]);
        $info['order']['ship_name'] = str_replace('"', '', $order_info[1]);
        $info['order']['ship_address_1'] = str_replace('"', '', $order_info[2]);
        $info['order']['ship_address_2'] = str_replace('"', '', $order_info[3]);
        $info['order']['city'] = str_replace('"', '', $order_info[4]);
        $info['order']['state'] = str_replace('"', '', $order_info[5]);
        $info['order']['zip'] = str_replace('"', '', $order_info[6]);
        $info['order']['ship_to_contact'] = str_replace('"', '', $order_info[7]);
        $info['order']['customer_po'] = str_replace('"', '', $order_info[8]);
        $info['order']['ship_code'] = str_replace('"', '', $order_info[9]);
        //$info['order']['ubs_order'] = str_replace('"', '', $order_info[10]);
        $info['order']['order_date'] = str_replace('"', '', $order_info[11]);
        $info['order']['warehouse'] = str_replace('"', '', $order_info[12]);

        /**
         * Tracking Line
         *
         * 0 - Number of Boxes          - 2
         * [number of boxes = tracking no], so if there is 2 box there should be 2 tracking no
         * after every one tracking no there should be one blank line
         * 1 - tracking no
         * 2 - blank
         * 3 - tracking no
         */

        //tracking numbers beginning with a “1” will have shipped via UPS.
        //tracking numbers beginning with a “9” will have shipped via USPS.
        $info['tracking'] = $lines[1];

        return $info;
    }


    /**
     * @param $tracks
     * @param $tracking_numbers_to_add
     * @param $found_shipment
     */
    private function addTrackDetails($tracks, $tracking_numbers_to_add, $found_shipment, $qItem)
    {
        $existing_tracks = [];
        /** @var \Magento\Sales\Model\Order\Shipment\Track $track */
        foreach ($tracks as $track) {
            //Remove any bad tracking numbers.
            if ($track->getTitle() == 'NO INFO AVAILABLE') {
                $tracks->removeItemByKey($track->getEntityId());
                try {
                    $this->_trackRepository->delete($track);
                } catch (\Exception $ex) {
                    //TODO: Error handling
                    //Note, in this case we really don't want to quit out, just keep going.
                    $qItem->setAppendError(['error' => 'Error deleting tracking file']);
                }
            } else {
                $existing_tracks[$track->getTrackNumber()] = true;
            }
        }

        foreach ($tracking_numbers_to_add as $number) {
            if (isset($existing_tracks[$number])) {
                //put as info
                $qItem->setAppendError(['info' => sprintf('Tracking no %s already added', $number)]);
                continue; //Don't recreate tracks if they are already part of this shipment.
            }

            $track = $this->_trackFactory->create();
            $first_digit = $number[0];
            $carrier_code = 'custom';
            $title = 'Custom';
            if (array_key_exists($first_digit, $this->carrierData)) {
                $carrier_code = $this->carrierData[$first_digit]['carrier_code'];
                $title = $this->carrierData[$first_digit]['title'];
            }
            $track->setCarrierCode($carrier_code);
            $track->setTitle($title);
            $track->setTrackNumber($number);
            $found_shipment->addTrack($track);
        }
    }

    /**
     * @param $data
     * @param $qItem
     * @return array
     */
    private function getTrackingNumbersByFiles($data, $qItem)
    {
        $trackData = explode(',', $data['tracking']);
        $tracking_numbers_to_add = [];
        for ($i = 1; $i < 2 * $trackData[0]; $i++) {
            if (isset($trackData[$i]) && strlen($trackData[$i])) {

                $qItem->setAppendError(['info' => sprintf('Tracking id added: %s', $trackData[$i])]);
                $tracking_numbers_to_add[] = $trackData[$i];
            }
        }
        return $tracking_numbers_to_add;
    }
}
