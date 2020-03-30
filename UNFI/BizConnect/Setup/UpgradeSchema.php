<?php

namespace UNFI\BizConnect\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;

        $installer->startSetup();
        if (version_compare($context->getVersion(), '1.0.1', '<')) {
            $installer->getConnection()->addColumn(
                $installer->getTable('unfi_bizconnect_queueitem'),
                'file_name',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    'nullable' => false,
                    'size' => '255',
                    'comment' => 'File Name'
                ]
            );

            $installer->getConnection()->addColumn(
                $installer->getTable('unfi_bizconnect_queueitem'),
                'ready_to_process',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                    'nullable' => false,
                    'size' => '1',
                    'default' => '1',
                    'comment' => 'Ready to Process'
                ]
            );

            $installer->getConnection()->addColumn(
                $installer->getTable('unfi_bizconnect_queueitem'),
                'has_error',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    'nullable' => false,
                    'size' => '1',
                    'default' => '0',
                    'comment' => 'Has Error'
                ]
            );

        }

        $installer->endSetup();
    }
}