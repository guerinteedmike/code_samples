<?php
declare(strict_types=1);

namespace Guerinteed\Slider\Model;

use Guerinteed\Slider\Api\SliderGroupRepositoryInterface;
use Guerinteed\Slider\Api\Data;
use Guerinteed\Slider\Model\ResourceModel\SliderGroup as SliderGroupResource;
use Guerinteed\Slider\Model\ResourceModel\SliderGroup\CollectionFactory as SliderGroupCollectionFactory;
use Guerinteed\Slider\Api\Data\SliderGroupSearchResultsInterface;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

class SliderGroupRepository implements SliderGroupRepositoryInterface
{
    /**
     * @var SliderGroupResource
     */
    protected $resource;

    /**
     * @var SliderGroupFactory;
     */
    protected $sliderGroupFactory;

    /**
     * @var SliderGroupCollectionFactory;
     */
    protected $sliderGroupCollectionFactory;

    /**
     * @var \Guerintee\Slider\Api\Data\SearchResultsInterfaceFactory;
     */
    protected $searchResultsFactory;

    /**
     * @var CollectionProcessorInterface
     */
    private $collectionProcessor;

    /**
     * @param SliderGroupResource $resource
     * @param SliderGroupFactory $sliderGroupFactory
     * @param SliderGroupCollectionFactory $sliderGroupCollectionFactory
     * @param CollectionProcessorInterface $collectionProcessor
     * @param \Guerinteed\Slider\Api\Data\SliderGroupSearchResultsInterfaceFactory $searchResultsFactory
     */
    public function __construct(
        SliderGroupResource $resource,
        SliderGroupFactory $sliderGroupFactory,
        SliderGroupCollectionFactory $sliderGroupCollectionFactory,
        \Guerinteed\Slider\Api\Data\SliderGroupSearchResultsInterfaceFactory $searchResultsFactory,
        CollectionProcessorInterface $collectionProcessor

    ) {
        $this->resource = $resource;
        $this->sliderGroupFactory = $sliderGroupFactory;
        $this->sliderGroupCollectionFactory = $sliderGroupCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->collectionProcessor = $collectionProcessor ?: $this->getCollectionProcessor();
    }

    /**
     * Save SliderGroup.
     *
     * @param \Guerinteed\Slider\Api\Data\SliderGroupInterface $sliderGroup
     * @return \Guerinteed\Slider\Api\Data\SliderGroupInterface
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function save(\Guerinteed\Slider\Api\Data\SliderGroupInterface $sliderGroup)
    {
        try {

            /** @var SliderGroup $sliderGroup */
            $this->resource->save($sliderGroup);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__($exception->getMessage()));
        }
        return $sliderGroup;
    }

    /**
     * @param int $sliderGroupId
     * @return \Guerinteed\Slider\Api\Data\SliderGroupInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById($sliderGroupId)
    {
        $sliderGroup = $this->sliderGroupFactory->create();
        $this->resource->load($sliderGroup, $sliderGroupId);
        if (!$sliderGroup->getId()) {
            throw new NoSuchEntityException(__('The SliderGroup with ID "%1" does not exist.', $sliderGroupId));
        }
        return $sliderGroup;
    }

    /**
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Guerinteed\Slider\Api\Data\SliderGroupSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria)
    {
        /** @var \Guerinteed\Slider\Model\ResourceModel\SliderGroup\Collection $collection */
        $collection = $this->sliderGroupCollectionFactory->create();

        $this->collectionProcessor->process($searchCriteria, $collection);

        /** @var SliderGroupSearchResultsInterface $searchResults */
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);
        $searchResults->setItems($collection->getItems());
        $searchResults->setTotalCount($collection->getSize());
        return $searchResults;
    }

    /**
     * @param \Guerinteed\Slider\Api\Data\SliderGroupInterface $sliderGroup
     * @return bool true on success
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     */
    public function delete(\Guerinteed\Slider\Api\Data\SliderGroupInterface $sliderGroup)
    {
        try {
            /** @var SliderGroup $sliderGroup */
            $this->resource->delete($sliderGroup);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__($exception->getMessage()));
        }
    }

    /**
     * @param int $sliderGroupId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function deleteById($sliderGroupId)
    {
        return $this->delete($this->getById($sliderGroupId));
    }

    /**
     * Retrieve collection processor
     *
     * @deprecated 101.0.0
     * @return CollectionProcessorInterface
     */
    private function getCollectionProcessor()
    {
        if (!$this->collectionProcessor) {
            $this->collectionProcessor = \Magento\Framework\App\ObjectManager::getInstance()->get(
                \Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface::class
            );
        }
        return $this->collectionProcessor;
    }
}
