<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Log\Listing;

use Ess\M2ePro\Block\Adminhtml\Magento\Grid\AbstractContainer;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Log\Listing\AbstractView
 */
abstract class AbstractView extends AbstractContainer
{
    /** @var  \Ess\M2ePro\Block\Adminhtml\Log\Listing\View\Switcher */
    protected $viewModeSwitcherBlock;

    /** @var  \Ess\M2ePro\Block\Adminhtml\Account\Switcher  */
    protected $accountSwitcherBlock;

    /** @var  \Ess\M2ePro\Block\Adminhtml\Marketplace\Switcher  */
    protected $marketplaceSwitcherBlock;

    /** @var  \Ess\M2ePro\Block\Adminhtml\Log\UniqueMessageFilter  */
    protected $uniqueMessageFilterBlock;

    //#######################################

    abstract protected function getComponentMode();

    abstract protected function getFiltersHtml();

    //#######################################

    protected function _prepareLayout()
    {
        $this->viewModeSwitcherBlock    = $this->createViewModeSwitcherBlock();
        $this->accountSwitcherBlock     = $this->createAccountSwitcherBlock();
        $this->marketplaceSwitcherBlock = $this->createMarketplaceSwitcherBlock();
        $this->uniqueMessageFilterBlock = $this->createUniqueMessageFilterBlock();

        $gridClass = $this->nameBuilder->buildClassName([
            $this->getComponentMode(),
            'Log_Listing',
            \Ess\M2ePro\Block\Adminhtml\Listing\Search\TypeSwitcher::LISTING_TYPE_M2E_PRO,
            'View',
            $this->viewModeSwitcherBlock->getSelectedParam(),
            'Grid'
        ]);

        $this->addChild('grid', $this->getBlockClass($gridClass));

        $this->removeButton('add');

        $this->js->add(<<<JS
require(['M2ePro/Log/View'], function () {

    window.LogViewObj = new LogView();

    {$this->getChildBlock('grid')->getJsObjectName()}.initCallback = LogViewObj.processColorMapping;
    LogViewObj.processColorMapping();
});
JS
        );

        return parent::_prepareLayout();
    }

    protected function createViewModeSwitcherBlock()
    {
        return $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Log\Listing\View\Switcher::class)->setData([
            'component_mode' => $this->getComponentMode()
        ]);
    }

    protected function createAccountSwitcherBlock()
    {
        return $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Account\Switcher::class)->setData([
            'component_mode' => $this->getComponentMode(),
        ]);
    }

    protected function createMarketplaceSwitcherBlock()
    {
        return $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Marketplace\Switcher::class)->setData([
            'component_mode' => $this->getComponentMode(),
        ]);
    }

    protected function createUniqueMessageFilterBlock()
    {
        return $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Log\UniqueMessageFilter::class)->setData([
            'route' => "*/{$this->getComponentMode()}_log_listing_product/",
            'title' => $this->__('Only messages with a unique Product ID')
        ]);
    }

    protected function getStaticFilterHtml($label, $value)
    {
        return <<<HTML
<p class="static-switcher">
    <span>{$label}:</span>
    <span>{$value}</span>
</p>
HTML;
    }

    protected function _toHtml()
    {
        $pageActionsHtml = <<<HTML
<div class="page-main-actions">
    <div class="filter_block">
        {$this->viewModeSwitcherBlock->toHtml()}
        {$this->getFiltersHtml()}
    </div>
</div>
HTML;

        return $pageActionsHtml . parent::_toHtml();
    }

    //#######################################
}
