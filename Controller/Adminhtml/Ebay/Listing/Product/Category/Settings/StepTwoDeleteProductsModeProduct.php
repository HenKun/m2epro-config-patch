<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Product\Category\Settings;

use \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Product\Category\Settings;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Product\Category\Settings\StepTwoDeleteProductsModeProduct
 */
class StepTwoDeleteProductsModeProduct extends Settings
{

    //########################################

    public function execute()
    {
        $ids = $this->getRequestIds('products_id');
        $ids = array_map('intval', $ids);

        $sessionData = $this->getSessionValue('mode_product');
        foreach ($ids as $id) {
            unset($sessionData[$id]);
        }
        $this->setSessionValue('mode_product', $sessionData);

        $collection = $this->ebayFactory->getObject('Listing\Product')->getCollection()
            ->addFieldToFilter('id', ['in' => $ids]);

        foreach ($collection->getItems() as $listingProduct) {
            $listingProduct->delete();
        }

        $listingProductAddIds = $this->getEbayListingFromRequest()->getAddedListingProductsIds();
        if (empty($listingProductAddIds)) {
            return $this->getResult();
        }
        $listingProductAddIds = array_map('intval', $listingProductAddIds);
        $listingProductAddIds = array_diff($listingProductAddIds, $ids);

        $this->getEbayListingFromRequest()->setData(
            'product_add_ids',
            $this->getHelper('Data')->jsonEncode($listingProductAddIds)
        )->save();

        return $this->getResult();
    }

    //########################################
}
