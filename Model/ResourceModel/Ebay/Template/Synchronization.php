<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\ResourceModel\Ebay\Template;

/**
 * Class \Ess\M2ePro\Model\ResourceModel\Ebay\Template\Synchronization
 */
class Synchronization extends \Ess\M2ePro\Model\ResourceModel\ActiveRecord\Component\Child\AbstractModel
{
    protected $_isPkAutoIncrement = false;

    //########################################

    public function _construct()
    {
        $this->_init('m2epro_ebay_template_synchronization', 'template_synchronization_id');
        $this->_isPkAutoIncrement = false;
    }

    //########################################
}
