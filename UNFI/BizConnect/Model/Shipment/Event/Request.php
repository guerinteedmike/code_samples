<?php

namespace UNFI\BizConnect\Model\Shipment\Event;

use UNFI\BizConnect\Api\Shipment\Event\RequestInterface;

class Request implements RequestInterface
{
    protected $_request_id;
    protected $_sales_channel;
    protected $_order_id;
    protected $_source_id;
    protected $_parent_request_id;
    protected $_shipping_method;
    protected $_shipping_price;
    protected $_shipping_address;
    protected $_billing_address;
    protected $_custom_details;
    protected $_items;
    protected $_aggregated_items;
    protected $_created;
    protected $_customer_id;
    protected $_customer_reference;
    protected $_language;
    protected $_vat_country;
    protected $_request_created_at;

    /**
     * @return string
     */
    public function getOrderId()
    {
        return $this->_order_id;
    }

    /**
     * @param string $order_id
     * @return $this
     */
    public function setOrderId($order_id)
    {
        $this->_order_id = $order_id;
        return $this;
    }
    /**
     * @return string
     */
    public function getRequestId()
    {
        return $this->_request_id;
    }

    /**
     * @param string $request_id
     * @return $this
     */
    public function setRequestId($request_id)
    {
        $this->_request_id = $request_id;
        return $this;
    }

    /**
     * @return string
     */
    public function getSalesChannel()
    {
        return $this->_sales_channel;
    }

    /**
     * @param string $sales_channel
     * @return $this
     */
    public function setSalesChannel($sales_channel)
    {
        $this->_sales_channel = $sales_channel;
        return $this;
    }

    /**
     * @return string
     */
    public function getSourceId()
    {
        return $this->_source_id;
    }

    /**
     * @param string $source_id
     * @return $this
     */
    public function setSourceId($source_id)
    {
        $this->_source_id = $source_id;
        return $this;
    }

    /**
     * @return string
     */
    public function getParentRequestId()
    {
        return $this->_parent_request_id;
    }

    /**
     * @param string $parent_request_id
     * @return $this
     */
    public function setParentRequestId($parent_request_id)
    {
        $this->_parent_request_id = $parent_request_id;
        return $this;
    }

    /**
     * @return string
     */
    public function getShippingMethod()
    {
        return $this->_shipping_method;
    }

    /**
     * @param string $shipping_method
     * @return $this
     */
    public function setShippingMethod($shipping_method)
    {
        $this->_shipping_method = $shipping_method;
        return $this;
    }

    /**
     * @return \Magento\SalesMessageBus\Api\Sales\Data\OrderLinePriceInterface
     */
    public function getShippingPrice()
    {
        return $this->_shipping_price;
    }

    /**
     * @param \Magento\SalesMessageBus\Api\Sales\Data\OrderLinePriceInterface $shipping_price
     * @return $this
     */
    public function setShippingPrice($shipping_price)
    {
        $this->_shipping_price = $shipping_price;
        return $this;
    }

    /**
     * @return \Magento\SalesMessageBus\Api\Sales\Data\OrderAddressInterface
     */
    public function getShippingAddress()
    {
        return $this->_shipping_address;
    }

    /**
     * @param \Magento\SalesMessageBus\Api\Sales\Data\OrderAddressInterface $shipping_address
     * @return $this
     */
    public function setShippingAddress($shipping_address)
    {
        $this->_shipping_address = $shipping_address;
        return $this;
    }

    /**
     * @return \Magento\SalesMessageBus\Api\Sales\Data\OrderAddressInterface
     */
    public function getBillingAddress()
    {
        return $this->_billing_address;
    }

    /**
     * @param \Magento\SalesMessageBus\Api\Sales\Data\OrderAddressInterface $billing_address
     * @return $this
     */
    public function setBillingAddress($billing_address)
    {
        $this->_billing_address = $billing_address;
        return $this;
    }

    /**
     * @return \Magento\CommonMessageBus\Api\Data\CustomAttributeInterface[]
     */
    public function getCustomDetails()
    {
        return $this->_custom_details;
    }

    /**
     * @param \Magento\CommonMessageBus\Api\Data\CustomAttributeInterface[] $custom_details
     * @return $this
     */
    public function setCustomDetails($custom_details)
    {
        $this->_custom_details = $custom_details;
        return $this;
    }

    /**
     * @return string
     */
    public function getCreated()
    {
        return $this->_created;
    }

    /**
     * @param string $created
     * @return $this
     */
    public function setCreated($created)
    {
        $this->_created = $created;
        return $this;
    }

    /**
     * @return string
     */
    public function getCustomerId()
    {
        return $this->_customer_id;
    }

    /**
     * @param string $customer_id
     * @return $this
     */
    public function setCustomerId($customer_id)
    {
        $this->_customer_id = $customer_id;
        return $this;
    }

    /**
     * @return string
     */
    public function getCustomerReference()
    {
        return $this->_customer_reference;
    }

    /**
     * @param string $customer_reference
     * @return $this
     */
    public function setCustomerReference($customer_reference)
    {
        $this->_customer_reference = $customer_reference;
        return $this;
    }

    /**
     * @return string
     */
    public function getLanguage()
    {
        return $this->_language;
    }

    /**
     * @param string $language
     * @return $this
     */
    public function setLanguage($language)
    {
        $this->_language = $language;
        return $this;
    }

    /**
     * @return string
     */
    public function getVatCountry()
    {
        return $this->_vat_country;
    }

    /**
     * @param string $vat_country
     * @return $this
     */
    public function setVatCountry($vat_country)
    {
        $this->_vat_country = $vat_country;
        return $this;
    }

    /**
     * @return string
     */
    public function getRequestCreatedAt()
    {
        return $this->_request_created_at;
    }

    /**
     * @param string $request_created_at
     * @return $this
     */
    public function setRequestCreatedAt($request_created_at)
    {
        $this->_request_created_at = $request_created_at;
        return $this;
    }

    /**
     * @return \Magento\SalesMessageBus\Api\Logistics\Data\ItemInterface[]
     */
    public function getItems()
    {
        return $this->_items;
    }

    /**
     * @param \Magento\SalesMessageBus\Api\Logistics\Data\ItemInterface[] $items
     * @return $this
     */
    public function setItems($items)
    {
        $this->_items = $items;
        return $this;
    }

    /**
     * @return \Magento\SalesMessageBus\Api\Logistics\Data\AggregatedItemInterface[]
     */
    public function getAggregatedItems()
    {
        return $this->_aggregated_items;
    }

    /**
     * @param \Magento\SalesMessageBus\Api\Logistics\Data\AggregatedItemInterface[] $aggregated_items
     * @return $this
     */
    public function setAggregatedItems($aggregated_items)
    {
        $this->_aggregated_items = $aggregated_items;
        return $this;
    }
}