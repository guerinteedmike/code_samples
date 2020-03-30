<?php

namespace UNFI\BizConnect\Model;

use UNFI\BizConnect\Api\Data\QueueItemInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Data\Collection\AbstractDb;

/**
 * Class QueueItem
 * @package UNFI\BizConnect\Model
 */
class QueueItem extends \Magento\Framework\Model\AbstractModel implements \UNFI\BizConnect\Api\Data\QueueItemInterface
{

    /**
     * @var Json
     */
    protected $json;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param \Magento\Framework\Serialize\Serializer\Json $json
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        Json $json,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->json = $json;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * Initialize user model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\UNFI\BizConnect\Model\ResourceModel\QueueItem::class);
    }

    /**
     * Get ready to process flag
     *
     * @return int
     */
    public function getReadyToProcess()
    {
        return $this->getData(self::READY_TO_PROCESS);
    }

    /**
     * Set ready to process flag
     *
     * @param int $readyToProcess
     * @return $this
     */
    public function setReadyToProcess($readyToProcess)
    {
        return $this->setData(self::READY_TO_PROCESS, $readyToProcess);
    }

    /**
     * @inheritDoc
     */
    public function getHasError()
    {
        return $this->getData(self::HAS_ERROR);
    }

    /**
     * @inheritDoc
     */
    public function setHasError($hasError)
    {
        return $this->setData(self::HAS_ERROR, $hasError);
    }

    /**
     * Get message type
     *
     * @return int|null
     */
    public function getMessageType()
    {
        return $this->getData(self::MESSAGE_TYPE);
    }

    /**
     * Set message type
     *
     * @param int $messageType
     * @return $this
     */
    public function setMessageType($messageType)
    {
        return $this->setData(self::MESSAGE_TYPE, $messageType);
    }

    /**
     * Get Magento internal order ID
     *
     * @return int|null
     */
    public function getOrderId()
    {
        return $this->getData(self::ORDER_ID);
    }

    /**
     * Set Magento internal order ID
     *
     * @param int $orderId
     * @return $this
     */
    public function setOrderId($orderId)
    {
        return $this->setData(self::ORDER_ID, $orderId);
    }

    /**
     * Get file name
     *
     * @return string|null
     */
    public function getFileName()
    {
        return $this->getData(self::FILE_NAME);
    }

    /**
     * Set file name
     *
     * @param string $fileName
     * @return $this
     */
    public function setFileName($fileName)
    {
        return $this->setData(self::FILE_NAME, $fileName);
    }

    /**
     * Get human formatted ID
     *
     * @return string|null
     */
    public function getOrderIncrementId()
    {
        return $this->getData(self::ORDER_INCREMENT_ID);
    }

    /**
     * Set human readable ID
     *
     * @param string $orderIncrementId
     * @return $this
     */
    public function setOrderIncrementId($orderIncrementId)
    {
        return $this->setData(self::ORDER_INCREMENT_ID, $orderIncrementId);
    }

    /**
     * Get UNFI order ID
     *
     * @return int|null
     */
    public function getUnfiOrderId()
    {
        return $this->getData(self::UNFI_ORDER_ID);
    }

    /**
     * Set UNFI Order ID.
     *
     * @param int $unfiOrderId
     * @return $this
     */
    public function setUnfiOrderId($unfiOrderId)
    {
        return $this->setData(self::UNFI_ORDER_ID, $unfiOrderId);
    }

    /**
     * Get Message.
     *
     * @return string|null
     */
    public function getMessage()
    {
        return $this->getData(self::MESSAGE);
    }

    /**
     * Set Message.
     *
     * @param string $message
     * @return $this
     */
    public function setMessage($message)
    {
        return $this->setData(self::MESSAGE, $message);
    }

    /**
     * Get Errors.
     *
     * @return string|null
     */
    public function getErrors()
    {
        return $this->getData(self::ERRORS);
    }

    /**
     * Set Errors.
     *
     * @param string $error
     * @return $this
     */
    public function setErrors($error)
    {
        return $this->setData(self::ERRORS, $error);
    }

    /**
     * Get number of attempts to process
     *
     * @return int|null
     */
    public function getProcessAttempts()
    {
        return $this->getData(self::PROCESS_ATTEMPTS);
    }

    /**
     * Set number of attempts to process
     *
     * @param int $processAttempt
     * @return $this
     */
    public function setProcessAttempts($processAttempt)
    {
        return $this->setData(self::PROCESS_ATTEMPTS, $processAttempt);
    }

    /**
     * Get date record was process successfully
     *
     * @return string|null
     */
    public function getProcessedSuccessfully()
    {
        return $this->getData(self::PROCESSED_SUCCESSFULLY);
    }

    /**
     * Set date record was processed successfully
     *
     * @param string $processedSuccessfully
     * @return $this
     */
    public function setProcessedSuccessfully($processedSuccessfully)
    {
        return $this->setData(self::PROCESSED_SUCCESSFULLY, $processedSuccessfully);
    }

    /**
     * Get date record was manually edited
     *
     * @return string|null
     */
    public function getManuallyEdited()
    {
        return $this->getData(self::MANUALLY_EDITED);
    }

    /**
     * Set date record was manually edited
     *
     * @param string $manuallyEdited
     * @return $this
     */
    public function setManuallyEdited($manuallyEdited)
    {
        return $this->setData(self::MANUALLY_EDITED, $manuallyEdited);
    }

    /**
     * Get created at time
     *
     * @return string|null
     */
    public function getCreatedAt()
    {
        return $this->getData(self::CREATED_AT);
    }

    /**
     * Set created at time
     *
     * @param string $createdAt
     * @return $this
     */
    public function setCreatedAt($createdAt)
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    /**
     * Get updated at time
     *
     * @return string|null
     */
    public function getUpdatedAt()
    {
        return $this->getData(self::UPDATED_AT);
    }

    /**
     * Set updated at time
     *
     * @param string $updatedAt
     * @return $this
     */
    public function setUpdatedAt($updatedAt)
    {
        return $this->setData(self::UPDATED_AT, $updatedAt);
    }

    /**
     * @param array $messages
     * @return $this
     * @throws \Exception
     */
    public function setAppendError($messages)
    {
        $date_now = new \DateTime();
        $timestamp = $date_now->format($date_now::ATOM);
        $current_value = $this->getErrors();
        $new_value = [];
        $messages_to_save = [];

        $current_value_array = [];
        if($current_value) {
            try {
                $current_value_array = $this->json->unserialize($current_value);
            } catch (\Exception $e) {
                echo "\n 1 -in setAppendError exception. Error: " . $e->getMessage() . "\n";
            }
        }

        // add timestamp as key to new messages
        $new_value[$timestamp][] = $messages;

        // merge the old and new
        $current_value_array[] = $new_value;

        try {
            return $this->setErrors($this->json->serialize($current_value_array));
        } catch (\Exception $e) {
            echo "\n 1 -in setAppendError exception. Error: " . $e->getMessage() . "\n";
        }

        // we shouldn't get here
        echo "\nWe shouldn't get here\n";
        return $this->setErrors($this->json->serialize($messages));
    }

    public function setIncrementProcessAttempt()
    {
        $current_attempts = (int)$this->getProcessAttempts();
        $incremented = $current_attempts ? $current_attempts + 1 : 1;
        return $this->setProcessAttempts($incremented);
    }

    public function MessageTypeSelectOptions()
    {
        $options = [];
        $options[] = [
            'label' => self::MESSAGE_TYPE_NONE_LABEL,
            'value' => self::MESSAGE_TYPE_NONE,
        ];
        $options[] = [
            'label' => self::MESSAGE_TYPE_CONFIRMATION_LABEL,
            'value' => self::MESSAGE_TYPE_CONFIRMATION,
        ];
        $options[] = [
            'label' => self::MESSAGE_TYPE_INVOICE_LABEL,
            'value' => self::MESSAGE_TYPE_INVOICE,
        ];
        $options[] = [
            'label' => self::MESSAGE_TYPE_TRACKING_LABEL,
            'value' => self::MESSAGE_TYPE_TRACKING,
        ];
        return $options;
    }

    public function getReadableMessageType($messageTypeId)
    {
        switch ($messageTypeId) {
            case self::MESSAGE_TYPE_CONFIRMATION:
                return self::MESSAGE_TYPE_CONFIRMATION_LABEL;
                break;
            case self::MESSAGE_TYPE_INVOICE:
                return self::MESSAGE_TYPE_INVOICE_LABEL;
                break;
            case self::MESSAGE_TYPE_TRACKING:
                return self::MESSAGE_TYPE_TRACKING_LABEL;
                break;
            default:
                return self::MESSAGE_TYPE_NONE_LABEL;
        }
    }

}