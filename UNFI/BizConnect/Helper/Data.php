<?php

namespace UNFI\BizConnect\Helper;

/**
 * Class Data
 * @package UNFI\BizConnect\Helper
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * Configuration prefix for SFTP integrations
     */
    const ENABLED_CONFIG_PREFIX = 'unfi_bizconnect/enable_disable';

    /**
     *
     */
    const SFTP_CONFIG_PREFIX = 'unfi_bizconnect/sftp';

    /**
     *
     */
    const SFTP_PREFIX_ORDER = 'order';

    /**
     *
     */
    const SFTP_PREFIX_INVENTORY = 'inventory';

    /**
     *
     */
    const SFTP_PREFIX_CONFIRMATION = 'confirmation';

    /**
     *
     */
    const SFTP_PREFIX_INVOICE = 'invoice';

    /**
     *
     */
    const SFTP_PREFIX_TRACKING = 'tracking';

    /**
     *
     */
    const SHIPPING_SKU = 'unfi_bizconnect/mappings/shipping_sku';

    const INTEGRATION_CONFIG_PREFIX = 'unfi_bizconnect/integration';

    const AMOUNT_TO_PROCESS = 'unfi_bizconnect/queue_process/amount_to_process';

    const WAREHOUSE_MAPPING = 'unfi_bizconnect/mappings/warehouse_mapping';

    const REQUEST_FILTER = 'unfi_integration/order/request_filter';

    const RETRY_ATTEMPTS = 'unfi_bizconnect/queue_process/retry_attempts';

    /**
     * Return an array of credentials for a specific integration
     *
     * @param string $integration
     * @return array|null
     */
    public function getSftpConfigByIntegration($integration)
    {
        if(!$integration)
        {
            return null;
        }
        $configPrefix = sprintf('%s/%s', self::SFTP_CONFIG_PREFIX, $integration);
        return $this->scopeConfig->getValue($configPrefix);
    }

    /**
     * @param $area             // which integration - sftp or process
     * @param $integration
     * @return mixed|null
     */
    public function getIsIntegrationEnabled($integration, $area)
    {
        if(!$integration)
        {
            return null;
        }
        $configPath = sprintf('%s/%s/%s',self::ENABLED_CONFIG_PREFIX, $area, $integration);
        //echo "----config path: " . $configPath . "\n";
        return $this->scopeConfig->getValue($configPath);
    }

    /**
     * get general integration config
     * @param $integration
     * @return mixed|null
     */
    public function getGeneralIntegrationConfig($integration)
    {
        if(!$integration)
        {
            return null;
        }
        $configPath = sprintf('%s/%s', self::INTEGRATION_CONFIG_PREFIX, $integration);
        return $this->scopeConfig->getValue($configPath);
    }

    public function getNumberOfItemsToProcess()
    {
        return $this->scopeConfig->getValue(self::AMOUNT_TO_PROCESS);
    }

    public function getRetryAttempts()
    {
        return $this->scopeConfig->getValue(self::RETRY_ATTEMPTS);
    }

    public function getWarehouseMappingsAsArray()
    {
        $warehouse_raw = $this->scopeConfig->getValue(self::WAREHOUSE_MAPPING);

        $raw_mapping_lines = preg_split('/\r\n|\r|\n/', $warehouse_raw);
        $mapping = [];
        foreach($raw_mapping_lines as $raw_mapping_line)
        {
            $line = explode(',', $raw_mapping_line);
            if(count($line) > 1)
            {
                $mapping[$line[0]] = $line[1];
            }
        }
        return $mapping;
    }

    public function getWarehouseMapping($warehouse_id)
    {
        /** @var array $warehouses */
        $warehouses = $this->getWarehouseMappingsAsArray();

        foreach ($warehouses as $key => $value){
            if((int)$value == (int)$warehouse_id){
                return $key;
            }
        }
        // we only get here if nothing was found
        return null;
    }

    public function getRequestFilter()
    {
        return $this->scopeConfig->getValue(self::REQUEST_FILTER);
    }

    public function getShippingSku()
    {
        return $this->scopeConfig->getValue(self::SHIPPING_SKU);
    }

    public function deleteFileFromSftpByIntegration($integrationType)
    {
        return $this->scopeConfig->getValue('unfi_bizconnect/sftp/' . $integrationType . '/delete_after_processing');
    }
}