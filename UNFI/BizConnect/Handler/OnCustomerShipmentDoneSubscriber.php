<?php

namespace UNFI\BizConnect\Handler;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\SalesMessageBus\Api\Logistics\Data\Event\CustomerShipmentDoneInterface;
use Magento\SalesMessageBus\Api\Logistics\FulfillmentManagement\OnCustomerShipmentDoneSubscriberInterface;
use Psr\Log\LoggerInterface;

/**
 * This is technically an extension of \Magento\SalesMessageBus\Handler\OnCustomerShipmentDoneSubscriber
 * but most of the elements are private, so might as well make it it's own thing.
 * Class OnCustomerShipmentDoneSubscriber
 * @package UNFI\BizConnect\Handler
 */
class OnCustomerShipmentDoneSubscriber implements OnCustomerShipmentDoneSubscriberInterface{


    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var \Magento\SalesMessageBus\Handler\Shipment
     */
    private $shipment;

    /**
     * @var \Magento\SalesMessageBus\Handler\Invoice
     */
    private $invoice;

    /**
     * @var LoggerInterface
     */
    private $logger;


    /**
     * @param OrderRepositoryInterface $orderRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Magento\SalesMessageBus\Handler\Shipment $shipment
     * @param \Magento\SalesMessageBus\Handler\Invoice $invoice
     * @param LoggerInterface $logger
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\SalesMessageBus\Handler\Shipment $shipment,
        \UNFI\Integration\Handler\Invoice $invoice,
        LoggerInterface $logger
    ) {
        $this->orderRepository = $orderRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->shipment = $shipment;
        $this->invoice = $invoice;
        $this->logger = $logger;
    }


    /**
     * Creates invoice and shipment after shipment creation in oms.
     *
     * @param CustomerShipmentDoneInterface $message
     */
    public function onCustomerShipmentDone(CustomerShipmentDoneInterface $message)
    {
        $this->logger->info('onCustomerShipmentDone Notification Handler Event');

        $specShipment = $message->getShipment();
        $orderIncrementId = $specShipment->getOrderId();
        $level = 'DEBUG';
        // saved in var/log/debug.log
        $this->logger->log($level,'onCustomerShipmentDone Handle debuglog', array('orderIncrementId'=>$orderIncrementId, 'specShipment' => $specShipment)); 

        $order = $this->findOrder($orderIncrementId);

        if (!$order) {
            $this->logger->error(
                __('Order %1 not found', $orderIncrementId),
                ['order' => $order]
            );
            return;
        }

        $orderId = $order->getEntityId();
        $packages = $this->shipment->gatherPackagesFromShipment($specShipment, $order);

        if (empty($packages)) {
            $this->logger->error(
                __('No package found for Order %1', $orderIncrementId),
                ['shipment' => $specShipment]
            );
            return;
        }

        foreach ($packages as $package) {
            try {
                $this->shipment->createShipmentFromPackage($package, $orderId);
            } catch(\Exception $e) {
                $this->logger->error(
                    __('No Shipment created for order %1', $orderIncrementId),
                    [
                        'order' => $order,
                        'shipmentItems' => $package['items']
                    ]
                );

                continue;
            }
        }

        try {
            $this->invoice->invoiceFullOrder($orderId);
        } catch(\Exception $e) {
            $this->logger->error(
                __('No Invoice created for order %1', $orderIncrementId),
                [
                    'order' => $order,
                    'shipmentItems' => $package['items']
                ]
            );
        }
    }

    /**
     * Get order by order id.
     *
     * @param string $orderId
     * @return OrderInterface
     */
    private function findOrder($orderId)
    {
        $searchCriteria = $this->searchCriteriaBuilder->addFilter('increment_id', $orderId, 'eq')->create();
        $orderList = $this->orderRepository->getList($searchCriteria)->getItems();

        return reset($orderList);
    }
}