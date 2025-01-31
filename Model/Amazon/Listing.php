<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon;

/**
 * @method \Ess\M2ePro\Model\Listing getParentObject()
 * @method \Ess\M2ePro\Model\ResourceModel\Amazon\Listing getResource()
 */
class Listing extends \Ess\M2ePro\Model\ActiveRecord\Component\Child\Amazon\AbstractModel
{
    public const SKU_MODE_PRODUCT_ID       = 3;
    public const SKU_MODE_DEFAULT          = 1;
    public const SKU_MODE_CUSTOM_ATTRIBUTE = 2;

    public const SKU_MODIFICATION_MODE_NONE     = 0;
    public const SKU_MODIFICATION_MODE_PREFIX   = 1;
    public const SKU_MODIFICATION_MODE_POSTFIX  = 2;
    public const SKU_MODIFICATION_MODE_TEMPLATE = 3;

    public const GENERATE_SKU_MODE_NO  = 0;
    public const GENERATE_SKU_MODE_YES = 1;

    public const CONDITION_MODE_DEFAULT          = 1;
    public const CONDITION_MODE_CUSTOM_ATTRIBUTE = 2;

    public const CONDITION_NEW                    = 'New';
    public const CONDITION_USED_LIKE_NEW          = 'UsedLikeNew';
    public const CONDITION_USED_VERY_GOOD         = 'UsedVeryGood';
    public const CONDITION_USED_GOOD              = 'UsedGood';
    public const CONDITION_USED_ACCEPTABLE        = 'UsedAcceptable';
    public const CONDITION_COLLECTIBLE_LIKE_NEW   = 'CollectibleLikeNew';
    public const CONDITION_COLLECTIBLE_VERY_GOOD  = 'CollectibleVeryGood';
    public const CONDITION_COLLECTIBLE_GOOD       = 'CollectibleGood';
    public const CONDITION_COLLECTIBLE_ACCEPTABLE = 'CollectibleAcceptable';
    public const CONDITION_REFURBISHED            = 'Refurbished';
    public const CONDITION_CLUB                   = 'Club';

    public const CONDITION_NOTE_MODE_NONE         = 3;
    public const CONDITION_NOTE_MODE_CUSTOM_VALUE = 1;

    public const IMAGE_MAIN_MODE_NONE      = 0;
    public const IMAGE_MAIN_MODE_PRODUCT   = 1;
    public const IMAGE_MAIN_MODE_ATTRIBUTE = 2;

    public const GALLERY_IMAGES_MODE_NONE      = 0;
    public const GALLERY_IMAGES_MODE_PRODUCT   = 1;
    public const GALLERY_IMAGES_MODE_ATTRIBUTE = 2;

    public const GALLERY_IMAGES_COUNT_MAX = 5;

    public const HANDLING_TIME_MODE_NONE             = 3;
    public const HANDLING_TIME_MODE_RECOMMENDED      = 1;
    public const HANDLING_TIME_MODE_CUSTOM_ATTRIBUTE = 2;

    public const RESTOCK_DATE_MODE_NONE             = 1;
    public const RESTOCK_DATE_MODE_CUSTOM_VALUE     = 2;
    public const RESTOCK_DATE_MODE_CUSTOM_ATTRIBUTE = 3;

    public const GIFT_WRAP_MODE_NO        = 0;
    public const GIFT_WRAP_MODE_YES       = 1;
    public const GIFT_WRAP_MODE_ATTRIBUTE = 2;

    public const GIFT_MESSAGE_MODE_NO        = 0;
    public const GIFT_MESSAGE_MODE_YES       = 1;
    public const GIFT_MESSAGE_MODE_ATTRIBUTE = 2;

    public const ADDING_MODE_ADD_AND_CREATE_NEW_ASIN_NO  = 0;
    public const ADDING_MODE_ADD_AND_CREATE_NEW_ASIN_YES = 1;

    public const CREATE_LISTING_SESSION_DATA = 'amazon_listing_create';

    /** @var \Ess\M2ePro\Helper\Module\Configuration */
    private $moduleConfiguration;
    /** @var \Ess\M2ePro\Model\Currency */
    protected $currencyModel;

