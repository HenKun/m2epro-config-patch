<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Listing\Auto\Actions;

use Ess\M2ePro\Model\Exception\Logic;
use Ess\M2ePro\Model\Listing\Product;

/**
 * Class \Ess\M2ePro\Model\Listing\Auto\Actions\Listing
 */
abstract class Listing extends \Ess\M2ePro\Model\AbstractModel
{
    const INSTRUCTION_TYPE_STOP            = 'auto_actions_stop';
    const INSTRUCTION_TYPE_STOP_AND_REMOVE = 'auto_actions_stop_and_remove';

    const INSTRUCTION_INITIATOR = 'auto_actions';

    /**
     * @var null|\Ess\M2ePro\Model\Listing
     */
    private $listing = null;

    protected $activeRecordFactory;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    ) {
        $this->activeRecordFactory = $activeRecordFactory;
        parent::__construct($helperFactory, $modelFactory);
    }

    //########################################

    public function setListing(\Ess\M2ePro\Model\Listing $listing)
    {
        $this->listing = $listing;
    }

    /**
     * @return \Ess\M2ePro\Model\Listing
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    protected function getListing()
    {
        if (!($this->listing instanceof \Ess\M2ePro\Model\Listing)) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Property "Listing" should be set first.');
        }

        return $this->listing;
    }

    //########################################

    public function deleteProduct(\Magento\Catalog\Model\Product $product, $deletingMode)
    {
        if ($deletingMode == \Ess\M2ePro\Model\Listing::DELETING_MODE_NONE) {
            return;
        }

        $listingsProducts = $this->getListing()->getProducts(true, ['product_id'=>(int)$product->getId()]);

        if (count($listingsProducts) <= 0) {
            return;
        }

        foreach ($listingsProducts as $listingProduct) {
            if (!($listingProduct instanceof \Ess\M2ePro\Model\Listing\Product)) {
                return;
            }

            if ($deletingMode == \Ess\M2ePro\Model\Listing::DELETING_MODE_STOP && !$listingProduct->isStoppable()) {
                continue;
            }

            try {
                $instructionType = self::INSTRUCTION_TYPE_STOP;

                if ($deletingMode == \Ess\M2ePro\Model\Listing::DELETING_MODE_STOP_REMOVE) {
                    $instructionType = self::INSTRUCTION_TYPE_STOP_AND_REMOVE;
                }

                $instruction = $this->activeRecordFactory->getObject('Listing_Product_Instruction');
                $instruction->setData(
                    [
                        'listing_product_id' => $listingProduct->getId(),
                        'component'          => $listingProduct->getComponentMode(),
                        'type'               => $instructionType,
                        'initiator'          => self::INSTRUCTION_INITIATOR,
                        'priority'           => $listingProduct->isStoppable() ? 60 : 0,
                    ]
                );
                $instruction->save();
            } catch (\Exception $exception) {
                $this->getHelper('Module\Exception')->process($exception);
            }
        }
    }

    //########################################

    abstract public function addProductByCategoryGroup(
        \Magento\Catalog\Model\Product $product,
        \Ess\M2ePro\Model\Listing\Auto\Category\Group $categoryGroup
    );

    abstract public function addProductByGlobalListing(
        \Magento\Catalog\Model\Product $product,
        \Ess\M2ePro\Model\Listing $listing
    );

    abstract public function addProductByWebsiteListing(
        \Magento\Catalog\Model\Product $product,
        \Ess\M2ePro\Model\Listing $listing
    );

    //########################################

    /**
     * @param Product $listingProduct
     * @throws Logic
     */
    protected function logAddedToMagentoProduct(Product $listingProduct)
    {
        $tempLog = $this->activeRecordFactory->getObject('Listing\Log');
        $tempLog->setComponentMode($this->getListing()->getComponentMode());
        $tempLog->addProductMessage(
            $this->getListing()->getId(),
            $listingProduct->getProductId(),
            $listingProduct->getId(),
            \Ess\M2ePro\Helper\Data::INITIATOR_UNKNOWN,
            null,
            \Ess\M2ePro\Model\Listing\Log::ACTION_ADD_PRODUCT_TO_MAGENTO,
            'Product was Added',
            \Ess\M2ePro\Model\Log\AbstractModel::TYPE_INFO
        );
    }

    //########################################
}
