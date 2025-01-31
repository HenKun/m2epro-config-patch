<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task\Amazon\Order\Update;

use Ess\M2ePro\Model\Amazon\Order;

/**
 * Class \Ess\M2ePro\Model\Cron\Task\Amazon\Order\Update\SellerOrderId
 */
class SellerOrderId extends \Ess\M2ePro\Model\Cron\Task\AbstractModel
{
    const NICK = 'amazon/order/update/seller_order_id';

    const ORDERS_PER_MERCHANT = 1000;

    /** @var int (in seconds) */
    protected $interval = 3600;
    /** @var \Magento\Sales\Model\ResourceModel\Order */
    private $orderResource;
    /** @var \Ess\M2ePro\Model\ResourceModel\Order\CollectionFactory */
    private $orderCollectionFactory;
    /** @var \Ess\M2ePro\Model\ResourceModel\Amazon\Order */
    private $orderAmazonResource;
    /** @var \Ess\M2ePro\Model\ResourceModel\Amazon\Account */
    private $amazonAccountResource;

    //####################################

    public function __construct(
        \Magento\Sales\Model\ResourceModel\Order $orderResource,
        \Ess\M2ePro\Helper\Data $helperData,
        \Magento\Framework\Event\Manager $eventManager,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\App\ResourceConnection $resource,
        \Ess\M2ePro\Model\Cron\Task\Repository $taskRepo,
        \Ess\M2ePro\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        \Ess\M2ePro\Model\ResourceModel\Amazon\Order $orderAmazonResource,
        \Ess\M2ePro\Model\ResourceModel\Amazon\Account $amazonAccountResource
    ) {
        $this->orderResource = $orderResource;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->orderAmazonResource = $orderAmazonResource;
        $this->amazonAccountResource = $amazonAccountResource;

        parent::__construct(
            $helperData,
            $eventManager,
            $parentFactory,
            $modelFactory,
            $activeRecordFactory,
            $helperFactory,
            $taskRepo,
            $resource
        );
    }

    //####################################

    public function isPossibleToRun()
    {
        if ($this->getHelper('Server\Maintenance')->isNow()) {
            return false;
        }

        return parent::isPossibleToRun();
    }

    //####################################

    protected function performActions()
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Amazon\Account\Collection $accounts */
        $accounts = $this->parentFactory->getObject(
            \Ess\M2ePro\Helper\Component\Amazon::NICK,
            'Account'
        )->getCollection();

        // Getting accounts with enabled feature
        $enabledAccountIds = [];
        $enabledMerchantIds = [];

        foreach ($accounts->getItems() as $account) {
            /** @var \Ess\M2ePro\Model\Account $account */

            if ($account->getChildObject()->isMagentoOrdersNumberApplyToAmazonOrderEnable()) {
                $enabledAccountIds[] = $account->getId();
                $enabledMerchantIds[] = $account->getChildObject()->getMerchantId();
            }
        }

        if (empty($enabledAccountIds)) {
            return;
        }

        // Processing orders from last day
        $backToDate = new \DateTime('now', new \DateTimeZone('UTC'));
        $backToDate->modify('-1 day');

        // Processing orders from last 7 days for orders of replacement
        $backToReplacementDate = new \DateTime('now', new \DateTimeZone('UTC'));
        $backToReplacementDate->modify('-7 day');

        $connection = $this->resource->getConnection();

        $enabledMerchantIds = array_unique($enabledMerchantIds);

