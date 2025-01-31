<?php
/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Plugin\MSI\Magento\InventorySales\Model\ResourceModel;

use Magento\InventoryApi\Api\StockRepositoryInterface;
use Magento\InventorySalesApi\Model\GetAssignedSalesChannelsForStockInterface;

/**
 * Class \Ess\M2ePro\Plugin\MSI\Magento\InventorySales\Model\ResourceModel\ReplaceSalesChannelsDataForStock
 */
class ReplaceSalesChannelsDataForStock extends \Ess\M2ePro\Plugin\AbstractPlugin
{
    /** @var \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory */
    protected $activeRecordFactory;

    /** @var \Ess\M2ePro\Model\MSI\AffectedProducts */
    protected $msiAffectedProducts;

    /** @var \Ess\M2ePro\PublicServices\Product\SqlChange */
    protected $publicService;

    // ---------------------------------------

    /** @var StockRepositoryInterface */
    protected $stockRepository;

    /** @var \Magento\InventorySalesApi\Model\GetAssignedSalesChannelsForStockInterface */
    protected $getAssignedChannels;

    //########################################

    public function __construct(
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\MSI\AffectedProducts $msiAffectedProducts,
        \Ess\M2ePro\PublicServices\Product\SqlChange $publicService,
        \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        parent::__construct($helperFactory, $modelFactory);
        $this->activeRecordFactory = $activeRecordFactory;
        $this->msiAffectedProducts = $msiAffectedProducts;
        $this->publicService = $publicService;

        $this->stockRepository = $objectManager->get(StockRepositoryInterface::class);
        $this->getAssignedChannels = $objectManager->get(GetAssignedSalesChannelsForStockInterface::class);
    }

    //########################################

    /**
     * @param $interceptor
     * @param \Closure $callback
     * @param array ...$arguments
     * @return mixed
     */
    public function aroundExecute($interceptor, \Closure $callback, ...$arguments)
    {
        return $this->execute('execute', $interceptor, $callback, $arguments);
    }

    /**
     * @param $interceptor
     * @param \Closure $callback
     * @param array $arguments
     * @return mixed
     */
    public function processExecute($interceptor, \Closure $callback, array $arguments)
    {
        $stockId        = $arguments[1];
        $channelsAfter  = $arguments[0];
        $channelsBefore = $this->getAssignedChannels->execute($stockId);

        $result = $callback(...$arguments);

        /** @var \Magento\InventorySalesApi\Api\Data\SalesChannelInterface[] $addedChannels */
        $addedChannels = $this->getOnlyAddedChannels($channelsBefore, $channelsAfter);
        if (empty($addedChannels)) {
            return $result;
        }

        $stock = $this->stockRepository->get($stockId);

        foreach ($addedChannels as $addedChannel) {
            foreach ($this->msiAffectedProducts->getAffectedListingsByChannel($addedChannel->getCode()) as $listing) {
                foreach ($listing->getChildObject()->getResource()->getUsedProductsIds($listing->getId()) as $prId) {
                    $this->publicService->markQtyWasChanged($prId);
                }
                $this->logListingMessage($listing, $addedChannel, $stock);
            }
        }
        $this->publicService->applyChanges();

        return $result;
    }

    /**
     * @param \Magento\InventorySalesApi\Api\Data\SalesChannelInterface[] $oldChannels
     * @param \Magento\InventorySalesApi\Api\Data\SalesChannelInterface[] $newChannels
     * @return array
     */
    private function getOnlyAddedChannels(array $oldChannels, array $newChannels)
    {
        $oldCodes = [];

        foreach ($oldChannels as $oldChannel) {
            $oldCodes[] = $oldChannel->getCode();
        }

        return array_filter($newChannels, function ($channel) use ($oldCodes) {
            return !in_array($channel->getCode(), $oldCodes, true);
        });
    }

    //########################################

    private function logListingMessage(
        \Ess\M2ePro\Model\Listing $listing,
        \Magento\InventorySalesApi\Api\Data\SalesChannelInterface $channel,
        \Magento\InventoryApi\Api\Data\StockInterface $stock
    ) {
        /** @var \Ess\M2ePro\Model\Listing\Log $log */
        $log = $this->activeRecordFactory->getObject('Listing\Log');
        $log->setComponentMode($listing->getComponentMode());

        $log->addListingMessage(
            $listing->getId(),
            \Ess\M2ePro\Helper\Data::INITIATOR_EXTENSION,
            null,
            null,
            $this->getHelper('Module\Log')->encodeDescription(
                'Website "%website%" has been linked with stock "%stock%".',
                ['!website' => $channel->getCode(), '!stock' => $stock->getName()]
            ),
            \Ess\M2ePro\Model\Log\AbstractModel::TYPE_INFO
        );
    }

    //########################################
}
