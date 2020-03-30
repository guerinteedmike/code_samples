<?php

namespace UNFI\BizConnect\Handler;


use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\OrderItemRepositoryInterface;
use Magento\SalesMessageBus\Api\Sales\Data\Event\LineCancelledInterface;
use Magento\SalesMessageBus\Api\Sales\OrderManagement\OnOrderLineCancelledSubscriberInterface;

class OnOrderLineCancelledSubscriber implements OnOrderLineCancelledSubscriberInterface{

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var OrderItemRepositoryInterface
     */
    private $orderItemRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var  \UNFI\BizConnect\Handler\Invoice $invoice,
     */
    private $invoice;

    /**
     * @param OrderRepositoryInterface     $orderRepository
     * @param OrderItemRepositoryInterface $orderItemRepository
     * @param SearchCriteriaBuilder        $searchCriteriaBuilder
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        OrderItemRepositoryInterface $orderItemRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        \UNFI\BizConnect\Handler\Invoice $invoice
    ) {
        $this->orderRepository = $orderRepository;
        $this->orderItemRepository = $orderItemRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->invoice = $invoice;
    }

    /**
     * Notification about order line cancellation.
     *
     * @param LineCancelledInterface $message
     */
    public function onLineCancelled(LineCancelledInterface $message)
    {
        $orderIncrementId = $message->getOrderId();
        $orderItemId = $message->getOrderLineId();

        $order = $this->findOrder($orderIncrementId);

        if (!$order) {
            return;
        }

        $this->markOrderItemAsCancelled($order, $orderItemId);
        $this->invoice->invoiceFullOrder($order->getEntityId());
    }

    /**
     * @param string $orderId
     *
     * @return OrderInterface
     */
    private function findOrder($orderId)
    {
        $searchCriteria = $this->searchCriteriaBuilder->addFilter('increment_id', $orderId, 'eq')->create();
        $orderList = $this->orderRepository->getList($searchCriteria)->getItems();

        return reset($orderList);
    }

    /**
     * @param OrderInterface $order
     * @param int            $orderItemId
     */
    private function markOrderItemAsCancelled(OrderInterface $order, $orderItemId)
    {
        $orderItem = $this->getCancellableOrderItem($order, $orderItemId);

        if (!$orderItem) {
            return;
        }

        $qtyCancelled = $orderItem->getQtyCanceled() + 1;
        $orderItem->setQtyCanceled($qtyCancelled);

        $order->setSubtotalCanceled($order->getSubtotalCanceled() + $orderItem->getPrice());
        $order->setBaseSubtotalCanceled($order->getBaseSubtotalCanceled() + $orderItem->getBasePrice());
        $this->orderItemRepository->save($orderItem);
        $this->orderRepository->save($order);
    }

    /**
     * @param OrderInterface $order
     * @param int            $orderItemId
     *
     * @return OrderItemInterface|false
     */
    private function getCancellableOrderItem(OrderInterface $order, $orderItemId)
    {
        foreach ($order->getItems() as $orderItem) {
            if ($orderItem->getItemId() == $orderItemId
                && $this->isEligibleToBeCancelled($orderItem)
            ) {
                return $orderItem;
            }
        }

        return false;
    }

    /**
     * @param OrderItemInterface $orderItem
     *
     * @return bool
     */
    private function isEligibleToBeCancelled(OrderItemInterface $orderItem)
    {
        $quantityProcessed = $orderItem->getQtyShipped() + $orderItem->getQtyCanceled();

        return $quantityProcessed < $orderItem->getQtyOrdered();
    }
}