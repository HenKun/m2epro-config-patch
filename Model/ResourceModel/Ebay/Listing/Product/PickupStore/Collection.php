<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Product\PickupStore;

/**
 * Class \Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Product\PickupStore\Collection
 */
class Collection extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Collection\AbstractModel
{
    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init(
            \Ess\M2ePro\Model\Ebay\Listing\Product\PickupStore::class,
            \Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Product\PickupStore::class
        );
    }

    //########################################
}
