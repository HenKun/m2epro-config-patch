<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product\Add\CategoryTemplate\Category;

class Grid extends \Ess\M2ePro\Block\Adminhtml\Category\Grid
{
    /** @var \Ess\M2ePro\Model\Listing */
    protected $listing = null;

    /** @var \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory  */
    protected $walmartFactory;

    /** @var \Magento\Framework\App\ResourceConnection  */
    protected $resourceConnection;

    /** @var \Ess\M2ePro\Model\ResourceModel\Magento\Product\CollectionFactory  */
    protected $magentoProductCollectionFactory;

    /** @var \Ess\M2ePro\Helper\Magento\Category */
    protected $magentoCategoryHelper;

    /** @var \Ess\M2ePro\Helper\Module\Database\Structure */
    private $databaseHelper;

    /** @var \Ess\M2ePro\Helper\Data\GlobalData */
    private $globalDataHelper;

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Model\ResourceModel\Magento\Product\CollectionFactory $magentoProductCollectionFactory,
        \Ess\M2ePro\Helper\Magento\Category $magentoCategoryHelper,
        \Ess\M2ePro\Model\ResourceModel\Magento\Category\CollectionFactory $categoryCollectionFactory,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Ess\M2ePro\Helper\Data $dataHelper,
        \Ess\M2ePro\Helper\Module\Database\Structure $databaseHelper,
        \Ess\M2ePro\Helper\Data\GlobalData $globalDataHelper,
        array $data = []
    ) {
        $this->walmartFactory = $walmartFactory;
        $this->resourceConnection = $resourceConnection;
        $this->magentoProductCollectionFactory = $magentoProductCollectionFactory;
        $this->magentoCategoryHelper = $magentoCategoryHelper;
        $this->databaseHelper = $databaseHelper;
        $this->globalDataHelper = $globalDataHelper;
        parent::__construct(
            $categoryCollectionFactory,
            $context,
            $backendHelper,
            $dataHelper,
            $data
        );
    }

    public function _construct()
    {
        parent::_construct();

        $this->listing = $this->globalDataHelper->getValue('listing_for_products_add');

        // Initialization block
        // ---------------------------------------
        $this->setId('newAsinCategoryGrid');
        // ---------------------------------------

        // Set default values
        // ---------------------------------------
        $this->setDefaultSort('id');
        $this->setDefaultDir('DESC');
        $this->setUseAjax(true);
        // ---------------------------------------

        $this->prepareDataByCategories();
    }

    protected function _prepareCollection()
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Magento\Category\Collection $collection */
        $collection = $this->categoryCollectionFactory->create();
        $collection->addAttributeToSelect('name');

        $collection->addFieldToFilter([
            ['attribute' => 'entity_id', 'in' => array_keys($this->getData('categories_data'))]
        ]);

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('magento_category', [
            'header'    => $this->__('Magento Category'),
            'align'     => 'left',
            'width'     => '500px',
            'type'      => 'text',
            'index'     => 'name',
            'filter'    => false,
            'sortable'  => false,
            'frame_callback' => [$this, 'callbackColumnMagentoCategory']
        ]);

        $this->addColumn('category_template', [
            'header'    => $this->__('Category Policy'),
            'align'     => 'left',
            'width'     => '*',
            'sortable'  => false,
            'type'      => 'options',
            'index'     => 'category_template_id',
            'filter_index' => 'category_template_id',
            'options'   => [
                1 => $this->__('Category Policy Selected'),
                0 => $this->__('Category Policy Not Selected')
            ],
            'frame_callback' => [$this, 'callbackColumnCategoryTemplateCallback'],
            'filter_condition_callback' => [$this, 'callbackColumnCategoryTemplateFilterCallback']
        ]);

        $actionsColumn = [
            'header'    => $this->__('Actions'),
            'renderer'  => \Ess\M2ePro\Block\Adminhtml\Magento\Grid\Column\Renderer\Action::class,
            'no_link' => true,
            'align'     => 'center',
            'width'     => '130px',
            'type'      => 'text',
            'sortable'  => false,
            'filter'    => false,
            'actions'   => []
        ];

        $actions = [
            [
                'caption' => $this->__('Set Category Policy'),
                'field'   => 'entity_id',
                'onclick_action' => 'ListingGridObj.setCategoryTemplateByCategoryRowAction'
            ]
        ];

        $actionsColumn['actions'] = $actions;

        $this->addColumn('actions', $actionsColumn);

        return parent::_prepareColumns();
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('entity_id');
        $this->setMassactionIdFieldOnlyIndexValue(true);

        // ---------------------------------------
        $this->getMassactionBlock()->addItem('setCategoryTemplateByCategory', [
            'label' => $this->__('Set Category Policy'),
            'url'   => ''
        ]);
        // ---------------------------------------

        return parent::_prepareMassaction();
    }

    public function callbackColumnCategoryTemplateCallback($value, $row, $column, $isExport)
    {
        $categoriesData = $this->getData('categories_data');
        $productsIds = implode(',', $categoriesData[$row->getData('entity_id')]);

        $categoryTemplatesData = $this->getData('category_templates_data');
        $categoryTemplatesIds = [];
        foreach ($categoriesData[$row->getData('entity_id')] as $productId) {
            if (empty($categoryTemplatesIds[$categoryTemplatesData[$productId]])) {
                $categoryTemplatesIds[$categoryTemplatesData[$productId]] = 0;
            }
            $categoryTemplatesIds[$categoryTemplatesData[$productId]]++;
        }

        arsort($categoryTemplatesIds);

        reset($categoryTemplatesIds);
        $categoryTemplateId = key($categoryTemplatesIds);

        if (empty($categoryTemplateId)) {
            $label = $this->__('Not Selected');

            return <<<HTML
<span class="icon-warning" style="color: gray; font-style: italic;">{$label}</span>
<input type="hidden" id="products_ids_{$row->getData('entity_id')}" value="{$productsIds}">
HTML;
        }

        $templateCategoryEditUrl = $this->getUrl('*/walmart_template_category/edit', [
            'id' => $categoryTemplateId
        ]);

        /** @var \Ess\M2ePro\Model\Walmart\Template\Category $categoryTemplate */
        $categoryTemplate = $this->activeRecordFactory->getObjectLoaded(
            'Walmart_Template_Category',
            $categoryTemplateId
        );

        $title = $this->dataHelper->escapeHtml($categoryTemplate->getData('title'));

        return <<<HTML
<a target="_blank" href="{$templateCategoryEditUrl}">{$title}</a>
<input type="hidden" id="products_ids_{$row->getData('entity_id')}" value="{$productsIds}">
HTML;
    }

    protected function callbackColumnCategoryTemplateFilterCallback($collection, $column)
    {
        $value = $column->getFilter()->getValue();

        if ($value == null) {
            return;
        }

        $filteredProductsCategories = [];
        $filteredListingProductsIds = [];

        $categoriesData = $this->getData('categories_data');
        $categoryTemplatesIds = $this->getData('category_templates_data');

        foreach ($categoryTemplatesIds as $listingProductId => $categoryTemplateId) {
            if ($categoryTemplateId !== null) {
                $filteredListingProductsIds[] = $listingProductId;
            }
        }

        foreach ($categoriesData as $categoryId => $listingProducts) {
            foreach ($filteredListingProductsIds as $listingProductId) {
                if (in_array($listingProductId, $listingProducts)) {
                    $filteredProductsCategories[] = $categoryId;
                }
            }
        }

        $filteredProductsCategories = array_unique($filteredProductsCategories);

        if ($value) {
            $collection->addFieldToFilter('entity_id', ['in' => $filteredProductsCategories]);
        } elseif (!empty($filteredProductsCategories)) {
            $collection->addFieldToFilter('entity_id', ['nin' => $filteredProductsCategories]);
        }
    }

    public function getRowUrl($row)
    {
        return false;
    }

    protected function _toHtml()
    {
            $errorMessage = $this
                ->__(
                    "To proceed, the category data must be specified.
                                Please select a relevant Category Policy for at least one Magento Category."
                );
            $isNotExistProductsWithCategoryTemplate = (int)$this->isNotExistProductsWithCategoryTemplate(
                $this->getData('category_templates_data')
            );

            $this->js->add(
                <<<JS
    require([
        'M2ePro/Plugin/Messages'
    ],function(MessageObj) {
        var button = $('add_products_category_template_category_continue');
        if ({$isNotExistProductsWithCategoryTemplate}) {
            button.addClassName('disabled');
            button.disable();
            MessageObj.addError(`{$errorMessage}`);
        } else {
            button.removeClassName('disabled');
            button.enable();
            MessageObj.clear();
        }
    });
JS
            );

        if ($this->getRequest()->isXmlHttpRequest()) {
            $this->js->add(
                <<<JS
    ListingGridObj.afterInitPage();
JS
            );
        }

        $this->css->add('.grid-listing-column-actions { width:100px; }');

        return parent::_toHtml();
    }

    private function prepareDataByCategories()
    {
        $listingProductsIds = $this->listing->getSetting('additional_data', 'adding_listing_products_ids');

        $listingProductCollection = $this->walmartFactory->getObject('Listing\Product')->getCollection()
            ->addFieldToFilter('id', ['in' => $listingProductsIds]);

        $productsIds = [];
        $categoryTemplatesIds = [];
        foreach ($listingProductCollection->getData() as $item) {
            $productsIds[$item['id']] = $item['product_id'];
            $categoryTemplatesIds[$item['id']] = $item['template_category_id'];
        }
        $productsIds = array_unique($productsIds);

        $categoriesIds = $this->magentoCategoryHelper->getLimitedCategoriesByProducts(
            $productsIds,
            $this->listing->getStoreId()
        );

        $categoriesData = [];

        foreach ($categoriesIds as $categoryId) {
            /** @var \Ess\M2ePro\Model\ResourceModel\Magento\Product\Collection $collection */
            $collection = $this->magentoProductCollectionFactory->create();
            $collection->setListing($this->listing);
            $collection->setStoreId($this->listing->getStoreId());
            $collection->addFieldToFilter('entity_id', ['in' => $productsIds]);

            $collection->joinTable(
                [
                    'ccp' => $this->databaseHelper
                        ->getTableNameWithPrefix('catalog_category_product')
                ],
                'product_id=entity_id',
                ['category_id' => 'category_id']
            );
            $collection->addFieldToFilter('category_id', $categoryId);

            $data = $collection->getData();

            foreach ($data as $item) {
                $categoriesData[$categoryId][] = array_search($item['entity_id'], $productsIds);
            }

            $categoriesData[$categoryId] = array_unique($categoriesData[$categoryId]);
        }

        $this->setData('categories_data', $categoriesData);
        $this->setData('category_templates_data', $categoryTemplatesIds);

        $this->listing->setSetting(
            'additional_data',
            'adding_new_asin_category_templates_data',
            $categoryTemplatesIds
        );
        $this->listing->save();
    }

    protected function isNotExistProductsWithCategoryTemplate($categoryTemplatesData)
    {
        if (empty($categoryTemplatesData)) {
            return true;
        }

        foreach ($categoryTemplatesData as $descriptionTemplateData) {
            if (!empty($descriptionTemplateData)) {
                return false;
            }
        }

        return true;
    }
}
