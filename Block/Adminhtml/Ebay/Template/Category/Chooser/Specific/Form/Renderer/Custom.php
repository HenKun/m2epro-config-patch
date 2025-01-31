<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Template\Category\Chooser\Specific\Form\Renderer;

use Magento\Backend\Block\Widget\Form\Renderer\Fieldset\Element as MagentoElement;
use Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * Class Ess\M2ePro\Block\Adminhtml\Ebay\Template\Category\Chooser\Specific\Form\Renderer\Custom
 */
class Custom extends MagentoElement
{
    /** @var \Ess\M2ePro\Helper\Factory  */
    public $helperFactory;

    /** @var \Magento\Framework\View\LayoutInterface  */
    public $layout;

    protected $element;

    /** @var \Ess\M2ePro\Helper\Module\Translation */
    public $translationHelper;

    public function __construct(
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Ess\M2ePro\Helper\Module\Translation $translationHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->helperFactory = $context->getHelperFactory();
        $this->layout = $context->getLayout();
        $this->setTemplate('ebay/template/category/chooser/specific/form/renderer/custom.phtml');
        $this->translationHelper = $translationHelper;
    }

    //########################################

    public function getElement()
    {
        return $this->element;
    }

    public function render(AbstractElement $element)
    {
        $this->element = $element;
        return $this->toHtml();
    }

    public function getRemoveCustomSpecificButtonHtml()
    {
        /** @var \Ess\M2ePro\Block\Adminhtml\Magento\Button $buttonBlock */
        $buttonBlock = $this->layout->createBlock(\Ess\M2ePro\Block\Adminhtml\Magento\Button::class)
            ->setData(
                [
                    'label'   => $this->translationHelper->__('Remove'),
                    'onclick' => 'EbayTemplateCategorySpecificsObj.removeCustomSpecific(this);',
                    'class'   => 'action remove_custom_specific_button'
                ]
            );

        return $buttonBlock->toHtml();
    }

    public function getAddCustomSpecificButtonHtml()
    {
        /** @var \Ess\M2ePro\Block\Adminhtml\Magento\Button $buttonBlock */
        $buttonBlock = $this->layout->createBlock(\Ess\M2ePro\Block\Adminhtml\Magento\Button::class)
            ->setData(
                [
                    'id'      => 'add_custom_specific_button',
                    'label'   => $this->translationHelper->__('Add Specific'),
                    'onclick' => 'EbayTemplateCategorySpecificsObj.addCustomSpecificRow();',
                    'class'   => 'action-primary add'
                ]
            );

        return $buttonBlock->toHtml();
    }
}
