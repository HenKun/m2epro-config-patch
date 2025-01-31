<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing;

class GoToEbay extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing
{
    /** @var \Ess\M2ePro\Helper\Component\Ebay */
    private $ebayHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Component\Ebay $ebayHelper,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($ebayFactory, $context);

        $this->ebayHelper = $ebayHelper;
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Ess_M2ePro::ebay_listings_m2epro') ||
               $this->_authorization->isAllowed('Ess_M2ePro::ebay_listings_other');
    }

    public function execute()
    {
        $itemId = $this->getRequest()->getParam('item_id');
        $accountId = $this->getRequest()->getParam('account_id');
        $marketplaceId = $this->getRequest()->getParam('marketplace_id');

        if ($itemId === null || $accountId === null || $marketplaceId === null) {
            $this->messageManager->addError($this->__('Requested eBay Item ID is not found.'));
            $this->_redirect('*/*/index');
            return;
        }

        $accountMode = $this->ebayFactory->getObjectLoaded('Account', $accountId)
            ->getChildObject()
            ->getMode();

        $url = $this->ebayHelper->getItemUrl(
            $itemId,
            $accountMode,
            $marketplaceId
        );

        return $this->_redirect($url);
    }
}
