<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\Component\Walmart;

class Vocabulary extends \Ess\M2ePro\Helper\Module\Product\Variation\Vocabulary
{
    /** @var \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory */
    private $walmartParentFactory;
    /** @var \Ess\M2ePro\Model\Factory */
    private $modelFactory;

    /**
     * @param \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartParentFactory
     * @param \Ess\M2ePro\Model\Factory $modelFactory
     * @param \Ess\M2ePro\Helper\Module $moduleHelper
     * @param \Ess\M2ePro\Helper\Module\Exception $exceptionHelper
     * @param \Ess\M2ePro\Helper\Data\Cache\Permanent $permanentCacheHelper
     * @param \Ess\M2ePro\Model\Config\Manager $config
     * @param \Ess\M2ePro\Model\Registry\Manager $registry
     */
    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartParentFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Helper\Module $moduleHelper,
        \Ess\M2ePro\Helper\Module\Exception $exceptionHelper,
        \Ess\M2ePro\Helper\Data\Cache\Permanent $permanentCacheHelper,
        \Ess\M2ePro\Model\Config\Manager $config,
        \Ess\M2ePro\Model\Registry\Manager $registry
    ) {
        $this->walmartParentFactory = $walmartParentFactory;
        parent::__construct(
            $modelFactory,
            $moduleHelper,
            $exceptionHelper,
            $permanentCacheHelper,
            $config,
            $registry
        );
        $this->modelFactory = $modelFactory;
    }

    // ----------------------------------------

    public function addAttribute($productAttribute, $channelAttribute)
    {
        if (!parent::addAttribute($productAttribute, $channelAttribute)) {
            return;
        }

        $affectedParentListingsProducts = $this->getParentListingsProductsAffectedToAttribute($channelAttribute);
        if (empty($affectedParentListingsProducts)) {
            return;
        }

        $massProcessor = $this->modelFactory->getObject(
            'Walmart_Listing_Product_Variation_Manager_Type_Relation_ParentRelation_Processor_Mass'
        );
        $massProcessor->setListingsProducts($affectedParentListingsProducts);
        $massProcessor->setForceExecuting(false);

        $massProcessor->execute();
    }

    public function addOption($productOption, $channelOption, $channelAttribute)
    {
        if (!parent::addOption($productOption, $channelOption, $channelAttribute)) {
            return;
        }

        $affectedParentListingsProducts = $this->getParentListingsProductsAffectedToOption(
            $channelAttribute,
            $channelOption
        );

        if (empty($affectedParentListingsProducts)) {
            return;
        }

        $massProcessor = $this->modelFactory->getObject(
            'Walmart_Listing_Product_Variation_Manager_Type_Relation_ParentRelation_Processor_Mass'
        );
        $massProcessor->setListingsProducts($affectedParentListingsProducts);
        $massProcessor->setForceExecuting(false);

        $massProcessor->execute();
    }

    //########################################

    public function getParentListingsProductsAffectedToAttribute($channelAttribute)
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection $collection */
        $collection = $this->walmartParentFactory->getObject('Listing\Product')->getCollection();
        $collection->addFieldToFilter('is_variation_parent', 1);

        $collection->addFieldToFilter(
            'additional_data',
            ['regexp' => '"variation_channel_attributes":.*"' . $channelAttribute . '"']
        );

        return $collection->getItems();
    }

    public function getParentListingsProductsAffectedToOption($channelAttribute, $channelOption)
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection $collection */
        $collection = $this->walmartParentFactory->getObject('Listing\Product')->getCollection();
        $collection->addFieldToFilter('variation_parent_id', ['notnull' => true]);

        $collection->addFieldToFilter('additional_data', [
            'regexp' => '"variation_channel_options":.*"' . $channelAttribute . '":"' . $channelOption . '"}',
        ]);

        $collection->getSelect()->reset(\Magento\Framework\DB\Select::COLUMNS);
        $collection->getSelect()->columns([
            'second_table.variation_parent_id',
        ]);

        $parentIds = $collection->getColumnValues('variation_parent_id');
        if (empty($parentIds)) {
            return [];
        }

        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Product\Collection $collection */
        $collection = $this->walmartParentFactory->getObject('Listing\Product')->getCollection();
        $collection->addFieldToFilter('is_variation_parent', 1);
        $collection->addFieldToFilter('id', ['in' => $parentIds]);

        return $collection->getItems();
    }
}
