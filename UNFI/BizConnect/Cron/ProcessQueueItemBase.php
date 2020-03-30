<?php

// @TODO send email functionality

namespace UNFI\BizConnect\Cron;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\ServiceBus\Exception\PublishException;
use UNFI\BizConnect\Api\Data\QueueItemInterface;
use UNFI\BizConnect\Helper\Data;
use UNFI\BizConnect\Logger\Logger;
use UNFI\BizConnect\Model\ResourceModel\QueueItem\Collection as QueueItemCollection;
use UNFI\BizConnect\Model\ResourceModel\QueueItem\CollectionFactory as QueueItemCollectionFactory;
use UNFI\BizConnect\Model\ResourceModel\QueueItem as QueueItemResourceModel;
use UNFI\Integration\Api\ShipReqRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\Shipment\NotifierInterface;
use Magento\Sales\Model\Order\Shipment\TrackFactory;
use Magento\Sales\Api\ShipmentTrackRepositoryInterface;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Magento\SalesSequence\Model\ResourceModel\Profile;
use Magento\SalesSequence\Model\ResourceModel\Meta;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use UNFI\BizConnect\Helper\MCOM;
use UNFI\BizConnect\Helper\Notify;
use Magento\Framework\Mail\Template\TransportBuilder;
use UNFI\BizConnect\Helper\Email;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\UrlInterface;

/**
 * Class ProcessQueueItemBase
 * @package UNFI\BizConnect\Cron
 */
abstract class ProcessQueueItemBase
{
    /**
     * MCOM ship lines message identifier
     */
    const MOM_LINES_SHIPPED_MESSAGE_IDENTIFIER = 'magento.logistics.warehouse_management.lines_shipped';

    /**
     * MCOM cancel lines identifier
     */
    const MOM_LINES_DECLINED_MESSAGE_IDENTIFIER = 'magento.logistics.warehouse_management.lines_declined';

    /**
     * area affected - sftp or process
     */
    const AREA = "process";

    /**
     * Config path
     */
    const CONFIG_PATH = 'unfi_bizconnect/mail_notificaion/notify_email_bizconnect';

    /**
     * Type stored as id in database
     * @var int
     */
    protected $queueTypeId;

    /**
     * Human readable representation of TypeId
     * @var string
     */
    protected $queueType;

    /**
     * Prefix to use when writing to log
     * @var string
     */
    protected $logPrefix;

    /**
     * @var QueueItemInterface
     */
    protected $_queueItemInterface;

    /**
     * @var Data
     */
    protected $_dataHelper;

    /**
     * @var QueueItemCollectionFactory
     */
    protected $_queueItemCollectionFactory;

    /**
     * @var ShipReqRepositoryInterface
     */
    protected $_shipReqRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $_searchCriteriaBuilder;

    /**
     * @var QueueItemResourceModel
     */
    protected $_queueItemResourceModel;

    /**
     * @var OrderRepositoryInterface
     */
    protected $_orderRepository;

    /**
     * @var Json
     */
    protected $_json;

    /**
     * @var \Magento\Sales\Api\ShipmentTrackRepositoryInterface
     */
    protected $_trackRepository;

    /**
     * @var \Magento\Sales\Model\Order\Shipment\TrackFactory
     */
    protected $_trackFactory;

    /**
     * @var NotifierInterface
     */
    protected $_shipmentNotifier;

    /**
     * @var ShipmentRepositoryInterface
     */
    protected $_shipmentRepository;

    /**
     * @var Logger
     */
    protected $_logger;

    /**
     * @var Profile
     */
    protected $_profile;

    /**
     * @var Meta
     */
    protected $_meta;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var TimezoneInterface
     */
    protected $_timezone;

    /**
     * @var MCOM
     */
    protected $_mcom;

    /**
     * @var Notify
     */
    protected $_notify;

    /**
     * @var DateTime
     */
    protected $_date;

    /**
     * @var TransportBuilder
     */
    protected $_transportBuilder;

    /**
     * @var Email
     */
    protected $_helperEmail;

    /**
     * @var UrlInterface
     */
    protected $_urlInterface;

