<?php

namespace Guerinteed\Slider\Api\Data;

/**
 * SliderGroup interface
 * @api
 * @package Guerinteed\Slider\Api\Data
 */
interface SliderGroupInterface
{
    /**#@+
     * Constants for keys of data array. Identical to the name of the getter in snake case
     */
    const GROUP_ID = 'group_id';
    const TITLE = 'title';
    const CONFIGURATION = 'configuration';
    const CREATED = 'created';
    const MODIFIED = 'modified';

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
     * @return SliderGroupInterface
     */
    public function setId($id);

    /**
     * Get title
     *
     * @return string|null
     */
    public function getTitle();

    /**
     * Set title
     *
     * @param string $title
     * @return SliderGroupInterface
     */
    public function setTitle($title);

    /**
     * Get configuration
     *
     * @return string|null
     */
    public function getConfiguration();

    /**
     * Set configuration
     *
     * @param string $configuration
     * @return SliderGroupInterface
     */
    public function setConfiguration($configuration);

    /**
     * Get date created
     *
     * @return string|null
     */
    public function getCreated();

    /**
     * Set created date
     *
     * @param string $creationDate
     * @return SliderGroupInterface
     */
    public function setCreated($creationDate);

    /**
     * Get date updated
     *
     * @return string|null
     */
    public function getModified();

    /**
     * Set created date
     *
     * @param string $modifiedDate
     * @return SliderGroupInterface
     */
    public function setModified($modifiedDate);
}
