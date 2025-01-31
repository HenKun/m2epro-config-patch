<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Template;

use Ess\M2ePro\Block\Adminhtml\Magento\AbstractBlock;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Ebay\Listing\Template\Switcher
 */
class Switcher extends AbstractBlock
{
    const MODE_LISTING_PRODUCT = 1;
    const MODE_COMMON = 2;

    const MAX_TEMPLATE_ITEMS_COUNT = 10000;

    protected $_template = 'ebay/listing/template/switcher.phtml';

    private $templates = null;
    /** @var \Ess\M2ePro\Helper\Data\GlobalData */
    private $globalDataHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Data\GlobalData $globalDataHelper,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->globalDataHelper = $globalDataHelper;
    }

    public function _construct()
    {
        $this->setId('ebayListingTemplateSwitcher');
        parent::_construct();
    }

    //########################################

    public function getHeaderText()
    {
        if ($this->getData('custom_header_text')) {
            return $this->getData('custom_header_text');
        }

        $title = '';

        switch ($this->getTemplateNick()) {
            case \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_SHIPPING:
                $title = $this->__('Shipping');
                break;
            case \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_RETURN_POLICY:
                $title = $this->__('Return');
                break;
            case \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_SELLING_FORMAT:
                $title = $this->__('Selling');
                break;
            case \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_DESCRIPTION:
                $title = $this->__('Description');
                break;
            case \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_SYNCHRONIZATION:
                $title = $this->__('Synchronization');
                break;
        }

        return $title;
    }

    //########################################

    public function getHeaderWidth()
    {
        switch ($this->getTemplateNick()) {
            case \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_RETURN_POLICY:
            case \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_SHIPPING:
                $width = 100;
                break;

            case \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_SELLING_FORMAT:
            case \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_DESCRIPTION:
                $width = 250;
                break;

            case \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_SYNCHRONIZATION:
                $width = 170;
                break;

            default:
                $width = 130;
                break;
        }

        return $width;
    }

    //########################################

    public function getTemplateNick()
    {
        if (!isset($this->_data['template_nick'])) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Template nick is not defined.');
        }

        return $this->_data['template_nick'];
    }

    public function getTemplateMode()
    {
        $templateMode = $this->globalDataHelper->getValue(
            'ebay_template_mode_' . $this->getTemplateNick()
        );

        if ($templateMode === null) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Template Mode is not initialized.');
        }

        return $templateMode;
    }

    public function getTemplateId()
    {
        $template = $this->getTemplateObject();

        if ($template === null) {
            return null;
        }

        return $template->getId();
    }

    public function getTemplateObject()
    {
        $template = $this->globalDataHelper->getValue('ebay_template_' . $this->getTemplateNick());

        if ($template !== null && $template->getId() !== null) {
            return $template;
        }

        return null;
    }

    // ---------------------------------------

    public function isTemplateModeParentForced()
    {
        $key = 'ebay_template_force_parent_' . $this->getTemplateNick();
        $forcedParent = $this->globalDataHelper->getValue($key);

        return (bool)$forcedParent;
    }

    public function isTemplateModeParent()
    {
        return $this->getTemplateMode() == \Ess\M2ePro\Model\Ebay\Template\Manager::MODE_PARENT;
    }

    public function isTemplateModeCustom()
    {
        return $this->getTemplateMode() == \Ess\M2ePro\Model\Ebay\Template\Manager::MODE_CUSTOM;
    }

    public function isTemplateModeTemplate()
    {
        return $this->getTemplateMode() == \Ess\M2ePro\Model\Ebay\Template\Manager::MODE_TEMPLATE;
    }

    //########################################

    public function getFormDataBlock()
    {
        $blockName = null;

        switch ($this->getTemplateNick()) {
            case \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_RETURN_POLICY:
                $blockName = \Ess\M2ePro\Block\Adminhtml\Ebay\Template\ReturnPolicy\Edit\Form\Data::class;
                break;
            case \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_SHIPPING:
                $blockName = \Ess\M2ePro\Block\Adminhtml\Ebay\Template\Shipping\Edit\Form\Data::class;
                break;
            case \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_SELLING_FORMAT:
                $blockName = \Ess\M2ePro\Block\Adminhtml\Ebay\Template\SellingFormat\Edit\Form\Data::class;
                break;
            case \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_DESCRIPTION:
                $blockName = \Ess\M2ePro\Block\Adminhtml\Ebay\Template\Description\Edit\Form\Data::class;
                break;
            case \Ess\M2ePro\Model\Ebay\Template\Manager::TEMPLATE_SYNCHRONIZATION:
                $blockName = \Ess\M2ePro\Block\Adminhtml\Ebay\Template\Synchronization\Edit\Form\Data::class;
                break;
        }

        if ($blockName === null) {
            throw new \Ess\M2ePro\Model\Exception\Logic(
                sprintf('Form data Block for Template nick "%s" is unknown.', $this->getTemplateNick())
            );
        }

        $parameters = [
            'is_custom'           => $this->isTemplateModeCustom(),
            'custom_title'        => $this->globalDataHelper->getValue('ebay_custom_template_title'),
            'policy_localization' => $this->getData('policy_localization'),
        ];

        return $this->getLayout()->createBlock($blockName, '', ['data' => $parameters]);
    }

    public function getFormDataBlockHtml($templateDataForce = false)
    {
        $nick = $this->getTemplateNick();

        if ($this->isTemplateModeCustom() || $templateDataForce) {
            $html = $this->getFormDataBlock()->toHtml();
            $style = '';
        } else {
            $html = '';
            $style = 'display: none;';
        }

        return <<<HTML
<div id="template_{$nick}_data_container" class="template-data-container" style="{$style}">
    {$html}
</div>
HTML;
    }

    //########################################

    public function canDisplaySwitcher()
    {
        if (!$this->canDisplayUseDefaultOption() && $this->getTemplatesCount() === 0) {
            return false;
        }

        return true;
    }

    public function canDisplayUseDefaultOption()
    {
        $displayUseDefaultOption = $this->globalDataHelper->getValue('ebay_display_use_default_option');

        if ($displayUseDefaultOption === null) {
            return true;
        }

        return (bool)$displayUseDefaultOption;
    }

    //########################################

    public function getTemplates()
    {
        if ($this->templates !== null) {
            return $this->templates;
        }

        $collection = $this->getTemplatesCollection();
        $collection->getSelect()->limit(self::MAX_TEMPLATE_ITEMS_COUNT);

        $this->templates = $collection->getItems();

        $currentTemplateOfListing = $this->getTemplateObject();
        if (!empty($currentTemplateOfListing) && !$this->isExistTemplate($currentTemplateOfListing->getId())) {
            $this->templates[$currentTemplateOfListing->getId()] = $currentTemplateOfListing;
        }

        return $this->templates;
    }

    protected function isExistTemplate($templateId)
    {
        if (array_key_exists($templateId, $this->templates)) {
            return true;
        }

        return false;
    }

    public function getTemplatesCount()
    {
        return $this->getTemplatesCollection()->getSize();
    }

    protected function getTemplatesCollection()
    {
        $manager = $this->modelFactory->getObject('Ebay_Template_Manager')->setTemplate($this->getTemplateNick());

        $collection = $manager->getTemplateModel()
                              ->getCollection()
                              ->addFieldToFilter('is_custom_template', 0)
                              ->setOrder('title', 'ASC');

        if ($manager->isMarketplaceDependentTemplate()) {
            $marketplace = $this->globalDataHelper->getValue('ebay_marketplace');
            $collection->addFieldToFilter('marketplace_id', $marketplace->getId());
        }

        return $collection;
    }

    //########################################

    public function getSwitcherJsObjectName()
    {
        $nick = ucfirst($this->getTemplateNick());

        return "ebayTemplate{$nick}SwitcherJsObject";
    }

    public function getSwitcherId()
    {
        $nick = $this->getTemplateNick();

        return "template_{$nick}";
    }

    public function getSwitcherName()
    {
        $nick = $this->getTemplateNick();

        return "template_{$nick}";
    }

    //########################################

    public function getButtonsHtml()
    {
        $html = $this->getChildHtml('save_custom_as_template');
        $nick = $this->getTemplateNick();

        return <<<HTML
<div id="template_{$nick}_buttons_container">
    {$html}
</div>
HTML;
    }

    //########################################

    protected function _beforeToHtml()
    {
        parent::_beforeToHtml();

        // ---------------------------------------
        $nick = $this->getTemplateNick();
        $data = [
            'class'   => 'action primary save-custom-template-' . $nick,
            'label'   => $this->__('Save as New Policy'),
            'onclick' => 'EbayListingTemplateSwitcherObj.customSaveAsTemplate(\'' . $nick . '\');',
        ];
        $buttonBlock = $this->getLayout()->createBlock(\Ess\M2ePro\Block\Adminhtml\Magento\Button::class)
                                         ->setData($data);
        $this->setChild('save_custom_as_template', $buttonBlock);
        // ---------------------------------------
    }

    //########################################

    protected function _toHtml()
    {
        $isTemplateModeTemplate = (int)$this->isTemplateModeTemplate();

        $this->js->add(
            <<<JS
    require([
        'Switcher/Initialization',
        'M2ePro/Ebay/Listing/Template/Switcher'
    ], function(){

        EbayListingTemplateSwitcherObj.updateEditVisibility('{$this->getTemplateNick()}');
        EbayListingTemplateSwitcherObj.updateButtonsVisibility('{$this->getTemplateNick()}');
        EbayListingTemplateSwitcherObj.updateTemplateLabelVisibility('{$this->getTemplateNick()}');

        $('{$this->getSwitcherId()}').observe('change', EbayListingTemplateSwitcherObj.change);

        if ({$isTemplateModeTemplate}) {
            $('{$this->getSwitcherId()}').simulate('change');
        }
    });
JS
        );

        return parent::_toHtml() .
            $this->getFormDataBlockHtml() .
            $this->getButtonsHtml();
    }

    //########################################
}
