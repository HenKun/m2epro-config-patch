<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Ebay\Listing\AutoAction\Mode\Category;

class Form extends \Ess\M2ePro\Block\Adminhtml\Listing\AutoAction\Mode\Category\AbstractForm
{

    public function __construct(
        \Ess\M2ePro\Block\Adminhtml\Magento\Context\Template $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Ess\M2ePro\Helper\Data $dataHelper,
        array $data = []
    ) {
        parent::__construct($context, $registry, $formFactory, $dataHelper, $data);
    }

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('listingAutoActionModeCategoryForm');
        // ---------------------------------------
    }

    protected function _prepareForm()
    {
        parent::_prepareForm();
        $form = $this->getForm();

        $addingMode = $form->getElement('adding_mode');
        $addingMode->addElementValues([
            \Ess\M2ePro\Model\Ebay\Listing::ADDING_MODE_ADD_AND_ASSIGN_CATEGORY => $this->__(
                'Add to the Listing and Assign eBay Category'
            )
        ]);

        return $this;
    }

    //########################################

    protected function _afterToHtml($html)
    {
        $this->jsPhp->addConstants(
            $this->dataHelper->getClassConstants(\Ess\M2ePro\Model\Ebay\Listing::class)
        );

        $this->js->add(<<<JS
            $('adding_mode')
                .observe('change', ListingAutoActionObj.categoryAddingMode)
                .simulate('change');
JS
        );

        return parent::_afterToHtml($html);
    }

    //########################################

    protected function _toHtml()
    {
        return parent::_toHtml() .
            '<div id="ebay_category_chooser"></div>';
    }

    //########################################
}
