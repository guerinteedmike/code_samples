<?php


namespace UNFI\BizConnect\Api\Data;

interface ShipReqInterface extends \Magento\Framework\Api\ExtensibleDataInterface
{

    const SHIPREQ_ID = 'shipreq_id';
    const MAGENTO_ORDER_ID = 'magento_order_id';
    const MAGENTO_ORDER_INCREMENT_ID = 'magento_order_increment_id';
    const UBS_ORDER_NUMBER = 'ubs_order_number';
    const OMS_REQUEST_NUMBER = 'oms_request_number';
    const WAREHOUSE = 'warehouse';
    const OMS_REQUEST_ID = 'oms_request_id';
    const STATUS = 'status';
    const CREATED_AT = 'created_at';
    const CONFIRMED_AT = 'confirmed_at';
    const REQUESTED_QTY = 'requested_qty';
    const CONFIRMED_QTY = 'confirmed_qty';
    const SHIPPED_QTY = 'shipped_qty';
    const SHIP_REQUEST_MESSAGE = 'ship_request_message';
    const INVOICE_MESSAGE = 'invoice_message';
    const TRACKING_MESSAGE = 'tracking_message';
    const CONFIRMATION_MESSAGE = 'confirmation_message';
    const ORDER_REQUEST_MESSAGE = 'order_request_message';

    const STATUS_RECEIVED = 0;
    const STATUS_SENT = 1;
    const STATUS_CONFIRMED = 2;
    const STATUS_SHIPPED = 3;
    const STATUS_COMPLETE = 4;
    const STATUS_CANCELED = 5;

    /**
     * Get shipreq_id
     * @return string|null
     */
    public function getShipreqId();

    /**
     * Set shipreq_id
     * @param string $shipreqId
     * @return \UNFI\BizConnect\Api\Data\ShipReqInterface
     */
    public function setShipreqId($shipreqId);

    /**
     * Get Magento Order ID
     * @return string|null
     */
    public function getMagentoOrderId();

    /**
     * Set Magento Order ID
     * @param string $magentoOrderId
     * @return \UNFI\BizConnect\Api\Data\ShipReqInterface
     */
    public function setMagentoOrderId($magentoOrderId);

    /**
     * Get Magento Order Increment ID
     * @return string|null
     */
    public function getMagentoOrderIncrementId();

    /**
     * Set Magento Order Increment ID
     * @param string $magentoOrderIncrementId
     * @return \UNFI\BizConnect\Api\Data\ShipReqInterface
     */
    public function setMagentoOrderIncrementId($magentoOrderIncrementId);

    /**
     * Get UbsOrderNumber
     * @return string|null
     */
    public function getUbsOrderNumber();

    /**
     * Set UbsOrderNumber
     * @param string $ubsOrderNumber
     * @return \UNFI\BizConnect\Api\Data\ShipReqInterface
     */
    public function setUbsOrderNumber($ubsOrderNumber);
    /**
     * Get OmsRequestId
     * @return string|null
     */
    public function getOmsRequestId();

    /**
     * Set OmsRequestId
     * @param string $omsRequestId
     * @return \UNFI\BizConnect\Api\Data\ShipReqInterface
     */
    public function setOmsRequestId($omsRequestId);

    /**
     * Get OmsRequestNumber
     * @return string|null
     */
    public function getOmsRequestNumber();

    /**
     * Set OmsRequestNumber
     * @param string $omsRequestNumber
     * @return \UNFI\BizConnect\Api\Data\ShipReqInterface
     */
    public function setOmsRequestNumber($omsRequestNumber);

    /**
     * Get Warehouse
     * @return string|null
     */
    public function getWarehouse();

    /**
     * Set Warehouse
     * @param string $warehouse
     * @return \UNFI\BizConnect\Api\Data\ShipReqInterface
     */
    public function setWarehouse($warehouse);

    /**
     * Get Status
     * @return string|null
     */
    public function getStatus();

    /**
     * Set Status
     * @param string $status
     * @return \UNFI\BizConnect\Api\Data\ShipReqInterface
     */
    public function setStatus($status);

    /**
     * Get CreatedAt
     * @return string|null
     */
    public function getCreatedAt();

    /**
     * Set CreatedAt
     * @param string $createdAt
     * @return \UNFI\BizConnect\Api\Data\ShipReqInterface
     */
    public function setCreatedAt($createdAt);

    /**
     * Get ConfirmedAt
     * @return string|null
     */
    public function getConfirmedAt();

    /**
     * Set ConfirmedAt
     * @param string $confirmedAt
     * @return \UNFI\BizConnect\Api\Data\ShipReqInterface
     */
    public function setConfirmedAt($confirmedAt);
    /**
     * Get Requested Qty
     * @return string|null
     */
    public function getRequestedQty();
    /**
     * Set Requested Qty
     * @param string $qty
     * @return \UNFI\BizConnect\Api\Data\ShipReqInterface
     */
    public function setRequestedQty($qty);
    /**
     * Get Confirmed Qty
     * @return string|null
     */
    public function getConfirmedQty();
    /**
     * Set Confirmed Qty
     * @param string $qty
     * @return \UNFI\BizConnect\Api\Data\ShipReqInterface
     */
    public function setConfirmedQty($qty);
    /**
     * Get Shipped Qty
     * @return string|null
     */
    public function getShippedQty();
    /**
     * Set Shipped Qty
     * @param string $qty
     * @return \UNFI\BizConnect\Api\Data\ShipReqInterface
     */
    public function setShippedQty($qty);
    /**
     * Get Ship Request Message
     * @return string|null
     */
    public function getShipRequestMessage();
    /**
     * Set Ship Request Message
     * @param string $message
     * @return \UNFI\BizConnect\Api\Data\ShipReqInterface
     */
    public function setShipRequestMessage($message);
/**
     * Get Confirmation Message
     * @return string|null
     */
    public function getConfirmationMessage();
    /**
     * Set Confirmation Message
     * @param string $message
     * @return \UNFI\BizConnect\Api\Data\ShipReqInterface
     */
    public function setConfirmationMessage($message);
/**
     * Get Invoice Message
     * @return string|null
     */
    public function getInvoiceMessage();
    /**
     * Set Invoice Message
     * @param string $message
     * @return \UNFI\BizConnect\Api\Data\ShipReqInterface
     */
    public function setInvoiceMessage($message);
/**
     * Get Tracking Message
     * @return string|null
     */
    public function getTrackingMessage();
    /**
     * Set Tracking Message
     * @param string $message
     * @return \UNFI\BizConnect\Api\Data\ShipReqInterface
     */
    public function setTrackingMessage($message);

    /**
     * Get Tracking Message
     * @return string|null
     */
    public function getOrderRequestMessage();
    /**
     * Set Tracking Message
     * @param string $message
     * @return \UNFI\BizConnect\Api\Data\ShipReqInterface
     */
    public function setOrderRequestMessage($message);

    /**
     * Retrieve existing extension attributes object or create a new one.
     * @return \UNFI\BizConnect\Api\Data\ShipReqExtensionInterface|null
     */
    public function getExtensionAttributes();

    /**
     * Set an extension attributes object.
     * @param \UNFI\BizConnect\Api\Data\ShipReqExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(
        /** @noinspection PhpUnnecessaryFullyQualifiedNameInspection */
        \UNFI\BizConnect\Api\Data\ShipReqExtensionInterface $extensionAttributes
    );
}
