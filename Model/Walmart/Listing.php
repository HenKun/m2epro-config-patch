<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

/**
 * @method \Ess\M2ePro\Model\Listing getParentObject()
 * @method \Ess\M2ePro\Model\ResourceModel\Walmart\Listing getResource()
 */

namespace Ess\M2ePro\Model\Walmart;

/**
 * Class \Ess\M2ePro\Model\Walmart\Listing
 */
class Listing extends \Ess\M2ePro\Model\ActiveRecord\Component\Child\Walmart\AbstractModel
{
    //########################################

    /**
     * @var \Ess\M2ePro\Model\Template\Description
     */
    private $descriptionTemplateModel = null;

    /**
     * @var \Ess\M2ePro\Model\Template\SellingFormat
     */
    private $sellingFormatTemplateModel = null;

    /**
     * @var \Ess\M2ePro\Model\Template\Synchronization
     */
    private $synchronizationTemplateModel = null;

    /** @var \Ess\M2ePro\Helper\Module\Configuration */
    private $moduleConfiguration;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Ess\M2ePro\Helper\Module\Configuration $moduleConfiguration,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $walmartFactory,
            $parentFactory,
            $modelFactory,
            $activeRecordFactory,
            $helperFactory,
            $context,
            $registry,
            $resource,
            $resourceCollection,
            $data
        );

        $this->moduleConfiguration = $moduleConfiguration;
    }

    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init(\Ess\M2ePro\Model\ResourceModel\Walmart\Listing::class);
    }

    //########################################

    public function delete()
    {
        $this->getHelper('Data_Cache_Permanent')->removeTagValues('listing');

        $temp = parent::delete();
        $temp && $this->descriptionTemplateModel = null;
        $temp && $this->sellingFormatTemplateModel = null;
        $temp && $this->synchronizationTemplateModel = null;
        return $temp;
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Account
     */
    public function getAccount()
    {
        return $this->getParentObject()->getAccount();
    }

    /**
     * @return \Ess\M2ePro\Model\Walmart\Account
     */
    public function getWalmartAccount()
    {
        return $this->getAccount()->getChildObject();
    }

    // ---------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Marketplace
     */
    public function getMarketplace()
    {
        return $this->getParentObject()->getMarketplace();
    }

    /**
     * @return \Ess\M2ePro\Model\Walmart\Marketplace
     */
    public function getWalmartMarketplace()
    {
        return $this->getMarketplace()->getChildObject();
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Template\Description
     */
    public function getDescriptionTemplate()
    {
        if ($this->descriptionTemplateModel === null) {
            $this->descriptionTemplateModel = $this->walmartFactory->getCachedObjectLoaded(
                'Template\Description',
                $this->getData('template_description_id')
            );
        }

        return $this->descriptionTemplateModel;
    }

    /**
     * @param \Ess\M2ePro\Model\Template\Description $instance
     */
    public function setDescriptionTemplate(\Ess\M2ePro\Model\Template\Description $instance)
    {
        $this->descriptionTemplateModel = $instance;
    }

    // ---------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Template\SellingFormat
     */
    public function getSellingFormatTemplate()
    {
        if ($this->sellingFormatTemplateModel === null) {
            $this->sellingFormatTemplateModel = $this->walmartFactory->getCachedObjectLoaded(
                'Template\SellingFormat',
                $this->getData('template_selling_format_id')
            );
        }

        return $this->sellingFormatTemplateModel;
    }

    /**
     * @param \Ess\M2ePro\Model\Template\SellingFormat $instance
     */
    public function setSellingFormatTemplate(\Ess\M2ePro\Model\Template\SellingFormat $instance)
    {
        $this->sellingFormatTemplateModel = $instance;
    }

    // ---------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Template\Synchronization
     */
    public function getSynchronizationTemplate()
    {
        if ($this->synchronizationTemplateModel === null) {
            $this->synchronizationTemplateModel = $this->walmartFactory->getCachedObjectLoaded(
                'Template\Synchronization',
                $this->getData('template_synchronization_id')
            );
        }

        return $this->synchronizationTemplateModel;
    }

    /**
     * @param \Ess\M2ePro\Model\Template\Synchronization $instance
     */
    public function setSynchronizationTemplate(\Ess\M2ePro\Model\Template\Synchronization $instance)
    {
        $this->synchronizationTemplateModel = $instance;
    }

    // ---------------------------------------

    /**
     * @return \Ess\M2ePro\Model\Walmart\Template\SellingFormat
     */
    public function getWalmartDescriptionTemplate()
    {
        return $this->getDescriptionTemplate()->getChildObject();
    }

    /**
     * @return \Ess\M2ePro\Model\Walmart\Template\SellingFormat
     */
    public function getWalmartSellingFormatTemplate()
    {
        return $this->getSellingFormatTemplate()->getChildObject();
    }

    /**
     * @return \Ess\M2ePro\Model\Walmart\Template\Synchronization
     */
    public function getWalmartSynchronizationTemplate()
    {
        return $this->getSynchronizationTemplate()->getChildObject();
    }

    //########################################

    /**
     * @param bool $asObjects
     * @param array $filters
     * @return array
     */
    public function getProducts($asObjects = false, array $filters = [])
    {
        return $this->getParentObject()->getProducts($asObjects, $filters);
    }

    //########################################

    /**
     * @return int
     */
    public function getAutoGlobalAddingCategoryTemplateId()
    {
        return (int)$this->getData('auto_global_adding_category_template_id');
    }

    /**
     * @return int
     */
    public function getAutoWebsiteAddingCategoryTemplateId()
    {
        return (int)$this->getData('auto_website_adding_category_template_id');
    }

    //########################################

    /**
     * @param \Ess\M2ePro\Model\Listing\Other $listingOtherProduct
     * @param int $initiator
     * @return bool|\Ess\M2ePro\Model\Listing\Product
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function addProductFromOther(
        \Ess\M2ePro\Model\Listing\Other $listingOtherProduct,
        $initiator = \Ess\M2ePro\Helper\Data::INITIATOR_UNKNOWN
    ) {
        if (!$listingOtherProduct->getProductId()) {
            return false;
        }

        $productId = $listingOtherProduct->getProductId();
        $result = $this->getParentObject()->addProduct($productId, $initiator, false, true);

        if (!($result instanceof \Ess\M2ePro\Model\Listing\Product)) {
            return false;
        }

        $listingProduct = $result;

        /** @var \Ess\M2ePro\Model\Walmart\Listing\Product $walmartListingProduct */
        $walmartListingProduct = $listingProduct->getChildObject();
        $variationManager = $walmartListingProduct->getVariationManager();

        if ($variationManager->isRelationParentType()) {
            $variationManager->switchModeToAnother();
        }

        /** @var \Ess\M2ePro\Model\Walmart\Listing\Other $walmartListingOther */
        $walmartListingOther = $listingOtherProduct->getChildObject();

        $dataForUpdate = [
            'sku'  => $walmartListingOther->getSku(),
            'gtin' => $walmartListingOther->getGtin(),
            'upc'  => $walmartListingOther->getUpc(),
            'ean'  => $walmartListingOther->getEan(),
            'wpid' => $walmartListingOther->getWpid(),

            'item_id'     => $walmartListingOther->getItemId(),

            'online_price'            => $walmartListingOther->getOnlinePrice(),
            'online_qty'              => $walmartListingOther->getOnlineQty(),
            'is_online_price_invalid' => $walmartListingOther->isOnlinePriceInvalid(),

            'status'         => $listingOtherProduct->getStatus(),
            'status_changer' => $listingOtherProduct->getStatusChanger(),

            'publish_status'   => $walmartListingOther->getPublishStatus(),
            'lifecycle_status' => $walmartListingOther->getLifecycleStatus(),

            'status_change_reasons' => $walmartListingOther->getData('status_change_reasons'),
        ];

        $listingProduct->setSetting(
            'additional_data',
            $listingProduct::MOVING_LISTING_OTHER_SOURCE_KEY,
            $listingOtherProduct->getId()
        );

        if ($listingProduct->getMagentoProduct()->isGroupedType() &&
            $this->moduleConfiguration->isGroupedProductModeSet()
        ) {
            $listingProduct->setSetting('additional_data', 'grouped_product_mode', 1);
        }

        $listingProduct->addData($dataForUpdate);
        $walmartListingProduct->addData($dataForUpdate);
        $listingProduct->save();

        $listingOtherProduct->setSetting(
            'additional_data',
            $listingOtherProduct::MOVING_LISTING_PRODUCT_DESTINATION_KEY,
            $listingProduct->getId()
        );

        $listingOtherProduct->save();

        $walmartItem = $walmartListingProduct->getWalmartItem();
        if ($listingProduct->getMagentoProduct()->isGroupedType() &&
            $this->moduleConfiguration->isGroupedProductModeSet()
        ) {
            $walmartItem->setAdditionalData(json_encode(['grouped_product_mode' => 1]));
        }
        $walmartItem->setData('store_id', $this->getParentObject()->getStoreId())
                    ->save();

        $instruction = $this->activeRecordFactory->getObject('Listing_Product_Instruction');
        $instruction->setData(
            [
                'listing_product_id' => $listingProduct->getId(),
                'component'          => \Ess\M2ePro\Helper\Component\Walmart::NICK,
                'type'               => \Ess\M2ePro\Model\Listing::INSTRUCTION_TYPE_PRODUCT_MOVED_FROM_OTHER,
                'initiator'          => \Ess\M2ePro\Model\Listing::INSTRUCTION_INITIATOR_MOVING_PRODUCT_FROM_OTHER,
                'priority'           => 20,
            ]
        );
        $instruction->save();

        return $listingProduct;
    }

    public function addProductFromListing(
        \Ess\M2ePro\Model\Listing\Product $listingProduct,
        \Ess\M2ePro\Model\Listing $sourceListing
    ) {
        if (!$this->getParentObject()->addProductFromListing($listingProduct, $sourceListing, false)) {
            return false;
        }

        if ($this->getParentObject()->getStoreId() != $sourceListing->getStoreId()) {
            if (!$listingProduct->isNotListed()) {
                if ($item = $listingProduct->getChildObject()->getWalmartItem()) {
                    $item->setData('store_id', $this->getParentObject()->getStoreId());
                    $item->save();
                }
            }
        }

        return true;
    }

    //########################################

    public function isCacheEnabled()
    {
        return true;
    }

    //########################################
}
