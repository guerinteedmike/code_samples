<?php

namespace UNFI\BizConnect\Api\Data;

interface QueueItemInterface
{

    /**
     * Constants defined for keys of the data array. Identical to the name of the getter in snake case
     */
    const ID = 'queueitem_id';
    const READY_TO_PROCESS = 'ready_to_process';
    const HAS_ERROR = 'has_error';
    const MESSAGE_TYPE = 'message_type';
    const FILE_NAME = 'file_name';
    const ORDER_ID = 'order_id';
    const ORDER_INCREMENT_ID = 'order_increment_id';
    const UNFI_ORDER_ID = 'unfi_order_id';
    const MESSAGE = 'message';
    const ERRORS = 'errors';
    const PROCESS_ATTEMPTS = 'process_attempts';
    const PROCESSED_SUCCESSFULLY = 'processed_successfully';
    const MANUALLY_EDITED = 'manually_edited';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    /**
     * Allowed values for message type.
     */
    const MESSAGE_TYPE_NONE = 0;
    const MESSAGE_TYPE_CONFIRMATION = 1;
    const MESSAGE_TYPE_INVOICE = 2;
    const MESSAGE_TYPE_TRACKING = 3;

    /**
     * Label Values for message type
     *
     **/
    const MESSAGE_TYPE_NONE_LABEL = "Not Set";
    const MESSAGE_TYPE_CONFIRMATION_LABEL = "Confirmation";
    const MESSAGE_TYPE_INVOICE_LABEL = "Invoice";
    const MESSAGE_TYPE_TRACKING_LABEL = "Tracking";

    /**
     * Get ID
     *
     * @return int|null
     */
    public function getId();

    /**
     * Set ID
     *
     * @param int $id
     * @return $this
     */
    public function setId($id);

    /**
     * Get ready to process flag
     *
     * @return int
     */
    public function getReadyToProcess();

    /**
     * Set ready to process flag
     *
     * @param int $readyToProcess
     * @return $this
     */
    public function setReadyToProcess($readyToProcess);

    /**
     * Get if item has error
     *
     * @return int
     */
    public function getHasError();

    /**
     * Set if item has error
     *
     * @param int $hasError
     * @return $this
     */
    public function setHasError($hasError);

    /**
     * Get message type
     *
     * @return int|null
     */
    public function getMessageType();

    /**
     * Set message type
     *
     * @param int $messageType
     * @return $this
     */
    public function setMessageType($messageType);

    /**
     * Get file name
     *
     * @return string|null
     */
    public function getFileName();

    /**
     * Set file name
     *
     * @param int $fileName
     * @return $this
     */
    public function setFileName($fileName);

    /**
     * Get Magento internal order ID
     *
     * @return int|null
     */
    public function getOrderId();

    /**
     * Set Magento internal order ID
     *
     * @param int $orderId
     * @return $this
     */
    public function setOrderId($orderId);

    /**
     * Get human formatted ID
     *
     * @return string|null
     */
    public function getOrderIncrementId();

    /**
     * Set human readable ID
     *
     * @param string $orderIncrementId
     * @return $this
     */
    public function setOrderIncrementId($orderIncrementId);

    /**
     * Get UNFI order ID
     *
     * @return int|null
     */
    public function getUnfiOrderId();

    /**
     * Set UNFI Order ID.
     *
     * @param int $unfiOrderId
     * @return $this
     */
    public function setUnfiOrderId($unfiOrderId);

    /**
     * Get Message.
     *
     * @return string|null
     */
    public function getMessage();

    /**
     * Set Message.
     *
     * @param string $message
     * @return $this
     */
    public function setMessage($message);

    /**
     * Get Errors.
     *
     * @return string|null
     */
    public function getErrors();

    /**
     * Set Errors.
     *
     * @param string $error
     * @return $this
     */
    public function setErrors($error);

    /**
     * Get number of attempts to process
     *
     * @return int|null
     */
    public function getProcessAttempts();

    /**
     * Set number of attempts to process
     *
     * @param int $processAttempt
     * @return $this
     */
    public function setProcessAttempts($processAttempt);

    /**
     * Get date record was process successfully
     *
     * @return string|null
     */
    public function getProcessedSuccessfully();

    /**
     * Set date record was processed successfully
     *
     * @param string $processedSuccessfully
     * @return $this
     */
    public function setProcessedSuccessfully($processedSuccessfully);

    /**
     * Get date record was manually edited
     *
     * @return string|null
     */
    public function getManuallyEdited();

    /**
     * Set date record was manually edited
     *
     * @param string $manuallyEdited
     * @return $this
     */
    public function setManuallyEdited($manuallyEdited);

    /**
     * Get created at time
     *
     * @return string|null
     */
    public function getCreatedAt();

    /**
     * Set created at time
     *
     * @param string $createdAt
     * @return $this
     */
    public function setCreatedAt($createdAt);

    /**
     * Get updated at time
     *
     * @return string|null
     */
    public function getUpdatedAt();

    /**
     * Set updated at time
     *
     * @param string $updatedAt
     * @return $this
     */
    public function setUpdatedAt($updatedAt);

    /**
     * Convenience method for appending a line to error field
     *
     * massages in format [arr][type][array of messages]
     * type = info, error & debug
     *
     * @param array $messages
     * @return $this
     * @throws \Exception
     */
    public function setAppendError($messages);

    /**
     * Convenience function used to increment the number of process attempts
     *
     * @return int
     */
    public function setIncrementProcessAttempt();

}
