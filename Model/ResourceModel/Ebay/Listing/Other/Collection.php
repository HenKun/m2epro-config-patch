<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Other;

/**
 * Class \Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Other\Collection
 */
class Collection extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Collection\Component\Child\AbstractModel
{
    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init(
            \Ess\M2ePro\Model\Ebay\Listing\Other::class,
            \Ess\M2ePro\Model\ResourceModel\Ebay\Listing\Other::class
        );
    }

    //########################################
}
