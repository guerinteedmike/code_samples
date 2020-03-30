<?php

namespace UNFI\BizConnect\Api\Shipment\Handler;

use UNFI\BizConnect\Api\Shipment\Event\RequestInterface;

interface OnShipmentNotificationSubscriberInterface
{
    /**
     * @param RequestInterface $message
     * @return mixed
     */
    public function onNotified(RequestInterface $message);
}