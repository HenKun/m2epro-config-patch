<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Template\Category\Chooser\Specific\Form\Renderer;

use Magento\Backend\Block\Widget\Form\Renderer\Fieldset\Element as MagentoElement;
use Magento\Framework\Data\Form\Element\AbstractElement;

class Dictionary extends MagentoElement
{
    /** @var \Ess\M2ePro\Helper\Factory  */
    public $helperFactory;

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
        $this->setTemplate('ebay/template/category/chooser/specific/form/renderer/dictionary.phtml');
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
}
