<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Template;

use \Ess\M2ePro\Model\Amazon\Template\Description\Definition;

/**
 * @method \Ess\M2ePro\Model\Template\Description getParentObject()
 * @method \Ess\M2ePro\Model\ResourceModel\Amazon\Template\Description getResource()
 */
class Description extends \Ess\M2ePro\Model\ActiveRecord\Component\Child\Amazon\AbstractModel
{
    /**
     * @var \Ess\M2ePro\Model\Marketplace
     */
    private $marketplaceModel = null;

    /**
     * @var \Ess\M2ePro\Model\Amazon\Template\Description\Definition
     */
    private $descriptionDefinitionModel = null;

    /**
     * @var \Ess\M2ePro\Model\Amazon\Template\Description\Source[]
     */
    private $descriptionSourceModels = [];

    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init(\Ess\M2ePro\Model\ResourceModel\Amazon\Template\Description::class);
    }

    //########################################

    /**
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function isLocked()
    {
        if (parent::isLocked()) {
            return true;
        }

        $collection = $this->activeRecordFactory->getObject('Amazon\Listing')->getCollection();
        $collection->getSelect()
            ->where("main_table.auto_global_adding_description_template_id = {$this->getId()} OR
                     main_table.auto_website_adding_description_template_id = {$this->getId()}");

        return (bool)$this->activeRecordFactory->getObject('Amazon_Listing_Product')->getCollection()
                        ->addFieldToFilter('template_description_id', $this->getId())
                        ->getSize() ||
               (bool)$this->activeRecordFactory->getObject('Amazon_Listing_Auto_Category_Group')->getCollection()
                        ->addFieldToFilter('adding_description_template_id', $this->getId())
                        ->getSize() ||
               (bool)$collection->getSize();
    }

    /**
     * @return bool
     */
    public function isLockedForCategoryChange()
    {
        $collection = $this->parentFactory->getObject(\Ess\M2ePro\Helper\Component\Amazon::NICK, 'Listing\Product')
            ->getCollection()
            ->addFieldToFilter('template_description_id', $this->getId())
            ->addFieldToFilter(
                'is_general_id_owner',
                \Ess\M2ePro\Model\Amazon\Listing\Product::IS_GENERAL_ID_OWNER_YES
            );

        if ($collection->getSize() <= 0) {
            return false;
        }

        $processingLockCollection = $this->activeRecordFactory->getObject('Processing\Lock')->getCollection();
        $processingLockCollection->addFieldToFilter('model_name', 'Listing_Product');
        $lockedListingProductsIds = $processingLockCollection->getColumnValues('object_id');

        $mysqlIds = implode(',', array_map('intval', $lockedListingProductsIds));
        $notListed = \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED;

        $whereConditions = ['(`is_variation_parent` = 1 AND `general_id` IS NOT NULL)'];
        if (!empty($mysqlIds)) {
            $whereConditions[] = "(`is_variation_parent` = 0 AND `status` = {$notListed} AND `id` IN ({$mysqlIds}))";
            $whereConditions[] = "(`is_variation_parent` = 1 AND `general_id` IS NULL AND `id` IN ({$mysqlIds}))";
        }

        $collection->getSelect()->where(implode(' OR ', $whereConditions));

        return (bool)$collection->getSize();
    }

    /**
     * @return bool
     */
    public function isLockedForNewAsinCreation()
    {
        $collection = $this->parentFactory->getObject(\Ess\M2ePro\Helper\Component\Amazon::NICK, 'Listing\Product')
            ->getCollection()
            ->addFieldToFilter('template_description_id', $this->getId())
            ->addFieldToFilter(
                'is_general_id_owner',
                \Ess\M2ePro\Model\Amazon\Listing\Product::IS_GENERAL_ID_OWNER_YES
            );

        $collection->getSelect()
            ->where("(`is_variation_parent` = 0 AND `status` = ?) OR
                     (`is_variation_parent` = 1)", \Ess\M2ePro\Model\Listing\Product::STATUS_NOT_LISTED);

        return (bool)$collection->getSize();
    }

    //########################################

    public function save()
    {
        $this->getHelper('Data_Cache_Permanent')->removeTagValues('amazon_template_description');
        return parent::save();
    }

    //########################################

    public function delete()
    {
        if ($this->isLocked()) {
            return false;
        }

        $this->getDefinitionTemplate()->delete();

        foreach ($this->getSpecifics(true) as $specific) {
            $specific->delete();
        }

        $this->marketplaceModel           = null;
        $this->descriptionDefinitionModel = null;
        $this->descriptionSourceModels    = [];

        $this->getHelper('Data_Cache_Permanent')->removeTagValues('amazon_template_description');

        return parent::delete();
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Marketplace
     */
    public function getMarketplace()
    {
        if ($this->marketplaceModel === null) {
            $this->marketplaceModel = $this->parentFactory->getCachedObjectLoaded(
                \Ess\M2ePro\Helper\Component\Amazon::NICK,
                'Marketplace',
                $this->getMarketplaceId()
            );
        }

        return $this->marketplaceModel;
    }

    /**
     * @param \Ess\M2ePro\Model\Marketplace $instance
     */
    public function setMarketplace(\Ess\M2ePro\Model\Marketplace $instance)
    {
        $this->marketplaceModel = $instance;
    }

    // ---------------------------------------

    public function setDefinitionTemplate(Definition $descriptionDefinitionModel)
    {
        $this->descriptionDefinitionModel = $descriptionDefinitionModel;
        return $this;
    }

    /**
     * @return \Ess\M2ePro\Model\Amazon\Template\Description\Definition
     */
    public function getDefinitionTemplate()
    {
        if ($this->descriptionDefinitionModel === null) {
            $this->descriptionDefinitionModel = $this->activeRecordFactory->getCachedObjectLoaded(
                'Amazon_Template_Description_Definition',
                $this->getId()
            );

            $this->descriptionDefinitionModel->setDescriptionTemplate($this->getParentObject());
        }

        return $this->descriptionDefinitionModel;
    }

    /**
     * @param bool $asObjects
     * @param array $filters
     * @return array|\Ess\M2ePro\Model\Amazon\Template\Description\Specific[]
     */
    public function getSpecifics($asObjects = false, array $filters = [])
    {
        $specifics = $this->getRelatedSimpleItems(
            'Amazon_Template_Description_Specific',
            'template_description_id',
            $asObjects,
            $filters
        );
        if ($asObjects) {
            /** @var \Ess\M2ePro\Model\Amazon\Template\Description\Specific $specific */
            foreach ($specifics as $specific) {
                $specific->setDescriptionTemplate($this->getParentObject());
            }
        }

        if (!$asObjects) {
            foreach ($specifics as &$specific) {
                $specific['attributes'] = (array)$this->getHelper('Data')->jsonDecode($specific['attributes']);
            }
        }

        return $specifics;
    }

    //########################################

    /**
     * @param \Ess\M2ePro\Model\Magento\Product $magentoProduct
     * @return \Ess\M2ePro\Model\Amazon\Template\Description\Source
     */
    public function getSource(\Ess\M2ePro\Model\Magento\Product $magentoProduct)
    {
        $productId = $magentoProduct->getProductId();

        if (!empty($this->descriptionSourceModels[$productId])) {
            return $this->descriptionSourceModels[$productId];
        }

        $this->descriptionSourceModels[$productId] = $this->modelFactory->getObject(
            'Amazon_Template_Description_Source'
        );
        $this->descriptionSourceModels[$productId]->setMagentoProduct($magentoProduct);
        $this->descriptionSourceModels[$productId]->setDescriptionTemplate($this->getParentObject());

        return $this->descriptionSourceModels[$productId];
    }

    //########################################

    /**
     * @return int
     */
    public function getMarketplaceId()
    {
        return (int)$this->getData('marketplace_id');
    }

    /**
     * @return bool
     */
    public function isNewAsinAccepted()
    {
        return (bool)$this->getData('is_new_asin_accepted');
    }

    // ---------------------------------------

    public function getCategoryPath()
    {
        return $this->getData('category_path');
    }

    public function getBrowsenodeId()
    {
        return $this->getData('browsenode_id');
    }

    public function getProductDataNick()
    {
        return $this->getData('product_data_nick');
    }

    //########################################

    public function isCacheEnabled()
    {
        return true;
    }

    public function getCacheGroupTags()
    {
        return array_merge(parent::getCacheGroupTags(), ['template']);
    }

    //########################################
}
