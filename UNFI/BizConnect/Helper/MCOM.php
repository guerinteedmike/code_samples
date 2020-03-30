<?php

namespace UNFI\BizConnect\Helper;

use \Magento\ServiceBus\Protocol\Client;
use Magento\CommonMessageBus\Message\MessageFromJson;

class MCOM extends \Magento\Framework\Url\Helper\Data
{
    /** @var Client $_client */
    protected $_client;
    protected $_messageFromJson;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        Client $client,
        MessageFromJson $messageFromJson
    )
    {
        parent::__construct($context);
        $this->_client = $client;
        $this->_messageFromJson = $messageFromJson;
    }

    /**
     * @param $method
     * @param $message
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\ServiceBus\Exception\PublishException
     */
    public function send($method, $message)
    {
        $messageObject = $this->_messageFromJson->create(
            json_encode($message),
            $method
        );
        $response = $this->_client->publish($messageObject, 'oms');
        return $response;
    }
}