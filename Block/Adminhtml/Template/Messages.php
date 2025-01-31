<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Template;

use Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Template\Messages
 */
class Messages extends AbstractBlock
{
    protected $templateNick;
    protected $componentMode;

    //########################################

    public function getResultBlock($templateNick, $componentMode)
    {
        $block = $this;

        if ($templateNick == \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_SHIPPING &&
            $componentMode == \Ess\M2ePro\Helper\Component\Ebay::NICK
        ) {
            $block = $this->getLayout()
                          ->createBlock(\Ess\M2ePro\Block\Adminhtml\Ebay\Template\Shipping\Messages::class);
        }

        if ($templateNick == \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_SELLING_FORMAT) {
            $block = $this->getLayout()
                          ->createBlock(\Ess\M2ePro\Block\Adminhtml\Template\SellingFormat\Messages::class);
        }

        $block->setComponentMode($componentMode);
        $block->setTemplateNick($templateNick);

        return $block;
    }

    //########################################

    public function getMessages()
    {
        return [];
    }

    //########################################

    public function getMessagesHtml(array $messages = [])
    {
        if (empty($messages)) {
            $messages = $this->getMessages();
        }

        if (empty($messages)) {
            return '';
        }

        $messagesBlock = $this->getLayout()->createBlock(\Magento\Framework\View\Element\Messages::class);

        $first = true;
        foreach ($messages as $messageType => $messageText) {
            $message = '';
            if ($first) {
                $first = false;
                $message .= <<<HTML
<div style="display: inline-block; float: right;">
    <a href="javascript: void(0);" class="refresh-messages">[{$this->__('Refresh')}]</a>
</div>
HTML;
            }
            $message .= $messageText;
            $messagesBlock->addWarning($message);
        }

        return $messagesBlock->toHtml();
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Marketplace|null
     */
    public function getMarketplace()
    {
        if (!isset($this->_data['marketplace_id'])) {
            return null;
        }

        return $this->parentFactory->getCachedObjectLoaded(
            $this->getComponentMode(),
            'Marketplace',
            (int)$this->_data['marketplace_id']
        );
    }

    //########################################

    /**
     * @return \Magento\Store\Model\Store|null
     */
    public function getStore()
    {
        if (!isset($this->_data['store_id'])) {
            return null;
        }

        return $this->_storeManager->getStore((int)$this->_data['store_id']);
    }

    //########################################

    public function setTemplateNick($templateNick)
    {
        $this->templateNick = $templateNick;
        return $this;
    }

    public function getTemplateNick()
    {
        if ($this->templateNick) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Policy nick is not set.');
        }

        return $this->templateNick;
    }

    //########################################

    public function setComponentMode($componentMode)
    {
        $this->componentMode = $componentMode;
        return $this;
    }

    public function getComponentMode()
    {
        if ($this->componentMode === null) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Component Mode is not set.');
        }

        return $this->componentMode;
    }

    //########################################

    protected function getTemplateData()
    {
        if (empty($this->_data['template_data']) || !is_array($this->_data['template_data'])) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Policy data is not set.');
        }

        return $this->_data['template_data'];
    }

    //########################################

    protected function getListingProductIds()
    {
        $listingProductIds = $this->getRequest()->getParam('listing_product_ids', '');
        $listingProductIds = explode(',', $listingProductIds);

        return $listingProductIds ? $listingProductIds : [];
    }

    //########################################

    protected function canDisplayAttributesAvailabilityMessage()
    {
        if (!$this->getRequest()->getParam('check_attributes_availability')) {
            return false;
        }

        if ($this->componentMode === null || $this->componentMode != \Ess\M2ePro\Helper\Component\Ebay::NICK) {
            return false;
        }

        $listingProductIds = $this->getListingProductIds();

        if (empty($listingProductIds)) {
            return false;
        }

        return true;
    }

    //########################################
}