        foreach ($enabledMerchantIds as $enabledMerchantId) {
            // Preparing data structure for calls
            $orders = [];
            $accounts = [];
            $ordersToUpdate = [];
            $collection = $this->getOrderCollection(
                $enabledAccountIds,
                $enabledMerchantId,
                $backToDate->format('Y-m-d H:i:s'),
                $backToReplacementDate->format('Y-m-d H:i:s')
            );

            foreach ($collection->getItems() as $orderData) {
                $orders[$orderData['order_id']] = [
                    'amazon_order_id' => $orderData['amazon_order_id'],
                    'seller_order_id' => $orderData['increment_id']
                ];
                $accounts[] = $orderData['server_hash'];

                $ordersToUpdate[$orderData['order_id']] = [
                    'seller_order_id' => $orderData['increment_id']
                ];
            }

            if (empty($ordersToUpdate)) {
                continue;
            }

            // Sending update requests
            /** @var \Ess\M2ePro\Model\Amazon\Connector\Dispatcher $dispatcherObject */
            $dispatcherObject = $this->modelFactory->getObject('Amazon_Connector_Dispatcher');
            $connectorObj = $dispatcherObject->getVirtualConnector(
                'orders',
                'update',
                'sellerOrderId',
                [
                    'orders' => $orders,
                    'accounts' => array_unique($accounts),
                    'ignore_processing_request' => 1
                ]
            );
            $dispatcherObject->process($connectorObj);
            $response = $connectorObj->getResponseData();

            // Updating orders if Amazon accepted processing
            if (isset($response['processed']) && $response['processed'] == true) {
                /** @var \Ess\M2ePro\Model\Order\Log $logModel */
                $logModel = $this->activeRecordFactory->getObject('Order\Log');
                $logModel->setComponentMode(\Ess\M2ePro\Helper\Component\Amazon::NICK);

                foreach ($ordersToUpdate as $orderId => $orderData) {
                    $connection->update(
                        $this->orderAmazonResource->getMainTable(),
                        [
                            'seller_order_id' => $orderData['seller_order_id']
                        ],
                        '`order_id` = ' . $orderId
                    );

                    $logModel->addMessage(
                        $orderId,
                        $this->getHelper('Module\Translation')->__(
                            'Magento Order number has been set as Your Seller Order ID in Amazon Order details.'
                        ),
                        \Ess\M2ePro\Model\Log\AbstractModel::TYPE_INFO
                    );
                }
            }
        }
    }

    /**
     * @param array $enabledAccountIds
     * @param string $enabledMerchantId
     * @param string $date
     * @param string $replacementDate
     *
     * @return \Ess\M2ePro\Model\ResourceModel\Order\Collection
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getOrderCollection($enabledAccountIds, $enabledMerchantId, $date, $replacementDate)
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Order\Collection $collection */
        $collection =  $this->orderCollectionFactory->create();
        $collection->joinInner(
            ['second_table' => $this->orderAmazonResource->getMainTable()],
            'second_table.order_id = main_table.id'
        );

        $collection->joinInner(
            ['sfo' => $this->orderResource->getMainTable()],
            '(`main_table`.`magento_order_id` = `sfo`.`entity_id`)',
            [
                'increment_id' => 'sfo.increment_id',
            ]
        );

        $collection->joinInner(
            ['maa' => $this->amazonAccountResource->getMainTable()],
            '(`main_table`.`account_id` = `maa`.`account_id`)',
            [
                'merchant_id' => 'maa.merchant_id',
                'server_hash' => 'maa.server_hash',
            ]
        );

        $collection->addFieldToFilter('main_table.component_mode', \Ess\M2ePro\Helper\Component\Amazon::NICK);
        $collection->addFieldToFilter('main_table.account_id', ['in' => $enabledAccountIds]);
        $collection->addFieldToFilter('main_table.magento_order_id', ['notnull' => true]);
        $collection->addFieldToFilter('second_table.status', ['neq' => Order::STATUS_CANCELED]);
        $collection->addFieldToFilter('second_table.seller_order_id', ['null' => true]);
        $collection->addFieldToFilter('maa.merchant_id', ['eq' => $enabledMerchantId]);
        $where = "(`main_table`.`create_date` > '{$date}' AND `second_table`.`is_replacement` = 0)";
        $where .= " OR (`main_table`.`create_date` > '{$replacementDate}' AND `second_table`.`is_replacement` = 1)";
        $collection->getSelect()->where($where);
        $collection->getSelect()->limit(self::ORDERS_PER_MERCHANT);

        return $collection;
    }
}
