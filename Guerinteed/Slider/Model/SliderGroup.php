<?php
declare(strict_types=1);

namespace Guerinteed\Slider\Model;

use Guerinteed\Slider\Api\Data\SliderGroupInterface;
use Magento\Framework\Model\AbstractModel;

class SliderGroup extends AbstractModel implements SliderGroupInterface
{
    /**
     * Construct
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Guerinteed\Slider\Model\ResourceModel\SliderGroup::class);
    }

    /**
     * Get ID
     *
     * @return int|null
     */
    public function getId()
    {
        return $this->getData(self::GROUP_ID);
    }

    /**
     * Set ID
     *
     * @param int $id
     * @return SliderGroupInterface
     */
    public function setId($id)
    {
        return $this->setData(self::GROUP_ID, $id);
    }

    /**
     * Get title
     *
     * @return string|null
     */
    public function getTitle()
    {
        return $this->getData(self::TITLE);
    }

    /**
     * Set title
     *
     * @param string $title
     * @return SliderGroupInterface
     */
    public function setTitle($title)
    {
        return $this->setData(self::TITLE, $title);
    }

    /**
     * Get configuration
     *
     * @return string|null
     */
    public function getConfiguration()
    {
        return $this->getData(self::CONFIGURATION);
    }

    /**
     * Set configuration
     *
     * @param string $configuration
     * @return SliderGroupInterface
     */
    public function setConfiguration($configuration)
    {
        return $this->setData(self::CONFIGURATION, $configuration);
    }

    /**
     * Get date created
     *
     * @return string|null
     */
    public function getCreated()
    {
        return $this->getData(self::CREATED);
    }

    /**
     * Set created date
     *
     * @param string $creationDate
     * @return SliderGroupInterface
     */
    public function setCreated($creationDate)
    {
        return $this->setData(self::CREATED, $creationDate);
    }

    /**
     * Get date updated
     *
     * @return string|null
     */
    public function getModified()
    {
        return $this->getData(self::MODIFIED);
    }

    /**
     * Set created date
     *
     * @param string $modifiedDate
     * @return SliderGroupInterface
     */
    public function setModified($modifiedDate)
    {
        return $this->setData(self::MODIFIED, $modifiedDate);
    }
}
