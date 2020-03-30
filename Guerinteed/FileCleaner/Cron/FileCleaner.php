<?php

namespace Guerinteed\FileCleaner\Cron;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\Driver\File;

/**
 * Class FileCleaner
 * @package Guerinteed\FileCleaner\Cron
 */
class FileCleaner
{
    /**
     * Path - from var - to the backup folder
     */
    const BACKUP_PATH = '/backups/';

    /**
     * Number of days a file will be saved
     */
    const DAYS_FILE_SAVED =  1;

    /**
     * @var DirectoryList
     */
    protected $_dir;

    /**
     * @var File
     */
    protected $_file;

    /**
     * FileCleaner constructor.
     * @param DirectoryList $dir
     * @param File $file
     */
    public function __construct(
        DirectoryList $dir,
        File $file
    ) {
        $this->_dir = $dir;
        $this->_file = $file;
    }

    /**
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function execute()
    {
        $var_folder = $this->_dir->getPath('var');
        $backup_path = $var_folder . self::BACKUP_PATH;

        // make sure directory is currently available
        if (!$this->_file->isExists($backup_path)) {
            echo PHP_EOL . self::BACKUP_PATH . PHP_EOL;
            return;
        }

        $files = [];
        try {
            $files = $this->_file->readDirectory($backup_path);
        } catch (FileSystemException $e) {
            echo PHP_EOL . $e->getMessage() . PHP_EOL;
            return;
        }

        foreach ($files as $file) {
            if ($this->_file->isDirectory($file)) {
                continue;
            }

            // get creation / last update
            $creation_date = filemtime($file);
            echo PHP_EOL . "creation date: " . $creation_date . PHP_EOL;

            if (time()-filemtime($file) > (self::DAYS_FILE_SAVED * 86400)) {
                // file older than x days
                try {
                    $this->_file->deleteFile($file);
                    echo PHP_EOL . "File deleted: " . $file . PHP_EOL;
                } catch (FileSystemException $e) {
                    echo PHP_EOL . $e->getMessage() . PHP_EOL;
                }
            }
        }
    }
}
