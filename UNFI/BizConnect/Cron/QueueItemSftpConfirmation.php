<?php

namespace UNFI\BizConnect\Cron;

/**
 * Class QueueItemSftpConfirmation
 * @package UNFI\BizConnect\Cron
 */
class QueueItemSftpConfirmation extends \UNFI\BizConnect\Cron\QueueItemSftpBase
{
    /**
     * A prefix to help identify messages in log files
     */
    const LOG_PREFIX = '[CONFIRMATION]';
    /**
     * The integration type identifier
     */
    const INTEGRATION_TYPE = 'confirmation';

    /**
     * Cron job to import a UNFI UBS confirmation file
     */
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
        if(!$this->sftpConfig) {
            $this->_logger->critical(
                sprintf("%s No SFTP credentials were found for the %s integration.", self::LOG_PREFIX, self::INTEGRATION_TYPE)
            );
            $subject = "BizConnect: Confirmation Issue";
            $body = "<p>[CONFIRMATION:SFTP] No invoice queue items found.</p><p>Environment: " . $this->_urlInterface->getBaseUrl() . "</p>";
            $this->_notifyEmail($subject, $body);
            return;
        }

        // set integration type
        $this->integrationId = self::INTEGRATION_TYPE;

        // set message type id
        $this->messageTypeId = \UNFI\BizConnect\Api\Data\QueueItemInterface::MESSAGE_TYPE_CONFIRMATION;

        // set an identifier for logs
        $this->logPrefix = self::LOG_PREFIX;

        // get items into the queues
        $result = $this->processQueues();

        // log errors
        if($this->errors) {
            $process_errors = implode(",\n --", $this->errors);
            $this->_logger->critical($process_errors);

            // send email
            $subject = "BizConnect: Invoice Issue";
            $body = "<p>[CONFIRMATION:SFTP] The following errors were found: " . $process_errors . "</p><p>Environment: " . $this->_urlInterface->getBaseUrl() . "</p>";
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
            $this->_logger->info(
                sprintf("%s: Files processed successfully: %s", $this->logPrefix, $files_processed)
            );
        }
    }
}