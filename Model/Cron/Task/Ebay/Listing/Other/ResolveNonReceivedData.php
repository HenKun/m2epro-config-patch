<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Cron\Task\Ebay\Listing\Other;

class ResolveNonReceivedData extends \Ess\M2ePro\Model\Cron\Task\AbstractModel
{
    const NICK = 'ebay/listing/other/resolve_nonReceived_data';

    /** @var \Ess\M2ePro\Helper\Component\Ebay\Category\Ebay */
    private $componentEbayCategoryEbay;

    public function __construct(
        \Ess\M2ePro\Helper\Component\Ebay\Category\Ebay $componentEbayCategoryEbay,
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

        $this->componentEbayCategoryEbay = $componentEbayCategoryEbay;
    }

    public function isPossibleToRun()
    {
        if ($this->getHelper('Server\Maintenance')->isNow()) {
            return false;
        }

        return parent::isPossibleToRun();
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Synchronization\Log
     */
    protected function getSynchronizationLog()
    {
        $synchronizationLog = parent::getSynchronizationLog();

        $synchronizationLog->setComponentMode(\Ess\M2ePro\Helper\Component\Ebay::NICK);
        $synchronizationLog->setSynchronizationTask(\Ess\M2ePro\Model\Synchronization\Log::TASK_OTHER_LISTINGS);

        return $synchronizationLog;
    }

    //########################################

    protected function performActions()
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Account\Collection $accountsCollection */
        $accountsCollection = $this->parentFactory->getObject(
            \Ess\M2ePro\Helper\Component\Ebay::NICK,
            'Account'
        )->getCollection();
        $accountsCollection->addFieldToFilter('other_listings_synchronization', 1);

        foreach ($accountsCollection->getItems() as $account) {
            /** @var \Ess\M2ePro\Model\Account $account **/

            $this->getOperationHistory()->addTimePoint(
                __METHOD__.'process'.$account->getId(),
                'Get and process SKUs for Account '.$account->getTitle()
            );

            try {
                $this->updateItems($account);
            } catch (\Exception $exception) {
                $message = $this->getHelper('Module\Translation')->__(
                    'The "Update SKUs" Action for eBay Account "%account%" was completed with error.',
                    $account->getTitle()
                );

                $this->processTaskAccountException($message, __FILE__, __LINE__);
                $this->processTaskException($exception);
            }

            $this->getOperationHistory()->saveTimePoint(__METHOD__.'process'.$account->getId());
        }
    }

    //########################################

    protected function updateItems(\Ess\M2ePro\Model\Account $account)
    {
        /** @var \Ess\M2ePro\Model\ResourceModel\Listing\Other\Collection $listingOtherCollection */
        $listingOtherCollection = $this->parentFactory->getObject(
            \Ess\M2ePro\Helper\Component\Ebay::NICK,
            'Listing\Other'
        )->getCollection();
        $listingOtherCollection->addFieldToFilter('main_table.account_id', (int)$account->getId());
        $listingOtherCollection->getSelect()->where('`second_table`.`sku` IS NULL');
        $listingOtherCollection->getSelect()->orWhere('`second_table`.`online_main_category` IS NULL');
        $listingOtherCollection->getSelect()->orWhere('`second_table`.`online_categories_data` IS NULL');
        $listingOtherCollection->getSelect()->order('second_table.start_date ASC');
        $listingOtherCollection->getSelect()->limit(200);

        if (!$listingOtherCollection->getSize()) {
            return;
        }

        $receivedData = $this->receiveFromEbay(
            $account,
            $listingOtherCollection->getFirstItem()->getChildObject()->getData('start_date')
        );

        $listingOthers = [];
        foreach ($listingOtherCollection->getItems() as $item) {
            /** @var \Ess\M2ePro\Model\Listing\Other $item */
            $listingOthers[(string)$item->getChildObject()->getData('item_id')] = $item;
        }

        if (empty($receivedData['items'])) {
            $this->updateNotReceivedItems($listingOthers, null);
            return;
        }

        $this->updateReceivedItems($listingOthers, $account, $receivedData['items']);
        $this->updateNotReceivedItems($listingOthers, $receivedData['to_time']);
    }

