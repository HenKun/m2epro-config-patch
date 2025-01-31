<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task\Ebay\PickupStore;

class UpdateOnChannel extends \Ess\M2ePro\Model\Cron\Task\AbstractModel
{
    const NICK = 'ebay/pickup_store/update_on_channel';

    const MAX_ITEMS_COUNT = 10000;

    /** @var \Ess\M2ePro\Helper\Component\Ebay\PickupStore */
    private $componentEbayPickupStore;

    public function __construct(
        \Ess\M2ePro\Helper\Component\Ebay\PickupStore $componentEbayPickupStore,
        \Ess\M2ePro\Helper\Data $helperData,
        \Magento\Framework\Event\Manager $eventManager,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Cron\Task\Repository $taskRepo,
        \Magento\Framework\App\ResourceConnection $resource
    ) {
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

        $this->componentEbayPickupStore = $componentEbayPickupStore;
    }

    public function isPossibleToRun()
    {
        if ($this->getHelper('Server\Maintenance')->isNow()) {
            return false;
        }

        return parent::isPossibleToRun();
    }

    //########################################

    public function performActions()
    {
        $accounts = $this->componentEbayPickupStore->getEnabledAccounts();

        if (empty($accounts)) {
            return;
        }

        foreach ($accounts as $account) {
            $this->getOperationHistory()->addText('Starting Account "' . $account->getTitle() . '"');

            $this->getOperationHistory()->addTimePoint(
                __METHOD__ . 'process' . $account->getId(),
                'Process Account ' . $account->getTitle()
            );

            try {
                $this->processAccount($account);
            } catch (\Exception $exception) {
                $message = $this->getHelper('Module\Translation')->__(
                    'The "Synchronize Data" Action for eBay Account: "%account%" was completed with error.',
                    $account->getTitle()
                );

                $this->processTaskAccountException($message, __FILE__, __LINE__);
                $this->processTaskException($exception);
            }

            $this->getOperationHistory()->saveTimePoint(__METHOD__ . 'process' . $account->getId());
        }
    }

    //########################################

    protected function processAccount(\Ess\M2ePro\Model\Account $account)
    {
        $collection = $this->activeRecordFactory->getObject('Ebay_Account_PickupStore_State')->getCollection();
        $collection->getSelect()->where('(is_deleted = 1) OR (target_qty != online_qty)');
        $collection->addFieldToFilter('is_in_processing', 0);

        $collection->getSelect()->joinLeft(
            [
                'eaps' => $this->activeRecordFactory->getObject('Ebay_Account_PickupStore')
                    ->getResource()->getMainTable()
            ],
            'eaps.id = main_table.account_pickup_store_id',
            ['account_id']
        );

        $collection->addFieldToFilter('eaps.account_id', $account->getId());

        $collection->getSelect()->limit(self::MAX_ITEMS_COUNT);

        $pickupStoreStateItems = $collection->getItems();
        if (empty($pickupStoreStateItems)) {
            return;
        }

        $dispatcher = $this->modelFactory->getObject('Ebay_Connector_Dispatcher');

        /** @var \Ess\M2ePro\Model\Ebay\Connector\AccountPickupStore\Synchronize\ProductsRequester $connector */
        $connector = $dispatcher->getConnector(
            'accountPickupStore',
            'synchronize',
            'productsRequester',
            [],
            null,
            $account
        );
        $connector->setPickupStoreStateItems($pickupStoreStateItems);
        $dispatcher->process($connector);
    }

    //########################################
}
