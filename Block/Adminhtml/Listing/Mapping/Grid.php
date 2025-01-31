<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Listing\Mapping;

class Grid extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractGrid
{
    /** @var \Ess\M2ePro\Model\ResourceModel\Magento\Product\CollectionFactory */
    protected $magentoProductCollectionFactory;

    /** @var \Magento\Catalog\Model\Product\Type */
    protected $type;

    /** @var \Ess\M2ePro\Helper\Magento\Product */
    protected $magentoProductHelper;
    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Magento\Product\CollectionFactory $magentoProductCollectionFactory,
        \Magento\Catalog\Model\Product\Type $type,
        \Ess\M2ePro\Helper\Magento\Product $magentoProductHelper,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Ess\M2ePro\Helper\Data $dataHelper,
        array $data = []
    ) {
        $this->magentoProductCollectionFactory = $magentoProductCollectionFactory;
        $this->type = $type;
        $this->magentoProductHelper = $magentoProductHelper;

        $this->dataHelper = $dataHelper;
        parent::__construct($context, $backendHelper, $data);
    }

    public function _construct()
    {
        parent::_construct();

        $this->setId('listingMappingGrid');

        $this->setDefaultSort('product_id');
        $this->setDefaultDir('DESC');
        $this->setUseAjax(true);
    }

    protected function _prepareCollection()
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Magento\Product\Collection $collection */
        $collection = $this->magentoProductCollectionFactory->create()
            ->addAttributeToSelect('sku')
            ->addAttributeToSelect('name')
            ->addAttributeToSelect('type_id');

        $collection->joinStockItem();

        $collection->addFieldToFilter(
            [
                [
                    'attribute' => 'type_id',
                    'in'        => $this->magentoProductHelper->getOriginKnownTypes()
                ]
            ]
        );

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn(
            'product_id',
            [
                'header'       => $this->__('Product ID'),
                'align'        => 'right',
                'type'         => 'number',
                'width'        => '100px',
                'index'        => 'entity_id',
                'filter_index' => 'entity_id',
                'renderer'     => \Ess\M2ePro\Block\Adminhtml\Magento\Grid\Column\Renderer\ProductId::class
            ]
        );

        $this->addColumn(
            'title',
            [
                'header'                    => $this->__('Product Title / Product SKU'),
                'align'                     => 'left',
                'type'                      => 'text',
                'width'                     => '200px',
                'index'                     => 'name',
                'filter_index'              => 'name',
                'escape'                    => false,
                'frame_callback'            => [$this, 'callbackColumnTitle'],
                'filter_condition_callback' => [$this, 'callbackFilterTitle']
            ]
        );

        $this->addColumn(
            'type',
            [
                'header'       => $this->__('Type'),
                'align'        => 'left',
                'width'        => '120px',
                'type'         => 'options',
                'sortable'     => false,
                'index'        => 'type_id',
                'filter_index' => 'type_id',
                'options'      => $this->getProductTypes()
            ]
        );

        $this->addColumn(
            'stock_availability',
            [
                'header'         => $this->__('Stock Availability'),
                'width'          => '100px',
                'index'          => 'is_in_stock',
                'filter_index'   => 'is_in_stock',
                'type'           => 'options',
                'sortable'       => false,
                'options'        => [
                    1 => $this->__('In Stock'),
                    0 => $this->__('Out of Stock')
                ],
                'frame_callback' => [$this, 'callbackColumnIsInStock']
            ]
        );

        $this->addColumn(
            'actions',
            [
                'header'         => $this->__('Actions'),
                'align'          => 'left',
                'type'           => 'text',
                'width'          => '125px',
                'filter'         => false,
                'sortable'       => false,
                'frame_callback' => [$this, 'callbackColumnActions'],
            ]
        );
    }

    //########################################

    public function callbackColumnTitle($value, $row, $column, $isExport)
    {
        $value = '<div style="margin-left: 3px">' . $this->dataHelper->escapeHtml($value);

        $tempSku = $row->getData('sku');
        if ($tempSku === null) {
            $tempSku = $this->modelFactory->getObject('Magento\Product')
                ->setProductId($row->getData('entity_id'))->getSku();
        }

        $value .= '<br/><strong>' . $this->__('SKU') . ':</strong> ';
        $value .= $this->dataHelper->escapeHtml($tempSku) . '</div>';

        return $value;
    }

    public function callbackColumnType($value, $row, $column, $isExport)
    {
        return '<div style="margin-left: 3px">' . $this->dataHelper->escapeHtml($value) . '</div>';
    }

    public function callbackColumnIsInStock($value, $row, $column, $isExport)
    {
        if ($row->getData('is_in_stock') === null) {
            return $this->__('N/A');
        }

        if ((int)$row->getData('is_in_stock') <= 0) {
            return '<span style="color: red;">' . $this->__('Out of Stock') . '</span>';
        }

        return $value;
    }

    public function callbackColumnActions($value, $row, $column, $isExport)
    {
        $actions = '&nbsp;<a href="javascript:void(0);"';
        $actions .= 'onclick="' . $this->getData('mapping_handler_js') . '.';
        $actions .= $this->getData('mapping_action') . '(' . $row->getId() . ');">';
        $actions .= $this->__('Link To This Product') . '</a>';

        return $actions;
    }

    protected function callbackFilterTitle($collection, $column)
    {
        $value = $column->getFilter()->getValue();

        if ($value == null) {
            return;
        }

        $collection->addFieldToFilter(
            [
                ['attribute' => 'sku', 'like' => '%' . $value . '%'],
                ['attribute' => 'name', 'like' => '%' . $value . '%']
            ]
        );
    }

    //########################################

    protected function _beforeToHtml()
    {
        $this->js->addOnReadyJs(
            <<<JS

        $$('#listingOtherMappingGrid div.grid th').each(function(el) {
            el.style.padding = '2px 4px';
        });

        $$('#listingOtherMappingGrid div.grid td').each(function(el) {
            el.style.padding = '2px 4px';
        });

         $$('.grid-listing-column-actions').each(function(el) {
            el.style.width = '200px';
        });

JS
        );

        return parent::_beforeToHtml();
    }

    //########################################

    public function getGridUrl()
    {
        return $this->getUrl(
            $this->getData('grid_url'),
            [
                '_current' => true,
                  'component_mode' => $this->getRequest()->getParam('component_mode')
            ]
        );
    }

    public function getRowUrl($row)
    {
        return false;
    }

    //########################################

    protected function getProductTypes()
    {
        $magentoProductTypes = $this->type->getOptionArray();
        $knownTypes = $this->magentoProductHelper->getOriginKnownTypes();

        foreach ($magentoProductTypes as $type => $magentoProductTypeLabel) {
            if (in_array($type, $knownTypes)) {
                continue;
            }

            unset($magentoProductTypes[$type]);
        }

        return $magentoProductTypes;
    }

    //########################################
}
