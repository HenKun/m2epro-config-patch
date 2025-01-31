<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model;

use \Ess\M2ePro\Model\Amazon\Account as AmazonAccount;
use \Ess\M2ePro\Model\Ebay\Account as EbayAccount;
use \Ess\M2ePro\Model\Walmart\Account as WalmartAccount;

/**
 * @method AmazonAccount|EbayAccount|WalmartAccount getChildObject()
 */
class Account extends \Ess\M2ePro\Model\ActiveRecord\Component\Parent\AbstractModel
{
    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init(\Ess\M2ePro\Model\ResourceModel\Account::class);
    }

    //########################################

    /**
     * @param bool $onlyMainConditions
     * @return bool
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function isLocked($onlyMainConditions = false)
    {
        if ($this->isComponentModeEbay() && $this->getChildObject()->isModeSandbox()) {
            return false;
        }

        if (!$onlyMainConditions && parent::isLocked()) {
            return true;
        }

        return (bool)$this->activeRecordFactory->getObject('Listing')
                            ->getCollection()
                            ->addFieldToFilter('account_id', $this->getId())
                            ->getSize();
    }

    //########################################

    public function save($reloadOnCreate = false)
    {
        $this->getHelper('Data_Cache_Permanent')->removeTagValues('account');
        return parent::save($reloadOnCreate);
    }

    //########################################

    public function delete()
    {
        if ($this->isLocked()) {
            return false;
        }

        $otherListings = $this->getOtherListings(true);
        foreach ($otherListings as $otherListing) {
            $otherListing->delete();
        }

        if ($this->isComponentModeEbay() && $this->getChildObject()->isModeSandbox()) {
            $listings = $this->getRelatedComponentItems('Listing', 'account_id', true);
            foreach ($listings as $listing) {
                $listing->delete();
            }
        }

        $orders = $this->getOrders(true);
        foreach ($orders as $order) {
            $order->delete();
        }

        $this->deleteChildInstance();

        $this->getHelper('Data_Cache_Permanent')->removeTagValues('account');
        return parent::delete();
    }

    //########################################

    /**
     * @param bool $asObjects
     * @param array $filters
     * @return array|\Ess\M2ePro\Model\Listing\Other[]
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getOtherListings($asObjects = false, array $filters = [])
    {
        $otherListings = $this->getRelatedComponentItems('Listing\Other', 'account_id', $asObjects, $filters);

        if ($asObjects) {
            foreach ($otherListings as $otherListing) {
                /** @var \Ess\M2ePro\Model\Listing\Other $otherListing */
                $otherListing->setAccount($this);
            }
        }

        return $otherListings;
    }

    /**
     * @param bool $asObjects
     * @param array $filters
     * @return array
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getOrders($asObjects = false, array $filters = [])
    {
        $orders = $this->getRelatedComponentItems('Order', 'account_id', $asObjects, $filters);

        if ($asObjects) {
            foreach ($orders as $order) {
                /** @var \Ess\M2ePro\Model\Order $order */
                $order->setAccount($this);
            }
        }

        return $orders;
    }

    //########################################

    public function getTitle()
    {
        return $this->getData('title');
    }

    public function getAdditionalData()
    {
        return $this->getData('additional_data');
    }

    /**
     * @return bool
     */
    public function isSingleAccountMode()
    {
        return $this->activeRecordFactory->getObject('Account')->getCollection()->getSize() <= 1;
    }

    //########################################

    public function isCacheEnabled()
    {
        return true;
    }

    //########################################
}
