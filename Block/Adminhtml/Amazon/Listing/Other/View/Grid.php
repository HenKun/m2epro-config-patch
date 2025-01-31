<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Other\View;

class Grid extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractGrid
{
    private const ACTUAL_QTY_EXPRESSION =
        'IF(second_table.is_afn_channel = 1, second_table.online_afn_qty, second_table.online_qty)';

    /** @var \Magento\Framework\Locale\CurrencyInterface */
    protected $localeCurrency;

    /** @var \Magento\Framework\App\ResourceConnection */
    protected $resourceConnection;

    /** @var \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory */
    protected $amazonFactory;

    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;

    /** @var \Ess\M2ePro\Helper\Component\Amazon */
    private $amazonHelper;

    /** @var \Ess\M2ePro\Helper\Component\Amazon\Repricing */
    private $amazonRepricingHelper;

    public function __construct(
        \Magento\Framework\Locale\CurrencyInterface $localeCurrency,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Ess\M2ePro\Helper\Data $dataHelper,
        \Ess\M2ePro\Helper\Component\Amazon $amazonHelper,
        \Ess\M2ePro\Helper\Component\Amazon\Repricing $amazonRepricingHelper,
        array $data = []
    ) {
        $this->localeCurrency = $localeCurrency;
        $this->resourceConnection = $resourceConnection;
        $this->amazonFactory = $amazonFactory;
        $this->dataHelper = $dataHelper;
        $this->amazonHelper = $amazonHelper;
        $this->amazonRepricingHelper = $amazonRepricingHelper;
        parent::__construct($context, $backendHelper, $data);
    }

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('amazonListingOtherGrid');
        // ---------------------------------------

        // Set default values
        // ---------------------------------------
        $this->setDefaultSort('id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
        // ---------------------------------------
    }

    protected function _prepareCollection()
    {
        $collection = $this->amazonFactory->getObject('Listing\Other')->getCollection();

        $collection->getSelect()->joinLeft(
            ['mp' => $this->activeRecordFactory->getObject('Marketplace')->getResource()->getMainTable()],
            'mp.id = main_table.marketplace_id',
            ['marketplace_title' => 'mp.title']
        )->joinLeft(
            ['am' => $this->activeRecordFactory->getObject('Amazon\Marketplace')->getResource()->getMainTable()],
            'am.marketplace_id = main_table.marketplace_id',
            ['currency' => 'am.default_currency']
        );

        // Add Filter By Account
        if ($this->getRequest()->getParam('account')) {
            $collection->addFieldToFilter(
                'main_table.account_id',
                $this->getRequest()->getParam('account')
            );
        }

        // Add Filter By Marketplace
        if ($this->getRequest()->getParam('marketplace')) {
            $collection->addFieldToFilter(
                'main_table.marketplace_id',
                $this->getRequest()->getParam('marketplace')
            );
        }

        $collection->getSelect()->columns(
            ['online_actual_qty' => self::ACTUAL_QTY_EXPRESSION]
        );

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn('product_id', [
            'header' => $this->__('Product ID'),
            'align'  => 'left',
            'width'  => '80px',
            'type'   => 'number',
            'index'  => 'product_id',
            'filter_index' => 'product_id',
            'frame_callback' => [$this, 'callbackColumnProductId'],
            'filter' => \Ess\M2ePro\Block\Adminhtml\Grid\Column\Filter\ProductId::class,
            'filter_condition_callback' => [$this, 'callbackFilterProductId']
        ]);

        $this->addColumn('title', [
            'header'    => $this->__('Title / SKU'),
            'align' => 'left',
            'type' => 'text',
            'index' => 'title',
            'escape' => false,
            'filter_index' => 'second_table.title',
            'frame_callback' => [$this, 'callbackColumnProductTitle'],
            'filter_condition_callback' => [$this, 'callbackFilterTitle']
        ]);

        $this->addColumn('general_id', [
            'header' => $this->__('ASIN / ISBN'),
            'align' => 'left',
            'width' => '100px',
            'type' => 'text',
            'index' => 'general_id',
            'filter_index' => 'general_id',
            'frame_callback' => [$this, 'callbackColumnGeneralId']
        ]);

        $this->addColumn('online_qty', [
            'header' => $this->__('QTY'),
            'align' => 'right',
            'width' => '100px',
            'type' => 'number',
            'index' => 'online_actual_qty',
            'filter_index' => 'online_actual_qty',
            'frame_callback' => [$this, 'callbackColumnAvailableQty'],
            'filter'   => \Ess\M2ePro\Block\Adminhtml\Amazon\Grid\Column\Filter\Qty::class,
            'filter_condition_callback' => [$this, 'callbackFilterQty']
        ]);

        $priceColumn = [
            'header' => $this->__('Price'),
            'align' => 'right',
            'width' => '110px',
            'type' => 'number',
            'index' => 'online_price',
            'filter_index' => 'online_price',
            'frame_callback' => [$this, 'callbackColumnPrice'],
            'filter_condition_callback' => [$this, 'callbackFilterPrice']
        ];

        $account = $this->amazonFactory->getObjectLoaded('Account', $this->getRequest()->getParam('account'));

        if ($this->amazonRepricingHelper->isEnabled() &&
            $account->getChildObject()->isRepricing()) {
            $priceColumn['filter'] = \Ess\M2ePro\Block\Adminhtml\Amazon\Grid\Column\Filter\Price::class;
        }

        $this->addColumn('online_price', $priceColumn);

        $this->addColumn('status', [
            'header' => $this->__('Status'),
            'width' => '75px',
            'index' => 'status',
            'filter_index' => 'main_table.status',
            'type' => 'options',
            'sortable' => false,
            'options' => [
                \Ess\M2ePro\Model\Listing\Product::STATUS_UNKNOWN => $this->__('Unknown'),
                \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED => $this->__('Active'),
                \Ess\M2ePro\Model\Listing\Product::STATUS_STOPPED => $this->__('Inactive'),
                \Ess\M2ePro\Model\Listing\Product::STATUS_BLOCKED => $this->__('Incomplete')
            ],
            'frame_callback' => [$this, 'callbackColumnStatus']
        ]);

        return parent::_prepareColumns();
    }

