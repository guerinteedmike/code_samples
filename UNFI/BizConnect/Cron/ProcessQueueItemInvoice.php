<?php /** @noinspection DuplicatedCode */

// @TODO - update echos to write to logs
// @TODO - add emails
// @TODO - add/change statues for queueitem and shipreq -- save
// @TODO - handle exceptions for model saves

namespace UNFI\BizConnect\Cron;

use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\LocalizedException;
use UNFI\BizConnect\Api\Data\QueueItemInterface;

class ProcessQueueItemInvoice extends \UNFI\BizConnect\Cron\ProcessQueueItemBase
{
    const EOF_CHARACTER = "***EOF***";

    public function execute()
    {
        // check if integration is enabled
        if(!$this->_dataHelper->getIsIntegrationEnabled(strtolower($this->_queueItemInterface::MESSAGE_TYPE_INVOICE_LABEL), self::AREA)) {
            $this->_logger->critical(
                sprintf("[%s]: The %s integration is disabled.",
                    strtoupper($this->_queueItemInterface::MESSAGE_TYPE_INVOICE_LABEL),
                    $this->_queueItemInterface::MESSAGE_TYPE_INVOICE_LABEL
                )
            );
            return;
        }

        $this->_init(
            $this->_queueItemInterface::MESSAGE_TYPE_INVOICE,
            $this->_queueItemInterface::MESSAGE_TYPE_INVOICE_LABEL
        );

        /** @var \UNFI\BizConnect\Model\ResourceModel\QueueItem\Collection $queueCollectionItems */
        $queueCollectionItems = $this->getQueueItemsToProcess();
        if ($queueCollectionItems->count() == 0) {
            $this->_logger->critical(
                sprintf("[%s]: No invoice queue items found.",
                    strtoupper($this->_queueItemInterface::MESSAGE_TYPE_INVOICE_LABEL)
                )
            );
            return;
        }

        /** @var \UNFI\BizConnect\Model\QueueItem $q_item */
        foreach ($queueCollectionItems as $q_item) {

            /** @var array $order_info - Which was stored in csv previously */
            $order_info = $this->_parseInvoiceMessage($q_item->getMessage());

            // NOTE -- DO NOT COMMENT OUT
            // ** - function is critical in validating AND normalizing data AND adding necessary info
            // NOTE - UBS order number = invoice number
            // STEP 1: Check Items Data to Batches
            /** @var array $validation */
            $validation = $this->validateOrderInfo($order_info);
            if ($validation['has_errors']) {
                $q_item->setAppendError(['error' => $validation["errors"]]);
                $q_item->setIncrementProcessAttempt();
                $q_item->setReadyToProcess(0);
                $q_item->setProcessedSuccessfully(1);
                try {
                    $this->_queueItemResourceModel->save($q_item);
                } catch (\Exception | AlreadyExistsException $e) {
                    $this->_logger->critical(
                        sprintf("[INVOICE] Unable to save QueueItem model. ID %d: %s",
                            $q_item->getOrderId(),
                            $e->getMessage()
                        )
                    );
                }
                continue;
            }
            $this->_processInvoice($order_info, $q_item);
        }
    }

    private function _processInvoice(Array $invoiceInfo, \UNFI\BizConnect\Model\QueueItem $qItem)
    {
        /*echo "OrderInfo--------\n";
        print_r($invoiceInfo);
        echo "--------------\n";
        echo "--------------\n";*/

        // array to collect messagews
        $processing_messages = [];

        // update the queue item record with new known data
        // note - invoice number and UBS order number are the same
        $qItem->setUnfiOrderId($invoiceInfo['order']['ubs_order']);
        $qItem->setOrderIncrementId($invoiceInfo['order']['customer_po']);
        $qItem->setIncrementProcessAttempt();

        // [mikeg] selecting the shipreq using this string ensures it matches the environment, magento order and part number
        // [mikeg] also ensure you are dealing with a single record
        // example found in shipping request - UEO_SALESCHANNEL_PROD-U000002200-01
        $ship_req_identifier = $invoiceInfo['order']['request_filter'] . "-" . $invoiceInfo['order']['customer_po'];

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
            // no item was found
            $processing_messages[] = ['error' => sprintf("No shipping request for %s was found", $ship_req_identifier)];

            // send email
            $subject = "BizConnect: Invoice Processing";
            $body = "<p>Order ID: " . $invoiceInfo['order']['customer_po'] . "</p>";
            $body .= "<p>Issue found processing order. No shipping request found.</p>";
            $this->_notifyEmail($subject, $body);

            $qItem->setAppendError($processing_messages);
            $qItem->setReadyToProcess(0);
            $qItem->setHasError(1);
            $this->_queueItemResourceModel->save($qItem);
            return;
        }

