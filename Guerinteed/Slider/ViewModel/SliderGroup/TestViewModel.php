<?php
declare(strict_types=1);

namespace Guerinteed\Slider\ViewModel\SliderGroup;

use Guerinteed\Slider\Model\SliderGroupRepository;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Guerinteed\Slider\Api\SliderGroupRepositoryInterface as Resource;
use Guerinteed\Slider\Model\SliderGroupFactory;
use Guerinteed\Slider\Model\SliderGroup;

class TestViewModel implements ArgumentInterface
{
    /**
     * @var SliderGroupRepository
     */
    protected $sliderGroupRepository;

    /**
     * @var SliderGroupFactory
     */
    protected $sliderGroupFactory;

    /**
     * TestViewModel constructor.
     * @param SliderGroupRepository $sliderGroupRepository
     * @param SliderGroupFactory $sliderGroupFactory
     */
    public function __construct(
        SliderGroupRepository $sliderGroupRepository,
        SliderGroupFactory $sliderGroupFactory
    ) {
        $this->sliderGroupRepository = $sliderGroupRepository;
        $this->sliderGroupFactory = $sliderGroupFactory;
    }


    public function TestMe()
    {
        return "Got Here";
    }

    public function getTestRecord()
    {

        $model = $this->sliderGroupFactory->create();
        $model = $this->sliderGroupRepository->getById(1);
        return $model;
    }
}
