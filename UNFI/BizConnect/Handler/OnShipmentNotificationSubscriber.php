<?php

namespace UNFI\BizConnect\Handler;

use UNFI\BizConnect\Api\Data\ShipReqInterface;
use UNFI\BizConnect\Api\Shipment\Event\RequestInterface;
use UNFI\BizConnect\Api\Shipment\Handler\OnShipmentNotificationSubscriberInterface;

class OnShipmentNotificationSubscriber implements OnShipmentNotificationSubscriberInterface
{

    const TOPIC = 'magento.logistics.warehouse_management.request_shipment';

    const LOG_PREFIX = '[SHIPMENT_REQUEST] - ';

    const TYPE_NAME = 'name';
    const TYPE_ADDRESS = 'address';
    const TYPE_CITY = 'city';
    const TYPE_PHONE = 'phone';
    const TYPE_ZIP = 'zip';
    const TYPE_SKU = 'sku';
    const TYPE_UPC = 'upc';
    const CONFIG_PATH = 'unfi_integration/order/';
    const LIMIT_PART = 'limit_type_';


    protected $_scoped_config;
    protected $_sftp;
    protected $_csv;
    protected $_shipReqRepository;
    protected $_shipReqFactory;
    protected $_searchCriteriaBuilder;
    protected $_filterBuilder;
    protected $_orderRepository;
    protected $_messageEncoder;
    protected $_myLogger;
    protected $_should_log_info = null;
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scoped_config,
        \UNFI\Integration\Helper\SFTP $sftp, //Need to make compatible with BizConnect
        \UNFI\Integration\Helper\CSV $csv, //Need to make compatible with BizConnect
        \UNFI\Integration\Api\ShipReqRepositoryInterface $shipReqRepository,
        \UNFI\Integration\Model\ShipReqFactory $shipReqFactory, //Need to make compatible with BizConnect
        \UNFI\Integration\Logger\Logger $logger, //Need to make compatible with BizConnect
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Framework\Api\FilterBuilder $filterBuilder,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Framework\MessageQueue\MessageEncoder $messageEncoder
    )
    {
        $this->_scoped_config = $scoped_config;
        $this->_sftp = $sftp; //Need to make compatible with BizConnect
        $this->_csv = $csv; //Need to make compatible with BizConnect
        $this->_shipReqRepository = $shipReqRepository;
        $this->_shipReqFactory = $shipReqFactory; //Need to make compatible with BizConnect
        $this->_myLogger = $logger; //Need to make compatible with BizConnect
        $this->_searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->_filterBuilder = $filterBuilder;
        $this->_orderRepository = $orderRepository;
        $this->_messageEncoder = $messageEncoder;
    }
    /**
     * @param RequestInterface $message
     * @return null|ShipReqInterface
     */
    public function onNotified(RequestInterface $message)
    {
        $request_filter = $this->_config('request_filter');
        $ship_request = $this->_createRequestRecord($message);
        $request_id = $message->getRequestId();
        if(!is_null($request_filter) && strlen($request_filter))
        {
            if(!preg_match('/' . $request_filter . '/', $request_id))
            {
                $this->_logInfo(sprintf('Skipping Shipment Request %s As It Did Not Match Our Filter %s', $request_id, $request_filter));
                return;
            }
        }
        $warehouse = $message->getSourceId();
        $order_id = $message->getOrderId();
        $parts = explode('-', $request_id);
        $request_number = $parts[count($parts) - 1];
        $outfile = $request_id . '-' . $warehouse;
        $this->_logInfo(sprintf('New Shipment Request "%s". Will output to "%s"', $request_id, $outfile));
        $output = [];
        //Step 1: Build Header Rows
        //Row 1: Just has special code for source
        $prefix = $this->_getFilePrefix();
        if($prefix) {
            $output[] = [$prefix]; // Special Code.
        }
        //Row 2: Number of Orders in File only.
        $output[] = [1]; // Number of Orders in File
        //Row 3: Order Specific Header
        $output[] = [
            $this->_getWarehouseIdMapping($message->getSourceId()), // Customer Number - Represents the 'warehouse'. TODO: Add mapping config
            '', // Ship-To Number, not required despite what docs say.
            $order_id . '-' . $request_number, // Purchase Order Number - Magento Order Number.
            '', // Message - Not being used.
            $this->_getMessageType(), // Type. Will always be FIL.
            '', // FAX Number - Not being used.
        ];

        //Step 2: Add Ship-To Information
        //Row 1: Ship-To Information
        $shipping_address = $message->getShippingAddress();
        $output[] = [
            $this->_truncate(
                $shipping_address->getFirstName() . ' ' . $shipping_address->getLastName(),
                self::TYPE_NAME
            ), // Ship To Name
            $this->_truncate(
                $shipping_address->getAddress1(),
                self::TYPE_ADDRESS
            ), // Ship To Address 1
            $this->_truncate(
                $shipping_address->getAddress2(),
                self::TYPE_ADDRESS
            ), // Ship To Address 2
            $this->_truncate(
                $shipping_address->getCity(),
                self::TYPE_CITY
            ), // Ship To City
            $shipping_address->getState(), // Ship To State
            $this->_truncate(
                $shipping_address->getZip(),
                self::TYPE_ZIP
            ), // Ship To Zip
            $this->_truncate(
                $shipping_address->getPhone(),
                self::TYPE_PHONE
            ), // Ship To Phone
            $this->_getShipVia(), // Ship Via Code
            '' // Store Number, not used
        ];
        //Step 3: Add rows for each aggregated line
        $items = $message->getAggregatedItems();
        $items = $this->_deDuplicateItems($items);
        $raw_items = $message->getItems();
        foreach($items as $item)
        {
            $output[] = [
                $this->_truncate($item->getSku(), self::TYPE_SKU), // SKU
                '', // UPC - Not available, so not using.
                $item->getQuantity(), // QTY
                '', // Customer Product Number - Not available, so not using
                $this->_getUnitPrice($item, $raw_items), // Customer Unit Price... How do I get this?
                '', // UNFI Line Number - Not needed
            ];
        }
        //Step 4: Add EOF row
        $output[] = [$this->_getEOF()];
        $csv_result = $this->_csv->arrayToCsv($output);
        if($ship_request)
        {
            $ship_request->setOrderRequestMessage($csv_result);
        }
        $result = $this->_sftp->sendFileByConfig($csv_result, self::CONFIG_PATH, $outfile);
        if($result)
        {
            $this->_logInfo(sprintf('Successfully sent shipment for request "%s"', $request_id));
            $this->_markShipmentRequestAsSent($ship_request);
        }
        else
        {
            $this->_logError(sprintf('Error sending shipment for request "%s". Please check your SFTP settings. Error Message Is: %s', $request_id, $this->_sftp->getLastErrorMessage()));
            $this->_markShipmentRequestAsReceived($ship_request);
        }
        return null;
    }

    /**
     * @param $items \Magento\SalesMessageBus\Api\Logistics\Data\AggregatedItemInterface[]
     * @return \Magento\SalesMessageBus\Api\Logistics\Data\AggregatedItemInterface[]
     */
    protected function _deDuplicateItems($items)
    {
        /** @var \Magento\SalesMessageBus\Api\Logistics\Data\AggregatedItemInterface[] $found_items */
        $found_items = [];
        foreach($items as $item)
        {
            if(!isset($found_items[$item->getSku()]))
            {
                $found_items[$item->getSku()] = $item;
            }
            else
            {
                /** @var \Magento\SalesMessageBus\Api\Logistics\Data\AggregatedItemInterface $temp */
                $temp = $found_items[$item->getSku()];
                $temp->setQuantity($temp->getQuantity() + $item->getQuantity());
                $found_items[$item->getSku()] = $temp; //Not needed but doing it just so people don't think there is a bug.
            }
        }
        return $found_items;
    }

    /**
     * @param ShipReqInterface $ship_request
     */
    protected function _markShipmentRequestAsSent($ship_request)
    {
        if(!is_null($ship_request) && $ship_request->getStatus() == ShipReqInterface::STATUS_RECEIVED)
        {
            try {
                $ship_request->setStatus(ShipReqInterface::STATUS_SENT);
                $this->_shipReqRepository->save($ship_request);
            }
            catch(\Exception $ex)
            {
                //Do Nothing
            }
        }
    }
    /**
     * @param ShipReqInterface $ship_request
     */
    protected function _markShipmentRequestAsReceived($ship_request)
    {
        if(!is_null($ship_request) && $ship_request->getStatus() == ShipReqInterface::STATUS_RECEIVED)
        {
            try {
                $this->_shipReqRepository->save($ship_request);
            }
            catch(\Exception $ex)
            {
                //Do Nothing
            }
        }
    }
    /**
     * @param RequestInterface $message
     * @return null|ShipReqInterface
     */
    protected function _createRequestRecord($message)
    {

        $request_id = $message->getRequestId();
        $filters = [
            $this->_filterBuilder->setField('oms_request_id')->setValue($request_id)->create()
        ];
        $this->_searchCriteriaBuilder->addFilters($filters);
        $search_criteria = $this->_searchCriteriaBuilder->create();
        try
        {
            $list = $this->_shipReqRepository->getList($search_criteria);
            $items = $list->getItems();
            if(count($items))
            {
                //We have a record for this already, so skip.
                return null;
            }
        }
        catch(\Exception $ex)
        {
            //DO NOTHING
        }
        $requested_qty = 0;
        foreach($message->getAggregatedItems() as $aggregatedItem)
        {
            $requested_qty += $aggregatedItem->getQuantity();
        }
        //Example of REquest ID: CHANNEL-SPRINT1-000000002-01
        $parts = explode('-', $request_id);
        $request_number = $parts[count($parts) - 1];
        $warehouse = $message->getSourceId();
        $order_id = $this->_getOrderIdFromIncrementId($message->getOrderId());
        /** @var \UNFI\Integration\Api\Data\ShipReqInterface $shipReq */
        $shipReq = $this->_shipReqFactory->create([])->getDataModel();
        $now = new \DateTime();
        $messageJson = 'UNABLE TO SERIALIZE MESSAGE. PLEASE SEE MESSAGE IN MOM ADMIN PORTAL';
        try
        {
            $messageJson = $this->_messageEncoder->encode(self::TOPIC, $message);
        }
        catch(\Exception $ex)
        {
            //DO NOTHING
        }
        $shipReq->setMagentoOrderId($order_id)
            ->setMagentoOrderIncrementId($message->getOrderId())
            ->setOmsRequestId($request_id)
            ->setOmsRequestNumber($request_number)
            ->setWarehouse($warehouse)
            ->setStatus(ShipReqInterface::STATUS_RECEIVED)
            ->setCreatedAt($now->format($now::ATOM))
            ->setShipRequestMessage($messageJson)
            ->setRequestedQty($requested_qty);

        try
        {
            $shipReq = $this->_shipReqRepository->save($shipReq);
            return $shipReq;
        }
        catch(\Exception $ex)
        {
        }
        return null;

    }

    protected function _getOrderIdFromIncrementId($increment_id)
    {
        $searchCriteria = $this->_searchCriteriaBuilder
            ->addFilter('increment_id', $increment_id, 'eq')->create();
        $orderList = $this->_orderRepository->getList($searchCriteria)->getItems();
        if(count($orderList))
        {
            foreach($orderList as $order)
            {
                return $order->getEntityId();
            }
        }
        return null;
    }

    /**
     * @param \Magento\SalesMessageBus\Api\Logistics\Data\AggregatedItemInterface $item
     * @param \Magento\SalesMessageBus\Api\Logistics\Data\ItemInterface[] $raw_items
     * @return int
     */
    protected function _getUnitPrice($item, $raw_items)
    {
        $order_lines = $item->getOrderLines();
        if(!is_array($order_lines))
        {
            return 0.0;
        }
        $order_line = current($order_lines);
        foreach($raw_items as $raw_item)
        {
            if($order_line == $raw_item->getOrderLineNumber())
            {
                return $raw_item->getOrderLinePrice()->getGrossAmount();
            }
        }
        return 0.0;
    }

    protected function _getMessageType()
    {
        return $this->_config('message_type');
    }
    protected function _getFilePrefix()
    {
        return $this->_config('file_prefix');
    }

    protected function _getShipVia()
    {
        return $this->_config('ship_via');
    }

    protected function _getWarehouseIdMapping($source_id)
    {
        $raw_mapping = $this->_config('warehouse_id_mapping');
        $mapping = $this->_csv->parseMappingCsv($raw_mapping);
        if(isset($mapping[$source_id]))
        {
            return $mapping[$source_id];
        }
        return $source_id;
    }

    protected function _truncate($value, $type)
    {
        $length = $this->_config( self::LIMIT_PART . $type);
        if(!$length)
        {
            return $value; // If we can't find a config, don't truncate
        }
        if(is_int((int)$length))
        {
            return substr($value, 0, (int)$length);
        }
        return $value;
    }

    protected function _getEOF()
    {
        return $this->_config('eof_text');//TODO: Make a config just in case.
    }

    protected function _config($path_part)
    {
        $store_scope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        return $this->_scoped_config->getValue(self::CONFIG_PATH . $path_part, $store_scope); //TODO: Find a way to get the scope code...
    }
    protected function _logInfo($message)
    {
        if(is_null($this->_should_log_info))
        {
            $this->_should_log_info = $this->_config('log_info');
        }
        if(!$this->_should_log_info)
        {
            return;
        }
        $this->_myLogger->info(self::LOG_PREFIX . $message);
    }
    protected function _logError($message)
    {
        $this->_myLogger->error(self::LOG_PREFIX . $message);
    }
}