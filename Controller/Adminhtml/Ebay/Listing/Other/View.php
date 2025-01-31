<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Other;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Other\View
 */
class View extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Other
{
    public function execute()
    {
        $accountId = $this->getRequest()->getParam('account');
        $marketplaceId = $this->getRequest()->getParam('marketplace');

        if (empty($accountId) || empty($marketplaceId)) {
            $this->getMessageManager()->addErrorMessage($this->__('You should provide correct parameters.'));

            return $this->_redirect('*/*/index');
        }

        $this->getResultPage()->getConfig()->getTitle()->prepend($this->__('Unmanaged Listings'));

        $this->addContent($this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Other\View::class));

        return $this->getResult();
    }
}