    protected function _prepareMassaction()
    {
        // Set mass-action identifiers
        // ---------------------------------------
        $this->setMassactionIdField('main_table.id');
        $this->getMassactionBlock()->setFormFieldName('ids');
        // ---------------------------------------

        $this->getMassactionBlock()->setGroups([
            'mapping' => $this->__('Linking'),
            'other'   => $this->__('Other')
        ]);

        // Set mass-action
        // ---------------------------------------
        $this->getMassactionBlock()->addItem('autoMapping', [
            'label'   => $this->__('Link Item(s) Automatically'),
            'url'     => ''
        ], 'mapping');
        $this->getMassactionBlock()->addItem('moving', [
            'label'   => $this->__('Move Item(s) to Listing'),
            'url'     => ''
        ], 'other');
        $this->getMassactionBlock()->addItem('removing', [
            'label'   => $this->__('Remove Item(s)'),
            'url'     => ''
        ], 'other');
        $this->getMassactionBlock()->addItem('unmapping', [
            'label'   => $this->__('Unlink Item(s)'),
            'url'     => ''
        ], 'mapping');
        // ---------------------------------------

        return parent::_prepareMassaction();
    }

    protected function _prepareLayout()
    {
        $this->css->addFile('listing/other/view/grid.css');

        return parent::_prepareLayout();
    }

    public function callbackColumnProductId($value, $row, $column, $isExport)
    {
        if (empty($value)) {
            $productTitle = $row->getChildObject()->getData('title');
            if (strlen($productTitle) > 60) {
                $productTitle = substr($productTitle, 0, 60) . '...';
            }
            $productTitle = $this->dataHelper->escapeHtml($productTitle);
            $productTitle = $this->dataHelper->escapeJs($productTitle);
            $htmlValue = '&nbsp;<a href="javascript:void(0);"
                                    onclick="ListingOtherMappingObj.openPopUp(
                                    '. (int)$row->getId(). ',
                                    \''. $productTitle. '\'
                                    );">' . $this->__('Link') . '</a>';
            return $htmlValue;
        }

        $htmlValue = '&nbsp<a href="'
                     .$this->getUrl(
                         'catalog/product/edit',
                         ['id' => $row->getData('product_id')]
                     )
                     .'" target="_blank">'
                     .$row->getData('product_id')
                     .'</a>';

