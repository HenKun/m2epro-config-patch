<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Account\PickupStore\Edit;

use Ess\M2ePro\Block\Adminhtml\Magento\Tabs\AbstractTabs;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Ebay\Account\PickupStore\Edit\Tabs
 */
class Tabs extends AbstractTabs
{
    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('ebayAccountPickupStoreEditTabs');
        $this->setDestElementId('edit_form');
        // ---------------------------------------
    }

    //########################################

    protected function _prepareLayout()
    {
        $this->css->addFile('ebay/account/pickup_store.css');

        return parent::_prepareLayout();
    }

    //########################################

    protected function _beforeToHtml()
    {
        $this->addTab('general', [
            'label'   => $this->__('General'),
            'title'   => $this->__('General'),
            'content' => $this->getLayout()
                          ->createBlock(\Ess\M2ePro\Block\Adminhtml\Ebay\Account\PickupStore\Edit\Tabs\General::class)
                          ->toHtml(),
        ]);

        $this->addTab('location', [
            'label'   => $this->__('Location'),
            'title'   => $this->__('Location'),
            'content' => $this->getLayout()
                          ->createBlock(\Ess\M2ePro\Block\Adminhtml\Ebay\Account\PickupStore\Edit\Tabs\Location::class)
                          ->toHtml(),
        ]);

        $this->addTab('business_hours', [
            'label'   => $this->__('Business Hours'),
            'title'   => $this->__('Business Hours'),
            'content' => $this->getLayout()
                      ->createBlock(\Ess\M2ePro\Block\Adminhtml\Ebay\Account\PickupStore\Edit\Tabs\BusinessHours::class)
                      ->toHtml(),
        ]);

        $this->addTab('stock_settings', [
            'label'   => $this->__('Quantity Settings'),
            'title'   => $this->__('Quantity Settings'),
            'content' => $this->getLayout()
                      ->createBlock(\Ess\M2ePro\Block\Adminhtml\Ebay\Account\PickupStore\Edit\Tabs\StockSettings::class)
                      ->toHtml(),
        ]);

        $this->setActiveTab($this->getRequest()->getParam('tab', 'general'));

        return parent::_beforeToHtml();
    }

    //########################################
}
