<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Transferring;

class AddProducts extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Main
{
    /** @var \Ess\M2ePro\Model\Listing $listing */
    private $listing;

    /** @var \Ess\M2ePro\Model\Ebay\Listing\Transferring $transferring */
    private $transferring;

    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Data $dataHelper,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context,
        \Ess\M2ePro\Model\Ebay\Listing\Transferring $transferring
    ) {
        parent::__construct($ebayFactory, $context);

        $this->transferring = $transferring;
        $this->dataHelper = $dataHelper;
    }

    public function execute()
    {
        $this->listing = $this->ebayFactory->getCachedObjectLoaded(
            'Listing',
            $this->getRequest()->getParam('listing_id')
        );

        $this->transferring->setListing($this->listing);

        /** @var \Ess\M2ePro\Model\Listing $targetListing */
        $targetListing = $this->ebayFactory->getCachedObjectLoaded(
            'Listing',
            $this->transferring->getTargetListingId()
        );

        $isDifferentMarketplaces = $targetListing->getMarketplaceId() != $this->listing->getMarketplaceId();

        $productsIds = $this->getRequest()->getParam('products');
        $productsIds = explode(',', $productsIds);
        $productsIds = array_unique($productsIds);
        $productsIds = array_filter($productsIds);

        $collection = $this->ebayFactory->getObject('Listing_Product')->getCollection();
        $collection->addFieldToFilter('id', ['in' => ($productsIds)]);

        $ids = [];
        foreach ($collection->getItems() as $sourceListingProduct) {
            /** @var \Ess\M2ePro\Model\Listing\Product $sourceListingProduct */
            $listingProduct = $targetListing->getChildObject()->addProductFromAnotherEbaySite(
                $sourceListingProduct,
                $this->listing
            );

            if (!($listingProduct instanceof \Ess\M2ePro\Model\Listing\Product)) {
                $this->transferring->setErrorsCount($this->transferring->getErrorsCount() + 1);
                continue;
            }

            $ids[] = $listingProduct->getId();
        }

        if ($isDifferentMarketplaces) {
            $existingIds = $targetListing->getChildObject()->getAddedListingProductsIds();
            $existingIds = array_values(array_unique(array_merge($existingIds, $ids)));

            $targetListing->getChildObject()->setData(
                'product_add_ids',
                $this->getHelper('Data')->jsonEncode($existingIds)
            );
            $targetListing->save();
        }

        if ($this->getRequest()->getParam('is_last_part')) {
            if ($this->transferring->getErrorsCount()) {
                $this->getMessageManager()->addErrorMessage(
                    $this->getHelper('Module_Translation')->__(
                        '%errors_count% product(s) were not added to the selected Listing.
                        Please view Log for the details.',
                        $this->transferring->getErrorsCount()
                    )
                );
            }

            $this->transferring->clearSession();
        }

        return $this->getResponse()->setBody($this->dataHelper->jsonEncode(['result' => 'success']));
    }
}
