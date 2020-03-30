<?php
namespace UNFI\BizConnect\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Framework\Escaper;
use Magento\Framework\Mail\Template\TransportBuilder;

class Email extends \Magento\Framework\App\Helper\AbstractHelper
{
    protected $inlineTranslation;
    protected $escaper;
    protected $transportBuilder;
    protected $logger;

    public function __construct(
        Context $context,
        StateInterface $inlineTranslation,
        Escaper $escaper,
        TransportBuilder $transportBuilder
    ) {
        parent::__construct($context);
        $this->inlineTranslation = $inlineTranslation;
        $this->escaper = $escaper;
        $this->transportBuilder = $transportBuilder;
        $this->logger = $context->getLogger();
    }

    public function sendEmail($send_to, $subject, $body)
    {

        if(!is_string($body)) {
            $convertedBody = json_encode($body);
        }
        else {
            $convertedBody = $body;
        }
        $report = [
            'report_date' => date("j F Y", strtotime('-1 day')),
            'body' => $convertedBody,
            'email_subject' => $subject
        ];
        try {
            $this->inlineTranslation->suspend();
            $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
            $sentFromEmail = $this->scopeConfig->getValue('trans_email/ident_support/email', $storeScope);
            $sentFromName = $this->scopeConfig->getValue('trans_email/ident_support/name', $storeScope);
            $sender = [
                'name' => $this->escaper->escapeHtml($sentFromName),
                'email' => $this->escaper->escapeHtml($sentFromEmail),
            ];
            $sendToArr = explode(',', $send_to);
            $postObject = new \Magento\Framework\DataObject();
            $postObject->setData($report);
            $transport = $this->transportBuilder
                ->setTemplateIdentifier('email_demo_template')
                ->setTemplateOptions(
                    [
                        'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                        'store' => \Magento\Store\Model\Store::DEFAULT_STORE_ID,
                    ]
                )
                ->setTemplateVars([
                    'templateVar'  => $postObject,
                ])
                ->setFrom($sender)
                ->addTo($sendToArr)
                ->getTransport();
            $transport->sendMessage();
            $this->inlineTranslation->resume();
        } catch (\Exception $e) {
            $this->logger->debug($e->getMessage());
        }
    }
}