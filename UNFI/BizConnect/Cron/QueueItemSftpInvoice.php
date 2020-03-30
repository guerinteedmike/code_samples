<?php

namespace UNFI\BizConnect\Cron;

class QueueItemSftpInvoice extends \UNFI\BizConnect\Cron\QueueItemSftpBase
{
    const LOG_PREFIX = '[INVOICE]';
    const INTEGRATION_TYPE = 'invoice';

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
            $subject = "BizConnect: Invoice Issue";
            $body = "<p>[INVOICE:SFTP] SFTP credentials are not correct.</p><p>Environment: " . $this->_urlInterface->getBaseUrl() . "</p>";
            $this->_notifyEmail($subject, $body);
            return;
        }

        // set integration type
        $this->integrationId = self::INTEGRATION_TYPE;

        // set message type id
        $this->messageTypeId = \UNFI\BizConnect\Api\Data\QueueItemInterface::MESSAGE_TYPE_INVOICE;

        // set an identifier for logs
        $this->logPrefix = self::LOG_PREFIX;

        $result = $this->processQueues();

        // log errors
        if($this->errors) {
            $process_errors = implode(",\n --", $this->errors);
            $this->_logger->critical($process_errors);
            $subject = "BizConnect: Invoice Issue";
            $body = "<p>[INVOICE:SFTP] The following errors were found: " . $process_errors . "</p><p>Environment: " . $this->_urlInterface->getBaseUrl() . "</p>";
            $this->_notifyEmail($subject, $body);
        }

        // log any warnings
        if($this->warnings) {
            $warnings_message = implode(",\n --", $this->warnings);
            $this->_logger->warning(
                sprintf("%s: Warnings - \n%s", $this->logPrefix, $warnings_message)
            );
        }

        // log files processed
        if ($this->filesProcessed) {
            $files_processed = implode(", ", $this->filesProcessed);
            $this->_notifyEmail(
                "[INVOICE] Files processed successfully",
                sprintf("%s: Files processed successfully: %s", $this->logPrefix, $files_processed)
            );
            $this->_logger->info(
                sprintf("%s: Files processed successfully: %s", $this->logPrefix, $files_processed)
            );
        }
    }
}