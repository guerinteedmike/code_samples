<?php

namespace UNFI\BizConnect\Logger;

/**
 * Class Handler
 * @package UNFI\BizConnect\Logger
 */
class Handler extends \Magento\Framework\Logger\Handler\Base
{
    /**
     * Logging level
     * @var int
     */
    protected $loggerType = Logger::INFO;

    /**
     * File name
     * @var string
     */
    protected $fileName = '/var/log/unfi_bizconnect.log';
}