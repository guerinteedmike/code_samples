<?php

namespace UNFI\BizConnect\Cron;

use Magento\Framework\Exception\AlreadyExistsException;
use UNFI\BizConnect\Api\Data\QueueItemInterface;
use UNFI\BizConnect\Model\QueueItem;

class ProcessQueueItemConfirmation extends \UNFI\BizConnect\Cron\ProcessQueueItemBase
{
    public function execute()
    {
        // check if integration is enabled
        if(!$this->_dataHelper->getIsIntegrationEnabled(strtolower($this->_queueItemInterface::MESSAGE_TYPE_CONFIRMATION_LABEL), self::AREA)) {
            $this->_logger->critical(
                sprintf("[%s]: The %s integration is disabled.",
                    strtoupper($this->_queueItemInterface::MESSAGE_TYPE_CONFIRMATION_LABEL),
                    $this->_queueItemInterface::MESSAGE_TYPE_CONFIRMATION_LABEL
                )
            );
            return;
        }

        $this->_init(
            $this->_queueItemInterface::MESSAGE_TYPE_CONFIRMATION,
            $this->_queueItemInterface::MESSAGE_TYPE_CONFIRMATION_LABEL
        );

        /** @var \UNFI\BizConnect\Model\ResourceModel\QueueItem\CollectionFactory $queueCollectionItems */
        $queueCollectionItems = $this->getQueueItemsToProcess();

        if ($queueCollectionItems->count() == 0) {
            $this->_logger->critical(
                sprintf("[%s]: No confirmation queue items found.",
                    strtoupper($this->_queueItemInterface::MESSAGE_TYPE_CONFIRMATION_LABEL)
                )
            );
            return;
        }

        /** @var \UNFI\BizConnect\Model\QueueItem $q_item */
        foreach ($queueCollectionItems->getItems() as $q_item) {

            /** @var array $order_info */
            $order_info = $this->_parseConfirmationMessage($q_item->getMessage());

            /** @var array $validation */
            $validation = $this->validateOrderInfo($order_info);
            if ($validation['has_errors']) {
                $q_item->setAppendError(['error' => $validation["errors"]]);
                $q_item->setHasError(1);
                $q_item->setReadyToProcess(0);
                $q_item->setIncrementProcessAttempt();
                try {
                    $this->_queueItemResourceModel->save($q_item);
                } catch (AlreadyExistsException | \Exception $e) {
                    $this->_logger->critical(
                        sprintf("[CONFIRMATION] - Line 71: Unable to save QueueItem using repository. Queue Item: %d", $q_item->getId())
                    );
                }
                continue;
            }

            if(!$validation['has_errors']) {
                $this->_processConfirmation($order_info, $q_item);
            }
        }
    }

