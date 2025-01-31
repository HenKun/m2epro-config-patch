<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Account\PickupStore;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Account\PickupStore\Index
 */
class Index extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Account
{
    //########################################

    public function execute()
    {
        if (!$this->getRequest()->getParam('account_id')) {
            return $this->_redirect('*/ebay_account/index');
        }

        if ($this->isAjax()) {
            $this->setAjaxContent(
                $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Ebay\Account\PickupStore\Grid::class)
            );
            return $this->getResult();
        }

        $account = $this->ebayFactory->getObjectLoaded(
            'Account',
            (int)$this->getRequest()->getParam('account_id')
        );
        $this->addContent($this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Ebay\Account\PickupStore::class));
        $this->getResultPage()->getConfig()->getTitle()->prepend(
            $this->__('My Stores for account "%s%"', $account->getTitle())
        );

        return $this->getResultPage();
    }

    //########################################
}
