<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Template;

use \Ess\M2ePro\Model\Amazon\Template\SellingFormat as AmazonTemplateSellingFormat;
use \Ess\M2ePro\Model\Ebay\Template\SellingFormat as EbayTemplateSellingFormat;
use \Ess\M2ePro\Model\Walmart\Template\SellingFormat as WalmartTemplateSellingFormat;

/**
 * Class \Ess\M2ePro\Model\Template\SellingFormat
 *
 * @method AmazonTemplateSellingFormat|EbayTemplateSellingFormat|WalmartTemplateSellingFormat getChildObject()
 */
class SellingFormat extends \Ess\M2ePro\Model\ActiveRecord\Component\Parent\AbstractModel
{
    const QTY_MODE_PRODUCT       = 1;
    const QTY_MODE_NUMBER        = 3;
    const QTY_MODE_ATTRIBUTE     = 4;
    const QTY_MODE_PRODUCT_FIXED = 5;

    const PRICE_MODE_NONE      = 0;
    const PRICE_MODE_PRODUCT   = 1;
    const PRICE_MODE_SPECIAL   = 2;
    const PRICE_MODE_ATTRIBUTE = 3;
    const PRICE_MODE_TIER      = 4;

    //########################################

    public function _construct()
    {
        parent::_construct();
        $this->_init(\Ess\M2ePro\Model\ResourceModel\Template\SellingFormat::class);
    }

    //########################################

    public function save($reloadOnCreate = false)
    {
        $this->getHelper('Data_Cache_Permanent')->removeTagValues('template_sellingformat');
        return parent::save($reloadOnCreate);
    }

    //########################################

    public function delete()
    {
        if ($this->isLocked()) {
            return false;
        }

        $this->getHelper('Data_Cache_Permanent')->removeTagValues('template_sellingformat');

        return parent::delete();
    }

    //########################################

    public function getTitle()
    {
        return $this->getData('title');
    }

    // ---------------------------------------

    public function getCreateDate()
    {
        return $this->getData('create_date');
    }

    public function getUpdateDate()
    {
        return $this->getData('update_date');
    }

    //########################################

    public function getCacheGroupTags()
    {
        return array_merge(parent::getCacheGroupTags(), ['template']);
    }

    //########################################

    public function isCacheEnabled()
    {
        return true;
    }

    //########################################
}
