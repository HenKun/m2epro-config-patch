<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\Magento;

class Attribute extends AbstractHelper
{
    public const PRICE_CODE = 'price';
    public const SPECIAL_PRICE_CODE = 'special_price';

    /** @var \Ess\M2ePro\Model\Factory */
    private $modelFactory;

    /** @var \Magento\Catalog\Model\ResourceModel\Product */
    private $productResource;

    /** @var \Magento\Framework\App\ResourceConnection */
    private $resourceConnection;

    /** @var \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory */
    private $attributeColFactory;

    /** @var \Magento\Eav\Model\ResourceModel\Entity\Attribute\CollectionFactory */
    private $eavEntityAttributeColFactory;

    /** @var \Magento\Eav\Model\Config */
    private $eavConfig;

    /** @var \Ess\M2ePro\Helper\Module\Configuration */
    private $moduleConfiguration;

    /** @var \Ess\M2ePro\Helper\Magento\AttributeSet */
    private $magentoAttributeSetHelper;

    /** @var \Ess\M2ePro\Helper\Magento */
    private $magentoHelper;

    /** @var \Ess\M2ePro\Helper\Module\Database\Structure */
    private $dbStructureHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Module\Configuration $moduleConfiguration,
        \Ess\M2ePro\Helper\Magento\AttributeSet $magentoAttributeSetHelper,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Helper\Magento $magentoHelper,
        \Ess\M2ePro\Helper\Module\Database\Structure $dbStructureHelper,
        \Magento\Catalog\Model\ResourceModel\Product $productResource,
        \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory $attributeColFactory,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute\CollectionFactory $eavEntityAttributeColFactory,
        \Magento\Eav\Model\Config $eavConfig,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        parent::__construct($objectManager);
        $this->modelFactory = $modelFactory;
        $this->productResource = $productResource;
        $this->attributeColFactory = $attributeColFactory;
        $this->eavEntityAttributeColFactory = $eavEntityAttributeColFactory;
        $this->eavConfig = $eavConfig;
        $this->resourceConnection = $resourceConnection;
        $this->moduleConfiguration = $moduleConfiguration;
        $this->magentoAttributeSetHelper = $magentoAttributeSetHelper;
        $this->magentoHelper = $magentoHelper;
        $this->dbStructureHelper = $dbStructureHelper;
    }

    public function getAll()
    {
        $attributeCollection = $this->getPreparedAttributeCollection();
        $attributeCollection->addVisibleFilter();

        $resultAttributes = [];
        foreach ($attributeCollection->getItems() as $attribute) {
            $resultAttributes[] = [
                'code' => $attribute['attribute_code'],
                'label' => $attribute['frontend_label']
            ];
        }

        return $resultAttributes;
    }

    public function getAllAsObjects()
    {
        $attributes = $this->getPreparedAttributeCollection()
            ->addVisibleFilter()
            ->getItems();

        return $attributes;
    }

    // ---------------------------------------

    public function getByCode($code, $returnType = self::RETURN_TYPE_ARRAYS)
    {
        $attributeCollection = $this->getPreparedAttributeCollection()
            ->addVisibleFilter()
            ->setCodeFilter($code);

        $attributes = $this->_convertCollectionToReturnType($attributeCollection, $returnType);
        if ($returnType != self::RETURN_TYPE_ARRAYS) {
            return $attributes;
        }

        $resultAttributes = [];
        foreach ($attributeCollection->getItems() as $attribute) {
            $resultAttributes[] = [
                'code' => $attribute['attribute_code'],
                'label' => $attribute['frontend_label']
            ];
        }

        return $resultAttributes;
    }

    // ---------------------------------------

    public function getByAttributeSet($attributeSet, $returnType = self::RETURN_TYPE_ARRAYS)
    {
        $attributeSetId = $this->_getIdFromInput($attributeSet);
        if ($attributeSetId === false) {
            return [];
        }

        return $this->getByAttributeSets([$attributeSetId], $returnType);
    }

    public function getByAttributeSets(array $attributeSets, $returnType = self::RETURN_TYPE_ARRAYS)
    {
        $attributeSetIds = $this->_getIdsFromInput($attributeSets, 'attribute_set_id');
        if (empty($attributeSetIds)) {
            return [];
        }

        $attributeCollection = $this->getPreparedAttributeCollection()
            ->addVisibleFilter()
            ->setAttributeSetFilter($attributeSetIds);

        $attributeCollection->getSelect()->group('entity_attribute.attribute_id');

        $attributes = $this->_convertCollectionToReturnType($attributeCollection, $returnType);
        if ($returnType != self::RETURN_TYPE_ARRAYS) {
            return $attributes;
        }

        $resultAttributes = [];
        foreach ($attributes as $attribute) {
            $resultAttributes[] = [
                'code' => $attribute['attribute_code'],
                'label' => $attribute['frontend_label']
            ];
        }

        return $resultAttributes;
    }

    //########################################

    public function getGeneralFromAttributeSets(array $attributeSets)
    {
        $attributeSetIds = $this->_getIdsFromInput($attributeSets, 'attribute_set_id');
        if (empty($attributeSetIds)) {
            return [];
        }

        $attributes = [];
        $isFirst = true;
        $idsParts = array_chunk($attributeSetIds, 50);
        foreach ($idsParts as $part) {
            $tempAttributes = $this->_getGeneralFromAttributeSets($part);

            if ($isFirst) {
                $attributes = $tempAttributes;
                $isFirst = false;

                continue;
            }

            if (!$isFirst && empty($attributes)) {
                return [];
            }

            $attributes = array_intersect($attributes, $tempAttributes);
        }

        if (empty($attributes)) {
            return [];
        }

        $attributesData = $this->getPreparedAttributeCollection()
            ->addVisibleFilter()
            ->addFieldToFilter('main_table.attribute_id', ['in' => $attributes])
            ->toArray();

        $resultAttributes = [];
        foreach ($attributesData['items'] as $attribute) {
            $resultAttributes[] = [
                'code' => $attribute['attribute_code'],
                'label' => $attribute['frontend_label'],
            ];
        }

        return $resultAttributes;
    }

    public function getGeneralFromAllAttributeSets()
    {
        $allAttributeSets = $this->magentoAttributeSetHelper->getAll(self::RETURN_TYPE_IDS);
        return $this->getGeneralFromAttributeSets($allAttributeSets);
    }

    // ---------------------------------------

    private function _getGeneralFromAttributeSets(array $attributeSetIds)
    {
        if (count($attributeSetIds) > 50) {
            throw new \Ess\M2ePro\Model\Exception("Attribute sets must be less then 50");
        }

        $attributeCollection = $this->getPreparedAttributeCollection()
            ->addVisibleFilter()
            ->setInAllAttributeSetsFilter($attributeSetIds);

        /**
         * We can't use $attributeCollection->getAllIds().
         * It will reset all columns and having clause will causes mysql syntax error
         */
        $idsSelect = clone $attributeCollection->getSelect();
        $idsSelect->reset(\Magento\Framework\DB\Select::ORDER);
        $idsSelect->reset(\Magento\Framework\DB\Select::LIMIT_COUNT);
        $idsSelect->reset(\Magento\Framework\DB\Select::LIMIT_OFFSET);
        $idsSelect->reset(\Magento\Framework\DB\Select::COLUMNS);

        $idsSelect->columns($attributeCollection->getResource()->getIdFieldName(), 'main_table');

        /**
         * Magento >= 2.2.4 uses HAVING on COUNT so we need to add COUNT after resetting columns
         */
        if (version_compare($this->magentoHelper->getVersion(), '2.2.4', '>=')) {
            $idsSelect->columns(
                ['count' => new \Zend_Db_Expr('COUNT(*)')],
                'main_table'
            );
        }

        //todo uncomment when issue wil be fixed
        //return $attributeCollection->getAllIds();

        return $attributeCollection->getConnection()->fetchCol($idsSelect);
    }

    // ---------------------------------------

    public function getGeneralFromProducts(array $products)
    {
        $productsAttributeSetIds = $this->magentoAttributeSetHelper->getFromProducts(
            $products,
            self::RETURN_TYPE_IDS
        );

        return $this->getGeneralFromAttributeSets($productsAttributeSetIds);
    }

    //########################################

    public function getConfigurableByAttributeSets(array $attributeSets)
    {
        if (empty($attributeSets)) {
            return [];
        }

        return $this->getConfigurable($attributeSets);
    }

    public function getAllConfigurable()
    {
        return $this->getConfigurable();
    }

    // ---------------------------------------

    private function getConfigurable(array $attributeSetIds = [])
    {
        $connection = $this->resourceConnection->getConnection();

        $cpTable  = $this->dbStructureHelper->getTableNameWithPrefix('catalog_product_entity');
        $saTable  = $this->dbStructureHelper
            ->getTableNameWithPrefix('catalog_product_super_attribute');
        $aTable   = $this->dbStructureHelper->getTableNameWithPrefix('eav_attribute');

        $select = $connection->select()
            ->distinct(true)
            ->from(['p' => $cpTable], null)
            ->join(
                ['sa' => $saTable],
                'p.entity_id = sa.product_id',
                null
            )
            ->join(
                ['a' => $aTable],
                'sa.attribute_id = a.attribute_id',
                ['label' => 'frontend_label', 'code' => 'attribute_code']
            )
            ->where('p.type_id = ?', \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE);

        if (!empty($attributeSetIds)) {
            $select->where('e.attribute_set_id IN ?', $attributeSetIds);
        }

        return $connection->fetchAll($select);
    }

    //########################################

    public function getAttributeLabel($attributeCode, $storeId = \Magento\Store\Model\Store::DEFAULT_STORE_ID)
    {
        /** @var \Magento\Eav\Model\Entity\Attribute\AbstractAttribute $attribute */
        $attribute = $this->productResource->getAttribute($attributeCode);

        if (!$attribute) {
            return $attributeCode;
        }

        $label = $attribute->getStoreLabel($storeId);
        $label == '' && $label = $attribute->getFrontendLabel();

        return $label == '' ? $attributeCode : $label;
    }

    public function getAttributesLabels(array $attributeCodes)
    {
        if (empty($attributeCodes)) {
            return [];
        }

        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->dbStructureHelper->getTableNameWithPrefix('eav_attribute');

        $entityTypeId = $this->eavConfig->getEntityType(\Magento\Catalog\Model\Product::ENTITY)->getId();
        $dbSelect = $connection->select();
        $dbSelect->from($tableName)
            ->where('attribute_code in (\''.implode('\',\'', $attributeCodes).'\')')
            ->where('entity_type_id = ?', $entityTypeId);
        $fetchResult = $connection->fetchAll($dbSelect);

        $result = [];
        foreach ($fetchResult as $attribute) {
            $result[] = [
                'label' => $attribute['frontend_label'],
                'code'  => $attribute['attribute_code']
            ];
        }

        return $result;
    }

    public function isExistInAttributesArray($attributeCode, array $attributes)
    {
        if ($attributeCode == '') {
            return false;
        }

        foreach ($attributes as $attribute) {
            if ($attribute['code'] == $attributeCode) {
                return true;
            }
        }
        return false;
    }

    public function filterByInputTypes(
        array $attributes,
        array $frontendInputTypes = [],
        array $backendInputTypes = []
    ) {
        if (empty($attributes)) {
            return [];
        }

        if (empty($frontendInputTypes) && empty($backendInputTypes)) {
            return $attributes;
        }

        $attributeCodes = [];
        foreach ($attributes as $attribute) {
            $attributeCodes[] = $attribute['code'];
        }

        $attributeCollection = $this->getPreparedAttributeCollection()
            ->addFieldToFilter('attribute_code', ['in' => $attributeCodes]);

        if (!empty($frontendInputTypes)) {
            $attributeCollection->addFieldToFilter('frontend_input', ['in' => $frontendInputTypes]);
        }
        if (!empty($backendInputTypes)) {
            $attributeCollection->addFieldToFilter('backend_type', ['in' => $backendInputTypes]);
        }

        $filteredAttributes = $attributeCollection->toArray();
        $resultAttributes = [];
        foreach ($filteredAttributes['items'] as $attribute) {
            $resultAttributes[] = [
                'code' => $attribute['attribute_code'],
                'label' => $attribute['frontend_label'],
            ];
        }

        return $resultAttributes;
    }

    public function filterAllAttrByInputTypes(
        array $frontendInputTypes = [],
        array $backendInputTypes = []
    ) {
        return $this->filterByInputTypes(
            $this->getAll(),
            $frontendInputTypes,
            $backendInputTypes
        );
    }

    //########################################

    public function getSetsFromProductsWhichLacksAttributes(array $attributes, array $productIds)
    {
        if (count($attributes) == 0 || count($productIds) == 0) {
            return [];
        }

        $scopeAttributesOptionArray = $this->getGeneralFromProducts($productIds);
        $scopeAttributes = [];
        foreach ($scopeAttributesOptionArray as $scopeAttributesOption) {
            $scopeAttributes[] = $scopeAttributesOption['code'];
        }

        $missingAttributes = array_diff($attributes, $scopeAttributes);

        if (count($missingAttributes) == 0) {
            return [];
        }

        $attributesCollection = $this->eavEntityAttributeColFactory->create()
            ->setEntityTypeFilter($this->productResource->getTypeId())
            ->addFieldToFilter('attribute_code', ['in' => $missingAttributes])
            ->addSetInfo(true);

        $attributeSets = $this->magentoAttributeSetHelper
            ->getFromProducts(
                $productIds,
                \Ess\M2ePro\Helper\Magento\AbstractHelper::RETURN_TYPE_IDS
            );

        $missingAttributesSets = [];

        foreach ($attributesCollection->getItems() as $attribute) {
            foreach ($attributeSets as $setId) {
                if (!$attribute->isInSet($setId)) {
                    $missingAttributesSets[] = $setId;
                }
            }
        }

        return array_unique($missingAttributesSets);
    }

    //########################################

    /**
     * Now Magento returns strange combined QTY and StockStatus Attribute. This attribute will not work for
     * Tracking of Attributes and we will skip it.
     *
     * @return \Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection
     */
    private function getPreparedAttributeCollection()
    {
        $collection = $this->attributeColFactory->create();
        $collection->addFieldToFilter('attribute_code', ['neq' => 'quantity_and_stock_status']);
        $collection->setOrder('frontend_label', \Magento\Framework\Data\Collection::SORT_ORDER_ASC);

        return $collection;
    }

    //########################################

    public function isAttributeInputTypePrice($attributeCode)
    {
        $attributes = $this->filterByInputTypes(
            [['code' => $attributeCode]],
            ['price']
        );

        return !empty($attributes);
    }

    public function convertAttributeTypePriceFromStoreToMarketplace(
        \Ess\M2ePro\Model\Magento\Product $magentoProduct,
        $attributeCode,
        $currencyCode,
        $store
    ) {
        $attributeValue = $magentoProduct->getAttributeValue($attributeCode);

        if (empty($attributeValue)) {
            return $attributeValue;
        }

        $isPriceConvertEnabled = $this->moduleConfiguration->isEnableMagentoAttributePriceTypeConvertingMode();

        if ($isPriceConvertEnabled && $this->isAttributeInputTypePrice($attributeCode)) {
            $attributeValue = $this->modelFactory->getObject('Currency')->convertPrice(
                $attributeValue,
                $currencyCode,
                $store
            );
        }

        return $attributeValue;
    }

    //########################################
}
