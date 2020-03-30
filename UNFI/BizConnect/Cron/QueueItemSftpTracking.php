<?php

namespace UNFI\BizConnect\Cron;

class QueueItemSftpTracking extends \UNFI\BizConnect\Cron\QueueItemSftpBase
{
    const LOG_PREFIX = '[TRACKING]';
    const INTEGRATION_TYPE = 'tracking';

    public function execute()
    {
        // check if integration is enabled
        if(!$this->_dataHelper->getIsIntegrationEnabled(self::INTEGRATION_TYPE, self::AREA)) {
            $this->_logger->info(
                sprintf("%s The %s integration is disabled.", self::LOG_PREFIX, self::INTEGRATION_TYPE)
            );
            return;
        }

        // set configuration for this sftp site
        // note that credentials will be validated upstream in processing
        $this->sftpConfig = $this->_dataHelper->getSftpConfigByIntegration(self::INTEGRATION_TYPE);
        if(!$this->sftpConfig)
        {
            $this->_logger->critical(
                sprintf("%s No SFTP credentials were found for the %s integration.", self::LOG_PREFIX, self::INTEGRATION_TYPE)
            );
            $subject = "BizConnect: Tracking Issue";
            $body = "<p>[Tracking:SFTP] SFTP credential issue.</p><p>Environment: " . $this->_urlInterface->getBaseUrl() . "</p>";
            $this->_notifyEmail($subject, $body);
            return;
        }

        // set integration type
        $this->integrationId = self::INTEGRATION_TYPE;

        // set message type id
        $this->messageTypeId = \UNFI\BizConnect\Api\Data\QueueItemInterface::MESSAGE_TYPE_TRACKING;

        // set an identifier for logs
        $this->logPrefix = self::LOG_PREFIX;

        $result = $this->processQueues();
    }
}