<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\PickupStore\Step\Stores;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\PickupStore\Step\Stores\Wrapper
 */
class Wrapper extends \Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('ebayListingPickupStoreStoresWrapper');
        // ---------------------------------------
    }

    protected function _toHtml()
    {
        $helpBlock = $this->getLayout()->createBlock(
            \Ess\M2ePro\Block\Adminhtml\HelpBlock::class,
            '',
            ['data' => [
                'content' => $this->__('
                In this section, you can <strong>review</strong> Store and Product details as well as Product Quantity
                and Logs.<br/>
                Press <strong>Assign Products to Stores</strong> button to add new Products to the selected Store for
                In-Store Pickup Service.<br/>
                If you want to <strong>unassign</strong> the Product from the Store you can use a
                <strong>Unassign Option</strong> from the Actions bulk at the top of the Grid.
                ')]
            ]
        );

        $breadcrumb = $this->getLayout()
                           ->createBlock(\Ess\M2ePro\Block\Adminhtml\Ebay\Listing\PickupStore\Breadcrumb::class);
        $breadcrumb->setSelectedStep(2);

        $grid = $this->getLayout()
                     ->createBlock(\Ess\M2ePro\Block\Adminhtml\Ebay\Listing\PickupStore\Step\Stores\Grid::class);

        return $helpBlock->toHtml() . $breadcrumb->toHtml() . parent::_toHtml() . $grid->toHtml();
    }

    //########################################
}