    /**
     * ProcessQueueItemBase constructor.
     * @param QueueItemInterface $queueItemInterface
     * @param Data $dataHelper
     * @param OrderRepositoryInterface $orderRepository
     * @param QueueItemCollectionFactory $queueItemCollectionFactory
     * @param QueueItemResourceModel $_queueItemResourceModel
     * @param ShipReqRepositoryInterface $shipRepositoryInterface
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param NotifierInterface $shipmentNotifier
     * @param ShipmentTrackRepositoryInterface $trackRepository
     * @param ShipmentRepositoryInterface $shipmentRepository
     * @param StoreManagerInterface $storeManager
     * @param TimezoneInterface $timezone
     * @param Logger $logger
     * @param Profile $sequence
     * @param Meta $meta
     * @param TrackFactory $trackFactory
     * @param Json $json
     * @param DateTime $date
     * @param MCOM $mcom
     * @param Notify $notify
     * @param TransportBuilder $transportBuilder
     * @param Email $helperEmail
     * @param ScopeConfigInterface $scopeConfig
     * @param UrlInterface $urlInterface
     */
    public function __construct(
        QueueItemInterface $queueItemInterface,
        Data $dataHelper,
        OrderRepositoryInterface $orderRepository,
        QueueItemCollectionFactory $queueItemCollectionFactory,
        QueueItemResourceModel $_queueItemResourceModel,
        ShipReqRepositoryInterface $shipRepositoryInterface,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        NotifierInterface $shipmentNotifier,
        ShipmentTrackRepositoryInterface $trackRepository,
        ShipmentRepositoryInterface $shipmentRepository,
        StoreManagerInterface $storeManager,
        TimezoneInterface $timezone,
        Logger $logger,
        Profile $sequence,
        Meta $meta,
        TrackFactory $trackFactory,
        Json $json,
        DateTime $date,
        MCOM $mcom,
        Notify $notify,
        TransportBuilder $transportBuilder,
        Email $helperEmail,
        ScopeConfigInterface $scopeConfig,
        UrlInterface $urlInterface
    )
    {
        $this->_queueItemInterface = $queueItemInterface;
        $this->_dataHelper = $dataHelper;
        $this->_queueItemCollectionFactory = $queueItemCollectionFactory;
        $this->_queueItemResourceModel = $_queueItemResourceModel;
        $this->_shipReqRepository = $shipRepositoryInterface;
        $this->_shipmentRepository = $shipmentRepository;
        $this->_searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->_orderRepository = $orderRepository;
        $this->_json = $json;
        $this->_trackRepository = $trackRepository;
        $this->_shipmentNotifier = $shipmentNotifier;
        $this->_trackFactory = $trackFactory;
        $this->_logger = $logger;
        $this->_profile = $sequence;
        $this->_meta = $meta;
        $this->_storeManager = $storeManager;
        $this->_timezone = $timezone;
        $this->_date = $date;
        $this->_mcom = $mcom;
        $this->_notify = $notify;
        $this->_transportBuilder = $transportBuilder;
        $this->_helperEmail = $helperEmail;
        $this->_scopeConfig = $scopeConfig;
        $this->_urlInterface = $urlInterface;
    }

    protected function _init($typeId, $type)
    {
        $this->queueTypeId = $typeId;
        $this->queueType = $type;
        $this->logPrefix = '[' . strtoupper($type) . ']: ';
    }

/*    protected function addHeaderToErrorMessage($message)
    {
        $date = " " . $this->_date->gmtDate();
        $new_string = "-- " . strtoupper($this->queueType) .": ". $date . PHP_EOL . $message;
        return $new_string;
    }*/

    protected function getQueueItemsToProcess()
    {
        $number_to_process = $this->_dataHelper->getNumberOfItemsToProcess();

        /** @var QueueItemCollection $collection */
        $collection = $this->_queueItemCollectionFactory->create();
        $collection->addFieldToFilter($this->_queueItemInterface::MESSAGE_TYPE, array('eq' => $this->queueTypeId))
            ->addFieldToFilter($this->_queueItemInterface::READY_TO_PROCESS, array('eq' => 1))
            ->setPageSize($number_to_process)
            ->setOrder($this->_queueItemInterface::CREATED_AT, \Magento\Framework\Data\Collection::SORT_ORDER_ASC);

        return $collection;
    }

