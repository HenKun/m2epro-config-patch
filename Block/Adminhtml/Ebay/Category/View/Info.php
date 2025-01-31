<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Category\View;

class Info extends \Ess\M2ePro\Block\Adminhtml\Widget\Info
{
    /** @var \Ess\M2ePro\Helper\Component\Ebay\Category\Ebay */
    private $componentEbayCategoryEbay;
    /** @var \Ess\M2ePro\Helper\Magento\Attribute */
    private $magentoAttributeHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Component\Ebay\Category\Ebay $componentEbayCategoryEbay,
        \Ess\M2ePro\Helper\Magento\Attribute $magentoAttributeHelper,
        \Magento\Framework\Math\Random $random,
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        array $data = []
    ) {
        parent::__construct($random, $context, $data);

        $this->componentEbayCategoryEbay = $componentEbayCategoryEbay;
        $this->magentoAttributeHelper = $magentoAttributeHelper;
    }

    protected function _prepareLayout()
    {
        /** @var \Ess\M2ePro\Model\Ebay\Template\Category $template */
        $template = $this->activeRecordFactory->getObjectLoaded(
            'Ebay_Template_Category',
            $this->getData('template_id')
        );

        $category = $this->componentEbayCategoryEbay->getPath(
            $template->getData('category_id'),
            $template->getData('marketplace_id')
        );
        $category .= ' (' . $template->getData('category_id') . ')';

        if ($template->getCategoryMode() == \Ess\M2ePro\Model\Ebay\Template\Category::CATEGORY_MODE_ATTRIBUTE) {
            $category = $this->__('Magento Attribute') .' > '.
                $this->magentoAttributeHelper->getAttributeLabel($template->getData('category_attribute'));
        }

        $this->setInfo(
            [
                [
                    'label' => $this->__('Marketplace'),
                    'value' => $template->getMarketplace()->getTitle()
                ],
                [
                    'label' => $this->__('Category'),
                    'value' => $category
                ]
            ]
        );

        return parent::_prepareLayout();
    }

    //########################################

    /*
     * To get "Category" block in center of screen
     */
    public function getInfoPartWidth($index)
    {
        if ($index === 0) {
            return '33%';
        }

        return '66%';
    }

    public function getInfoPartAlign($index)
    {
        return 'left';
    }

    //########################################
}