    /** @var \Ess\M2ePro\Model\Template\SellingFormat */
    private $sellingFormatTemplateModel = null;
    /** @var \Ess\M2ePro\Model\Template\Synchronization */
    private $synchronizationTemplateModel = null;
    /** @var \Ess\M2ePro\Model\Amazon\Listing\Source[] */
    private $listingSourceModels = [];

    public function __construct(
        \Ess\M2ePro\Model\Currency $currencyModel,
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
        $this->currencyModel = $currencyModel;
        $this->moduleConfiguration = $moduleConfiguration;

        parent::__construct(
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
    }

    public function _construct()
    {
        parent::_construct();
        $this->_init(\Ess\M2ePro\Model\ResourceModel\Amazon\Listing::class);
    }

    public function save()
    {
        $this->getHelper('Data_Cache_Permanent')->removeTagValues('listing');

        return parent::save();
    }

    //########################################

    public function delete()
    {
        $this->getHelper('Data_Cache_Permanent')->removeTagValues('listing');

        $temp = parent::delete();
        $temp && $this->sellingFormatTemplateModel = null;
        $temp && $this->synchronizationTemplateModel = null;

        return $temp;
    }

    //########################################

    /**
     * @param \Ess\M2ePro\Model\Magento\Product $magentoProduct
     * @return \Ess\M2ePro\Model\Amazon\Listing\Source
     */
    public function getSource(\Ess\M2ePro\Model\Magento\Product $magentoProduct)
    {
        $productId = $magentoProduct->getProductId();

        if (!empty($this->listingSourceModels[$productId])) {
            return $this->listingSourceModels[$productId];
        }

        $this->listingSourceModels[$productId] = $this->modelFactory->getObject('Amazon_Listing_Source');
        $this->listingSourceModels[$productId]->setMagentoProduct($magentoProduct);
        $this->listingSourceModels[$productId]->setListing($this->getParentObject());

        return $this->listingSourceModels[$productId];
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
     * @return \Ess\M2ePro\Model\Amazon\Account
     */
    public function getAmazonAccount()
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
     * @return \Ess\M2ePro\Model\Amazon\Marketplace
     */
    public function getAmazonMarketplace()
    {
        return $this->getMarketplace()->getChildObject();
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Template\SellingFormat
     */
    public function getSellingFormatTemplate()
    {
        if ($this->sellingFormatTemplateModel === null) {
            $this->sellingFormatTemplateModel = $this->parentFactory->getCachedObjectLoaded(
                $this->getComponentMode(),
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
            $this->synchronizationTemplateModel = $this->parentFactory->getCachedObjectLoaded(
                $this->getComponentMode(),
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
     * @return \Ess\M2ePro\Model\Amazon\Template\SellingFormat
     */
    public function getAmazonSellingFormatTemplate()
    {
        return $this->getSellingFormatTemplate()->getChildObject();
    }

    /**
     * @return \Ess\M2ePro\Model\Amazon\Template\Synchronization
     */
    public function getAmazonSynchronizationTemplate()
    {
        return $this->getSynchronizationTemplate()->getChildObject();
    }
    // ---------------------------------------

    /**
     * @return int
     */
    public function getTemplateShippingId()
    {
        return (int)($this->getData('template_shipping_id'));
    }

    /**
     * @return bool
     */
    public function isExistShippingTemplate()
    {
        return $this->getTemplateShippingId() > 0;
    }

    /**
     * @return \Ess\M2ePro\Model\Amazon\Template\Shipping | null
     */
    public function getShippingTemplate()
    {
        if (!$this->isExistShippingTemplate()) {
            return null;
        }

        return $this->activeRecordFactory->getCachedObjectLoaded(
            'Amazon_Template_Shipping',
            $this->getTemplateShippingId()
        );
    }

    // ---------------------------------------

    /**
     * @param \Ess\M2ePro\Model\Magento\Product $magentoProduct
     * @return \Ess\M2ePro\Model\Amazon\Template\Shipping\Source
     */
    public function getShippingTemplateSource(\Ess\M2ePro\Model\Magento\Product $magentoProduct)
    {
        if (!$this->isExistShippingTemplate()) {
            return null;
        }

        return $this->getShippingTemplate()->getSource($magentoProduct);
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

    /**
     * @param bool $asObjects
     * @param array $filters
     * @return mixed
     */
    public function getCategories($asObjects = false, array $filters = [])
    {
        return $this->getParentObject()->getCategories($asObjects, $filters);
    }

    //########################################

    /**
     * @return int
     */
    public function getAutoGlobalAddingDescriptionTemplateId()
    {
        return (int)$this->getData('auto_global_adding_description_template_id');
    }

    /**
     * @return int
     */
    public function getAutoWebsiteAddingDescriptionTemplateId()
    {
        return (int)$this->getData('auto_website_adding_description_template_id');
    }

    //########################################

    /**
     * @return int
     */
    public function getSkuMode()
    {
        return (int)$this->getData('sku_mode');
    }

    /**
     * @return bool
     */
    public function isSkuProductIdMode()
    {
        return $this->getSkuMode() == self::SKU_MODE_PRODUCT_ID;
    }

    /**
     * @return bool
     */
    public function isSkuDefaultMode()
    {
        return $this->getSkuMode() == self::SKU_MODE_DEFAULT;
    }

    /**
     * @return bool
     */
    public function isSkuAttributeMode()
    {
        return $this->getSkuMode() == self::SKU_MODE_CUSTOM_ATTRIBUTE;
    }

    /**
     * @return array
     */
    public function getSkuSource()
    {
        return [
            'mode'      => $this->getSkuMode(),
            'attribute' => $this->getData('sku_custom_attribute')
        ];
    }

    // ---------------------------------------

    /**
     * @return int
     */
    public function getSkuModificationMode()
    {
        return (int)$this->getData('sku_modification_mode');
    }

    /**
     * @return bool
     */
    public function isSkuModificationModeNone()
    {
        return $this->getSkuModificationMode() == self::SKU_MODIFICATION_MODE_NONE;
    }

    /**
     * @return bool
     */
    public function isSkuModificationModePrefix()
    {
        return $this->getSkuModificationMode() == self::SKU_MODIFICATION_MODE_PREFIX;
    }

    /**
     * @return bool
     */
    public function isSkuModificationModePostfix()
    {
        return $this->getSkuModificationMode() == self::SKU_MODIFICATION_MODE_POSTFIX;
    }

    /**
     * @return bool
     */
    public function isSkuModificationModeTemplate()
    {
        return $this->getSkuModificationMode() == self::SKU_MODIFICATION_MODE_TEMPLATE;
    }

    /**
     * @return array
     */
    public function getSkuModificationSource()
    {
        return [
            'mode'  => $this->getSkuModificationMode(),
            'value' => $this->getData('sku_modification_custom_value')
        ];
    }

    // ---------------------------------------

    /**
     * @return int
     */
    public function getGenerateSkuMode()
    {
        return (int)$this->getData('generate_sku_mode');
    }

    /**
     * @return bool
     */
    public function isGenerateSkuModeNo()
    {
        return $this->getGenerateSkuMode() == self::GENERATE_SKU_MODE_NO;
    }

    /**
     * @return bool
     */
    public function isGenerateSkuModeYes()
    {
        return $this->getGenerateSkuMode() == self::GENERATE_SKU_MODE_YES;
    }

    // ---------------------------------------

    /**
     * @return int
     */
    public function getConditionMode()
    {
        return (int)$this->getData('condition_mode');
    }

    /**
     * @return bool
     */
    public function isConditionDefaultMode()
    {
        return $this->getConditionMode() == self::CONDITION_MODE_DEFAULT;
    }

    /**
     * @return bool
     */
    public function isConditionAttributeMode()
    {
        return $this->getConditionMode() == self::CONDITION_MODE_CUSTOM_ATTRIBUTE;
    }

    /**
     * @return array
     */
    public function getConditionSource()
    {
        return [
            'mode'      => $this->getConditionMode(),
            'value'     => $this->getData('condition_value'),
            'attribute' => $this->getData('condition_custom_attribute')
        ];
    }

    public function getConditionValues()
    {
        $temp = $this->getData('cache_condition_values');

        if (!empty($temp)) {
            return $temp;
        }

        $reflectionClass = new \ReflectionClass(__CLASS__);
        $tempConstants = $reflectionClass->getConstants();

        $values = [];
        foreach ($tempConstants as $key => $value) {
            $prefixKey = strtolower(substr($key, 0, 14));
            if (substr($prefixKey, 0, 10) != 'condition_' ||
                in_array($prefixKey, ['condition_mode', 'condition_note'])) {
                continue;
            }
            $values[] = $value;
        }

        $this->setData('cache_condition_values', $values);

        return $values;
    }

    // ---------------------------------------

    /**
     * @return int
     */
    public function getConditionNoteMode()
    {
        return (int)$this->getData('condition_note_mode');
    }

    /**
     * @return bool
     */
    public function isConditionNoteNoneMode()
    {
        return $this->getConditionNoteMode() == self::CONDITION_NOTE_MODE_NONE;
    }

    /**
     * @return bool
     */
    public function isConditionNoteValueMode()
    {
        return $this->getConditionNoteMode() == self::CONDITION_NOTE_MODE_CUSTOM_VALUE;
    }

    /**
     * @return array
     */
    public function getConditionNoteSource()
    {
        return [
            'mode'  => $this->getConditionNoteMode(),
            'value' => $this->getData('condition_note_value')
        ];
    }

    /**
     * @return array
     */
    public function getConditionNoteAttributes()
    {
        $attributes = [];
        $src = $this->getConditionNoteSource();

        if ($src['mode'] == self::CONDITION_NOTE_MODE_CUSTOM_VALUE) {
            $match = [];
            preg_match_all('/#([a-zA-Z_0-9]+?)#/', $src['value'], $match);
            $match && $attributes = $match[1];
        }

        return $attributes;
    }

    // ---------------------------------------

    /**
     * @return int
     */
    public function getImageMainMode()
    {
        return (int)$this->getData('image_main_mode');
    }

    /**
     * @return bool
     */
    public function isImageMainModeNone()
    {
        return $this->getImageMainMode() == self::IMAGE_MAIN_MODE_NONE;
    }

    /**
     * @return bool
     */
    public function isImageMainModeProduct()
    {
        return $this->getImageMainMode() == self::IMAGE_MAIN_MODE_PRODUCT;
    }

    /**
     * @return bool
     */
    public function isImageMainModeAttribute()
    {
        return $this->getImageMainMode() == self::IMAGE_MAIN_MODE_ATTRIBUTE;
    }

    /**
     * @return array
     */
    public function getImageMainSource()
    {
        return [
            'mode'      => $this->getImageMainMode(),
            'attribute' => $this->getData('image_main_attribute')
        ];
    }

    /**
     * @return array
     */
    public function getImageMainAttributes()
    {
        $attributes = [];
        $src = $this->getImageMainSource();

        if ($src['mode'] == self::IMAGE_MAIN_MODE_PRODUCT) {
            $attributes[] = 'image';
        } elseif ($src['mode'] == self::IMAGE_MAIN_MODE_ATTRIBUTE) {
            $attributes[] = $src['attribute'];
        }

        return $attributes;
    }

    // ---------------------------------------

    /**
     * @return int
     */
    public function getGalleryImagesMode()
    {
        return (int)$this->getData('gallery_images_mode');
    }

    /**
     * @return bool
     */
    public function isGalleryImagesModeNone()
    {
        return $this->getGalleryImagesMode() == self::GALLERY_IMAGES_MODE_NONE;
    }

    /**
     * @return bool
     */
    public function isGalleryImagesModeProduct()
    {
        return $this->getGalleryImagesMode() == self::GALLERY_IMAGES_MODE_PRODUCT;
    }

    /**
     * @return bool
     */
    public function isGalleryImagesModeAttribute()
    {
        return $this->getGalleryImagesMode() == self::GALLERY_IMAGES_MODE_ATTRIBUTE;
    }

    /**
     * @return array
     */
    public function getGalleryImagesSource()
    {
        return [
            'mode'      => $this->getGalleryImagesMode(),
            'limit'     => $this->getData('gallery_images_limit'),
            'attribute' => $this->getData('gallery_images_attribute')
        ];
    }

    /**
     * @return array
     */
    public function getGalleryImagesAttributes()
    {
        $attributes = [];
        $src = $this->getGalleryImagesSource();

        if ($src['mode'] == self::GALLERY_IMAGES_MODE_ATTRIBUTE) {
            $attributes[] = $src['attribute'];
        }

        return $attributes;
    }

    // ---------------------------------------

    /**
     * @return int
     */
    public function getHandlingTimeMode()
    {
        return (int)$this->getData('handling_time_mode');
    }

    /**
     * @return bool
     */
    public function isHandlingTimeNoneMode()
    {
        return $this->getHandlingTimeMode() == self::HANDLING_TIME_MODE_NONE;
    }

    /**
     * @return bool
     */
    public function isHandlingTimeRecommendedMode()
    {
        return $this->getHandlingTimeMode() == self::HANDLING_TIME_MODE_RECOMMENDED;
    }

    /**
     * @return bool
     */
    public function isHandlingTimeAttributeMode()
    {
        return $this->getHandlingTimeMode() == self::HANDLING_TIME_MODE_CUSTOM_ATTRIBUTE;
    }

    /**
     * @return array
     */
    public function getHandlingTimeSource()
    {
        return [
            'mode'      => $this->getHandlingTimeMode(),
            'value'     => (int)$this->getData('handling_time_value'),
            'attribute' => $this->getData('handling_time_custom_attribute')
        ];
    }

    /**
     * @return array
     */
    public function getHandlingTimeAttributes()
    {
        $attributes = [];
        $src = $this->getHandlingTimeSource();

        if ($src['mode'] == self::HANDLING_TIME_MODE_CUSTOM_ATTRIBUTE) {
            $attributes[] = $src['attribute'];
        }

        return $attributes;
    }

    // ---------------------------------------

    /**
     * @return int
     */
    public function getRestockDateMode()
    {
        return (int)$this->getData('restock_date_mode');
    }

    /**
     * @return bool
     */
    public function isRestockDateNoneMode()
    {
        return $this->getRestockDateMode() == self::RESTOCK_DATE_MODE_NONE;
    }

    /**
     * @return bool
     */
    public function isRestockDateValueMode()
    {
        return $this->getRestockDateMode() == self::RESTOCK_DATE_MODE_CUSTOM_VALUE;
    }

    /**
     * @return bool
     */
    public function isRestockDateAttributeMode()
    {
        return $this->getRestockDateMode() == self::RESTOCK_DATE_MODE_CUSTOM_ATTRIBUTE;
    }

    /**
     * @return array
     */
    public function getRestockDateSource()
    {
        return [
            'mode'      => $this->getRestockDateMode(),
            'value'     => $this->getData('restock_date_value'),
            'attribute' => $this->getData('restock_date_custom_attribute')
        ];
    }

    /**
     * @return array
     */
    public function getRestockDateAttributes()
    {
        $attributes = [];
        $src = $this->getRestockDateSource();

        if ($src['mode'] == self::RESTOCK_DATE_MODE_CUSTOM_ATTRIBUTE) {
            $attributes[] = $src['attribute'];
        }

        return $attributes;
    }

    // ---------------------------------------

    public function getGiftWrapMode()
    {
        return $this->getData('gift_wrap_mode');
    }

    /**
     * @return bool
     */
    public function isGiftWrapModeYes()
    {
        return $this->getGiftWrapMode() == self::GIFT_WRAP_MODE_YES;
    }

    /**
     * @return bool
     */
    public function isGiftWrapModeNo()
    {
        return $this->getGiftWrapMode() == self::GIFT_WRAP_MODE_NO;
    }

    /**
     * @return bool
     */
    public function isGiftWrapModeAttribute()
    {
        return $this->getGiftWrapMode() == self::GIFT_WRAP_MODE_ATTRIBUTE;
    }

    /**
     * @return array
     */
    public function getGiftWrapSource()
    {
        return [
            'mode'      => $this->getGiftWrapMode(),
            'attribute' => $this->getData('gift_wrap_attribute')
        ];
    }

    /**
     * @return array
     */
    public function getGiftWrapAttributes()
    {
        $attributes = [];
        $src = $this->getGiftWrapSource();

        if ($src['mode'] == self::GIFT_WRAP_MODE_ATTRIBUTE) {
            $attributes[] = $src['attribute'];
        }

        return $attributes;
    }

    // ---------------------------------------

    public function getGiftMessageMode()
    {
        return $this->getData('gift_message_mode');
    }

    /**
     * @return bool
     */
    public function isGiftMessageModeYes()
    {
        return $this->getGiftMessageMode() == self::GIFT_MESSAGE_MODE_YES;
    }

    /**
     * @return bool
     */
    public function isGiftMessageModeNo()
    {
        return $this->getGiftMessageMode() == self::GIFT_MESSAGE_MODE_NO;
    }

    /**
     * @return bool
     */
    public function isGiftMessageModeAttribute()
    {
        return $this->getGiftMessageMode() == self::GIFT_MESSAGE_MODE_ATTRIBUTE;
    }

    /**
     * @return array
     */
    public function getGiftMessageSource()
    {
        return [
            'mode'      => $this->getGiftMessageMode(),
            'attribute' => $this->getData('gift_message_attribute')
        ];
    }

    /**
     * @return array
     */
    public function getGiftMessageAttributes()
    {
        $attributes = [];
        $src = $this->getGiftMessageSource();

        if ($src['mode'] == self::GIFT_MESSAGE_MODE_ATTRIBUTE) {
            $attributes[] = $src['attribute'];
        }

        return $attributes;
    }

    /**
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getAddedListingProductsIds()
    {
        $ids = $this->getData('product_add_ids');
        $ids = array_filter((array)$this->getHelper('Data')->jsonDecode($ids));

        return array_values(array_unique($ids));
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

        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
        $amazonListingProduct = $listingProduct->getChildObject();
        $variationManager = $amazonListingProduct->getVariationManager();

        if ($variationManager->isRelationParentType()) {
            $variationManager->switchModeToAnother();
        }

        /** @var \Ess\M2ePro\Model\Amazon\Listing\Other $amazonListingOther */
        $amazonListingOther = $listingOtherProduct->getChildObject();

        $dataForUpdate = [
            'general_id'           => $amazonListingOther->getGeneralId(),
            'sku'                  => $amazonListingOther->getSku(),
            'online_regular_price' => $amazonListingOther->getOnlinePrice(),
            'online_qty'           => $amazonListingOther->getOnlineQty(),
            'is_repricing'         => (int)$amazonListingOther->isRepricing(),
            'is_afn_channel'       => (int)$amazonListingOther->isAfnChannel(),
            'is_isbn_general_id'   => (int)$amazonListingOther->isIsbnGeneralId(),
            'status'               => $listingOtherProduct->getStatus(),
            'status_changer'       => $listingOtherProduct->getStatusChanger()
        ];

        $listingProduct->addData($dataForUpdate);
        $amazonListingProduct->addData($dataForUpdate);

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

        $listingProduct->save();

        $listingOtherProduct->setSetting(
            'additional_data',
            $listingOtherProduct::MOVING_LISTING_PRODUCT_DESTINATION_KEY,
            $listingProduct->getId()
        );

        $listingOtherProduct->save();

        $amazonItem = $amazonListingProduct->getAmazonItem();
        if ($listingProduct->getMagentoProduct()->isGroupedType() &&
            $this->moduleConfiguration->isGroupedProductModeSet()
        ) {
            $amazonItem->setAdditionalData(json_encode(['grouped_product_mode' => 1]));
        }

        $amazonItem->setData('store_id', $this->getParentObject()->getStoreId());
        $amazonItem ->save();

        if ($amazonListingOther->isRepricing()) {
            $listingProductRepricing = $this->activeRecordFactory->getObject('Amazon_Listing_Product_Repricing');
            $listingProductRepricing->setData(
                [
                    'listing_product_id' => $listingProduct->getId(),
                    'is_online_disabled' => $amazonListingOther->isRepricingDisabled(),
                    'update_date'        => $this->getHelper('Data')->getCurrentGmtDate(),
                    'create_date'        => $this->getHelper('Data')->getCurrentGmtDate(),
                ]
            );
            $listingProductRepricing->save();
        }

        $instruction = $this->activeRecordFactory->getObject('Listing_Product_Instruction');
        $instruction->setData(
            [
                'listing_product_id' => $listingProduct->getId(),
                'component'          => \Ess\M2ePro\Helper\Component\Amazon::NICK,
                'type'               => \Ess\M2ePro\Model\Listing::INSTRUCTION_TYPE_PRODUCT_MOVED_FROM_OTHER,
                'initiator'          => \Ess\M2ePro\Model\Listing::INSTRUCTION_INITIATOR_MOVING_PRODUCT_FROM_OTHER,
                'priority'           => 20,
            ]
        );
        $instruction->save();

        return $listingProduct;
    }

    public function addProductFromAnotherAmazonSite(
        \Ess\M2ePro\Model\Listing\Product $sourceListingProduct,
        \Ess\M2ePro\Model\Listing $sourceListing
    ) {
        /** @var \Ess\M2ePro\Model\Listing\Product $listingProduct */
        $listingProduct = $this->getParentObject()->addProduct(
            $sourceListingProduct->getProductId(),
            \Ess\M2ePro\Helper\Data::INITIATOR_USER
        );

        /** @var \Ess\M2ePro\Model\Listing\Log $logModel */
        $logModel = $this->activeRecordFactory->getObject('Listing_Log');
        $logModel->setComponentMode($this->getComponentMode());

        $logMessage = $this->getHelper('Module\Translation')->__(
            'Product was copied from %previous_listing_name% (%previous_marketplace%)
            Listing to %current_listing_name% (%current_marketplace%) Listing.',
            $sourceListing->getTitle(),
            $sourceListing->getMarketplace()->getCode(),
            $this->getParentObject()->getTitle(),
            $this->getMarketplace()->getCode()
        );

        if ($listingProduct instanceof \Ess\M2ePro\Model\Listing\Product) {
            $logModel->addProductMessage(
                $sourceListing->getId(),
                $sourceListingProduct->getProductId(),
                $sourceListingProduct->getId(),
                \Ess\M2ePro\Helper\Data::INITIATOR_USER,
                $logModel->getResource()->getNextActionId(),
                \Ess\M2ePro\Model\Listing\Log::ACTION_SELL_ON_ANOTHER_SITE,
                $logMessage,
                \Ess\M2ePro\Model\Log\AbstractModel::TYPE_INFO
            );

            if ($sourceListing->getMarketplaceId() == $this->getParentObject()->getMarketplaceId()) {
                $listingProduct->getChildObject()->setData(
                    'template_description_id',
                    $sourceListingProduct->getChildObject()->getTemplateDescriptionId()
                );
                $listingProduct->getChildObject()->setData(
                    'template_shipping_id',
                    $sourceListingProduct->getChildObject()->getTemplateShippingId()
                );
                $listingProduct->getChildObject()->setData(
                    'template_product_tax_code_id',
                    $sourceListingProduct->getChildObject()->getTemplateProductTaxCodeId()
                );
            }

            // @codingStandardsIgnoreLine
            $listingProduct->getChildObject()->save();

            return $listingProduct;
        }

        $logMessage = $this->getHelper('Module\Translation')->__(
            'Product already exists in the %listing_name% Listing.',
            $this->getParentObject()->getTitle()
        );

        $logModel->addProductMessage(
            $sourceListing->getId(),
            $sourceListingProduct->getProductId(),
            $sourceListingProduct->getId(),
            \Ess\M2ePro\Helper\Data::INITIATOR_USER,
            $logModel->getResource()->getNextActionId(),
            \Ess\M2ePro\Model\Listing\Log::ACTION_SELL_ON_ANOTHER_SITE,
            $logMessage,
            \Ess\M2ePro\Model\Log\AbstractModel::TYPE_ERROR
        );

        return false;
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
                if ($item = $listingProduct->getChildObject()->getAmazonItem()) {
                    $item->setData('store_id', $this->getParentObject()->getStoreId());
                    $item->save();
                }
            }
        }

        /** @var \Ess\M2ePro\Model\Amazon\Listing\Product $amazonListingProduct */
        $amazonListingProduct = $listingProduct->getChildObject();
        $variationManager = $amazonListingProduct->getVariationManager();

        if ($variationManager->isRelationParentType()) {
            /** @var \Ess\M2ePro\Model\ResourceModel\Amazon\Listing\Product $resourceModel */
            $resourceModel = $this->activeRecordFactory->getObject('Amazon_Listing_Product')->getResource();
            $resourceModel->moveChildrenToListing($listingProduct);
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