    protected function validateOrderInfo(&$info)
    {
        $status = [];
        $status["has_errors"] = false;

        // -- UNFI ORDER NUMBER --
        if (empty($info['order']['customer_po'])) {
            $status['has_errors'] = true;
            $status['errors'][] = "A Magento order number was not given.";
            return $status;
        } else {
            $order_prefix = $this->getOrderPrefix();
            $info['order']['prefix'] = $order_prefix;

            // must begin with correct prefix
            $message_order_prefix = substr($info['order']['customer_po'], 0, strlen($order_prefix));
            if ($message_order_prefix !== $order_prefix) {
                $status['has_errors'] = true;
                $status['errors'][] = sprintf("The order prefix is for this environment is incorrect. Expected %s.",
                    $order_prefix
                );
                return $status;
            }

            $order_parts_arr = explode("-", $info['order']['customer_po']);
            $info['order']['order_number'] = $order_parts_arr[0];
            $info['order']['order_part'] = $order_parts_arr[1];

            // add request filter to info
            // request filter is unique ID used as order prefix when sending orders to MOM
            $info['order']['request_filter'] = $this->_dataHelper->getRequestFilter();
        }

        // -- UBS ORDER NUMBER --
        if (empty($info['order']['ubs_order'])) {
            $status['has_errors'] = true;
            $status['errors'][] = "A UBS order was not given.";
        }

        // -- WAREHOUSE --
        // warehouse - value from UNFI UBS will be number
        if (empty($info['order']['warehouse'])) {
            $status['has_errors'] = true;
            $status['errors'][] = sprintf("A warehouse value was not found. Given %d from UBS.", $info['order']['warehouse']);
        } else {
            // and value must exist in mapping list
            $magento_warehouse_id = $this->_dataHelper->getWarehouseMapping($info['order']['warehouse']);
            if ($magento_warehouse_id) {
                $info['order']['magento_warehouse_id'] = $magento_warehouse_id;
            } else {
                $status['has_errors'] = true;
                $status['errors'][] = sprintf("A warehouse mapping was not found. Given %d from UBS.", $info['order']['warehouse']);
            }
        }
        return $status;
    }
    
