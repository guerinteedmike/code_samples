<?php

namespace UNFI\BizConnect\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Framework\Mail\Template\TransportBuilder;

class Notify extends \Magento\Framework\App\Helper\AbstractHelper
{
    protected $_inlineTranslation;
    protected $_transportBuilder;
    protected $_escaper;

    public function __construct(
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        TransportBuilder $transportBuilder,
        \Magento\Framework\Escaper $escaper,
        Context $context)
    {
        parent::__construct($context);
        $this->_inlineTranslation = $inlineTranslation;
        $this->_transportBuilder = $transportBuilder;
        $this->_escaper = $escaper;
    }

    public function send($send_to, $subject, $body, $attachment = '')
    {
        if(!is_string($body)) {
            $convertedBody = json_encode($body);
        }
        else {
            $convertedBody = $body;
        }
        try {
            $report = [
                'report_date' => date("j F Y", strtotime('-1 day')),
                'body' => $convertedBody,
                'email_subject' => $subject
            ];

            $sendToArr = explode(',', $send_to);
            $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;

            $postObject = new \Magento\Framework\DataObject();
            $postObject->setData($report);
            $sentToEmail = $this->scopeConfig->getValue('trans_email/ident_support/email', $storeScope);
            $testSentToEmail = "bethelumos@gmail.com";
            $sentToName = $this->scopeConfig->getValue('trans_email/ident_support/name', $storeScope);

            $sender = [
                'name' => $this->_escaper->escapeHtml($sentToName),
                'email' => $this->_escaper->escapeHtml($testSentToEmail),
            ];

            $this->_transportBuilder
                ->setTemplateIdentifier('UNFI_BizConnect_Notify')
                ->setTemplateVars(['data' => $postObject])
                ->setFrom($sender)
                ->addTo($sendToArr);

            try {
                $transport = $this->_transportBuilder->getTransport();
                $transport->sendMessage();
                $this->_inlineTranslation->resume();
            } catch (\Exception $e) {
                echo $e->getMessage(); die;
            }
        } catch (\Exception $e) {

        }
    }
}