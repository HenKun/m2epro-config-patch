<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Walmart;

/**
 * Class \Ess\M2ePro\Model\ResourceModel\Walmart\Order
 */
class Order extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Component\Child\AbstractModel
{
    protected $_isPkAutoIncrement = false;

    protected $walmartFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory,
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        $connectionName = null
    ) {
        $this->walmartFactory = $walmartFactory;

        parent::__construct($helperFactory, $activeRecordFactory, $parentFactory, $context, $connectionName);
    }

    //########################################

    public function _construct()
    {
        $this->_init('m2epro_walmart_order', 'order_id');
        $this->_isPkAutoIncrement = false;
    }

    //########################################

    public function getItemsTotal($orderId)
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Walmart\Order\Collection $collection */
        $collection = $this->walmartFactory->getObject('Order\Item')->getCollection();
        $collection->getSelect()->reset(\Magento\Framework\DB\Select::COLUMNS);
        $collection->addFieldToFilter('order_id', (int)$orderId);
        $collection->getSelect()->columns([
            'items_total' => new \Zend_Db_Expr('SUM((`price`)*`qty_purchased`)')
        ]);
        $collection->getSelect()->group('order_id');

        return round((float)$collection->getFirstItem()->getData('items_total'), 2);
    }

    //########################################
}
