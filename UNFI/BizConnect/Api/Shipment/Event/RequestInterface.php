<?php

namespace UNFI\BizConnect\Api\Shipment\Event;

interface RequestInterface
{
    /**
     * @return string
     */
    public function getOrderId();

    /**
     * @param string $order_id
     * @return $this
     */
    public function setOrderId($order_id);
    /**
     * @return string
     */
    public function getRequestId();

    /**
     * @param string $request_id
     * @return $this
     */
    public function setRequestId($request_id);

    /**
     * @return string
     */
    public function getSalesChannel();

    /**
     * @param string $sales_channel
     * @return $this
     */
    public function setSalesChannel($sales_channel);

    /**
     * @return string
     */
    public function getSourceId();

    /**
     * @param string $source_id
     * @return $this
     */
    public function setSourceId($source_id);

    /**
     * @return string
     */
    public function getParentRequestId();

    /**
     * @param string $parent_request_id
     * @return $this
     */
    public function setParentRequestId($parent_request_id);

    /**
     * @return string
     */
    public function getShippingMethod();

    /**
     * @param string $shipping_method
     * @return $this
     */
    public function setShippingMethod($shipping_method);

    /**
     * @return \Magento\SalesMessageBus\Api\Sales\Data\OrderLinePriceInterface
     */
    public function getShippingPrice();

    /**
     * @param \Magento\SalesMessageBus\Api\Sales\Data\OrderLinePriceInterface $shipping_price
     * @return $this
     */
    public function setShippingPrice($shipping_price);

    /**
     * @return \Magento\SalesMessageBus\Api\Sales\Data\OrderAddressInterface
     */
    public function getShippingAddress();

    /**
     * @param \Magento\SalesMessageBus\Api\Sales\Data\OrderAddressInterface $shipping_address
     * @return $this
     */
    public function setShippingAddress($shipping_address);

    /**
     * @return \Magento\SalesMessageBus\Api\Sales\Data\OrderAddressInterface
     */
    public function getBillingAddress();

    /**
     * @param \Magento\SalesMessageBus\Api\Sales\Data\OrderAddressInterface $billing_address
     * @return $this
     */
    public function setBillingAddress($billing_address);

    /**
     * @return \Magento\CommonMessageBus\Api\Data\CustomAttributeInterface[]
     */
    public function getCustomDetails();

    /**
     * @param \Magento\CommonMessageBus\Api\Data\CustomAttributeInterface[] $custom_details
     * @return $this
     */
    public function setCustomDetails($custom_details);

    /**
     * @return string
     */
    public function getCreated();

    /**
     * @param string $created
     * @return $this
     */
    public function setCreated($created);

    /**
     * @return string
     */
    public function getCustomerId();

    /**
     * @param string $customer_id
     * @return $this
     */
    public function setCustomerId($customer_id);

    /**
     * @return string
     */
    public function getCustomerReference();

    /**
     * @param string $customer_reference
     * @return $this
     */
    public function setCustomerReference($customer_reference);

    /**
     * @return string
     */
    public function getLanguage();

    /**
     * @param string $language
     * @return $this
     */
    public function setLanguage($language);

    /**
     * @return string
     */
    public function getVatCountry();

    /**
     * @param string $vat_country
     * @return $this
     */
    public function setVatCountry($vat_country);

    /**
     * @return string
     */
    public function getRequestCreatedAt();

    /**
     * @param string $request_created_at
     * @return $this
     */
    public function setRequestCreatedAt($request_created_at);

    /**
     * @return \Magento\SalesMessageBus\Api\Logistics\Data\ItemInterface[]
     */
    public function getItems();

    /**
     * @param \Magento\SalesMessageBus\Api\Logistics\Data\ItemInterface[] $items
     * @return $this
     */
    public function setItems($items);

    /**
     * @return \Magento\SalesMessageBus\Api\Logistics\Data\AggregatedItemInterface[]
     */
    public function getAggregatedItems();

    /**
     * @param \Magento\SalesMessageBus\Api\Logistics\Data\AggregatedItemInterface[] $aggregated_items
     * @return $this
     */
    public function setAggregatedItems($aggregated_items);

}