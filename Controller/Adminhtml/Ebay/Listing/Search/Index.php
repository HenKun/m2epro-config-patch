<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Search;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Search\Index
 */
class Index extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing
{
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Ess_M2ePro::ebay_listings_search');
    }

    public function execute()
    {
        if ($this->isAjax()) {
            $listingType = $this->getRequest()->getParam('listing_type', false);

            if ($listingType == \Ess\M2ePro\Block\Adminhtml\Listing\Search\TypeSwitcher::LISTING_TYPE_LISTING_OTHER) {
                $gridBlock = \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Search\Other\Grid::class;
            } else {
                $gridBlock = \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Search\Product\Grid::class;
            }

            $this->setAjaxContent(
                $this->getLayout()->createBlock($gridBlock)
            );
            return $this->getResult();
        }

        $this->addContent($this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Search::class));
        $this->getResultPage()->getConfig()->getTitle()->prepend($this->__('Search Products'));
        $this->setPageHelpLink('x/Ev8UB');

        return $this->getResult();
    }
}
