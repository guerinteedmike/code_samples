<?php

namespace UNFI\BizConnect\Cron;

use Magento\Framework\Filesystem\Io\Sftp;
use UNFI\BizConnect\Helper\Data;
use UNFI\BizConnect\Model\QueueItemFactory;
use UNFI\BizConnect\Api\Data\QueueItemInterface;
use UNFI\BizConnect\Model\QueueItem;
use UNFI\BizConnect\Logger\Logger;
use UNFI\BizConnect\Helper\Notify;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\App\Config\ScopeConfigInterface;
use UNFI\BizConnect\Helper\Email;
use Magento\Framework\UrlInterface;

abstract class QueueItemSftpBase
{
    /**
     * area affected - sftp or process
     */
    const AREA =  "sftp";

    /**
     * Config path
     */
    const CONFIG_PATH = 'unfi_bizconnect/mail_notificaion/notify_email_bizconnect';

    /**
     * @var Sftp
     */
    protected $_sftp;

    /**
     * @var Data
     */
    protected $_dataHelper;

    /**
     * @var QueueItemFactory
     */
    protected $_queueItemFactory;

    /**
     * @var
     */
    protected $integrationId;

    /**
     * @var
     */
    protected $logPrefix;

    /**
     * @var
     */
    protected $messageTypeId;

    /**
     * @var Logger
     */
    protected $_logger;

    /**
     * @var array
     */
    protected $warnings = [];

    /**
     * @var array
     */
    protected $errors = [];

    /**
     * @var array
     */
    protected $filesProcessed = [];

    /**
     * @var array
     */
    protected $sftpConfig = [];
    /**
     * @var Notify
     */
    protected $_notify;

    /**
     * @var TransportBuilder
     */
    protected $_transportBuilder;

    /**
     * @var ScopeConfigInterface
     */
    protected $_scoped_config;

    /**
     * @var Email
     */
    protected $_helperEmail;

    /**
     * @var UrlInterface
     */
    protected $_urlInterface;

    /**
     * QueueItemCronBase constructor.
     * @param Sftp $sftp
     * @param Data $dataHelper
     * @param QueueItemFactory $queueItemFactory
     * @param Logger $logger
     * @param Notify $notify
     * @param TransportBuilder $transportBuilder
     * @param ScopeConfigInterface $scopeConfigInterface
     * @param Email $helperEmail
     * @param UrlInterface $urlInterface
     */
    public function __construct(
        Sftp $sftp,
        Data $dataHelper,
        QueueItemFactory $queueItemFactory,
        Logger $logger,
        Notify $notify,
        TransportBuilder $transportBuilder,
        ScopeConfigInterface $scopeConfigInterface,
        Email $helperEmail,
        UrlInterface $urlInterface
    ) {
        $this->_sftp = $sftp;
        $this->_dataHelper = $dataHelper;
        $this->_queueItemFactory = $queueItemFactory;
        $this->_logger = $logger;
        $this->_notify = $notify;
        $this->_transportBuilder = $transportBuilder;
        $this->_scoped_config = $scopeConfigInterface;
        $this->_helperEmail = $helperEmail;
        $this->_urlInterface = $urlInterface;
    }

    /**
     * Validate SFTP credentials
     */
    public function validateSftpCredentials()
    {
        $requiredFields = [
            'host' => "Host",
            'port' => 'Port Number',
            'username' => 'Username',
            'password' => 'Password',
            'path' => 'Path'
        ];

        foreach ($requiredFields as $requiredFieldKey => $requiredFieldValue) {
            if (empty($this->sftpConfig[$requiredFieldKey])) {
                $this->errors[] = $requiredFieldValue;
            }
        }
    }

