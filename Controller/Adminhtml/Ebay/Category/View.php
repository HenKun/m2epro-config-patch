<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Category;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Ebay\Category\View
 */
class View extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Category
{
    //########################################

    public function execute()
    {
        /**
         * tabs widget makes an redundant ajax call for tab content by clicking on it even when tab is just a link
         */
        if ($this->isAjax()) {
            return;
        }

        $this->setRuleData('ebay_rule_category');
        $this->addContent($this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Ebay\Category\View::class));
        $this->getResultPage()->getConfig()->getTitle()->prepend($this->__('Edit Category'));
        $this->setPageHelpLink('x/c4FkB');

        return $this->getResult();
    }

    //########################################
}
