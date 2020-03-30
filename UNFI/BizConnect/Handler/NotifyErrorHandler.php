<?php

namespace UNFI\BizConnect\Handler;

use Magento\CommonMessageBus\Api\Common\Data\Event\NotifyInterface;
use Magento\CommonMessageBus\Api\Common\ErrorManagement\NotifyErrorHandlerInterface;
use Magento\CommonMessageBus\Exception\ErrorCreatedException;

class NotifyErrorHandler implements NotifyErrorHandlerInterface
{
    protected $_scoped_config;
    protected $_notify;
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scoped_config,
        \UNFI\BizConnect\Helper\Notify $notify
    )
    {
        $this->_scoped_config = $scoped_config;
        $this->_notify = $notify;
    }
    /**
     * {@inheritdoc}
     */
    public function handle(NotifyInterface $message)
    {

        $notify_email = $this->_scoped_config->getValue('unfi_integration/connector_notify/notify_email');
        if($notify_email)
        {
            $escaped_payload = nl2br(htmlspecialchars(print_r(json_decode($message->getPayload()), true)));
            $body = <<<XML
        <table>
            <tr>
                <th>Topic:</th>
                <td>{$message->getTopic()}</td>
            </tr>
            <tr>
                <th>Message:</th>
                <td>{$message->getMessage()}</td>
            </tr>
            <tr>
                <th>Payload:</th>
                <td></td>
            </tr>
            <td>
                <td colspan="2"><pre>{$escaped_payload}</pre></td>
            </tr>
        </table>

XML;

            $this->_notify->send($notify_email, 'General MOM Connector Error', $body);
        }

        throw new ErrorCreatedException(
            __(
                'Message on topic %1 produced following error: %2',
                $message->getTopic(),
                $message->getMessage()
            )
        );
    }
}