        /*
         * what status is the shipping request in?
         * 1 - confirmation has not been resolved yet
         * 2 - confirmation is good - ok to move forward
         * 3 or greater ->
         */
        $ship_req_status = $ship_req_item->getStatus();
        if($ship_req_status == 1) {
            $processing_messages[] = ['error' => sprintf("The confirmation file is not yet complete. ID %s. Be sure to reset process flag on invoice.",
                $qItem->getOrderId()
            )];
            $qItem->setAppendError($processing_messages);
            $qItem->setHasError(1);
            $qItem->setReadyToProcess(0);
            try {
                $this->_queueItemResourceModel->save($qItem);
            } catch (\Exception | AlreadyExistsException $e) {
                $this->_logger->critical(
                    sprintf("[INVOICE] - Line 151: Unable to save Queue Item. ID %s",
                        $qItem->getOrderId()
                    )
                );
            }
            return;
        }
        if($ship_req_status >= 3) {
            $processing_messages[] = ['error' => sprintf("The invoice has already been processed. Exiting without processing. ID %s.",
                $qItem->getOrderId()
            )];
            $qItem->setAppendError($processing_messages);
            $qItem->setHasError(0);
            $qItem->setReadyToProcess(0);
            try {
                $this->_queueItemResourceModel->save($qItem);
            } catch (\Exception | AlreadyExistsException $e) {
                $this->_logger->critical(
                    sprintf("[INVOICE] - Line 171: Unable to save Queue Item. ID %s",
                        $qItem->getOrderId()
                    )
                );
            }
            return;
        }

        // add magento order id
        $qItem->setOrderId($ship_req_item->getMagentoOrderId());

        // Update ship request with info
        $ship_req_item->setInvoiceMessage($qItem->getMessage());

        // get what was originally requested
        // this is warehouse specific and so is the invoice
        $warehouse_request = $this->_json->unserialize($ship_req_item->getShipRequestMessage());

        /*echo "\nWAREHOUSE REQUEST----------------\n";
        print_r($warehouse_request);
        echo "-------------------\n";
        echo "-------------------\n";
        return;*/

        // this sku is for shipping
        $shipping_sku = $this->_dataHelper->getShippingSku();

        // make sure the invoice has all required SKUs
        // also get a list of order_line_numbers by sku - we need this to ship cancel lines later on
        $missing_skus = [];
        $order_lines_by_sku = [];
        foreach ($warehouse_request['aggregated_items'] as $requested_product) {

            $order_lines_by_sku[$requested_product['sku']] = $requested_product['order_lines'];

            $bol_found = false;
            foreach ($invoiceInfo['products'] as $invoice_prod) {
                if($requested_product['sku'] == $invoice_prod['sku']) {
                    $bol_found = true;
                    break;
                }
            }
            if(!$bol_found) {
                $missing_skus[] = $requested_product['sku'];
            }
        }
        // if we have any missing skus we can stop processing so the order can be fixed
        if($missing_skus) {
            echo "\n----- Record missing SKUs ------\n";
            print_r($missing_skus);
            $processing_messages[] = ['error' => sprintf("SKUs missing from invoice: %s", implode(', ', $missing_skus))];
            $qItem->setAppendError($processing_messages);
            $qItem->setReadyToProcess(0);
            $qItem->setHasError(1);

            // send email
            $subject = "BizConnect: Invoice Processing";
            $body = "<p>Order ID: " . $invoiceInfo['order']['customer_po'] . "</p>";
            $body .= sprintf("<p>SKUs missing from invoice: %s</p>", implode(', ', $missing_skus));
            $this->_notifyEmail($subject, $body);

            try {
                $this->_queueItemResourceModel->save($qItem);
            } catch (AlreadyExistsException | \Exception $e) {
                $this->_logger->critical(
                    sprintf("[INVOICE] - Line 229: Unable to save Queue Item. ID %s",
                        $qItem->getOrderId()
                    )
                );
            }
            return;
        }

