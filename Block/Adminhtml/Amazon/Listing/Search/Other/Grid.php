<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Search\Other;

use Ess\M2ePro\Model\Amazon\Listing\Product;

class Grid extends \Ess\M2ePro\Block\Adminhtml\Amazon\Listing\Search\AbstractGrid
{
    private const ACTUAL_QTY_EXPRESSION =
        'IF(second_table.is_afn_channel = 1, second_table.online_afn_qty, second_table.online_qty)';

    /** @var \Ess\M2ePro\Helper\Module\Database\Structure */
    private $databaseHelper;

    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;

    public function __construct(
        \Ess\M2ePro\Model\ResourceModel\Magento\Product\CollectionFactory $magentoProductCollectionFactory,
        \Magento\Framework\Locale\CurrencyInterface $localeCurrency,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Helper\Data $helperData,
        \Ess\M2ePro\Helper\Component\Amazon $amazonHelper,
        \Ess\M2ePro\Helper\Component\Amazon\Repricing $amazonRepricingHelper,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Ess\M2ePro\Helper\Module\Database\Structure $databaseHelper,
        \Ess\M2ePro\Helper\Data $dataHelper,
        array $data = []
    ) {
        $this->databaseHelper = $databaseHelper;
        $this->dataHelper = $dataHelper;
        parent::__construct(
            $magentoProductCollectionFactory,
            $localeCurrency,
            $amazonFactory,
            $resourceConnection,
            $helperData,
            $amazonHelper,
            $amazonRepricingHelper,
            $context,
            $backendHelper,
            $data
        );
    }

    public function _construct()
    {
        parent::_construct();

        $this->setId('amazonListingSearchOtherGrid');

        $this->setDefaultSort(false);
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
    }

    protected function _prepareColumns()
    {
        parent::_prepareColumns();

        $this->getColumn('name')->setData('header', $this->__('Product Title / Product SKU'));
    }

    //########################################

