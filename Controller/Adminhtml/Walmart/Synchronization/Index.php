<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Synchronization;

use Ess\M2ePro\Controller\Adminhtml\Walmart\Settings;

class Index extends Settings
{
    public function execute()
    {
        // Remove when Magento fix Horizontal Tabs bug
        if ($this->getRequest()->isXmlHttpRequest()) {
            $block = $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Walmart\Synchronization::class)
                                       ->toHtml();
            $this->setAjaxContent($block);

            return $this->getResult();
        }

        $block = $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Synchronization\Tabs::class);
        $block->setData('active_tab', \Ess\M2ePro\Block\Adminhtml\Walmart\Synchronization\Tabs::TAB_ID_GENERAL);

        $this->addContent($block);

        $this->resultPage->getConfig()->getTitle()->prepend($this->__('Synchronization'));
        $this->resultPage->getConfig()->getTitle()->prepend($this->__('General'));

        return $this->resultPage;
    }
}