    private function _processConfirmation(Array $orderInfo, QueueItem $qItem)
    {
        /*echo "------------------------------------------------";
        echo "-- orderInfo--------------\n";
        print_r($orderInfo);
        echo "\n---------------\n";*/

        // array to hold messages
        $processing_messages = [];

        // update the queue item record with new known data
        $qItem->setUnfiOrderId($orderInfo['order']['ubs_order']);
        $qItem->setOrderIncrementId($orderInfo['order']['customer_po']);
        $qItem->setIncrementProcessAttempt();

        // example found in shipping request - UEO_SALESCHANNEL_PROD-U000002200-01
        $ship_req_identifier = $orderInfo['order']['request_filter'] . "-" . $orderInfo['order']['customer_po'];

        $searchCriteria = $this->_searchCriteriaBuilder
            ->addFilter(\UNFI\Integration\Api\Data\ShipReqInterface::OMS_REQUEST_ID, $ship_req_identifier, "eq")
            ->create();

        $ship_req_item = null;
        try {
            /** @var \UNFI\Integration\Api\Data\ShipReqSearchResultsInterface $ship_req_items */
            $ship_req_items = $this->_shipReqRepository->getList($searchCriteria);

            // we should only ever have a single record returned
            /** @var \UNFI\Integration\Api\Data\ShipReqInterface $ship_req_item */
            $ship_req_item = $ship_req_items->getItems()[0];
        } catch (\Exception $exception) {
            // not item was found
            $processing_messages[] = ['error' => sprintf("No shipping request for %s was found", $ship_req_identifier)];
            $qItem->setAppendError($processing_messages);
            $qItem->setReadyToProcess(0);
            $qItem->setHasError(1);
            $updated_date = new \DateTime();
            $qItem->setUpdatedAt($updated_date->format($updated_date::ATOM));
            try {
                $this->_queueItemResourceModel->save($qItem);
            } catch (AlreadyExistsException | \Exception $e) {
                $this->_logger->critical(
                    sprintf("[CONFIRMATION] - Line 124: Unable to save QueueItem using repository. Queue Item: %d", $qItem->getId())
                );
            }
            return;
        }

        // add magento order id
        $qItem->setOrderId($ship_req_item->getMagentoOrderId());

        /** @var array $warehouse_request */
        $warehouse_request = $this->_json->unserialize($ship_req_item->getShipRequestMessage());

        // validate info between confirmation and warehouse request
        if($warehouse_request['order_id'] != $orderInfo['order']['order_number']) {
            $processing_messages[] = ['error' => sprintf("Order does not match. Confirmation file value %s does name warehouse request value of %s",
                $orderInfo['order']['order_number'],
                $warehouse_request['order_id']
            )];
            $qItem->setAppendError($processing_messages);
            $qItem->setReadyToProcess(0);
            $updated_date = new \DateTime();
            $qItem->setUpdatedAt($updated_date->format($updated_date::ATOM));
            try {
                $this->_queueItemResourceModel->save($qItem);
            } catch (AlreadyExistsException | \Exception $e) {
                $this->_logger->critical(
                    sprintf("[CONFIRMATION] - Line 152: Unable to save QueueItem using repository. Queue Item: %d", $qItem->getId())
                );
            }
            return;
        }

        /*echo "-- warehouse items -----\n";
        print_r($warehouse_request['aggregated_items']);*/

        $missing_skus = [];
        $unmatched_quantity = [];
        $expected_quantity = 0;
        $confirmed_quantity = 0;
        // ensure all items requested are in the confirmation file
        /** @var array $warehouse_request_item */
        foreach($warehouse_request['aggregated_items'] as $warehouse_request_item) {
            //echo "\n--- sku: " . $warehouse_request_item['sku'] . ' - quantity: ' . $warehouse_request_item['quantity'] . "\n";

            // updated quantity the was ordered/expected
            $expected_quantity += (int)$warehouse_request_item['quantity'];

            $sku_found = false;
            /** @var array $confirmed_item */
            foreach($orderInfo['products'] as $confirmed_item) {
                //echo "\n~~ does confirmed sku: " . $warehouse_request_item['sku'] . " = " . $confirmed_item['sku'] . "\n";
                if($warehouse_request_item['sku'] == $confirmed_item['sku']) {
                    $sku_found = true;

                    // update the actual quantity we will deliver
                    $confirmed_quantity += (int)$confirmed_item['quantity_ordered'];

                    if((int)$warehouse_request_item['quantity'] != (int)$confirmed_item['quantity_ordered']) {
                        $unmatched_quantity[$confirmed_item['sku']] = [
                            'ordered' => (int)$warehouse_request_item['quantity'],
                            'confirmed' => (int)$confirmed_item['quantity_ordered']
                        ];
                    }
                    continue;
                }
            }
            // if you got here the sku was not found
            if(!$sku_found) {
                $missing_skus[] = $warehouse_request_item['sku'];
            }
        }

        // does item have missing SKUs or mismatched quantities
        $sku_qty_error = [];
        if($missing_skus) {
            $sku_qty_error[] = sprintf("SKUs missing from confirmation file: %s",
                implode(',', $missing_skus)
            );
        }
        if($unmatched_quantity) {
            foreach($unmatched_quantity as $unmatched_key => $unmatched_value) {
                $sku_qty_error[] = sprintf("SKU %s: ordered %d, confirmed %d", $unmatched_key, $unmatched_value['ordered'], $unmatched_value['confirmed']);
            }
        }
        if ($sku_qty_error) {
            // we have errors
            $qItem->setHasError(1);
            $processing_messages[] = ['error' => $sku_qty_error];
            // send email
            $subject = "BizConnect: Confirmation Processing";
            $body = "<p>Order ID: " . $orderInfo['order']['customer_po'] . "</p>";
            $body .= "<p><strong>Issue found processing confirmation.</strong>.</p>";
            $body .= implode('<p>-- ', $sku_qty_error);
            $body .= "<p><br>Environment: " . $this->_urlInterface->getBaseUrl() . "</p>";
            $this->_notifyEmail($subject, $body);
        }

        $confirmed_date = new \DateTime();
        $qItem->setReadyToProcess(0);
        $qItem->setProcessedSuccessfully($confirmed_date->format($confirmed_date::ATOM));
        $qItem->setUpdatedAt($confirmed_date->format($confirmed_date::ATOM));
        $processing_messages[] = ['info' => 'Confirmation processed successfully'];
        $qItem->setAppendError($processing_messages);

        $ship_req_item->setUbsOrderNumber($orderInfo['order']['ubs_order']);
        $ship_req_item->setConfirmedQty($confirmed_quantity);
        $ship_req_item->setConfirmationMessage($qItem->getMessage());
        // status will be advanced even though there is issues.
        // reminder - confirmation messages are informational only
        $ship_req_item->setStatus($ship_req_item::STATUS_CONFIRMED);
        $ship_req_item->setConfirmedAt($confirmed_date->format($confirmed_date::ATOM));

        /*echo "UNMATCHED SKUS\n";
        print_r($missing_skus);
        echo "UNMATCHED QUANTITY\n";
        print_r($unmatched_quantity);*/

        // save shipping request
        try {
            $this->_shipReqRepository->save($ship_req_item);
        } catch (\Exception $exception) {
            $this->_logger->critical(
                sprintf("[CONFIRMATION] - Line 229: Unable to save ShipReq using repository. ShipReq ID: %d", $ship_req_item->getShipreqId())
            );
        }

        // save queueitem
        try {
            $this->_queueItemResourceModel->save($qItem);
        } catch (\Exception $exception) {
            // @TODO send email
            $this->_logger->critical(
                sprintf("[CONFIRMATION] - Line 238: Error saving QueueItem %s", $qItem->getId())
            );
        }
    }
}