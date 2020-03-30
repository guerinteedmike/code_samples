<?php

namespace UNFI\BizConnect\Handler;

use Magento\Sales\Model\Order;
use Magento\Sales\Api\InvoiceOrderInterface;
use Magento\Sales\Model\OrderRepository;
use Magento\SalesMessageBus\Order\EmailNotifier;
use Magento\Sales\Api\Data\InvoiceItemCreationInterface;
use Magento\Sales\Api\Data\InvoiceItemCreationInterfaceFactory;
use Magento\Sales\Api\Data\OrderItemInterface;

class Invoice extends \Magento\SalesMessageBus\Handler\Invoice{

    /**
     * @var InvoiceOrderInterface
     */
    private $invoiceOrder;

    /**
     * @var EmailNotifier
     */
    private $emailNotifier;

    /**
     * @var InvoiceItemCreationInterfaceFactory
     */
    private $invoiceFactory;

    /**
     * @var bool
     */
    private $capturePayment = true;

    protected $orderRepository;

    public function __construct(
        InvoiceOrderInterface $invoiceOrder,
        EmailNotifier $emailNotifier,
        InvoiceItemCreationInterfaceFactory $invoiceFactory,
        OrderRepository $orderRepository
    ) {
        $this->invoiceOrder = $invoiceOrder;
        $this->emailNotifier = $emailNotifier;
        $this->invoiceFactory = $invoiceFactory;
        $this->orderRepository = $orderRepository;

        parent::__construct($invoiceOrder, $emailNotifier, $invoiceFactory);
    }
    /**
     * @param $order Order
     * @return int|null
     */
    public function invoiceFullOrder($orderId){

        $order = $this->orderRepository->get($orderId);
        //if we are done shipping, but can still invoice, invoice everything.
        if(!$order->canShip() && $order->canInvoice()){
            $items = [];
            foreach($order->getAllItems() as $item){
                $items[$item->getItemId()] = $item->getQtyToInvoice();
            }

            return $this->createInvoiceFromItems($items, $order->getEntityId());
        }

        return null;
    }
}