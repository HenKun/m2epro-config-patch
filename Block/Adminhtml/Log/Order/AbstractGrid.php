<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Log\Order;

abstract class AbstractGrid extends \Ess\M2ePro\Block\Adminhtml\Log\AbstractGrid
{
    /** @var \Ess\M2ePro\Helper\Module\Database\Structure */
    private $databaseHelper;

    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;

    /** @var \Ess\M2ePro\Helper\Component */
    private $componentHelper;

    /** @var \Ess\M2ePro\Helper\Component\Amazon */
    private $amazonHelper;

    /** @var \Ess\M2ePro\Helper\Component\Ebay */
    private $ebayHelper;

    /** @var \Ess\M2ePro\Helper\Component\Walmart */
    private $walmartHelper;

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Ess\M2ePro\Helper\View $viewHelper,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Ess\M2ePro\Helper\Module\Database\Structure $databaseHelper,
        \Ess\M2ePro\Helper\Data $dataHelper,
        \Ess\M2ePro\Helper\Component $componentHelper,
        \Ess\M2ePro\Helper\Component\Amazon $amazonHelper,
        \Ess\M2ePro\Helper\Component\Ebay $ebayHelper,
        \Ess\M2ePro\Helper\Component\Walmart $walmartHelper,
        array $data = []
    ) {
        $this->databaseHelper = $databaseHelper;
        $this->dataHelper = $dataHelper;
        $this->componentHelper = $componentHelper;
        $this->amazonHelper = $amazonHelper;
        $this->ebayHelper = $ebayHelper;
        $this->walmartHelper = $walmartHelper;
        parent::__construct(
            $resourceConnection,
            $viewHelper,
            $context,
            $backendHelper,
            $data
        );
    }

    abstract protected function getComponentMode();

    public function _construct()
    {
        parent::_construct();

        $this->css->addFile('order/log/grid.css');

        $this->setId(ucfirst($this->getComponentMode()) . 'OrderLogGrid');

        $this->setDefaultSort('create_date');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
        $this->setCustomPageSize(true);

        $this->entityIdFieldName = self::ORDER_ID_FIELD;
        $this->logModelName = 'Order_Log';
    }

    protected function _prepareCollection()
    {
        $collection = $this->activeRecordFactory->getObject('Order\Log')->getCollection();

        $isNeedCombine = $this->isNeedCombineMessages();

        if ($isNeedCombine) {
            $collection->getSelect()->columns(
                ['create_date' => new \Zend_Db_Expr('MAX(main_table.create_date)')]
            );
            $collection->getSelect()->group(['main_table.order_id', 'main_table.description']);
        }

        $collection->getSelect()->joinLeft(
            ['mo' => $this->activeRecordFactory->getObject('Order')->getResource()->getMainTable()],
            '(mo.id = `main_table`.order_id)',
            ['magento_order_id']
        );

        $accountId = (int)$this->getRequest()->getParam($this->getComponentMode() . 'Account', false);
        $marketplaceId = (int)$this->getRequest()->getParam($this->getComponentMode() . 'Marketplace', false);

        if ($accountId) {
            $collection->addFieldToFilter('main_table.account_id', $accountId);
        } else {
            $collection->getSelect()->joinLeft(
                [
                    'account_table' => $this->activeRecordFactory->getObject('Account')
                        ->getResource()->getMainTable()
                ],
                'main_table.account_id = account_table.id',
                ['real_account_id' => 'account_table.id']
            );
            $collection->addFieldToFilter('account_table.id', ['notnull' => true]);
        }

        if ($marketplaceId) {
            $collection->addFieldToFilter('main_table.marketplace_id', $marketplaceId);
        } else {
            $collection->getSelect()->joinLeft(
                [
                    'marketplace_table' => $this->activeRecordFactory->getObject('Marketplace')
                        ->getResource()->getMainTable()
                ],
                'main_table.marketplace_id = marketplace_table.id',
                ['marketplace_status' => 'marketplace_table.status']
            );
            $collection->addFieldToFilter('marketplace_table.status', \Ess\M2ePro\Model\Marketplace::STATUS_ENABLE);
        }

        $collection->getSelect()->joinLeft(
            ['so' => $this->databaseHelper->getTableNameWithPrefix('sales_order')],
            '(so.entity_id = `mo`.magento_order_id)',
            ['magento_order_number' => 'increment_id']
        );

        $orderId = $this->getRequest()->getParam('id', false);

        if ($orderId) {
            $collection->addFieldToFilter('main_table.order_id', (int)$orderId);
        }

        $backToDate = new \DateTime('now', new \DateTimeZone('UTC'));
        $backToDate->modify('- 1 days');

        if ($this->getRequest()->getParam('magento_order_failed')) {
            $text = 'Magento Order was not created';
            $collection->addFieldToFilter('main_table.description', ['like' => '%' . $text . '%']);
            $collection->addFieldToFilter('main_table.create_date', ['gt' => $backToDate->format('Y-m-d H:i:s')]);
        }

        $collection->addFieldToFilter('main_table.component_mode', $this->getComponentMode());

        $this->setCollection($collection);
        $result = parent::_prepareCollection();

        if ($isNeedCombine) {
            $this->prepareMessageCount($collection);
        }

        return $result;
    }

    protected function _prepareColumns()
    {
        $this->addColumn('create_date', [
            'header'       => $this->__('Creation Date'),
            'align'        => 'left',
            'type'         => 'datetime',
            'filter'       => \Ess\M2ePro\Block\Adminhtml\Magento\Grid\Column\Filter\Datetime::class,
            'filter_time'  => true,
            'index'        => 'create_date',
            'filter_index' => 'main_table.create_date'
        ]);

        $componentNick = $this->componentHelper->getComponentTitle(
            $this->getComponentMode()
        );

        $this->addColumn('channel_order_id', [
            'header'    => $this->__('%1% Order #', $componentNick),
            'align'     => 'left',
            'sortable'  => false,
            'index'     => 'channel_order_id',
            'frame_callback' => [$this, 'callbackColumnChannelOrderId'],
            'filter_condition_callback' => [$this, 'callbackFilterChannelOrderId']
        ]);

        $this->addColumn('magento_order_number', [
            'header'    => $this->__('Magento Order #'),
            'align'     => 'left',
            'index'     => 'so.increment_id',
            'sortable'      => false,
            'frame_callback' => [$this, 'callbackColumnMagentoOrderNumber']
        ]);

        $this->addColumn('description', [
            'header'    => $this->__('Message'),
            'align'     => 'left',
            'index'     => 'description',
            'frame_callback' => [$this, 'callbackColumnDescription']
        ]);

        $this->addColumn('initiator', [
            'header'    => $this->__('Run Mode'),
            'align'     => 'right',
            'index'     => 'initiator',
            'sortable'  => false,
            'type'      => 'options',
            'options'   => $this->_getLogInitiatorList(),
            'frame_callback' => [$this, 'callbackColumnInitiator']
        ]);

        $this->addColumn('type', [
            'header'    => $this->__('Type'),
            'align'     => 'right',
            'index'     => 'type',
            'type'      => 'options',
            'sortable'  => false,
            'options'   => $this->_getLogTypeList(),
            'frame_callback' => [$this, 'callbackColumnType']
        ]);

        return parent::_prepareColumns();
    }

    public function callbackColumnChannelOrderId($value, $row, $column, $isExport)
    {
        $order = $this->parentFactory
            ->getObjectLoaded($row['component_mode'], 'Order', $row->getData('order_id'), null, false);

        if ($order === null || $order->getChildObject()->getId() === null) {
            return $this->__('N/A');
        }

        switch ($order->getComponentMode()) {
            case \Ess\M2ePro\Helper\Component\Ebay::NICK:
                $channelOrderId = $order
                    ->getChildObject()->getData('ebay_order_id');
                $url = $this->getUrl('*/ebay_order/view', ['id' => $row->getData('order_id')]);
                break;
            case \Ess\M2ePro\Helper\Component\Amazon::NICK:
                $channelOrderId = $order
                    ->getChildObject()->getData('amazon_order_id');
                $url = $this->getUrl('*/amazon_order/view', ['id' => $row->getData('order_id')]);
                break;
            case \Ess\M2ePro\Helper\Component\Walmart::NICK:
                $channelOrderId = $order
                    ->getChildObject()->getData('walmart_order_id');
                $url = $this->getUrl('*/walmart_order/view', ['id' => $row->getData('order_id')]);
                break;
            default:
                $channelOrderId = $this->__('N/A');
                $url = '#';
        }

        return '<a href="' . $url . '" target="_blank">' . $this->dataHelper->escapeHtml($channelOrderId) . '</a>';
    }

    public function callbackColumnMagentoOrderNumber($value, $row, $column, $isExport)
    {
        $magentoOrderId = $row->getData('magento_order_id');
        $magentoOrderNumber = $row->getData('magento_order_number');

        if (!$magentoOrderId) {
            $result = $this->__('N/A');
        } else {
            $url = $this->getUrl('sales/order/view', ['order_id' => $magentoOrderId]);
            $result = '<a href="' . $url . '" target="_blank">'
                        . $this->dataHelper->escapeHtml($magentoOrderNumber) . '</a>';
        }

        return "<span style='min-width: 110px; display: block;'>{$result}</span>";
    }

    public function callbackFilterChannelOrderId($collection, $column)
    {
        $value = $column->getFilter()->getValue();
        if ($value == null) {
            return;
        }

        $ordersIds = [];

        if ($this->ebayHelper->isEnabled()) {
            $tempOrdersIds = $this->activeRecordFactory->getObject('Ebay\Order')
                ->getCollection()
                ->addFieldToFilter('ebay_order_id', ['like' => '%' . $value . '%'])
                ->getColumnValues('order_id');
            $ordersIds = array_merge($ordersIds, $tempOrdersIds);
        }

        if ($this->amazonHelper->isEnabled()) {
            $tempOrdersIds = $this->activeRecordFactory->getObject('Amazon\Order')
                ->getCollection()
                ->addFieldToFilter('amazon_order_id', ['like' => '%' . $value . '%'])
                ->getColumnValues('order_id');
            $ordersIds = array_merge($ordersIds, $tempOrdersIds);
        }

        if ($this->walmartHelper->isEnabled()) {
            $tempOrdersIds = $this->activeRecordFactory->getObject('Walmart\Order')
                ->getCollection()
                ->addFieldToFilter('walmart_order_id', ['like' => '%' . $value . '%'])
                ->getColumnValues('order_id');
            $ordersIds = array_merge($ordersIds, $tempOrdersIds);
        }

        $ordersIds = array_unique($ordersIds);

        $collection->addFieldToFilter('main_table.order_id', ['in' => $ordersIds]);
    }

    public function getRowUrl($row)
    {
        return false;
    }
}