    protected function _notifyEmail($subject, $body)
    {
        $send_to = $this->_scopeConfig->getValue(self::CONFIG_PATH, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        return $this->_helperEmail->sendEmail($send_to,$subject,$body);
    }

    /**
     * get Order prefix
     *
     * @return mixed
     */
    protected function getOrderPrefix()
    {
        try {
            $metaId = $this->_meta->loadByEntityTypeAndStore('order', $this->_storeManager->getStore()->getId());
        } catch (\Exception $e) {
            $this->_logger->critical(
                sprintf("[%s]: Meta not found for Order prefix.",
                    strtoupper($this->queueType)
                )
            );
            return null;
        }

        try {
            /** @var \Magento\SalesSequence\Model\Profile $collection */
            $collection = $this->_profile->loadActiveProfile($metaId->getId());
        } catch (\Exception $e) {
            $this->_logger->critical(
                sprintf("[%s]: profile not found for Order prefix.",
                    strtoupper($this->queueType)
                )
            );
            return null;
        }
        return $collection->getPrefix();
    }

    /**
     * break down confirmation to an array
     *
     * @param $message
     * @return array
     */
    protected function _parseConfirmationMessage($message)
    {
        $info = [];

        /**
         * line 0 - order info / ship to
         * line 1 - amount
         * line 2 to end of file - product lines
         */
        $lines = explode(PHP_EOL, $message);

        /**
         * Order line
         *
         * 0 - UBS order #              - "45933092"
         * 1 - Ship to name             - ""Sheila Piala""
         * 2 - Ship to address 1        - "615 W Ojai Ave"
         * 3 - Ship to address 2        - ""
         * 4 - Ship to city & state     - "Ojai    CA"
         * 5 - Ship to zip              - "93023"
         * 6 - Customer PO              - "U000002446-02"
         * 7 - Ship via description     - "UPS"
         * 8 - Sales order date         - "09/10/2019"
         * 9 - Warehouse index          - "47795"
         */
        $order_info = explode(',', $lines[0]);

        // @TODO add validation for required values
        $info['order']['ubs_order'] = str_replace('"', '', $order_info[0]);
        $info['order']['ship_name'] = str_replace('"', '', $order_info[1]);
        $info['order']['ship_address_1'] = str_replace('"', '', $order_info[2]);
        $info['order']['ship_address_2'] = str_replace('"', '', $order_info[3]);
        $info['order']['city_state'] = str_replace('"', '', $order_info[4]);
        $info['order']['zip'] = str_replace('"', '', $order_info[5]);
        $info['order']['customer_po'] = str_replace('"', '', $order_info[6]);
        $info['order']['ship_via'] = str_replace('"', '', $order_info[7]);
        $info['order']['order_date'] = str_replace('"', '', $order_info[8]);
        $info['order']['warehouse'] = str_replace('"', '', $order_info[9]);

        /**
         * Amount line
         *
         * 0 - Sub-total                - 8.38
         * 1 - Freight                  - 0.00
         * 2 - Misc Charge              - 0.00
         * 3 - Volume Discount          - 0.00
         * 4 - Projected Total          - 8.38
         */
        $amount_info = explode(',', $lines[1]);
        $info['amount']['sub_total'] = $amount_info[0];
        $info['amount']['freight'] = $amount_info[1];
        $info['amount']['misc_charge'] = $amount_info[2];
        $info['amount']['volume_discount'] = $amount_info[3];
        $info['amount']['projected_total'] = $amount_info[4];

        /**
         * product lines
         * lines 2 to end
         * NOTES - last line tends to be empty so check
         *
         * 0 - SN part number - SKU     - "0154062"
         * 1 - upc                      - "0003967577777"
         * 2 - description              - "HAVE-A-CHIP CORN CHIPS"
         * 3 - quantity ordered         - 1
         * 4 - retail price             - 0.00
         * 5 - regular wholesale        - 45.86
         * 6 - sale wholesale           - 45.86
         * 7 - extended price           - 45.86
         * 8 - line number              - [not needed - incrementer, step 2
         * 9 - on hand status           - always "=" ????
         */
        $products = array_slice($lines, 2);
        foreach ($products as $product) {
            if (!empty($product)) {
                $product_temp = [];

                $arr_split = explode(',', $product);

                $product_temp['sku'] = str_replace('"', '', $arr_split[0]);
                $product_temp['upc'] = str_replace('"', '', $arr_split[1]);
                $product_temp['description'] = str_replace('"', '', $arr_split[2]);
                $product_temp['quantity_ordered'] = $arr_split[3];
                $product_temp['retail_price'] = $arr_split[4];
                $product_temp['regular_price'] = $arr_split[5];
                $product_temp['sale_wholesale'] = $arr_split[6];
                $product_temp['extended_price'] = $arr_split[7];

                $info['products'][] = $product_temp;
            }
        }
        return $info;
    }

    /**
     * @param $requestId
     * @param $orderLinesToCancel
     * @return bool
     * @throws \Exception
     */
    protected function _sendCancelledItemsToMom($requestId, $orderLinesToCancel)
    {
        $cancel_message = [
            'request_id' => $requestId,
            'items' => $orderLinesToCancel
        ];
        echo "\nCancel message\n";
        print_r($cancel_message);
        echo "\n---------------\n";
        try {
            $response = $this->_mcom->send(self::MOM_LINES_DECLINED_MESSAGE_IDENTIFIER, $cancel_message);
        } catch (PublishException $e) {
            throw new \Exception($e->getMessage());
        } catch (LocalizedException $e) {
            throw new \Exception($e->getMessage());
        }
        return true;
    }

    /**
     * @param $message
     * @return bool
     * @throws \Exception
     */
    protected function _sendShippedLinesToMom($message)
    {
        /*$message = [
            'request_id' => $found_ship_req->getOmsRequestId(),
            'packages' => [$package],
            'items' => $shipped_items
        ];*/

        try {
            $response = $this->_mcom->send(self::MOM_LINES_SHIPPED_MESSAGE_IDENTIFIER, $message);
        } catch (PublishException $e) {
            throw new \Exception($e->getMessage());
        } catch (LocalizedException $e) {
            throw new \Exception($e->getMessage());
        }
        return true;
    }

}