        /**
         * we need to check the invoice against the warehouse request message first to validate SKUs and quantities
         *
         * Why?
         * Multiple UBS orders could have the same SKU so we cannot just look at one UBS order against complete Magento
         * order to determine how many to cancel
         * We need to compare the what is contained in the UBS order only
         */
        $shipped_qty = [];
        $canceled_info = [];
        $total_qty_shipped = 0;
        $total_qty_canceled = 0;
        foreach ($invoiceInfo['products'] as $invoice_product) {
            $invoice_sku = $invoice_product['sku'];

            // Magento already has predetermined shipping price so shipping cost from warehouse can be ignored
            if($shipping_sku == $invoice_sku) {
                continue;
            }

            $warehouse_product_found = null;
            foreach ($warehouse_request['aggregated_items'] as $warehouse_product) {
                if($warehouse_product['sku'] == $invoice_sku) {
                    $warehouse_product_found = $warehouse_product;
                    break;
                }
            }

            // item was found
            if($warehouse_product_found) {

                $total_qty_shipped += $invoice_product['quantity_shipped'];
                $shipped_qty[$invoice_sku] = $invoice_product['quantity_shipped'];

                /*echo "invoice shipped: " . $invoice_product['quantity_shipped'] . "\n";
                echo "warehouse qty: " . $warehouse_product_found['quantity'] . "\n";*/

                if($invoice_product['quantity_shipped'] != $warehouse_product_found['quantity']) {
                    // things don't match - we need to do some work
                    $canceled_amount = $warehouse_product_found['quantity'] - $invoice_product['quantity_shipped'];
                    $canceled_info[$invoice_sku] = $canceled_amount;
                    $total_qty_canceled = $canceled_amount;
                    $processing_messages[] = sprintf("SKU %s: requested %d, invoiced %d, cancelled %d",
                        $invoice_sku, $warehouse_product_found['quantity'], $invoice_product['quantity_shipped'], $canceled_amount);
                }

            } else {
                $processing_messages[] = sprintf("SKU %s: not present in invoice.", $invoice_sku);
                $missing_skus[] = $invoice_product;
            }
        }

        echo "============================\n";
        echo "total quantity shipping: " . $total_qty_shipped . "\n";
        echo "total quantity cancelled: " . $total_qty_canceled . "\n";
        echo "QUANTITY SHIPPED -----------\n";
        print_r($shipped_qty);
        echo "CANCELLED INFO: ---------------\n";
        print_r($canceled_info);
        echo "\n============================\n\n";
        echo "\n============================\n\n";
        echo "\n============================\n\n";

        // now we need to determine which order_lines ship and which need to be cancelled
        $order_lines_to_ship = [];
        $order_lines_to_cancel = [];
        foreach ($invoiceInfo['products'] as $invoice_item) {
            $this_sku = $invoice_item['sku'];

            // Magento already has predetermined shipping price so shipping cost from warehouse can be ignored
            if($shipping_sku == $this_sku) {
                continue;
            }

            // first check if sku has cancelled item...
            if(!empty($canceled_info[$this_sku])) {
                //echo sprintf("SKU %s HAS cancelled items", $this_sku);
                $lines_ship_slice = array_slice($order_lines_by_sku[$this_sku], 0 , $shipped_qty[$this_sku]);
                $order_lines_to_ship = array_merge($order_lines_to_ship, $lines_ship_slice);

                $order_cancel_slice = array_slice($order_lines_by_sku[$this_sku], ($canceled_info[$this_sku]) * -1);
                $order_lines_to_cancel = array_merge($order_lines_to_cancel, $order_cancel_slice);
            } else {
                //echo sprintf("SKU %s DOES NOT HAVE cancelled items", $this_sku);
                $order_lines_to_ship = array_merge($order_lines_to_ship, $order_lines_by_sku[$this_sku]);
            }
        }

