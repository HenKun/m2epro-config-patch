<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Listing\Product\Variation\Individual;

use Ess\M2ePro\Controller\Adminhtml\Walmart\Main;
use Ess\M2ePro\Block\Adminhtml\Walmart\Listing\Product\Variation\Individual\Edit;

class GetEditPopup extends Main
{
    public function execute()
    {
        $listingProductId = (int)$this->getRequest()->getParam('listing_product_id');

        if (!$listingProductId) {
            $this->setJsonContent([
                'type' => 'error',
                'message' => $this->__('Listing Product must be specified.')
            ]);

            return $this->getResult();
        }

        $variationEditBlock = $this->getLayout()->createBlock(Edit::class)
            ->setData('listing_product_id', $listingProductId);

        $this->setJsonContent([
            'type' => 'success',
            'html' => $variationEditBlock->toHtml()
        ]);

        return $this->getResult();
    }
}