    protected function _prepareCollection()
    {
        $collection = $this->amazonFactory->getObject('Listing\Other')->getCollection();
        $collection->getSelect()->distinct();

        $collection->getSelect()->joinLeft(
            [
                'cpe' => $this->databaseHelper
                    ->getTableNameWithPrefix('catalog_product_entity')
            ],
            '(cpe.entity_id = `main_table`.product_id)',
            ['sku' => 'sku']
        );

        $afnStateAllNo  = Product::VARIATION_PARENT_IS_AFN_STATE_ALL_NO;
        $afnStateAllYes = Product::VARIATION_PARENT_IS_AFN_STATE_ALL_YES;
        $repricingStateAllNo = Product::VARIATION_PARENT_IS_REPRICING_STATE_ALL_NO;
        $repricingStateAllYes = Product::VARIATION_PARENT_IS_REPRICING_STATE_ALL_YES;

        $collection->getSelect()->reset(\Magento\Framework\DB\Select::COLUMNS);
        $collection->getSelect()->columns(
            [
                'sku'                          => 'cpe.sku',
                'name'                         => 'second_table.title',
                'listing_title'                => new \Zend_Db_Expr('NULL'),
                'store_id'                     => new \Zend_Db_Expr(0),
                'account_id'                   => 'main_table.account_id',
                'marketplace_id'               => 'main_table.marketplace_id',
                'listing_product_id'           => new \Zend_Db_Expr('NULL'),
                'entity_id'                    => 'main_table.product_id',
                'listing_id'                   => new \Zend_Db_Expr('NULL'),
                'amazon_status'                => 'main_table.status',
                'is_general_id_owner'          => new \Zend_Db_Expr('NULL'),
                'general_id'                   => 'second_table.general_id',
                'is_afn_channel'               => 'second_table.is_afn_channel',
                'is_variation_parent'          => new \Zend_Db_Expr('NULL'),
                'variation_child_statuses'     => new \Zend_Db_Expr('NULL'),
                'online_sku'                   => 'second_table.sku',
                'online_title'                 => new \Zend_Db_Expr('NULL'),
                'online_qty'                   => 'second_table.online_qty',
                'online_afn_qty'               => 'second_table.online_afn_qty',
                'online_actual_qty'            => self::ACTUAL_QTY_EXPRESSION,
                'online_price'                 => 'second_table.online_price',
                'online_sale_price'            => new \Zend_Db_Expr('NULL'),
                'online_sale_price_start_date' => new \Zend_Db_Expr('NULL'),
                'online_sale_price_end_date'   => new \Zend_Db_Expr('NULL'),

                'online_business_price'        => new \Zend_Db_Expr('NULL'),

                'online_current_price'         => 'second_table.online_price',
                'online_regular_price'         => 'second_table.online_price',

                'is_repricing'                 => 'second_table.is_repricing',
                'is_repricing_disabled'        => 'second_table.is_repricing_disabled',

                'variation_parent_afn_state' => new \Zend_Db_Expr("IF(
                    second_table.is_afn_channel = 1,
                    {$afnStateAllYes},
                    {$afnStateAllNo}
                )"),
                'variation_parent_repricing_state' => new \Zend_Db_Expr("IF(
                    second_table.is_repricing = 1,
                    {$repricingStateAllYes},
                    {$repricingStateAllNo}
                )"),
            ]
        );

        $accountId     = (int)$this->getRequest()->getParam('amazonAccount', false);
        $marketplaceId = (int)$this->getRequest()->getParam('amazonMarketplace', false);

        if ($accountId) {
            $collection->getSelect()->where('account_id = ?', $accountId);
        }

        if ($marketplaceId) {
            $collection->getSelect()->where('marketplace_id = ?', $marketplaceId);
        }

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    //########################################

    public function callbackColumnProductTitle($value, $row, $column, $isExport)
    {
        $title = $row->getData('name');

        if ($title === null || $title === '') {
            $value = '<i style="color:gray;">' . $this->__('receiving') . '...</i>';
        } else {
            $value = $this->dataHelper->escapeHtml($title);
        }

        $value = '<div style="margin-bottom: 5px">' . $value . '</div>';

        $account = $this->amazonFactory->getCachedObjectLoaded('Account', $row->getData('account_id'));
        $marketplace = $this->amazonFactory->getCachedObjectLoaded('Marketplace', $row->getData('marketplace_id'));

        $value .= '<strong>' . $this->__('Account') . ':</strong>'
            . '&nbsp;' . $account->getTitle() . '<br/>'
            .'<strong>' . $this->__('Marketplace') . ':</strong>'
            . '&nbsp;' . $marketplace->getTitle();

        $sku = $row->getData('sku');
        if (!empty($sku)) {
            $sku = $this->dataHelper->escapeHtml($sku);
            $skuWord = $this->__('SKU');

            $value .= <<<HTML
<br/><strong>{$skuWord}:</strong>&nbsp;
{$sku}
HTML;
        }

        return $value;
    }

    public function callbackColumnStatus($value, $row, $column, $isExport)
    {
        return $this->getProductStatus($row->getData('amazon_status'));
    }

    public function callbackColumnActions($value, $row, $column, $isExport)
    {
        $altTitle = $this->dataHelper->escapeHtml($this->__('Go to Listing'));
        $iconSrc  = $this->getViewFileUrl('Ess_M2ePro::images/goto_listing.png');

        $manageUrl = $this->getUrl('*/amazon_listing_other/view/', [
            'account'     => $row->getData('account_id'),
            'marketplace' => $row->getData('marketplace_id'),
            'filter'      => base64_encode(
                'title=' . $row->getData('online_sku')
            )
        ]);

        $html = <<<HTML
<div style="float:right; margin:5px 15px 0 0;">
    <a title="{$altTitle}" target="_blank" href="{$manageUrl}"><img src="{$iconSrc}" alt="{$altTitle}" /></a>
</div>
HTML;

        return $html;
    }

    //########################################

    protected function callbackFilterProductId($collection, $column)
    {
        $cond = $column->getFilter()->getCondition();

        if (empty($cond)) {
            return;
        }

        $collection->addFieldToFilter('main_table.product_id', $cond);
    }

    protected function callbackFilterTitle($collection, $column)
    {
        $value = $column->getFilter()->getValue();

        if ($value == null) {
            return;
        }

        $collection->getSelect()->where('second_table.title LIKE ? OR cpe.sku LIKE ?', '%'.$value.'%');
    }

    protected function callbackFilterOnlineSku($collection, $column)
    {
        $value = $column->getFilter()->getValue();

        if ($value == null) {
            return;
        }

        $collection->getSelect()->where('second_table.sku LIKE ?', '%'.$value.'%');
    }

    protected function callbackFilterAsinIsbn($collection, $column)
    {
        $value = $column->getFilter()->getValue();

        if ($value == null) {
            return;
        }

        $collection->addFieldToFilter('general_id', ['like' => '%'.$value.'%']);
    }

    protected function callbackFilterPrice($collection, $column)
    {
        $value = $column->getFilter()->getValue();

        if (empty($value)) {
            return;
        }

        $condition = '';

        if (isset($value['from']) || isset($value['to'])) {
            if (isset($value['from']) && $value['from'] != '') {
                $condition = 'second_table.online_price >= \'' . (float)$value['from'] . '\'';
            }

            if (isset($value['to']) && $value['to'] != '') {
                if (isset($value['from']) && $value['from'] != '') {
                    $condition .= ' AND ';
                }
                $condition .= 'second_table.online_price <= \'' . (float)$value['to'] . '\'';
            }
        }

        if ($this->amazonRepricingHelper->isEnabled() &&
            isset($value['is_repricing']) && $value['is_repricing'] !== '') {
            if (!empty($condition)) {
                $condition = '(' . $condition . ') OR ';
            }
            $condition .= 'second_table.is_repricing = ' . (int)$value['is_repricing'];
        }

        $collection->getSelect()->where($condition);
    }

    protected function callbackFilterQty($collection, $column)
    {
        $value = $column->getFilter()->getValue();

        if (empty($value)) {
            return;
        }

        $where = '';

        if (isset($value['from']) && $value['from'] != '') {
            $quoted = $collection->getConnection()->quote($value['from']);
            $where .= self::ACTUAL_QTY_EXPRESSION . ' >= ' . $quoted;
        }

        if (isset($value['to']) && $value['to'] != '') {
            if (isset($value['from']) && $value['from'] != '') {
                $where .= ' AND ';
            }
            $quoted = $collection->getConnection()->quote($value['to']);
            $where .= self::ACTUAL_QTY_EXPRESSION . ' <= ' . $quoted;
        }

        if (isset($value['afn']) && $value['afn'] !== '') {
            if (!empty($where)) {
                $where .= ' AND ';
            }
            $where .= 'second_table.is_afn_channel = ' . (int)$value['afn'];
        }

        $collection->getSelect()->where($where);
    }

    protected function callbackFilterStatus($collection, $column)
    {
        $value = $column->getFilter()->getValue();

        if ($value == null) {
            return;
        }

        $collection->getSelect()->where('status = ?', $value);
    }

    //########################################
}