        echo "\n============================\n\n";
        echo "\ARRAYS FOR MOMn============================\n\n";
        echo "\n============================\n\n";
        echo "ship lines\n";
        print_r($order_lines_to_ship);
        echo "cancel lines\n";
        print_r($order_lines_to_cancel);

        /*echo "\n--SHIPPING REQUEST MESSAGE\n";
        print_r($ship_line_message);*/

        // set item to processed correctly
        // if errors they will be appended below
        $confirmed_date = new \DateTime();
        $qItem->setReadyToProcess(0);
        $qItem->setHasError(0);
        $qItem->setProcessedSuccessfully($confirmed_date->format($confirmed_date::ATOM));

        if($order_lines_to_cancel) {
            echo "\nIN ORDER LINES CANCELLED\n";
            try {
                $this->_sendCancelledItemsToMom($ship_req_identifier, $order_lines_to_cancel);
            } catch (\Exception $e) {
                echo "\nERROR - cancel lines\n";
                $processing_messages[] = ['error' => sprintf("QueueItem %d: MOM cancel lines error - %s", $qItem->getId(), $e->getMessage())];
                $qItem->setHasError(1);
                $qItem->setProcessedSuccessfully(null);
            }
        }

        //TODO: Don't send the lines shipped message if nothing was shipped. In that case make it status = canceled
        if(!$order_lines_to_ship)
        {
            $ship_req_item->setShippedQty($total_qty_shipped);
            $ship_req_item->setStatus($ship_req_item::STATUS_SHIPPED);
            try {
                $this->_shipReqRepository->save($ship_req_item);
            } catch(\Exception $exception) {
                echo "\nError saving shipreq when all lines are cancelled\n";
                $this->_logger->critical(
                    sprintf("[INVOICE] Unable to save ShipReq model - all lines cancelled. ID %d: %s",
                        $ship_req_item->getShipreqId(),
                        $exception->getMessage()
                    )
                );
            }
        }

        // send ship lines
        if($order_lines_to_ship){
            echo "\nIN ORDER LINES SHIPPED\n";
            $ship_message_to_mom = $this->_buildShippingMessageForMom($ship_req_identifier, $order_lines_to_ship, $warehouse_request);
            echo "\nShip Message to Mom array\n";
            print_r($ship_message_to_mom);
            echo "\n----------------\n";
            try {
                $response_mom_shiplines = $this->_mcom->send(self::MOM_LINES_SHIPPED_MESSAGE_IDENTIFIER, $ship_message_to_mom);

                if (empty($response_mom_shiplines)) {
                    $ship_req_item->setShippedQty($total_qty_shipped);
                    $ship_req_item->setStatus($ship_req_item::STATUS_SHIPPED);
                } else {
                    echo "\nMOM ship lines error\n";
                    print_r($response_mom_shiplines);
                    echo "\nMessage: " . $response_mom_shiplines . "\n";
                    $processing_messages[] = ['error' => sprintf("QueueItem %d: MOM ship lines error - %s", $qItem->getId(), $response_mom_shiplines)];
                    $qItem->setHasError(1);
                    $qItem->setProcessedSuccessfully(null);
                }
            } catch (\Exception $e) {
                echo "\nMOM ship lines error\n";
                $processing_messages[] = ['error' => sprintf("QueueItem %d: MOM ship lines error - %s", $qItem->getId(), $e->getMessage())];
                $qItem->setHasError(1);
                $qItem->setProcessedSuccessfully(null);
            }
        }