    /**
     * Process Queues
     * @return int
     */
    public function processQueues()
    {
        // validation first - make sure we have all information needed
        if (empty($this->logPrefix)) {
            $this->warnings[] = "IMPLEMENTATION ISSUE: log prefix not set. Assigning generic.";
            $this->logPrefix = '[QueueItem Import]';
        }

        // integration ID is required
        if (empty($this->integrationId)) {
            $this->warnings[] = sprintf("%s Missing integration identifier.", $this->logPrefix);
            return false;
        }

        // sftp credentials/info must exist
        $this->validateSftpCredentials();

        // at this point we can go no further
        if ($this->errors) {
            $this->_notifyEmail(
                "Invalid SFTP Credentials",
                sprintf("%s: Unable to move forward with provided information.", $this->logPrefix)
            );
            $this->errors[] = sprintf("%s: Unable to move forward with provided information.", $this->logPrefix);
            return false;
        }


        // format SFTP credentials
        $args = [
            'host' => $this->sftpConfig['host'] . ':' . $this->sftpConfig['port'],
            'username' => $this->sftpConfig['username'],
            'password' => $this->sftpConfig['password']
        ];
        if ($this->sftpConfig['timeout']) {
            $args['timeout'] = $this->sftpConfig['timeout'];
        }

        try {
            $this->_sftp->open($args);

            // TECHNOTE - $this->sftp->_connection is protected and cannot be accessed directly
            $cd_result = $this->_sftp->cd($this->sftpConfig['path']);
            if (!$cd_result) {
                $this->errors[] = sprintf("%s: Target directory does not exist or permissions issues exist.", $this->logPrefix);
                return false;
            }

            // get list of filenames on server
            $files = $this->_sftp->ls();

            if (count($files) == 0) {
                $this->warnings[] = sprintf("%s: No files were found to process.", $this->logPrefix);
                return true;
            }
            if (!is_array($files)) {
                $this->errors[] = sprintf("%s: Unable to get a list of files in target directory.", $this->logPrefix);
                return false;
            }

            // If this is true, we are assuming this is a regular expression match.
            $should_regex = $this->sftpConfig['file_name'][0] == '/';

            foreach ($files as $file) {
                $fn = $file['text'];

                //echo "file: " . $fn . "\n";

                if ($fn == '.' || $fn == '..') {
                    continue;
                }
                $matches = null;
                if ($should_regex) {
                    $matches = preg_match($this->sftpConfig['file_name'], $fn);
                } else {
                    $matches = fnmatch($this->sftpConfig['file_name'], $fn);
                }
                if ($matches) {
                    $fc = $this->_sftp->read($fn);
                    if ($fc) {
                        $model = $this->getQueItemByFilename($fn);
                        if (!$model->getId()) {
                            $model->setMessage($fc);
                            $model->setMessageType($this->messageTypeId);
                            $model->setFileName($fn);
                            $model->setProcessAttempts(0);
                            $model->setReadyToProcess(1);
                            $model->save();
                            $this->filesProcessed[] = $fn;
                        } else {
                            $this->warnings[] = sprintf(
                                "%s: File processed already - %s", $this->logPrefix, $fn
                            );
                        }

                        // check config to determine if we should delete file
                        if($this->_dataHelper->deleteFileFromSftpByIntegration($this->integrationId)) {
                            $delete_successful = $this->_sftp->rm($fn);
                            if(!$delete_successful) {
                                $this->errors[] = sprintf("%s: Unable to delete file - %s", $this->logPrefix, $fn);
                            }
                        }
                    } else {
                        $this->errors[] = sprintf("%s:  Unable to download file. %s", $this->logPrefix, $fn);
                    }
                }
            }
            $this->_sftp->close();
        } catch (\Exception $ex) {
            /*$this->_notifyEmail(
                "Invalid SFTP Credentials",
                sprintf("%s\n\n%s: Unable to move forward with provided information.", $this->_urlInterface->getBaseUrl(), $this->logPrefix)
            );*/
            $this->errors[] = sprintf("%s: Unable to connect to SFTP", $this->logPrefix);
            return false;
        }

        return true;
    }

    /**
     * @param $fn
     * @return QueueItem
     */
    public function getQueItemByFilename($fn)
    {
        return $this->_queueItemFactory->create()
            ->load($fn, 'file_name');
    }

    protected function _notifyEmail($subject, $body)
    {
        $send_to = $this->_scoped_config->getValue(self::CONFIG_PATH, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        return $this->_helperEmail->sendEmail($send_to,$subject,$body);
    }

}