    // ---------------------------------------

    /**
     * @param \Ess\M2ePro\Model\Listing\Other[] $listingOthers
     * @param \Ess\M2ePro\Model\Account $account
     * @param array $items
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function updateReceivedItems($listingOthers, \Ess\M2ePro\Model\Account $account, array $items)
    {
        /** @var \Ess\M2ePro\Model\Ebay\Listing\Other\Mapping $mappingModel */
        $mappingModel = $this->modelFactory->getObject('Ebay_Listing_Other_Mapping');

        foreach ($items as $item) {
            if (!isset($listingOthers[$item['id']])) {
                continue;
            }

            /** @var \Ess\M2ePro\Model\Listing\Other $listingOther */
            $listingOther = $listingOthers[$item['id']];

            $newData = [
                'sku' => (string)$item['sku']
            ];

            if (!empty($item['categories'])) {
                $categories = [
                    'category_main_id'            => 0,
                    'category_secondary_id'       => 0,
                    'store_category_main_id'      => 0,
                    'store_category_secondary_id' => 0,
                ];

                foreach ($categories as $categoryKey => &$categoryValue) {
                    if (!empty($item['categories'][$categoryKey])) {
                        $categoryValue = $item['categories'][$categoryKey];
                    }
                }

                unset($categoryValue);

                $categoryPath = $this->componentEbayCategoryEbay->getPath(
                    $categories['category_main_id'],
                    $listingOther->getMarketplaceId()
                );

                $newData['online_main_category'] = $categoryPath.' ('.$categories['category_main_id'].')';
                $newData['online_categories_data'] = $this->helperData->jsonEncode($categories);
            }

            $listingOther->getChildObject()->addData($newData);
            $listingOther->getChildObject()->save();

            if ($account->getChildObject()->isOtherListingsMappingEnabled()) {
                $mappingModel->initialize($account);
                $mappingModel->autoMapOtherListingProduct($listingOther);
            }
        }
    }

    protected function updateNotReceivedItems($listingOthers, $toTimeReceived)
    {
        foreach ($listingOthers as $listingOther) {
            /** @var \Ess\M2ePro\Model\Ebay\Listing\Other $ebayListingOther */
            $ebayListingOther = $listingOther->getChildObject();

            $sku = $ebayListingOther->getSku();
            $onlineMainCategory = $ebayListingOther->getOnlineMainCategory();
            $onlineCategoriesData = $ebayListingOther->getOnlineCategoriesData();

            if ($sku !== null && $onlineMainCategory !== null && $onlineCategoriesData !== null) {
                continue;
            }

            $startDateTimestamp = (int)$this->helperData
                ->createGmtDateTime($ebayListingOther->getStartDate())
                ->format('U');
            if ($toTimeReceived !== null &&
                $startDateTimestamp >= (int)$this->helperData->createGmtDateTime($toTimeReceived)->format('U')
            ) {
                continue;
            }

            $onlineMainCategory === null && $ebayListingOther->setData('online_main_category', '');
            $onlineCategoriesData === null && $ebayListingOther->setData('online_categories_data', '');
            $sku === null && $ebayListingOther->setData('sku', '');

            $ebayListingOther->save();
        }
    }

    //########################################

    protected function receiveFromEbay(\Ess\M2ePro\Model\Account $account, $sinceTime)
    {
        $sinceTime = new \DateTime($sinceTime, new \DateTimeZone('UTC'));
        $sinceTime->modify('-1 minute');
        $sinceTime = $sinceTime->format('Y-m-d H:i:s');

        $inputData = [
            'since_time'    => $sinceTime,
            'only_one_page' => true,
            'realtime'      => true
        ];

        $dispatcherObj = $this->modelFactory->getObject('Ebay_Connector_Dispatcher');
        $connectorObj = $dispatcherObj->getVirtualConnector(
            'inventory',
            'get',
            'items',
            $inputData,
            null,
            null,
            $account->getId()
        );

        $dispatcherObj->process($connectorObj);
        $responseData = $connectorObj->getResponseData();

        if (!isset($responseData['items']) || !is_array($responseData['items'])) {
            return [];
        }

        return $responseData;
    }

    //########################################
}