        echo "\nbefore saving queue and shipreq item";
        // save shipping request item
        try {
            $this->_shipReqRepository->save($ship_req_item);
        } catch (LocalizedException $e) {
            $processing_messages[] = ['error' => sprintf("QueueItem %d: MOM ship lines error - %s", $qItem->getId(), $e->getMessage())];
            $qItem->setHasError(1);
            $qItem->setReadyToProcess(0);
            $this->_logger->critical(
                sprintf("[INVOICE] Unable to save ShipReq model. ID %d: %s",
                    $ship_req_item->getShipreqId(),
                    $e->getMessage()
                )
            );
            $qItem->setProcessedSuccessfully(null);
        }

        // save queue item
        try {
            $processing_messages[] = ['info' => 'Invoice processed successfully'];
            $qItem->setAppendError($processing_messages);
            $this->_queueItemResourceModel->save($qItem);
        } catch (\Exception | AlreadyExistsException $e) {
            $this->_logger->critical(
                sprintf("[INVOICE] Unable to save QueueItem model. ID %s: %s",
                    $qItem->getOrderId(),
                    $e->getMessage()
                )
            );
        }
        echo "\nDONE\n";
    }

    private function _parseInvoiceMessage($message) {
        $info = [];;

        /**
         * line 0 - order info / ship to
         * line 1 - amount
         * line 2 to end of file - product lines
         */
        $lines = explode(PHP_EOL, $message);

        /**
         * Order line
         *
         * 0 - Invoice #                - "45830086"
         * 1 - Ship to name             - ""Shayna San Nicolas""
         * 2 - Ship to address 1        - "Ramona Fitness Center"
         * 3 - Ship to address 2        - "558 Main Street"
         * 4 - Ship to city & state     - "Ramona    CA"
         * 5 - Ship to zip              - "92065"
         * 6 - Ship to phone            - ""
         * 7 - Customer PO              - "U000002290-02"
         * 8 - Ship via description     - ""
         * 9 - Sales order #            - "45830086"
         * 10 - Shipping Date           - "09/05/2019"
         * 11 - Warehouse index         - "32594"
         */

        $order_info = explode(',', $lines[0]);

        // note invoice number = order number
        $info['order']['invoice_number'] = $info['order']['ubs_order'] = str_replace('"', '', $order_info[0]);
        $info['order']['ship_name'] = str_replace('"', '', $order_info[1]);
        $info['order']['ship_address_1'] = str_replace('"', '', $order_info[2]);
        $info['order']['ship_address_2'] = str_replace('"', '', $order_info[3]);
        $info['order']['city_state'] = str_replace('"', '', $order_info[4]);
        $info['order']['zip'] = str_replace('"', '', $order_info[5]);
        $info['order']['telephone'] = str_replace('"', '', $order_info[6]);
        $info['order']['customer_po'] = str_replace('"', '', $order_info[7]);
        $info['order']['ship_via'] = str_replace('"', '', $order_info[8]);
        $info['order']['sales_order'] = str_replace('"', '', $order_info[9]);
        $info['order']['shipping_date'] = str_replace('"', '', $order_info[10]);
        $info['order']['warehouse'] = str_replace('"', '', $order_info[11]);

        // TECHNOTE - Second line - $line[1] - is not used - 0 value

        /**
         * Amount line
         *
         * 0 - Sub-total                - 79.73
         * 1 - Freight                  - 0.00
         * 2 - Misc Charge              - 0.00
         * 3 - Volume Discount          - 0.00
         * 4 - Tax Amount               - 0.00
         * 5 - Invoice Total            - 79.73
         */

        echo "\nmount line Array\n";
        print_r($lines);
        echo "\n----------------\n";

        echo "\nmount line Array 2nd Index\n";
        print_r($lines[2]);
        echo "\n----------------\n";

        $amount_info = explode(',', $lines[2]);
        $info['amount']['sub_total'] = $amount_info[0];
        $info['amount']['freight'] = $amount_info[1];
        $info['amount']['misc_charge'] = $amount_info[2];
        $info['amount']['volume_discount'] = $amount_info[3];
        $info['amount']['tax_amount'] = $amount_info[4];
        $info['amount']['invoice_total'] = $amount_info[5];

        /**
         * product lines
         * lines 2 to end
         * NOTES - last line tends to be empty so check
         *
         * 0 - SN part number - SKU     - "0154062"
         * 1 - upc                      - "0003967577777"
         * 2 - description              - "HAVE-A-CHIP CORN CHIPS"
         * 3 - quantity ordered         - 1
         * 4 - quantity shipped         - 1
         * 5 - unit price               - 1
         * 6 - regular wholesale        - 45.86
         * 7 - sale wholesale           - 45.86
         * 8 - extended price           - 45.86
         * 9 - line number              - [not needed - incrementer, step 2
         */
        $products = array_slice($lines, 3);

        foreach ($products as $product) {
            if(!empty($product)) {
                $product_temp = [];

                // if we get end of line character then bail - we are done
                if($product == "" || $product == self::EOF_CHARACTER) {
                    continue;
                }

                $arr_split = explode(',', $product);

                $product_temp['sku'] = str_replace('"', '', $arr_split[0]);
                $product_temp['upc'] = str_replace('"', '', $arr_split[1]);
                $product_temp['description'] = str_replace('"', '', $arr_split[2]);
                $product_temp['quantity_ordered'] = $arr_split[3];
                $product_temp['quantity_shipped'] = $arr_split[4];
                $product_temp['unit_price'] = $arr_split[5];
                $product_temp['regular_price'] = $arr_split[6];
                $product_temp['sale_wholesale'] = $arr_split[7];
                $product_temp['extended_price'] = $arr_split[8];

                $info['products'][] = $product_temp;
            }
        }

        /*echo "\n";
        echo $message;
        echo "\n";
        print_r($info['products']);*/

        return $info;
    }

    protected function _buildShippingMessageForMom($requestId, $orderLinesToShip, $shipRequest)
    {
        /*
         * need to build info for lines_shipped
         * https://omsdocs.magento.com/en/specifications/#magento.logistics.warehouse_management
         *
         * $message = [
              'request_id' => $found_ship_req->getOmsRequestId(),
              'packages' => [$package],
              'items' => $shipped_items
           ];
        */

        // items array - items from ship request that ARE shipping only
        // basically include all items in shipment_request['items'] that are shipping
        $shipping_items = [];
        $ship_lines_by_sku = [];
        foreach ($shipRequest['items'] as $item) {
            $item_line_number = $item['order_line_number'];
            if(in_array($item_line_number, $orderLinesToShip)) {
                $shipping_items[] = $item;
                $ship_lines_by_sku[$item['sku']][] = $item['order_line_number'];
            }
        }

        // ---------------------------- //
        // packages array
        //
        // aggregated lines - use shipReq aggregate items but...
        // ...update to only product SKUs that ship
        // ...update their lines
        $aggregated_ship_items = [];
        foreach ($ship_lines_by_sku as $key_sku => $value_order_line_numbers) {
            foreach ($shipRequest['aggregated_items'] as $aggregated_item) {
                if($key_sku == $aggregated_item['sku']) {
                    $aggregated_item['quantity'] = count($value_order_line_numbers);
                    $aggregated_item['order_lines'] = $value_order_line_numbers;
                    $aggregated_ship_items[] = $aggregated_item;
                    continue;
                }
            }
        }
        $package = [
            'aggregated_items' => $aggregated_ship_items,
            'details' => [
                'dimensions_unit' => 'mm',
                'height' => 0,
                'length' => 0,
                'width' => 0,
                'name' => 'Package-' . $requestId
            ],
            'id' => 'Package-' . $requestId,
            'items' => $orderLinesToShip
        ];

        // put it all together now
        return [
            'request_id' => $requestId,
            'packages' => [$package],
            'items' => $shipping_items
        ];
    }
}