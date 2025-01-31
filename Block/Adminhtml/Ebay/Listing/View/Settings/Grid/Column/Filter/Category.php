<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\View\Settings\Grid\Column\Filter;

class Category extends \Ess\M2ePro\Block\Adminhtml\Magento\Grid\Column\Filter\AbstractFilter
{
    /** @var \Ess\M2ePro\Helper\Component\Ebay\Category */
    private $componentEbayCategory;

    public function __construct(
        \Ess\M2ePro\Helper\Component\Ebay\Category $componentEbayCategory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Magento\Backend\Block\Context $context,
        \Magento\Framework\DB\Helper $resourceHelper,
        array $data = []
    ) {
        parent::__construct($helperFactory, $modelFactory, $context, $resourceHelper, $data);

        $this->componentEbayCategory = $componentEbayCategory;
    }

    protected function _renderOption($option, $value)
    {
        $selected = (($option['value'] == $value && ($value !== null)) ? ' selected="selected"' : '' );
        return '<option value="'. $this->escapeHtml($option['value']).'"'.$selected.'>'
                    .$this->escapeHtml($option['label'])
              .'</option>';
    }

    public function getHtml()
    {
        $value = $this->getValue('select');
        $optionsHtml = '';

        foreach ($this->_getOptions() as $option) {
            $optionsHtml .= $this->_renderOption($option, $value);
        }

        $label = $this->componentEbayCategory
            ->getCategoryTitle(\Ess\M2ePro\Helper\Component\Ebay\Category::TYPE_EBAY_MAIN);
        $label = $this->__('%1% Assigned', $label);

        $html = <<<HTML
<div>
    <input type="text" name="{$this->_getHtmlName()}[input]" id="{$this->_getHtmlId()}_input"
           value="{$this->getEscapedValue('input')}" class="input-text admin__control-text no-changes"/>
</div>
<div style="padding: 5px 0; text-align: right; font-weight: normal">
    <label>{$label}</label>
    <select class="admin__control-select"
            style="width: 75px"
            name="{$this->_getHtmlName()}[select]" id="{$this->_getHtmlId()}_select">
        {$optionsHtml}
    </select>
</div>
HTML;

        return parent::getHtml() . $html;
    }

    protected function _getOptions()
    {
        return [
            [
                'label' => $this->__('Any'),
                'value' => ''
            ],
            [
                'label' => $this->__('Yes'),
                'value' => 1
            ],
            [
                'label' => $this->__('No'),
                'value' => 0
            ],
        ];
    }

    //########################################
}