        $htmlValue .= '&nbsp&nbsp&nbsp<a href="javascript:void(0);"'
                      .' onclick="AmazonListingOtherGridObj.movingHandler.getGridHtml('
                      .$this->dataHelper->jsonEncode([(int)$row->getData('id')])
                      .')">'
                      .$this->__('Move')
                      .'</a>';

        return $htmlValue;
    }

    public function callbackColumnProductTitle($value, $row, $column, $isExport)
    {
        $title = $row->getChildObject()->getData('title');

        if ($title === null) {
            $title = '<i style="color:gray;">' . $this->__('receiving') . '...</i>';
        } else {
            $title = '<span>' . $this->dataHelper->escapeHtml($title) . '</span>';
        }

        $tempSku = $row->getChildObject()->getData('sku');
        empty($tempSku) && $tempSku = $this->__('N/A');

        $title .= '<br/><strong>'
                  .$this->__('SKU')
                  .':</strong> '
                  .$this->dataHelper->escapeHtml($tempSku);

        return $title;
    }

    public function callbackColumnGeneralId($value, $row, $column, $isExport)
    {
        $url = $this->amazonHelper
            ->getItemUrl($row->getChildObject()->getData('general_id'), $row->getData('marketplace_id'));
        return '<a href="'.$url.'" target="_blank">'.$row->getChildObject()->getData('general_id').'</a>';
    }

    public function callbackColumnAvailableQty($value, $row, $column, $isExport)
    {
        if ($row->getChildObject()->getData('is_afn_channel')) {
            $qty = $row->getChildObject()->getData('online_afn_qty') ?? $this->__('N/A');
            return "AFN ($qty)";
        }

        $value = $row->getChildObject()->getData('online_qty');
        if ($value === null || $value === '') {
            return $this->__('N/A');
        }

        if ($value <= 0) {
            return '<span style="color: red;">0</span>';
        }

        return $value;
    }

    public function callbackColumnPrice($value, $row, $column, $isExport)
    {
        $html ='';
        $value = $row->getChildObject()->getData('online_price');

        if ($this->amazonRepricingHelper->isEnabled() &&
            (int)$row->getChildObject()->getData('is_repricing') == 1) {
            $icon = 'repricing-enabled';
            $text = $this->__(
                'This Product is used by Amazon Repricing Tool, so its Price cannot be managed via M2E Pro. <br>
                 <strong>Please note</strong> that the Price value(s) shown in the grid might
                 be different from the actual one from Amazon. It is caused by the delay
                 in the values updating made via the Repricing Service'
            );

            if ((int)$row->getChildObject()->getData('is_repricing_disabled') == 1) {
                $icon = 'repricing-disabled';
                $text = $this->__(
                    'This product is disabled on Amazon Repricing Tool. <br>
                    You can link it to Magento Product and Move into M2E Pro Listing to make the
                    Price being updated via M2E Pro.'
                );
            }

            $html = <<<HTML
&nbsp;<div class="fix-magento-tooltip {$icon}" style="float:right;">
    {$this->getTooltipHtml($text)}
</div>
HTML;
        }

        if ($value === null || $value === '') {
            return $this->__('N/A') . $html;
        }

        if ((float)$value <= 0) {
            return '<span style="color: #f00;">0</span>' . $html;
        }

        $currency = $this->amazonFactory
                        ->getCachedObjectLoaded('Marketplace', $row->getData('marketplace_id'))
                        ->getChildObject()
                        ->getDefaultCurrency();

        $priceValue = $this->localeCurrency->getCurrency($currency)->toCurrency($value) . $html;

        if ($row->getData('is_repricing') &&
            !$row->getData('is_repricing_disabled')
        ) {
            $accountId = $row->getData('account_id');
            $sku = $row->getData('sku');

            $priceValue =<<<HTML
<a id="m2epro_repricing_price_value_{$sku}"
   class="m2epro-repricing-price-value"
   sku="{$sku}"
   account_id="{$accountId}"
   href="javascript:void(0)"
   onclick="AmazonListingProductRepricingPriceObj.showRepricingPrice()">{$priceValue}</a>
HTML;

            return $priceValue.$html;
        }

        return $this->localeCurrency->getCurrency($currency)->toCurrency($value) . $html;
    }

    public function callbackColumnStatus($value, $row, $column, $isExport)
    {
        $coloredStstuses = [
            \Ess\M2ePro\Model\Listing\Product::STATUS_UNKNOWN => 'gray',
            \Ess\M2ePro\Model\Listing\Product::STATUS_LISTED => 'green',
            \Ess\M2ePro\Model\Listing\Product::STATUS_STOPPED => 'red',
            \Ess\M2ePro\Model\Listing\Product::STATUS_BLOCKED => 'orange',
        ];

        $status = $row->getData('status');

        if ($status !== null && isset($coloredStstuses[$status])) {
            $value = '<span style="color: '.$coloredStstuses[$status].';">' . $value . '</span>';
        }

        return $value;
    }

    protected function callbackFilterProductId($collection, $column)
    {
        $value = $column->getFilter()->getValue();

        if (empty($value)) {
            return;
        }

        $where = '';

        if (isset($value['from']) && $value['from'] != '') {
            $where .= 'product_id >= ' . (int)$value['from'];
        }

        if (isset($value['to']) && $value['to'] != '') {
            if (isset($value['from']) && $value['from'] != '') {
                $where .= ' AND ';
            }

            $where .= 'product_id <= ' . (int)$value['to'];
        }

        if (isset($value['is_mapped']) && $value['is_mapped'] !== '') {
            if (!empty($where)) {
                $where = '(' . $where . ') AND ';
            }

            if ($value['is_mapped']) {
                $where .= 'product_id IS NOT NULL';
            } else {
                $where .= 'product_id IS NULL';
            }
        }

        $collection->getSelect()->where($where);
    }

    protected function callbackFilterTitle($collection, $column)
    {
        $value = $column->getFilter()->getValue();

        if ($value == null) {
            return;
        }

        $collection->getSelect()->where('second_table.title LIKE ? OR second_table.sku LIKE ?', '%'.$value.'%');
    }

    protected function callbackFilterQty($collection, $column)
    {
        $value = $column->getFilter()->getValue();

        if (empty($value)) {
            return;
        }

        $where = '';

        if (isset($value['from']) && $value['from'] != '') {
            $where .= self::ACTUAL_QTY_EXPRESSION . ' >= ' . (int)$value['from'];
        }

        if (isset($value['to']) && $value['to'] != '') {
            if (isset($value['from']) && $value['from'] != '') {
                $where .= ' AND ';
            }
            $where .= self::ACTUAL_QTY_EXPRESSION . ' <= ' . (int)$value['to'];
        }

        if (isset($value['afn']) && $value['afn'] !== '') {
            if (!empty($where)) {
                $where .= ' AND ';
            }
            $where .= 'is_afn_channel = ' . (int)$value['afn'];
        }

        $collection->getSelect()->where($where);
    }

    protected function callbackFilterPrice($collection, $column)
    {
        $value = $column->getFilter()->getValue();

        if (empty($value)) {
            return;
        }

        $where = '';

        if (isset($value['from']) && $value['from'] != '') {
            $where .= 'online_price >= ' . (float)$value['from'];
        }

        if (isset($value['to']) && $value['to'] != '') {
            if (isset($value['from']) && $value['from'] != '') {
                $where .= ' AND ';
            }
            $where .= 'online_price <= ' . (float)$value['to'];
        }

        if ($this->amazonRepricingHelper->isEnabled() &&
            (isset($value['is_repricing']) && $value['is_repricing'] !== '')) {
            if (!empty($where)) {
                $where = '(' . $where . ') OR ';
            }
            $where .= 'is_repricing = ' . (int)$value['is_repricing'];
        }

        $collection->getSelect()->where($where);
    }

    protected function _beforeToHtml()
    {
        if ($this->getRequest()->isXmlHttpRequest() || $this->getRequest()->getParam('isAjax')) {
            $this->js->addRequireJs([
                'jQuery' => 'jquery'
            ], <<<JS

            AmazonListingOtherGridObj.afterInitPage();
JS
            );
        }

        return parent::_beforeToHtml();
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/amazon_listing_other/grid', ['_current' => true]);
    }

    public function getRowUrl($row)
    {
        return false;
    }
}
